<?php
/**
 * Copy the imported Hub data ("_hub" meta, from import-local.php) into the fields (plain post meta, see the modules'
 * data.php), so every business, offer, event and class opens in wp-admin with its details filled in and the pages
 * read them from there.
 *
 *   - every business: listing, contact and map pin; claimed ones also hours, timetable, logo, cover, photos (copied
 *     into the media library once), social links, news, notes
 *   - offers: their details and business (the imported link)
 *   - events: their details (date, times, venue, area, price, ages, photo …)
 *   - classes: their details, photo and Classes page card, and the business with the provider's name
 *   - Hub settings > Newsletter: today's wording, if it was never saved
 *
 * Run from the command line: php wp-local/hub/fields-migrate-local.php (import-local.php runs it too).
 * It overwrites the fields from the imported data, so wp-admin edits are replaced. Safe to run again.
 */
defined( 'ABSPATH' ) || exit;

function hub_fields_migrate() {
	global $wpdb;
	set_time_limit( 0 );
	$t0 = microtime( true );
	$wpdb->query( 'BEGIN' );

	hub_fields_migrate_newsletter();

	// ---------------------------------------------------------------- businesses
	$ids     = get_posts( array( 'post_type' => 'hub_business', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
	$by_name = array();
	foreach ( $ids as $n => $id ) {
		$x = get_post_meta( $id, '_hub', true );
		if ( is_array( $x ) ) {
			hub_businesses_db_save_fields( $id, hub_fields_from_record( $id, $x ) );
			if ( ! empty( $x['claimed']['name'] ) ) {
				$by_name[ hub_fields_key( $x['claimed']['name'] ) ] = $id;
			}
		}
		if ( 0 === ( $n + 1 ) % 500 ) {
			echo '  businesses: ' . ( $n + 1 ) . "\n";
		}
	}

	// ---------------------------------------------------------------- offers: details and business
	$offers = 0;
	foreach ( get_posts( array( 'post_type' => 'hub_offer', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $oid ) {
		$r = get_post_meta( $oid, '_hub', true );
		if ( is_array( $r ) ) {
			hub_offers_db_save_fields( $oid, hub_fields_offer_from_record( $oid, $r ) );
		}
		$biz = (int) get_post_meta( $oid, '_hub_business', true );
		if ( $biz ) {
			hub_offers_db_set_business( $oid, $biz );
			$offers++;
		}
	}

	// ---------------------------------------------------------------- classes: details, and business by provider name
	$classes = 0;
	$nolink  = array();
	foreach ( get_posts( array( 'post_type' => 'hub_class', 'post_status' => 'any', 'posts_per_page' => -1 ) ) as $p ) {
		$c    = get_post_meta( $p->ID, '_hub', true );
		$c    = is_array( $c ) ? $c : array();
		hub_events_db_save_class_fields( $p->ID, hub_fields_class_from_record( $p->ID, $c ) );
		$name = ! empty( $c['provider'] ) ? $c['provider'] : ( isset( $c['venue'] ) ? $c['venue'] : '' );
		$biz  = isset( $by_name[ hub_fields_key( $name ) ] ) ? $by_name[ hub_fields_key( $name ) ] : 0;
		if ( $biz ) {
			hub_events_db_set_class_business( $p->ID, $biz );
			$classes++;
		} else {
			$nolink[] = $p->post_title;
		}
	}

	// ---------------------------------------------------------------- events: details
	$events = get_posts( array( 'post_type' => 'hub_event', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
	foreach ( $events as $eid ) {
		$r = get_post_meta( $eid, '_hub', true );
		if ( is_array( $r ) ) {
			hub_events_db_save_event_fields( $eid, hub_fields_event_from_record( $eid, $r ) );
		}
	}

	$wpdb->query( 'COMMIT' );
	printf( "Filled the fields of %d events and every offer and class;\n", count( $events ) );
	printf( "Filled the fields of %d businesses; linked %d offers and %d classes (%d classes have no business on the Hub: %s) in %.0fs\n",
		count( $ids ), $offers, $classes, count( $nolink ), $nolink ? html_entity_decode( implode( ', ', $nolink ) ) : 'none', microtime( true ) - $t0 );
}

/** Name -> match key (entities and case ignored). */
function hub_fields_key( $name ) {
	return strtolower( trim( html_entity_decode( wp_strip_all_tags( (string) $name ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

/** The fields (meta key => value) for one imported business record. */
function hub_fields_from_record( $id, array $x ) {
	$c      = isset( $x['claimed'] ) && is_array( $x['claimed'] ) ? $x['claimed'] : null;
	$phones = isset( $x['phone'] ) ? (array) $x['phone'] : array();
	$v      = array(
		'biz_plan'         => isset( $x['tier'] ) ? $x['tier'] : 'unclaimed',
		'biz_claimed'      => $c ? '1' : '',
		'biz_checked'      => ! empty( $x['checked'] ) ? '1' : '',
		'biz_sub'          => isset( $x['sub'] ) ? $x['sub'] : '',
		'biz_address'      => $c ? $c['address'] : $x['address'],
		'biz_postcode'     => isset( $x['postcode'] ) ? $x['postcode'] : '',
		'biz_phone'        => $c ? $c['phone'] : ( $phones ? $phones[0] : '' ),
		'biz_phones_extra' => $c ? ( isset( $c['phones_extra'] ) ? $c['phones_extra'] : array() ) : array_slice( $phones, 1 ),
		'biz_website'      => ! empty( $x['web'] ) ? $x['web'][0] : ( $c && ! empty( $c['website'] ) ? $c['website'] : '' ),
		'biz_email'        => ! empty( $x['email'] ) ? $x['email'][0] : '',
		'biz_lat'          => isset( $x['lat'] ) ? (string) $x['lat'] : '',
		'biz_lng'          => isset( $x['lng'] ) ? (string) $x['lng'] : '',
	);
	if ( ! $c ) {
		return $v;
	}
	$get = function ( $k ) use ( $c ) {
		return isset( $c[ $k ] ) ? $c[ $k ] : '';
	};
	$v['biz_tagline']       = $get( 'tagline' );
	$v['biz_icon']          = $get( 'icon' );
	$v['biz_claimed_since'] = $get( 'claimed' );
	$v['biz_botw']          = ! empty( $c['botw'] ) ? '1' : '';
	foreach ( array( 'verified_note', 'seo_title', 'service_area', 'hours_note', 'status', 'schedule_title', 'schedule_note', 'cover_bg' ) as $k ) {
		$v[ 'biz_' . $k ] = $get( $k );
	}
	$v['biz_hours']           = ! empty( $c['hours'] ) ? array_values( $c['hours'] ) : array();
	$v['biz_schedule']        = isset( $c['schedule'] ) ? array_values( $c['schedule'] ) : array();
	$v['biz_other_locations'] = isset( $c['other_locations'] ) ? array_values( $c['other_locations'] ) : array();
	$v['biz_news']            = isset( $c['news'] ) ? array_values( $c['news'] ) : array();
	foreach ( isset( $c['social_urls'] ) ? $c['social_urls'] : array() as $s => $url ) {
		if ( in_array( $s, HUB_BUSINESSES_SOCIAL, true ) ) {
			$v[ 'biz_' . $s ] = $url;
		}
	}

	// Logo, cover and photos: theme images copied into the media library (once)
	$name = html_entity_decode( $x['name'], ENT_QUOTES, 'UTF-8' );
	$v['biz_logo']  = ! empty( $c['logo'] ) ? (string) hub_fields_import_image( $c['logo'], $name . ' logo', $id ) : '';
	$v['biz_cover'] = ! empty( $c['cover'] ) ? (string) hub_fields_import_image( $c['cover'], isset( $c['cover_alt'] ) ? $c['cover_alt'] : $name, $id ) : '';
	$gallery        = array();
	foreach ( isset( $c['images'] ) ? $c['images'] : array() as $k => $path ) {
		$aid = hub_fields_import_image( $path, isset( $c['image_alts'][ $k ] ) ? $c['image_alts'][ $k ] : '', $id );   // no description: empty alt
		if ( $aid ) {
			$gallery[] = $aid;
		}
	}
	$v['biz_gallery'] = $gallery;
	return array_map( function ( $val ) { return '0' === $val ? '' : $val; }, $v );
}

/** A theme image ("/assets/img/business/x.jpg") as a media library attachment, added once; a full URL is looked up. */
function hub_fields_import_image( $path, $alt, $parent ) {
	if ( preg_match( '#^https?://#', (string) $path ) ) {
		// Already in the media library (a photo sent from the website): find its attachment by its address, the
		// address without a size ("-1024x683"), or the post's featured image
		$aid = attachment_url_to_postid( $path );
		if ( ! $aid ) {
			$aid = attachment_url_to_postid( preg_replace( '/-\d+x\d+(\.\w+)$/', '$1', $path ) );
		}
		return $aid ? (int) $aid : (int) get_post_thumbnail_id( $parent );
	}
	$keep  = null === $alt;   // null: leave an existing image's alt text as it is
	$alt   = html_entity_decode( (string) $alt, ENT_QUOTES, 'UTF-8' );
	$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_hub_src', 'meta_value' => $path ) );
	if ( $found ) {
		if ( ! $keep ) {
			update_post_meta( $found[0], '_wp_attachment_image_alt', $alt );
		}
		return (int) $found[0];
	}
	$src = get_template_directory() . '/assets/' . preg_replace( '#^/assets/#', '', $path );
	if ( ! is_readable( $src ) ) {
		echo "  missing image {$path}\n";
		return 0;
	}
	// The same file may already be in uploads (it comes with git): use it rather than adding a "-1" copy
	$dir  = wp_upload_dir();
	$same = trailingslashit( $dir['path'] ) . wp_basename( $src );
	if ( is_readable( $same ) && md5_file( $same ) === md5_file( $src ) ) {
		$up = array( 'file' => $same );
	} else {
		$up = wp_upload_bits( wp_basename( $src ), null, file_get_contents( $src ) );
		if ( ! empty( $up['error'] ) ) {
			echo "  could not copy {$path}: {$up['error']}\n";
			return 0;
		}
	}
	$type = wp_check_filetype( $up['file'] );
	$aid  = wp_insert_attachment( array(
		'post_mime_type' => $type['type'],
		'post_title'     => '' !== $alt ? $alt : html_entity_decode( get_the_title( $parent ), ENT_QUOTES, 'UTF-8' ),
		'post_status'    => 'inherit',
	), $up['file'], $parent );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $up['file'] ) );
	update_post_meta( $aid, '_wp_attachment_image_alt', $alt );
	update_post_meta( $aid, '_hub_src', $path );
	return $aid;
}

/** Hub settings > Newsletter: today's wording, unless the page has been saved already. */
function hub_fields_migrate_newsletter() {
	if ( false === get_option( 'hub_newsletter', false ) ) {
		update_option( 'hub_newsletter', hub_newsletter_defaults(), false );
	}
}

/** Text from a record as it reads (entities such as &pound; and &rsquo; as their characters). */
function hub_fields_text( $v ) {
	return html_entity_decode( (string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/** An offer's fields from its imported record. */
function hub_fields_offer_from_record( $id, array $r ) {
	return array(
		'offer_value'    => isset( $r['value'] ) ? hub_fields_text( $r['value'] ) : '',
		'offer_end'      => isset( $r['end'] ) ? (string) $r['end'] : '',
		'offer_featured' => ! empty( $r['featured'] ) ? '1' : '',
		'offer_sub'      => ! empty( $r['sub'] ) ? '1' : '',
		'offer_code'     => isset( $r['code'] ) ? (string) $r['code'] : '',
		'offer_redeem'   => isset( $r['redeem'] ) ? hub_fields_text( $r['redeem'] ) : '',
		'offer_terms'    => array_map( 'hub_fields_text', isset( $r['terms'] ) ? array_values( (array) $r['terms'] ) : array() ),
		'offer_icon'     => isset( $r['icon'] ) ? (string) $r['icon'] : '',
		'offer_position' => (string) (int) get_post_meta( $id, '_hub_index', true ),
	);
}

/** An event's fields from its imported record. */
function hub_fields_event_from_record( $id, array $r ) {
	$t = function ( $k ) use ( $r ) {
		return isset( $r[ $k ] ) ? hub_fields_text( $r[ $k ] ) : '';
	};
	return array(
		'event_date'     => isset( $r['date'] ) ? (string) $r['date'] : substr( (string) $r['start'], 0, 10 ),
		'event_start'    => substr( (string) $r['start'], 11, 5 ),
		'event_end'      => substr( (string) $r['end'], 11, 5 ),
		'event_time'     => $t( 'time' ),
		'event_note'     => $t( 'note' ),
		'event_cat'      => $t( 'cat' ),
		'event_venue'    => $t( 'venue' ),
		'event_area'     => $t( 'area' ),
		'event_price'    => $t( 'price' ),
		'event_age'      => $t( 'age' ),
		'event_icon'     => $t( 'icon' ),
		'event_image'    => ! empty( $r['image'] ) ? (string) hub_fields_import_image( $r['image'], null, $id ) : '',
		'event_featured' => ! empty( $r['featured'] ) ? '1' : '',
		'event_sample'   => ! empty( $r['sample'] ) ? '1' : '',
		'event_org'      => $t( 'org' ),
	);
}

/** A class's fields from its imported record. */
function hub_fields_class_from_record( $id, array $r ) {
	$t    = function ( $a, $k ) {
		return isset( $a[ $k ] ) ? hub_fields_text( $a[ $k ] ) : '';
	};
	$real = ! empty( $r['real'] ) && is_array( $r['real'] ) ? $r['real'] : null;
	$card = isset( $r['card'] ) && is_array( $r['card'] ) ? $r['card'] : array();
	$day  = isset( $r['day'] ) && null !== $r['day'] && '' !== $r['day'] ? (string) (int) $r['day'] : '';
	return array(
		'class_page'          => ( ! isset( $r['page'] ) || $r['page'] ) ? '1' : '',
		'class_day'           => $day,
		'class_time'          => $t( $r, 'time' ),
		'class_sort'          => isset( $r['sort'] ) ? (string) $r['sort'] : '',
		'class_order'         => (string) (int) ( isset( $r['order'] ) ? $r['order'] : 0 ),
		'class_type'          => $t( $r, 'type' ),
		'class_venue'         => $t( $r, 'venue' ),
		'class_area'          => $t( $r, 'area' ),
		'class_price'         => $t( $r, 'price' ),
		'class_age'           => $t( $r, 'age' ),
		'class_icon'          => $t( $r, 'icon' ),
		'class_featured'      => ! empty( $r['featured'] ) ? '1' : '',
		'class_provider'      => $t( $r, 'provider' ),
		'class_sample'        => $real ? '' : '1',
		'class_image'         => $real && ! empty( $real['img'] ) ? (string) hub_fields_import_image( $real['img'], null, $id ) : '',
		'class_image_alt'     => $real ? $t( $real, 'alt' ) : '',
		'class_image_fit'     => $real && isset( $real['fit'] ) && 'logo' === $real['fit'] ? 'logo' : '',
		'class_street'        => $real ? $t( $real, 'street' ) : '',
		'class_postcode'      => $real ? $t( $real, 'postcode' ) : '',
		'class_note'          => $real ? $t( $real, 'note' ) : '',
		'class_card_order'    => isset( $card['order'] ) ? (string) (int) $card['order'] : '',
		'class_card_text'     => $t( $card, 'text' ),
		'class_card_when'     => $t( $card, 'when' ),
		'class_card_price'    => $t( $card, 'price' ),
		'class_card_age'      => $t( $card, 'age' ),
		'class_card_provider' => $t( $card, 'provider' ),
	);
}

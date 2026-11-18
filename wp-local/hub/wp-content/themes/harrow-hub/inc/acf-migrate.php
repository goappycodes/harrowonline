<?php
/**
 * Copy the imported Hub data ("_hub" meta, from import-local.php) into the ACF fields, so every business opens in
 * wp-admin with its details filled in and the pages read them from there.
 *
 *   - every business: listing, contact and map pin; claimed ones also hours, timetable, logo, cover, photos (copied
 *     into the media library once), social links, news, notes
 *   - offers: their business (the imported link); classes: the business with the provider's name
 *   - Hub settings > Newsletter: today's wording, if it was never saved
 *
 * Run from the command line: php wp-local/hub/acf-migrate-local.php (import-local.php runs it too). Safe to run again.
 */
defined( 'ABSPATH' ) || exit;

function hub_acf_migrate() {
	if ( ! function_exists( 'update_field' ) ) {
		echo "ACF Pro is not active: nothing copied.\n";
		return;
	}
	global $wpdb;
	set_time_limit( 0 );
	kses_remove_filters();
	$t0 = microtime( true );
	$wpdb->query( 'BEGIN' );

	hub_acf_migrate_newsletter();

	// ---------------------------------------------------------------- businesses
	$ids     = get_posts( array( 'post_type' => 'hub_business', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
	$by_name = array();
	foreach ( $ids as $n => $id ) {
		$x = get_post_meta( $id, '_hub', true );
		if ( is_array( $x ) ) {
			hub_acf_migrate_business( $id, $x );
			if ( ! empty( $x['claimed']['name'] ) ) {
				$by_name[ hub_acf_key( $x['claimed']['name'] ) ] = $id;
			}
		}
		if ( 0 === ( $n + 1 ) % 500 ) {
			echo '  businesses: ' . ( $n + 1 ) . "\n";
		}
	}

	// ---------------------------------------------------------------- offers -> business
	$offers = array();
	foreach ( get_posts( array( 'post_type' => 'hub_offer', 'post_status' => 'any', 'posts_per_page' => -1, 'meta_key' => '_hub_index', 'orderby' => 'meta_value_num', 'order' => 'ASC' ) ) as $p ) {
		$biz = (int) get_post_meta( $p->ID, '_hub_business', true );
		if ( $biz ) {
			update_field( 'field_hub_offer_business', $biz, $p->ID );
			$offers[ $biz ][] = $p->ID;
		}
	}
	foreach ( $offers as $biz => $list ) {
		update_field( 'field_hub_biz_offers', $list, $biz );
	}

	// ---------------------------------------------------------------- classes -> business (by provider name)
	$classes = array();
	$nolink  = array();
	foreach ( get_posts( array( 'post_type' => 'hub_class', 'post_status' => 'any', 'posts_per_page' => -1, 'meta_key' => '_hub_order', 'orderby' => 'meta_value_num', 'order' => 'ASC' ) ) as $p ) {
		$c    = get_post_meta( $p->ID, '_hub', true );
		$name = ! empty( $c['provider'] ) ? $c['provider'] : ( isset( $c['venue'] ) ? $c['venue'] : '' );
		$biz  = isset( $by_name[ hub_acf_key( $name ) ] ) ? $by_name[ hub_acf_key( $name ) ] : 0;
		if ( $biz ) {
			update_field( 'field_hub_class_business', $biz, $p->ID );
			$classes[ $biz ][] = $p->ID;
		} else {
			$nolink[] = $p->post_title;
		}
	}
	foreach ( $classes as $biz => $list ) {
		update_field( 'field_hub_biz_classes', $list, $biz );
	}

	$wpdb->query( 'COMMIT' );
	printf( "Copied %d businesses into ACF; linked %d offers and %d classes (%d classes have no business on the Hub: %s) in %.0fs\n",
		count( $ids ), array_sum( array_map( 'count', $offers ) ), array_sum( array_map( 'count', $classes ) ),
		count( $nolink ), $nolink ? html_entity_decode( implode( ', ', $nolink ) ) : 'none', microtime( true ) - $t0 );
}

/** Name -> match key (entities and case ignored). */
function hub_acf_key( $name ) {
	return strtolower( trim( html_entity_decode( wp_strip_all_tags( (string) $name ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

/** Set a field only when there is something to store (empty fields read as empty anyway). */
function hub_acf_set( $post_id, $name, $value ) {
	if ( null === $value || '' === $value || array() === $value || false === $value ) {
		return;
	}
	update_field( 'field_hub_' . $name, $value, $post_id );
}

function hub_acf_migrate_business( $id, array $x ) {
	$c       = isset( $x['claimed'] ) && is_array( $x['claimed'] ) ? $x['claimed'] : null;
	$phones  = isset( $x['phone'] ) ? (array) $x['phone'] : array();
	$extra   = $c ? ( isset( $c['phones_extra'] ) ? $c['phones_extra'] : array() ) : array_slice( $phones, 1 );

	hub_acf_set( $id, 'biz_plan', isset( $x['tier'] ) ? $x['tier'] : 'unclaimed' );
	hub_acf_set( $id, 'biz_claimed', $c ? 1 : 0 );
	hub_acf_set( $id, 'biz_checked', ! empty( $x['checked'] ) ? 1 : 0 );
	hub_acf_set( $id, 'biz_sub', isset( $x['sub'] ) ? $x['sub'] : '' );
	hub_acf_set( $id, 'biz_address', $c ? $c['address'] : $x['address'] );
	hub_acf_set( $id, 'biz_postcode', isset( $x['postcode'] ) ? $x['postcode'] : '' );
	hub_acf_set( $id, 'biz_phone', $c ? $c['phone'] : ( $phones ? $phones[0] : '' ) );
	hub_acf_set( $id, 'biz_phones_extra', array_map( function ( $p ) { return array( 'field_hub_biz_phone_extra' => $p ); }, $extra ) );
	hub_acf_set( $id, 'biz_website', ! empty( $x['web'] ) ? $x['web'][0] : ( $c && ! empty( $c['website'] ) ? $c['website'] : '' ) );
	hub_acf_set( $id, 'biz_email', ! empty( $x['email'] ) ? $x['email'][0] : '' );
	hub_acf_set( $id, 'biz_lat', isset( $x['lat'] ) ? $x['lat'] : null );
	hub_acf_set( $id, 'biz_lng', isset( $x['lng'] ) ? $x['lng'] : null );

	if ( $c ) {
		$get = function ( $k ) use ( $c ) {
			return isset( $c[ $k ] ) ? $c[ $k ] : '';
		};
		hub_acf_set( $id, 'biz_tagline', $get( 'tagline' ) );
		hub_acf_set( $id, 'biz_icon', $get( 'icon' ) );
		hub_acf_set( $id, 'biz_claimed_since', $get( 'claimed' ) );
		hub_acf_set( $id, 'biz_botw', ! empty( $c['botw'] ) ? 1 : 0 );
		foreach ( array( 'verified_note', 'seo_title', 'service_area', 'hours_note', 'status', 'schedule_title', 'schedule_note', 'cover_bg' ) as $k ) {
			hub_acf_set( $id, 'biz_' . $k, $get( $k ) );
		}
		if ( ! empty( $c['hours'] ) ) {
			$hours = array();
			foreach ( HUB_HOUR_KEYS as $i => $k ) {
				$hours[ 'field_hub_biz_hours_' . $k ] = isset( $c['hours'][ $i ] ) ? $c['hours'][ $i ] : '';
			}
			update_field( 'field_hub_biz_hours', $hours, $id );
		}
		hub_acf_set( $id, 'biz_schedule', array_map( function ( $r ) { return array( 'field_hub_biz_sch_day' => $r[0], 'field_hub_biz_sch_text' => $r[1] ); }, isset( $c['schedule'] ) ? $c['schedule'] : array() ) );
		hub_acf_set( $id, 'biz_other_locations', array_map( function ( $l ) {
			return array( 'field_hub_biz_loc_label' => $l['label'], 'field_hub_biz_loc_name' => $l['name'], 'field_hub_biz_loc_address' => $l['address'] );
		}, isset( $c['other_locations'] ) ? $c['other_locations'] : array() ) );
		foreach ( isset( $c['social_urls'] ) ? $c['social_urls'] : array() as $s => $url ) {
			if ( in_array( $s, HUB_SOCIAL, true ) ) {
				hub_acf_set( $id, 'biz_' . $s, $url );
			}
		}
		hub_acf_set( $id, 'biz_news', array_map( function ( $n ) {
			return array( 'field_hub_biz_news_title' => $n['title'], 'field_hub_biz_news_url' => $n['url'], 'field_hub_biz_news_cat' => $n['cat'], 'field_hub_biz_news_date' => $n['date'] );
		}, isset( $c['news'] ) ? $c['news'] : array() ) );

		// Logo, cover and photos: theme images copied into the media library (once)
		$name = html_entity_decode( $x['name'], ENT_QUOTES, 'UTF-8' );
		if ( ! empty( $c['logo'] ) ) {
			hub_acf_set( $id, 'biz_logo', hub_acf_import_image( $c['logo'], $name . ' logo', $id ) );
		}
		if ( ! empty( $c['cover'] ) ) {
			hub_acf_set( $id, 'biz_cover', hub_acf_import_image( $c['cover'], isset( $c['cover_alt'] ) ? $c['cover_alt'] : $name, $id ) );
		}
		$gallery = array();
		foreach ( isset( $c['images'] ) ? $c['images'] : array() as $k => $path ) {
			$alt = isset( $c['image_alts'][ $k ] ) ? $c['image_alts'][ $k ] : '';   // no description: empty alt, as before
			$aid = hub_acf_import_image( $path, $alt, $id );
			if ( $aid ) {
				$gallery[] = $aid;
			}
		}
		hub_acf_set( $id, 'biz_gallery', $gallery );
	}

	update_post_meta( $id, '_hub_acf', 1 );
}

/** A theme image ("/assets/img/business/x.jpg") as a media library attachment; the same file is only added once. */
function hub_acf_import_image( $path, $alt, $parent ) {
	$alt   = html_entity_decode( (string) $alt, ENT_QUOTES, 'UTF-8' );
	$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_hub_src', 'meta_value' => $path ) );
	if ( $found ) {
		update_post_meta( $found[0], '_wp_attachment_image_alt', $alt );
		return (int) $found[0];
	}
	$src = get_template_directory() . '/assets/' . preg_replace( '#^/assets/#', '', $path );
	if ( ! is_readable( $src ) ) {
		echo "  missing image {$path}\n";
		return 0;
	}
	$up = wp_upload_bits( wp_basename( $src ), null, file_get_contents( $src ) );
	if ( ! empty( $up['error'] ) ) {
		echo "  could not copy {$path}: {$up['error']}\n";
		return 0;
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

/** Hub settings > Newsletter: today's wording, unless someone has saved the page already. */
function hub_acf_migrate_newsletter() {
	if ( false !== get_option( 'options_nl_title', false ) ) {
		return;
	}
	update_field( 'field_hub_nl_show', 1, 'option' );
	update_field( 'field_hub_nl_title', 'Harrow in your inbox, every morning', 'option' );
	update_field( 'field_hub_nl_text', 'The day’s local news, what’s on and the best Local Offers. Free, from Harrow Online Newsletter.', 'option' );
	update_field( 'field_hub_nl_editions', array_map( function ( $e ) {
		return array( 'field_hub_nl_ed_value' => $e[0], 'field_hub_nl_ed_label' => $e[1], 'field_hub_nl_ed_desc' => $e[2], 'field_hub_nl_ed_checked' => $e[3] ? 1 : 0 );
	}, hub_newsletter_default_editions() ), 'option' );
	update_field( 'field_hub_nl_placeholder', 'Your email address', 'option' );
	update_field( 'field_hub_nl_button', 'Sign up free', 'option' );
	update_field( 'field_hub_nl_action', 'https://harrowonline.org/wp-json/ho/v1/subscribe', 'option' );
	update_field( 'field_hub_nl_source', 'hub-strip', 'option' );
}

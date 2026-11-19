<?php
/**
 * wp-admin: the "Homepage" box on the homepage's Page (Pages > Home), a native meta box with tabs. Every text, link,
 * list and switch on the front page. Read and saved through the home module (hub_home_fields / hub_home_save_fields);
 * an emptied box goes back to the default wording.
 */
defined( 'ABSPATH' ) || exit;

/** The homepage fields, by tab. */
function hub_admin_home_fields() {
	$c     = hub_home_choices();
	$link  = 'A Hub page such as events/classes/ or a full web address.';
	$hide  = function ( $key, $what ) {
		return array( 'key' => $key, 'label' => 'Hide ' . $what, 'type' => 'toggle', 'width' => 100 );
	};
	$head  = function ( $p, $title, $sub = true, $link_label = true ) use ( $link ) {
		$out = array( array( 'key' => "home_{$p}_title", 'label' => 'Heading', 'type' => 'text', 'width' => $sub ? 40 : 100 ) );
		if ( $sub ) {
			$out[] = array( 'key' => "home_{$p}_sub", 'label' => 'Text under the heading', 'type' => 'text', 'width' => 60 );
		}
		if ( $link_label ) {
			$out[] = array( 'key' => "home_{$p}_link_label", 'label' => 'Link text (top right)', 'type' => 'text', 'width' => 40 );
			$out[] = array( 'key' => "home_{$p}_link", 'label' => 'Link goes to', 'type' => 'text', 'width' => 60, 'help' => $link );
		}
		return $out;
	};
	return array(
		'hero' => array( 'First screen', array(
			array( 'key' => 'home_hero_eyebrow', 'label' => 'Small line above the heading', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'home_hero_title', 'label' => 'Heading', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'home_hero_title_em', 'label' => 'End of the heading (in red)', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'home_hero_lead', 'label' => 'Text under the heading', 'type' => 'textarea' ),
			array( 'key' => 'home_search_placeholder', 'label' => 'Search box placeholder', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'home_search_button', 'label' => 'Search button', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'home_popular_label', 'label' => '“Popular” label', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'home_popular', 'label' => 'Popular links (under the search)', 'type' => 'list', 'add' => 'Add a link', 'help' => 'On phones only the first two fit. Links: ' . $link,
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'Goes to' ) ) ),
			array( 'key' => 'home_tiles', 'label' => 'The big choices (right of the search)', 'type' => 'list', 'add' => 'Add a choice',
				'help' => 'Four fit best. Icon: store, calendar, repeat, tag, food, people, star, gift… Links: ' . $link,
				'cols' => array( 'icon' => array( 'Icon' ), 'title' => array( 'Title' ), 'text' => array( 'Short text' ), 'link' => array( 'Goes to' ) ) ),
			array( 'key' => 'home_add_buttons', 'label' => 'Buttons under the choices', 'type' => 'list', 'add' => 'Add a button',
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'Goes to' ) ) ),
			$hide( 'home_ticker_hide', 'the “Just joined” strip' ),
		) ),
		'weekend' => array( 'This weekend', array_merge(
			array( $hide( 'home_weekend_hide', 'this section' ) ),
			$head( 'weekend', '', false ),
			array(
				array( 'key' => 'home_weekend_offer', 'label' => 'Offer shown here', 'type' => 'select', 'choices' => array( '' => '— Automatic: the first live offer —' ) + $c['offers'],
					'help' => 'If the picked offer ends, the first live offer shows instead. The two events are this weekend’s, featured ones first.' ),
				array( 'key' => 'note', 'type' => 'note', 'label' => '<strong>Newsletter card</strong> (beside the events)' ),
				$hide( 'home_nl_hide', 'the newsletter card' ),
				array( 'key' => 'home_nl_title', 'label' => 'Card heading', 'type' => 'text', 'width' => 60 ),
				array( 'key' => 'home_nl_text', 'label' => 'Card text', 'type' => 'text', 'width' => 40 ),
				array( 'key' => 'home_nl_editions', 'label' => 'Newsletters to tick', 'type' => 'list', 'add' => 'Add a newsletter',
					'help' => '<strong>Value</strong> is what the sign-up form sends (daily, weekend, business).',
					'cols' => array( 'value' => array( 'Value' ), 'label' => array( 'Name' ), 'note' => array( 'Small note' ), 'checked' => array( 'Ticked', 'check' ) ) ),
				array( 'key' => 'home_nl_placeholder', 'label' => 'Email box placeholder', 'type' => 'text', 'width' => 50 ),
				array( 'key' => 'home_nl_button', 'label' => 'Button', 'type' => 'text', 'width' => 50 ),
			)
		) ),
		'offers' => array( 'Offers and numbers', array_merge(
			array( array( 'key' => 'note', 'type' => 'note', 'label' => '<strong>More Local Offers</strong>: the next six live offers (not the one in This weekend).' ), $hide( 'home_offers_hide', 'More Local Offers' ) ),
			$head( 'offers', '' ),
			array(
				array( 'key' => 'note', 'type' => 'note', 'label' => '<strong>The numbers</strong>: the counts are worked out from the listings. Use {areas} for the number of areas.' ),
				$hide( 'home_stats_hide', 'the numbers' ),
				array( 'key' => 'home_stat_offers_label', 'label' => 'Offers: label', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_offers_note', 'label' => 'Offers: note', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_events_label', 'label' => 'Events: label', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_events_note', 'label' => 'Events: note', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_classes_label', 'label' => 'Classes: label', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_classes_note', 'label' => 'Classes: note', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_biz_label', 'label' => 'Businesses: label', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_biz_note', 'label' => 'Businesses: note', 'type' => 'text', 'width' => 25 ),
			)
		) ),
		'businesses' => array( 'Featured and categories', array_merge(
			array( $hide( 'home_featured_hide', 'Featured local businesses' ) ),
			$head( 'featured', '' ),
			array(
				array( 'key' => 'home_featured_pick', 'label' => 'Businesses to feature (up to 4)', 'type' => 'businesses', 'max' => HUB_HOME_FEATURED,
					'help' => 'Leave empty for the automatic choice: businesses on the Featured and Local Partner plans, one per category. Drag to change the order.' ),
				array( 'key' => 'note', 'type' => 'note', 'label' => '<strong>Popular categories</strong>: business counts are worked out from the listings.' ),
				$hide( 'home_cats_hide', 'Popular categories' ),
			),
			$head( 'cats', '' ),
			array(
				array( 'key' => 'home_cats_pick', 'label' => 'Categories to show (up to 6, most businesses first)', 'type' => 'checks', 'choices' => $c['categories'] ),
			)
		) ),
		'areas' => array( 'Areas', array_merge(
			array( array( 'key' => 'note', 'type' => 'note', 'label' => '<strong>Browse by area</strong>: every area, with its business count and bar worked out from the listings.' ), $hide( 'home_areas_hide', 'Browse by area' ) ),
			$head( 'areas', '', true, false ),
			array(
				array( 'key' => 'note', 'type' => 'note', 'label' => '<strong>“Know a hidden gem?”</strong> box' ),
				$hide( 'home_gem_hide', 'the hidden gem box' ),
				array( 'key' => 'home_gem_eyebrow', 'label' => 'Small line', 'type' => 'text', 'width' => 34 ),
				array( 'key' => 'home_gem_title', 'label' => 'Heading', 'type' => 'text', 'width' => 66 ),
				array( 'key' => 'home_gem_text', 'label' => 'Text', 'type' => 'textarea' ),
				array( 'key' => 'home_gem_button', 'label' => 'Button', 'type' => 'text', 'width' => 40 ),
				array( 'key' => 'home_gem_link', 'label' => 'Button goes to', 'type' => 'text', 'width' => 60, 'help' => $link ),
			)
		) ),
		'biz' => array( 'For businesses', array(
			$hide( 'home_biz_hide', 'this section' ),
			array( 'key' => 'home_biz_eyebrow', 'label' => 'Small line', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'home_biz_title', 'label' => 'Heading', 'type' => 'text', 'width' => 66 ),
			array( 'key' => 'home_biz_lead', 'label' => 'Text', 'type' => 'textarea' ),
			array( 'key' => 'home_biz_buttons', 'label' => 'Buttons (the first is red)', 'type' => 'list', 'add' => 'Add a button',
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'Goes to' ) ) ),
			array( 'key' => 'home_biz_note', 'label' => 'Note under the buttons', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'home_biz_note_link_label', 'label' => 'Note link text', 'type' => 'text', 'width' => 30 ),
			array( 'key' => 'home_biz_note_link', 'label' => 'Note link goes to', 'type' => 'text', 'width' => 30 ),
			array( 'key' => 'home_biz_steps', 'label' => 'Steps (numbered)', 'type' => 'list', 'add' => 'Add a step',
				'cols' => array( 'title' => array( 'Title' ), 'text' => array( 'Text' ) ) ),
		) ),
		'seo' => array( 'Search engines', array(
			array( 'key' => 'home_seo_title', 'label' => 'Page title', 'type' => 'text', 'help' => 'Shown in the browser tab and in Google results.' ),
			array( 'key' => 'home_seo_description', 'label' => 'Description', 'type' => 'textarea', 'help' => 'The short text under the title in Google results (about 155 characters).' ),
		) ),
	);
}

add_action( 'add_meta_boxes_page', function ( WP_Post $post ) {
	if ( $post->ID && $post->ID === hub_home_page_id() ) {
		add_meta_box( 'hub-home', 'Homepage', 'hub_admin_home_box', 'page', 'normal', 'high' );
	}
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && isset( $_GET['post'] ) && (int) $_GET['post'] === hub_home_page_id() ) { // phpcs:ignore WordPress.Security.NonceVerification
		hub_admin_enqueue();
	}
} );

/** The box. */
function hub_admin_home_box( WP_Post $post ) {
	$values = hub_home_fields();
	wp_nonce_field( 'hub_home_save', 'hub_home_nonce' );
	echo '<p class="hub-f__help" style="margin:4px 12px 0">The front page’s text, links and sections. Empty a box to go back to the default wording. The events, offers, businesses, categories and areas come from the listings.</p>';
	echo '<div class="hub-box" data-hub-box><nav class="hub-tabs" role="tablist">';
	$first = true;
	foreach ( hub_admin_home_fields() as $slug => $tab ) {
		echo '<button type="button" role="tab" class="hub-tabs__tab' . ( $first ? ' is-active' : '' ) . '" data-hub-tab="' . esc_attr( $slug ) . '">' . esc_html( $tab[0] ) . '</button>';
		$first = false;
	}
	echo '</nav>';
	$first = true;
	foreach ( hub_admin_home_fields() as $slug => $tab ) {
		echo '<div class="hub-panel" data-hub-panel="' . esc_attr( $slug ) . '"' . ( $first ? '' : ' hidden' ) . '><div class="hub-grid">';
		foreach ( $tab[1] as $f ) {
			$v = 'note' === $f['type'] ? '' : $values[ $f['key'] ];
			if ( 'list' === $f['type'] && is_array( $v ) ) {
				$v = array_map( function ( $row ) { return is_array( $row ) ? $row : array(); }, $v );
			}
			hub_admin_field( $f, $v, 'hub[' . $f['key'] . ']' );
		}
		echo '</div></div>';
		$first = false;
	}
	echo '</div>';
}

add_action( 'save_post_page', function ( $post_id ) {
	if ( $post_id !== hub_home_page_id() || ! isset( $_POST['hub_home_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hub_home_nonce'] ), 'hub_home_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fields = array();
	foreach ( hub_admin_home_fields() as $tab ) {
		$fields = array_merge( $fields, $tab[1] );
	}
	$sent  = isset( $_POST['hub'] ) && is_array( $_POST['hub'] ) ? $_POST['hub'] : array(); // phpcs:ignore -- cleaned field by field
	$clean = hub_admin_clean_all( $fields, $sent );
	$clean['home_cats_pick'] = array_slice( $clean['home_cats_pick'], 0, HUB_HOME_CATEGORIES );
	hub_home_save_fields( $clean );
} );

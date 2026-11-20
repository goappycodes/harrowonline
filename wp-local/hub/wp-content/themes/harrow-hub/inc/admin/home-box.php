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
	$link  = 'A Hub path (events/classes/) or a full URL.';
	$hide  = function ( $key, $what ) {
		return array( 'key' => $key, 'label' => 'Hide ' . $what, 'type' => 'toggle', 'width' => 100 );
	};
	$head  = function ( $p, $title, $sub = true, $link_label = true ) use ( $link ) {
		$out = array( array( 'key' => "home_{$p}_title", 'label' => 'Title', 'type' => 'text', 'width' => $sub ? 40 : 100 ) );
		if ( $sub ) {
			$out[] = array( 'key' => "home_{$p}_sub", 'label' => 'Subtitle', 'type' => 'text', 'width' => 60 );
		}
		if ( $link_label ) {
			$out[] = array( 'key' => "home_{$p}_link_label", 'label' => 'Link text', 'type' => 'text', 'width' => 40 );
			$out[] = array( 'key' => "home_{$p}_link", 'label' => 'Link URL', 'type' => 'text', 'width' => 60, 'help' => $link );
		}
		return $out;
	};
	return array(
		'hero' => array( 'Hero', array(
			array( 'key' => 'home_hero_eyebrow', 'label' => 'Label', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'home_hero_title', 'label' => 'Title', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'home_hero_title_em', 'label' => 'Title end (red)', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'home_hero_lead', 'label' => 'Intro', 'type' => 'textarea' ),
			array( 'key' => 'home_search_placeholder', 'label' => 'Search placeholder', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'home_search_button', 'label' => 'Search button', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'home_popular_label', 'label' => 'Popular label', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'home_popular', 'label' => 'Popular links', 'type' => 'list', 'add' => 'Add a link', 'help' => 'Phones show the first two. Links: ' . $link,
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'URL' ) ) ),
			array( 'key' => 'home_tiles', 'label' => 'Tiles', 'type' => 'list', 'add' => 'Add a choice',
				'help' => 'Four fit best. Icons: store, calendar, repeat, tag, food… Links: ' . $link,
				'cols' => array( 'icon' => array( 'Icon' ), 'title' => array( 'Title' ), 'text' => array( 'Text' ), 'link' => array( 'URL' ) ) ),
			array( 'key' => 'home_add_buttons', 'label' => 'Buttons', 'type' => 'list', 'add' => 'Add a button',
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'URL' ) ) ),
			$hide( 'home_ticker_hide', '“Just joined”' ),
		) ),
		'weekend' => array( 'Weekend', array_merge(
			array( $hide( 'home_weekend_hide', 'section' ) ),
			$head( 'weekend', '', false ),
			array(
				array( 'key' => 'home_weekend_offer', 'label' => 'Offer', 'type' => 'select', 'choices' => array( '' => '— Automatic —' ) + $c['offers'],
					'help' => 'If it ends, the first live offer shows. The events are this weekend’s, featured first.' ),
				array( 'key' => 'note', 'type' => 'heading', 'label' => 'Newsletter card' ),
				$hide( 'home_nl_hide', 'card' ),
				array( 'key' => 'home_nl_title', 'label' => 'Title', 'type' => 'text', 'width' => 60 ),
				array( 'key' => 'home_nl_text', 'label' => 'Text', 'type' => 'text', 'width' => 40 ),
				array( 'key' => 'home_nl_editions', 'label' => 'Newsletters', 'type' => 'list', 'add' => 'Add a newsletter',
					'help' => '<strong>Value</strong> is sent with the form: daily, weekend or business.',
					'cols' => array( 'value' => array( 'Value' ), 'label' => array( 'Name' ), 'note' => array( 'Note' ), 'checked' => array( 'Ticked', 'check' ) ) ),
				array( 'key' => 'home_nl_placeholder', 'label' => 'Email placeholder', 'type' => 'text', 'width' => 50 ),
				array( 'key' => 'home_nl_button', 'label' => 'Button text', 'type' => 'text', 'width' => 50 ),
			)
		) ),
		'offers' => array( 'Offers &amp; stats', array_merge(
			array( array( 'key' => 'note', 'type' => 'heading', 'label' => 'More Local Offers', 'help' => 'The next six live offers, not the weekend one.' ), $hide( 'home_offers_hide', 'section' ) ),
			$head( 'offers', '' ),
			array(
				array( 'key' => 'note', 'type' => 'heading', 'label' => 'Stats', 'help' => 'Counts come from the listings. {areas} = the number of areas.' ),
				$hide( 'home_stats_hide', 'stats' ),
				array( 'key' => 'home_stat_offers_label', 'label' => 'Offers label', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_offers_note', 'label' => 'Offers note', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_events_label', 'label' => 'Events label', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_events_note', 'label' => 'Events note', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_classes_label', 'label' => 'Classes label', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_classes_note', 'label' => 'Classes note', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_biz_label', 'label' => 'Businesses label', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'home_stat_biz_note', 'label' => 'Businesses note', 'type' => 'text', 'width' => 25 ),
			)
		) ),
		'businesses' => array( 'Businesses', array_merge(
			array( array( 'key' => 'note', 'type' => 'heading', 'label' => 'Featured businesses' ), $hide( 'home_featured_hide', 'section' ) ),
			$head( 'featured', '' ),
			array(
				array( 'key' => 'home_featured_pick', 'label' => 'Businesses', 'type' => 'businesses', 'max' => HUB_HOME_FEATURED,
					'help' => 'Up to 4. Empty: Featured and Local Partner businesses, one per category. Drag to reorder.' ),
				array( 'key' => 'note', 'type' => 'heading', 'label' => 'Popular categories', 'help' => 'The 6 with the most businesses, unless you tick some below.' ),
				$hide( 'home_cats_hide', 'section' ),
			),
			$head( 'cats', '' ),
			array(
				array( 'key' => 'home_cats_pick', 'label' => 'Categories', 'type' => 'checks', 'choices' => $c['categories'],
					'help' => 'Optional. Up to 6, most businesses first.' ),
			)
		) ),
		'areas' => array( 'Areas', array_merge(
			array( array( 'key' => 'note', 'type' => 'heading', 'label' => 'Browse by area', 'help' => 'Every area, with counts from the listings.' ), $hide( 'home_areas_hide', 'section' ) ),
			$head( 'areas', '', true, false ),
			array(
				array( 'key' => 'note', 'type' => 'heading', 'label' => '“Hidden gem” box' ),
				$hide( 'home_gem_hide', 'box' ),
				array( 'key' => 'home_gem_eyebrow', 'label' => 'Label', 'type' => 'text', 'width' => 34 ),
				array( 'key' => 'home_gem_title', 'label' => 'Title', 'type' => 'text', 'width' => 66 ),
				array( 'key' => 'home_gem_text', 'label' => 'Text', 'type' => 'textarea' ),
				array( 'key' => 'home_gem_button', 'label' => 'Button text', 'type' => 'text', 'width' => 40 ),
				array( 'key' => 'home_gem_link', 'label' => 'Button URL', 'type' => 'text', 'width' => 60, 'help' => $link ),
			)
		) ),
		'biz' => array( 'For businesses', array(
			$hide( 'home_biz_hide', 'section' ),
			array( 'key' => 'home_biz_eyebrow', 'label' => 'Label', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'home_biz_title', 'label' => 'Title', 'type' => 'text', 'width' => 66 ),
			array( 'key' => 'home_biz_lead', 'label' => 'Intro', 'type' => 'textarea' ),
			array( 'key' => 'home_biz_buttons', 'label' => 'Buttons', 'help' => 'The first is red.', 'type' => 'list', 'add' => 'Add a button',
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'URL' ) ) ),
			array( 'key' => 'home_biz_note', 'label' => 'Note', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'home_biz_note_link_label', 'label' => 'Note link text', 'type' => 'text', 'width' => 30 ),
			array( 'key' => 'home_biz_note_link', 'label' => 'Note link URL', 'type' => 'text', 'width' => 30 ),
			array( 'key' => 'home_biz_steps', 'label' => 'Steps', 'type' => 'list', 'add' => 'Add a step',
				'cols' => array( 'title' => array( 'Title' ), 'text' => array( 'Text' ) ) ),
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
	echo '<p class="hub-f__help" style="margin:4px 12px 0">Empty fields use the default text. Events, offers, businesses, categories and areas come from the listings.</p>';
	hub_admin_tabs( hub_admin_home_fields(), $values );
}

add_action( 'save_post_page', function ( $post_id ) {
	if ( $post_id !== hub_home_page_id() || ! isset( $_POST['hub_home_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hub_home_nonce'] ), 'hub_home_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fields = hub_admin_tab_fields( hub_admin_home_fields() );
	$sent  = isset( $_POST['hub'] ) && is_array( $_POST['hub'] ) ? $_POST['hub'] : array(); // phpcs:ignore -- cleaned field by field
	$clean = hub_admin_clean_all( $fields, $sent );
	$clean['home_cats_pick'] = array_slice( $clean['home_cats_pick'], 0, HUB_HOME_CATEGORIES );
	hub_home_save_fields( $clean );
} );

<?php
/**
 * wp-admin: the "Page text" box on the Hub's listing pages (Pages > What's On, Pages > Classes and activities), a
 * native meta box with tabs. Read and saved through the pages module (hub_pages_fields / hub_pages_save_fields); an emptied
 * field goes back to the default wording. The fields of each page: hub_admin_page_text_{page}().
 */
defined( 'ABSPATH' ) || exit;

/** A page's fields, by tab (empty for a page without a box). */
function hub_admin_page_text_fields( $page ) {
	$fn = 'hub_admin_page_text_' . str_replace( '-', '_', (string) $page );
	return function_exists( $fn ) ? $fn() : array();
}

/** What's On (/events/). */
function hub_admin_page_text_events() {
	$link = 'A Hub page such as events/classes/ or a full web address.';
	$hide = function ( $key, $what ) {
		return array( 'key' => $key, 'label' => 'Hide ' . $what, 'type' => 'toggle', 'width' => 100 );
	};
	$editions = array();
	foreach ( hub_newsletter_settings()['editions'] as $e ) {
		$editions[ (string) $e[0] ] = $e[1];
	}
	return array(
		'top'    => array( 'Top of the page', array(
			array( 'key' => 'events_title', 'label' => 'Heading', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'events_crumb', 'label' => 'Breadcrumb (above the heading)', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_lead', 'label' => 'Text under the heading', 'type' => 'textarea' ),
			array( 'key' => 'events_lead_link_label', 'label' => 'Then a link: its text', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_lead_link', 'label' => 'Link goes to', 'type' => 'text', 'width' => 60, 'help' => $link ),
			array( 'key' => 'events_lead_after', 'label' => 'Text after the link', 'type' => 'textarea', 'help' => 'Starts straight after the link, so begin with a full stop or a space.' ),
		) ),
		'list'   => array( 'The list', array(
			array( 'key' => 'note', 'type' => 'note', 'label' => 'The events, dates, counts and filters come from the listings (Events and Classes).' ),
			$hide( 'events_chips_hide', 'the quick links above the list' ),
			array( 'key' => 'events_chips', 'label' => 'Quick links above the list', 'type' => 'list', 'add' => 'Add a link',
				'help' => 'Links such as events/?when=today, events/?when=weekend, events/?cat=family or events/?free=1 filter the list. Icon (optional): repeat, star, calendar, tag…',
				'cols' => array( 'icon' => array( 'Icon' ), 'label' => array( 'Text' ), 'link' => array( 'Goes to' ) ) ),
			array( 'key' => 'events_featured_title', 'label' => 'Featured events heading', 'type' => 'text', 'width' => 100 ),
			array( 'key' => 'events_month_button', 'label' => 'Button under the list', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_month_link', 'label' => 'Button goes to', 'type' => 'text', 'width' => 60, 'help' => $link ),
			array( 'key' => 'note', 'type' => 'note', 'label' => '<strong>When nothing matches the filters</strong>' ),
			array( 'key' => 'events_empty_text', 'label' => 'Message', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_empty_link_label', 'label' => 'Link text', 'type' => 'text', 'width' => 30 ),
			array( 'key' => 'events_empty_link', 'label' => 'Link goes to', 'type' => 'text', 'width' => 30 ),
		) ),
		'panels' => array( 'Under the list', array(
			array( 'key' => 'note', 'type' => 'note', 'label' => '<strong>Classes panel</strong> (left)' ),
			$hide( 'events_classes_hide', 'the classes panel' ),
			array( 'key' => 'events_classes_title', 'label' => 'Heading', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_classes_text', 'label' => 'Text', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'events_classes_link_label', 'label' => 'Link text', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_classes_link', 'label' => 'Link goes to', 'type' => 'text', 'width' => 60, 'help' => $link ),
			array( 'key' => 'note', 'type' => 'note', 'label' => '<strong>Newsletter panel</strong> (right)' ),
			$hide( 'events_nl_hide', 'the newsletter panel' ),
			array( 'key' => 'events_nl_title', 'label' => 'Heading', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_nl_text', 'label' => 'Text', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'events_nl_edition', 'label' => 'Signs people up to', 'type' => 'select', 'choices' => $editions, 'width' => 34,
				'help' => 'The newsletters are set in Hub settings > Newsletter.' ),
			array( 'key' => 'events_nl_placeholder', 'label' => 'Email box placeholder', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'events_nl_button', 'label' => 'Button', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'events_nl_note', 'label' => 'Small note under the form', 'type' => 'text' ),
			array( 'key' => 'events_nl_done', 'label' => 'Message after signing up', 'type' => 'text' ),
		) ),
		'cta'    => array( 'Box at the bottom', array(
			$hide( 'events_cta_hide', 'this box' ),
			array( 'key' => 'events_cta_title', 'label' => 'Heading', 'type' => 'text' ),
			array( 'key' => 'events_cta_text', 'label' => 'Text', 'type' => 'textarea' ),
			array( 'key' => 'events_cta_buttons', 'label' => 'Buttons (the first is red)', 'type' => 'list', 'add' => 'Add a button', 'help' => 'Links: ' . $link,
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'Goes to' ) ) ),
		) ),
		'seo'    => array( 'Search engines', array(
			array( 'key' => 'events_seo_title', 'label' => 'Page title', 'type' => 'text', 'help' => 'Shown in the browser tab and in Google results.' ),
			array( 'key' => 'events_seo_description', 'label' => 'Description', 'type' => 'textarea', 'help' => 'The short text under the title in Google results (about 155 characters).' ),
		) ),
	);
}

/** Classes and activities (/events/classes/). */
function hub_admin_page_text_classes() {
	$link = 'A Hub page such as events/classes/ or a full web address.';
	return array(
		'top'  => array( 'Top of the page', array(
			array( 'key' => 'classes_title', 'label' => 'Heading', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'classes_crumb', 'label' => 'Breadcrumb (above the heading)', 'type' => 'text', 'width' => 40,
				'help' => 'After “Hub › What’s On”. “What’s On” is set in the What’s On page’s Page text box.' ),
			array( 'key' => 'classes_lead', 'label' => 'Text under the heading', 'type' => 'textarea' ),
		) ),
		'list' => array( 'The list', array(
			array( 'key' => 'note', 'type' => 'note', 'label' => 'The class types, counts, filters and classes come from the listings (Classes).' ),
			array( 'key' => 'classes_all_label', 'label' => 'First type link (shows every class)', 'type' => 'text', 'width' => 100 ),
			array( 'key' => 'note', 'type' => 'note', 'label' => '<strong>When nothing matches the filters</strong>' ),
			array( 'key' => 'classes_empty_text', 'label' => 'Message', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'classes_empty_link_label', 'label' => 'Link text', 'type' => 'text', 'width' => 40, 'help' => 'The link clears the filters.' ),
		) ),
		'cta'  => array( 'Box at the bottom', array(
			array( 'key' => 'classes_cta_hide', 'label' => 'Hide this box', 'type' => 'toggle', 'width' => 100 ),
			array( 'key' => 'classes_cta_title', 'label' => 'Heading', 'type' => 'text' ),
			array( 'key' => 'classes_cta_text', 'label' => 'Text', 'type' => 'textarea' ),
			array( 'key' => 'classes_cta_buttons', 'label' => 'Buttons (the first is red)', 'type' => 'list', 'add' => 'Add a button', 'help' => 'Links: ' . $link,
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'Goes to' ) ) ),
		) ),
		'seo'  => array( 'Search engines', array(
			array( 'key' => 'classes_seo_title', 'label' => 'Page title', 'type' => 'text', 'help' => 'Shown in the browser tab and in Google results.' ),
			array( 'key' => 'classes_seo_description', 'label' => 'Description', 'type' => 'textarea', 'help' => 'The short text under the title in Google results (about 155 characters).' ),
		) ),
	);
}

add_action( 'add_meta_boxes_page', function ( WP_Post $post ) {
	if ( hub_pages_for_post( $post->ID ) ) {
		add_meta_box( 'hub-page-text', 'Page text', 'hub_admin_page_text_box', 'page', 'normal', 'high' );
	}
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && isset( $_GET['post'] ) && hub_pages_for_post( (int) $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		hub_admin_enqueue();
	}
} );

/** The box. */
function hub_admin_page_text_box( WP_Post $post ) {
	$page = hub_pages_for_post( $post->ID );
	wp_nonce_field( 'hub_page_text_save', 'hub_page_text_nonce' );
	echo '<p class="hub-f__help" style="margin:4px 12px 0">This page’s text, links and panels. Empty a box to go back to the default wording. The listings on the page come from the Hub’s content.</p>';
	hub_admin_tabs( hub_admin_page_text_fields( $page ), hub_pages_fields( $page ) );
}

add_action( 'save_post_page', function ( $post_id ) {
	$page = hub_pages_for_post( $post_id );
	if ( ! $page || ! isset( $_POST['hub_page_text_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hub_page_text_nonce'] ), 'hub_page_text_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$sent = isset( $_POST['hub'] ) && is_array( $_POST['hub'] ) ? $_POST['hub'] : array(); // phpcs:ignore -- cleaned field by field
	hub_pages_save_fields( $page, hub_admin_clean_all( hub_admin_tab_fields( hub_admin_page_text_fields( $page ) ), $sent ) );
} );

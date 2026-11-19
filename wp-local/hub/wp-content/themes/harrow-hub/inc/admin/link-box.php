<?php
/**
 * wp-admin: the "Business" box on an offer and on a weekly class (native meta box): type part of a business's name,
 * pick it. The same link shows (and can be changed) on the business's "Offers and classes" tab.
 */
defined( 'ABSPATH' ) || exit;

/** [ post type => [ get business, set business, help ] ] */
function hub_admin_link_types() {
	return array(
		'hub_offer' => array( 'hub_offers_db_business_id', 'hub_offers_db_set_business', 'The business giving this offer.' ),
		'hub_class' => array( 'hub_events_db_class_business', 'hub_events_db_set_class_business', 'The business that runs this class.' ),
	);
}

add_action( 'add_meta_boxes', function ( $post_type ) {
	$types = hub_admin_link_types();
	if ( isset( $types[ $post_type ] ) ) {
		add_meta_box( 'hub-business-link', 'Business', 'hub_admin_link_box', $post_type, 'side', 'high' );
	}
} );

function hub_admin_link_box( WP_Post $post ) {
	$t    = hub_admin_link_types()[ $post->post_type ];
	$biz  = (int) call_user_func( $t[0], $post->ID );
	$name = $biz ? html_entity_decode( get_the_title( $biz ), ENT_QUOTES, 'UTF-8' ) : '';
	wp_nonce_field( 'hub_link_save', 'hub_link_nonce' );
	?>
	<div class="hub-pick" data-hub-pick>
		<p class="hub-f__help"><?php echo esc_html( $t[2] ); ?></p>
		<input type="hidden" name="hub_business_id" value="<?php echo $biz ? (int) $biz : ''; ?>" data-hub-pick-id>
		<p class="hub-pick__current" data-hub-pick-current<?php echo $biz ? '' : ' hidden'; ?>>
			<strong data-hub-pick-name><?php echo esc_html( $name ); ?></strong>
			<a href="<?php echo $biz ? esc_url( get_edit_post_link( $biz ) ) : '#'; ?>" data-hub-pick-edit>Edit</a> &middot;
			<button type="button" class="button-link" data-hub-pick-clear>Remove</button>
		</p>
		<input type="search" class="widefat" placeholder="Search businesses by name…" autocomplete="off" data-hub-pick-search>
		<ul class="hub-pick__results" data-hub-pick-results hidden></ul>
	</div>
	<?php
}

/** Business search for the picker (logged-in editors only). */
add_action( 'wp_ajax_hub_business_search', function () {
	check_ajax_referer( 'hub_business_search' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( null, 403 );
	}
	$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	wp_send_json_success( strlen( $term ) < 2 ? array() : hub_businesses_db_search( $term, 20 ) );
} );

foreach ( array_keys( hub_admin_link_types() ) as $hub_type ) {
	add_action( 'save_post_' . $hub_type, function ( $post_id, $post ) {
		if ( ! isset( $_POST['hub_link_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hub_link_nonce'] ), 'hub_link_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$biz = isset( $_POST['hub_business_id'] ) ? absint( $_POST['hub_business_id'] ) : 0;
		if ( $biz && 'hub_business' !== get_post_type( $biz ) ) {
			return;
		}
		call_user_func( hub_admin_link_types()[ $post->post_type ][1], $post_id, $biz );
	}, 10, 2 );
}
unset( $hub_type );

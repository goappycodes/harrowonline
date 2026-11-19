<?php
/**
 * Account pages: roles, who may see /account/..., the signed-in header, and the admin link between a user and the
 * business they manage. Data comes from the account module's controller.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'hub_account_db_ensure_roles' );

/** Path of the requested Hub page, without the Hub's base: "account/billing" */
function hub_account_request_path() {
	$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	return trim( substr( $path, strlen( $base ) ), '/' );
}

/**
 * /account/... needs a signed-in user (signed-out visitors go to sign-in and come back); signed-in users don't need
 * the sign-in and register pages (they go to their account).
 */
add_action( 'template_redirect', function () {
	$path      = hub_account_request_path();
	$signed_in = is_user_logged_in();
	if ( ! $signed_in && ( 'account' === $path || 0 === strpos( $path, 'account/' ) ) ) {
		$back = hub_url( $path . '/' ) . ( empty( $_SERVER['QUERY_STRING'] ) ? '' : '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( $back ), hub_url( 'sign-in/' ) ) );
		exit;
	}
	if ( $signed_in && in_array( $path, array( 'sign-in', 'register' ), true ) ) {
		wp_safe_redirect( hub_url( 'account/' ) );
		exit;
	}
	if ( 'sign-out' === $path ) {
		hub_account_sign_out();
		wp_safe_redirect( hub_url() );
		exit;
	}
}, 5 );

/** Signed in: "My account" instead of "Sign in" in the header and tab bar (hub.css .is-logged-in). */
add_filter( 'body_class', function ( $classes ) {
	if ( is_user_logged_in() ) {
		$classes[] = 'is-logged-in';
	}
	return $classes;
} );

/** Business owners and organisers manage everything from /account/, never wp-admin. */
add_action( 'admin_init', function () {
	if ( wp_doing_ajax() || current_user_can( 'edit_posts' ) ) {
		return;
	}
	$u = wp_get_current_user();
	if ( array_intersect( array_keys( HUB_ACCOUNT_ROLES ), (array) $u->roles ) ) {
		wp_safe_redirect( hub_url( 'account/' ) );
		exit;
	}
} );

/** Users > Edit user: "Business" (the listing this user manages), for admins. */
function hub_account_admin_field( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}
	$account = hub_account_business_id( $user->ID );
	$name    = $account ? get_the_title( $account ) : '';
	?>
	<h2>Harrow Online Hub</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="hub_business_id">Business</label></th>
			<td>
				<input type="text" class="regular-text" name="hub_business_id" id="hub_business_id" value="<?php echo esc_attr( $account ? $account : '' ); ?>" placeholder="Business post ID or slug">
				<?php wp_nonce_field( 'hub_link_business', '_hub_link_nonce' ); ?>
				<p class="description">
					<?php if ( $account ) : ?>
						Manages <a href="<?php echo esc_url( get_edit_post_link( $account ) ); ?>"><?php echo esc_html( $name ); ?></a>.
					<?php endif; ?>
					One business per account. Enter the business&rsquo;s post ID or slug (for example aj-pizzas-harrow-town-centre); leave empty to unlink.
				</p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'hub_account_admin_field' );
add_action( 'edit_user_profile', 'hub_account_admin_field' );

function hub_account_admin_save( $user_id ) {
	if ( ! current_user_can( 'edit_users' ) || ! isset( $_POST['_hub_link_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hub_link_nonce'] ) ), 'hub_link_business' ) ) {
		return;
	}
	$value = isset( $_POST['hub_business_id'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['hub_business_id'] ) ) ) : '';
	$id    = 0;
	if ( '' !== $value ) {
		$post = ctype_digit( $value ) ? get_post( (int) $value ) : get_page_by_path( sanitize_title( $value ), OBJECT, 'hub_business' );
		$id   = ( $post && 'hub_business' === $post->post_type ) ? (int) $post->ID : 0;
	}
	hub_account_link_business( $user_id, $id );
}
add_action( 'personal_options_update', 'hub_account_admin_save' );
add_action( 'edit_user_profile_update', 'hub_account_admin_save' );

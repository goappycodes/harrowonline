<?php
/**
 * Account pages: roles, who may see /account/..., the signed-in header, and the admin link between a user and the
 * business they manage. Data comes from the account module's controller.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'hub_account_db_migrate_roles' );

/** A business owner's plan field follows their business's plan: Stripe, wp-admin edits, and old-role users moving over. */
add_action( 'hub_business_plan_changed', 'hub_account_sync_business_owner' );
add_action( 'hub_account_role_migrated', 'hub_account_sync_plan' );

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
		// Only with the link's token; without it (an old link, or another site's), ask on the dashboard instead
		if ( $signed_in && ! hub_account_sign_out_ok( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '' ) ) {
			wp_safe_redirect( hub_url( 'account/?sign_out=confirm' ) );
			exit;
		}
		hub_account_sign_out();
		wp_safe_redirect( hub_url() );
		exit;
	}
	// The link in the confirmation email, and the "Resend the email" button on the account dashboard
	if ( 'confirm-email' === $path ) {
		nocache_headers();
		header( 'Referrer-Policy: no-referrer' );
		$q = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- the key in the link is the check
		$r = isset( $q['change'] ) ? hub_account_confirm_email_change( $q ) : hub_account_confirm_email( $q );
		wp_safe_redirect( $r['redirect'] );
		exit;
	}
	// Settings forms: saved before the page is drawn; on success back to the page (?saved=), else shown with errors
	if ( 'account/settings' === $path && $signed_in && 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$r = hub_account_settings_save( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification -- the controller checks it
		if ( $r['ok'] ) {
			wp_safe_redirect( $r['redirect'] );
			exit;
		}
		hub_account_settings_result( $r );
	}
	if ( 'confirm-email/resend' === $path && 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$r = hub_account_resend_confirmation( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification -- the controller checks it
		wp_safe_redirect( $r['redirect'] ? $r['redirect'] : hub_url( 'account/' ) );
		exit;
	}
}, 5 );

/* ------------------------------------------------------------------ attempt limits on every sign-in */

/** Too many wrong passwords for this email or from this IP: refuse, even with the right password, until it clears. */
add_filter( 'authenticate', function ( $user, $username ) {
	if ( '' === (string) $username ) {
		return $user;
	}
	$blocked = hub_account_sign_in_blocked( (string) $username );
	return $blocked ? $blocked : $user;
}, 99, 2 );
add_action( 'wp_login_failed', function ( $username ) {
	hub_account_sign_in_failed( (string) $username );
} );
add_action( 'wp_login', function ( $login ) {
	hub_account_sign_in_succeeded( (string) $login );
} );

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
	if ( hub_account_is_member( get_current_user_id() ) ) {
		wp_safe_redirect( hub_url( 'account/' ) );
		exit;
	}
} );

/** Users > Edit user: the Hub section (account type, plan, business), for admins. */
function hub_account_admin_field( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}
	$account = hub_account_for_user( $user->ID );
	$biz     = hub_account_business_id( $user->ID );
	$type    = hub_account_is_member( $user->ID ) ? $account['type'] : '';
	$plan    = (string) get_user_meta( $user->ID, 'hub_plan', true );
	$sub     = $biz ? hub_billing_status_for_business( $biz ) : null;
	?>
	<h2>Harrow Online Hub</h2>
	<?php wp_nonce_field( 'hub_link_business', '_hub_link_nonce' ); ?>
	<table class="form-table" role="presentation">
		<tr>
			<th>Account type</th>
			<td><?php echo esc_html( '' !== $type ? HUB_ACCOUNT_TYPES[ $type ] : 'Not a Hub account (staff)' ); ?></td>
		</tr>
		<?php if ( '' !== $type ) : $details = hub_account_admin_details( $user->ID ); ?>
		<?php if ( 'organiser' === $type || '' !== $details['organisation'] ) : ?>
		<tr>
			<th><label for="hub_organisation">Organisation</label></th>
			<td><input type="text" class="regular-text" name="hub_organisation" id="hub_organisation" value="<?php echo esc_attr( $details['organisation'] ); ?>">
				<p class="description">The school, club, charity or group they organise for.</p></td>
		</tr>
		<?php endif; ?>
		<tr>
			<th><label for="hub_area">Area</label></th>
			<td>
				<select name="hub_area" id="hub_area">
					<option value="">&ndash; Not given &ndash;</option>
					<?php foreach ( hub_areas_list()['items'] as $a ) : ?>
						<option value="<?php echo esc_attr( $a['slug'] ); ?>" <?php selected( $details['area'], $a['slug'] ); ?>><?php echo esc_html( $a['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">Chosen when they registered, to send them news and events near them.</p>
			</td>
		</tr>
		<tr>
			<th>Newsletter</th>
			<td>
				<?php foreach ( HUB_ACCOUNT_EDITIONS as $value => $label ) : ?>
					<label style="margin-right:1.5em"><input type="checkbox" name="hub_editions[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $details['editions'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
				<?php endforeach; ?>
				<p class="description"><?php echo $details['editions'] ? 'Joined the newsletter.' : 'Not on the newsletter.'; ?> They can change this on their account&rsquo;s Settings page. (Sending to Beehiiv comes with the newsletter work.)</p>
			</td>
		</tr>
		<?php if ( '' !== $details['signup_source'] ) : ?>
		<tr>
			<th>Signed up from</th>
			<td><?php echo esc_html( 'hub-registration' === $details['signup_source'] ? 'The Hub registration page' : ( 'seed' === $details['signup_source'] ? 'Local test data' : $details['signup_source'] ) ); ?></td>
		</tr>
		<?php endif; ?>
		<?php endif; ?>
		<?php if ( 'organiser' !== $type ) : ?>
		<tr>
			<th><label for="hub_plan">Plan</label></th>
			<td>
				<select name="hub_plan" id="hub_plan">
					<?php if ( '' === $plan ) : ?><option value="">&ndash;</option><?php endif; ?>
					<?php foreach ( HUB_ACCOUNT_PLANS as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $plan, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					Follows the linked business&rsquo;s plan. Changing it here changes the business too.
					<?php if ( $sub && $sub['has_subscription'] ) : ?>
						<strong>This business pays through Stripe (<?php echo esc_html( $sub['plan_name'] . ', ' . $sub['status_label'] ); ?>)</strong>:
						change the plan in Stripe, or the next payment puts it back.
					<?php endif; ?>
				</p>
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<th><label for="hub_business_id">Business</label></th>
			<td>
				<input type="text" class="regular-text" name="hub_business_id" id="hub_business_id" value="<?php echo esc_attr( $biz ? $biz : '' ); ?>" placeholder="Business post ID or slug">
				<p class="description">
					<?php if ( $biz ) : ?>
						Manages <a href="<?php echo esc_url( get_edit_post_link( $biz ) ); ?>"><?php echo esc_html( get_the_title( $biz ) ); ?></a>.
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
	$plan_before = (string) get_user_meta( $user_id, 'hub_plan', true );
	if ( hub_account_business_id( $user_id ) !== $id ) {
		hub_account_link_business( $user_id, $id );   // also fills in the plan from the newly linked business
	}
	// Organisation, area and newsletter (Hub accounts only: the fields aren't on staff accounts' pages)
	if ( isset( $_POST['hub_area'] ) ) {
		hub_account_admin_update_details( $user_id, array(
			'organisation' => isset( $_POST['hub_organisation'] ) ? $_POST['hub_organisation'] : (string) get_user_meta( $user_id, 'hub_organisation', true ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- the controller sanitises
			'area'         => wp_unslash( $_POST['hub_area'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'editions'     => isset( $_POST['hub_editions'] ) ? (array) $_POST['hub_editions'] : array(), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		) );
	}
	// Then the plan, only if the admin changed the dropdown
	$plan = isset( $_POST['hub_plan'] ) ? sanitize_key( wp_unslash( $_POST['hub_plan'] ) ) : '';
	if ( '' !== $plan && $plan !== $plan_before ) {
		hub_account_set_plan( $user_id, $plan );
	}
}
add_action( 'personal_options_update', 'hub_account_admin_save' );
add_action( 'edit_user_profile_update', 'hub_account_admin_save' );

// The Users list's columns are in inc/admin/list-screens.php

/** Users list: filter by plan (Users > ?hub_plan=featured), with links above the table. */
add_filter( 'views_users', function ( $views ) {
	foreach ( HUB_ACCOUNT_PLANS as $value => $label ) {
		$n = count( get_users( array( 'meta_key' => 'hub_plan', 'meta_value' => $value, 'fields' => 'ID' ) ) );
		$on = isset( $_GET['hub_plan'] ) && $value === $_GET['hub_plan']; // phpcs:ignore WordPress.Security.NonceVerification
		$views[ 'hub_plan_' . $value ] = '<a href="' . esc_url( add_query_arg( 'hub_plan', $value, admin_url( 'users.php' ) ) ) . '"' . ( $on ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html( $label ) . ' <span class="count">(' . $n . ')</span></a>';
	}
	return $views;
} );
add_action( 'pre_get_users', function ( $q ) {
	global $pagenow;
	if ( is_admin() && 'users.php' === $pagenow && ! empty( $_GET['hub_plan'] ) && isset( HUB_ACCOUNT_PLANS[ sanitize_key( $_GET['hub_plan'] ) ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$q->set( 'meta_key', 'hub_plan' );
		$q->set( 'meta_value', sanitize_key( $_GET['hub_plan'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
} );

/* ------------------------------------------------------------------ WordPress's own login screens -> Hub pages */

/** Reset links last an hour (the forgot-password page says so). WordPress's default is a day. */
add_filter( 'password_reset_expiration', function () {
	return HOUR_IN_SECONDS;
} );

/** The reset email WordPress sends (from the Hub's forgot-password page or wp-login.php) links to the Hub, not wp-login.php. */
add_filter( 'retrieve_password_notification_email', function ( $email, $key, $user_login, $user ) {
	$mail             = hub_account_reset_email( $user, $key );
	$email['subject'] = $mail['subject'];
	$email['message'] = $mail['body'];
	return $email;
}, 10, 4 );

/** Links WordPress and plugins build for "Lost your password?" and "Register" point to the Hub's pages. */
add_filter( 'lostpassword_url', function () {
	return hub_url( 'forgot-password/' );
} );
add_filter( 'register_url', function () {
	return hub_url( 'register/' );
} );

/**
 * wp-login.php's register, lost-password and set-new-password screens send people to the Hub's pages (with the same
 * reset key). Its plain sign-in form stays, for staff going to wp-admin.
 */
add_action( 'login_init', function () {
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'register' === $action ) {
		$to = hub_url( 'register/' );
	} elseif ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
		$to = hub_url( 'forgot-password/' );
	} elseif ( in_array( $action, array( 'rp', 'resetpass' ), true ) && isset( $_GET['key'], $_GET['login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$to = add_query_arg( array( 'key' => rawurlencode( wp_unslash( $_GET['key'] ) ), 'login' => rawurlencode( wp_unslash( $_GET['login'] ) ) ), hub_url( 'forgot-password/' ) ); // phpcs:ignore WordPress.Security
	} else {
		return;
	}
	wp_safe_redirect( $to );
	exit;
} );

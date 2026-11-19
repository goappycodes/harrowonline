<?php
/**
 * Reset your password (from src/pages/forgot-password.html; hand-edited). Two steps on one page:
 *   1. /forgot-password/                      email address -> WordPress emails a reset link (same answer for any email)
 *   2. /forgot-password/?key=...&login=...    the link from that email: choose a new password, then signed in
 * The reset email itself and wp-login.php's reset screens are pointed here by app/setup/accounts.php.
 */
defined( 'ABSPATH' ) || exit;
$hub_result = null;
$hub_reset  = isset( $_REQUEST['key'], $_REQUEST['login'] ); // phpcs:ignore WordPress.Security.NonceVerification
$hub_link   = $hub_reset ? array( 'key' => wp_unslash( $_REQUEST['key'] ), 'login' => wp_unslash( $_REQUEST['login'] ) ) : array(); // phpcs:ignore WordPress.Security
if ( $hub_reset ) {
	header( 'Referrer-Policy: no-referrer' );   // keep the reset key out of other sites' logs
}
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	// phpcs:ignore WordPress.Security.NonceVerification -- the controller checks it
	$hub_result = $hub_reset ? hub_account_reset_password( $_POST ) : hub_account_reset_request( $_POST );
	if ( $hub_reset && $hub_result['ok'] ) {
		wp_safe_redirect( $hub_result['redirect'] );
		exit;
	}
} elseif ( $hub_reset ) {
	$hub_result = hub_account_reset_check( $hub_link );
}
// A bad or expired link falls back to step 1, with the reason shown above the email form
$hub_choose = $hub_reset && $hub_result && ( $hub_result['ok'] || ! empty( $hub_result['errors'] ) );
hub_set_page( array(
	'description' => 'Reset the password for your Harrow Online Hub account. We will email you a secure link to choose a new password.',
	'hubnav' => 'account',
	'layout' => 'bare',
	'robots' => 'noindex',
	'title' => $hub_choose ? 'Choose a new password' : 'Reset your password',
) );
get_header();
?>
<!-- Account pages (redesign, Nov 2026): one card in the site style, with a short "why join" column beside it
     (below it on phones). Same fields, links and copy as before. -->
<div class="authx">
  <div class="authx__card">

      <?php if ( $hub_choose ) : ?>
      <h1 class="authx__title">Choose a new password</h1>
      <p class="authx__lead">For <?php echo esc_html( $hub_link['login'] ); ?>. Use at least <?php echo (int) HUB_ACCOUNT_MIN_PASSWORD; ?> characters.</p>

      <?php hub_view_form_notice( $hub_result['ok'] ? null : $hub_result ); ?>
      <form class="form" action="<?php echo esc_url( hub_url( 'forgot-password/' ) ); ?>" method="post">
        <?php wp_nonce_field( 'hub_account', '_hub_nonce' ); ?>
        <input type="hidden" name="key" value="<?php echo esc_attr( $hub_link['key'] ); ?>">
        <input type="hidden" name="login" value="<?php echo esc_attr( $hub_link['login'] ); ?>">
        <input type="email" name="username" value="<?php echo esc_attr( $hub_link['login'] ); ?>" autocomplete="username" hidden>
        <div class="field<?php echo esc_attr( hub_view_field_class( $hub_result, 'password' ) ); ?>">
          <label for="fp-pass">New password</label>
          <input class="input" id="fp-pass" type="password" name="password" autocomplete="new-password" minlength="<?php echo (int) HUB_ACCOUNT_MIN_PASSWORD; ?>" required>
          <?php hub_view_field_error( $hub_result, 'password' ); ?>
        </div>
        <button class="ho-btn ho-btn--primary ho-btn--block" type="submit">Save password and sign in</button>
      </form>

      <?php else : ?>
      <h1 class="authx__title">Reset your password</h1>
      <p class="authx__lead">Enter the email address you use for the Hub and we&rsquo;ll send you a reset link.</p>

      <?php if ( ! $hub_reset && $hub_result && $hub_result['ok'] ) : ?>
      <div class="notice notice--ok" role="status">
        <svg class="ho-icon"><use href="#i-mail"/></svg>
        <div>
          <p><strong>Check your inbox.</strong></p>
          <p>If that email has a Hub account, a reset link is on its way. It can take a few minutes, so check your spam folder too.</p>
        </div>
      </div>
      <?php else : ?>
      <?php hub_view_form_notice( $hub_result ); ?>
      <form class="form" action="<?php echo esc_url( hub_url( 'forgot-password/' ) ); ?>" method="post">
        <?php wp_nonce_field( 'hub_account', '_hub_nonce' ); ?>
        <div class="field<?php echo esc_attr( hub_view_field_class( $hub_reset ? null : $hub_result, 'user_login' ) ); ?>">
          <label for="fp-email">Email address</label>
          <input class="input" id="fp-email" type="email" name="user_login" autocomplete="email" required value="<?php echo esc_attr( $hub_reset ? '' : hub_view_posted( 'user_login' ) ); ?>">
          <?php hub_view_field_error( $hub_reset ? null : $hub_result, 'user_login' ); ?>
        </div>
        <button class="ho-btn ho-btn--primary ho-btn--block" type="submit">Send reset link</button>
      </form>
      <?php endif; ?>
      <?php endif; ?>

      <div class="authx__foot">
        <p class="mb-0"><a href="<?php echo esc_url( hub_url( 'sign-in/' ) ); ?>">Back to sign in</a> &middot; <a href="<?php echo esc_url( hub_url( 'register/' ) ); ?>">Create an account</a></p>
      </div>
  </div>

  <aside class="authx__perks" aria-label="About your Hub account">
      <p class="authx__eyebrow">Harrow Online Hub</p>
      <h2 class="authx__perks-title">Back into your account in a minute</h2>
      <p>We&rsquo;ll email you a link to choose a new password. The link works once and expires after an hour.</p>
  </aside>
</div>
<?php get_footer();

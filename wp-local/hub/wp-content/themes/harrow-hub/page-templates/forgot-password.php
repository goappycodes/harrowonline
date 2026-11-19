<?php
/**
 * Reset your password (from src/pages/forgot-password.html; hand-edited: sends WordPress's reset email through the
 * account module, and shows the same answer whether or not the email has an account).
 * URL: /forgot-password/
 */
defined( 'ABSPATH' ) || exit;
$hub_result = null;
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$hub_result = hub_account_reset_request( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification -- the controller checks it
}
hub_set_page( array(
	'description' => 'Reset the password for your Harrow Online Hub account. We will email you a secure link to choose a new password.',
	'hubnav' => 'account',
	'layout' => 'bare',
	'robots' => 'noindex',
	'title' => 'Reset your password',
) );
get_header();
?>
<!-- Account pages (redesign, Nov 2026): one card in the site style, with a short "why join" column beside it
     (below it on phones). Same fields, links and copy as before. -->
<div class="authx">
  <div class="authx__card">

      <h1 class="authx__title">Reset your password</h1>
      <p class="authx__lead">Enter the email address you use for the Hub and we&rsquo;ll send you a reset link.</p>

      <?php if ( $hub_result && $hub_result['ok'] ) : ?>
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
        <div class="field<?php echo esc_attr( hub_view_field_class( $hub_result, 'user_login' ) ); ?>">
          <label for="fp-email">Email address</label>
          <input class="input" id="fp-email" type="email" name="user_login" autocomplete="email" required value="<?php echo esc_attr( hub_view_posted( 'user_login' ) ); ?>">
          <?php hub_view_field_error( $hub_result, 'user_login' ); ?>
        </div>
        <button class="ho-btn ho-btn--primary ho-btn--block" type="submit">Send reset link</button>
      </form>
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

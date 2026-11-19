<?php
/**
 * Sign in (from src/pages/sign-in.html; hand-edited: the form signs in through the account module).
 * URL: /sign-in/   ?redirect_to= the Hub page to go back to afterwards
 */
defined( 'ABSPATH' ) || exit;
$hub_result = null;
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$hub_result = hub_account_sign_in( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification -- the controller checks it
	if ( $hub_result['ok'] ) {
		wp_safe_redirect( $hub_result['redirect'] );
		exit;
	}
}
$hub_return = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
hub_set_page( array(
	'description' => 'Sign in to your Harrow Online Hub account to manage your listing, offers, events and classes.',
	'hubnav' => 'account',
	'layout' => 'bare',
	'robots' => 'noindex',
	'title' => 'Sign in',
) );
get_header();
?>
<!-- Account pages (redesign, Nov 2026): one card in the site style, with a short "why join" column beside it
     (below it on phones). Same fields, links and copy as before. -->
<div class="authx">
  <div class="authx__card">

      <h1 class="authx__title">Sign in</h1>
      <p class="authx__lead">Welcome back. Sign in to manage your listing, offers and events.</p>

      <?php
      // Opened the email confirmation link while signed out (app/setup/accounts.php)
      $hub_email_q = isset( $_GET['email'] ) ? sanitize_key( $_GET['email'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
      if ( ! $hub_result && 'confirmed' === $hub_email_q ) {
          hub_view_form_notice( array( 'ok' => true, 'message' => 'Thanks, your email address is confirmed. Sign in to carry on.' ) );
      } elseif ( ! $hub_result && 'link-invalid' === $hub_email_q ) {
          hub_view_form_notice( array( 'ok' => false, 'message' => 'That confirmation link didn&rsquo;t work. Sign in and we&rsquo;ll send you a new one.' ) );
      }
      ?>
      <?php hub_view_form_notice( $hub_result ); ?>
      <form class="form" action="<?php echo esc_url( hub_url( 'sign-in/' ) ); ?>" method="post">
        <?php wp_nonce_field( 'hub_account', '_hub_nonce' ); ?>
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $hub_return ); ?>">
        <div class="field<?php echo esc_attr( hub_view_field_class( $hub_result, 'log' ) ); ?>">
          <label for="si-email">Email address</label>
          <input class="input" id="si-email" type="email" name="log" autocomplete="email" required value="<?php echo esc_attr( hub_view_posted( 'log' ) ); ?>">
          <?php hub_view_field_error( $hub_result, 'log' ); ?>
        </div>
        <div class="field<?php echo esc_attr( hub_view_field_class( $hub_result, 'pwd' ) ); ?>">
          <div class="split">
            <label class="field__label" for="si-pass">Password</label>
            <a class="link-quiet" href="<?php echo esc_url( hub_url( 'forgot-password/' ) ); ?>">Forgotten your password?</a>
          </div>
          <input class="input" id="si-pass" type="password" name="pwd" autocomplete="current-password" required>
          <?php hub_view_field_error( $hub_result, 'pwd' ); ?>
        </div>
        <label class="check"><input type="checkbox" name="rememberme" value="forever"> Keep me signed in on this device</label>
        <button class="ho-btn ho-btn--primary ho-btn--block" type="submit">Sign in</button>
      </form>

      <div class="authx__foot">
        <p class="mb-0">New to the Hub? <a href="<?php echo esc_url( hub_url( 'register/' ) ); ?>">Create a free account</a></p>
        <p class="help mb-0">Creating an account is free. You only pay if you choose a paid plan, a featured spot or a sponsor slot.</p>
      </div>
  </div>

  <aside class="authx__perks" aria-label="About your Hub account">
      <p class="authx__eyebrow">Harrow Online Hub</p>
      <h2 class="authx__perks-title">One login for your business, offers and events</h2>
      <p>Harrow Online reaches thousands of local readers every day. Your Hub account puts your business in front of them.</p>
      <ul class="ticks authx__ticks">
        <li>Claim your listing free and get a verified badge</li>
        <li>Add Local Offers, events and weekly classes on the Featured or Local Partner plan</li>
        <li>Reach Harrow Online Newsletter readers with featured spots and sponsor slots</li>
        <li>Schools, clubs and charities add events and classes free</li>
      </ul>
  </aside>
</div>
<?php get_footer();

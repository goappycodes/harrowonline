<?php
/**
 * Account settings forms (display only). $args:
 *   settings  hub_account_settings()
 *   result    hub_account_settings_result(): the form just sent with errors, or null
 *   saved     ?saved= after a successful save (details | email | password | notify | newsletter)
 *   email     ?email= after an email-change link (changed | link-invalid | taken)
 * Each form posts to this page; app/setup/accounts.php saves it through the account controller.
 */
defined( 'ABSPATH' ) || exit;
$s      = $args['settings'];
$result = $args['result'];
$saved  = $args['saved'];
$for    = function ( $form ) use ( $result ) { return $result && $form === $result['form'] ? $result : null; };
$posted = function ( $form, $key, $fallback ) use ( $result ) { return $result && $form === $result['form'] ? hub_view_posted( $key ) : $fallback; };
$ok     = function ( $form, $text ) use ( $saved ) {
	if ( $form === $saved ) {
		echo '<div class="notice notice--ok mb-4" role="status"><svg class="ho-icon"><use href="#i-check"/></svg><div><p class="mb-0">' . wp_kses( $text, array( 'strong' => array() ) ) . '</p></div></div>';
	}
};
$nonce = function ( $form ) {
	wp_nonce_field( 'hub_account', '_hub_nonce' );
	echo '<input type="hidden" name="form" value="' . esc_attr( $form ) . '">';
};
$action = hub_url( 'account/settings/' );
$email_notes = array(
	'changed'      => array( 'ok', 'Your email address is changed. Use it to sign in from now on.' ),
	'link-invalid' => array( 'warn', 'That email-change link didn&rsquo;t work. It may have expired: change the email below to get a new one.' ),
	'taken'        => array( 'warn', 'Another account started using that email address, so we kept your old one.' ),
);
?>
<?php if ( isset( $email_notes[ $args['email'] ] ) ) : ?>
<div class="notice notice--<?php echo esc_attr( $email_notes[ $args['email'] ][0] ); ?> mb-5" role="status"><svg class="ho-icon"><use href="#i-mail"/></svg><div><p class="mb-0"><?php echo wp_kses( $email_notes[ $args['email'] ][1], array() ); ?></p></div></div>
<?php endif; ?>

<form class="form-card" action="<?php echo esc_url( $action ); ?>#details" method="post" id="details">
  <h2 class="form-card__title">Your details</h2>
  <p class="form-card__sub">Used for your login and for emails about your account. Not shown on your listing.</p>
  <?php $ok( 'details', 'Details saved.' ); ?>
  <?php $ok( 'email', 'Details saved. We&rsquo;ve sent a link to <strong>' . esc_html( (string) $s['pending_email'] ) . '</strong>: your email changes when you click it.' ); ?>
  <?php hub_view_form_notice( $for( 'details' ) ); ?>
  <?php $nonce( 'details' ); ?>
  <div class="stack">
    <div class="row row-2">
      <div class="field<?php echo esc_attr( hub_view_field_class( $for( 'details' ), 'name' ) ); ?>">
        <label for="st-name">Your name</label>
        <input class="input" id="st-name" type="text" name="name" value="<?php echo esc_attr( $posted( 'details', 'name', $s['name'] ) ); ?>" autocomplete="name" required>
        <?php hub_view_field_error( $for( 'details' ), 'name' ); ?>
      </div>
      <div class="field<?php echo esc_attr( hub_view_field_class( $for( 'details' ), 'email' ) ); ?>">
        <label for="st-email">Email address</label>
        <input class="input" id="st-email" type="email" name="email" value="<?php echo esc_attr( $posted( 'details', 'email', $s['email'] ) ); ?>" autocomplete="email" aria-describedby="st-email-help" required>
        <?php hub_view_field_error( $for( 'details' ), 'email' ); ?>
        <p class="help" id="st-email-help">
          <?php if ( $s['pending_email'] && 'email' !== $saved ) : ?>
            Waiting for you to confirm <strong><?php echo esc_html( $s['pending_email'] ); ?></strong>: check that inbox for our link.
          <?php else : ?>
            If you change it, we&rsquo;ll send a link to the new address to confirm.
          <?php endif; ?>
        </p>
      </div>
    </div>
    <div class="form-actions"><button class="ho-btn ho-btn--primary" type="submit">Save details</button></div>
  </div>
</form>

<form class="form-card" action="<?php echo esc_url( $action ); ?>#password" method="post" id="password">
  <h2 class="form-card__title">Change password</h2>
  <p class="form-card__sub">At least <?php echo (int) HUB_ACCOUNT_MIN_PASSWORD; ?> characters. You&rsquo;ll stay signed in on this device.</p>
  <?php $ok( 'password', 'Password changed. We&rsquo;ve emailed you to let you know.' ); ?>
  <?php hub_view_form_notice( $for( 'password' ) ); ?>
  <?php $nonce( 'password' ); ?>
  <input type="email" name="username" value="<?php echo esc_attr( $s['email'] ); ?>" autocomplete="username" hidden>
  <div class="stack">
    <div class="field<?php echo esc_attr( hub_view_field_class( $for( 'password' ), 'current_password' ) ); ?>">
      <label for="st-cur">Current password</label>
      <input class="input" id="st-cur" type="password" name="current_password" autocomplete="current-password" required>
      <?php hub_view_field_error( $for( 'password' ), 'current_password' ); ?>
    </div>
    <div class="row row-2">
      <div class="field<?php echo esc_attr( hub_view_field_class( $for( 'password' ), 'new_password' ) ); ?>">
        <label for="st-new">New password</label>
        <input class="input" id="st-new" type="password" name="new_password" minlength="<?php echo (int) HUB_ACCOUNT_MIN_PASSWORD; ?>" autocomplete="new-password" required>
        <?php hub_view_field_error( $for( 'password' ), 'new_password' ); ?>
      </div>
      <div class="field<?php echo esc_attr( hub_view_field_class( $for( 'password' ), 'new_password_2' ) ); ?>">
        <label for="st-new2">Confirm new password</label>
        <input class="input" id="st-new2" type="password" name="new_password_2" minlength="<?php echo (int) HUB_ACCOUNT_MIN_PASSWORD; ?>" autocomplete="new-password" required>
        <?php hub_view_field_error( $for( 'password' ), 'new_password_2' ); ?>
      </div>
    </div>
    <div class="form-actions"><button class="ho-btn ho-btn--primary" type="submit">Change password</button></div>
  </div>
</form>

<form class="form-card" action="<?php echo esc_url( $action ); ?>#notify" method="post" id="notify">
  <h2 class="form-card__title">Email notifications</h2>
  <p class="form-card__sub">Emails we send about your listing, offers and events.</p>
  <?php $ok( 'notify', 'Email choices saved.' ); ?>
  <?php $nonce( 'notify' ); ?>
  <div class="stack">
    <?php foreach ( HUB_ACCOUNT_NOTIFY as $key => $n ) : ?>
    <label class="check"><input type="checkbox" name="notify[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $s['notify'][ $key ] ); ?>> <span><?php echo esc_html( $n[0] ); ?><small><?php echo wp_kses( $n[1], array() ); ?></small></span></label>
    <?php endforeach; ?>
    <label class="check"><input type="checkbox" checked disabled> <span>Receipts and billing<small>Always on. Payment receipts, failed payments and plan changes.</small></span></label>
    <div class="form-actions"><button class="ho-btn ho-btn--primary" type="submit">Save notifications</button></div>
  </div>
</form>

<form class="form-card" action="<?php echo esc_url( $action ); ?>#newsletter" method="post" id="newsletter">
  <h2 class="form-card__title">Harrow Online Newsletter</h2>
  <p class="form-card__sub">Free editions sent to <?php echo esc_html( $s['email'] ); ?>. Untick all three to unsubscribe.</p>
  <?php $ok( 'newsletter', $s['editions'] ? 'Newsletter choices saved.' : 'You&rsquo;re unsubscribed from the newsletter.' ); ?>
  <?php $nonce( 'newsletter' ); ?>
  <!-- Saved on the account (user meta hub_newsletter_editions); the newsletter module will sync it to Beehiiv -->
  <fieldset class="fieldset">
    <legend class="ho-sr">Editions</legend>
    <div class="stack">
      <?php foreach ( array( 'daily' => 'Every morning: the day&rsquo;s Harrow news, what&rsquo;s on and offers', 'weekend' => 'Thursdays: what&rsquo;s on in Harrow this weekend', 'business' => 'Mondays: local business news, openings and new Hub members' ) as $key => $what ) : ?>
      <label class="check"><input type="checkbox" name="editions[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $s['editions'], true ) ); ?>> <span><?php echo esc_html( HUB_ACCOUNT_EDITIONS[ $key ] ); ?><small><?php echo wp_kses( $what, array() ); ?></small></span></label>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <div class="form-actions mt-4"><button class="ho-btn ho-btn--primary" type="submit">Save newsletter choices</button></div>
</form>

<form class="form-card" action="<?php echo esc_url( $action ); ?>#close" method="post" id="close" aria-labelledby="close-title">
  <h2 class="form-card__title" id="close-title">Close your account</h2>
  <p class="form-card__sub">This can&rsquo;t be undone.</p>
  <ul class="ticks ticks--dark small">
    <?php if ( $s['business'] ) : ?>
    <li><?php echo esc_html( $s['business'] ); ?> goes back to an unclaimed listing. Its basic details stay on the Hub; your description, photos, offers and events are hidden.</li>
    <?php endif; ?>
    <li>Paid plans must be cancelled in <a href="<?php echo esc_url( hub_url( 'billing/portal/' ) ); ?>">Manage billing</a> first. We can&rsquo;t close an account with an active plan.</li>
    <li>Your newsletter subscription isn&rsquo;t affected. Change it above.</li>
  </ul>
  <?php if ( ! $s['can_close'] ) : ?>
  <div class="notice notice--warn mt-4"><svg class="ho-icon"><use href="#i-alert"/></svg><p><?php echo esc_html( $s['close_blocked'] ); ?></p></div>
  <div class="form-actions mt-4">
    <button class="ho-btn ho-btn--outline is-disabled" type="button" disabled>Close my account</button>
    <a class="link-quiet" href="https://harrowonline.org/contact-us/">Need help? Contact us</a>
  </div>
  <?php else : ?>
  <?php hub_view_form_notice( $for( 'close' ) ); ?>
  <?php $nonce( 'close' ); ?>
  <div class="stack mt-4">
    <div class="field<?php echo esc_attr( hub_view_field_class( $for( 'close' ), 'close_password' ) ); ?>">
      <label for="st-close-pw">Your password</label>
      <input class="input" id="st-close-pw" type="password" name="close_password" autocomplete="current-password" required>
      <?php hub_view_field_error( $for( 'close' ), 'close_password' ); ?>
    </div>
    <label class="check<?php echo esc_attr( hub_view_field_class( $for( 'close' ), 'close_confirm' ) ); ?>"><input type="checkbox" name="close_confirm" value="1" required> <span>I understand my account will be deleted and this can&rsquo;t be undone.</span></label>
    <?php hub_view_field_error( $for( 'close' ), 'close_confirm' ); ?>
  </div>
  <div class="form-actions mt-4">
    <button class="ho-btn ho-btn--outline" type="submit">Close my account</button>
    <a class="link-quiet" href="https://harrowonline.org/contact-us/">Need help? Contact us</a>
  </div>
  <?php endif; ?>
</form>

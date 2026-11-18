<?php
/**
 * "Subscriber-only. Enter your email to unlock." form. The code itself is never in the page: hub.js asks for it
 * after the email is confirmed.
 *
 * $args: offer (offers shape), note (bool, default true: show the "we'll email you a link" line)
 */
defined( 'ABSPATH' ) || exit;

$o    = $args['offer'];
$note = ! isset( $args['note'] ) || $args['note'];

$GLOBALS['hub_unlock_uid'] = isset( $GLOBALS['hub_unlock_uid'] ) ? $GLOBALS['hub_unlock_uid'] + 1 : 1;
$id = 'unlock-' . substr( $o['business']['slug'], 0, 20 ) . '-' . $GLOBALS['hub_unlock_uid'];
?>

          <div class="offer__lock">
            <p><?php echo hub_icon( 'lock', 'ho-icon ho-icon--sm' ); ?>Subscriber-only. Enter your email to unlock.</p>
            <form data-unlock action="<?php echo esc_url( hub_url( 'wp-json/ho/v1/unlock' ) ); ?>" method="post">
              <input type="hidden" name="signup_source" value="subscriber-offer">
              <input type="hidden" name="offer" value="<?php echo esc_attr( $o['slug'] ); ?>">
              <label class="ho-sr" for="<?php echo esc_attr( $id ); ?>">Email address</label>
              <input id="<?php echo esc_attr( $id ); ?>" type="email" name="email" placeholder="Your email" autocomplete="email" required>
              <button class="ho-btn ho-btn--sm ho-btn--primary" type="submit">Unlock</button>
            </form><?php if ( $note ) : ?><span class="offer__lock-note">We&rsquo;ll email you a link to confirm. Already a subscriber? Use the email you signed up with.</span><?php endif; ?>

          </div>

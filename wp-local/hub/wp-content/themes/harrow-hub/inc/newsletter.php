<?php
/**
 * The newsletter sign-up band above the footer. Its wording is the option "hub_newsletter", edited at
 * Hub settings > Newsletter (inc/admin/newsletter-page.php); anything not saved uses the defaults below.
 *
 *   hub_newsletter = { show, title, text, editions: [ [ value, name, description, ticked ] ], placeholder, button,
 *                      action, source }
 */
defined( 'ABSPATH' ) || exit;

/** The defaults: today's wording. */
function hub_newsletter_defaults() {
	return array(
		'show'        => true,
		'title'       => 'Harrow in your inbox, every morning',
		'text'        => 'The day’s local news, what’s on and the best Local Offers. Free, from Harrow Online Newsletter.',
		'editions'    => hub_newsletter_default_editions(),
		'placeholder' => 'Your email address',
		'button'      => 'Sign up free',
		'action'      => 'https://harrowonline.org/wp-json/ho/v1/subscribe',
		'source'      => 'hub-strip',
	);
}

/** The band's settings: what is saved, with the defaults for anything empty. */
function hub_newsletter_settings() {
	static $s = null;
	if ( null !== $s ) {
		return $s;
	}
	$saved = get_option( 'hub_newsletter', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$s     = hub_newsletter_defaults();
	foreach ( $s as $k => $default ) {
		if ( isset( $saved[ $k ] ) && '' !== $saved[ $k ] && array() !== $saved[ $k ] ) {
			$s[ $k ] = $saved[ $k ];
		}
	}
	$s['show'] = ! isset( $saved['show'] ) || ! empty( $saved['show'] );
	return $s;
}

/** [ value, name, description, ticked ] */
function hub_newsletter_default_editions() {
	return array(
		array( 'daily', 'Daily News', 'Every morning: Harrow’s news', true ),
		array( 'weekend', 'The Weekend', 'Thursdays: what’s on and offers', false ),
		array( 'business', 'Harrow Business', 'Mondays: local business news', false ),
	);
}

/** The sign-up band above the footer (same markup as the prototype's nl-strip partial). */
function hub_newsletter_strip() {
	$s = hub_newsletter_settings();
	if ( ! $s['show'] ) {
		return;
	}
	$picks = '';
	foreach ( $s['editions'] as $e ) {
		$picks .= "\n          " . '<label class="nl-pick"><input type="checkbox" name="editions[]" value="' . esc_attr( $e[0] ) . '"' . ( $e[3] ? ' checked' : '' ) . '><span><strong>' . esc_html( $e[1] ) . '</strong>' . ( '' !== $e[2] ? '<small>' . esc_html( $e[2] ) . '</small>' : '' ) . '</span></label>';
	}
	?>
<!-- Newsletter sign-up above the footer (Hub settings > Newsletter): the reader picks each edition separately
     (hub.js 5i shows known subscribers a short "you're signed up" line instead). -->
<section class="nl-band nl-band--strip" aria-label="Newsletter sign-up">
  <div class="ho-container">
    <div class="nl-band__box">
      <div class="nl-band__text">
        <p class="nl-band__title"><?php echo esc_html( $s['title'] ); ?></p>
        <p><?php echo esc_html( $s['text'] ); ?></p>
      </div>
      <form class="nl-band__form" action="<?php echo esc_url( $s['action'] ); ?>" method="post" data-nl-form>
        <input type="hidden" name="signup_source" value="<?php echo esc_attr( $s['source'] ); ?>">
        <fieldset class="nl-band__editions">
          <legend class="ho-sr">Choose your newsletters</legend><?php echo $picks; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>

        </fieldset>
        <div class="nl-band__row">
          <label class="ho-sr" for="nl-email">Email address</label>
          <input class="input" id="nl-email" type="email" name="email" placeholder="<?php echo esc_attr( $s['placeholder'] ); ?>" autocomplete="email" required>
          <button class="ho-btn ho-btn--primary" type="submit"><?php echo esc_html( $s['button'] ); ?></button>
        </div>
        <p class="ho-nl__msg nl-band__msg" role="status" aria-live="polite"></p>
      </form>
    </div>
  </div>
</section>
	<?php
}

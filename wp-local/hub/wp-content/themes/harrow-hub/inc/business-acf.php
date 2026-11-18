<?php
/**
 * ACF glue that has not moved into app/modules/ yet, and the newsletter band.
 *
 * Business fields are read and saved by the businesses module (app/modules/businesses/data.php), offers by the
 * offers module. Offers and classes belong to a business through their "Business" field (offer_business /
 * class_business), kept in step with the business's "Offers" and "Weekly classes" fields by ACF.
 */
defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------ one business per offer / class
 * When an offer or class is added on a business, ACF adds that business to the offer's or class's "Business" field
 * as a list ([old, new]). An offer or class has one business, so keep the new one and take the offer or class off
 * the old business's list. (ACF is already inside its own two-way update here, so this does not loop.) */
foreach ( array( 'offer' => 'biz_offers', 'class' => 'biz_classes' ) as $hub_k => $hub_list ) {
	add_filter( "acf/update_value/key=field_hub_{$hub_k}_business", function ( $value, $post_id ) use ( $hub_list ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$ids = array_values( array_filter( array_map( 'intval', $value ) ) );
		$new = $ids ? end( $ids ) : 0;
		foreach ( array_diff( $ids, array( $new ) ) as $old ) {
			$list = array_map( 'intval', acf_get_array( get_field( $hub_list, $old, false ) ) );
			update_field( 'field_hub_' . $hub_list, array_values( array_diff( $list, array( (int) $post_id ) ) ), $old );
		}
		return $new ? (string) $new : '';
	}, 20, 2 );
}

/* ------------------------------------------------------------ newsletter band (Hub settings > Newsletter) */
function hub_newsletter_settings() {
	static $s = null;
	if ( null !== $s ) {
		return $s;
	}
	$opt = function ( $name, $default ) {
		$v = function_exists( 'get_field' ) ? get_field( $name, 'option', false ) : null;
		return ( null === $v || '' === $v ) ? $default : $v;
	};
	$editions = function_exists( 'get_field' ) ? get_field( 'nl_editions', 'option' ) : null;
	$list     = array();
	foreach ( is_array( $editions ) ? $editions : array() as $row ) {
		if ( ! empty( $row['nl_ed_value'] ) && ! empty( $row['nl_ed_label'] ) ) {
			$list[] = array( $row['nl_ed_value'], $row['nl_ed_label'], (string) $row['nl_ed_desc'], ! empty( $row['nl_ed_checked'] ) );
		}
	}
	if ( ! $list ) {
		$list = hub_newsletter_default_editions();
	}
	$s = array(
		'show'        => (bool) $opt( 'nl_show', true ),
		'title'       => $opt( 'nl_title', 'Harrow in your inbox, every morning' ),
		'text'        => $opt( 'nl_text', 'The day’s local news, what’s on and the best Local Offers. Free, from Harrow Online Newsletter.' ),
		'editions'    => $list,
		'placeholder' => $opt( 'nl_placeholder', 'Your email address' ),
		'button'      => $opt( 'nl_button', 'Sign up free' ),
		'action'      => $opt( 'nl_action', 'https://harrowonline.org/wp-json/ho/v1/subscribe' ),
		'source'      => $opt( 'nl_source', 'hub-strip' ),
	);
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

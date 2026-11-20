<?php
/**
 * wp-admin > Hub settings > Prices: the plan and one-off prices, in pounds. Saving stores them (billing module,
 * option "hub_billing_prices") and sends them to Stripe: a changed amount becomes a new Stripe price that new
 * sign-ups pay; existing subscriptions keep their price. Every page that shows a price (Pricing, account billing,
 * Advertise, Checkout) reads them from the billing module.
 */
defined( 'ABSPATH' ) || exit;

/** The tabs at the top of every Hub settings page. */
function hub_admin_settings_tabs( $active ) {
	$tabs = array(
		'hub-settings'        => array( 'Newsletter sign-up', 'dashicons-email-alt' ),
		'hub-settings-prices' => array( 'Prices', 'dashicons-tag' ),
	);
	echo '<nav class="hub-settings__tabs" aria-label="Hub settings">';
	foreach ( $tabs as $page => $t ) {
		$on = $page === $active;
		echo '<a class="hub-settings__tab' . ( $on ? ' is-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=' . $page ) ) . '"' . ( $on ? ' aria-current="page"' : '' ) . '>'
			. '<span class="dashicons ' . esc_attr( $t[1] ) . '" aria-hidden="true"></span>' . esc_html( $t[0] ) . '</a>';
	}
	echo '</nav>';
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'hub-settings', 'Prices', 'Prices', 'manage_options', 'hub-settings-prices', 'hub_admin_prices_page' );
}, 11 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'hub-settings_page_hub-settings-prices' === $hook ) {
		hub_admin_enqueue();
		$css = get_template_directory() . '/admin/hub-admin-settings.css';
		wp_enqueue_style( 'hub-admin-settings', get_template_directory_uri() . '/admin/hub-admin-settings.css', array(), (string) filemtime( $css ) );
	}
} );

/** Pence as pounds for an input box: 2900 -> "29", 799 -> "7.99". */
function hub_admin_pounds( $pence ) {
	$p = (int) $pence;
	return 0 === $p % 100 ? (string) ( $p / 100 ) : number_format( $p / 100, 2, '.', '' );
}

function hub_admin_prices_page() {
	$d      = hub_billing_prices_for_staff();   // billing module
	$result = get_transient( 'hub_prices_result_' . get_current_user_id() );
	if ( $result ) {
		delete_transient( 'hub_prices_result_' . get_current_user_id() );
	}
	$errors = $result && ! empty( $result['errors'] ) ? $result['errors'] : array();
	$sent   = $result && ! empty( $result['sent'] ) ? $result['sent'] : array();   // what was typed, when not saved
	$val    = function ( $field, $pence ) use ( $sent ) {
		return isset( $sent[ $field ] ) ? $sent[ $field ] : hub_admin_pounds( $pence );
	};
	$input  = function ( $name, $field, $value, $label ) use ( $errors ) {
		$bad = isset( $errors[ $field ] );
		return '<label class="hub-price"><span class="screen-reader-text">' . esc_html( $label ) . '</span><span class="hub-price__pound" aria-hidden="true">&pound;</span>'
			. '<input type="text" inputmode="decimal" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="hub-price__input' . ( $bad ? ' is-bad' : '' ) . '"' . ( $bad ? ' aria-invalid="true"' : '' ) . ' required></label>';
	};
	?>
	<div class="wrap hub-settings hub-prices">
		<header class="hub-settings__head">
			<h1>Hub settings</h1>
			<p class="hub-settings__lead">What businesses pay. Saving updates every price on the Hub and in Stripe.</p>
			<?php hub_admin_settings_tabs( 'hub-settings-prices' ); ?>
		</header>
		<?php if ( $result ) : ?>
			<div class="notice notice-<?php echo $result['ok'] ? 'success' : 'error'; ?> is-dismissible"><p><strong><?php echo wp_kses( $result['message'], array() ); ?></strong></p>
				<?php if ( ! empty( $result['done'] ) ) : ?>
					<ul class="hub-prices__done"><?php foreach ( $result['done'] as $line ) { echo '<li>' . esc_html( $line ) . '</li>'; } ?></ul>
				<?php endif; ?></div>
		<?php endif; ?>
		<?php if ( ! $d['configured'] ) : ?>
			<div class="notice notice-warning"><p>Stripe isn&rsquo;t set up on this site: prices are saved here and sent to Stripe when its keys are added and the Stripe setup runs.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hub-prices__form">
			<input type="hidden" name="action" value="hub_prices_save">
			<?php wp_nonce_field( 'hub_prices_save' ); ?>

			<section class="hub-settings__card">
				<div class="hub-settings__card-head"><span class="hub-settings__step">1</span><div>
					<h2>Plans</h2><p>Monthly and yearly prices. Prices include VAT.</p></div></div>
				<table class="hub-prices__table">
					<thead><tr><th scope="col">Plan</th><th scope="col">Monthly</th><th scope="col">Yearly</th><th scope="col"><span class="screen-reader-text">Saving</span></th></tr></thead>
					<tbody>
					<?php foreach ( $d['plans'] as $p ) :
						$fm = 'plans-' . $p['key'] . '-monthly';
						$fa = 'plans-' . $p['key'] . '-annual';
						$save = 12 * $p['monthly'] - $p['annual']; ?>
						<tr>
							<th scope="row"><?php echo esc_html( $p['name'] ); ?></th>
							<td><?php echo $input( 'plans[' . $p['key'] . '][monthly]', $fm, $val( $fm, $p['monthly'] ), $p['name'] . ' monthly' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="hub-price__per">/ month</span></td>
							<td><?php echo $input( 'plans[' . $p['key'] . '][annual]', $fa, $val( $fa, $p['annual'] ), $p['name'] . ' yearly' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="hub-price__per">/ year</span></td>
							<td class="hub-prices__hint"><?php echo $save > 0 ? 'Yearly saves ' . esc_html( hub_billing_rule_money( $save ) ) : '<span class="hub-pill hub-pill--amber">Yearly costs more than 12 months</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>

			<?php foreach ( array( false => array( '2', 'One-off payments', 'Paid once, without a plan.' ), true => array( '3', 'Newsletter adverts', HUB_BILLING_NEWSLETTER_ADVERTS ? 'Booked on the Advertise page.' : 'Not sold on the Hub for launch (adverts are booked through harrowonline.org/advertise); the prices are kept for later.' ) ) as $advert => $g ) : ?>
				<section class="hub-settings__card">
					<div class="hub-settings__card-head"><span class="hub-settings__step"><?php echo esc_html( $g[0] ); ?></span><div>
						<h2><?php echo esc_html( $g[1] ); ?></h2><p><?php echo esc_html( $g[2] ); ?></p></div></div>
					<table class="hub-prices__table">
						<tbody>
						<?php foreach ( $d['items'] as $i ) :
							if ( (bool) $i['advert'] !== (bool) $advert ) {
								continue;
							}
							$f = 'items-' . $i['key']; ?>
							<tr>
								<th scope="row"><?php echo esc_html( $i['name'] ); ?><span class="hub-cell-sub"><?php echo esc_html( $i['what'] ); ?></span></th>
								<td><?php echo $input( 'items[' . $i['key'] . ']', $f, $val( $f, $i['amount'] ), $i['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endforeach; ?>

			<p class="hub-prices__note"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
				A new price applies to new sign-ups and purchases. Businesses already paying for a plan keep their price; change theirs in Stripe if needed.
				<?php echo $d['configured'] ? 'Stripe: ' . esc_html( $d['mode'] ) . ' mode.' : ''; ?></p>

			<div class="hub-settings__bar">
				<?php submit_button( $d['configured'] ? 'Save and update Stripe' : 'Save prices', 'primary large', 'submit', false ); ?>
			</div>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_hub_prices_save', function () {
	check_admin_referer( 'hub_prices_save' );
	$params = array(
		'plans' => isset( $_POST['plans'] ) && is_array( $_POST['plans'] ) ? $_POST['plans'] : array(), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- the controller checks each price
		'items' => isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? $_POST['items'] : array(), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	);
	$r = hub_billing_save_prices( $params );   // billing module
	if ( ! $r ) {
		wp_die( 'You are not allowed to change prices.' );
	}
	if ( ! empty( $r['errors'] ) ) {
		// Keep what was typed so the form shows it again
		$sent = array();
		foreach ( $params['plans'] as $k => $per ) {
			foreach ( (array) $per as $p => $v ) {
				$sent[ 'plans-' . sanitize_key( $k ) . '-' . sanitize_key( $p ) ] = sanitize_text_field( wp_unslash( (string) $v ) );
			}
		}
		foreach ( $params['items'] as $k => $v ) {
			$sent[ 'items-' . sanitize_key( $k ) ] = sanitize_text_field( wp_unslash( (string) $v ) );
		}
		$r['sent'] = $sent;
	}
	set_transient( 'hub_prices_result_' . get_current_user_id(), $r, 5 * MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'admin.php?page=hub-settings-prices' ) );
	exit;
} );

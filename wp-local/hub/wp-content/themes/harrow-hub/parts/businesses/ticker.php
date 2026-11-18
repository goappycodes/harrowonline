<?php
/**
 * Homepage "Just joined" ticker. The list is repeated once so the CSS scroll loops seamlessly; the copy is hidden
 * from screen readers.
 *
 * $args: businesses (businesses shape)
 */
defined( 'ABSPATH' ) || exit;

$items = '';
foreach ( $args['businesses'] as $b ) {
	$items .= '<a class="ticker__item" href="' . esc_url( $b['url'] ) . '">' . esc_html( $b['name'] ) . ', ' . esc_html( $b['area']['name'] ) . '</a>';
}
?>
<div class="ticker" aria-label="Businesses that recently joined the Hub"><span class="ticker__label"><i aria-hidden="true"></i>Just joined</span><div class="ticker__track"><div class="ticker__run"><?php echo $items; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><div class="ticker__run" aria-hidden="true"><?php echo $items; // phpcs:ignore WordPress.Security.EscapeOutput ?></div></div></div>

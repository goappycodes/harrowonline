<?php
/**
 * Featured business card (homepage "Featured local businesses"): photo or logo, plan badge, name, area, tagline.
 *
 * $args: business (businesses shape)
 */
defined( 'ABSPATH' ) || exit;

$b = $args['business'];
if ( $b['images'] ) {
	$media = '<div class="' . esc_attr( hub_view_ph_cls( $b ) ) . '">' . hub_view_photo( $b, 0, null, $b['name'] ) . '</div>';
} elseif ( $b['logo'] ) {
	$media = hub_view_logo( $b, 'ph ph--logo', $b['name'] );
} else {
	$media = '<div class="ph">' . hub_icon( $b['icon'] ) . '</div>';
}
?>
<article class="card card--featured">
        <div class="card__badges"><?php echo 'partner' === $b['plan'] ? '<span class="badge badge--partner">Local Partner</span>' : '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>'; ?></div>
        <?php echo $media; // phpcs:ignore WordPress.Security.EscapeOutput ?>

        <div class="card__body">
          <span class="biz-card__cat"><?php echo esc_html( $b['category']['name'] ); ?></span>
          <h3 class="card__title"><a href="<?php echo esc_url( $b['url'] ); ?>"><?php echo hub_view_name_with_tick( $b ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></h3>
          <p class="card__meta"><span><?php echo hub_icon( 'pin' ) . esc_html( $b['area']['name'] ); ?></span></p>
          <?php if ( $b['tagline'] ) : ?><p class="card__text"><?php echo esc_html( $b['tagline'] ); ?></p><?php endif; ?>

        </div>
      </article>

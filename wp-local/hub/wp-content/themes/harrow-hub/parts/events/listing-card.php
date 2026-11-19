<?php
/**
 * Class card on the Classes and activities page: type, when, area, ages, price, a short text and who runs it.
 * The data-* attributes drive the hub.js filters (?type=, ?area=, ?day=, ?age=).
 *
 * $args: class (events class shape)
 */
defined( 'ABSPATH' ) || exit;

$c    = $args['class'];
$card = $c['card'];
if ( $c['image'] ) {
	$media = hub_view_class_media( $c );
} else {
	$media = '<div class="' . ( $c['featured'] ? 'ph ph--dark' : 'ph' ) . '">' . hub_icon( $c['icon'] ) . '</div>';
}
// Classes run by real businesses link to the business
$provider = ! $c['sample'] && $c['provider_url'] ? '<a href="' . esc_url( $c['provider_url'] ) . '">' . esc_html( $card['provider'] ) . '</a>' : esc_html( $card['provider'] );
?>
      <article class="card event-card<?php echo $c['featured'] ? ' card--featured' : ''; ?>" data-type="<?php echo esc_attr( $c['type']['slug'] ); ?>" data-area="<?php echo esc_attr( $c['area']['slug'] ); ?>" data-day="<?php echo esc_attr( $card['day'] ); ?>" data-ages="<?php echo esc_attr( implode( ' ', $c['age_groups'] ) ); ?>">
        <div class="card__badges"><?php echo $c['featured'] ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : ''; ?></div>
        <?php echo $media; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>

        <div class="card__body">
          <span class="biz-card__cat"><?php echo esc_html( $c['type']['name'] ); ?></span>
          <h3 class="card__title"><a href="<?php echo esc_url( $c['url'] ); ?>"><?php echo esc_html( $c['title'] ); ?></a></h3>
          <p class="card__meta"><span><?php echo hub_icon( 'repeat' ) . esc_html( $card['when'] ); ?></span><span><?php echo hub_icon( 'pin' ) . esc_html( $c['area']['name'] ); ?></span></p>
          <p class="card__meta"><span><?php echo hub_icon( 'people' ) . esc_html( $card['age'] ); ?></span><span><?php echo hub_icon( 'ticket' ) . esc_html( $card['price'] ); ?></span></p>
          <p class="card__text"><?php echo esc_html( $card['text'] ); ?></p>
          <p class="card__meta mt-2"><span><?php echo hub_icon( 'store' ) . $provider; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?></span></p>
        </div>
      </article>

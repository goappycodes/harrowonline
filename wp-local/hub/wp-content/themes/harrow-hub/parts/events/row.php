<?php
/**
 * One row of the What's On list: an event, or a weekly class on a given day. The data-* attributes drive the
 * hub.js filters.
 *
 * $args: item (events shape: an event, or a class with "date")
 */
defined( 'ABSPATH' ) || exit;

$x     = $args['item'];
$class = 'class' === $x['kind'];
$ph    = $x['featured'] ? ( $class ? 'ph ph--dark' : 'ph ph--red' ) : 'ph';
$when  = $class ? 'Every ' . $x['day_name'] . ', ' . $x['time'] : $x['time'];
?>
    <article class="event-row<?php echo $x['featured'] ? ' event-row--featured' : ''; ?>" data-kind="<?php echo esc_attr( $x['kind'] ); ?>" data-date="<?php echo esc_attr( $x['date'] ); ?>"<?php echo hub_view_event_filter_attrs( $x ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
      <?php echo hub_view_date_tile( $x['date'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

      <?php echo $class ? hub_view_class_media( $x, $ph ) : '<div class="' . esc_attr( $ph ) . '">' . hub_icon( $x['icon'] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput ?>

      <div>
        <div class="cluster mb-2"><?php
			echo $x['featured'] ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : '';
			echo $class ? '<span class="badge badge--outline">' . hub_icon( 'repeat' ) . 'Weekly class</span>' : '';
			?><span class="biz-card__cat"><?php echo esc_html( $x['category']['name'] ); ?></span></div>
        <h3 class="event-row__title"><a href="<?php echo esc_url( $x['url'] ); ?>"><?php echo esc_html( $x['title'] ); ?></a></h3>
        <p class="card__meta"><span><?php echo hub_icon( $class ? 'repeat' : 'clock' ) . esc_html( $when ); ?></span><span><?php echo hub_icon( 'pin' ) . esc_html( $x['venue'] . ', ' . $x['area']['name'] ); ?></span><span><?php echo hub_icon( 'people' ) . esc_html( $x['age'] ); ?></span></p><?php if ( '' !== $x['description'] ) : ?>

      <p class="card__text"><?php echo esc_html( $x['description'] ); ?></p><?php endif; ?>

      </div>
      <div class="event-row__side"><strong><?php echo esc_html( $x['price'] ); ?></strong></div>
    </article>

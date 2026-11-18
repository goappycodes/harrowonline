<?php
/**
 * Weekly class card ("More classes" on a class page).
 *
 * $args: class (events class shape)
 */
defined( 'ABSPATH' ) || exit;

$c = $args['class'];
?>
<article class="card event-card">
        <div class="card__badges"><?php echo $c['featured'] ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : ''; ?></div>
        <?php echo hub_view_class_media( $c ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

        <div class="card__body">
          <span class="biz-card__cat"><?php echo esc_html( $c['type']['name'] ); ?></span>
          <h3 class="card__title"><a href="<?php echo esc_url( $c['url'] ); ?>"><?php echo esc_html( $c['title'] ); ?></a></h3>
          <p class="card__meta"><span><?php echo hub_icon( 'repeat' ) . esc_html( 'Every ' . $c['day_name'] . ', ' . $c['time'] ); ?></span><span><?php echo hub_icon( 'pin' ) . esc_html( $c['area']['name'] ); ?></span></p>
          <p class="card__meta"><span><?php echo hub_icon( 'ticket' ) . esc_html( $c['price'] ); ?></span><span><?php echo hub_icon( 'people' ) . esc_html( $c['age'] ); ?></span></p>
        </div>
      </article>

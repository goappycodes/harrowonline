<?php
/**
 * Event card (homepage "This weekend", "More coming up" on an event page).
 *
 * $args: event (events shape)
 */
defined( 'ABSPATH' ) || exit;

$e = $args['event'];
?>
<article class="card event-card<?php echo $e['featured'] ? ' card--featured' : ''; ?>">
        <?php if ( $e['featured'] ) : ?><div class="card__badges"><span class="badge badge--featured"><?php echo hub_icon( 'star' ); ?>Featured</span></div>
        <?php endif; ?><?php echo hub_view_date_tile( $e['date'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

        <?php echo hub_view_event_media( $e, $e['featured'] ? ' ph--red' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

        <div class="card__body">
          <span class="biz-card__cat"><?php echo esc_html( $e['category']['name'] ); ?></span>
          <h3 class="card__title"><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
          <p class="card__meta"><span><?php echo hub_icon( 'clock' ) . esc_html( $e['time'] ); ?></span><span><?php echo hub_icon( 'pin' ) . esc_html( $e['area']['name'] ); ?></span></p>
          <p class="card__meta"><span><?php echo hub_icon( 'ticket' ) . esc_html( $e['price'] ); ?></span></p>
        </div>
      </article>

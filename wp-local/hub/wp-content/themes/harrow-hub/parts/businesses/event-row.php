<?php
/**
 * One of a business's events or weekly classes, on its profile.
 *
 * $args: event (businesses event row: title, url, date, when, price, featured, weekly)
 */
defined( 'ABSPATH' ) || exit;

$ev = $args['event'];
$d  = date_create_immutable( $ev['date'] );
?>
<article class="event-row<?php echo $ev['featured'] ? ' event-row--featured' : ''; ?>">
          <time class="date-tile" datetime="<?php echo esc_attr( $ev['date'] ); ?>"><span class="date-tile__m"><?php echo $d->format( 'M' ); ?></span><span class="date-tile__d"><?php echo $d->format( 'j' ); ?></span><span class="date-tile__w"><?php echo $d->format( 'D' ); ?></span></time>
          <div class="ph"><?php echo hub_icon( $ev['weekly'] ? 'repeat' : 'calendar' ); ?></div>
          <div>
            <div class="cluster mb-2"><?php echo ( $ev['featured'] ? '<span class="badge badge--featured">Featured</span>' : '' ) . ( $ev['weekly'] ? '<span class="badge">' . hub_icon( 'repeat' ) . 'Weekly class</span>' : '' ); ?></div>
            <h3 class="event-row__title"><a href="<?php echo esc_url( $ev['url'] ); ?>"><?php echo esc_html( $ev['title'] ); ?></a></h3>
            <p class="card__meta"><span><?php echo hub_icon( 'clock' ) . esc_html( $ev['when'] ); ?></span></p>
          </div>
          <div class="event-row__side"><strong><?php echo esc_html( $ev['price'] ); ?></strong></div>
        </article>

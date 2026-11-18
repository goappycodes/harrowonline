<?php
/**
 * An event's page body: /events/{slug}/. Past events keep their page, marked as ended.
 *
 * $args: event (events shape), organiser (businesses shape | null), more (events)
 */
defined( 'ABSPATH' ) || exit;

$e      = $args['event'];
$org    = $args['organiser'];
$past   = $e['past'];
$d      = date_create_immutable( $e['date'] );
$url    = HUB_ORIGIN . '/events/' . $e['slug'] . '/';   // shared links always point at the live site
$events = hub_url( 'events/' );
$cat_u  = add_query_arg( 'cat', $e['category']['slug'], $events );
$maps   = 'https://www.google.com/maps/search/?api=1&query=' . hub_quote_plus( $e['venue'] . ' ' . $e['area']['name'] . ' Harrow' );
$long   = hub_events_rule_long_date( $d ) . ' ' . $d->format( 'Y' );
$when   = $long . ' · ' . $e['time'] . ( $e['note'] ? '. ' . $e['note'] . '.' : '' );
$price  = $e['price'] . ( '' !== $e['age'] ? ' · ' . $e['age'] : '' );
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol>
    <li><a href="<?php echo esc_url( hub_url() ); ?>">Hub</a></li>
    <li><a href="<?php echo esc_url( $events ); ?>">What&rsquo;s On</a></li>
    <li><a href="<?php echo esc_url( $cat_u ); ?>"><?php echo esc_html( $e['category']['name'] ); ?></a></li>
    <li><span aria-current="page"><?php echo esc_html( $e['title'] ); ?></span></li>
  </ol>
</nav>
<?php if ( $past ) : ?>

<div class="ho-container mt-4">
  <div class="notice notice--warn"><?php echo hub_icon( 'alert' ); ?>

    <div><p><strong>This event has ended.</strong> It took place on <?php echo esc_html( $long ); ?>.</p>
    <p><a href="<?php echo esc_url( $events ); ?>">See what&rsquo;s on in Harrow this week</a></p></div>
  </div>
</div>
<?php endif; ?>

<article class="event-page--simple">
  <div class="ho-container offer-simple event-simple">
    <?php echo hub_view_event_media( $e, ' offer-simple__media' . ( $e['featured'] ? ' ph--red' : '' ), true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

    <div class="offer-simple__main">
      <div class="cluster offer-simple__tags"><?php echo $e['featured'] ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : ''; ?><a class="biz-card__cat" href="<?php echo esc_url( $cat_u ); ?>"><?php echo esc_html( $e['category']['name'] ); ?></a></div>
      <h1 class="offer-simple__title event-simple__title"><?php echo esc_html( $e['title'] ); ?></h1>
      <ul class="event-lines">
        <li><?php echo hub_icon( 'calendar' ); ?><span><?php echo esc_html( $when ); ?></span></li>
        <li><?php echo hub_icon( 'pin' ); ?><span><?php echo esc_html( $e['venue'] . ', ' . $e['area']['name'] ); ?> &middot; <a href="<?php echo esc_url( $maps ); ?>" rel="noopener" target="_blank">Directions</a></span></li>
        <li><?php echo hub_icon( 'ticket' ); ?><span><?php echo esc_html( $price ); ?></span></li>
      </ul><?php
		if ( ! $past ) :
			list( $google, $outlook, $ics ) = hub_view_calendar_links( $e, $url );
			$share = hub_quote( $e['title'] . ', ' . $d->format( 'D j M' ) . ' ' . $url, '' );
			?>

      <div class="event-actions" id="add-to-calendar">
        <span>Add to calendar</span>
        <a href="<?php echo esc_url( $google ); ?>" rel="noopener" target="_blank">Google</a>
        <a href="<?php echo esc_url( $outlook ); ?>" rel="noopener" target="_blank">Outlook</a>
        <a download="<?php echo esc_attr( $e['slug'] ); ?>.ics" href="<?php echo esc_attr( $ics ); ?>">Apple (.ics)</a>
      </div>
      <div class="share-row" aria-label="Share this event">
        <span>Share</span>
        <a href="<?php echo esc_url( 'https://wa.me/?text=' . $share ); ?>" rel="noopener" target="_blank" aria-label="Share on WhatsApp"><?php echo hub_icon( 'whatsapp' ); ?></a>
        <a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . hub_quote( $url, '' ) ); ?>" rel="noopener" target="_blank" aria-label="Share on Facebook"><?php echo hub_icon( 'facebook' ); ?></a>
        <a href="<?php echo esc_url( 'mailto:?subject=' . hub_quote( $e['title'] ) . '&body=' . hub_quote( $url, '' ) ); ?>" aria-label="Share by email"><?php echo hub_icon( 'mail' ); ?></a>
        <button type="button" data-copy="<?php echo esc_attr( $url ); ?>" data-copied="Link copied">Copy link</button>
      </div><?php endif; ?>

    </div>
  </div>

  <div class="ho-container offer-simple__below">
    <section class="event-about" aria-labelledby="about-title">
      <h2 id="about-title">About this event</h2>
      <div class="profile-desc"><p><?php echo '' !== $e['description'] ? esc_html( $e['description'] ) : 'Full details from the organiser coming soon.'; ?></p></div>
      <p class="small muted mb-0"><?php echo $e['sample'] ? 'Made-up sample for the preview.' : 'Checked by Harrow Online.'; ?> Details can change, so check with the organiser before you travel. <a class="report-link" href="<?php echo esc_url( hub_url( 'report/' ) ); ?>"><?php echo hub_icon( 'flag' ); ?>Report a problem</a></p>
    </section>
<?php if ( $org ) : ?>

    <a class="offer-biz" href="<?php echo esc_url( $org['url'] ); ?>">
      <span class="mono" aria-hidden="true"><?php echo esc_html( hub_view_two_initials( $org['name'] ) ); ?></span>
      <span class="offer-biz__main"><span class="eyebrow mb-0">Organiser</span><strong><?php echo esc_html( $org['name'] ); ?></strong><span><?php echo esc_html( $org['area']['name'] ); ?></span></span>
      <span class="offer-biz__go">View profile <?php echo hub_icon( 'arrow', 'ho-icon ho-icon--sm' ); ?></span>
    </a>
<?php endif; ?>
<?php if ( $args['more'] ) : ?>

    <section class="event-more" aria-labelledby="more-title">
      <div class="sec-head">
        <h2 class="sec-title" id="more-title">More coming up</h2>
        <a class="link-arrow" href="<?php echo esc_url( $events ); ?>">All events <?php echo hub_icon( 'arrow' ); ?></a>
      </div>
      <div class="grid grid-3">
        <?php
		foreach ( $args['more'] as $m ) {
			get_template_part( 'parts/events/card', null, array( 'event' => $m ) );
		}
		?>
      </div>
    </section>
<?php endif; ?>
  </div>
</article>

<?php
/**
 * A weekly class's page body: /events/{slug}/.
 *
 * $args: class (events class shape), provider (businesses shape | null), more (classes)
 */
defined( 'ABSPATH' ) || exit;

$c       = $args['class'];
$prov    = $args['provider'] && $args['provider']['claimed'] ? $args['provider'] : null;
$url     = HUB_ORIGIN . '/events/' . $c['slug'] . '/';   // shared links always point at the live site
$classes = hub_url( 'events/classes/' );
$maps    = 'https://www.google.com/maps/search/?api=1&query=' . hub_quote_plus( $c['venue'] . ' ' . $c['area']['name'] . ' Harrow' );
$every   = 'Every ' . $c['day_name'] . ', ' . $c['time'];
$share   = hub_quote( $c['title'] . ', every ' . $c['day_name'] . ' ' . $url, '' );
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol>
    <li><a href="<?php echo esc_url( hub_url() ); ?>">Hub</a></li>
    <li><a href="<?php echo esc_url( hub_url( 'events/' ) ); ?>">What&rsquo;s On</a></li>
    <li><a href="<?php echo esc_url( $classes ); ?>">Classes and activities</a></li>
    <li><span aria-current="page"><?php echo esc_html( $c['title'] ); ?></span></li>
  </ol>
</nav>

<article class="event-page--simple">
  <div class="ho-container offer-simple event-simple">
    <?php echo hub_view_class_media( $c, 'ph offer-simple__media', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

    <div class="offer-simple__main">
      <div class="cluster offer-simple__tags"><?php echo $c['featured'] ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : ''; ?><span class="badge badge--outline"><?php echo hub_icon( 'repeat' ); ?>Weekly class</span><a class="biz-card__cat" href="<?php echo esc_url( $classes ); ?>"><?php echo esc_html( $c['type']['name'] ); ?></a></div>
      <h1 class="offer-simple__title event-simple__title"><?php echo esc_html( $c['title'] ); ?></h1>
      <ul class="event-lines">
        <li><?php echo hub_icon( 'repeat' ); ?><span><?php echo esc_html( $every ); ?></span></li>
        <li><?php echo hub_icon( 'pin' ); ?><span><?php echo esc_html( $c['venue'] . ', ' . $c['area']['name'] ); ?> &middot; <a href="<?php echo esc_url( $maps ); ?>" rel="noopener" target="_blank">Directions</a></span></li>
        <li><?php echo hub_icon( 'ticket' ); ?><span><?php echo esc_html( $c['price'] . ' · ' . $c['age'] ); ?></span></li>
      </ul>
      <div class="share-row" aria-label="Share this class">
        <span>Share</span>
        <a href="<?php echo esc_url( 'https://wa.me/?text=' . $share ); ?>" rel="noopener" target="_blank" aria-label="Share on WhatsApp"><?php echo hub_icon( 'whatsapp' ); ?></a>
        <a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . hub_quote( $url, '' ) ); ?>" rel="noopener" target="_blank" aria-label="Share on Facebook"><?php echo hub_icon( 'facebook' ); ?></a>
        <a href="<?php echo esc_url( 'mailto:?subject=' . hub_quote( $c['title'] ) . '&body=' . hub_quote( $url, '' ) ); ?>" aria-label="Share by email"><?php echo hub_icon( 'mail' ); ?></a>
        <button type="button" data-copy="<?php echo esc_attr( $url ); ?>" data-copied="Link copied">Copy link</button>
      </div>
    </div>
  </div>

  <div class="ho-container offer-simple__below">
    <section class="event-about" aria-labelledby="about-title">
      <h2 id="about-title">About this class</h2>
      <div class="profile-desc"><p><?php echo '' !== $c['description'] ? esc_html( $c['description'] ) : 'Full details from the organiser coming soon.'; ?></p></div>
      <p class="small muted mb-0"><?php echo esc_html( $c['note'] ); ?> Check times with the organiser before you go. <a class="report-link" href="<?php echo esc_url( hub_url( 'report/' ) ); ?>"><?php echo hub_icon( 'flag' ); ?>Report a problem</a></p>
    </section>
<?php if ( $prov ) : ?>

    <a class="offer-biz" href="<?php echo esc_url( $prov['url'] ); ?>">
      <?php echo $prov['logo'] ? '<span class="mono mono--logo" aria-hidden="true"><img src="' . esc_url( $prov['logo'] ) . '" alt="" width="480" height="480" loading="lazy"></span>' : '<span class="mono" aria-hidden="true">' . esc_html( hub_view_two_initials( $prov['name'] ) ) . '</span>'; ?>

      <span class="offer-biz__main"><span class="eyebrow mb-0">Run by</span><strong><?php echo esc_html( $prov['name'] ); ?></strong><span><?php echo esc_html( $prov['area']['name'] ); ?></span></span>
      <span class="offer-biz__go">View profile <?php echo hub_icon( 'arrow', 'ho-icon ho-icon--sm' ); ?></span>
    </a>
<?php endif; ?>
    <section class="event-more" aria-labelledby="more-title">
      <div class="sec-head">
        <h2 class="sec-title" id="more-title">More classes</h2>
        <a class="link-arrow" href="<?php echo esc_url( $classes ); ?>">All classes <?php echo hub_icon( 'arrow' ); ?></a>
      </div>
      <div class="grid grid-3"><?php
		foreach ( $args['more'] as $m ) {
			get_template_part( 'parts/events/class-card', null, array( 'class' => $m ) );
		}
		?></div>
    </section>
  </div>
</article>

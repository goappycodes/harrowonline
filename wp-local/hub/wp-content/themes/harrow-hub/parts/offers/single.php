<?php
/**
 * An offer's page body: /offers/{slug}/. Ended offers keep their page, marked as ended.
 *
 * $args: offer (offers shape), more (offers to suggest, offers shape)
 */
defined( 'ABSPATH' ) || exit;

$o      = $args['offer'];
$more   = $args['more'];
$b      = $o['business'];
$ended  = 'ended' === $o['status'];
$sub    = $o['subscriber_only'];
$end    = $o['ends']['date'] ? date_create_immutable( $o['ends']['date'] ) : null;
$long   = $end ? $end->format( 'l j F Y' ) : '';
$offers = hub_url( 'offers/' );
$share  = HUB_ORIGIN . '/offers/' . $o['slug'] . '/';   // shared links always point at the live site
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol>
    <li><a href="<?php echo esc_url( hub_url() ); ?>">Hub</a></li>
    <li><a href="<?php echo esc_url( $offers ); ?>">Local Offers</a></li>
    <li><a href="<?php echo esc_url( add_query_arg( 'cat', $b['category']['slug'], $offers ) ); ?>"><?php echo esc_html( $b['category']['name'] ); ?></a></li>
    <li><span aria-current="page"><?php echo esc_html( $o['title'] ); ?></span></li>
  </ol>
</nav>
<?php if ( $ended ) : ?>

<div class="ho-container mt-4">
  <div class="notice notice--warn"><?php echo hub_icon( 'alert' ); ?>

    <div><p><strong>This offer has ended.</strong> It ran until <?php echo esc_html( $long ); ?> and has come off Local Offers and the business profile.</p>
    <p><a href="<?php echo esc_url( $offers ); ?>">See current offers in Harrow</a></p></div>
  </div>
</div>
<?php endif; ?>

<article class="offer-page offer-page--simple<?php echo $ended ? ' offer-page--ended' : ''; ?>">
  <div class="ho-container offer-simple">
    <div class="offer-simple__main">
      <div class="cluster offer-simple__tags"><?php
		if ( $ended ) {
			echo '<span class="badge badge--unclaimed">Ended</span>';
		} elseif ( $sub ) {
			echo hub_sub_lock();
		} elseif ( $o['ends']['ending_soon'] ) {
			echo '<span class="badge badge--soon">' . hub_icon( 'clock' ) . 'Ends soon</span>';
		}
		?><span class="biz-card__cat"><?php echo esc_html( $b['category']['name'] ); ?></span></div>
      <p class="offer-simple__value"><?php echo esc_html( $o['value'] ); ?></p>
      <h1 class="offer-simple__title"><?php echo esc_html( $o['title'] ); ?></h1>
      <p class="offer-simple__biz"><a href="<?php echo esc_url( $b['url'] ); ?>"><?php echo esc_html( $b['name'] ); ?></a> &middot; <?php echo esc_html( $b['area']['name'] ); ?></p>
      <p class="offer-simple__text"><?php echo esc_html( $o['description'] ); ?></p>
    </div>
    <div class="offer-simple__side">
      <div class="voucher voucher--simple<?php echo $ended ? ' voucher--ended' : ''; ?>" id="redeem">
          <?php if ( $ended ) : ?>
<p class="voucher__state">This offer has ended</p>
          <p class="small mb-0">It ran until <?php echo esc_html( $end->format( 'j F Y' ) ); ?>. Any code for it no longer works.</p>
          <a class="ho-btn ho-btn--primary mt-4" href="<?php echo esc_url( $offers ); ?>">See current offers</a>
          <?php elseif ( $sub ) : ?>
<div data-sub-gate data-offer="<?php echo esc_attr( $o['slug'] ); ?>" data-has-code="<?php echo $o['has_code'] ? 1 : 0; ?>"><p class="small mb-0"><?php echo $o['has_code'] ? 'Your code appears here once you confirm your email.' : 'Full details appear here once you confirm your email.'; ?></p><?php get_template_part( 'parts/offers/unlock-box', null, array( 'offer' => $o, 'note' => false ) ); ?></div>
          <?php elseif ( $o['code'] ) : ?>
<div class="voucher__row"><span class="voucher__code"><?php echo esc_html( $o['code'] ); ?></span>
          <button class="ho-btn ho-btn--dark" type="button" data-copy="<?php echo esc_attr( $o['code'] ); ?>" data-copied="Code copied">Copy code</button></div>
          <?php else : ?>
<p class="voucher__state">No code needed</p>
          <?php endif; ?>
          <?php if ( ! $ended ) : ?><p class="voucher__how"><?php echo esc_html( $o['redeem'] ); ?></p><?php endif; ?>

        </div>
      <p class="offer-simple__meta"><?php
		echo hub_icon( 'calendar', 'ho-icon ho-icon--sm' );
		if ( $end ) {
			echo ( $ended ? 'Ended' : 'Ends' ) . ' ' . esc_html( $end->format( 'j M Y' ) );
			if ( ! $ended && '' !== $o['ends']['days_left_label'] ) {
				echo ' &middot; ' . esc_html( $o['ends']['days_left_label'] );
			}
		} else {
			echo 'No end date';
		}
		?> &middot; <a href="#terms">Terms</a></p>
    </div><?php if ( ! $ended ) : ?>

        <div class="share-row" aria-label="Share this offer">
          <span>Share</span>
          <a href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $o['title'] . ' at ' . $b['name'] . ': ' . $share ) ); ?>" rel="noopener" target="_blank" aria-label="Share on WhatsApp"><?php echo hub_icon( 'whatsapp' ); ?></a>
          <a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $share ) ); ?>" rel="noopener" target="_blank" aria-label="Share on Facebook"><?php echo hub_icon( 'facebook' ); ?></a>
          <a href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( $o['title'] ) . '&body=' . rawurlencode( $share ) ); ?>" aria-label="Share by email"><?php echo hub_icon( 'mail' ); ?></a>
          <button type="button" data-copy="<?php echo esc_attr( $share ); ?>" data-copied="Link copied">Copy link</button>
        </div><?php endif; ?>

  </div>

  <div class="ho-container offer-simple__below">
    <details class="offer-terms" id="terms">
      <summary>Terms and conditions</summary>
      <ul class="terms-list"><?php
		foreach ( $o['terms'] as $t ) {
			echo '<li>' . esc_html( $t ) . '</li>';
		}
		echo $end ? '<li>' . ( $ended ? 'Ended' : 'Ends' ) . ' at 11.59pm on ' . esc_html( $long ) . '.</li>'
			: '<li>No end date has been set. The offer runs until the business ends it.</li>';
		if ( $o['code'] ) {
			echo '<li>Use the code <strong>' . esc_html( $o['code'] ) . '</strong> where asked.</li>';
		}
		echo '<li>Run by ' . esc_html( $b['name'] ) . '. The business can end the offer early if it can no longer honour it.</li>';
		?></ul>
      <p class="small muted mb-0"><?php echo $b['sample'] ? 'Made-up sample for the preview.' : 'Checked by Harrow Online.'; ?> Offers are run by the business, so check the terms with them before you buy. <a class="report-link" href="<?php echo esc_url( hub_url( 'report/' ) ); ?>"><?php echo hub_icon( 'flag' ); ?>Report a problem</a></p>
    </details>

    <a class="offer-biz" href="<?php echo esc_url( $b['url'] ); ?>">
      <span class="ph ph--square offer-biz__pic<?php echo $b['image'] ? ' ph--photo' : ''; ?>"><?php
		if ( $b['image'] ) {
			echo '<img src="' . esc_url( $b['image']['url'] ) . '" alt="' . esc_attr( $b['image']['alt'] ) . '" width="1200" height="800" loading="lazy" decoding="async">';
		} else {
			echo hub_icon( $b['icon'] );
		}
		?></span>
      <span class="offer-biz__main"><strong><?php echo esc_html( $b['name'] ); ?></strong><span><?php echo esc_html( $b['address'] ); ?></span></span>
      <span class="offer-biz__go">View business <?php echo hub_icon( 'arrow', 'ho-icon ho-icon--sm' ); ?></span>
    </a>
<?php if ( $more ) : ?>

    <section class="offer-more" aria-labelledby="more-title">
      <div class="sec-head">
        <h2 class="sec-title" id="more-title">More Local Offers</h2>
        <a class="link-arrow" href="<?php echo esc_url( $offers ); ?>">All offers <?php echo hub_icon( 'arrow' ); ?></a>
      </div>
      <div class="offer-tiles offer-tiles--4"><?php
		foreach ( $more as $m ) {
			get_template_part( 'parts/offers/tile', null, array( 'offer' => $m ) );
		}
		?></div>
    </section>
<?php endif; ?>
  </div>
</article>

<?php
/**
 * Offer pages: /offers/{slug}/ (gen_claimed.offer_page). Ended offers keep their page, marked as ended and noindexed.
 */
defined( 'ABSPATH' ) || exit;

/** [ page settings, main HTML ] for one offer. */
function hub_offer_page( array $x, array $o ) {
	$url    = HUB_ORIGIN . '/offers/' . $o['slug'] . '/';
	$ended  = ! $o['live'];
	$title  = $o['title'];
	$name   = $x['name'];
	$area   = hub_area_name( $x['area'] );
	$end    = $o['end_date'];
	$long   = $end ? $end->format( 'l' ) . ' ' . $end->format( 'j' ) . ' ' . $end->format( 'F Y' ) : '';
	$sample = hub_is_sample( $x );
	$soon   = $o['days_left'] >= 0 && $o['days_left'] <= 7;
	$c      = $x['claimed'];
	$offers = hub_url( 'offers/' );

	$status = '';
	if ( $ended ) {
		$status = '<span class="badge badge--unclaimed">Ended</span>';
	} elseif ( ! empty( $o['sub'] ) ) {
		$status = hub_sub_lock();
	} elseif ( $soon ) {
		$status = '<span class="badge badge--soon">' . hub_icon( 'clock' ) . 'Ends soon</span>';
	}
	if ( $ended ) {
		$left = 'This offer has ended';
	} elseif ( null === $end ) {
		$left = '';
	} elseif ( 0 === $o['days_left'] ) {
		$left = 'Last day today';
	} else {
		$left = $o['days_left'] . ' day' . ( 1 !== $o['days_left'] ? 's' : '' ) . ' left';
	}

	if ( $ended ) {
		$voucher_main = '<p class="voucher__state">This offer has ended</p>
          <p class="small mb-0">It ran until ' . $end->format( 'j' ) . ' ' . $end->format( 'F Y' ) . '. Any code for it no longer works.</p>
          <a class="ho-btn ho-btn--primary mt-4" href="' . $offers . '">See current offers</a>';
	} elseif ( ! empty( $o['sub'] ) ) {
		$what         = ! empty( $o['code'] ) ? 'Your code appears here once you confirm your email.' : 'Full details appear here once you confirm your email.';
		$voucher_main = '<div data-sub-gate data-offer="' . $o['slug'] . '" data-has-code="' . ( ! empty( $o['code'] ) ? 1 : 0 ) . '"><p class="small mb-0">' . $what . '</p>' . hub_unlock_box( $x, $o, false ) . '</div>';
	} elseif ( ! empty( $o['code'] ) ) {
		$voucher_main = '<div class="voucher__row"><span class="voucher__code">' . $o['code'] . '</span>
          <button class="ho-btn ho-btn--dark" type="button" data-copy="' . $o['code'] . '" data-copied="Code copied">Copy code</button></div>';
	} else {
		$voucher_main = '<p class="voucher__state">No code needed</p>';
	}
	$redeem  = $ended ? '' : '<p class="voucher__how">' . ( isset( $o['redeem'] ) ? $o['redeem'] : 'Show this page when you pay.' ) . '</p>';
	$voucher = '<div class="voucher voucher--simple' . ( $ended ? ' voucher--ended' : '' ) . '" id="redeem">
          ' . $voucher_main . '
          ' . $redeem . '
        </div>';

	$terms = implode( '', array_map( function ( $t ) { return "<li>{$t}</li>"; }, isset( $o['terms'] ) ? $o['terms'] : array() ) );
	$terms .= $end ? '<li>' . ( $ended ? 'Ended' : 'Ends' ) . ' at 11.59pm on ' . $long . '.</li>' : '<li>No end date has been set. The offer runs until the business ends it.</li>';
	if ( ! empty( $o['code'] ) && empty( $o['sub'] ) ) {
		$terms .= '<li>Use the code <strong>' . $o['code'] . '</strong> where asked.</li>';
	}
	$terms .= '<li>Run by ' . hub_e( $name ) . '. The business can end the offer early if it can no longer honour it.</li>';

	$live = hub_all_offers();
	$more = array();
	foreach ( $live as $i => $pair ) {
		if ( $pair[1]['_id'] !== $o['_id'] ) {
			$more[] = array( $pair[0], $pair[1], $i );
		}
	}
	usort( $more, function ( $a, $b ) use ( $x ) {
		$k = function ( $p ) use ( $x ) {
			return array( $p[0]['slug'] !== $x['slug'] ? 1 : 0, $p[0]['cat'] !== $x['cat'] ? 1 : 0, empty( $p[1]['featured'] ) ? 1 : 0, $p[2] );
		};
		return $k( $a ) <=> $k( $b );
	} );
	$more_html = '';
	if ( $more ) {
		$tiles     = implode( '', array_map( function ( $p ) { return hub_offer_tile( $p[0], $p[1] ); }, array_slice( $more, 0, 4 ) ) );
		$more_html = '
    <section class="offer-more" aria-labelledby="more-title">
      <div class="sec-head">
        <h2 class="sec-title" id="more-title">More Local Offers</h2>
        <a class="link-arrow" href="' . $offers . '">All offers ' . hub_icon( 'arrow' ) . '</a>
      </div>
      <div class="offer-tiles offer-tiles--4">' . $tiles . '</div>
    </section>';
	}

	$share     = hub_quote( hub_plain( $title ) . ' at ' . hub_plain( $name ) . ': ' . $url, '' );
	$share_row = $ended ? '' : '
        <div class="share-row" aria-label="Share this offer">
          <span>Share</span>
          <a href="https://wa.me/?text=' . $share . '" rel="noopener" target="_blank" aria-label="Share on WhatsApp">' . hub_icon( 'whatsapp' ) . '</a>
          <a href="https://www.facebook.com/sharer/sharer.php?u=' . hub_quote( $url, '' ) . '" rel="noopener" target="_blank" aria-label="Share on Facebook">' . hub_icon( 'facebook' ) . '</a>
          <a href="mailto:?subject=' . hub_quote( hub_plain( $title ) ) . '&amp;body=' . hub_quote( $url, '' ) . '" aria-label="Share by email">' . hub_icon( 'mail' ) . '</a>
          <button type="button" data-copy="' . $url . '" data-copied="Link copied">Copy link</button>
        </div>';
	$biz_link = hub_url( 'business/' . $x['slug'] . '/' );
	$biz_row  = '
    <a class="offer-biz" href="' . $biz_link . '">
      <span class="' . hub_ph_cls( $x, 'ph ph--square offer-biz__pic' ) . '">' . hub_photo_inner( $x, 0, isset( $c['icon'] ) ? $c['icon'] : 'store' ) . '</span>
      <span class="offer-biz__main"><strong>' . hub_e( $name ) . '</strong><span>' . hub_address_line( $x, true ) . '</span></span>
      <span class="offer-biz__go">View business ' . hub_icon( 'arrow', 'ho-icon ho-icon--sm' ) . '</span>
    </a>';
	$ended_note = $ended ? '
<div class="ho-container mt-4">
  <div class="notice notice--warn">' . hub_icon( 'alert' ) . '
    <div><p><strong>This offer has ended.</strong> It ran until ' . $long . ' and has come off Local Offers and the business profile.</p>
    <p><a href="' . $offers . '">See current offers in Harrow</a></p></div>
  </div>
</div>' : '';
	$pub       = $sample ? 'Made-up sample for the preview.' : 'Checked by Harrow Online.';
	$still     = ( $ended || '' === $left ) ? '' : ' &middot; ' . $left;
	$meta_line = $end ? hub_icon( 'calendar', 'ho-icon ho-icon--sm' ) . ( $ended ? 'Ended' : 'Ends' ) . ' ' . $end->format( 'j' ) . ' ' . $end->format( 'M Y' ) . $still
		: hub_icon( 'calendar', 'ho-icon ho-icon--sm' ) . 'No end date';

	$body = '<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol>
    <li><a href="' . hub_url() . '">Hub</a></li>
    <li><a href="' . $offers . '">Local Offers</a></li>
    <li><a href="' . $offers . '?cat=' . $x['cat'] . '">' . hub_e( hub_cat_name( $x['cat'] ) ) . '</a></li>
    <li><span aria-current="page">' . $title . '</span></li>
  </ol>
</nav>
' . $ended_note . '
<article class="offer-page offer-page--simple' . ( $ended ? ' offer-page--ended' : '' ) . '">
  <div class="ho-container offer-simple">
    <div class="offer-simple__main">
      <div class="cluster offer-simple__tags">' . $status . '<span class="biz-card__cat">' . hub_e( hub_cat_name( $x['cat'] ) ) . '</span></div>
      <p class="offer-simple__value">' . hub_offer_value( $o ) . '</p>
      <h1 class="offer-simple__title">' . $title . '</h1>
      <p class="offer-simple__biz"><a href="' . $biz_link . '">' . hub_e( $name ) . '</a> &middot; ' . hub_e( $area ) . '</p>
      <p class="offer-simple__text">' . $o['text'] . '</p>
    </div>
    <div class="offer-simple__side">
      ' . $voucher . '
      <p class="offer-simple__meta">' . $meta_line . ' &middot; <a href="#terms">Terms</a></p>
    </div>' . $share_row . '
  </div>

  <div class="ho-container offer-simple__below">
    <details class="offer-terms" id="terms">
      <summary>Terms and conditions</summary>
      <ul class="terms-list">' . $terms . '</ul>
      <p class="small muted mb-0">' . $pub . ' Offers are run by the business, so check the terms with them before you buy. <a class="report-link" href="' . hub_url( 'report/' ) . '">' . hub_icon( 'flag' ) . 'Report a problem</a></p>
    </details>
' . $biz_row . '
' . $more_html . '
  </div>
</article>
';
	$node = array( '@type' => 'Offer', '@id' => $url . '#offer', 'name' => hub_plain( $title ), 'description' => hub_plain( $o['text'] ), 'url' => $url );
	if ( $end ) {
		$node['validThrough'] = $end->format( 'Y-m-d' ) . 'T23:59:00';
	}
	$node['availability'] = 'https://schema.org/' . ( $ended ? 'Discontinued' : 'InStock' );
	$node['offeredBy']    = array( '@type' => 'LocalBusiness', 'name' => hub_plain( $name ), 'url' => HUB_ORIGIN . '/business/' . $x['slug'] . '/' );
	hub_add_ld( hub_json( array( '@context' => 'https://schema.org', '@graph' => array( $node ) ) ) );   // printed in <head>, as in the prototype

	$when = $end ? ( $ended ? 'Ended' : 'Ends' ) . ' ' . $end->format( 'j' ) . ' ' . $end->format( 'F Y' ) . '.' : 'No end date.';
	$full = hub_plain( $title . ' at ' . $name );
	$meta = array(
		'title'       => mb_strlen( $full ) <= 50 ? $full : hub_plain( $title ),
		'description' => trim( preg_replace( '/\s+/u', ' ', hub_plain( $title . ' at ' . $name . ', ' . $area . '. ' . $o['text'] . ' ' . $when ) ) ),
		'nav' => 'offers', 'hubnav' => 'offers', 'body' => 'page-offer',
	);
	if ( $ended || $sample ) {
		$meta['robots'] = 'noindex, follow';
	}
	return array( $meta, $body );
}

<?php
/**
 * Newsletter and promotions (converted from src/pages/account-bookings.html).
 * URL: /account/newsletter/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Your Harrow Online Newsletter advert bookings and featured items: dates, artwork, approval status and results.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'Newsletter and promotions',
) );
get_header();
?>
<div class="ho-container dash">
  <?php hub_view_account_nav( 'newsletter' ); ?>

  <div class="dash-main dash-simple">
    <!-- Simplified (client feedback, Nov 2026): newsletter adverts, featured items, and the Local Partner newsletter feature. -->
    <div class="dash-hello">
      <div>
        <h1>Newsletter and promotions</h1>
        <p>Your adverts in the Harrow Online Newsletter, and offers or events you&rsquo;ve paid to feature.</p>
      </div>
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'advertise/newsletter/' ) ); ?>"><svg class="ho-icon"><use href="#i-plus"/></svg>Book an advert</a>
    </div>

    <section aria-labelledby="sp-title">
      <h2 class="dash-h2" id="sp-title">Newsletter adverts</h2>
      <ul class="promo-list">
        <li>
          <span class="promo-list__main"><strong>Daily News</strong><small>Mon 30 Nov to Fri 4 Dec 2026</small></span>
          <span class="status status--pending">Being checked</span>
          <span class="promo-list__price">&pound;180.00</span>
          <a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( hub_url( 'advertise/newsletter/' ) ); ?>">Edit advert</a>
        </li>
        <li>
          <span class="promo-list__main"><strong>The Weekend</strong><small>Thu 3 Dec 2026</small></span>
          <span class="status status--published">Approved</span>
          <span class="promo-list__price">&pound;60.00</span>
          <a class="ho-btn ho-btn--sm ho-btn--ghost" href="#invoice-pdf">Receipt</a>
        </li>
        <li>
          <span class="promo-list__main"><strong>Harrow Business</strong><small>Mon 26 Oct 2026 &middot; Refunded: the logo was too small to read</small></span>
          <span class="status status--rejected">Not approved</span>
          <span class="promo-list__price"><s>&pound;50.00</s></span>
          <a class="ho-btn ho-btn--sm ho-btn--ghost" href="<?php echo esc_url( hub_url( 'advertise/newsletter/' ) ); ?>">Book again</a>
        </li>
      </ul>
      <p class="help mt-3">We check every advert before it goes out. If we can&rsquo;t approve it, we tell you why and refund you in full.</p>
      <!-- WP: from the sponsor inventory table (edition + date unique). Creative status: In review / Approved / Rejected (admin reject triggers a Stripe refund). -->
    </section>

    <section aria-labelledby="ft-title">
      <h2 class="dash-h2" id="ft-title">Featured offers and events</h2>
      <ul class="promo-list">
        <li>
          <span class="promo-list__main"><strong>20% off your first cut</strong><small>Offer &middot; 11 to 18 Nov 2026</small></span>
          <span class="status status--active">Featured now</span>
          <span class="promo-list__price">&pound;15.00</span>
        </li>
        <li>
          <span class="promo-list__main"><strong>Barbering taster class</strong><small>Weekly class &middot; 9 to 16 Nov 2026</small></span>
          <span class="status status--active">Featured now</span>
          <span class="promo-list__price">&pound;15.00</span>
        </li>
        <li>
          <span class="promo-list__main"><strong>Halloween kids&rsquo; cuts</strong><small>Event &middot; 24 to 31 Oct 2026</small></span>
          <span class="status status--ended">Finished</span>
          <span class="promo-list__price">&pound;15.00</span>
        </li>
      </ul>
      <p class="help mt-3">Feature an offer or event from its edit page. The Featured label comes off by itself at the end of the 7 days.</p>
    </section>

    <section class="dash-upsell" aria-labelledby="lp-title">
      <span class="dash-upsell__icon"><svg class="ho-icon"><use href="#i-mail"/></svg></span>
      <div>
        <h2 class="dash-h2 mb-1" id="lp-title">A Daily News advert every month</h2>
        <p>Local Partner (&pound;79 a month) includes one Daily News advert each month and one Harrow Online Facebook Group post each month. You&rsquo;re on Featured.</p>
      </div>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See Local Partner</a>
    </section>
  </div>
</div>
<?php get_footer();

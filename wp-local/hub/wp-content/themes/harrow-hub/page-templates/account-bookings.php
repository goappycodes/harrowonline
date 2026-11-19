<?php
/**
 * Newsletter and promotions (converted from src/pages/account-bookings.html).
 * URL: /account/newsletter/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Your Harrow Online Newsletter sponsor bookings and featured items: dates, artwork, approval status and results.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'Newsletter and promotions',
) );
get_header();
?>
<div class="ho-container dash">
  <aside class="dash-nav" aria-label="Account">
    <div class="dash-nav__who">
      <?php hub_view_account_who(); ?>
    </div>
    <div class="dash-add">
      <!-- "+ Add" on every account page (client plan, Nov 2026): an event, a class or an offer, one tap from anywhere. -->
      <details class="add-menu" data-add-menu>
        <summary class="ho-btn ho-btn--primary ho-btn--sm"><svg class="ho-icon"><use href="#i-plus"/></svg>Add</summary>
        <div class="add-menu__pop">
          <p class="add-menu__label">Add to the Hub</p>
          <a class="add-menu__item" href="<?php echo esc_url( hub_url( 'account/events/edit/' ) ); ?>"><span class="add-menu__icon"><svg class="ho-icon"><use href="#i-calendar"/></svg></span><span><strong>Event</strong><small>A one-off date, on What&rsquo;s On</small></span></a>
          <a class="add-menu__item" href="<?php echo esc_url( hub_url( 'account/classes/edit/' ) ); ?>"><span class="add-menu__icon"><svg class="ho-icon"><use href="#i-repeat"/></svg></span><span><strong>Class</strong><small>Runs every week, on Classes</small></span></a>
          <a class="add-menu__item" href="<?php echo esc_url( hub_url( 'account/offers/edit/' ) ); ?>"><span class="add-menu__icon"><svg class="ho-icon"><use href="#i-tag"/></svg></span><span><strong>Offer</strong><small>A deal for Harrow Online readers</small></span></a>
        </div>
      </details>
    </div>

    <ul>
      <li><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>"><svg class="ho-icon"><use href="#i-home"/></svg>Overview</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/listings/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-store"/></svg>My listing</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>"><svg class="ho-icon"><use href="#i-tag"/></svg>Offers<span class="num">1<span class="ho-sr"> offer needs changes</span></span></a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>"><svg class="ho-icon"><use href="#i-calendar"/></svg>Events</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>"><svg class="ho-icon"><use href="#i-repeat"/></svg>Classes</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/newsletter/' ) ); ?>" aria-current="page"><svg class="ho-icon"><use href="#i-megaphone"/></svg>Newsletter and promotions</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/billing/' ) ); ?>"><svg class="ho-icon"><use href="#i-card"/></svg>Plan and billing</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/settings/' ) ); ?>"><svg class="ho-icon"><use href="#i-settings"/></svg>Settings</a></li>
    </ul>
    <hr>
    <ul>
      <li><a href="<?php echo esc_url( hub_view_sign_out_url() ); ?>"><svg class="ho-icon"><use href="#i-logout"/></svg>Sign out</a></li>
    </ul>
  </aside>

  <div class="dash-main dash-simple">
    <!-- Simplified (client feedback, Nov 2026): sponsor slots, featured items, and the Local Partner newsletter feature. -->
    <div class="dash-hello">
      <div>
        <h1>Newsletter and promotions</h1>
        <p>Your sponsor slots in the Harrow Online Newsletter, and offers or events you&rsquo;ve paid to feature.</p>
      </div>
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'advertise/newsletter/' ) ); ?>"><svg class="ho-icon"><use href="#i-plus"/></svg>Book a sponsor slot</a>
    </div>

    <section aria-labelledby="sp-title">
      <h2 class="dash-h2" id="sp-title">Newsletter sponsor slots</h2>
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
        <h2 class="dash-h2 mb-1" id="lp-title">A Daily News sponsor slot every month</h2>
        <p>Local Partner (&pound;79 a month) includes one sponsored slot each month in the Daily News newsletter and one sponsored post each month in the Harrow Online Facebook Group. You&rsquo;re on Featured.</p>
      </div>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See Local Partner</a>
    </section>
  </div>
</div>
<?php get_footer();

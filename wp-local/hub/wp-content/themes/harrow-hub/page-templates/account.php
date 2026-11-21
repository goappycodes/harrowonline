<?php
/**
 * My account (converted from src/pages/account.html).
 * URL: /account/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Your Harrow Online Hub dashboard: listing status, profile views, offers, events, plan and next steps for your business.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'My account',
) );
get_header();
?>
<div class="ho-container dash">
  <?php hub_view_account_nav( 'overview' ); ?>

  <div class="dash-main dash-simple">
    <!-- Simplified dashboard (client feedback, Nov 2026): what needs me, what can I do, how am I doing. -->
    <div class="dash-hello">
      <div>
        <?php hub_view_account_hello(); ?>
      </div>
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'account/listings/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-edit"/></svg>Edit my listing</a>
    </div>

    <!-- Getting started checklist (client plan, Nov 2026). Shown after a claim is approved; each task ticks itself off.
         WP: done states come from the listing. Preview: /account/?welcome=1 shows a newly approved claim (only step 1 done). -->
    <details class="setup" data-setup>
      <summary class="setup__head">
        <span class="setup__title"><strong>Getting started</strong><span data-setup-count>All 5 done</span></span>
        <span class="setup__bar" aria-hidden="true"><span data-setup-bar style="width: 100%"></span></span>
        <svg class="ho-icon setup__chev" aria-hidden="true"><use href="#i-chevron-down"/></svg>
      </summary>
      <ol class="setup__list">
        <li class="setup__item is-done" data-task="claim">
          <span class="setup__tick" role="img" aria-label="Done"></span>
          <div><strong>Claim your listing</strong><span>Approved. Your listing has the &ldquo;Verified by the business&rdquo; badge.</span></div>
        </li>
        <li class="setup__item is-done" data-task="listing">
          <span class="setup__tick" role="img" aria-label="Done"></span>
          <div><strong>Complete your listing</strong><span>Add a description and your opening hours, so people know what you do and when you&rsquo;re open.</span></div>
          <div class="setup__go"><a class="ho-btn ho-btn--sm ho-btn--primary" href="<?php echo esc_url( hub_url( 'account/listings/edit/' ) ); ?>">Complete it</a></div>
        </li>
        <li class="setup__item is-done" data-task="photos">
          <span class="setup__tick" role="img" aria-label="Done"></span>
          <div><strong>Add photos</strong><span>Listings with photos get more views. Add at least 3.</span></div>
          <div class="setup__go"><a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/listings/edit/#photos' ) ); ?>">Add photos</a></div>
        </li>
        <li class="setup__item is-done" data-task="event">
          <span class="setup__tick" role="img" aria-label="Done"></span>
          <div><strong>Add your first event or class</strong><span>One-off events go on What&rsquo;s On, weekly classes on Classes.</span></div>
          <div class="setup__go"><a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/events/edit/' ) ); ?>">Add an event</a><a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/classes/edit/' ) ); ?>">Add a class</a></div>
        </li>
        <li class="setup__item is-done" data-task="offer">
          <span class="setup__tick" role="img" aria-label="Done"></span>
          <div><strong>Add your first offer</strong><span>A deal for Harrow Online readers, like 20% off a first visit.</span></div>
          <div class="setup__go"><a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/offers/edit/' ) ); ?>">Add an offer</a></div>
        </li>
      </ol>
    </details>

    <a class="dash-alert" href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>" data-not-welcome>
      <span class="dash-alert__icon"><svg class="ho-icon"><use href="#i-alert"/></svg></span>
      <span class="dash-alert__text"><strong>1 offer needs a small change</strong><span>&ldquo;Kids&rsquo; cuts &pound;10 on weekdays&rdquo;: our team left a note on what to fix.</span></span>
      <span class="dash-alert__go">Fix it <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span>
    </a>

    <section aria-labelledby="do-title">
      <h2 class="dash-h2" id="do-title">What would you like to do?</h2>
      <div class="dash-actions dash-actions--4">
        <a class="dash-action" href="<?php echo esc_url( hub_url( 'account/offers/edit/' ) ); ?>">
          <span class="dash-action__icon"><svg class="ho-icon"><use href="#i-tag"/></svg></span>
          <strong>Add an offer</strong>
          <span>A deal for Harrow Online readers, like 20% off a first visit.</span>
        </a>
        <a class="dash-action" href="<?php echo esc_url( hub_url( 'account/events/edit/' ) ); ?>">
          <span class="dash-action__icon"><svg class="ho-icon"><use href="#i-calendar"/></svg></span>
          <strong>Add an event</strong>
          <span>A one-off date. It goes on What&rsquo;s On once we&rsquo;ve checked it.</span>
        </a>
        <a class="dash-action" href="<?php echo esc_url( hub_url( 'account/classes/edit/' ) ); ?>">
          <span class="dash-action__icon"><svg class="ho-icon"><use href="#i-repeat"/></svg></span>
          <strong>Add a class</strong>
          <span>Something that runs every week. It goes on Classes.</span>
        </a>
        <a class="dash-action" href="<?php echo esc_url( hub_url( 'account/listings/edit/#photos' ) ); ?>">
          <span class="dash-action__icon"><svg class="ho-icon"><use href="#i-upload"/></svg></span>
          <strong>Add photos</strong>
          <span data-welcome-text="Listings with photos get more views. Add at least 3.">Listings with photos get more views. You have 3 so far.</span>
        </a>
      </div>
    </section>

    <p class="notice notice--plain" data-welcome-only hidden><svg class="ho-icon"><use href="#i-chart"/></svg><span>Your listing&rsquo;s views, calls and website visits will show here once people start finding you.</span></p>

    <section aria-labelledby="stats-title" data-not-welcome>
      <div class="dash-row-head">
        <h2 class="dash-h2" id="stats-title">How your listing is doing</h2>
        <span class="small muted">Last 30 days</span>
      </div>
      <div class="dash-stats">
        <div><strong>1,284</strong><span>people viewed your listing</span><em>Up 18%</em></div>
        <div><strong>46</strong><span>tapped to call you</span><em>Up 9%</em></div>
        <div><strong>132</strong><span>visited your website</span><em>Up 22%</em></div>
      </div>
    </section>

    <div class="dash-two">
      <section class="dash-box" aria-labelledby="upd-title" data-not-welcome>
        <h2 class="dash-h2" id="upd-title">Latest updates</h2>
        <ul class="dash-updates">
          <li><span><strong>Free beard trim with any haircut</strong><small>Offer &middot; starts 1 Dec</small></span><span class="status status--pending">Being checked</span></li>
          <li><span><strong>Christmas charity cut-a-thon</strong><small>Event &middot; Sun 13 Dec</small></span><span class="status status--pending">Being checked</span></li>
          <li><span><strong>Beard care workshop</strong><small>Event &middot; approved 9 Nov</small></span><span class="status status--published">Live</span></li>
        </ul>
        <p class="small muted mb-0">We usually check new offers and events within 2 working days and email you either way.</p>
      </section>

      <section class="dash-box" aria-labelledby="plan-title">
        <h2 class="dash-h2" id="plan-title">Your plan</h2>
        <p class="dash-plan"><strong>Featured</strong><span>&pound;29 a month &middot; renews 2 December 2026</span></p>
        <p class="small muted">Includes priority placement, more photos, Local Offers, and events and classes.</p>
        <div class="cluster">
          <a class="ho-btn ho-btn--outline ho-btn--sm" href="<?php echo esc_url( hub_url( 'account/billing/' ) ); ?>">Plan and billing</a>
          <a class="link-arrow small" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Compare plans <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
        </div>
      </section>
    </div>
  </div>
</div>
<?php get_footer();

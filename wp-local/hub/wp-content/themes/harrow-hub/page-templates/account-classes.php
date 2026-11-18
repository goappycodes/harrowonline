<?php
/**
 * My classes (converted from src/pages/account-classes.html).
 * URL: /account/classes/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Manage the weekly classes you list on Harrow Online Hub: drafts, classes being checked and published classes.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'My classes',
) );
get_header();
?>
<div class="ho-container dash">
  <aside class="dash-nav" aria-label="Account">
    <div class="dash-nav__who">
      <span class="mono mono--sm" aria-hidden="true">SR</span>
      <div><strong>Station Road Barbers</strong><span>Featured plan</span></div>
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
      <li><a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>" aria-current="page"><svg class="ho-icon"><use href="#i-repeat"/></svg>Classes</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/newsletter/' ) ); ?>"><svg class="ho-icon"><use href="#i-megaphone"/></svg>Newsletter and promotions</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/billing/' ) ); ?>"><svg class="ho-icon"><use href="#i-card"/></svg>Plan and billing</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/settings/' ) ); ?>"><svg class="ho-icon"><use href="#i-settings"/></svg>Settings</a></li>
    </ul>
    <hr>
    <ul>
      <li><a href="<?php echo esc_url( hub_url( 'sign-in/' ) ); ?>"><svg class="ho-icon"><use href="#i-logout"/></svg>Sign out</a></li>
    </ul>
  </aside>

  <div class="dash-main">
    <div class="dash-head">
      <div>
        <h1>Classes</h1>
        <p>Anything you run every week. Each class keeps one page on <a href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>">Classes</a> for as long as it runs.</p>
      </div>
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'account/classes/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-plus"/></svg>Add a class</a>
    </div>

    <div class="notice notice--plain mb-5">
      <svg class="ho-icon"><use href="#i-info"/></svg>
      <div>
        <p><strong>Something on a single date?</strong> Add it as an <a href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>">event</a> instead, so it goes on What&rsquo;s On for that day.</p>
      </div>
    </div>

    <nav class="tabs" aria-label="Filter classes">
      <a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>" aria-current="page">All <span class="count">2</span></a>
      <a href="<?php echo esc_url( hub_url( 'account/classes/?status=published' ) ); ?>">Published <span class="count">1</span></a>
      <a href="<?php echo esc_url( hub_url( 'account/classes/?status=draft' ) ); ?>">Draft <span class="count">1</span></a>
    </nav>

    <div class="table-wrap">
      <table class="table table--stack">
        <thead>
          <tr><th scope="col">Class</th><th scope="col">Status</th><th scope="col">Featured</th><th scope="col"><span class="ho-sr">Actions</span></th></tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="table__item">
                <div><strong>Barbering taster class</strong><span><span class="badge badge--outline"><svg class="ho-icon"><use href="#i-repeat"/></svg>Weekly</span> Every Tuesday, 6.30pm &middot; Ages 16+ &middot; Term ends 15 Dec</span></div>
              </div>
            </td>
            <td data-label="Status"><span class="status status--published">Published</span></td>
            <td data-label="Featured"><span class="badge badge--featured">Until 16 Nov</span></td>
            <td>
              <div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/classes/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-edit"/></svg>Edit</a></div>
            </td>
          </tr>
          <tr>
            <td>
              <div class="table__item">
                <div><strong>Saturday kids&rsquo; cuts club</strong><span><span class="badge badge--outline"><svg class="ho-icon"><use href="#i-repeat"/></svg>Weekly</span> Every Saturday, 10am &middot; Ages 5 to 11 &middot; Not sent yet</span></div>
              </div>
            </td>
            <td data-label="Status"><span class="status status--draft">Draft</span></td>
            <td data-label="Featured"><span class="muted">No</span></td>
            <td>
              <div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/classes/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-edit"/></svg>Edit</a></div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- WP: a class stays published until its term end date passes (if set), then moves to Ended. -->

    <div class="grid grid-2 mt-6">
      <div class="panel panel--soft">
        <h2 class="panel__title">How classes work</h2>
        <ul class="ticks ticks--dark small mb-0">
          <li>A class keeps the same page. Update it whenever the day, time or price changes.</li>
          <li>Set term dates and it only shows while it&rsquo;s running.</li>
          <li>Your classes also show on your listing.</li>
        </ul>
      </div>
      <div class="panel">
        <h2 class="panel__title"><svg class="ho-icon"><use href="#i-star"/></svg> Feature a class</h2>
        <p class="small">Featured classes show first on Classes and What&rsquo;s On, and in The Weekend newsletter, with a Featured label.</p>
        <p class="small mb-0"><strong>&pound;15 for 7 days</strong>, one-off payment. No subscription.</p>
      </div>
    </div>
  </div>
</div>
<?php get_footer();

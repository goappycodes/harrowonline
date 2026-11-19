<?php
/**
 * My events (converted from src/pages/account-events.html).
 * URL: /account/events/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Manage the one-off events you list on Harrow Online Hub: events being checked, published dates and ended events.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'My events',
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
      <li><a href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>" aria-current="page"><svg class="ho-icon"><use href="#i-calendar"/></svg>Events</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>"><svg class="ho-icon"><use href="#i-repeat"/></svg>Classes</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/newsletter/' ) ); ?>"><svg class="ho-icon"><use href="#i-megaphone"/></svg>Newsletter and promotions</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/billing/' ) ); ?>"><svg class="ho-icon"><use href="#i-card"/></svg>Plan and billing</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/settings/' ) ); ?>"><svg class="ho-icon"><use href="#i-settings"/></svg>Settings</a></li>
    </ul>
    <hr>
    <ul>
      <li><a href="<?php echo esc_url( hub_view_sign_out_url() ); ?>"><svg class="ho-icon"><use href="#i-logout"/></svg>Sign out</a></li>
    </ul>
  </aside>

  <div class="dash-main">
    <div class="dash-head">
      <div>
        <h1>Events</h1>
        <p>One-off dates, like a workshop or an open day. Each one is checked by our team before it goes on What&rsquo;s On.</p>
      </div>
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'account/events/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-plus"/></svg>Add an event</a>
    </div>

    <div class="notice notice--plain mb-5">
      <svg class="ho-icon"><use href="#i-info"/></svg>
      <div>
        <p><strong>Runs every week?</strong> Add it on <a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>">Classes</a> instead, so it keeps one page for as long as it runs.</p>
      </div>
    </div>

    <nav class="tabs" aria-label="Filter events">
      <a href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>" aria-current="page">All <span class="count">3</span></a>
      <a href="<?php echo esc_url( hub_url( 'account/events/?status=published' ) ); ?>">Published <span class="count">1</span></a>
      <a href="<?php echo esc_url( hub_url( 'account/events/?status=pending' ) ); ?>">Pending <span class="count">1</span></a>
      <a href="<?php echo esc_url( hub_url( 'account/events/?status=ended' ) ); ?>">Ended <span class="count">1</span></a>
    </nav>

    <div class="table-wrap">
      <table class="table table--stack">
        <thead>
          <tr><th scope="col">Event</th><th scope="col">Status</th><th scope="col">Featured</th><th scope="col"><span class="ho-sr">Actions</span></th></tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="table__item">
                <div><strong>Beard care workshop</strong><span>Sat 21 Nov, 2pm &middot; Approved 9 Nov</span></div>
              </div>
            </td>
            <td data-label="Status"><span class="status status--published">Published</span></td>
            <td data-label="Featured"><span class="muted">No</span></td>
            <td>
              <div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/events/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-edit"/></svg>Edit</a></div>
            </td>
          </tr>
          <tr>
            <td>
              <div class="table__item">
                <div><strong>Christmas charity cut-a-thon</strong><span>Sun 13 Dec, 10am &middot; Sent for approval 10 Nov</span></div>
              </div>
            </td>
            <td data-label="Status"><span class="status status--pending">Pending</span></td>
            <td data-label="Featured"><span class="muted">No</span></td>
            <td>
              <div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/events/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-edit"/></svg>Edit</a></div>
            </td>
          </tr>
          <tr>
            <td>
              <div class="table__item">
                <div><strong>Halloween kids&rsquo; cuts</strong><span>Sat 31 Oct &middot; Ended automatically</span></div>
              </div>
            </td>
            <td data-label="Status"><span class="status status--ended">Ended</span></td>
            <td data-label="Featured"><span class="muted">Ended 31 Oct</span></td>
            <td>
              <div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/events/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-edit"/></svg>Edit</a></div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- WP: events move to Ended after their end date. -->

    <div class="grid grid-2 mt-6">
      <div class="panel panel--soft">
        <h2 class="panel__title">How events work</h2>
        <ul class="ticks ticks--dark small mb-0">
          <li>Events show on What&rsquo;s On for their date and leave once they&rsquo;ve finished.</li>
          <li>Upcoming events also show on your listing.</li>
          <li>Something weekly? Add it as a class instead.</li>
        </ul>
      </div>
      <div class="panel">
        <h2 class="panel__title"><svg class="ho-icon"><use href="#i-star"/></svg> Feature an event</h2>
        <p class="small">Featured events show first on What&rsquo;s On and in The Weekend newsletter, with a Featured label.</p>
        <p class="small mb-0"><strong>&pound;15 for 7 days</strong>, one-off payment. No subscription.</p>
      </div>
    </div>
  </div>
</div>
<?php get_footer();

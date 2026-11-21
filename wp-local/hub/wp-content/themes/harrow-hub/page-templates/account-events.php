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
<?php $hub_list = hub_submissions_list( 'event', $_GET ); // phpcs:ignore WordPress.Security.NonceVerification ?>
<div class="ho-container dash">
  <?php hub_view_account_nav( 'events' ); ?>

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

    <?php get_template_part( 'parts/account/list', null, array( 'list' => $hub_list ) ); ?>

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
        <p class="small mb-0"><strong><?php echo esc_html( $hub_list['feature_price'] ); ?> for 7 days</strong>, one-off payment. No subscription.</p>
      </div>
    </div>
  </div>
</div>
<?php get_footer();

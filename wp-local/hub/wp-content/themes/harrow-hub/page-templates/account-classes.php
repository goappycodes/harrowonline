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
<?php $hub_list = hub_submissions_list( 'class', $_GET ); // phpcs:ignore WordPress.Security.NonceVerification ?>
<div class="ho-container dash">
  <?php hub_view_account_nav( 'classes' ); ?>

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

    <?php get_template_part( 'parts/account/list', null, array( 'list' => $hub_list ) ); ?>

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
        <p class="small mb-0"><strong><?php echo esc_html( $hub_list['feature_price'] ); ?> for 7 days</strong>, one-off payment. No subscription.</p>
      </div>
    </div>
  </div>
</div>
<?php get_footer();

<?php
/**
 * My offers (converted from src/pages/account-offers.html).
 * URL: /account/offers/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Manage your Local Offers on Harrow Online Hub: live, scheduled and ended offers, views and subscriber unlocks.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'My offers',
) );
get_header();
?>
<?php $hub_list = hub_submissions_list( 'offer', $_GET ); // phpcs:ignore WordPress.Security.NonceVerification ?>
<div class="ho-container dash">
  <?php hub_view_account_nav( 'offers' ); ?>

  <div class="dash-main">
    <div class="dash-head">
      <div>
        <h1>Offers</h1>
        <p>Local Offers<?php echo esc_html( '' !== $hub_list['business'] ? ' for ' . $hub_list['business'] : '' ); ?>. Every offer is checked by our team before it goes live.</p>
      </div>
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'account/offers/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-plus"/></svg>Add an offer</a>
    </div>


    <?php get_template_part( 'parts/account/list', null, array( 'list' => $hub_list ) ); ?>

    <div class="grid grid-2 mt-6">
      <div class="panel panel--soft">
        <h2 class="panel__title">How offers work</h2>
        <ul class="ticks ticks--dark small mb-0">
          <li>Send an offer for approval. We usually check it within 2 working days.</li>
          <li>Approved offers show on Local Offers and on your listing, and can appear in the newsletter.</li>
          <li>Every approved offer gets its own page with the terms, end date and code. Share the link on your own social media.</li>
          <li>Offers come off the Hub by themselves on their end date. You&rsquo;ll still see them here.</li>
        </ul>
      </div>
      <div class="panel">
        <h2 class="panel__title"><svg class="ho-icon"><use href="#i-star"/></svg> Feature an offer</h2>
        <p class="small">Featured offers show first on Local Offers and in the newsletter&rsquo;s offers section, with a Featured label.</p>
        <p class="small mb-0"><strong><?php echo esc_html( $hub_list['feature_price'] ); ?> for 7 days</strong>, one-off payment. No subscription.</p>
      </div>
    </div>
  </div>
</div>
<?php get_footer();

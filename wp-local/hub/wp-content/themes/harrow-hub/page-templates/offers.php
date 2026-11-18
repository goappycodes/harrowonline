<?php
/**
 * Local Offers: deals from Harrow businesses.
 * URL: /offers/   Filters: ?cat=, ?area=, ?sub=1
 *
 * Data: hub_offers_list() (app/modules/offers), hub_categories_list(), hub_areas_list(). Markup: parts/offers/.
 * Edited by hand (tools/build_theme.py leaves it alone).
 */
defined( 'ABSPATH' ) || exit;

$hub_offers = hub_offers_list( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- read-only filters
$hub_cats   = hub_categories_list();
$hub_areas  = hub_areas_list();

hub_set_page( array(
	'body' => 'page-offers',
	'description' => 'Discounts and deals from Harrow businesses, from restaurants and cafés to gyms and barbers. Some offers are just for Harrow Online Newsletter subscribers.',
	'hubnav' => 'offers',
	'nav' => 'offers',
	'title' => 'Local Offers: deals from Harrow businesses',
) );
get_header();
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><span aria-current="page">Local Offers</span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">Local Offers</h1>
  <p class="page-head__lead">Deals from Harrow businesses, checked by our team and removed automatically when they end. Some are just for newsletter subscribers.</p>
</header>

<div class="ho-container">
<?php
get_template_part( 'parts/offers/filters', null, array(
	'list'       => $hub_offers,
	'categories' => $hub_cats['items'],
	'areas'      => $hub_areas['items'],
) );
?>

  <div class="offer-cards" data-offers-list>
    <?php
	foreach ( $hub_offers['items'] as $hub_offer ) {
		get_template_part( 'parts/offers/card', null, array( 'offer' => $hub_offer ) );
	}
	?>
  </div>

  <p class="small muted mt-5">Offers are run by the businesses listed. Check terms with the business before you buy. Spotted a problem with an offer? <a href="<?php echo esc_url( hub_url( 'report/' ) ); ?>">Let us know</a>.</p>

  <div class="cta-band mt-6">
    <div>
      <h2>Run an offer for Harrow</h2>
      <p>Hub members on a paid plan can post Local Offers from their dashboard. Offers appear here, on your profile and in the newsletter.</p>
    </div>
    <div class="cta-band__actions">
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See plans</a>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>">Add an offer</a>
    </div>
  </div>
</div>
<?php get_footer();

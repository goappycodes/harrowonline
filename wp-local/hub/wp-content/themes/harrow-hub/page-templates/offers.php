<?php
/**
 * Local Offers: deals from Harrow businesses.
 * URL: /offers/   Filters: ?cat=, ?area=, ?sub=1
 *
 * Data: hub_offers_list() (app/modules/offers), hub_categories_list(), hub_areas_list(). The page's text:
 * hub_pages_text( 'offers' ) (app/modules/pages; wp-admin: the "Page text" box on Pages > Local Offers). Markup: parts/offers/.
 * Edited by hand (tools/build_theme.py leaves it alone).
 */
defined( 'ABSPATH' ) || exit;

$hub_offers = hub_offers_list( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- read-only filters
$hub_cats   = hub_categories_list();
$hub_areas  = hub_areas_list();
$hub_t      = hub_pages_text( 'offers' );
$hub_e      = function ( $k ) use ( $hub_t ) { return esc_html( $hub_t[ $k ] ); };   // a text field, escaped

hub_set_page( array(
	'body' => 'page-offers',
	'description' => 'Discounts and deals from Harrow businesses, from restaurants and cafés to gyms and barbers. Some offers are just for Harrow Online Newsletter subscribers.',
	'hubnav' => 'offers',
	'nav' => 'offers',
	'title' => 'Local Offers: deals from Harrow businesses',   // page title and description: Yoast SEO box, else these
) );
get_header();
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><span aria-current="page"><?php echo $hub_e( 'offers_crumb' ); ?></span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title"><?php echo $hub_e( 'offers_title' ); ?></h1>
  <p class="page-head__lead"><?php echo $hub_e( 'offers_lead' ); ?></p>
</header>

<div class="ho-container">
<?php
get_template_part( 'parts/offers/filters', null, array(
	'list'             => $hub_offers,
	'categories'       => $hub_cats['items'],
	'areas'            => $hub_areas['items'],
	'all_label'        => $hub_t['offers_all_label'],
	'empty_text'       => $hub_t['offers_empty_text'],
	'empty_link_label' => $hub_t['offers_empty_link_label'],
) );
?>

  <div class="offer-cards" data-offers-list>
    <?php
	foreach ( $hub_offers['items'] as $hub_offer ) {
		get_template_part( 'parts/offers/card', null, array( 'offer' => $hub_offer ) );
	}
	?>
  </div>

<?php if ( ! $hub_t['offers_note_hide'] ) : ?>
  <p class="small muted mt-5"><?php echo $hub_e( 'offers_note' ); ?> <a href="<?php echo esc_url( $hub_t['offers_note_link'] ); ?>"><?php echo $hub_e( 'offers_note_link_label' ); ?></a>.</p>
<?php endif; ?>

<?php if ( ! $hub_t['offers_cta_hide'] ) : ?>
  <div class="cta-band mt-6">
    <div>
      <h2><?php echo $hub_e( 'offers_cta_title' ); ?></h2>
      <p><?php echo $hub_e( 'offers_cta_text' ); ?></p>
    </div>
    <div class="cta-band__actions">
<?php echo hub_view_cta_buttons( $hub_t['offers_cta_buttons'], false ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper ?>
    </div>
  </div>
<?php endif; ?>
</div>
<?php get_footer();

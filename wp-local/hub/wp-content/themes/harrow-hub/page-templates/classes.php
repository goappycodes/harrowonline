<?php
/**
 * Classes and activities in Harrow: every weekly class, in the page's own order.
 * URL: /events/classes/   Filters: ?type=, ?age=, ?area=, ?day= (pages are filtered server-side; hub.js 6c does the same in the browser)
 *
 * Data: hub_classes_list() (app/modules/events), hub_areas_list(). The page's text: hub_pages_text( 'classes' )
 * (app/modules/pages; wp-admin: the "Page text" box on Pages > Classes and activities). Markup: parts/events/listing-card.php.
 * Edited by hand (tools/build_theme.py leaves it alone).
 */
defined( 'ABSPATH' ) || exit;

$hub_list  = hub_classes_list( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- read-only filters
$hub_f     = $hub_list['filters'];
$hub_areas = hub_areas_list();
$hub_any   = '' !== $hub_f['type'] . $hub_f['age'] . $hub_f['area'] . $hub_f['day'];
$hub_base  = hub_url( 'events/classes/' );
$hub_t     = hub_pages_text( 'classes' );
$hub_up    = hub_pages_text( 'events' )['events_crumb'];   // the breadcrumb's "What's On", as on that page
$hub_e     = function ( $k ) use ( $hub_t ) { return esc_html( $hub_t[ $k ] ); };   // a text field, escaped

hub_set_page( array(
	'body' => 'page-classes',
	'description' => 'Weekly classes and activities across Harrow: children\'s clubs, fitness classes, dance schools, workshops and evening courses. Filter by age, area and day.',
	'hubnav' => 'events',
	'nav' => 'classes',
	'title' => 'Classes and activities in Harrow',   // page title and description: Yoast SEO box, else these
) );
get_header();
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><a href="<?php echo esc_url( hub_url( 'events/' ) ); ?>"><?php echo esc_html( $hub_up ); ?></a></li><li><span aria-current="page"><?php echo $hub_e( 'classes_crumb' ); ?></span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title"><?php echo $hub_e( 'classes_title' ); ?></h1>
  <p class="page-head__lead"><?php echo $hub_e( 'classes_lead' ); ?></p>
</header>

<div class="ho-container">
  <!-- Filters: class type chips, then one line with the count and three small filters that apply on change -->
  <div class="chips chips--scroll" aria-label="Class type">
    <a class="chip" data-type="" href="<?php echo esc_url( $hub_base ); ?>"<?php echo '' === $hub_f['type'] ? ' aria-current="page"' : ''; ?>><?php echo $hub_e( 'classes_all_label' ); ?> <span class="count"><?php echo (int) $hub_list['all_total']; ?></span></a>
<?php foreach ( $hub_list['types'] as $hub_type ) : ?>
    <a class="chip" data-type="<?php echo esc_attr( $hub_type['slug'] ); ?>" href="<?php echo esc_url( add_query_arg( 'type', $hub_type['slug'], $hub_base ) ); ?>"<?php echo $hub_type['slug'] === $hub_f['type'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $hub_type['name'] ); ?> <span class="count"><?php echo (int) $hub_type['count']; ?></span></a>
<?php endforeach; ?>
  </div>
  <div class="fline">
    <div class="fline__count">
      <p class="toolbar__count" data-classes-count><strong><?php echo (int) $hub_list['total']; ?></strong> <?php echo $hub_any ? ( 1 === $hub_list['total'] ? 'class matches' : 'classes match' ) : 'classes and activities'; ?></p>
    </div>
    <form class="fline__form" action="<?php echo esc_url( $hub_base ); ?>" method="get" aria-label="Filter classes" data-autofilter>
      <input type="hidden" name="type" value="<?php echo esc_attr( $hub_f['type'] ); ?>" data-keep-type>
      <label class="fsel"><span class="ho-sr">Age</span><select id="c-age" name="age"><option value="">Any age</option><?php
		foreach ( array( 'under-5' => 'Under 5s', '5-11' => '5 to 11', '12-17' => '12 to 17', 'adults' => 'Adults', '60-plus' => 'Over 60s' ) as $hub_v => $hub_n ) {
			echo ' <option value="' . esc_attr( $hub_v ) . '"' . selected( $hub_v, $hub_f['age'], false ) . '>' . esc_html( $hub_n ) . '</option>';
		}
		?></select></label>
      <label class="fsel"><span class="ho-sr">Area</span><select id="c-area" name="area"><option value="">All of Harrow</option><?php
		foreach ( $hub_areas['items'] as $hub_a ) {
			echo '<option value="' . esc_attr( $hub_a['slug'] ) . '"' . selected( $hub_a['slug'], $hub_f['area'], false ) . '>' . esc_html( $hub_a['name'] ) . '</option>';
		}
		?></select></label>
      <label class="fsel"><span class="ho-sr">Day of the week</span><select id="c-day" name="day"><option value="">Any day</option><?php
		foreach ( HUB_EVENTS_DAY_NAMES as $hub_i => $hub_n ) {
			$hub_v = hub_events_rule_day_slug( $hub_i );
			echo ' <option value="' . esc_attr( $hub_v ) . '"' . selected( $hub_v, $hub_f['day'], false ) . '>' . esc_html( $hub_n ) . '</option>';
		}
		?></select></label>
      <button class="ho-btn ho-btn--dark ho-btn--sm fpanel__go" type="submit">Show classes</button>
      <a class="fpanel__clear" href="<?php echo esc_url( $hub_base ); ?>" data-filter-clear hidden>Clear</a>
    </form>
  </div>

  <h2 class="ho-sr" id="classes-list">All classes and activities</h2>
  <div class="notice mb-5" data-classes-empty<?php echo $hub_list['total'] ? ' hidden' : ''; ?>><?php echo hub_icon( 'calendar' ); ?><p><?php echo $hub_e( 'classes_empty_text' ); ?> <a href="<?php echo esc_url( $hub_base ); ?>"><?php echo $hub_e( 'classes_empty_link_label' ); ?></a>.</p></div>
  <div class="grid grid-3" data-classes-list>
<?php
foreach ( $hub_list['items'] as $hub_c ) {
	get_template_part( 'parts/events/listing-card', null, array( 'class' => $hub_c ) );
}
?>
  </div>

<?php if ( ! $hub_t['classes_cta_hide'] ) : ?>
  <div class="cta-band mt-7">
    <div>
      <h2><?php echo $hub_e( 'classes_cta_title' ); ?></h2>
      <p><?php echo $hub_e( 'classes_cta_text' ); ?></p>
    </div>
    <div class="cta-band__actions">
<?php echo hub_view_cta_buttons( $hub_t['classes_cta_buttons'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper ?>
    </div>
  </div>
<?php endif; ?>
</div>
<?php get_footer();

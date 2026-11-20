<?php
/**
 * What's On in Harrow: featured events, then everything on each of the next 7 days.
 * URL: /events/   Filters (hub.js 6b): ?when=today|weekend|7days|date&date=, ?area=, ?cat[]=, ?age[]=, ?free=1, ?classes=0
 *
 * Data: hub_events_whats_on() (app/modules/events), hub_areas_list(). The page's text: hub_pages_text( 'events' )
 * (app/modules/pages; wp-admin: the "Page text" box on Pages > What's On). Markup: parts/events/row.php.
 * Edited by hand (tools/build_theme.py leaves it alone).
 */
defined( 'ABSPATH' ) || exit;

$hub_wo    = hub_events_whats_on();
$hub_areas = hub_areas_list();
$hub_w0    = date_create_immutable( $hub_wo['weekend']['from'] );
$hub_w1    = date_create_immutable( $hub_wo['weekend']['to'] );
$hub_from  = date_create_immutable( $hub_wo['range']['from'] );
$hub_span  = hub_events_rule_long_date( $hub_from ) . ' to ' . hub_events_rule_long_date( date_create_immutable( $hub_wo['range']['to'] ) );
$hub_t     = hub_pages_text( 'events' );
$hub_e     = function ( $k ) use ( $hub_t ) { return esc_html( $hub_t[ $k ] ); };   // a text field, escaped
$hub_u     = function ( $k ) use ( $hub_t ) { return esc_url( $hub_t[ $k ] ); };    // a link field, escaped

hub_set_page( array(
	'body' => 'page-events',
	'description' => 'Events, things to do and classes across Harrow this week and weekend: family days out, live music, markets and community events.',
	'hubnav' => 'events',
	'nav' => 'events',
	'title' => 'What\'s On in Harrow: events this week and weekend',   // page title and description: Yoast SEO box, else these
) );
get_header();
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><span aria-current="page"><?php echo $hub_e( 'events_crumb' ); ?></span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title"><?php echo $hub_e( 'events_title' ); ?></h1>
  <p class="page-head__lead"><?php echo $hub_e( 'events_lead' ); ?> <a href="<?php echo $hub_u( 'events_lead_link' ); ?>"><?php echo $hub_e( 'events_lead_link_label' ); ?></a><?php echo $hub_e( 'events_lead_after' ); ?></p>
</header>

<div class="ho-container">
  <div class="layout-filters">
    <aside class="filters" id="filters" aria-label="Filter events">
      <form action="<?php echo esc_url( hub_url( 'events/' ) ); ?>" method="get">
        <div class="filters__head">
          <h2>Filter events</h2>
          <button class="ho-btn ho-btn--ghost ho-btn--sm filters__close" type="button" data-filters-close aria-label="Close filters"><?php echo hub_icon( 'close' ); ?></button>
        </div>
        <fieldset class="filters__group">
          <legend>Date</legend>
          <ul class="check-list">
            <li><label><input type="radio" name="when" value="" checked> Any date</label></li>
            <li><label><input type="radio" name="when" value="today"> Today</label></li>
            <li><label><input type="radio" name="when" value="weekend"> This weekend <span class="count"><?php echo esc_html( $hub_w0->format( 'j' ) . ' to ' . $hub_w1->format( 'j M' ) ); ?></span></label></li>
            <li><label><input type="radio" name="when" value="7days"> Next 7 days</label></li>
            <li><label><input type="radio" name="when" value="date" data-reveal="#pick-date"> Choose a date</label></li>
          </ul>
          <div class="field mt-4" id="pick-date" hidden>
            <label for="f-date">Date</label>
            <input class="input input--sm" id="f-date" type="date" name="date" min="<?php echo esc_attr( $hub_wo['range']['from'] ); ?>" max="<?php echo esc_attr( $hub_wo['range']['to'] ); ?>" value="<?php echo esc_attr( $hub_wo['weekend']['from'] ); ?>">
          </div>
        </fieldset>
        <fieldset class="filters__group">
          <legend>Area</legend>
          <label class="ho-sr" for="f-area">Area</label>
          <select class="select select--sm" id="f-area" name="area"><option value="">All of Harrow</option><?php
			foreach ( $hub_areas['items'] as $hub_a ) {
				echo '<option value="' . esc_attr( $hub_a['slug'] ) . '">' . esc_html( $hub_a['name'] ) . '</option>';
			}
			?></select>
        </fieldset>
        <fieldset class="filters__group">
          <legend>Category</legend>
          <ul class="check-list">
<?php foreach ( $hub_wo['categories'] as $hub_c ) : ?>
              <li><label><input type="checkbox" name="cat[]" value="<?php echo esc_attr( $hub_c['slug'] ); ?>"> <?php echo esc_html( $hub_c['name'] ); ?> <span class="count"><?php echo (int) $hub_c['count']; ?></span></label></li>
<?php endforeach; ?>
          </ul>
        </fieldset>
        <fieldset class="filters__group">
          <legend>Age</legend>
          <ul class="check-list">
            <li><label><input type="checkbox" name="age[]" value="under-5"> Under 5s</label></li>
            <li><label><input type="checkbox" name="age[]" value="5-11"> 5 to 11</label></li>
            <li><label><input type="checkbox" name="age[]" value="12-17"> 12 to 17</label></li>
            <li><label><input type="checkbox" name="age[]" value="adults"> Adults</label></li>
            <li><label><input type="checkbox" name="age[]" value="60-plus"> Over 60s</label></li>
          </ul>
        </fieldset>
        <fieldset class="filters__group">
          <legend>Price</legend>
          <ul class="check-list">
            <li><label><input type="checkbox" name="free" value="1"> Free events only</label></li>
            <li><label><input type="checkbox" name="classes" value="0"> Hide weekly classes</label></li>
          </ul>
        </fieldset>
        <div class="filters__foot">
          <a class="ho-btn ho-btn--ghost" href="<?php echo esc_url( hub_url( 'events/' ) ); ?>">Clear</a>
          <button class="ho-btn ho-btn--primary" type="submit">Show results</button>
        </div>
      </form>
    </aside>

    <div data-events-list data-today="<?php echo esc_attr( $hub_wo['today'] ); ?>" data-weekend="<?php echo esc_attr( $hub_wo['weekend']['from'] . ',' . $hub_wo['weekend']['to'] ); ?>">
      <div class="toolbar">
        <p class="toolbar__count" data-events-count><strong><?php echo (int) $hub_wo['total']; ?></strong> events and classes, <?php echo esc_html( $hub_span ); ?></p>
        <div class="toolbar__right">
          <button class="ho-btn ho-btn--outline ho-btn--sm filters-toggle" type="button" data-filters-open aria-controls="filters" aria-expanded="false"><?php echo hub_icon( 'filter' ); ?>Filters</button>
          <nav class="seg" aria-label="View">
            <a href="<?php echo esc_url( hub_url( 'events/' ) ); ?>" aria-current="page"><?php echo hub_icon( 'list' ); ?>List</a>
            <a href="<?php echo esc_url( hub_url( 'events/month/' ) ); ?>"><?php echo hub_icon( 'calendar' ); ?>Month</a>
          </nav>
        </div>
      </div>

<?php if ( ! $hub_t['events_chips_hide'] ) : ?>
      <div class="chips chips--scroll mb-5" aria-label="Quick dates">
<?php
	foreach ( $hub_t['events_chips'] as $hub_chip ) {
		$hub_when = hub_pages_rule_when( $hub_chip['link'] );   // a date link is shown as selected by its date
		echo '        <a class="chip" href="' . esc_url( $hub_chip['link'] ) . '"' . ( '' !== $hub_when ? ' data-when="' . esc_attr( $hub_when ) . '"' : '' ) . '>'
			. ( '' !== $hub_chip['icon'] ? hub_icon( sanitize_key( $hub_chip['icon'] ), 'ho-icon ho-icon--sm' ) : '' ) . esc_html( $hub_chip['label'] ) . "</a>\n";
	}
	?>
      </div>
<?php endif; ?>

      <div class="notice mb-5" data-events-empty hidden><?php echo hub_icon( 'calendar' ); ?><p><?php echo $hub_e( 'events_empty_text' ); ?> <a href="<?php echo $hub_u( 'events_empty_link' ); ?>"><?php echo $hub_e( 'events_empty_link_label' ); ?></a>.</p></div>

<?php if ( $hub_wo['featured'] ) : ?>
  <section class="day-group day-group--featured" data-featured-group aria-labelledby="day-featured">
    <h2 class="day-group__title" id="day-featured"><?php echo hub_icon( 'star' ); ?><?php echo $hub_e( 'events_featured_title' ); ?> <small data-day-count><?php echo count( $hub_wo['featured'] ); ?> coming up</small></h2>
    <div>
<?php
	foreach ( $hub_wo['featured'] as $hub_x ) {
		get_template_part( 'parts/events/row', null, array( 'item' => $hub_x ) );
	}
	?>
    </div>
  </section>
<?php endif; ?>
<?php
foreach ( $hub_wo['days'] as $hub_day ) :
	$hub_d     = date_create_immutable( $hub_day['date'] );
	$hub_n     = count( $hub_day['items'] );
	$hub_label = esc_html( hub_events_rule_long_date( $hub_d ) );
	if ( $hub_day['date'] === $hub_wo['today'] ) {
		$hub_label = 'Today <small>' . $hub_label . '</small>';
	} elseif ( $hub_day['date'] === date_create_immutable( $hub_wo['today'] )->modify( '+1 day' )->format( 'Y-m-d' ) ) {
		$hub_label = 'Tomorrow <small>' . $hub_label . '</small>';
	}
	?>
  <section class="day-group" data-date="<?php echo esc_attr( $hub_day['date'] ); ?>" aria-labelledby="day-<?php echo esc_attr( $hub_day['date'] ); ?>">
    <h2 class="day-group__title" id="day-<?php echo esc_attr( $hub_day['date'] ); ?>"><?php echo $hub_label; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?> <small data-day-count><?php echo $hub_n . ' ' . ( 1 === $hub_n ? 'thing' : 'things' ); ?> on</small></h2>
    <div>
<?php
	foreach ( $hub_day['items'] as $hub_x ) {
		get_template_part( 'parts/events/row', null, array( 'item' => $hub_x ) );
	}
	?>
    </div>
  </section>
<?php endforeach; ?>

      <div class="split mt-6">
        <p class="muted small mb-0" data-events-range>Showing <?php echo esc_html( $hub_span ); ?>.</p>
        <a class="ho-btn ho-btn--outline" href="<?php echo $hub_u( 'events_month_link' ); ?>"><?php echo $hub_e( 'events_month_button' ); ?> <?php echo hub_icon( 'arrow' ); ?></a>
      </div>

<?php if ( ! $hub_t['events_classes_hide'] || ! $hub_t['events_nl_hide'] ) : ?>
      <div class="grid grid-2 mt-6">
<?php if ( ! $hub_t['events_classes_hide'] ) : ?>
        <div class="panel panel--soft">
          <h2 class="panel__title"><?php echo hub_icon( 'repeat' ); ?> <?php echo $hub_e( 'events_classes_title' ); ?></h2>
          <p class="small"><?php echo $hub_e( 'events_classes_text' ); ?></p>
          <a class="link-arrow" href="<?php echo $hub_u( 'events_classes_link' ); ?>"><?php echo $hub_e( 'events_classes_link_label' ); ?> <?php echo hub_icon( 'arrow' ); ?></a>
        </div>
<?php endif; ?>
<?php if ( ! $hub_t['events_nl_hide'] ) : ?>
        <div class="panel panel--soft">
          <h2 class="panel__title"><?php echo hub_icon( 'mail' ); ?> <?php echo $hub_e( 'events_nl_title' ); ?></h2>
          <p class="small"><?php echo $hub_e( 'events_nl_text' ); ?></p>
          <form class="inline-signup" action="https://harrowonline.org/wp-json/ho/v1/subscribe" method="post" data-nl-form data-nl-done="<?php echo esc_attr( $hub_t['events_nl_done'] ); ?>">
            <input type="hidden" name="signup_source" value="events-<?php echo esc_attr( $hub_t['events_nl_edition'] ); ?>">
            <input type="hidden" name="editions[]" value="<?php echo esc_attr( $hub_t['events_nl_edition'] ); ?>">
            <div class="inline-signup__row">
              <label class="ho-sr" for="wk-email">Email address</label>
              <input class="input" id="wk-email" type="email" name="email" placeholder="<?php echo esc_attr( $hub_t['events_nl_placeholder'] ); ?>" autocomplete="email" required>
              <button class="ho-btn ho-btn--primary ho-btn--sm" type="submit"><?php echo $hub_e( 'events_nl_button' ); ?></button>
            </div>
            <p class="help mt-2 mb-0" role="status" aria-live="polite"><?php echo $hub_e( 'events_nl_note' ); ?></p>
          </form>
        </div>
<?php endif; ?>
      </div>
<?php endif; ?>
    </div>
  </div>

<?php if ( ! $hub_t['events_cta_hide'] ) : ?>
  <div class="cta-band mt-7">
    <div>
      <h2><?php echo $hub_e( 'events_cta_title' ); ?></h2>
      <p><?php echo $hub_e( 'events_cta_text' ); ?></p>
    </div>
    <div class="cta-band__actions">
<?php echo hub_view_cta_buttons( $hub_t['events_cta_buttons'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper ?>
    </div>
  </div>
<?php endif; ?>
</div>
<?php get_footer();

<?php
/**
 * What's On in Harrow: featured events, then everything on each of the next 7 days.
 * URL: /events/   Filters (hub.js 6b): ?when=today|weekend|7days|date&date=, ?area=, ?cat[]=, ?age[]=, ?free=1, ?classes=0
 *
 * Data: hub_events_whats_on() (app/modules/events), hub_areas_list(). Markup: parts/events/row.php.
 * Edited by hand (tools/build_theme.py leaves it alone).
 */
defined( 'ABSPATH' ) || exit;

$hub_wo    = hub_events_whats_on();
$hub_areas = hub_areas_list();
$hub_w0    = date_create_immutable( $hub_wo['weekend']['from'] );
$hub_w1    = date_create_immutable( $hub_wo['weekend']['to'] );
$hub_from  = date_create_immutable( $hub_wo['range']['from'] );
$hub_span  = hub_events_rule_long_date( $hub_from ) . ' to ' . hub_events_rule_long_date( date_create_immutable( $hub_wo['range']['to'] ) );

hub_set_page( array(
	'body' => 'page-events',
	'description' => 'Events, things to do and classes across Harrow this week and weekend: family days out, live music, markets and community events.',
	'hubnav' => 'events',
	'nav' => 'events',
	'title' => 'What\'s On in Harrow: events this week and weekend',
) );
get_header();
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><span aria-current="page">What&rsquo;s On</span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">What&rsquo;s On in Harrow</h1>
  <p class="page-head__lead">Events, days out and regular classes across the borough, checked by the Harrow Online team. Running something? <a href="<?php echo esc_url( hub_url( 'register/' ) ); ?>">Add your event</a>. It&rsquo;s free for schools, clubs and charities, and included in the Featured and Local Partner plans for businesses.</p>
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

      <div class="chips chips--scroll mb-5" aria-label="Quick dates">
        <a class="chip" href="<?php echo esc_url( hub_url( 'events/?when=today' ) ); ?>" data-when="today">Today</a>
        <a class="chip" href="<?php echo esc_url( hub_url( 'events/?when=weekend' ) ); ?>" data-when="weekend">This weekend</a>
        <a class="chip" href="<?php echo esc_url( hub_url( 'events/?when=7days' ) ); ?>" data-when="7days">Next 7 days</a>
        <a class="chip" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>"><?php echo hub_icon( 'repeat', 'ho-icon ho-icon--sm' ); ?>Classes and activities</a>
        <a class="chip" href="<?php echo esc_url( hub_url( 'events/?cat=family' ) ); ?>">Family</a>
        <a class="chip" href="<?php echo esc_url( hub_url( 'events/?free=1' ) ); ?>">Free</a>
      </div>

      <div class="notice mb-5" data-events-empty hidden><?php echo hub_icon( 'calendar' ); ?><p>Nothing listed for that date yet. <a href="<?php echo esc_url( hub_url( 'events/month/' ) ); ?>">See the month calendar</a>.</p></div>

<?php if ( $hub_wo['featured'] ) : ?>
  <section class="day-group day-group--featured" data-featured-group aria-labelledby="day-featured">
    <h2 class="day-group__title" id="day-featured"><?php echo hub_icon( 'star' ); ?>Featured events <small data-day-count><?php echo count( $hub_wo['featured'] ); ?> coming up</small></h2>
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
        <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'events/month/' ) ); ?>">See the whole month <?php echo hub_icon( 'arrow' ); ?></a>
      </div>

      <div class="grid grid-2 mt-6">
        <div class="panel panel--soft">
          <h2 class="panel__title"><?php echo hub_icon( 'repeat' ); ?> Looking for a regular class?</h2>
          <p class="small">Fitness, dance, kids&rsquo; clubs and workshops that run every week, all in one place.</p>
          <a class="link-arrow" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>">Browse classes and activities <?php echo hub_icon( 'arrow' ); ?></a>
        </div>
        <div class="panel panel--soft">
          <h2 class="panel__title"><?php echo hub_icon( 'mail' ); ?> The Weekend, every Thursday</h2>
          <p class="small">Our Thursday newsletter picks the best things to do in Harrow that weekend.</p>
          <form class="inline-signup" action="https://harrowonline.org/wp-json/ho/v1/subscribe" method="post" data-nl-form data-nl-done="Thanks! Check your inbox to confirm. The Weekend arrives every Thursday.">
            <input type="hidden" name="signup_source" value="events-weekend">
            <input type="hidden" name="editions[]" value="weekend">
            <div class="inline-signup__row">
              <label class="ho-sr" for="wk-email">Email address</label>
              <input class="input" id="wk-email" type="email" name="email" placeholder="Your email" autocomplete="email" required>
              <button class="ho-btn ho-btn--primary ho-btn--sm" type="submit">Get The Weekend</button>
            </div>
            <p class="help mt-2 mb-0" role="status" aria-live="polite">Free. The Weekend only: it won&rsquo;t sign you up to Daily News or Harrow Business.</p>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="cta-band mt-7">
    <div>
      <h2>Running an event in Harrow?</h2>
      <p>Businesses add events and classes on the Featured or Local Partner plan, or list a single event for &pound;15 with no plan. Schools, clubs, charities and community groups add them free with an organiser account. We check every listing before it goes live, usually within one working day.</p>
    </div>
    <div class="cta-band__actions">
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'register/' ) ); ?>"><?php echo hub_icon( 'plus' ); ?>Add your event</a>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Business plans</a>
    </div>
  </div>
</div>
<?php get_footer();

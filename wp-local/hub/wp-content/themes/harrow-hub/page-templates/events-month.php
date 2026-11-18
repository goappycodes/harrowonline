<?php
/**
 * What's On month calendar: every event and weekly class, six weeks from the Monday before the 1st, plus the
 * month's one-off events as an agenda (shown on phones).
 * URL: /events/month/   ?month=2026-12 for another month; filters (hub.js): ?area=, ?cat=
 *
 * Data: hub_events_month() (app/modules/events), hub_areas_list(). Edited by hand (tools/build_theme.py leaves it alone).
 */
defined( 'ABSPATH' ) || exit;

$hub_m     = hub_events_month( isset( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification -- read-only
$hub_month = $hub_m['month'];
$hub_areas = hub_areas_list();
$hub_label = function ( $value ) {
	return date_create_immutable( $value . '-01' )->format( 'F Y' );
};
$hub_month_url = function ( $value ) {
	return add_query_arg( 'month', $value, hub_url( 'events/month/' ) );
};
/** One calendar entry: time and title; featured and weekly classes have their own colour. */
$hub_cal_ev = function ( array $x ) {
	$cls   = 'cal__ev' . ( $x['featured'] ? ' cal__ev--featured' : ( 'class' === $x['kind'] ? ' cal__ev--class' : '' ) );
	$start = explode( ' to ', $x['time'] )[0];
	return '<a class="' . $cls . '" href="' . esc_url( $x['url'] ) . '" title="' . esc_attr( $x['time'] . ': ' . $x['title'] ) . '"' . hub_view_event_filter_attrs( $x ) . '><span class="cal__ev-time">' . esc_html( $start ) . '</span> ' . esc_html( $x['title'] ) . '</a>';
};

hub_set_page( array(
	'body' => 'page-events',
	'description' => 'Month calendar of events and weekly classes in Harrow for ' . $hub_month['label'] . ': markets, live music, family days out and community events.',
	'hubnav' => 'events',
	'nav' => 'events',
	'robots' => 'noindex, follow',
	'title' => 'What\'s On in Harrow: ' . $hub_month['label'] . ' calendar',
) );
get_header();
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><a href="<?php echo esc_url( hub_url( 'events/' ) ); ?>">What&rsquo;s On</a></li><li><span aria-current="page"><?php echo esc_html( $hub_month['label'] ); ?></span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">What&rsquo;s On in <?php echo esc_html( $hub_month['name'] ); ?></h1>
  <p class="page-head__lead">Every event and weekly class in Harrow this month. Pick a day to see everything that&rsquo;s on.</p>
</header>

<div class="ho-container">
  <div class="fline month-line">
    <form class="fline__form" action="<?php echo esc_url( hub_url( 'events/month/' ) ); ?>" method="get" aria-label="Filter the calendar" data-autofilter data-month-filters>
      <label class="fsel"><span class="ho-sr">Area</span><select id="m-area" name="area"><option value="">All of Harrow</option><?php
		foreach ( $hub_areas['items'] as $hub_a ) {
			echo '<option value="' . esc_attr( $hub_a['slug'] ) . '">' . esc_html( $hub_a['name'] ) . '</option>';
		}
		?></select></label>
      <label class="fsel"><span class="ho-sr">Category</span><select id="m-cat" name="cat">
        <option value="">All categories</option>
<?php foreach ( $hub_m['categories'] as $hub_c ) : ?>
        <option value="<?php echo esc_attr( $hub_c['slug'] ); ?>"><?php echo esc_html( $hub_c['name'] ); ?></option>
<?php endforeach; ?>
      </select></label>
      <button class="ho-btn ho-btn--dark ho-btn--sm fpanel__go" type="submit">Apply</button>
      <a class="fpanel__clear" href="<?php echo esc_url( hub_url( 'events/month/' ) ); ?>" data-filter-clear hidden>Clear</a>
    </form>
    <nav class="seg" aria-label="View">
      <a href="<?php echo esc_url( hub_url( 'events/' ) ); ?>"><?php echo hub_icon( 'list' ); ?>List</a>
      <a href="<?php echo esc_url( hub_url( 'events/month/' ) ); ?>" aria-current="page"><?php echo hub_icon( 'calendar' ); ?>Month</a>
    </nav>
  </div>

  <div class="cal" data-month-cal>
    <div class="cal__head">
      <h2><?php echo esc_html( $hub_month['label'] ); ?></h2>
      <div class="cal__nav">
        <a href="<?php echo esc_url( $hub_month_url( $hub_month['prev'] ) ); ?>" aria-label="Previous month: <?php echo esc_attr( $hub_label( $hub_month['prev'] ) ); ?>"><?php echo hub_icon( 'chevron-left' ); ?></a>
        <a href="<?php echo esc_url( $hub_month_url( $hub_month['next'] ) ); ?>" aria-label="Next month: <?php echo esc_attr( $hub_label( $hub_month['next'] ) ); ?>"><?php echo hub_icon( 'chevron' ); ?></a>
      </div>
    </div>
    <div class="cal__grid cal__grid--month">
      <?php
		foreach ( HUB_EVENTS_DAY_NAMES as $hub_dn ) {
			echo '<div class="cal__dow"><abbr title="' . esc_attr( $hub_dn ) . '">' . esc_html( substr( $hub_dn, 0, 3 ) ) . '</abbr></div>';
		}
		echo "\n";
		foreach ( $hub_m['cells'] as $hub_cell ) {
			$hub_cls   = 'cal__day' . ( $hub_cell['in_month'] ? '' : ' is-out' ) . ( $hub_cell['is_today'] ? ' is-today' : '' );
			$hub_inner = implode( '', array_map( $hub_cal_ev, $hub_cell['items'] ) );
			if ( $hub_cell['more'] ) {
				$hub_inner .= '<a class="cal__more" href="' . esc_url( add_query_arg( array( 'when' => 'date', 'date' => $hub_cell['date'] ), hub_url( 'events/' ) ) ) . '">+' . (int) $hub_cell['more'] . ' more</a>';
			}
			echo '      <div class="' . $hub_cls . '"><time class="cal__num" datetime="' . esc_attr( $hub_cell['date'] ) . '"' . ( $hub_cell['is_today'] ? ' aria-current="date"' : '' ) . '>'
				. (int) substr( $hub_cell['date'], 8, 2 ) . '</time>' . $hub_inner . "</div>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above
		}
		?>
    </div>
    <div class="cal__agenda">
      <p class="small muted mt-4">One-off events only. Weekly classes run every week: <a href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>">see all classes</a>.</p>
<?php
foreach ( $hub_m['agenda'] as $hub_day ) :
	$hub_long = esc_html( hub_events_rule_long_date( date_create_immutable( $hub_day['date'] ) ) );
	?>
      <section class="day-group<?php echo $hub_day['past'] ? ' is-past' : ''; ?>">
        <h3 class="day-group__title"><?php echo $hub_day['is_today'] ? 'Today <small>' . $hub_long . '</small>' : $hub_long; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $hub_day['past'] ? ' <small>Ended</small>' : ''; ?></h3>
        <?php echo implode( '', array_map( $hub_cal_ev, $hub_day['items'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

      </section>
<?php endforeach; ?>
      <p class="mt-5 mb-5"><a class="link-arrow" href="<?php echo esc_url( $hub_month_url( $hub_month['next'] ) ); ?>"><?php echo esc_html( $hub_label( $hub_month['next'] ) ); ?> <?php echo hub_icon( 'arrow' ); ?></a></p>
    </div>
  </div>

  <div class="split mt-2">
    <div class="cal-legend" aria-label="Key">
      <span><i class="f"></i>Featured</span>
      <span><i></i>Event</span>
      <span><i class="c"></i>Weekly class</span>
    </div>
    <a class="link-quiet" href="<?php echo esc_url( hub_url( 'events/' ) ); ?>">Prefer a list? See this week&rsquo;s events</a>
  </div>

  <div class="cta-band mt-7">
    <div>
      <h2>Running an event in Harrow?</h2>
      <p>Businesses add events and classes on the Featured or Local Partner plan, or list a single event for &pound;15 with no plan. Schools, clubs, charities and community groups add them free with an organiser account. Every listing is checked before it goes live.</p>
    </div>
    <div class="cta-band__actions">
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'register/' ) ); ?>"><?php echo hub_icon( 'plus' ); ?>Add your event</a>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>">Browse classes</a>
    </div>
  </div>
</div>
<?php get_footer();

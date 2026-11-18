<?php
/**
 * A claimed business's page: cover, head card, photos, about, offers, events, contact, news, related, hours.
 *
 * $args: business (profile shape), offers (offers shape), events (event rows), related (businesses with counts)
 */
defined( 'ABSPATH' ) || exit;

$b      = $args['business'];
$top    = $b['featured'];
$paid   = $b['paid'];
$sample = $b['sample'];
$ic     = $b['icon'];
$name   = $b['name'];
$maps   = $b['address']['has_premises'] ? hub_view_maps_url( $name . ' ' . $b['address']['postcode'] ) : '';
$tel    = $b['phones'] ? $b['phones'][0]['tel'] : '';
$phone  = $b['phones'] ? $b['phones'][0]['display'] : '';

// Cover (top plans)
$cover = '';
if ( $top && $b['cover'] ) {
	$bg    = $b['cover']['background'];
	$cover = '<div class="ho-container profile-cover"><div class="ph ph--banner ph--photo' . ( $bg ? ' ph--contain' : '' ) . '"' . ( $bg ? ' style="background:' . esc_attr( $bg ) . '"' : '' ) . '>'
		. '<img src="' . esc_url( $b['cover']['url'] ) . '" alt="' . esc_attr( $b['cover']['alt'] ) . '" width="1600" height="600" fetchpriority="high"></div></div>';
} elseif ( $top && ( $sample || $b['images'] ) ) {
	$cover = '<div class="ho-container profile-cover"><div class="' . esc_attr( hub_view_ph_cls( $b, 'ph ph--banner ph--dark' ) ) . '">' . hub_view_photo( $b, 0, $ic, $name ) . '</div></div>';
}

// Photos: the business's own on paid plans; samples show placeholder tiles
$n = $paid ? $b['photo_count'] : 1;
if ( ! $sample && $b['images'] && $paid ) {
	$tiles = '';
	foreach ( array_slice( array_keys( $b['images'] ), 0, 5 ) as $k ) {
		$tiles .= '<div class="' . esc_attr( hub_view_ph_cls( $b ) ) . '">' . hub_view_photo( $b, $k ) . '</div>';
	}
	$gallery = '<div class="gallery gallery--' . min( 5, count( $b['images'] ) ) . ' mb-6" aria-label="Photos">' . $tiles . '</div>';
} elseif ( $n >= 3 ) {
	$tiles = array();
	for ( $k = 0; $k < min( $n, 5 ); $k++ ) {
		$tiles[] = '<div class="' . esc_attr( hub_view_ph_cls( $b ) ) . '">' . hub_view_photo( $b, $k + 1, $ic ) . '</div>';
	}
	if ( $n > 5 ) {
		$tiles[ count( $tiles ) - 1 ] = '<div class="gallery__more"><div class="' . esc_attr( hub_view_ph_cls( $b ) ) . '">' . hub_view_photo( $b, 2, $ic ) . '</div><span>+' . ( $n - 4 ) . ' photos</span></div>';
	}
	$gallery = '<div class="gallery mb-6" aria-label="Photos">' . implode( '', $tiles ) . '</div>';
} elseif ( ! $sample ) {
	$gallery = '';
} else {
	$gallery = '<div class="ph ph--wide mb-6">' . hub_icon( $ic ) . '</div>';
}

// Sections of the main column
$sections = array( '<section class="profile-section"><h2>About ' . esc_html( $name ) . '</h2><div class="profile-desc">' . implode( '', array_map( function ( $p ) { return '<p>' . $p . '</p>'; }, $b['description'] ) ) . '</div></section>' );

if ( $args['offers'] ) {
	$cards      = implode( '', array_map( function ( $o ) { return hub_part( 'offers/card-full', array( 'offer' => $o, 'show_business' => false ) ); }, $args['offers'] ) );
	$sections[] = '<section class="profile-section"><div class="sec-head"><h2 class="mb-0">Local Offers</h2><a class="link-arrow" href="' . esc_url( hub_url( 'offers/' ) ) . '">All offers ' . hub_icon( 'arrow' ) . '</a></div><div class="stack">' . $cards . '</div></section>';
}
if ( $args['events'] ) {
	$rows       = implode( '', array_map( function ( $ev ) { return hub_part( 'businesses/event-row', array( 'event' => $ev ) ); }, $args['events'] ) );
	$sections[] = '<section class="profile-section"><div class="sec-head"><h2 class="mb-0">Upcoming events and classes</h2><a class="link-arrow" href="' . esc_url( hub_url( 'events/' ) ) . '">What&rsquo;s On ' . hub_icon( 'arrow' ) . '</a></div><div>' . $rows . '</div></section>';
}

if ( $b['address']['has_premises'] ) {
	$info = '<li>' . hub_icon( 'pin' ) . '<div><address>' . esc_html( hub_view_address( $b, true ) ) . '</address><a class="small" href="' . esc_url( $maps ) . '" target="_blank" rel="noopener">Get directions</a></div></li>';
} else {
	$info = '<li>' . hub_icon( 'pin' ) . '<div><span>' . esc_html( hub_view_address( $b ) ) . '</span><p class="small muted mb-0">Works at customers&rsquo; homes and premises. No shop or office to visit.</p></div></li>';
}
foreach ( $b['phones'] as $ph ) {
	$info .= '<li>' . hub_icon( 'phone' ) . '<a href="tel:' . esc_attr( $ph['tel'] ) . '">' . esc_html( $ph['display'] ) . '</a></li>';
}
if ( $b['website'] ) {
	$info .= '<li>' . hub_icon( 'globe' ) . '<a href="' . esc_url( $b['website'] ) . '" target="_blank" rel="noopener">' . esc_html( hub_view_short_url( $b['website'] ) ) . '</a></li>';
}
if ( $b['email'] ) {
	$info .= '<li>' . hub_icon( 'mail' ) . '<a href="' . esc_url( 'mailto:' . $b['email'] ) . '">' . esc_html( $b['email'] ) . '</a></li>';
}
foreach ( $b['other_locations'] as $loc ) {
	$info .= '<li>' . hub_icon( 'pin' ) . '<div><span class="small muted">' . esc_html( $loc['label'] ) . '</span><address>' . esc_html( $loc['name'] . ', ' . $loc['address'] ) . '</address><a class="small" href="' . esc_url( hub_view_maps_url( $loc['name'] . ' ' . $loc['address'] ) ) . '" target="_blank" rel="noopener">Get directions</a></div></li>';
}
$mapbox     = $maps ? '<a class="ph ph--map" href="' . esc_url( $maps ) . '" target="_blank" rel="noopener" aria-label="Open ' . esc_attr( $name ) . ' in Google Maps">' . hub_icon( 'pin' ) . '</a>' : '';
$sections[] = '<section class="profile-section"><h2>Contact and location</h2>
      <div class="grid grid-2"><ul class="info-list">' . $info . '</ul>
      ' . $mapbox . '</div></section>';

if ( $b['news'] ) {
	$items = '';
	foreach ( $b['news'] as $nw ) {
		$items .= '<article class="news-link"><div class="ph">' . hub_icon( 'news' ) . '</div><div><span class="news-link__cat">' . esc_html( $nw['category'] ) . '</span>
          <h3 class="news-link__title"><a href="' . esc_url( $nw['url'] ) . '">' . esc_html( $nw['title'] ) . '</a></h3><p class="news-link__date">' . esc_html( $nw['date'] ) . '</p></div></article>';
	}
	$sections[] = '<section class="profile-section"><h2>In the news</h2>' . $items . '</section>';
}

if ( ! $top ) {
	$catn       = strtolower( $b['category']['name'] );
	$rel        = implode( '', array_map( function ( $r ) { return hub_part( 'businesses/row', array( 'business' => $r ) ); }, $args['related'] ) );
	$sections[] = '<section class="profile-section"><div class="sec-head"><h2 class="mb-0">More ' . esc_html( $catn ) . ' in ' . esc_html( $b['area']['name'] ) . '</h2>
          <a class="link-arrow" href="' . esc_url( $b['category']['slug'] ? hub_url( 'category/' . $b['category']['slug'] . '/' ) : hub_url() ) . '">All ' . esc_html( $catn ) . ' in Harrow ' . hub_icon( 'arrow' ) . '</a></div>' . ( '' !== $rel ? $rel : '<p class="muted">No other listings in this area yet.</p>' ) . '</section>';
}

// Hours (or a club's timetable)
if ( $b['schedule'] ) {
	$hours_title = $b['schedule_title'] ? $b['schedule_title'] : 'When we meet';
	$hours_note  = $b['schedule_note'];
	$hours       = '';
	foreach ( $b['schedule'] as $row ) {
		$hours .= '<tr><th scope="row">' . esc_html( $row['day'] ) . '</th><td>' . esc_html( $row['text'] ) . '</td></tr>';
	}
} else {
	$hours_title = 'Opening hours';
	$hours_note  = $b['hours_note'];
	$hours       = '';
	foreach ( HUB_DAYS as $i => $d ) {
		if ( ! isset( $b['hours'][ $i ] ) ) {
			break;
		}
		$hours .= '<tr' . ( $i === $b['today_index'] ? ' class="is-today"' : '' ) . '><th scope="row">' . $d . '</th><td>' . esc_html( $b['hours'][ $i ] ) . '</td></tr>';
	}
}
$hours_note = $hours_note ? '<p class="small muted mt-3 mb-0">' . esc_html( $hours_note ) . '</p>' : '';

$social = '';
if ( $paid && $b['social'] ) {
	$links = '';
	foreach ( $b['social'] as $s ) {
		$links .= $s['url'] ? '<a href="' . esc_url( $s['url'] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $s['label'] ) . '">' . hub_icon( $s['network'] ) . '</a>'
			: '<a href="#" aria-label="' . esc_attr( $s['label'] ) . '">' . hub_icon( $s['network'] ) . '</a>';
	}
	$social = '<div class="profile-card__follow"><h3 class="profile-card__h">Follow</h3><div class="social-row">' . $links . '</div></div>';
}
$trust = $sample ? '<p class="profile-card__trust">' . hub_icon( 'alert', 'ho-icon ho-icon--sm' ) . '<span><strong>Sample listing.</strong> Made up for the preview; removed before launch.</span></p>'
	: '<p class="profile-card__trust">' . hub_icon( $b['checked'] ? 'check' : 'verified', 'ho-icon ho-icon--sm' ) . '<span>' . esc_html( $b['verified_note'] ) . '</span></p>';

$badges = 'partner' === $b['plan'] ? '<span class="badge badge--partner">' . hub_icon( 'star' ) . 'Local Partner</span>'
	: ( 'premium' === $b['plan'] ? '<span class="badge badge--premium">' . hub_icon( 'star' ) . 'Featured</span>' : '' );
if ( $b['business_of_the_week'] ) {
	$badges = '<span class="botw">' . hub_icon( 'star' ) . 'Business of the Week</span>' . $badges;
}
$mono = $b['logo'] ? '<span class="mono mono--lg mono--logo"><img src="' . esc_url( $b['logo'] ) . '" alt="' . esc_attr( $name . ' badge' ) . '" width="480" height="480"></span>'
	: '<span class="mono mono--lg' . ( $top ? ' mono--red' : '' ) . '" aria-hidden="true">' . esc_html( hub_initials( $name ) ) . '</span>';
$cat_url  = hub_url( 'category/' . $b['category']['slug'] . '/' );
$area_url = hub_url( 'area/' . $b['area']['slug'] . '/' );
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol><li><a href="<?php echo esc_url( hub_url() ); ?>">Hub</a></li><li><a href="<?php echo esc_url( $cat_url ); ?>"><?php echo esc_html( $b['category']['name'] ); ?></a></li><li><a href="<?php echo esc_url( $area_url ); ?>"><?php echo esc_html( $b['area']['name'] ); ?></a></li><li><span aria-current="page"><?php echo esc_html( $name ); ?></span></li></ol></nav>
<?php echo $cover; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>

<section class="profile-head profile-head--card ho-container<?php echo $top ? ' profile-head--premium' : ''; ?>">
  <div class="profile-head__row">
    <?php echo $mono; // phpcs:ignore WordPress.Security.EscapeOutput ?>

    <div class="profile-head__main">
      <div class="cluster"><?php echo $badges; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
      <h1 class="profile-head__title"><?php echo hub_view_name_with_tick( $b, false ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
      <?php if ( $b['tagline'] ) : ?><p class="profile-head__summary"><?php echo esc_html( $b['tagline'] ); ?></p><?php endif; ?>

      <p class="profile-head__meta">
        <a href="<?php echo esc_url( $cat_url ); ?>"><?php echo hub_icon( 'tag' ) . esc_html( $b['category']['name'] ); ?></a>
        <span><?php echo esc_html( $b['subcategory'] ); ?></span>
        <a href="<?php echo esc_url( $area_url ); ?>"><?php echo hub_icon( 'pin' ) . esc_html( $b['area']['name'] ); ?></a>
        <?php echo hub_view_open_today( $b ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

      </p>
      <div class="profile-head__actions">
        <a class="ho-btn ho-btn--primary" href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo hub_icon( 'phone' ); ?>Call</a>
        <?php if ( $b['website'] ) : ?><a class="ho-btn ho-btn--outline" href="<?php echo esc_url( $b['website'] ); ?>" target="_blank" rel="noopener"><?php echo hub_icon( 'globe' ); ?>Website</a><?php endif; ?>

        <?php if ( $maps ) : ?><a class="ho-btn ho-btn--outline" href="<?php echo esc_url( $maps ); ?>" target="_blank" rel="noopener"><?php echo hub_icon( 'map' ); ?>Directions</a><?php endif; ?>

        <button class="ho-btn ho-btn--outline" type="button" data-copy><?php echo hub_icon( 'external' ); ?>Share</button>
      </div>
    </div>
  </div>
</section>

<div class="ho-container layout-aside profile-layout">
  <div class="profile-main">
    <?php echo $gallery; // phpcs:ignore WordPress.Security.EscapeOutput ?>

    <?php echo implode( '', $sections ); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped above ?>

    <p class="mt-4"><a class="report-link" href="<?php echo esc_url( hub_url( 'report/?listing=' . $b['slug'] ) ); ?>"><?php echo hub_icon( 'flag' ); ?>Report a mistake on this listing</a></p>
  </div>
  <aside class="sticky profile-aside">
    <div class="panel profile-card">
      <h2 class="panel__title"><?php echo esc_html( $hours_title ); ?></h2>
      <p class="mb-3"><?php echo hub_view_open_today( $b ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
      <table class="hours"><caption class="ho-sr"><?php echo esc_html( $hours_title ); ?></caption><tbody><?php echo $hours; // phpcs:ignore WordPress.Security.EscapeOutput ?></tbody></table><?php echo $hours_note; // phpcs:ignore WordPress.Security.EscapeOutput ?>

      <a class="ho-btn ho-btn--primary ho-btn--block mt-4" href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo hub_icon( 'phone' ); ?>Call <?php echo esc_html( $phone ); ?></a>
      <?php echo $social; // phpcs:ignore WordPress.Security.EscapeOutput ?>

      <?php echo $trust; // phpcs:ignore WordPress.Security.EscapeOutput ?>

    </div>
  </aside>
</div>

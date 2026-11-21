<?php
/**
 * Search: the index hub.js searches and lists from (the Businesses search page, category and area pages, the
 * "already on the Hub?" check and the report form). Short keys and no empty values keep it small: it holds every
 * business. Text is plain; "h" paths start after the Hub's home ("/events/x/"); images are full URLs.
 *
 * index:
 *   cats   { slug: { n: name, i: icon } }        areas { slug: name }
 *   items  business[] in directory order        today 'Y-m-d'     weekend [ 'Y-m-d', 'Y-m-d' ]
 *   events event[] (from today, by date)        classes class[]   offers offer[] (live)
 *
 * business: k slug, n name, c category, s subcategory, a area, ad street, pc postcode, p phone (tel), w has a
 *   website, t plan (claimed only), i icon (claimed only), ck Checked by Harrow Online, tg tagline, im logo or first
 *   photo, lg im is a logo, sm sample
 * event: t title, h path, d date, tm time, c category name, v venue, an area name, a area, p price, ag age, i icon,
 *   im photo, o organiser, f featured, sm sample
 * class: as an event without d, plus wd (weekday, 0 = Monday; always there), c its type, o its provider, lg im is a logo
 * offer: t title, h path, vl value ("20% OFF"), b business name, k business slug, a area, c category, e end label,
 *   sub subscribers only, f featured, sm sample
 */
defined( 'ABSPATH' ) || exit;

/** @param array $b A business (businesses module shape). */
function hub_search_shape_business( array $b ) {
	return hub_search_rule_slim( array(
		'k'  => $b['slug'],
		'n'  => $b['name'],
		'c'  => $b['category']['slug'],
		's'  => $b['subcategory'],
		'a'  => $b['area']['slug'],
		'ad' => $b['address']['street'],
		'pc' => $b['address']['postcode'],
		'p'  => $b['phones'] ? (string) $b['phones'][0]['tel'] : '',
		'w'  => $b['website'] ? 1 : 0,
		't'  => $b['claimed'] ? $b['plan'] : '',
		'i'  => $b['claimed'] ? $b['icon'] : '',
		'ck' => $b['checked'] ? 1 : 0,
		'tg' => $b['tagline'],
		'im' => $b['logo'] ? $b['logo'] : ( $b['images'] ? $b['images'][0]['url'] : '' ),
		'lg' => $b['logo'] ? 1 : 0,
		'sm' => $b['sample'] ? 1 : 0,
	) );
}

/** @param array $e An event (events module shape). @param string $home The Hub's home URL. */
function hub_search_shape_event( array $e, $home ) {
	return hub_search_rule_slim( array(
		't'  => $e['title'],
		'h'  => hub_search_rule_path( $e['url'], $home ),
		'd'  => $e['date'],
		'tm' => $e['time'],
		'c'  => $e['category']['name'],
		'v'  => $e['venue'],
		'an' => $e['area']['name'],
		'a'  => $e['area']['slug'],
		'p'  => $e['price'],
		'ag' => $e['age'],
		'i'  => $e['icon'],
		'im' => (string) $e['image'],
		'o'  => (string) $e['organiser'],
		'f'  => $e['featured'] ? 1 : 0,
		'sm' => $e['sample'] ? 1 : 0,
	) );
}

/** @param array $c A weekly class with a weekday and its own page (events module shape). */
function hub_search_shape_class( array $c, $home ) {
	return hub_search_rule_slim( array(
		't'  => $c['title'],
		'h'  => hub_search_rule_path( $c['url'], $home ),
		'tm' => $c['time'],
		'c'  => $c['type']['name'],
		'v'  => $c['venue'],
		'an' => $c['area']['name'],
		'a'  => $c['area']['slug'],
		'p'  => $c['price'],
		'ag' => $c['age'],
		'i'  => $c['icon'],
		'im' => $c['image'] ? $c['image']['url'] : '',
		'lg' => $c['image'] && 'logo' === $c['image']['fit'] ? 1 : 0,
		'o'  => $c['provider'],
		'f'  => $c['featured'] ? 1 : 0,
		'sm' => $c['sample'] ? 1 : 0,
		'wd' => (int) $c['weekday'],
	), array( 'wd' ) );
}

/** @param array $o A live offer (offers module shape). */
function hub_search_shape_offer( array $o, $home ) {
	return hub_search_rule_slim( array(
		't'   => $o['title'],
		'h'   => hub_search_rule_path( $o['url'], $home ),
		'vl'  => $o['value'],
		'b'   => $o['business']['name'],
		'k'   => $o['business']['slug'],
		'a'   => $o['business']['area']['slug'],
		'c'   => $o['business']['category']['slug'],
		'e'   => $o['ends']['label'],
		'sub' => $o['subscriber_only'] ? 1 : 0,
		'f'   => $o['featured'] ? 1 : 0,
		'sm'  => $o['business']['sample'] ? 1 : 0,
	) );
}

<?php
/**
 * Local Offers filters: category chips, then one line with the count and the area and subscriber-only filters
 * (they apply on change). The category select stays hidden so the chosen chip is kept when another filter changes.
 * Pages are filtered server-side; hub.js applies the same ?cat=, ?area= and ?sub=1 in the browser.
 *
 * $args: list (hub_offers_list() result), categories (hub_categories_list() items), areas (hub_areas_list() items)
 */
defined( 'ABSPATH' ) || exit;

$list    = $args['list'];
$f       = $list['filters'];
$offers  = hub_url( 'offers/' );
$cat_tag = '';
$where   = 'across Harrow';
foreach ( $args['categories'] as $c ) {
	if ( $c['slug'] === $f['cat'] ) {
		$cat_tag = strtolower( $c['name'] );
	}
}
foreach ( $args['areas'] as $a ) {
	if ( $a['slug'] === $f['area'] ) {
		$where = 'in ' . $a['name'];
	}
}
$words = trim( $cat_tag . ( $f['sub'] ? ' subscriber-only' : '' ) );
$count = ( '' !== $words ? $words . ' ' : '' ) . ( 1 === $list['total'] ? 'offer' : 'offers' ) . ' ' . $where;
?>
  <div class="chips chips--scroll" aria-label="Popular categories">
    <a class="chip" href="<?php echo esc_url( $offers ); ?>" data-cat=""<?php echo '' === $f['cat'] ? ' aria-current="page"' : ''; ?>>All offers <span class="count"><?php echo (int) $list['live_total']; ?></span></a>
<?php foreach ( $list['facets']['categories'] as $c ) : ?>
    <a class="chip" href="<?php echo esc_url( add_query_arg( 'cat', $c['slug'], $offers ) ); ?>" data-cat="<?php echo esc_attr( $c['slug'] ); ?>"<?php echo $c['slug'] === $f['cat'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c['name'] ); ?> <span class="count"><?php echo (int) $c['count']; ?></span></a>
<?php endforeach; ?>
  </div>
  <div class="fline">
    <p class="toolbar__count" data-offers-count><strong><?php echo (int) $list['total']; ?></strong> <?php echo esc_html( $count ); ?></p>
    <form class="fline__form" action="<?php echo esc_url( $offers ); ?>" method="get" aria-label="Filter offers" data-autofilter>
      <select id="o-cat" name="cat" hidden aria-label="Category" tabindex="-1"><option value="">All categories</option><?php
		foreach ( $args['categories'] as $c ) {
			echo '<option value="' . esc_attr( $c['slug'] ) . '"' . selected( $c['slug'], $f['cat'], false ) . '>' . esc_html( $c['name'] ) . '</option>';
		}
		?></select>
      <label class="fsel"><span class="ho-sr">Area</span><select id="o-area" name="area"><option value="">All of Harrow</option><?php
		foreach ( $args['areas'] as $a ) {
			echo '<option value="' . esc_attr( $a['slug'] ) . '"' . selected( $a['slug'], $f['area'], false ) . '>' . esc_html( $a['name'] ) . '</option>';
		}
		?></select></label>
      <label class="fcheck" for="o-sub"><input id="o-sub" type="checkbox" name="sub" value="1"<?php checked( $f['sub'] ); ?>> Subscriber-only</label>
      <button class="ho-btn ho-btn--dark ho-btn--sm fpanel__go" type="submit">Show offers</button>
      <a class="fpanel__clear" href="<?php echo esc_url( $offers ); ?>" data-filter-clear hidden>Clear</a>
    </form>
  </div>

  <div class="notice mb-5" data-offers-empty<?php echo $list['total'] ? ' hidden' : ''; ?>><?php echo hub_icon( 'tag' ); ?><p>No offers match that yet. <a href="<?php echo esc_url( $offers ); ?>">See all offers</a>.</p></div>

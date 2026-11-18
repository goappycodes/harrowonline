<?php
/**
 * Category or area page body: heading, search and filters on one line, the first page of listings, and the
 * "list your business" band. hub.js pages and filters the rest from the search index.
 *
 * $args: kind ('cat' | 'area'), term (category or area shape), title, lead,
 *        list (hub_businesses_list() result), options (the other kind's list: areas on a category page, categories
 *        on an area page)
 */
defined( 'ABSPATH' ) || exit;

$is_cat = 'cat' === $args['kind'];
$term   = $args['term'];
$list   = $args['list'];
$crumbs = '<li><a href="' . esc_url( hub_url() ) . '">Hub</a></li>'
	. ( $is_cat ? '' : '<li><a href="' . esc_url( hub_url() . '#areas' ) . '">Areas</a></li>' )
	. '<li><span aria-current="page">' . esc_html( $term['name'] ) . '</span></li>';
$opts = '';
foreach ( $args['options'] as $o ) {
	$opts .= '<option value="' . esc_attr( $o['slug'] ) . '">' . esc_html( $o['name'] ) . '</option>';
}
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol><?php echo $crumbs; // phpcs:ignore WordPress.Security.EscapeOutput ?></ol></nav>
<header class="page-head ho-container">
  <h1 class="page-head__title"><?php echo esc_html( $args['title'] ); ?></h1>
  <p class="page-head__lead"><?php echo esc_html( $args['lead'] ); ?></p>
</header>

<div class="ho-container">
  <form class="dline" id="filters" data-directory-form action="" method="get" role="search" aria-label="Search and filter">
    <input type="hidden" name="<?php echo $is_cat ? 'cat' : 'area'; ?>" value="<?php echo esc_attr( $term['slug'] ); ?>">
    <label class="fsearch"><?php echo hub_icon( 'search' ); ?><span class="ho-sr">Search <?php echo esc_html( $args['title'] ); ?></span><input id="f-q" type="search" name="q" placeholder="Search by name or service" autocomplete="off"></label>
    <?php if ( $is_cat ) : ?>
<label class="fsel"><span class="ho-sr">Area</span><select id="f-area" name="area"><option value="">All areas</option><?php echo $opts; // phpcs:ignore WordPress.Security.EscapeOutput ?></select></label>
    <?php else : ?>
<label class="fsel"><span class="ho-sr">Category</span><select id="f-cat" name="cat"><option value="">All categories</option><?php echo $opts; // phpcs:ignore WordPress.Security.EscapeOutput ?></select></label>
    <?php endif; ?>
    <label class="fcheck"><input type="checkbox" name="web" value="1"> Has a website</label>
    <label class="fcheck"><input type="checkbox" name="phone" value="1"> Has a phone</label>
  </form>

  <div data-directory <?php echo $is_cat ? 'data-cat' : 'data-area'; ?>="<?php echo esc_attr( $term['slug'] ); ?>" data-page-size="<?php echo (int) HUB_BUSINESSES_PAGE_SIZE; ?>">
    <h2 class="ho-sr">Listings</h2>
    <div class="toolbar dtoolbar">
      <div class="dtoolbar__lead"><p class="toolbar__count" data-count><strong><?php echo esc_html( number_format( $list['total'] ) ); ?></strong> businesses</p><a class="dtoolbar__key" href="<?php echo esc_url( hub_url( 'pricing/' ) . '#fb-labels' ); ?>"><svg class="ho-icon" aria-hidden="true"><use href="#i-info"/></svg>What the icons mean</a></div>
      <label class="fsel fsel--sort"><span class="dtoolbar__label">Sort</span><select id="f-sort" name="sort" form="filters"><option value="relevance">Best match</option><option value="name">Name (A to Z)</option></select></label>
    </div>
    <div class="active-filters" data-active hidden></div>
    <div data-results><?php
		foreach ( $list['items'] as $b ) {
			get_template_part( 'parts/businesses/row', null, array( 'business' => $b ) );
		}
		?></div>
    <nav class="pager" data-pager aria-label="Pages"></nav>
  </div>

  <div class="cta-band mt-6">
    <div><h2>Run a business in Harrow?</h2><p>Claim your free listing or add a missing business. It takes two minutes.</p></div>
    <div class="cta-band__actions"><a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'add-business/' ) ); ?>">List your business</a><a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See plans</a></div>
  </div>
</div>

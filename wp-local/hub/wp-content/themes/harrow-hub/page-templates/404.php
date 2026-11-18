<?php
/**
 * Page not found (converted from src/pages/404.html).
 * URL: /404.html
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-404',
	'description' => 'Sorry, we couldn\'t find that page on Harrow Online Hub. Search for a local business or browse popular categories.',
	'nav' => 'hub',
	'robots' => 'noindex',
	'title' => 'Page not found',
) );
get_header();
?>
<!-- 404 (redesign, Nov 2026): a short message, the site search bar, quick routes to the main sections as cards,
     then Popular categories as on the homepage -->
<section class="nf" aria-labelledby="nf-title">
  <div class="ho-container nf__inner">
    <p class="nf__code">Error 404</p>
    <h1 class="nf__title" id="nf-title">Sorry, we can&rsquo;t find that page</h1>
    <p class="nf__lead">It may have moved, or the event or offer may have ended. Try a search, or pick where to go next.</p>

    <form class="searchbar nf__search" action="<?php echo esc_url( hub_url( 'search/' ) ); ?>" method="get" role="search">
      <div class="searchbar__field">
        <svg class="ho-icon"><use href="#i-search"/></svg>
        <label class="ho-sr" for="nf-q">What are you looking for?</label>
        <input id="nf-q" type="search" name="q" placeholder="Plumber, Indian restaurant&hellip;" autocomplete="off">
      </div>
      <div class="searchbar__field">
        <svg class="ho-icon"><use href="#i-pin"/></svg>
        <label class="ho-sr" for="nf-area">Area</label>
        <select id="nf-area" name="area"><option value="">All of Harrow</option><option value="belmont">Belmont</option><option value="canons-park">Canons Park</option><option value="edgware">Edgware</option><option value="greenhill">Greenhill</option><option value="harrow-town-centre">Harrow Town Centre</option><option value="harrow-weald">Harrow Weald</option><option value="harrow-on-the-hill">Harrow on the Hill</option><option value="hatch-end">Hatch End</option><option value="headstone">Headstone</option><option value="kenton">Kenton</option><option value="north-harrow">North Harrow</option><option value="pinner">Pinner</option><option value="queensbury">Queensbury</option><option value="rayners-lane">Rayners Lane</option><option value="south-harrow">South Harrow</option><option value="stanmore">Stanmore</option><option value="sudbury-hill">Sudbury Hill and Roxeth</option><option value="wealdstone">Wealdstone</option><option value="west-harrow">West Harrow</option></select>
      </div>
      <button class="ho-btn ho-btn--primary" type="submit">Search</button>
    </form>

    <nav class="nf-go" aria-label="Where to go next">
      <a class="nf-go__item" href="<?php echo esc_url( hub_url( '' ) ); ?>"><span class="nf-go__icon"><svg class="ho-icon"><use href="#i-home"/></svg></span><span class="nf-go__text"><strong>Hub homepage</strong><span>Start again</span></span></a>
      <a class="nf-go__item" href="<?php echo esc_url( hub_url( 'events/' ) ); ?>"><span class="nf-go__icon"><svg class="ho-icon"><use href="#i-calendar"/></svg></span><span class="nf-go__text"><strong>What&rsquo;s On</strong><span>Events and days out</span></span></a>
      <a class="nf-go__item" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>"><span class="nf-go__icon"><svg class="ho-icon"><use href="#i-repeat"/></svg></span><span class="nf-go__text"><strong>Classes &amp; clubs</strong><span>Regular activities</span></span></a>
      <a class="nf-go__item" href="<?php echo esc_url( hub_url( 'offers/' ) ); ?>"><span class="nf-go__icon"><svg class="ho-icon"><use href="#i-tag"/></svg></span><span class="nf-go__text"><strong>Local offers</strong><span>Deals from Harrow businesses</span></span></a>
      <a class="nf-go__item" href="https://harrowonline.org/"><span class="nf-go__icon"><svg class="ho-icon"><use href="#i-news"/></svg></span><span class="nf-go__text"><strong>Harrow news</strong><span>Latest stories</span></span></a>
    </nav>
  </div>
</section>

<section class="section section--soft" aria-labelledby="nf-cats">
  <div class="ho-container">
    <div class="sec-head">
      <div><h2 class="sec-title" id="nf-cats">Popular categories</h2><p class="sec-sub">The categories with the most Harrow businesses.</p></div>
      <a class="link-arrow" href="<?php echo esc_url( hub_url( 'categories/' ) ); ?>">View all categories <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="cat-cards"><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/shops/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-gift"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Shops &amp; Specialists</span><span class="cat-card__count">372 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-gift"/></svg></span></a><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/restaurants/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-food"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Restaurants</span><span class="cat-card__count">275 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-food"/></svg></span></a><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/doctors-dentists-and-clinics/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-medical"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Doctors, Dentists &amp; Clinics</span><span class="cat-card__count">253 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-medical"/></svg></span></a><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/hair-and-beauty/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-scissors"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Hair &amp; Beauty</span><span class="cat-card__count">232 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-scissors"/></svg></span></a><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/motoring/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-car"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Motoring</span><span class="cat-card__count">149 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-car"/></svg></span></a><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/finance-and-accountants/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-pound"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Finance &amp; Accountants</span><span class="cat-card__count">128 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-pound"/></svg></span></a></div>
  </div>
</section>
<?php get_footer();

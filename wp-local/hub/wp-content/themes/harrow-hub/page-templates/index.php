<?php
/**
 * Harrow Online Hub: local businesses, offers and what's on in Harrow (converted from src/pages/index.html).
 * URL: /
 *
 * Data from app/modules: events ("This weekend in Harrow"), offers (this weekend's offer, More Local Offers, "Offers
 * live"), businesses ("Just joined", "Featured local businesses", "Local businesses") and areas. The other sections
 * are still the prototype's markup until their modules are built.
 * Edited by hand (tools/build_theme.py leaves it alone).
 */
defined( 'ABSPATH' ) || exit;

$hub_weekend  = hub_events_weekend( 2 );
$hub_week     = hub_events_whats_on();                       // its range: today and the next 6 days
$hub_classes  = hub_classes_list();
$hub_offers   = hub_offers_list( array( 'limit' => 7 ) );   // [0] this weekend's offer, [1-6] More Local Offers
$hub_featured = hub_businesses_featured( 4 );
$hub_newest   = hub_businesses_newest( 10 );
$hub_biz      = hub_businesses_list( array( 'limit' => 1 ) );  // for the total
$hub_areas    = hub_areas_list();
$hub_w0       = date_create_immutable( $hub_weekend['from'] );
$hub_w1       = date_create_immutable( $hub_weekend['to'] );
$hub_wknd_label = 'Friday ' . $hub_w0->format( $hub_w0->format( 'm' ) === $hub_w1->format( 'm' ) ? 'j' : 'j F' ) . ' to Sunday ' . $hub_w1->format( 'j F' );
$hub_week_events = hub_events_list( array( 'to' => $hub_week['range']['to'] ) )['total'];   // one-off events, today to 6 days on

hub_set_page( array(
	'body' => 'page-home',
	'description' => 'Discover what\'s happening in Harrow. Find local businesses, events, classes and exclusive offers from across the borough, from Harrow Online.',
	'nav' => 'hub',
	'title' => 'Harrow Online Hub: local businesses, offers and what\'s on in Harrow',
) );
get_header();
?>
<!-- Section order (client's ChatGPT mock-up, Nov 2026): the first screen is a simple choice. One search, four big
     buttons (businesses, What's On, classes, offers). Then this
     weekend: two events, one offer and the newsletter. Then more offers, the numbers, featured businesses, categories and areas. -->
<section class="hero hero--choose">
  <div class="ho-container hero__grid">
   <div class="hero__main">
    <p class="eyebrow" style="color:#fff;opacity:.8">Harrow Online Hub</p>
    <h1 class="hero__title">Discover what&rsquo;s happening <em>in&nbsp;Harrow</em></h1>
    <p class="hero__lead">Find local businesses, events, classes and exclusive offers from across the borough.</p>

    <form class="searchbar" action="<?php echo esc_url( hub_url( 'search/' ) ); ?>" method="get" role="search">
      <div class="searchbar__field">
        <svg class="ho-icon"><use href="#i-search"/></svg>
        <label class="ho-sr" for="hero-q">What are you looking for?</label>
        <input id="hero-q" type="search" name="q" placeholder="Plumber, Indian restaurant&hellip;" autocomplete="off">
      </div>
      <div class="searchbar__field">
        <svg class="ho-icon"><use href="#i-pin"/></svg>
        <label class="ho-sr" for="hero-area">Area</label>
        <select id="hero-area" name="area"><option value="">All of Harrow</option><option value="belmont">Belmont</option><option value="canons-park">Canons Park</option><option value="edgware">Edgware</option><option value="greenhill">Greenhill</option><option value="harrow-town-centre">Harrow Town Centre</option><option value="harrow-weald">Harrow Weald</option><option value="harrow-on-the-hill">Harrow on the Hill</option><option value="hatch-end">Hatch End</option><option value="headstone">Headstone</option><option value="kenton">Kenton</option><option value="north-harrow">North Harrow</option><option value="pinner">Pinner</option><option value="queensbury">Queensbury</option><option value="rayners-lane">Rayners Lane</option><option value="south-harrow">South Harrow</option><option value="stanmore">Stanmore</option><option value="sudbury-hill">Sudbury Hill and Roxeth</option><option value="wealdstone">Wealdstone</option><option value="west-harrow">West Harrow</option></select>
      </div>
      <button class="ho-btn ho-btn--primary" type="submit">Search</button>
    </form>

    <div class="popular popular--one">
      <span>Popular:</span>
      <a href="<?php echo esc_url( hub_url( 'category/restaurants/' ) ); ?>">Restaurants</a>
      <a href="<?php echo esc_url( hub_url( 'category/hair-and-beauty/' ) ); ?>">Hair &amp; Beauty</a>
      <a href="<?php echo esc_url( hub_url( 'category/home-and-garden/' ) ); ?>">Home &amp; Garden</a>
      <a href="<?php echo esc_url( hub_url( 'category/doctors-dentists-and-clinics/' ) ); ?>">Dentists</a>
    </div>

   </div>

    <div class="hero__side">
      <nav class="choose" aria-label="Browse the Hub">
        <a class="choose__item" href="<?php echo esc_url( hub_url( 'search/' ) ); ?>"><span class="choose__icon"><svg class="ho-icon"><use href="#i-store"/></svg></span><span class="choose__text"><strong>Find a business</strong><span>Shops, trades and services</span></span><svg class="ho-icon choose__go"><use href="#i-arrow"/></svg></a>
        <a class="choose__item" href="<?php echo esc_url( hub_url( 'events/' ) ); ?>"><span class="choose__icon"><svg class="ho-icon"><use href="#i-calendar"/></svg></span><span class="choose__text"><strong>What&rsquo;s On</strong><span>Events and days out</span></span><svg class="ho-icon choose__go"><use href="#i-arrow"/></svg></a>
        <a class="choose__item" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>"><span class="choose__icon"><svg class="ho-icon"><use href="#i-repeat"/></svg></span><span class="choose__text"><strong>Classes &amp; clubs</strong><span>Regular activities for all ages</span></span><svg class="ho-icon choose__go"><use href="#i-arrow"/></svg></a>
        <a class="choose__item" href="<?php echo esc_url( hub_url( 'offers/' ) ); ?>"><span class="choose__icon"><svg class="ho-icon"><use href="#i-tag"/></svg></span><span class="choose__text"><strong>Local offers</strong><span>Deals from Harrow businesses</span></span><svg class="ho-icon choose__go"><use href="#i-arrow"/></svg></a>
      </nav>
      <!-- Client request (18 Nov 2026): quick routes for organisers and businesses under the four choices -->
      <div class="choose-add">
        <a class="ho-btn ho-btn--on-dark" href="<?php echo esc_url( hub_url( 'register/' ) ); ?>"><svg class="ho-icon"><use href="#i-plus"/></svg>Add your event</a>
        <a class="ho-btn ho-btn--on-dark" href="<?php echo esc_url( hub_url( 'add-business/' ) ); ?>"><svg class="ho-icon"><use href="#i-plus"/></svg>Add your business</a>
      </div>
    </div>
  </div>
</section>

<?php get_template_part( 'parts/businesses/ticker', null, array( 'businesses' => $hub_newest['items'] ) ); ?>

<section class="section" aria-labelledby="wknd-title">
  <div class="ho-container">
    <div class="sec-head">
      <div><h2 class="sec-title" id="wknd-title">This weekend in Harrow</h2><p class="sec-sub"><?php echo esc_html( $hub_wknd_label ); ?></p></div>
      <a class="link-arrow" href="<?php echo esc_url( hub_url( 'events/?when=weekend' ) ); ?>">Everything this weekend <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="home-now">
      <?php
		foreach ( $hub_weekend['items'] as $hub_e ) {
			get_template_part( 'parts/events/card', null, array( 'event' => $hub_e ) );
		}
		?>

      <div class="home-now__offer">
        <?php
		if ( $hub_offers['items'] ) {
			get_template_part( 'parts/offers/card', null, array( 'offer' => $hub_offers['items'][0] ) );
		}
		?>
      </div>
      <div class="home-nl">
        <span class="home-nl__icon"><svg class="ho-icon"><use href="#i-mail"/></svg></span>
        <h3 class="home-nl__title">Get Harrow&rsquo;s best events and local offers every Thursday</h3>
        <p class="home-nl__text">Free. Pick the newsletters you want; each one is separate.</p>
        <form class="home-nl__form" action="https://harrowonline.org/wp-json/ho/v1/subscribe" method="post" data-nl-form>
          <input type="hidden" name="signup_source" value="hub-home">
          <fieldset class="home-nl__editions">
            <legend class="ho-sr">Choose your newsletters</legend>
            <label><input type="checkbox" name="editions[]" value="daily"> Daily News <small>(Daily)</small></label>
            <label><input type="checkbox" name="editions[]" value="weekend" checked> The Weekend <small>(Thu)</small></label>
            <label><input type="checkbox" name="editions[]" value="business"> Harrow Business <small>(Mon)</small></label>
          </fieldset>
          <label class="ho-sr" for="home-nl-email">Email address</label>
          <input class="input" id="home-nl-email" type="email" name="email" placeholder="Your email address" autocomplete="email" required>
          <button class="ho-btn ho-btn--primary ho-btn--block" type="submit">Sign up free</button>
          <p class="home-nl__msg" role="status" aria-live="polite"></p>
        </form>
      </div>
    </div>
  </div>
</section>

<section class="section section--soft" aria-labelledby="offers-title">
  <div class="ho-container">
    <div class="sec-head">
      <div><h2 class="sec-title" id="offers-title">More Local Offers</h2><p class="sec-sub">Deals from Harrow businesses. Some are just for newsletter subscribers.</p></div>
      <a class="link-arrow" href="<?php echo esc_url( hub_url( 'offers/' ) ); ?>">All offers <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="offer-tiles">
      <?php
		foreach ( array_slice( $hub_offers['items'], 1, 6 ) as $hub_offer ) {
			get_template_part( 'parts/offers/tile', null, array( 'offer' => $hub_offer ) );
		}
		?>
    </div>
  </div>
</section>

<section class="stats-band" aria-label="The Hub in numbers">
  <div class="ho-container">
    <div class="stats-strip"><a class="stat" href="<?php echo esc_url( hub_url( 'offers/' ) ); ?>"><span class="stat__label">Offers live</span><strong class="stat__value" data-count="<?php echo (int) $hub_offers['live_total']; ?>"><?php echo (int) $hub_offers['live_total']; ?></strong><span class="stat__note">and counting</span></a><a class="stat" href="<?php echo esc_url( hub_url( 'events/' ) ); ?>"><span class="stat__label">This week</span><strong class="stat__value" data-count="<?php echo (int) $hub_week_events; ?>"><?php echo (int) $hub_week_events; ?></strong><span class="stat__note">events across Harrow</span></a><a class="stat" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>"><span class="stat__label">Every week</span><strong class="stat__value" data-count="<?php echo (int) $hub_classes['total']; ?>"><?php echo (int) $hub_classes['total']; ?></strong><span class="stat__note">regular classes</span></a><a class="stat" href="<?php echo esc_url( hub_url( 'search/' ) ); ?>"><span class="stat__label">Local businesses</span><strong class="stat__value" data-count="<?php echo (int) $hub_biz['total']; ?>"><?php echo esc_html( number_format( $hub_biz['total'] ) ); ?></strong><span class="stat__note">across <?php echo (int) $hub_areas['total']; ?> areas</span></a></div>
  </div>
</section>

<section class="section" aria-labelledby="feat-title">
  <div class="ho-container">
    <div class="sec-head">
      <div><h2 class="sec-title" id="feat-title">Featured local businesses</h2><p class="sec-sub">Featured and Local Partner businesses on the Hub.</p></div>
      <a class="link-arrow hide-mobile" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Get featured <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="grid grid-4">
      <?php
		foreach ( $hub_featured['items'] as $hub_b ) {
			get_template_part( 'parts/businesses/card', null, array( 'business' => $hub_b ) );
		}
		?>
    </div>
  </div>
</section>

<section class="section section--soft" id="categories" aria-labelledby="cats-title">
  <div class="ho-container">
    <div class="sec-head">
      <div><h2 class="sec-title" id="cats-title">Popular categories</h2><p class="sec-sub">The categories with the most Harrow businesses.</p></div>
      <a class="link-arrow" href="<?php echo esc_url( hub_url( 'categories/' ) ); ?>">View all categories <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="cat-cards"><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/shops/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-gift"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Shops &amp; Specialists</span><span class="cat-card__count">372 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-gift"/></svg></span></a><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/restaurants/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-food"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Restaurants</span><span class="cat-card__count">275 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-food"/></svg></span></a><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/doctors-dentists-and-clinics/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-medical"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Doctors, Dentists &amp; Clinics</span><span class="cat-card__count">253 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-medical"/></svg></span></a><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/hair-and-beauty/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-scissors"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Hair &amp; Beauty</span><span class="cat-card__count">232 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-scissors"/></svg></span></a><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/motoring/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-car"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Motoring</span><span class="cat-card__count">149 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-car"/></svg></span></a><a class="cat-card" href="<?php echo esc_url( hub_url( 'category/finance-and-accountants/' ) ); ?>"><span class="cat-card__icon"><svg class="ho-icon"><use href="#i-pound"/></svg></span><span class="cat-card__body"><span class="cat-card__name">Finance &amp; Accountants</span><span class="cat-card__count">128 businesses</span></span><span class="cat-card__go"><svg class="ho-icon"><use href="#i-arrow"/></svg></span><span class="cat-card__mark" aria-hidden="true"><svg class="ho-icon"><use href="#i-pound"/></svg></span></a></div>
  </div>
</section>

<section class="section" id="areas" aria-labelledby="areas-title">
  <div class="ho-container">
    <div class="sec-head">
      <div><h2 class="sec-title" id="areas-title">Browse by area</h2><p class="sec-sub">From Pinner to Edgware, find what&rsquo;s near you.</p></div>
    </div>
    <div class="area-cards"><a class="area-card" href="<?php echo esc_url( hub_url( 'area/belmont/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Belmont</span><span class="area-card__count">79 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:25%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/canons-park/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Canons Park</span><span class="area-card__count">130 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:41%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/edgware/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Edgware</span><span class="area-card__count">178 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:56%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/greenhill/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Greenhill</span><span class="area-card__count">150 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:47%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/harrow-town-centre/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Harrow Town Centre</span><span class="area-card__count">318 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:100%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/harrow-weald/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Harrow Weald</span><span class="area-card__count">105 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:33%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/harrow-on-the-hill/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Harrow on the Hill</span><span class="area-card__count">55 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:17%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/hatch-end/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Hatch End</span><span class="area-card__count">130 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:41%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/headstone/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Headstone</span><span class="area-card__count">62 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:19%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/kenton/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Kenton</span><span class="area-card__count">100 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:31%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/north-harrow/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">North Harrow</span><span class="area-card__count">102 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:32%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/pinner/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Pinner</span><span class="area-card__count">210 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:66%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/queensbury/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Queensbury</span><span class="area-card__count">131 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:41%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/rayners-lane/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Rayners Lane</span><span class="area-card__count">207 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:65%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/south-harrow/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">South Harrow</span><span class="area-card__count">207 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:65%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/stanmore/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Stanmore</span><span class="area-card__count">136 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:43%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/sudbury-hill/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Sudbury Hill and Roxeth</span><span class="area-card__count">25 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:8%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/wealdstone/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">Wealdstone</span><span class="area-card__count">194 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:61%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a><a class="area-card" href="<?php echo esc_url( hub_url( 'area/west-harrow/' ) ); ?>"><span class="area-card__pin"><svg class="ho-icon"><use href="#i-pin"/></svg></span><span class="area-card__body"><span class="area-card__name">West Harrow</span><span class="area-card__count">90 businesses</span><span class="area-card__bar" aria-hidden="true"><i style="width:28%"></i></span></span><span class="area-card__go"><svg class="ho-icon"><use href="#i-chevron"/></svg></span></a></div>
    <div class="suggest mt-6">
      <div><p class="eyebrow mb-1">Local favourites</p><h2 class="suggest__title">Know a hidden gem?</h2><p class="mb-0">Tell us about a Harrow business you love. We&rsquo;re always looking for great local places.</p></div>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'add-business/?recommend=1' ) ); ?>">Recommend a business</a>
    </div>
  </div>
</section>

<!-- For businesses: one panel, text and actions left, the three steps as a timeline right (redesign, Nov 2026) -->
<section class="section" aria-labelledby="biz-title">
  <div class="ho-container">
    <div class="biz-panel">
      <div class="biz-panel__intro">
        <p class="eyebrow">For businesses</p>
        <h2 class="biz-panel__title" id="biz-title">Run a business in Harrow?</h2>
        <p class="biz-panel__lead">Claim your free listing, publish Local Offers and reach Harrow Online readers.</p>
        <div class="biz-panel__actions">
          <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'search/?type=businesses' ) ); ?>">Find my business</a>
          <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See business plans</a>
        </div>
        <p class="biz-panel__note"><svg class="ho-icon ho-icon--sm"><use href="#i-check"/></svg>Plans from free to Local Partner. <a href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See what&rsquo;s included</a></p>
      </div>
      <ol class="biz-steps">
        <li><span class="biz-steps__num" aria-hidden="true">1</span><div><h3>Claim or add your listing</h3><p>Most Harrow businesses are already listed. Find yours and claim it free, or add it in two minutes.</p></div></li>
        <li><span class="biz-steps__num" aria-hidden="true">2</span><div><h3>Add events, classes and offers</h3><p>On the Featured or Local Partner plan, your events go on What&rsquo;s On, weekly classes on Classes and deals on Offers.</p></div></li>
        <li><span class="biz-steps__num" aria-hidden="true">3</span><div><h3>Manage everything in one place</h3><p>One account for your listing, events, classes, offers and plan, with a checklist to get you started.</p></div></li>
      </ol>
    </div>
  </div>
</section>
<?php get_footer();

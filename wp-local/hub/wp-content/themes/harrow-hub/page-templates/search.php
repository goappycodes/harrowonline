<?php
/**
 * Search the Hub (converted from src/pages/search.html).
 * URL: /search/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-search',
	'description' => 'Search Harrow businesses, what\'s on, weekly classes and local offers on Harrow Online Hub.',
	'hubnav' => 'directory',
	'robots' => 'noindex, follow',
	'title' => 'Search the Hub',
) );
get_header();
?>
<?php $hub_biz_total = number_format( (int) hub_businesses_list( array( 'limit' => 1 ) )['total'] ); ?>
<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><span aria-current="page">Search</span></li></ol></nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">Search the Hub</h1>
  <p class="page-head__lead mb-5">Search <?php echo esc_html( $hub_biz_total ); ?> Harrow businesses, plus what&rsquo;s on, weekly classes and local offers.</p>

  <div class="searchbar searchbar--inline" role="search">
    <div class="searchbar__field">
      <svg class="ho-icon"><use href="#i-search"/></svg>
      <label class="ho-sr" for="s-q">What are you looking for?</label>
      <input id="s-q" type="search" name="q" form="filters" placeholder="Plumber, dance classes, pizza deals&hellip;" autocomplete="off">
    </div>
    <div class="searchbar__field">
      <svg class="ho-icon"><use href="#i-pin"/></svg>
      <label class="ho-sr" for="s-area">Area</label>
      <select id="s-area" name="area" form="filters"><option value="">All of Harrow</option><option value="belmont">Belmont</option><option value="canons-park">Canons Park</option><option value="edgware">Edgware</option><option value="greenhill">Greenhill</option><option value="harrow-town-centre">Harrow Town Centre</option><option value="harrow-weald">Harrow Weald</option><option value="harrow-on-the-hill">Harrow on the Hill</option><option value="hatch-end">Hatch End</option><option value="headstone">Headstone</option><option value="kenton">Kenton</option><option value="north-harrow">North Harrow</option><option value="pinner">Pinner</option><option value="queensbury">Queensbury</option><option value="rayners-lane">Rayners Lane</option><option value="south-harrow">South Harrow</option><option value="stanmore">Stanmore</option><option value="sudbury-hill">Sudbury Hill and Roxeth</option><option value="wealdstone">Wealdstone</option><option value="west-harrow">West Harrow</option></select>
    </div>
    <button class="ho-btn ho-btn--primary" type="submit" form="filters">Search</button>
  </div>
</header>

<div class="ho-container">
  <!-- Clean layout (Nov 2026): no filter sidebar. Tabs, then one quiet filter line; filters apply on change (hub.js 6f). -->
  <div data-directory data-all data-page-size="20">
    <nav class="tabs search-tabs" data-tabs aria-label="Result types" hidden></nav>
    <!-- Filters live on the count line; the (empty) form holds the search state, the controls join it with form="filters" -->
    <form class="dline dline--bare" id="filters" data-directory-form action="<?php echo esc_url( hub_url( 'search/' ) ); ?>" method="get" aria-label="Filter businesses">
      <input type="hidden" name="type">
    </form>
    <h2 class="ho-sr">Results</h2>
    <div class="toolbar dtoolbar dtoolbar--filters">
      <div class="dtoolbar__lead"><p class="toolbar__count" data-count><strong><?php echo esc_html( $hub_biz_total ); ?></strong> businesses</p><a class="dtoolbar__key" href="<?php echo esc_url( hub_url( 'pricing/#fb-labels' ) ); ?>"><svg class="ho-icon" aria-hidden="true"><use href="#i-info"/></svg>What the icons mean</a></div>
      <div class="dtoolbar__filters" data-biz-filters>
        <label class="fmini"><span class="ho-sr">Business category</span><select id="f-cat" name="cat" form="filters"><option value="">All categories</option><option value="arts-and-entertainment">Arts &amp; Entertainment</option><option value="classes-and-activities">Classes &amp; Activities</option><option value="cleaning-laundry-and-hire">Cleaning, Laundry &amp; Hire</option><option value="construction-and-building-services">Construction &amp; Building Services</option><option value="doctors-dentists-and-clinics">Doctors, Dentists &amp; Clinics</option><option value="education-and-tuition">Education &amp; Tuition</option><option value="events-and-parties">Events, Parties &amp; Catering</option><option value="family-and-childcare">Family &amp; Childcare</option><option value="fashion-and-clothing">Fashion &amp; Clothing</option><option value="finance-and-accountants">Finance &amp; Accountants</option><option value="food-and-drink">Food &amp; Drink</option><option value="food-shops-and-groceries">Food Shops &amp; Groceries</option><option value="hair-and-beauty">Hair &amp; Beauty</option><option value="health-and-wellbeing">Health &amp; Wellbeing</option><option value="home-and-garden">Home &amp; Garden</option><option value="hotels-and-places-to-stay">Hotels &amp; Places to Stay</option><option value="legal-services">Legal Services</option><option value="motoring">Motoring</option><option value="pets">Pets &amp; Vets</option><option value="printing-and-signs">Printing &amp; Signs</option><option value="professional-services">Professional Services</option><option value="property-and-estate-agents">Property &amp; Estate Agents</option><option value="pubs-and-bars">Pubs &amp; Bars</option><option value="restaurants">Restaurants</option><option value="shops">Shops &amp; Specialists</option><option value="sport-and-fitness">Sports &amp; Fitness</option><option value="travel">Travel &amp; Transport</option></select></label>
        <label class="fmini fmini--check"><input type="checkbox" name="web" value="1" form="filters"> Website</label>
        <label class="fmini fmini--check"><input type="checkbox" name="phone" value="1" form="filters"> Phone</label>
        <label class="fmini"><span class="ho-sr">Sort</span><select id="f-sort" name="sort" form="filters"><option value="relevance">Best match</option><option value="name">Name (A to Z)</option></select></label>
      </div>
    </div>
    <div class="active-filters" data-active hidden></div>
    <div data-groups></div>
    <div data-results><article class="biz-row biz-row--featured biz-row--media">
        <div class="ph ph--logo"><img src="<?php echo esc_url( hub_asset( 'img/business/aj-pizzas-logo.jpg' ) ); ?>" alt="" width="480" height="480" loading="lazy" decoding="async"></div>
        <div class="biz-row__main">
          <span class="biz-card__cat">Food &amp; Drink · <span class="muted">Pizza takeaway and delivery</span></span>
          <h3 class="biz-row__title"><a href="<?php echo esc_url( hub_url( 'business/aj-pizzas-harrow-town-centre/' ) ); ?>">AJ <span class="name-end">Pizza&#x27;s<span class="card__tick card__tick--verified" role="img" aria-label="Verified by the business" data-tip="Verified by the business"><svg class="ho-icon" aria-hidden="true"><use href="#i-verified"/></svg></span></span></a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg>123 Headstone Road, HA1 1PG</span><span>Harrow Town Centre</span></p>
          <p class="card__text">Local pizza takeaway on Headstone Road in Harrow, serving freshly prepared pizzas, sides and drinks for collection and delivery.</p>
          <p class="card__meta"><span class="open-now">Open today</span><span><svg class="ho-icon"><use href="#i-tag"/></svg>1 offer</span></p>
        </div>
        <div class="biz-row__side"><div class="cluster"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:02084889310"><svg class="ho-icon"><use href="#i-phone"/></svg>Call</a></div>
      </article><article class="biz-row biz-row--featured biz-row--media">
        <div class="ph ph--logo"><img src="<?php echo esc_url( hub_asset( 'img/business/empire-chase-logo.jpg' ) ); ?>" alt="" width="480" height="480" loading="lazy" decoding="async"></div>
        <div class="biz-row__main">
          <span class="biz-card__cat">Property &amp; Estate Agents · <span class="muted">Estate and letting agent</span></span>
          <h3 class="biz-row__title"><a href="<?php echo esc_url( hub_url( 'business/empire-chase-estate-agents-harrow-town-centre/' ) ); ?>">Empire Chase Estate <span class="name-end">Agents<span class="card__tick card__tick--verified" role="img" aria-label="Verified by the business" data-tip="Verified by the business"><svg class="ho-icon" aria-hidden="true"><use href="#i-verified"/></svg></span></span></a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg>14 Peterborough Road, HA1 2BQ</span><span>Harrow Town Centre</span></p>
          <p class="card__text">Independent estate and letting agents in Harrow Town Centre, helping people sell, buy, let and manage property across north-west London since 2012.</p>
          <p class="card__meta"><span class="open-now">Open today</span></p>
        </div>
        <div class="biz-row__side"><div class="cluster"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:02084227722"><svg class="ho-icon"><use href="#i-phone"/></svg>Call</a></div>
      </article><article class="biz-row biz-row--featured biz-row--media">
        <div class="ph ph--logo"><img src="<?php echo esc_url( hub_asset( 'img/business/gradesmart-tuition-logo.jpg' ) ); ?>" alt="" width="480" height="480" loading="lazy" decoding="async"></div>
        <div class="biz-row__main">
          <span class="biz-card__cat">Education &amp; Tuition · <span class="muted">Tuition centre</span></span>
          <h3 class="biz-row__title"><a href="<?php echo esc_url( hub_url( 'business/gradesmart-tuition-south-harrow/' ) ); ?>">GradeSmart <span class="name-end">Tuition<span class="card__tick card__tick--verified" role="img" aria-label="Verified by the business" data-tip="Verified by the business"><svg class="ho-icon" aria-hidden="true"><use href="#i-verified"/></svg></span></span></a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg>Christ Church Roxeth, HA2 0JN</span><span>South Harrow</span></p>
          <p class="card__text">Affordable Maths, English and Science tuition in South Harrow, in small groups of no more than six, from &pound;8.50 an hour.</p>
          <p class="card__meta"><span class="open-now open-now--closed">Closed today</span><span><svg class="ho-icon"><use href="#i-tag"/></svg>1 offer</span><span><svg class="ho-icon"><use href="#i-calendar"/></svg>1 upcoming</span></p>
        </div>
        <div class="biz-row__side"><div class="cluster"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:07346121877"><svg class="ho-icon"><use href="#i-phone"/></svg>Call</a></div>
      </article><article class="biz-row biz-row--mono biz-row--unclaimed">
        <span class="mono mono--soft" aria-hidden="true">1S</span>
        <div class="biz-row__main">
          <span class="biz-card__cat">Cleaning, Laundry &amp; Hire · <span class="muted">Rental service</span></span>
          <h3 class="biz-row__title"><a href="<?php echo esc_url( hub_url( 'business/1-stop-letting-solutions-west-harrow/' ) ); ?>">1 Stop Letting Solutions</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg>68 Vaughan Road, HA1 4ED</span><span>West Harrow</span></p>
        </div>
        <div class="biz-row__side"><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:07712809716"><svg class="ho-icon"><use href="#i-phone"/></svg>Call</a></div>
      </article><article class="biz-row biz-row--mono biz-row--unclaimed">
        <span class="mono mono--soft" aria-hidden="true">AC</span>
        <div class="biz-row__main">
          <span class="biz-card__cat">Property &amp; Estate Agents · <span class="muted">Real estate agent</span></span>
          <h3 class="biz-row__title"><a href="<?php echo esc_url( hub_url( 'business/andrea-co-estate-agents-harrow-town-centre/' ) ); ?>">Andrea &amp; Co. Estate Agents</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg>14 Havelock Place, HA1 1LJ</span><span>Harrow Town Centre</span></p>
        </div>
        <div class="biz-row__side"><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:02081766755"><svg class="ho-icon"><use href="#i-phone"/></svg>Call</a></div>
      </article><article class="biz-row biz-row--mono biz-row--unclaimed">
        <span class="mono mono--soft" aria-hidden="true">BP</span>
        <div class="biz-row__main">
          <span class="biz-card__cat">Legal Services · <span class="muted">Divorce and family law</span></span>
          <h3 class="biz-row__title"><a href="<?php echo esc_url( hub_url( 'business/barnes-partners-pinner/' ) ); ?>">Barnes &amp; Partners</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg>38 Bridge Street, HA5 3JF</span><span>Pinner</span></p>
        </div>
        <div class="biz-row__side"><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:02088665566"><svg class="ho-icon"><use href="#i-phone"/></svg>Call</a></div>
      </article></div>
    <nav class="pager" data-pager aria-label="Pages"></nav>
    <div data-after></div>

    <div class="limited-note mt-6" data-biz-note>
      <svg class="ho-icon"><use href="#i-info"/></svg>
      <p class="mb-0">Can&rsquo;t find a business? <a href="<?php echo esc_url( hub_url( 'add-business/' ) ); ?>">Add it to the Hub</a>. If it&rsquo;s yours, you can claim it free once it&rsquo;s listed. Featured and claimed listings always show first.</p>
    </div>
  </div>

  <div class="cta-band mt-6">
    <div><h2>Run a business in Harrow?</h2><p>Find your listing and claim it free, or add a missing business. It takes two minutes.</p></div>
    <div class="cta-band__actions"><a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'add-business/' ) ); ?>">List your business</a><a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See plans</a></div>
  </div>
</div>
<?php get_footer();

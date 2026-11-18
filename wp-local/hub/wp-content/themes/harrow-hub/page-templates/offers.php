<?php
/**
 * Local Offers: deals from Harrow businesses (converted from src/pages/offers.html).
 * URL: /offers/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-offers',
	'description' => 'Discounts and deals from Harrow businesses, from restaurants and cafés to gyms and barbers. Some offers are just for Harrow Online Newsletter subscribers.',
	'hubnav' => 'offers',
	'nav' => 'offers',
	'title' => 'Local Offers: deals from Harrow businesses',
) );
get_header();
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><span aria-current="page">Local Offers</span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">Local Offers</h1>
  <p class="page-head__lead">Deals from Harrow businesses, checked by our team and removed automatically when they end. Some are just for newsletter subscribers.</p>
</header>

<div class="ho-container">
  <!-- Filters (clean, Nov 2026): category chips, then one line with the count and two small filters that apply on change.
       The category select stays (hidden) so the chosen chip is kept when the area or subscriber filter changes. -->
  <div class="chips chips--scroll" aria-label="Popular categories">
    <a class="chip" href="<?php echo esc_url( hub_url( 'offers/' ) ); ?>" data-cat="" aria-current="page">All offers <span class="count">18</span></a>
    <a class="chip" href="<?php echo esc_url( hub_url( 'offers/?cat=restaurants' ) ); ?>" data-cat="restaurants">Restaurants <span class="count">4</span></a>
    <a class="chip" href="<?php echo esc_url( hub_url( 'offers/?cat=doctors-dentists-and-clinics' ) ); ?>" data-cat="doctors-dentists-and-clinics">Doctors, Dentists &amp; Clinics <span class="count">3</span></a>
    <a class="chip" href="<?php echo esc_url( hub_url( 'offers/?cat=food-and-drink' ) ); ?>" data-cat="food-and-drink">Food &amp; Drink <span class="count">3</span></a>
    <a class="chip" href="<?php echo esc_url( hub_url( 'offers/?cat=education-and-tuition' ) ); ?>" data-cat="education-and-tuition">Education &amp; Tuition <span class="count">2</span></a>
    <a class="chip" href="<?php echo esc_url( hub_url( 'offers/?cat=hair-and-beauty' ) ); ?>" data-cat="hair-and-beauty">Hair &amp; Beauty <span class="count">2</span></a>
    <a class="chip" href="<?php echo esc_url( hub_url( 'offers/?cat=sport-and-fitness' ) ); ?>" data-cat="sport-and-fitness">Sports &amp; Fitness <span class="count">2</span></a>
    <a class="chip" href="<?php echo esc_url( hub_url( 'offers/?cat=home-and-garden' ) ); ?>" data-cat="home-and-garden">Home &amp; Garden <span class="count">1</span></a>
    <a class="chip" href="<?php echo esc_url( hub_url( 'offers/?cat=shops' ) ); ?>" data-cat="shops">Shops &amp; Specialists <span class="count">1</span></a>
  </div>
  <div class="fline">
    <p class="toolbar__count" data-offers-count><strong>18</strong> offers across Harrow</p>
    <form class="fline__form" action="<?php echo esc_url( hub_url( 'offers/' ) ); ?>" method="get" aria-label="Filter offers" data-autofilter>
      <select id="o-cat" name="cat" hidden aria-label="Category" tabindex="-1"><option value="">All categories</option><option value="arts-and-entertainment">Arts &amp; Entertainment</option><option value="classes-and-activities">Classes &amp; Activities</option><option value="cleaning-laundry-and-hire">Cleaning, Laundry &amp; Hire</option><option value="construction-and-building-services">Construction &amp; Building Services</option><option value="doctors-dentists-and-clinics">Doctors, Dentists &amp; Clinics</option><option value="education-and-tuition">Education &amp; Tuition</option><option value="events-and-parties">Events, Parties &amp; Catering</option><option value="family-and-childcare">Family &amp; Childcare</option><option value="fashion-and-clothing">Fashion &amp; Clothing</option><option value="finance-and-accountants">Finance &amp; Accountants</option><option value="food-and-drink">Food &amp; Drink</option><option value="food-shops-and-groceries">Food Shops &amp; Groceries</option><option value="hair-and-beauty">Hair &amp; Beauty</option><option value="health-and-wellbeing">Health &amp; Wellbeing</option><option value="home-and-garden">Home &amp; Garden</option><option value="hotels-and-places-to-stay">Hotels &amp; Places to Stay</option><option value="legal-services">Legal Services</option><option value="motoring">Motoring</option><option value="pets">Pets &amp; Vets</option><option value="printing-and-signs">Printing &amp; Signs</option><option value="professional-services">Professional Services</option><option value="property-and-estate-agents">Property &amp; Estate Agents</option><option value="pubs-and-bars">Pubs &amp; Bars</option><option value="restaurants">Restaurants</option><option value="shops">Shops &amp; Specialists</option><option value="sport-and-fitness">Sports &amp; Fitness</option><option value="travel">Travel &amp; Transport</option></select>
      <label class="fsel"><span class="ho-sr">Area</span><select id="o-area" name="area"><option value="">All of Harrow</option><option value="belmont">Belmont</option><option value="canons-park">Canons Park</option><option value="edgware">Edgware</option><option value="greenhill">Greenhill</option><option value="harrow-town-centre">Harrow Town Centre</option><option value="harrow-weald">Harrow Weald</option><option value="harrow-on-the-hill">Harrow on the Hill</option><option value="hatch-end">Hatch End</option><option value="headstone">Headstone</option><option value="kenton">Kenton</option><option value="north-harrow">North Harrow</option><option value="pinner">Pinner</option><option value="queensbury">Queensbury</option><option value="rayners-lane">Rayners Lane</option><option value="south-harrow">South Harrow</option><option value="stanmore">Stanmore</option><option value="sudbury-hill">Sudbury Hill and Roxeth</option><option value="wealdstone">Wealdstone</option><option value="west-harrow">West Harrow</option></select></label>
      <label class="fcheck" for="o-sub"><input id="o-sub" type="checkbox" name="sub" value="1"> Subscriber-only</label>
      <button class="ho-btn ho-btn--dark ho-btn--sm fpanel__go" type="submit">Show offers</button>
      <a class="fpanel__clear" href="<?php echo esc_url( hub_url( 'offers/' ) ); ?>" data-filter-clear hidden>Clear</a>
    </form>
  </div>

  <div class="notice mb-5" data-offers-empty hidden><svg class="ho-icon"><use href="#i-tag"/></svg><p>No offers match that yet. <a href="<?php echo esc_url( hub_url( 'offers/' ) ); ?>">See all offers</a>.</p></div>

  <!-- hub.js filters these by ?cat=, ?area= and ?sub=1 (WordPress filters server-side with the same parameters) -->
  <div class="offer-cards" data-offers-list>
    <a class="offer-card offer-card--featured" href="<?php echo esc_url( hub_url( 'offers/two-pizza-collection-deal-from-7-99-each/' ) ); ?>" data-cat="food-and-drink" data-area="harrow-town-centre" data-sub="1">
        <span class="offer-card__top"><span class="biz-card__cat">Food &amp; Drink</span><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span><span class="card__tick card__tick--sub" role="img" aria-label="Subscribers only" data-tip="Subscribers only"><svg class="ho-icon"><use href="#i-lock"/></svg></span></span>
        <strong class="offer-card__value">FROM &pound;7.99</strong>
        <span class="offer-card__title">Two-pizza collection deal from &pound;7.99 each</span>
        <span class="offer-card__biz">AJ Pizza&#x27;s &middot; Harrow Town Centre</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>No end date</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card offer-card--featured" href="<?php echo esc_url( hub_url( 'offers/75-off-your-first-month-of-tuition/' ) ); ?>" data-cat="education-and-tuition" data-area="south-harrow" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Education &amp; Tuition</span><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></span>
        <strong class="offer-card__value">75% OFF</strong>
        <span class="offer-card__title">75% off your first month of tuition</span>
        <span class="offer-card__biz">GradeSmart Tuition &middot; South Harrow</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>No end date</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card offer-card--featured" href="<?php echo esc_url( hub_url( 'offers/10-off-your-meal-when-you-spend-over-20/' ) ); ?>" data-cat="restaurants" data-area="wealdstone" data-sub="1">
        <span class="offer-card__top"><span class="biz-card__cat">Restaurants</span><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span><span class="card__tick card__tick--sub" role="img" aria-label="Subscribers only" data-tip="Subscribers only"><svg class="ho-icon"><use href="#i-lock"/></svg></span></span>
        <strong class="offer-card__value">10% OFF</strong>
        <span class="offer-card__title">10% off your meal when you spend over &pound;20</span>
        <span class="offer-card__biz">Meeting Palace &middot; Wealdstone</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 28 Feb</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card offer-card--featured" href="<?php echo esc_url( hub_url( 'offers/2-for-1-mains-monday-to-thursday/' ) ); ?>" data-cat="restaurants" data-area="harrow-on-the-hill" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Restaurants</span><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></span>
        <strong class="offer-card__value">2-FOR-1</strong>
        <span class="offer-card__title">2-for-1 mains, Monday to Thursday</span>
        <span class="offer-card__biz">Hill Street Kitchen &middot; Harrow on the Hill</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 30 Nov</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card offer-card--featured" href="<?php echo esc_url( hub_url( 'offers/free-poppadoms-and-chutneys-with-any-takeaway-over-20/' ) ); ?>" data-cat="restaurants" data-area="rayners-lane" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Restaurants</span><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></span>
        <strong class="offer-card__value">FREE POPPADOMS</strong>
        <span class="offer-card__title">Free poppadoms and chutneys with any takeaway over &pound;20</span>
        <span class="offer-card__biz">Rayners Lane Spice House &middot; Rayners Lane</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 31 Dec</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card offer-card--featured" href="<?php echo esc_url( hub_url( 'offers/25-off-your-first-hygienist-visit/' ) ); ?>" data-cat="doctors-dentists-and-clinics" data-area="stanmore" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Doctors, Dentists &amp; Clinics</span><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></span>
        <strong class="offer-card__value">£25 OFF</strong>
        <span class="offer-card__title">&pound;25 off your first hygienist visit</span>
        <span class="offer-card__biz">Stanmore Family Dental &middot; Stanmore</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 31 Dec</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card offer-card--featured" href="<?php echo esc_url( hub_url( 'offers/a-free-week-of-classes/' ) ); ?>" data-cat="sport-and-fitness" data-area="wealdstone" data-sub="1">
        <span class="offer-card__top"><span class="biz-card__cat">Sports &amp; Fitness</span><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span><span class="card__tick card__tick--sub" role="img" aria-label="Subscribers only" data-tip="Subscribers only"><svg class="ho-icon"><use href="#i-lock"/></svg></span></span>
        <strong class="offer-card__value">FREE WEEK</strong>
        <span class="offer-card__title">A free week of classes</span>
        <span class="offer-card__biz">Wealdstone Fitness Studio &middot; Wealdstone</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 14 Dec</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/free-coffee-with-any-slice-of-cake/' ) ); ?>" data-cat="food-and-drink" data-area="hatch-end" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Food &amp; Drink</span></span>
        <strong class="offer-card__value">FREE COFFEE</strong>
        <span class="offer-card__title">Free coffee with any slice of cake</span>
        <span class="offer-card__biz">Hatch End Bookshop &amp; Café &middot; Hatch End</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 31 Dec</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/10-off-every-book-all-winter/' ) ); ?>" data-cat="food-and-drink" data-area="hatch-end" data-sub="1">
        <span class="offer-card__top"><span class="biz-card__cat">Food &amp; Drink</span><span class="card__tick card__tick--sub" role="img" aria-label="Subscribers only" data-tip="Subscribers only"><svg class="ho-icon"><use href="#i-lock"/></svg></span></span>
        <strong class="offer-card__value">10% OFF</strong>
        <span class="offer-card__title">10% off every book, all winter</span>
        <span class="offer-card__biz">Hatch End Bookshop &amp; Café &middot; Hatch End</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 31 Jan</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/kids-eat-free-on-sundays/' ) ); ?>" data-cat="restaurants" data-area="harrow-on-the-hill" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Restaurants</span></span>
        <strong class="offer-card__value">KIDS EAT FREE</strong>
        <span class="offer-card__title">Kids eat free on Sundays</span>
        <span class="offer-card__biz">Hill Street Kitchen &middot; Harrow on the Hill</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 27 Dec</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/free-electric-toothbrush-with-a-new-patient-exam/' ) ); ?>" data-cat="doctors-dentists-and-clinics" data-area="stanmore" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Doctors, Dentists &amp; Clinics</span></span>
        <strong class="offer-card__value">FREE TOOTHBRUSH</strong>
        <span class="offer-card__title">Free electric toothbrush with a new patient exam</span>
        <span class="offer-card__biz">Stanmore Family Dental &middot; Stanmore</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 31 Dec</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/no-joining-fee-in-november/' ) ); ?>" data-cat="sport-and-fitness" data-area="wealdstone" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Sports &amp; Fitness</span></span>
        <strong class="offer-card__value">NO JOINING FEE</strong>
        <span class="offer-card__title">No joining fee in November</span>
        <span class="offer-card__biz">Wealdstone Fitness Studio &middot; Wealdstone</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 30 Nov</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/first-session-free/' ) ); ?>" data-cat="education-and-tuition" data-area="kenton" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Education &amp; Tuition</span></span>
        <strong class="offer-card__value">FIRST SESSION FREE</strong>
        <span class="offer-card__title">First session free</span>
        <span class="offer-card__biz">Bright Sparks Coding &middot; Kenton</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 31 Dec</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/20-off-a-boiler-service/' ) ); ?>" data-cat="home-and-garden" data-area="edgware" data-sub="1">
        <span class="offer-card__top"><span class="biz-card__cat">Home &amp; Garden</span><span class="card__tick card__tick--sub" role="img" aria-label="Subscribers only" data-tip="Subscribers only"><svg class="ho-icon"><use href="#i-lock"/></svg></span></span>
        <strong class="offer-card__value">£20 OFF</strong>
        <span class="offer-card__title">&pound;20 off a boiler service</span>
        <span class="offer-card__biz">Edgware Heating &amp; Plumbing &middot; Edgware</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 31 Dec</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/free-blood-pressure-check/' ) ); ?>" data-cat="doctors-dentists-and-clinics" data-area="kenton" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Doctors, Dentists &amp; Clinics</span></span>
        <strong class="offer-card__value">FREE BP CHECK</strong>
        <span class="offer-card__title">Free blood pressure check</span>
        <span class="offer-card__biz">Kenton Road Pharmacy &middot; Kenton</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 31 Jan</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/free-winter-bike-safety-check/' ) ); ?>" data-cat="shops" data-area="pinner" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Shops &amp; Specialists</span></span>
        <strong class="offer-card__value">FREE SAFETY CHECK</strong>
        <span class="offer-card__title">Free winter bike safety check</span>
        <span class="offer-card__biz">Pinner Cycle Workshop &middot; Pinner</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 31 Dec</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/20-off-your-first-cut/' ) ); ?>" data-cat="hair-and-beauty" data-area="harrow-town-centre" data-sub="1">
        <span class="offer-card__top"><span class="biz-card__cat">Hair &amp; Beauty</span><span class="card__tick card__tick--sub" role="img" aria-label="Subscribers only" data-tip="Subscribers only"><svg class="ho-icon"><use href="#i-lock"/></svg></span></span>
        <strong class="offer-card__value">20% OFF</strong>
        <span class="offer-card__title">20% off your first cut</span>
        <span class="offer-card__biz">Station Road Barbers &middot; Harrow Town Centre</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends 20 Dec</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
<a class="offer-card" href="<?php echo esc_url( hub_url( 'offers/student-tuesdays-15-cuts/' ) ); ?>" data-cat="hair-and-beauty" data-area="harrow-town-centre" data-sub="0">
        <span class="offer-card__top"><span class="biz-card__cat">Hair &amp; Beauty</span><span class="badge badge--soon"><svg class="ho-icon"><use href="#i-clock"/></svg>Ends soon</span></span>
        <strong class="offer-card__value">£15 CUTS</strong>
        <span class="offer-card__title">Student Tuesdays: &pound;15 cuts</span>
        <span class="offer-card__biz">Station Road Barbers &middot; Harrow Town Centre</span>
        <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg>Ends in 6 days</span><span class="offer-card__go">View offer <svg class="ho-icon ho-icon--sm"><use href="#i-arrow"/></svg></span></span>
      </a>
  </div>

  <p class="small muted mt-5">Offers are run by the businesses listed. Check terms with the business before you buy. Spotted a problem with an offer? <a href="<?php echo esc_url( hub_url( 'report/' ) ); ?>">Let us know</a>.</p>

  <div class="cta-band mt-6">
    <div>
      <h2>Run an offer for Harrow</h2>
      <p>Hub members on a paid plan can post Local Offers from their dashboard. Offers appear here, on your profile and in the newsletter.</p>
    </div>
    <div class="cta-band__actions">
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See plans</a>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>">Add an offer</a>
    </div>
  </div>
</div>
<?php get_footer();

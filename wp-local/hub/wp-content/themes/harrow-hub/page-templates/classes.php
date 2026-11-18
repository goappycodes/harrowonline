<?php
/**
 * Classes and activities in Harrow (converted from src/pages/classes.html).
 * URL: /events/classes/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-classes',
	'description' => 'Weekly classes and activities across Harrow: children\'s clubs, fitness classes, dance schools, workshops and evening courses. Filter by age, area and day.',
	'hubnav' => 'events',
	'nav' => 'classes',
	'title' => 'Classes and activities in Harrow',
) );
get_header();
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><a href="<?php echo esc_url( hub_url( 'events/' ) ); ?>">What&rsquo;s On</a></li><li><span aria-current="page">Classes and activities</span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">Classes and activities</h1>
  <p class="page-head__lead">Regular classes that run every week across Harrow: kids&rsquo; clubs, fitness, dance schools, workshops and evening courses. Each class has one page that the provider keeps up to date.</p>
</header>

<div class="ho-container">
  <!-- Filters (clean, Nov 2026): class type chips, then one line with the count and three small filters that apply on change -->
  <div class="chips chips--scroll" aria-label="Class type">
    <a class="chip" data-type="" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>" aria-current="page">All classes <span class="count">23</span></a>
    <a class="chip" data-type="childrens-activities" href="<?php echo esc_url( hub_url( 'events/classes/?type=childrens-activities' ) ); ?>">Children&rsquo;s activities <span class="count">8</span></a>
    <a class="chip" data-type="fitness" href="<?php echo esc_url( hub_url( 'events/classes/?type=fitness' ) ); ?>">Fitness <span class="count">5</span></a>
    <a class="chip" data-type="dance-schools" href="<?php echo esc_url( hub_url( 'events/classes/?type=dance-schools' ) ); ?>">Dance schools <span class="count">8</span></a>
    <a class="chip" data-type="workshops" href="<?php echo esc_url( hub_url( 'events/classes/?type=workshops' ) ); ?>">Workshops <span class="count">1</span></a>
    <a class="chip" data-type="other" href="<?php echo esc_url( hub_url( 'events/classes/?type=other' ) ); ?>">Other regular classes <span class="count">1</span></a>
  </div>
  <div class="fline">
    <div class="fline__count">
      <p class="toolbar__count" data-classes-count><strong>23</strong> classes and activities</p>
    </div>
    <form class="fline__form" action="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>" method="get" aria-label="Filter classes" data-autofilter>
      <input type="hidden" name="type" value="" data-keep-type>
      <label class="fsel"><span class="ho-sr">Age</span><select id="c-age" name="age"><option value="">Any age</option> <option value="under-5">Under 5s</option> <option value="5-11">5 to 11</option> <option value="12-17">12 to 17</option> <option value="adults">Adults</option> <option value="60-plus">Over 60s</option></select></label>
      <label class="fsel"><span class="ho-sr">Area</span><select id="c-area" name="area"><option value="">All of Harrow</option><option value="belmont">Belmont</option><option value="canons-park">Canons Park</option><option value="edgware">Edgware</option><option value="greenhill">Greenhill</option><option value="harrow-town-centre">Harrow Town Centre</option><option value="harrow-weald">Harrow Weald</option><option value="harrow-on-the-hill">Harrow on the Hill</option><option value="hatch-end">Hatch End</option><option value="headstone">Headstone</option><option value="kenton">Kenton</option><option value="north-harrow">North Harrow</option><option value="pinner">Pinner</option><option value="queensbury">Queensbury</option><option value="rayners-lane">Rayners Lane</option><option value="south-harrow">South Harrow</option><option value="stanmore">Stanmore</option><option value="sudbury-hill">Sudbury Hill and Roxeth</option><option value="wealdstone">Wealdstone</option><option value="west-harrow">West Harrow</option></select></label>
      <label class="fsel"><span class="ho-sr">Day of the week</span><select id="c-day" name="day"><option value="">Any day</option> <option value="mon">Monday</option> <option value="tue">Tuesday</option> <option value="wed">Wednesday</option> <option value="thu">Thursday</option> <option value="fri">Friday</option> <option value="sat">Saturday</option> <option value="sun">Sunday</option></select></label>
      <button class="ho-btn ho-btn--dark ho-btn--sm fpanel__go" type="submit">Show classes</button>
      <a class="fpanel__clear" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>" data-filter-clear hidden>Clear</a>
    </form>
  </div>

  <h2 class="ho-sr" id="classes-list">All classes and activities</h2>
  <div class="notice mb-5" data-classes-empty hidden><svg class="ho-icon"><use href="#i-calendar"/></svg><p>No classes match that yet. <a href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>">See all classes</a>.</p></div>
  <!-- hub.js filters these by ?type=, ?age=, ?area= and ?day= (WordPress filters server-side with the same parameters) -->
  <div class="grid grid-3" data-classes-list>
      <article class="card event-card card--featured" data-type="childrens-activities" data-area="hatch-end" data-day="sat" data-ages="under-5 5-11">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--photo"><img src="<?php echo esc_url( hub_asset( 'img/business/parkfield-youth-fc-team.jpg' ) ); ?>" alt="Parkfield Youth FC players in a team huddle" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Children&rsquo;s activities</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/saturday-soccer-school-reception-to-year-2/' ) ); ?>">Saturday Soccer School: Reception to Year 2</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Saturday, 10am to 11am</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 4 to 7 (Reception to Year 2)</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;6</span></p>
          <p class="card__text">Football coaching from DBS-checked, FA-qualified coaches. No booking needed: just turn up.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/parkfield-youth-fc-hatch-end/' ) ); ?>">Parkfield Youth FC</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="childrens-activities" data-area="hatch-end" data-day="sat" data-ages="5-11 12-17">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--photo"><img src="<?php echo esc_url( hub_asset( 'img/business/parkfield-youth-fc-team.jpg' ) ); ?>" alt="Parkfield Youth FC players in a team huddle" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Children&rsquo;s activities</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/saturday-soccer-school-years-3-to-9/' ) ); ?>">Saturday Soccer School: Years 3 to 9</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Saturday, 11am to 12pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 7 to 14 (Years 3 to 9)</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;6</span></p>
          <p class="card__text">Football coaching from DBS-checked, FA-qualified coaches. No booking needed: just turn up.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/parkfield-youth-fc-hatch-end/' ) ); ?>">Parkfield Youth FC</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="childrens-activities" data-area="hatch-end" data-day="sat" data-ages="under-5 5-11 12-17">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--photo"><img src="<?php echo esc_url( hub_asset( 'img/business/parkfield-youth-fc-team.jpg' ) ); ?>" alt="Parkfield Youth FC players in a team huddle" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Children&rsquo;s activities</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/girls-saturday-soccer-school/' ) ); ?>">Girls&rsquo; Saturday Soccer School</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Saturday, time to be confirmed</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Girls (ages to be confirmed)</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Ask the club</span></p>
          <p class="card__text">A girls-only Soccer School session. Call or email the club for the time before you go.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/parkfield-youth-fc-hatch-end/' ) ); ?>">Parkfield Youth FC</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="childrens-activities" data-area="south-harrow" data-day="sat" data-ages="5-11 12-17 adults">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--logo"><img src="<?php echo esc_url( hub_asset( 'img/business/gradesmart-tuition-logo.jpg' ) ); ?>" alt="GradeSmart Tuition logo" width="480" height="480" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Children&rsquo;s activities</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/saturday-small-group-tuition/' ) ); ?>">Saturday Small Group Tuition</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Saturday, 10am to 2pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>South Harrow</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 7 to 18 (Years 3 to 13)</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>From &pound;8.50 an hour</span></p>
          <p class="card__text">Maths, English and Science in groups of six or fewer. Free assessment and 75% off the first month.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/gradesmart-tuition-south-harrow/' ) ); ?>">GradeSmart Tuition</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="dance-schools" data-area="hatch-end" data-day="mon" data-ages="5-11 12-17 adults">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--photo"><img src="<?php echo esc_url( hub_asset( 'img/business/kidology-dance-co-dancers.jpg' ) ); ?>" alt="Young Kidology dancers in KDC T-shirts" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Dance schools</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/kids-breakdance/' ) ); ?>">Kids&rsquo; Breakdance</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Monday, 5pm to 6.30pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 8+</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Free trial, then ask for prices</span></p>
          <p class="card__text">Footwork, freezes and power moves at Harrow Arts Centre. Book by half term. Free trial class available.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/kidology-dance-co-hatch-end/' ) ); ?>">Kidology Dance Co</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="dance-schools" data-area="hatch-end" data-day="thu" data-ages="5-11 12-17 adults">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--photo"><img src="<?php echo esc_url( hub_asset( 'img/business/kidology-dance-co-dancers.jpg' ) ); ?>" alt="Young Kidology dancers in KDC T-shirts" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Dance schools</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/musical-theatre-for-kids/' ) ); ?>">Musical Theatre for Kids</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Thursday, 5pm to 6pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 8+</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Free trial, then ask for prices</span></p>
          <p class="card__text">Singing, acting and dance from the musicals. Book by half term. Free trial class available.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/kidology-dance-co-hatch-end/' ) ); ?>">Kidology Dance Co</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="dance-schools" data-area="hatch-end" data-day="fri" data-ages="under-5">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--photo"><img src="<?php echo esc_url( hub_asset( 'img/business/kidology-dance-co-dancers.jpg' ) ); ?>" alt="Young Kidology dancers in KDC T-shirts" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Dance schools</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/toddler-dance/' ) ); ?>">Toddler Dance</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Friday, 5pm to 5.45pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 2 to 4</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Free trial, then ask for prices</span></p>
          <p class="card__text">A first dance class: music, movement and fun. Book by half term. Free trial class available.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/kidology-dance-co-hatch-end/' ) ); ?>">Kidology Dance Co</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="dance-schools" data-area="hatch-end" data-day="fri" data-ages="5-11">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--photo"><img src="<?php echo esc_url( hub_asset( 'img/business/kidology-dance-co-dancers.jpg' ) ); ?>" alt="Young Kidology dancers in KDC T-shirts" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Dance schools</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/mini-street-dance/' ) ); ?>">Mini Street Dance</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Friday, 5pm to 6pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 5 to 7</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Free trial, then ask for prices</span></p>
          <p class="card__text">Fun street dance routines to the latest music. Book by half term. Free trial class available.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/kidology-dance-co-hatch-end/' ) ); ?>">Kidology Dance Co</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="dance-schools" data-area="hatch-end" data-day="fri" data-ages="5-11">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--photo"><img src="<?php echo esc_url( hub_asset( 'img/business/kidology-dance-co-dancers.jpg' ) ); ?>" alt="Young Kidology dancers in KDC T-shirts" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Dance schools</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/junior-street-dance/' ) ); ?>">Junior Street Dance</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Friday, 5pm to 6pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 8 to 11</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Free trial, then ask for prices</span></p>
          <p class="card__text">Street dance and hip-hop routines. Book by half term. Free trial class available.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/kidology-dance-co-hatch-end/' ) ); ?>">Kidology Dance Co</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="dance-schools" data-area="hatch-end" data-day="fri" data-ages="12-17 adults">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--photo"><img src="<?php echo esc_url( hub_asset( 'img/business/kidology-dance-co-dancers.jpg' ) ); ?>" alt="Young Kidology dancers in KDC T-shirts" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Dance schools</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/senior-street-dance-and-hip-hop/' ) ); ?>">Senior Street Dance and Hip-Hop</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Friday, 6pm to 7pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 12+</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Free trial, then ask for prices</span></p>
          <p class="card__text">Routines for performances and the annual showcase. Book by half term. Free trial class available.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/kidology-dance-co-hatch-end/' ) ); ?>">Kidology Dance Co</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="childrens-activities" data-area="harrow-weald" data-day="" data-ages="under-5 5-11 12-17">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--logo"><img src="<?php echo esc_url( hub_asset( 'img/business/kodak-fc-badge.jpg' ) ); ?>" alt="Kodak FC badge" width="480" height="480" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Children&rsquo;s activities</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'business/kodak-fc-harrow-weald/' ) ); ?>">Kodak FC Youth Football</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Training and matches, times vary by age group</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Harrow Weald</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Youth teams</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Ask the club</span></p>
          <p class="card__text">Youth teams in the Harrow Youth Football League, with FA-qualified coaches. Call the club to find the right age group.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/kodak-fc-harrow-weald/' ) ); ?>">Kodak FC</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="fitness" data-area="harrow-weald" data-day="sat" data-ages="adults">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--logo"><img src="<?php echo esc_url( hub_asset( 'img/business/kodak-fc-badge.jpg' ) ); ?>" alt="Kodak FC badge" width="480" height="480" loading="lazy" decoding="async"></div>
        <div class="card__body">
          <span class="biz-card__cat">Fitness</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'business/kodak-fc-harrow-weald/' ) ); ?>">Kodak FC Adult Football</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Training and Saturday matches, times vary by team</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Harrow Weald</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Adults</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Ask the club</span></p>
          <p class="card__text">Senior teams in the Middlesex County Football League. New players welcome, including those coming back to football.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg><a href="<?php echo esc_url( hub_url( 'business/kodak-fc-harrow-weald/' ) ); ?>">Kodak FC</a></span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="fitness" data-area="wealdstone" data-day="sat" data-ages="adults">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--dark"><svg class="ho-icon"><use href="#i-dumbbell"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Fitness</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/weekend-bootcamp/' ) ); ?>">Weekend Bootcamp</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Saturday, 9am</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Wealdstone</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Adults</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;10</span></p>
          <p class="card__text">Circuits for every fitness level. First session free.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>Wealdstone Fitness Studio</span></p>
        </div>
      </article>
      <article class="card event-card card--featured" data-type="fitness" data-area="wealdstone" data-day="fri" data-ages="adults">
        <div class="card__badges"><span class="badge badge--featured"><svg class="ho-icon"><use href="#i-star"/></svg>Featured</span></div>
        <div class="ph ph--dark"><svg class="ho-icon"><use href="#i-dumbbell"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Fitness</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/evening-yoga-flow/' ) ); ?>">Evening Yoga Flow</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Friday, 6.30pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Wealdstone</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Adults</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;10</span></p>
          <p class="card__text">An hour of vinyasa yoga to end the week. Mats provided.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>Wealdstone Fitness Studio</span></p>
        </div>
      </article>
      <article class="card event-card" data-type="childrens-activities" data-area="kenton" data-day="sat" data-ages="5-11 12-17">
        <div class="card__badges"></div>
        <div class="ph"><svg class="ho-icon"><use href="#i-family"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Children&rsquo;s activities</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/saturday-kids-coding-club/' ) ); ?>">Saturday Kids&rsquo; Coding Club</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Saturday, 10am</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Kenton</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 8 to 12</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;12</span></p>
          <p class="card__text">Games and animations in Scratch and Python. Laptops provided.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>Bright Sparks Coding</span></p>
        </div>
      </article>
      <article class="card event-card" data-type="childrens-activities" data-area="hatch-end" data-day="sat" data-ages="under-5 5-11">
        <div class="card__badges"></div>
        <div class="ph"><svg class="ho-icon"><use href="#i-cup"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Children&rsquo;s activities</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/story-time/' ) ); ?>">Story Time</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Saturday, 10.30am</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 3 to 6</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Free</span></p>
          <p class="card__text">Picture books, songs and a craft, with a babyccino for grown-ups.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>Hatch End Bookshop &amp; Caf&eacute;</span></p>
        </div>
      </article>
      <article class="card event-card" data-type="dance-schools" data-area="pinner" data-day="sat" data-ages="under-5 5-11">
        <div class="card__badges"></div>
        <div class="ph"><svg class="ho-icon"><use href="#i-music"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Dance schools</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/junior-ballet/' ) ); ?>">Junior Ballet</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Saturday, 9.30am</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Pinner</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 4 to 7</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;9</span></p>
          <p class="card__text">Playful ballet for little dancers, working towards a summer show.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>Pinner Village Hall</span></p>
        </div>
      </article>
      <article class="card event-card" data-type="dance-schools" data-area="rayners-lane" data-day="fri" data-ages="12-17">
        <div class="card__badges"></div>
        <div class="ph"><svg class="ho-icon"><use href="#i-music"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Dance schools</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/street-dance-for-teens/' ) ); ?>">Street Dance for Teens</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Friday, 6pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Rayners Lane</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 12 to 16</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;7</span></p>
          <p class="card__text">Hip hop and commercial routines. No experience needed.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>Rayners Lane Youth Hall</span></p>
        </div>
      </article>
      <article class="card event-card" data-type="workshops" data-area="hatch-end" data-day="tue" data-ages="adults">
        <div class="card__badges"></div>
        <div class="ph"><svg class="ho-icon"><use href="#i-edit"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Workshops</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/beginners-pottery/' ) ); ?>">Beginners&rsquo; Pottery</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Tuesdays, 7pm (6 weeks)</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Hatch End</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Adults</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;150 course</span></p>
          <p class="card__text">Learn to throw and glaze on the wheel. Clay and firing included.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>Harrow Arts Centre</span></p>
        </div>
      </article>
      <article class="card event-card" data-type="childrens-activities" data-area="stanmore" data-day="thu" data-ages="under-5">
        <div class="card__badges"></div>
        <div class="ph"><svg class="ho-icon"><use href="#i-family"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Children&rsquo;s activities</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/toddler-rhyme-time/' ) ); ?>">Toddler Rhyme Time</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Thursday, 10.30am</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Stanmore</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Under 4s</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>Free</span></p>
          <p class="card__text">Songs, rhymes and stories for little ones. Just turn up.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>Stanmore Library</span></p>
        </div>
      </article>
      <article class="card event-card" data-type="fitness" data-area="wealdstone" data-day="wed" data-ages="adults">
        <div class="card__badges"></div>
        <div class="ph"><svg class="ho-icon"><use href="#i-dumbbell"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Fitness</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/lunchtime-spin/' ) ); ?>">Lunchtime Spin</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Wednesday, 12.30pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Wealdstone</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Adults</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;8</span></p>
          <p class="card__text">45 minutes of high-energy spin, back at your desk by 1.30pm.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>Wealdstone Fitness Studio</span></p>
        </div>
      </article>
      <article class="card event-card" data-type="fitness" data-area="headstone" data-day="mon" data-ages="60-plus adults">
        <div class="card__badges"></div>
        <div class="ph"><svg class="ho-icon"><use href="#i-heart"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Fitness</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/chair-yoga-for-over-60s/' ) ); ?>">Chair Yoga for Over-60s</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Monday, 11am</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>Headstone</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Ages 60+</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;3</span></p>
          <p class="card__text">Gentle seated yoga to help with balance and movement.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>St George&rsquo;s Hall</span></p>
        </div>
      </article>
      <article class="card event-card" data-type="other" data-area="north-harrow" data-day="thu" data-ages="adults">
        <div class="card__badges"></div>
        <div class="ph"><svg class="ho-icon"><use href="#i-people"/></svg></div>
        <div class="card__body">
          <span class="biz-card__cat">Other regular classes</span>
          <h3 class="card__title"><a href="<?php echo esc_url( hub_url( 'events/conversational-spanish/' ) ); ?>">Conversational Spanish</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg>Every Thursday, 7pm</span><span><svg class="ho-icon"><use href="#i-pin"/></svg>North Harrow</span></p>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-people"/></svg>Adults</span><span><svg class="ho-icon"><use href="#i-ticket"/></svg>&pound;90 for 10 weeks</span></p>
          <p class="card__text">A relaxed evening class for improvers. Join any week.</p>
          <p class="card__meta mt-2"><span><svg class="ho-icon"><use href="#i-store"/></svg>North Harrow Community Library</span></p>
        </div>
      </article>
  </div>

  <div class="cta-band mt-7">
    <div>
      <h2>Run a class in Harrow?</h2>
      <p>Businesses add classes on the Featured or Local Partner plan. Schools, clubs, charities and community groups add them free with an organiser account. You keep one page up to date all year, and we check it before it goes live.</p>
    </div>
    <div class="cta-band__actions">
      <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'register/' ) ); ?>"><svg class="ho-icon"><use href="#i-plus"/></svg>List your class</a>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Business plans</a>
    </div>
  </div>
</div>
<?php get_footer();

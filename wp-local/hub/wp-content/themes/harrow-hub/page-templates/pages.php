<?php
/**
 * All pages (design preview) (converted from src/pages/pages.html).
 * URL: /pages/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'description' => 'Index of every page in the Harrow Online Hub front-end design preview.',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'All pages (design preview)',
) );
get_header();
?>
<!-- All pages (redesign, 18 Nov 2026): grouped cards with the current URLs and example pages. -->
<header class="page-head ho-container">
  <h1 class="page-head__title">All pages</h1>
  <p class="page-head__lead">Every page in the Harrow Online Hub preview, grouped by what people use it for. Each line shows the address it will have on harrowonline.org/hub (news site pages are marked).</p>
</header>

<div class="ho-container pidx">
  <nav class="pidx-jump chips" aria-label="Jump to a group"><a class="chip" href="#find">Find local businesses</a><a class="chip" href="#biz">Business pages</a><a class="chip" href="#whatson">Offers, events and classes</a><a class="chip" href="#biz-owners">For businesses</a><a class="chip" href="#account">Account</a><a class="chip" href="#info">Information</a><a class="chip" href="#news">News site mock-ups</a></nav>

  <div class="pidx-key" aria-label="Labels">
    <span><span class="pidx-tag pidx-tag--verified">Verified</span>Verified by the business</span>
    <span><span class="pidx-tag pidx-tag--checked">Checked</span>Checked by Harrow Online</span>
    <span><span class="pidx-tag pidx-tag--imported">Imported</span>From public data</span>
    <span><span class="pidx-tag pidx-tag--real">Real business</span>or <span class="pidx-tag pidx-tag--sample">Made-up sample</span></span>
  </div>

  <section class="pidx-sec" id="find" aria-labelledby="find-t">
    <h2 class="pidx-sec__title" id="find-t"><svg class="ho-icon" aria-hidden="true"><use href="#i-search"/></svg>Find local businesses<span class="pidx-sec__n">6</span></h2>
    <ul class="pidx-list">
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub homepage</a><span class="pidx-row__desc">Search, choices, this weekend, featured businesses, offers</span></div><code class="pidx-row__url">/hub/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'search/' ) ); ?>">Search results</a><span class="pidx-row__desc">Businesses, events, classes and offers in one search</span></div><code class="pidx-row__url">/hub/search/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'search/?q=pizza' ) ); ?>">Search: an example search</a><span class="pidx-row__desc">Best match first, Verified before Checked before imported</span></div><code class="pidx-row__url">/hub/search/?q=pizza</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'categories/' ) ); ?>">All categories</a><span class="pidx-row__desc">27 categories</span></div><code class="pidx-row__url">/hub/categories/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'category/food-and-drink/' ) ); ?>">Category page (27)</a><span class="pidx-row__desc">Example: Food &amp; Drink</span></div><code class="pidx-row__url">/hub/category/{category}/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'area/pinner/' ) ); ?>">Area page (19)</a><span class="pidx-row__desc">Example: Pinner</span></div><code class="pidx-row__url">/hub/area/{area}/</code></li>
    </ul>
  </section>
  <section class="pidx-sec" id="biz" aria-labelledby="biz-t">
    <h2 class="pidx-sec__title" id="biz-t"><svg class="ho-icon" aria-hidden="true"><use href="#i-store"/></svg>Business pages<span class="pidx-sec__n">6</span></h2>
    <ul class="pidx-list">
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'business/aj-pizzas-harrow-town-centre/' ) ); ?>">AJ Pizza&rsquo;s</a><span class="pidx-tag pidx-tag--verified">Verified</span><span class="pidx-tag pidx-tag--plan">Featured</span><span class="pidx-tag pidx-tag--real">Real business</span><span class="pidx-row__desc">One of the 9 real businesses from Harrow Online</span></div><code class="pidx-row__url">/hub/business/{name-area}/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'business/kodak-fc-harrow-weald/' ) ); ?>">Kodak FC</a><span class="pidx-tag pidx-tag--verified">Verified</span><span class="pidx-tag pidx-tag--plan">Featured</span><span class="pidx-tag pidx-tag--real">Real business</span><span class="pidx-row__desc">Real business with a weekly schedule instead of opening hours</span></div><code class="pidx-row__url">/hub/business/{name-area}/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'business/hill-street-kitchen-harrow-on-the-hill/' ) ); ?>">Hill Street Kitchen</a><span class="pidx-tag pidx-tag--checked">Checked</span><span class="pidx-tag pidx-tag--plan">Featured</span><span class="pidx-tag pidx-tag--sample">Made-up sample</span><span class="pidx-row__desc">Business of the Week, offers, events, gallery</span></div><code class="pidx-row__url">/hub/business/{name-area}/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'business/stanmore-family-dental-stanmore/' ) ); ?>">Stanmore Family Dental</a><span class="pidx-tag pidx-tag--checked">Checked</span><span class="pidx-tag pidx-tag--plan">Local Partner</span><span class="pidx-tag pidx-tag--sample">Made-up sample</span><span class="pidx-row__desc">Top plan</span></div><code class="pidx-row__url">/hub/business/{name-area}/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'business/pinner-village-florist-pinner/' ) ); ?>">Pinner Village Florist</a><span class="pidx-tag pidx-tag--checked">Checked</span><span class="pidx-tag pidx-tag--plan">Basic</span><span class="pidx-tag pidx-tag--sample">Made-up sample</span><span class="pidx-row__desc">Free plan</span></div><code class="pidx-row__url">/hub/business/{name-area}/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'business/1-stop-letting-solutions-west-harrow/' ) ); ?>">1 Stop Letting Solutions</a><span class="pidx-tag pidx-tag--imported">Imported</span><span class="pidx-row__desc">Unclaimed listing from public data (2,588 of these)</span></div><code class="pidx-row__url">/hub/business/{name-area}/</code></li>
    </ul>
  </section>
  <section class="pidx-sec" id="whatson" aria-labelledby="whatson-t">
    <h2 class="pidx-sec__title" id="whatson-t"><svg class="ho-icon" aria-hidden="true"><use href="#i-calendar"/></svg>Offers, events and classes<span class="pidx-sec__n">8</span></h2>
    <ul class="pidx-list">
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'offers/' ) ); ?>">Local Offers</a><span class="pidx-row__desc">All current offers, with filters</span></div><code class="pidx-row__url">/hub/offers/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'offers/two-pizza-collection-deal-from-7-99-each/' ) ); ?>">Offer page: subscribers only</a><span class="pidx-tag pidx-tag--real">Real business</span><span class="pidx-row__desc">AJ Pizza&rsquo;s. Code shows once the reader confirms their email</span></div><code class="pidx-row__url">/hub/offers/{offer}/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'offers/2-for-1-mains-monday-to-thursday/' ) ); ?>">Offer page: open to everyone</a><span class="pidx-tag pidx-tag--sample">Made-up sample</span><span class="pidx-row__desc">Hill Street Kitchen. Code and Copy code</span></div><code class="pidx-row__url">/hub/offers/{offer}/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'events/' ) ); ?>">What&rsquo;s On: list</a><span class="pidx-row__desc">Filters, featured events, this week</span></div><code class="pidx-row__url">/hub/events/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'events/month/' ) ); ?>">What&rsquo;s On: month</a><span class="pidx-row__desc">Calendar view with the same filters</span></div><code class="pidx-row__url">/hub/events/month/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'events/harrow-christmas-lights-switch-on/' ) ); ?>">Event page</a><span class="pidx-row__desc">Example: Harrow Christmas Lights Switch-On</span></div><code class="pidx-row__url">/hub/events/{event}/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>">Classes and activities</a><span class="pidx-row__desc">Weekly classes, filter by age, area and day</span></div><code class="pidx-row__url">/hub/events/classes/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'events/saturday-kids-coding-club/' ) ); ?>">Class page</a><span class="pidx-row__desc">Example: Saturday Kids Coding Club</span></div><code class="pidx-row__url">/hub/events/{class}/</code></li>
    </ul>
  </section>
  <section class="pidx-sec" id="biz-owners" aria-labelledby="biz-owners-t">
    <h2 class="pidx-sec__title" id="biz-owners-t"><svg class="ho-icon" aria-hidden="true"><use href="#i-briefcase"/></svg>For businesses<span class="pidx-sec__n">6</span></h2>
    <ul class="pidx-list">
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Pricing</a><span class="pidx-row__desc">Plans, what the labels mean, compare plans, billing, FAQs (now includes the old For businesses page)</span></div><code class="pidx-row__url">/hub/pricing/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'add-business/' ) ); ?>">Add your business</a></div><code class="pidx-row__url">/hub/add-business/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'claim/?listing=1-stop-letting-solutions-west-harrow' ) ); ?>">Claim a business</a><span class="pidx-row__desc">Opened from an unclaimed listing</span></div><code class="pidx-row__url">/hub/claim/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'claim/submitted/' ) ); ?>">Claim submitted</a></div><code class="pidx-row__url">/hub/claim/submitted/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'report/?listing=1-stop-letting-solutions-west-harrow' ) ); ?>">Correction or removal request</a></div><code class="pidx-row__url">/hub/report/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'advertise/newsletter/' ) ); ?>">Sponsor the newsletter</a><span class="pidx-row__desc">Book a Presented by slot (Advertise in the main menu)</span></div><code class="pidx-row__url">/hub/advertise/newsletter/</code></li>
    </ul>
  </section>
  <section class="pidx-sec" id="account" aria-labelledby="account-t">
    <h2 class="pidx-sec__title" id="account-t"><svg class="ho-icon" aria-hidden="true"><use href="#i-user"/></svg>Account<span class="pidx-sec__n">14</span></h2>
    <ul class="pidx-list">
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'sign-in/' ) ); ?>">Sign in</a></div><code class="pidx-row__url">/hub/sign-in/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'register/' ) ); ?>">Create an account</a></div><code class="pidx-row__url">/hub/register/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'forgot-password/' ) ); ?>">Forgot password</a></div><code class="pidx-row__url">/hub/forgot-password/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">Dashboard</a><span class="pidx-row__desc">Sample account</span></div><code class="pidx-row__url">/hub/account/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/listings/edit/' ) ); ?>">Edit my listing</a><span class="pidx-row__desc">One listing per account</span></div><code class="pidx-row__url">/hub/account/listings/edit/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>">My offers</a></div><code class="pidx-row__url">/hub/account/offers/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/offers/edit/' ) ); ?>">Add or edit an offer</a></div><code class="pidx-row__url">/hub/account/offers/edit/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>">My events</a></div><code class="pidx-row__url">/hub/account/events/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/events/edit/' ) ); ?>">Add or edit an event</a></div><code class="pidx-row__url">/hub/account/events/edit/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>">My classes</a></div><code class="pidx-row__url">/hub/account/classes/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/classes/edit/' ) ); ?>">Add or edit a class</a></div><code class="pidx-row__url">/hub/account/classes/edit/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/newsletter/' ) ); ?>">Newsletter bookings</a><span class="pidx-row__desc">Booked newsletter sponsor slots</span></div><code class="pidx-row__url">/hub/account/newsletter/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/billing/' ) ); ?>">Plan and billing</a></div><code class="pidx-row__url">/hub/account/billing/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'account/settings/' ) ); ?>">Settings</a></div><code class="pidx-row__url">/hub/account/settings/</code></li>
    </ul>
  </section>
  <section class="pidx-sec" id="info" aria-labelledby="info-t">
    <h2 class="pidx-sec__title" id="info-t"><svg class="ho-icon" aria-hidden="true"><use href="#i-info"/></svg>Information<span class="pidx-sec__n">4</span></h2>
    <ul class="pidx-list">
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'privacy/' ) ); ?>">Privacy</a></div><code class="pidx-row__url">/hub/privacy/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'terms/' ) ); ?>">Terms</a></div><code class="pidx-row__url">/hub/terms/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'data-sources/' ) ); ?>">Data sources</a></div><code class="pidx-row__url">/hub/data-sources/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( '404.html' ) ); ?>">Page not found</a></div><code class="pidx-row__url">404</code></li>
    </ul>
  </section>
  <section class="pidx-sec" id="news" aria-labelledby="news-t">
    <h2 class="pidx-sec__title" id="news-t"><svg class="ho-icon" aria-hidden="true"><use href="#i-news"/></svg>News site mock-ups (harrowonline.org)<span class="pidx-sec__n">3</span></h2>
    <ul class="pidx-list">
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'news-site/article/' ) ); ?>">News article</a><span class="pidx-row__desc">Support box, businesses mentioned, related stories</span></div><code class="pidx-row__url">harrowonline.org/{article}/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'news-site/support/' ) ); ?>">Support Harrow Online</a></div><code class="pidx-row__url">harrowonline.org/support/</code></li>
        <li class="pidx-row"><div class="pidx-row__main"><a class="pidx-row__name" href="<?php echo esc_url( hub_url( 'news-site/advertise/' ) ); ?>">Advertise with Harrow Online</a><span class="pidx-row__desc">Includes newsletter sponsorship</span></div><code class="pidx-row__url">harrowonline.org/advertise/</code></li>
    </ul>
  </section>

</div>
<?php get_footer();

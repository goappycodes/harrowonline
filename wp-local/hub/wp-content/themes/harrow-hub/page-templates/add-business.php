<?php
/**
 * Add your business (converted from src/pages/add-business.html).
 * URL: /add-business/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-add-business',
	'description' => 'Add a missing Harrow business to Harrow Online Hub. Free to list, checked by our team before it goes live.',
	'hubnav' => 'directory',
	'robots' => 'noindex, follow',
	'title' => 'Add your business',
) );
get_header();
?>
<!-- Add a business (cleaned up, Nov 2026): search first, then five short numbered steps; optional extras folded away;
     "What happens next" beside the form. ?recommend=1 (homepage "Recommend a business") switches the wording for
     readers suggesting a business they don't own. hub.js section 5g: duplicate check, recommend mode. -->
<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><a href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Plans</a></li><li><span aria-current="page" data-add-crumb>Add a business</span></li></ol></nav>

<header class="page-head ho-container">
  <h1 class="page-head__title" data-add-title>Add your business</h1>
  <p class="page-head__lead" data-add-lead>Free to list and takes about two minutes. Our team checks it before it goes live.</p>
</header>

<div class="ho-container add-biz">
  <div class="add-biz__main">
    <form class="add-biz__check" action="<?php echo esc_url( hub_url( 'search/' ) ); ?>" method="get" role="search">
      <input type="hidden" name="type" value="businesses">
      <div>
        <p class="add-biz__check-title"><svg class="ho-icon"><use href="#i-search"/></svg>Is it already on the Hub?</p>
        <p class="add-biz__check-text">Most Harrow businesses are listed. If yours is, claim it instead: it&rsquo;s quicker.</p>
      </div>
      <div class="add-biz__check-row">
        <label class="ho-sr" for="a-find">Business name</label>
        <input class="input" id="a-find" type="search" name="q" placeholder="Search by business name" autocomplete="off">
        <button class="ho-btn ho-btn--dark" type="submit">Search</button>
      </div>
    </form>

    <form class="form form-card add-biz__form" action="<?php echo esc_url( hub_url( 'claim/submitted/' ) ); ?>" method="get" data-add-form>
      <!-- Same steps, names and order as the listing edit page (account/listings/edit), minus what Basic doesn't show
           (logo, gallery, social links). -->
      <section class="form-step" aria-labelledby="ab1">
        <h2 class="form-step__title" id="ab1"><span>1</span>The basics</h2>
        <div class="field">
          <label for="a-name">Business name</label>
          <input class="input" id="a-name" type="text" name="name" required autocomplete="organization" placeholder="For example, Station Road Barbers">
          <p class="help">The name on the sign, without the area.</p>
        </div>
        <div class="row row-2">
          <div class="field">
            <label for="a-cat">Category</label>
            <select class="select" id="a-cat" name="cat" required><option value="">Choose a category</option><option value="arts-and-entertainment">Arts &amp; Entertainment</option><option value="classes-and-activities">Classes &amp; Activities</option><option value="cleaning-laundry-and-hire">Cleaning, Laundry &amp; Hire</option><option value="construction-and-building-services">Construction &amp; Building Services</option><option value="doctors-dentists-and-clinics">Doctors, Dentists &amp; Clinics</option><option value="education-and-tuition">Education &amp; Tuition</option><option value="events-and-parties">Events, Parties &amp; Catering</option><option value="family-and-childcare">Family &amp; Childcare</option><option value="fashion-and-clothing">Fashion &amp; Clothing</option><option value="finance-and-accountants">Finance &amp; Accountants</option><option value="food-and-drink">Food &amp; Drink</option><option value="food-shops-and-groceries">Food Shops &amp; Groceries</option><option value="hair-and-beauty">Hair &amp; Beauty</option><option value="health-and-wellbeing">Health &amp; Wellbeing</option><option value="home-and-garden">Home &amp; Garden</option><option value="hotels-and-places-to-stay">Hotels &amp; Places to Stay</option><option value="legal-services">Legal Services</option><option value="motoring">Motoring</option><option value="pets">Pets &amp; Vets</option><option value="printing-and-signs">Printing &amp; Signs</option><option value="professional-services">Professional Services</option><option value="property-and-estate-agents">Property &amp; Estate Agents</option><option value="pubs-and-bars">Pubs &amp; Bars</option><option value="restaurants">Restaurants</option><option value="shops">Shops &amp; Specialists</option><option value="sport-and-fitness">Sports &amp; Fitness</option><option value="travel">Travel &amp; Transport</option></select>
          </div>
          <div class="field">
            <label for="a-type">Business type</label>
            <input class="input" id="a-type" type="text" name="business_type" required placeholder="For example, Barber shop">
          </div>
        </div>
        <details class="add-biz__more">
          <summary><svg class="ho-icon ho-icon--sm"><use href="#i-plus"/></svg>Add more categories <span class="opt">(optional)</span></summary>
          <div class="row row-2 mt-3">
            <div class="field">
              <label for="a-cat2">Second category</label>
              <select class="select" id="a-cat2" name="cat_extra[]"><option value="">None</option><option value="arts-and-entertainment">Arts &amp; Entertainment</option><option value="classes-and-activities">Classes &amp; Activities</option><option value="cleaning-laundry-and-hire">Cleaning, Laundry &amp; Hire</option><option value="construction-and-building-services">Construction &amp; Building Services</option><option value="doctors-dentists-and-clinics">Doctors, Dentists &amp; Clinics</option><option value="education-and-tuition">Education &amp; Tuition</option><option value="events-and-parties">Events, Parties &amp; Catering</option><option value="family-and-childcare">Family &amp; Childcare</option><option value="fashion-and-clothing">Fashion &amp; Clothing</option><option value="finance-and-accountants">Finance &amp; Accountants</option><option value="food-and-drink">Food &amp; Drink</option><option value="food-shops-and-groceries">Food Shops &amp; Groceries</option><option value="hair-and-beauty">Hair &amp; Beauty</option><option value="health-and-wellbeing">Health &amp; Wellbeing</option><option value="home-and-garden">Home &amp; Garden</option><option value="hotels-and-places-to-stay">Hotels &amp; Places to Stay</option><option value="legal-services">Legal Services</option><option value="motoring">Motoring</option><option value="pets">Pets &amp; Vets</option><option value="printing-and-signs">Printing &amp; Signs</option><option value="professional-services">Professional Services</option><option value="property-and-estate-agents">Property &amp; Estate Agents</option><option value="pubs-and-bars">Pubs &amp; Bars</option><option value="restaurants">Restaurants</option><option value="shops">Shops &amp; Specialists</option><option value="sport-and-fitness">Sports &amp; Fitness</option><option value="travel">Travel &amp; Transport</option></select>
            </div>
            <div class="field">
              <label for="a-cat3">Third category</label>
              <select class="select" id="a-cat3" name="cat_extra[]"><option value="">None</option><option value="arts-and-entertainment">Arts &amp; Entertainment</option><option value="classes-and-activities">Classes &amp; Activities</option><option value="cleaning-laundry-and-hire">Cleaning, Laundry &amp; Hire</option><option value="construction-and-building-services">Construction &amp; Building Services</option><option value="doctors-dentists-and-clinics">Doctors, Dentists &amp; Clinics</option><option value="education-and-tuition">Education &amp; Tuition</option><option value="events-and-parties">Events, Parties &amp; Catering</option><option value="family-and-childcare">Family &amp; Childcare</option><option value="fashion-and-clothing">Fashion &amp; Clothing</option><option value="finance-and-accountants">Finance &amp; Accountants</option><option value="food-and-drink">Food &amp; Drink</option><option value="food-shops-and-groceries">Food Shops &amp; Groceries</option><option value="hair-and-beauty">Hair &amp; Beauty</option><option value="health-and-wellbeing">Health &amp; Wellbeing</option><option value="home-and-garden">Home &amp; Garden</option><option value="hotels-and-places-to-stay">Hotels &amp; Places to Stay</option><option value="legal-services">Legal Services</option><option value="motoring">Motoring</option><option value="pets">Pets &amp; Vets</option><option value="printing-and-signs">Printing &amp; Signs</option><option value="professional-services">Professional Services</option><option value="property-and-estate-agents">Property &amp; Estate Agents</option><option value="pubs-and-bars">Pubs &amp; Bars</option><option value="restaurants">Restaurants</option><option value="shops">Shops &amp; Specialists</option><option value="sport-and-fitness">Sports &amp; Fitness</option><option value="travel">Travel &amp; Transport</option></select>
            </div>
          </div>
        </details>
        <div class="field">
          <label for="a-summary">One-line summary</label>
          <input class="input" id="a-summary" type="text" name="summary" maxlength="120" required data-counter placeholder="For example, Classic cuts and hot towel shaves, six days a week.">
          <p class="help">Shown on your card in search and category pages.</p>
        </div>
      </section>

      <section class="form-step" aria-labelledby="ab2">
        <h2 class="form-step__title" id="ab2"><span>2</span>Where you are</h2>
        <div class="field">
          <label for="a-addr">Street address</label>
          <input class="input" id="a-addr" type="text" name="address" required autocomplete="address-line1" placeholder="309 Station Road">
        </div>
        <div class="row row-2">
          <div class="field">
            <label for="a-pc">Postcode</label>
            <input class="input" id="a-pc" type="text" name="postcode" required autocomplete="postal-code" placeholder="HA1 2TA">
          </div>
          <div class="field">
            <label for="a-area">Area</label>
            <select class="select" id="a-area" name="area" required><option value="">Choose an area</option><option value="belmont">Belmont</option><option value="canons-park">Canons Park</option><option value="edgware">Edgware</option><option value="greenhill">Greenhill</option><option value="harrow-town-centre">Harrow Town Centre</option><option value="harrow-weald">Harrow Weald</option><option value="harrow-on-the-hill">Harrow on the Hill</option><option value="hatch-end">Hatch End</option><option value="headstone">Headstone</option><option value="kenton">Kenton</option><option value="north-harrow">North Harrow</option><option value="pinner">Pinner</option><option value="queensbury">Queensbury</option><option value="rayners-lane">Rayners Lane</option><option value="south-harrow">South Harrow</option><option value="stanmore">Stanmore</option><option value="sudbury-hill">Sudbury Hill and Roxeth</option><option value="wealdstone">Wealdstone</option><option value="west-harrow">West Harrow</option></select>
          </div>
        </div>
        <!-- Shown by hub.js when the name and postcode match a business already on the Hub -->
        <div class="notice notice--warn" role="status" data-add-dupe hidden>
          <svg class="ho-icon"><use href="#i-alert"/></svg>
          <div>
            <p><strong>This looks like it&rsquo;s already listed:</strong> <a href="<?php echo esc_url( hub_url( 'search/' ) ); ?>" data-add-dupe-link>see the listing</a>.</p>
            <p data-owner-only>If it&rsquo;s yours, <a href="#" data-add-dupe-claim>claim it instead</a>. If it&rsquo;s a different business, carry on.</p>
          </div>
        </div>
      </section>

      <section class="form-step" aria-labelledby="ab3">
        <h2 class="form-step__title" id="ab3"><span>3</span>Contact details</h2>
        <div class="row row-2">
          <div class="field">
            <label for="a-phone">Phone</label>
            <input class="input" id="a-phone" type="tel" name="phone" required autocomplete="tel" placeholder="020 8123 4567">
          </div>
          <div class="field">
            <label for="a-web">Website <span class="opt">(optional)</span></label>
            <input class="input" id="a-web" type="url" name="website" autocomplete="url" placeholder="https://">
          </div>
        </div>
        <div class="field" data-owner-only>
          <label for="a-email">Email for customers <span class="opt">(optional)</span></label>
          <input class="input" id="a-email" type="email" name="email" autocomplete="email" placeholder="hello@yourbusiness.co.uk">
        </div>
      </section>

      <section class="form-step" aria-labelledby="ab4">
        <h2 class="form-step__title" id="ab4"><span>4</span>About your business</h2>
        <div class="field">
          <label for="a-desc">Description</label>
          <textarea class="textarea" id="a-desc" name="description" maxlength="1500" rows="5" required data-counter aria-describedby="a-desc-help" placeholder="What you do, who for and what makes you different."></textarea>
          <p class="help" id="a-desc-help">At least 50 words helps you show up in Google. No prices or deals here: those go in Local Offers.</p>
        </div>
        <fieldset class="facilities" data-owner-only>
          <legend>Facilities <span class="opt">(tick all that apply)</span></legend>
          <ul class="check-list facilities__list">
            <li><label class="check"><input type="checkbox" name="facilities[]" value="wheelchair"> Wheelchair access</label></li>
            <li><label class="check"><input type="checkbox" name="facilities[]" value="parking"> Parking nearby</label></li>
            <li><label class="check"><input type="checkbox" name="facilities[]" value="card"> Card payments</label></li>
            <li><label class="check"><input type="checkbox" name="facilities[]" value="wifi"> Free Wi-Fi</label></li>
            <li><label class="check"><input type="checkbox" name="facilities[]" value="toilets"> Toilets</label></li>
            <li><label class="check"><input type="checkbox" name="facilities[]" value="children"> Child friendly</label></li>
            <li><label class="check"><input type="checkbox" name="facilities[]" value="booking"> Online booking</label></li>
            <li><label class="check"><input type="checkbox" name="facilities[]" value="walkin"> Walk-ins welcome</label></li>
            <li><label class="check"><input type="checkbox" name="facilities[]" value="delivery"> Delivery</label></li>
            <li><label class="check"><input type="checkbox" name="facilities[]" value="outdoor"> Outdoor seating</label></li>
          </ul>
        </fieldset>
      </section>

      <section class="form-step" aria-labelledby="ab5" data-owner-only>
        <h2 class="form-step__title" id="ab5"><span>5</span>Opening hours <small class="form-step__opt">(optional)</small></h2>
        <div class="hours-edit">
            <span class="day">Monday</span><input class="input input--sm" type="time" name="hours[mon][open]" value="09:00" aria-label="Monday opening time"><input class="input input--sm" type="time" name="hours[mon][close]" value="17:30" aria-label="Monday closing time"><label class="check"><input type="checkbox" name="hours[mon][closed]" value="1"> Closed</label>
            <span class="day">Tuesday</span><input class="input input--sm" type="time" name="hours[tue][open]" value="09:00" aria-label="Tuesday opening time"><input class="input input--sm" type="time" name="hours[tue][close]" value="17:30" aria-label="Tuesday closing time"><label class="check"><input type="checkbox" name="hours[tue][closed]" value="1"> Closed</label>
            <span class="day">Wednesday</span><input class="input input--sm" type="time" name="hours[wed][open]" value="09:00" aria-label="Wednesday opening time"><input class="input input--sm" type="time" name="hours[wed][close]" value="17:30" aria-label="Wednesday closing time"><label class="check"><input type="checkbox" name="hours[wed][closed]" value="1"> Closed</label>
            <span class="day">Thursday</span><input class="input input--sm" type="time" name="hours[thu][open]" value="09:00" aria-label="Thursday opening time"><input class="input input--sm" type="time" name="hours[thu][close]" value="17:30" aria-label="Thursday closing time"><label class="check"><input type="checkbox" name="hours[thu][closed]" value="1"> Closed</label>
            <span class="day">Friday</span><input class="input input--sm" type="time" name="hours[fri][open]" value="09:00" aria-label="Friday opening time"><input class="input input--sm" type="time" name="hours[fri][close]" value="17:30" aria-label="Friday closing time"><label class="check"><input type="checkbox" name="hours[fri][closed]" value="1"> Closed</label>
            <span class="day">Saturday</span><input class="input input--sm" type="time" name="hours[sat][open]" value="10:00" aria-label="Saturday opening time"><input class="input input--sm" type="time" name="hours[sat][close]" value="16:00" aria-label="Saturday closing time"><label class="check"><input type="checkbox" name="hours[sat][closed]" value="1"> Closed</label>
            <span class="day">Sunday</span><input class="input input--sm" type="time" name="hours[sun][open]" aria-label="Sunday opening time"><input class="input input--sm" type="time" name="hours[sun][close]" aria-label="Sunday closing time"><label class="check"><input type="checkbox" name="hours[sun][closed]" value="1" checked> Closed</label>
          </div>
        <p class="help">Tick Closed for days you don&rsquo;t open. Leave them out if they vary.</p>
      </section>

      <section class="form-step" aria-labelledby="ab6" data-owner-only>
        <h2 class="form-step__title" id="ab6"><span>6</span>Photo <small class="form-step__opt">(optional)</small></h2>
        <label class="dropzone dropzone--compact" for="a-photo">
          <svg class="ho-icon"><use href="#i-upload"/></svg>
          <span><strong>Add a photo</strong><small>Shop front, team or your work. JPG or PNG, up to 8MB.</small></span>
          <input class="ho-sr" id="a-photo" type="file" name="photo" accept=".jpg,.jpeg,.png">
        </label>
        <p class="help">The free Basic listing shows one photo. A logo, more photos and social links come with Featured; add them from your account after you upgrade.</p>
      </section>

      <div class="add-biz__submit">
        <label class="check">
          <input type="checkbox" name="declaration" value="1" required>
          <span data-add-declare>I own or manage this business, or have permission to list it, and I agree to the <a href="<?php echo esc_url( hub_url( 'terms/' ) ); ?>">terms</a>.</span>
        </label>
        <div class="add-biz__actions">
          <button class="ho-btn ho-btn--primary" type="submit" name="save" value="pending" data-add-submit>Send for checking</button>
          <a class="ho-btn ho-btn--ghost" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Cancel</a>
        </div>
      </div>
    </form>
  </div>

  <aside class="add-biz__side" aria-label="What happens next">
    <div class="add-biz__panel">
      <h2 class="add-biz__panel-title">What happens next</h2>
      <ol class="add-biz__next">
        <li><span>1</span><div><strong>We check it</strong><small>Usually within 2 working days.</small></div></li>
        <li><span>2</span><div><strong>We email you</strong><small>Whether it&rsquo;s approved or we need something.</small></div></li>
        <li data-owner-only><span>3</span><div><strong>It goes live, free</strong><small>Manage it from your account on the Basic plan.</small></div></li>
        <li data-recommend-only hidden><span>3</span><div><strong>It goes live</strong><small>The owner can claim it free at any time.</small></div></li>
      </ol>
      <p class="add-biz__panel-note" data-owner-only>Want offers, events and classes? <a href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Upgrade any time</a>.</p>
    </div>
  </aside>
</div>
<?php get_footer();

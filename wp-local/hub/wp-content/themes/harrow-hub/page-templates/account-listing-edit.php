<?php
/**
 * Edit listing (converted from src/pages/account-listing-edit.html).
 * URL: /account/listings/edit/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Edit your Harrow Online Hub business listing: the basics, location, contact details, description, facilities, opening hours, logo, photos and social links.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'Edit listing',
) );
get_header();
?>
<div class="ho-container dash">
  <aside class="dash-nav" aria-label="Account">
    <div class="dash-nav__who">
      <?php hub_view_account_who(); ?>
    </div>
    <div class="dash-add">
      <!-- "+ Add" on every account page (client plan, Nov 2026): an event, a class or an offer, one tap from anywhere. -->
      <details class="add-menu" data-add-menu>
        <summary class="ho-btn ho-btn--primary ho-btn--sm"><svg class="ho-icon"><use href="#i-plus"/></svg>Add</summary>
        <div class="add-menu__pop">
          <p class="add-menu__label">Add to the Hub</p>
          <a class="add-menu__item" href="<?php echo esc_url( hub_url( 'account/events/edit/' ) ); ?>"><span class="add-menu__icon"><svg class="ho-icon"><use href="#i-calendar"/></svg></span><span><strong>Event</strong><small>A one-off date, on What&rsquo;s On</small></span></a>
          <a class="add-menu__item" href="<?php echo esc_url( hub_url( 'account/classes/edit/' ) ); ?>"><span class="add-menu__icon"><svg class="ho-icon"><use href="#i-repeat"/></svg></span><span><strong>Class</strong><small>Runs every week, on Classes</small></span></a>
          <a class="add-menu__item" href="<?php echo esc_url( hub_url( 'account/offers/edit/' ) ); ?>"><span class="add-menu__icon"><svg class="ho-icon"><use href="#i-tag"/></svg></span><span><strong>Offer</strong><small>A deal for Harrow Online readers</small></span></a>
        </div>
      </details>
    </div>

    <ul>
      <li><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>"><svg class="ho-icon"><use href="#i-home"/></svg>Overview</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/listings/edit/' ) ); ?>" aria-current="page"><svg class="ho-icon"><use href="#i-store"/></svg>My listing</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>"><svg class="ho-icon"><use href="#i-tag"/></svg>Offers<span class="num">1<span class="ho-sr"> offer needs changes</span></span></a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>"><svg class="ho-icon"><use href="#i-calendar"/></svg>Events</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>"><svg class="ho-icon"><use href="#i-repeat"/></svg>Classes</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/newsletter/' ) ); ?>"><svg class="ho-icon"><use href="#i-megaphone"/></svg>Newsletter and promotions</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/billing/' ) ); ?>"><svg class="ho-icon"><use href="#i-card"/></svg>Plan and billing</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/settings/' ) ); ?>"><svg class="ho-icon"><use href="#i-settings"/></svg>Settings</a></li>
    </ul>
    <hr>
    <ul>
      <li><a href="<?php echo esc_url( hub_url( 'sign-in/' ) ); ?>"><svg class="ho-icon"><use href="#i-logout"/></svg>Sign out</a></li>
    </ul>
  </aside>

  <div class="dash-main">
    <nav class="crumbs mb-4" aria-label="Breadcrumb">
      <ol><li><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">My account</a></li><li><span aria-current="page">My listing</span></li></ol>
    </nav>
    <div class="dash-head">
      <div>
        <h1>Edit your listing</h1>
        <p>Station Road Barbers &middot; <span class="badge badge--dark">Featured</span> <span class="badge badge--verified"><svg class="ho-icon"><use href="#i-verified"/></svg>Verified by the business</span></p>
      </div>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'business/station-road-barbers-harrow-town-centre/' ) ); ?>"><svg class="ho-icon"><use href="#i-external"/></svg>View listing</a>
    </div>

    <!-- One account = one listing for now ("My listing" opens this page). Later an account may hold several listings, but only one of them can be on a paid Featured plan.
         Listing edit (cleaned up, Nov 2026): seven short steps; live card preview and a checklist beside the form.
         WP: the checklist's done states and the Google line come from the listing (index rule from brief 03). -->
    <div class="layout-aside offer-form listing-form">
      <form class="form-card form" action="#" method="post" data-listing-form>

        <section class="form-step" id="basics" aria-labelledby="ls1">
          <h2 class="form-step__title" id="ls1"><span>1</span>The basics</h2>
          <div class="field">
            <label for="le-name">Business name</label>
            <input class="input" id="le-name" type="text" name="name" value="Station Road Barbers" required data-lp-in="name">
            <p class="help">A new name is checked by our team first. The current one stays live until then.</p>
          </div>
          <div class="row row-2">
            <div class="field">
              <label for="le-cat">Category</label>
              <!-- WP: render the category list with the listing's current category selected -->
              <select class="select" id="le-cat" name="category" data-lp-in="cat">
                <option value="hair-and-beauty" selected>Hair &amp; Beauty</option>
                <option value="arts-and-entertainment">Arts &amp; Entertainment</option><option value="classes-and-activities">Classes &amp; Activities</option><option value="cleaning-laundry-and-hire">Cleaning, Laundry &amp; Hire</option><option value="construction-and-building-services">Construction &amp; Building Services</option><option value="doctors-dentists-and-clinics">Doctors, Dentists &amp; Clinics</option><option value="education-and-tuition">Education &amp; Tuition</option><option value="events-and-parties">Events, Parties &amp; Catering</option><option value="family-and-childcare">Family &amp; Childcare</option><option value="fashion-and-clothing">Fashion &amp; Clothing</option><option value="finance-and-accountants">Finance &amp; Accountants</option><option value="food-and-drink">Food &amp; Drink</option><option value="food-shops-and-groceries">Food Shops &amp; Groceries</option><option value="hair-and-beauty">Hair &amp; Beauty</option><option value="health-and-wellbeing">Health &amp; Wellbeing</option><option value="home-and-garden">Home &amp; Garden</option><option value="hotels-and-places-to-stay">Hotels &amp; Places to Stay</option><option value="legal-services">Legal Services</option><option value="motoring">Motoring</option><option value="pets">Pets &amp; Vets</option><option value="printing-and-signs">Printing &amp; Signs</option><option value="professional-services">Professional Services</option><option value="property-and-estate-agents">Property &amp; Estate Agents</option><option value="pubs-and-bars">Pubs &amp; Bars</option><option value="restaurants">Restaurants</option><option value="shops">Shops &amp; Specialists</option><option value="sport-and-fitness">Sports &amp; Fitness</option><option value="travel">Travel &amp; Transport</option>
              </select>
            </div>
            <div class="field">
              <label for="le-type">Business type</label>
              <input class="input" id="le-type" type="text" name="business_type" value="Barber shop" data-lp-in="type" placeholder="For example, Barber shop">
            </div>
          </div>
          <details class="add-biz__more">
            <summary><svg class="ho-icon ho-icon--sm"><use href="#i-plus"/></svg>Add more categories <span class="opt">(optional)</span></summary>
            <div class="row row-2 mt-3">
              <div class="field">
                <label for="le-cat2">Second category</label>
                <select class="select" id="le-cat2" name="category_extra[]"><option value="">None</option><option value="arts-and-entertainment">Arts &amp; Entertainment</option><option value="classes-and-activities">Classes &amp; Activities</option><option value="cleaning-laundry-and-hire">Cleaning, Laundry &amp; Hire</option><option value="construction-and-building-services">Construction &amp; Building Services</option><option value="doctors-dentists-and-clinics">Doctors, Dentists &amp; Clinics</option><option value="education-and-tuition">Education &amp; Tuition</option><option value="events-and-parties">Events, Parties &amp; Catering</option><option value="family-and-childcare">Family &amp; Childcare</option><option value="fashion-and-clothing">Fashion &amp; Clothing</option><option value="finance-and-accountants">Finance &amp; Accountants</option><option value="food-and-drink">Food &amp; Drink</option><option value="food-shops-and-groceries">Food Shops &amp; Groceries</option><option value="hair-and-beauty">Hair &amp; Beauty</option><option value="health-and-wellbeing">Health &amp; Wellbeing</option><option value="home-and-garden">Home &amp; Garden</option><option value="hotels-and-places-to-stay">Hotels &amp; Places to Stay</option><option value="legal-services">Legal Services</option><option value="motoring">Motoring</option><option value="pets">Pets &amp; Vets</option><option value="printing-and-signs">Printing &amp; Signs</option><option value="professional-services">Professional Services</option><option value="property-and-estate-agents">Property &amp; Estate Agents</option><option value="pubs-and-bars">Pubs &amp; Bars</option><option value="restaurants">Restaurants</option><option value="shops">Shops &amp; Specialists</option><option value="sport-and-fitness">Sports &amp; Fitness</option><option value="travel">Travel &amp; Transport</option></select>
              </div>
              <div class="field">
                <label for="le-cat3">Third category</label>
                <select class="select" id="le-cat3" name="category_extra[]"><option value="">None</option><option value="arts-and-entertainment">Arts &amp; Entertainment</option><option value="classes-and-activities">Classes &amp; Activities</option><option value="cleaning-laundry-and-hire">Cleaning, Laundry &amp; Hire</option><option value="construction-and-building-services">Construction &amp; Building Services</option><option value="doctors-dentists-and-clinics">Doctors, Dentists &amp; Clinics</option><option value="education-and-tuition">Education &amp; Tuition</option><option value="events-and-parties">Events, Parties &amp; Catering</option><option value="family-and-childcare">Family &amp; Childcare</option><option value="fashion-and-clothing">Fashion &amp; Clothing</option><option value="finance-and-accountants">Finance &amp; Accountants</option><option value="food-and-drink">Food &amp; Drink</option><option value="food-shops-and-groceries">Food Shops &amp; Groceries</option><option value="hair-and-beauty">Hair &amp; Beauty</option><option value="health-and-wellbeing">Health &amp; Wellbeing</option><option value="home-and-garden">Home &amp; Garden</option><option value="hotels-and-places-to-stay">Hotels &amp; Places to Stay</option><option value="legal-services">Legal Services</option><option value="motoring">Motoring</option><option value="pets">Pets &amp; Vets</option><option value="printing-and-signs">Printing &amp; Signs</option><option value="professional-services">Professional Services</option><option value="property-and-estate-agents">Property &amp; Estate Agents</option><option value="pubs-and-bars">Pubs &amp; Bars</option><option value="restaurants">Restaurants</option><option value="shops">Shops &amp; Specialists</option><option value="sport-and-fitness">Sports &amp; Fitness</option><option value="travel">Travel &amp; Transport</option></select>
              </div>
            </div>
          </details>
          <div class="field">
            <label for="le-summary">One-line summary</label>
            <input class="input" id="le-summary" type="text" name="summary" maxlength="120" data-counter value="Classic cuts, skin fades and hot towel shaves, six days a week." data-lp-in="summary">
            <p class="help">Shown on your card in search and category pages.</p>
          </div>
        </section>

        <section class="form-step" id="location" aria-labelledby="ls2">
          <h2 class="form-step__title" id="ls2"><span>2</span>Where you are</h2>
          <div class="field">
            <label for="le-ad1">Street address</label>
            <input class="input" id="le-ad1" type="text" name="address_1" value="48 Station Road" autocomplete="address-line1" required>
          </div>
          <div class="row row-2">
            <div class="field">
              <label for="le-pc">Postcode</label>
              <input class="input" id="le-pc" type="text" name="postcode" value="HA1 2RS" autocomplete="postal-code" required>
            </div>
            <div class="field">
              <label for="le-area">Area</label>
              <input class="input" id="le-area" type="text" value="Harrow Town Centre" readonly aria-describedby="le-area-help">
              <p class="help" id="le-area-help">Set from your postcode. <a href="<?php echo esc_url( hub_url( 'report/' ) ); ?>">Wrong?</a></p>
            </div>
          </div>
          <input type="hidden" name="town" value="Harrow">
        </section>

        <section class="form-step" id="contact" aria-labelledby="ls3">
          <h2 class="form-step__title" id="ls3"><span>3</span>Contact details</h2>
          <div class="row row-2">
            <div class="field">
              <label for="le-phone">Phone</label>
              <input class="input" id="le-phone" type="tel" name="phone" value="020 8427 0000" autocomplete="tel">
            </div>
            <div class="field">
              <label for="le-web">Website <span class="opt">(optional)</span></label>
              <input class="input" id="le-web" type="url" name="website" value="https://stationroadbarbers.co.uk" placeholder="https://">
            </div>
          </div>
          <div class="field">
            <label for="le-email">Email for customers <span class="opt">(optional)</span></label>
            <input class="input" id="le-email" type="email" name="email" value="bookings@stationroadbarbers.co.uk">
            <p class="help">Shown on your listing. Account emails go to the address in <a href="<?php echo esc_url( hub_url( 'account/settings/' ) ); ?>">Settings</a>.</p>
          </div>
        </section>

        <section class="form-step" id="about" aria-labelledby="ls4">
          <h2 class="form-step__title" id="ls4"><span>4</span>About your business</h2>
          <div class="field">
            <label for="le-desc">Description</label>
            <textarea class="textarea" id="le-desc" name="description" rows="6" maxlength="1500" data-counter aria-describedby="le-desc-help">Family-run barbers on Station Road since 2009. We do classic cuts, skin fades, hot towel shaves and beard trims, with walk-ins welcome six days a week and online booking for evenings. Kids are welcome, and we offer a quieter chair for anyone who finds busy shops hard. Three experienced barbers, a fresh coffee while you wait, and five minutes from Harrow-on-the-Hill station.</textarea>
            <p class="help" id="le-desc-help">What you do, who for and what makes you different. At least 50 words helps you show up in Google.</p>
          </div>
          <fieldset class="facilities">
            <legend>Facilities <span class="opt">(tick all that apply)</span></legend>
            <ul class="check-list facilities__list">
              <li><label class="check"><input type="checkbox" name="facilities[]" value="wheelchair" checked> Wheelchair access</label></li>
              <li><label class="check"><input type="checkbox" name="facilities[]" value="parking"> Parking nearby</label></li>
              <li><label class="check"><input type="checkbox" name="facilities[]" value="card" checked> Card payments</label></li>
              <li><label class="check"><input type="checkbox" name="facilities[]" value="wifi" checked> Free Wi-Fi</label></li>
              <li><label class="check"><input type="checkbox" name="facilities[]" value="toilets"> Toilets</label></li>
              <li><label class="check"><input type="checkbox" name="facilities[]" value="children" checked> Child friendly</label></li>
              <li><label class="check"><input type="checkbox" name="facilities[]" value="booking" checked> Online booking</label></li>
              <li><label class="check"><input type="checkbox" name="facilities[]" value="walkin" checked> Walk-ins welcome</label></li>
              <li><label class="check"><input type="checkbox" name="facilities[]" value="delivery"> Delivery</label></li>
              <li><label class="check"><input type="checkbox" name="facilities[]" value="outdoor"> Outdoor seating</label></li>
            </ul>
          </fieldset>
        </section>

        <section class="form-step" id="hours" aria-labelledby="ls5">
          <h2 class="form-step__title" id="ls5"><span>5</span>Opening hours</h2>
          <div class="hours-edit">
          <span class="day">Monday</span>
          <label class="ho-sr" for="h-mon-o">Monday opens</label><input class="input input--sm" id="h-mon-o" type="time" name="hours[mon][open]" value="09:00">
          <label class="ho-sr" for="h-mon-c">Monday closes</label><input class="input input--sm" id="h-mon-c" type="time" name="hours[mon][close]" value="19:00">
          <label class="check"><input type="checkbox" name="hours[mon][closed]" value="1"> Closed</label>

          <span class="day">Tuesday</span>
          <label class="ho-sr" for="h-tue-o">Tuesday opens</label><input class="input input--sm" id="h-tue-o" type="time" name="hours[tue][open]" value="09:00">
          <label class="ho-sr" for="h-tue-c">Tuesday closes</label><input class="input input--sm" id="h-tue-c" type="time" name="hours[tue][close]" value="19:00">
          <label class="check"><input type="checkbox" name="hours[tue][closed]" value="1"> Closed</label>

          <span class="day">Wednesday</span>
          <label class="ho-sr" for="h-wed-o">Wednesday opens</label><input class="input input--sm" id="h-wed-o" type="time" name="hours[wed][open]" value="09:00">
          <label class="ho-sr" for="h-wed-c">Wednesday closes</label><input class="input input--sm" id="h-wed-c" type="time" name="hours[wed][close]" value="19:00">
          <label class="check"><input type="checkbox" name="hours[wed][closed]" value="1"> Closed</label>

          <span class="day">Thursday</span>
          <label class="ho-sr" for="h-thu-o">Thursday opens</label><input class="input input--sm" id="h-thu-o" type="time" name="hours[thu][open]" value="09:00">
          <label class="ho-sr" for="h-thu-c">Thursday closes</label><input class="input input--sm" id="h-thu-c" type="time" name="hours[thu][close]" value="20:00">
          <label class="check"><input type="checkbox" name="hours[thu][closed]" value="1"> Closed</label>

          <span class="day">Friday</span>
          <label class="ho-sr" for="h-fri-o">Friday opens</label><input class="input input--sm" id="h-fri-o" type="time" name="hours[fri][open]" value="09:00">
          <label class="ho-sr" for="h-fri-c">Friday closes</label><input class="input input--sm" id="h-fri-c" type="time" name="hours[fri][close]" value="20:00">
          <label class="check"><input type="checkbox" name="hours[fri][closed]" value="1"> Closed</label>

          <span class="day">Saturday</span>
          <label class="ho-sr" for="h-sat-o">Saturday opens</label><input class="input input--sm" id="h-sat-o" type="time" name="hours[sat][open]" value="08:30">
          <label class="ho-sr" for="h-sat-c">Saturday closes</label><input class="input input--sm" id="h-sat-c" type="time" name="hours[sat][close]" value="18:00">
          <label class="check"><input type="checkbox" name="hours[sat][closed]" value="1"> Closed</label>

          <span class="day">Sunday</span>
          <label class="ho-sr" for="h-sun-o">Sunday opens</label><input class="input input--sm" id="h-sun-o" type="time" name="hours[sun][open]" disabled>
          <label class="ho-sr" for="h-sun-c">Sunday closes</label><input class="input input--sm" id="h-sun-c" type="time" name="hours[sun][close]" disabled>
          <label class="check"><input type="checkbox" name="hours[sun][closed]" value="1" checked> Closed</label>
        </div>
          <p class="help">Tick Closed for days you don&rsquo;t open. Today&rsquo;s hours are highlighted on your listing.</p>
        </section>

        <section class="form-step" id="photos" aria-labelledby="ls6">
          <h2 class="form-step__title" id="ls6"><span>6</span>Logo and photos</h2>
          <div class="logo-row">
            <div class="logo-row__img" aria-hidden="true"><svg class="ho-icon"><use href="#i-scissors"/></svg></div>
            <label class="dropzone dropzone--compact" for="le-logo">
              <svg class="ho-icon"><use href="#i-upload"/></svg>
              <span><strong>Logo or profile image</strong><small>Featured and Local Partner. Square, at least 400 &times; 400 pixels.</small></span>
              <input class="ho-sr" id="le-logo" type="file" name="logo" accept="image/jpeg,image/png,image/svg+xml">
            </label>
          </div>
          <label class="dropzone dropzone--compact" for="le-photos">
            <svg class="ho-icon"><use href="#i-upload"/></svg>
            <span><strong>Add gallery photos</strong><small>JPG or PNG, up to 8MB each. The first photo is your main image.</small></span>
            <input class="ho-sr" id="le-photos" type="file" name="photos[]" accept="image/jpeg,image/png" multiple>
          </label>
          <div class="thumbs">
            <div class="thumb"><div class="ph"><svg class="ho-icon"><use href="#i-scissors"/></svg></div><button type="button" aria-label="Remove photo 1"><svg class="ho-icon"><use href="#i-close"/></svg></button></div>
            <div class="thumb"><div class="ph"><svg class="ho-icon"><use href="#i-scissors"/></svg></div><button type="button" aria-label="Remove photo 2"><svg class="ho-icon"><use href="#i-close"/></svg></button></div>
            <div class="thumb"><div class="ph"><svg class="ho-icon"><use href="#i-store"/></svg></div><button type="button" aria-label="Remove photo 3"><svg class="ho-icon"><use href="#i-close"/></svg></button></div>
            <div class="thumb thumb--empty"><div class="ph"><svg class="ho-icon"><use href="#i-plus"/></svg></div><span class="ho-sr">Empty photo slot</span></div>
            <div class="thumb thumb--empty"><div class="ph"><svg class="ho-icon"><use href="#i-plus"/></svg></div><span class="ho-sr">Empty photo slot</span></div>
          </div>
          <p class="help">3 photos added. Basic listings show one photo; Featured and Local Partner show them all. If your plan ends, the extra photos are hidden, not deleted.</p>
        </section>

        <section class="form-step" id="social" aria-labelledby="ls7">
          <h2 class="form-step__title" id="ls7"><span>7</span>Social links <small class="form-step__opt">Featured and Local Partner</small></h2>
          <div class="row row-2">
            <div class="field">
              <label for="le-fb">Facebook</label>
              <div class="input-group"><span class="input-group__addon">facebook.com/</span><input class="input" id="le-fb" type="text" name="social[facebook]" value="stationroadbarbers"></div>
            </div>
            <div class="field">
              <label for="le-ig">Instagram</label>
              <div class="input-group"><span class="input-group__addon">instagram.com/</span><input class="input" id="le-ig" type="text" name="social[instagram]" value="stationroadbarbers"></div>
            </div>
          </div>
          <details class="add-biz__more">
            <summary><svg class="ho-icon ho-icon--sm"><use href="#i-plus"/></svg>Add TikTok or X <span class="opt">(optional)</span></summary>
            <div class="row row-2 mt-3">
              <div class="field">
                <label for="le-tt">TikTok</label>
                <div class="input-group"><span class="input-group__addon">tiktok.com/@</span><input class="input" id="le-tt" type="text" name="social[tiktok]"></div>
              </div>
              <div class="field">
                <label for="le-x">X</label>
                <div class="input-group"><span class="input-group__addon">x.com/</span><input class="input" id="le-x" type="text" name="social[x]"></div>
              </div>
            </div>
          </details>
        </section>

        <div class="form-actions form-actions--between">
          <p class="help mb-0">Changes go live straight away. A new name is checked first.</p>
          <div class="cluster">
            <a class="ho-btn ho-btn--ghost" href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">Cancel</a>
            <button class="ho-btn ho-btn--primary" type="submit"><svg class="ho-icon"><use href="#i-check"/></svg>Save changes</button>
          </div>
        </div>
      </form>

      <aside class="sticky offer-form__aside listing-form__aside" aria-label="Your listing">
        <p class="eyebrow">Preview</p>
        <div class="lp-card" aria-live="polite">
          <span class="lp-card__logo" aria-hidden="true"><svg class="ho-icon"><use href="#i-scissors"/></svg></span>
          <div>
            <span class="biz-card__cat"><span data-lp-out="cat">Hair &amp; Beauty</span> &middot; <span class="muted" data-lp-out="type">Barber shop</span></span>
            <strong class="lp-card__name" data-lp-out="name">Station Road Barbers</strong>
            <span class="lp-card__summary" data-lp-out="summary">Classic cuts, skin fades and hot towel shaves, six days a week.</span>
            <span class="lp-card__where"><svg class="ho-icon ho-icon--sm"><use href="#i-pin"/></svg>48 Station Road, HA1 2RS</span>
          </div>
        </div>
        <p class="help mt-3">How your card looks in search and category pages.</p>

        <div class="lp-check">
          <h2 class="lp-check__title">Your listing <span class="lp-check__score">7 of 7 done</span></h2>
          <ul>
            <li class="is-done"><a href="#basics">Name, type and summary</a></li>
            <li class="is-done"><a href="#about">Description of 50+ words</a></li>
            <li class="is-done"><a href="#hours">Opening hours</a></li>
            <li class="is-done"><a href="#contact">Phone and website</a></li>
            <li class="is-done"><a href="#photos">Logo and photos</a></li>
            <li class="is-done"><a href="#about">Facilities</a></li>
            <li class="is-done"><a href="#social">Social links</a></li>
          </ul>
          <p class="lp-check__google"><svg class="ho-icon ho-icon--sm"><use href="#i-check"/></svg>Complete enough to show in Google.</p>
        </div>
      </aside>
    </div>
  </div>
</div>
<?php get_footer();

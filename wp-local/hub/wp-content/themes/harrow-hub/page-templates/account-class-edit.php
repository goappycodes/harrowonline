<?php
/**
 * Edit class (converted from src/pages/account-class-edit.html).
 * URL: /account/classes/edit/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Add or edit a weekly class on Harrow Online Hub: day and time, term dates, venue, ages, price and booking.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'Edit class',
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
      <li><a href="<?php echo esc_url( hub_url( 'account/listings/edit/' ) ); ?>"><svg class="ho-icon"><use href="#i-store"/></svg>My listing</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>"><svg class="ho-icon"><use href="#i-tag"/></svg>Offers<span class="num">1<span class="ho-sr"> offer needs changes</span></span></a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>"><svg class="ho-icon"><use href="#i-calendar"/></svg>Events</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>" aria-current="true"><svg class="ho-icon"><use href="#i-repeat"/></svg>Classes</a></li>
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
      <ol><li><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">My account</a></li><li><a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>">Classes</a></li><li><span aria-current="page">Barbering taster class</span></li></ol>
    </nav>
    <div class="dash-head">
      <div>
        <h1>Your class</h1>
        <p>Barbering taster class &middot; <span class="status status--published">Published</span></p>
      </div>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'events/classes/' ) ); ?>"><svg class="ho-icon"><use href="#i-external"/></svg>View on Classes</a>
    </div>

    <div class="notice notice--warn mb-5">
      <svg class="ho-icon"><use href="#i-info"/></svg>
      <div>
        <p><strong>Edits to an approved class may need approval again.</strong> Your class stays live as it is while we check the changes.</p>
      </div>
    </div>
    <!-- OPEN QUESTION (brief 01, 6.9 and 10): should edits to an approved class need re-approval? Copy above assumes yes, with the live version kept until approved. -->

    <!-- Class form (client plan, Nov 2026): its own form, separate from one-off events. Day and time, term, ages, price and booking. -->
    <div class="layout-aside offer-form">
      <form class="form-card form" action="#" method="post" data-event-form data-kind="class">
        <input type="hidden" name="organiser_listing" value="station-road-barbers">
        <input type="hidden" name="recurrence" value="weekly">

        <section class="form-step" aria-labelledby="cs1">
          <h2 class="form-step__title" id="cs1"><span>1</span>What is it?</h2>
          <div class="field">
            <label for="ev-title">Class name</label>
            <input class="input" id="ev-title" type="text" name="title" maxlength="80" value="Barbering taster class" required data-preview="title">
          </div>
          <div class="field">
            <label for="ev-desc">Short description</label>
            <textarea class="textarea" id="ev-desc" name="description" rows="3" maxlength="600">Learn the basics of clipper work, fading and beard shaping with our senior barbers. All tools provided.</textarea>
          </div>
          <div class="field">
            <span class="field__label" id="cl-type-label">Type of class</span>
            <div class="choice-row" role="radiogroup" aria-labelledby="cl-type-label">
              <label class="choice choice--pill"><input type="radio" name="category" value="childrens-activities" data-preview="cat"><span>Children&rsquo;s activities</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="dance" data-preview="cat"><span>Dance schools</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="fitness" data-preview="cat"><span>Fitness</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="workshops" checked data-preview="cat"><span>Workshops</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="other" data-preview="cat"><span>Other regular classes</span></label>
            </div>
          </div>
          <label class="dropzone dropzone--compact" for="ev-img">
            <svg class="ho-icon"><use href="#i-upload"/></svg>
            <span><strong>Add a photo</strong> <span class="muted">(optional) JPG or PNG, up to 5MB</span></span>
            <input class="ho-sr" id="ev-img" type="file" name="image" accept="image/jpeg,image/png">
          </label>
        </section>

        <section class="form-step" aria-labelledby="cs2">
          <h2 class="form-step__title" id="cs2"><span>2</span>When does it run?</h2>
          <div class="row row-3 slot">
            <div class="field"><label for="sl1-day">Every</label>
              <select class="select" id="sl1-day" name="slots[0][day]" data-preview="day"><option>Monday</option><option selected>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option><option>Sunday</option></select></div>
            <div class="field"><label for="sl1-s">From</label><input class="input" id="sl1-s" type="time" name="slots[0][start]" value="18:30" data-preview="time"></div>
            <div class="field"><label for="sl1-e">To</label><input class="input" id="sl1-e" type="time" name="slots[0][end]" value="20:00"></div>
          </div>
          <p class="help">Runs on more than one day? Add the other days in the description and we&rsquo;ll set them up when we check your class.</p>

          <div class="field mt-4">
            <span class="field__label" id="cl-term-label">How long does it run?</span>
            <div class="choice-row" role="radiogroup" aria-labelledby="cl-term-label">
              <label class="choice choice--pill"><input type="radio" name="term" value="ongoing"><span>All year round</span></label>
              <label class="choice choice--pill"><input type="radio" name="term" value="term" checked data-reveal="#cl-term"><span>For a term</span></label>
            </div>
          </div>
          <div id="cl-term">
            <div class="row row-3">
              <div class="field"><label for="cl-from">Term starts</label><input class="input" id="cl-from" type="date" name="term_start" value="2026-09-08"></div>
              <div class="field"><label for="cl-until">Term ends</label><input class="input" id="cl-until" type="date" name="term_end" value="2026-12-15"></div>
              <div class="field"><label for="cl-weeks">Or number of weeks</label><input class="input" id="cl-weeks" type="number" name="term_weeks" min="1" max="52" placeholder="e.g. 10"></div>
            </div>
            <label class="check mt-2"><input type="checkbox" name="no_half_term" value="1" checked> No class in half term</label>
          </div>
        </section>

        <section class="form-step" aria-labelledby="cs3">
          <h2 class="form-step__title" id="cs3"><span>3</span>Where?</h2>
          <div class="choice-row">
            <label class="choice choice--pill"><input type="radio" name="venue_is_listing" value="1" checked data-preview="where"><span><svg class="ho-icon ho-icon--sm"><use href="#i-store"/></svg>At my business</span></label>
            <label class="choice choice--pill"><input type="radio" name="venue_is_listing" value="0" data-reveal="#ev-venue-box" data-preview="where"><span><svg class="ho-icon ho-icon--sm"><use href="#i-pin"/></svg>Somewhere else</span></label>
          </div>
          <p class="help mb-0" data-venue-home>Station Road Barbers, 48 Station Road, Harrow HA1 2RS</p>
          <div id="ev-venue-box" hidden>
            <div class="row row-2">
              <div class="field"><label for="ev-venue">Venue name</label><input class="input" id="ev-venue" type="text" name="venue"></div>
              <div class="field"><label for="ev-pc">Postcode</label><input class="input" id="ev-pc" type="text" name="venue_postcode"></div>
            </div>
          </div>
        </section>

        <section class="form-step" aria-labelledby="cs4">
          <h2 class="form-step__title" id="cs4"><span>4</span>Who is it for?</h2>
          <div class="row row-2">
            <div class="field">
              <label for="ev-age">Ages</label>
              <select class="select" id="ev-age" name="age" data-preview="age"><option>All ages</option><option>Under 5s</option><option>Ages 5 to 11</option><option>Ages 12 to 17</option><option selected>Ages 16+</option><option>Adults</option><option>Over 60s</option></select>
            </div>
            <div class="field">
              <label for="cl-level">Level <span class="opt">(optional)</span></label>
              <select class="select" id="cl-level" name="level"><option>All levels</option><option selected>Beginners</option><option>Improvers</option><option>Advanced</option></select>
            </div>
          </div>
        </section>

        <section class="form-step" aria-labelledby="cs5">
          <h2 class="form-step__title" id="cs5"><span>5</span>Price and booking</h2>
          <div class="row row-2">
            <div class="field">
              <label for="ev-price">Price <span class="opt">(leave blank if free)</span></label>
              <input class="input" id="ev-price" type="text" name="price" value="&pound;25" placeholder="e.g. &pound;8" data-preview="price">
            </div>
            <div class="field">
              <label for="cl-per">Per</label>
              <select class="select" id="cl-per" name="price_per" data-preview="per"><option value="session" selected>Session</option><option value="term">Term</option><option value="month">Month</option></select>
            </div>
          </div>
          <label class="check"><input type="checkbox" name="free_trial" value="1" data-preview="trial"> First class free (a free trial)</label>
          <div class="field mt-4">
            <span class="field__label" id="cl-book-label">Booking</span>
            <div class="choice-row" role="radiogroup" aria-labelledby="cl-book-label">
              <label class="choice choice--pill"><input type="radio" name="booking" value="turn-up"><span>No booking needed, just turn up</span></label>
              <label class="choice choice--pill"><input type="radio" name="booking" value="book" checked data-reveal="#cl-book"><span>Book in advance</span></label>
            </div>
          </div>
          <div class="field" id="cl-book">
            <label for="ev-link">Booking link or phone number</label>
            <input class="input" id="ev-link" type="text" name="link" placeholder="https:// or 020 &hellip;">
          </div>
        </section>

        <div class="form-actions form-actions--between">
          <button class="ho-btn ho-btn--outline" type="submit" name="action" value="draft">Save draft</button>
          <button class="ho-btn ho-btn--primary" type="submit" name="action" value="submit"><svg class="ho-icon"><use href="#i-check"/></svg>Send for approval</button>
        </div>
      </form>

      <aside class="sticky offer-form__aside" aria-label="Preview">
        <p class="eyebrow">Preview</p>
        <article class="card event-card event-preview" aria-live="polite">
          <div class="card__badges"><span class="badge badge--outline"><svg class="ho-icon"><use href="#i-repeat"/></svg>Weekly class</span></div>
          <div class="ph"><svg class="ho-icon"><use href="#i-scissors"/></svg></div>
          <div class="card__body">
            <span class="biz-card__cat" data-ev-out="cat">Workshops</span>
            <h3 class="card__title" data-ev-out="title">Barbering taster class</h3>
            <p class="card__meta"><span><svg class="ho-icon"><use href="#i-repeat"/></svg><span data-ev-out="when">Every Tuesday, 6.30pm</span></span><span><svg class="ho-icon"><use href="#i-pin"/></svg><span data-ev-out="where">Harrow Town Centre</span></span></p>
            <p class="card__meta"><span><svg class="ho-icon"><use href="#i-ticket"/></svg><span data-ev-out="price">&pound;25 a session</span></span><span><svg class="ho-icon"><use href="#i-people"/></svg><span data-ev-out="age">Ages 16+</span></span></p>
          </div>
        </article>
        <p class="help mt-3">How it looks on Classes. We check every class before it goes live, usually within 2 working days.</p>

        <div class="feature-box">
          <div class="feature-box__head">
            <span class="feature-box__icon"><svg class="ho-icon"><use href="#i-star"/></svg></span>
            <div><h2>Feature this</h2><p>Shown first on Classes and What&rsquo;s On, and at the top of The Weekend newsletter.</p></div>
          </div>
          <div class="feature-box__buy">
            <p><strong>&pound;15</strong> for 7 days</p>
            <a class="ho-btn ho-btn--dark ho-btn--sm" href="#stripe-checkout">Feature it</a>
          </div>
          <p class="help mb-0"><span class="badge badge--featured">Featured until 16 Nov</span> Featuring again adds 7 more days.</p>
        </div>
        <!-- WP: #stripe-checkout = one-off Checkout Session for "Featured class, 7 days" (brief 02 N7). -->
      </aside>
    </div>
  </div>
</div>
<?php get_footer();

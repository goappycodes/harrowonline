<?php
/**
 * Edit event (converted from src/pages/account-event-edit.html).
 * URL: /account/events/edit/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Add or edit a one-off event on Harrow Online Hub: date, time, venue, price, booking link, photo and age range.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'Edit event',
) );
get_header();
?>
<div class="ho-container dash">
  <aside class="dash-nav" aria-label="Account">
    <div class="dash-nav__who">
      <span class="mono mono--sm" aria-hidden="true">SR</span>
      <div><strong>Station Road Barbers</strong><span>Featured plan</span></div>
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
      <li><a href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>" aria-current="true"><svg class="ho-icon"><use href="#i-calendar"/></svg>Events</a></li>
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
      <ol><li><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">My account</a></li><li><a href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>">Events</a></li><li><span aria-current="page">Christmas charity cut-a-thon</span></li></ol>
    </nav>
    <div class="dash-head">
      <div>
        <h1>Your event</h1>
        <p>Christmas charity cut-a-thon &middot; <span class="status status--pending">Being checked</span></p>
      </div>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'events/' ) ); ?>"><svg class="ho-icon"><use href="#i-external"/></svg>View What&rsquo;s On</a>
    </div>

    <div class="notice notice--plain mb-5">
      <svg class="ho-icon"><use href="#i-repeat"/></svg>
      <div>
        <p><strong>Runs every week?</strong> <a href="<?php echo esc_url( hub_url( 'account/classes/edit/' ) ); ?>">Add it as a class</a> instead. Classes have their own form, with the day, time and term.</p>
      </div>
    </div>

    <!-- Event form (client plan, Nov 2026): one-off events only. Weekly classes have their own form (account-class-edit). -->
    <div class="layout-aside offer-form">
      <form class="form-card form" action="#" method="post" data-event-form>
        <input type="hidden" name="organiser_listing" value="station-road-barbers">

        <section class="form-step" aria-labelledby="es1">
          <h2 class="form-step__title" id="es1"><span>1</span>What is it?</h2>
          <div class="field">
            <label for="ev-title">Title</label>
            <input class="input" id="ev-title" type="text" name="title" maxlength="80" value="Christmas charity cut-a-thon" required data-preview="title">
          </div>
          <div class="field">
            <label for="ev-desc">Short description</label>
            <textarea class="textarea" id="ev-desc" name="description" rows="3" maxlength="600">Our barbers cut hair all day for Harrow Food Bank. Walk in, pay what you like and every penny goes to the charity.</textarea>
          </div>
          <label class="dropzone dropzone--compact" for="ev-img">
            <svg class="ho-icon"><use href="#i-upload"/></svg>
            <span><strong>Add a photo</strong> <span class="muted">(optional) JPG or PNG, up to 5MB</span></span>
            <input class="ho-sr" id="ev-img" type="file" name="image" accept="image/jpeg,image/png">
          </label>
        </section>

        <section class="form-step" aria-labelledby="es2">
          <h2 class="form-step__title" id="es2"><span>2</span>What kind of event?</h2>
          <div class="field">
            <span class="field__label" id="ev-cat-label">Category</span>
            <div class="choice-row" role="radiogroup" aria-labelledby="ev-cat-label">
              <label class="choice choice--pill"><input type="radio" name="category" value="family" data-preview="cat"><span>Family</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="music" data-preview="cat"><span>Music</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="food-and-drink" data-preview="cat"><span>Food and drink</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="community" checked data-preview="cat"><span>Community</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="nightlife" data-preview="cat"><span>Nightlife</span></label>
            </div>
          </div>
        </section>

        <section class="form-step" aria-labelledby="es3">
          <h2 class="form-step__title" id="es3"><span>3</span>When?</h2>
          <div class="row row-3">
            <div class="field"><label for="ev-sd">Date</label><input class="input" id="ev-sd" type="date" name="start_date" value="2026-12-13" data-preview="date"></div>
            <div class="field"><label for="ev-st">Starts</label><input class="input" id="ev-st" type="time" name="start_time" value="10:00" data-preview="time"></div>
            <div class="field"><label for="ev-et">Ends <span class="opt">(optional)</span></label><input class="input" id="ev-et" type="time" name="end_time" value="16:00"></div>
          </div>
          <p class="help mb-0">On for more than one day? Add the other dates in the description and we&rsquo;ll set them up when we check your event.</p>
        </section>

        <section class="form-step" aria-labelledby="es4">
          <h2 class="form-step__title" id="es4"><span>4</span>Where?</h2>
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

        <section class="form-step" aria-labelledby="es5">
          <h2 class="form-step__title" id="es5"><span>5</span>Who and how much?</h2>
          <div class="row row-2">
            <div class="field">
              <label for="ev-age">Who is it for?</label>
              <select class="select" id="ev-age" name="age" data-preview="age"><option selected>All ages</option><option>Under 5s</option><option>Ages 5 to 11</option><option>Ages 12 to 17</option><option>Ages 16+</option><option>Adults</option><option>Over 60s</option></select>
            </div>
            <div class="field">
              <label for="ev-price">Price <span class="opt">(leave blank if free)</span></label>
              <input class="input" id="ev-price" type="text" name="price" value="Pay what you like" placeholder="e.g. &pound;10 or &pound;5 for children" data-preview="price">
            </div>
          </div>
          <div class="field">
            <label for="ev-link">Booking or info link <span class="opt">(optional)</span></label>
            <input class="input" id="ev-link" type="url" name="link" placeholder="https://">
          </div>
        </section>

        <!-- WP: shown only to businesses without Featured / Local Partner; "Send for approval" then opens a one-off Stripe Checkout (£15 event listing). -->
        <p class="help oneoff-help"><svg class="ho-icon ho-icon--sm"><use href="#i-info"/></svg><span>On the free Basic plan? Listing this event costs <strong>&pound;15</strong>, paid at the next step. Featured and Local Partner include events.</span></p>
        <div class="form-actions form-actions--between">
          <button class="ho-btn ho-btn--outline" type="submit" name="action" value="draft">Save draft</button>
          <button class="ho-btn ho-btn--primary" type="submit" name="action" value="submit"><svg class="ho-icon"><use href="#i-check"/></svg>Send for approval</button>
        </div>
      </form>

      <aside class="sticky offer-form__aside" aria-label="Preview">
        <p class="eyebrow">Preview</p>
        <article class="card event-card event-preview" aria-live="polite">
          <div class="ph"><svg class="ho-icon"><use href="#i-scissors"/></svg></div>
          <div class="card__body">
            <span class="biz-card__cat" data-ev-out="cat">Community</span>
            <h3 class="card__title" data-ev-out="title">Christmas charity cut-a-thon</h3>
            <p class="card__meta"><span><svg class="ho-icon"><use href="#i-clock"/></svg><span data-ev-out="when">Sun 13 Dec, 10am</span></span><span><svg class="ho-icon"><use href="#i-pin"/></svg><span data-ev-out="where">Harrow Town Centre</span></span></p>
            <p class="card__meta"><span><svg class="ho-icon"><use href="#i-ticket"/></svg><span data-ev-out="price">Pay what you like</span></span><span><svg class="ho-icon"><use href="#i-people"/></svg><span data-ev-out="age">All ages</span></span></p>
          </div>
        </article>
        <p class="help mt-3">How it looks on What&rsquo;s On. We check every event before it goes live, usually within 2 working days.</p>

        <div class="feature-box">
          <div class="feature-box__head">
            <span class="feature-box__icon"><svg class="ho-icon"><use href="#i-star"/></svg></span>
            <div><h2>Feature this</h2><p>Shown first on What&rsquo;s On, and at the top of The Weekend newsletter.</p></div>
          </div>
          <div class="feature-box__buy">
            <p><strong>&pound;15</strong> for 7 days</p>
            <a class="ho-btn ho-btn--dark ho-btn--sm" href="#stripe-checkout">Feature it</a>
          </div>
          <p class="help mb-0">Pay now; the 7 days start when the event is approved and live.</p>
        </div>
        <!-- WP: #stripe-checkout = one-off Checkout Session for "Featured event, 7 days" (brief 02 N7). -->
      </aside>
    </div>
  </div>
</div>
<?php get_footer();

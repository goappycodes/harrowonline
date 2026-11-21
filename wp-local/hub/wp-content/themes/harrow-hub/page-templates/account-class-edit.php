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
<?php $hub_page = hub_submission_page( 'class', $_GET ); $hub_result = hub_submission_result(); // phpcs:ignore WordPress.Security.NonceVerification ?>
<div class="ho-container dash">
  <?php hub_view_account_nav( 'classes', 'true' ); ?>

  <div class="dash-main">
    <nav class="crumbs mb-4" aria-label="Breadcrumb">
      <ol><li><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">My account</a></li><li><a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>">Classes</a></li><li><span aria-current="page"><?php hub_view_submission_crumb( $hub_page ); ?></span></li></ol>
    </nav>
    <?php hub_view_submission_head( $hub_page ); ?>


    <!-- Class form (client plan, Nov 2026): its own form, separate from one-off events. Day and time, term, ages, price and booking. -->
    <?php hub_view_submission_notices( $hub_page, $hub_result ); ?>
    <div class="layout-aside offer-form">
      <?php if ( 'ready' === $hub_page['state'] ) : ?>
      <form class="form-card form" action="<?php echo esc_url( hub_view_submission_action( $hub_page ) ); ?>" method="post" enctype="multipart/form-data" data-event-form data-kind="class">
        <?php wp_nonce_field( 'hub_submission', '_hub_nonce' ); ?>
        <?php if ( $hub_page['item'] ) : ?><input type="hidden" name="id" value="<?php echo (int) $hub_page['item']['id']; ?>"><?php endif; ?>
        <input type="hidden" name="recurrence" value="weekly">

        <section class="form-step" aria-labelledby="cs1">
          <h2 class="form-step__title" id="cs1"><span>1</span>What is it?</h2>
          <div class="field">
            <label for="ev-title">Class name</label>
            <input class="input" id="ev-title" type="text" name="title" maxlength="80" required data-preview="title" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'title' ) ); ?>">
          </div>
          <div class="field">
            <label for="ev-desc">Short description</label>
            <textarea class="textarea" id="ev-desc" name="description" rows="3" maxlength="600"><?php echo esc_textarea( hub_view_submission_value( $hub_page, 'description' ) ); ?></textarea>
          </div>
          <div class="field">
            <span class="field__label" id="cl-type-label">Type of class</span>
            <div class="choice-row" role="radiogroup" aria-labelledby="cl-type-label">
              <label class="choice choice--pill"><input type="radio" name="category" value="childrens-activities" data-preview="cat" <?php checked( hub_view_submission_value( $hub_page, 'category' ), 'childrens-activities' ); ?>><span>Children&rsquo;s activities</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="dance" data-preview="cat" <?php checked( hub_view_submission_value( $hub_page, 'category' ), 'dance' ); ?>><span>Dance schools</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="fitness" data-preview="cat" <?php checked( hub_view_submission_value( $hub_page, 'category' ), 'fitness' ); ?>><span>Fitness</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="workshops" data-preview="cat" <?php checked( hub_view_submission_value( $hub_page, 'category' ), 'workshops' ); ?>><span>Workshops</span></label>
              <label class="choice choice--pill"><input type="radio" name="category" value="other" data-preview="cat" <?php checked( hub_view_submission_value( $hub_page, 'category' ), 'other' ); ?>><span>Other regular classes</span></label>
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
              <select class="select" id="sl1-day" name="slots[0][day]" data-preview="day"><option <?php selected( hub_view_submission_value( $hub_page, 'slots[0][day]' ), 'Monday' ); ?>>Monday</option><option <?php selected( hub_view_submission_value( $hub_page, 'slots[0][day]' ), 'Tuesday' ); ?>>Tuesday</option><option <?php selected( hub_view_submission_value( $hub_page, 'slots[0][day]' ), 'Wednesday' ); ?>>Wednesday</option><option <?php selected( hub_view_submission_value( $hub_page, 'slots[0][day]' ), 'Thursday' ); ?>>Thursday</option><option <?php selected( hub_view_submission_value( $hub_page, 'slots[0][day]' ), 'Friday' ); ?>>Friday</option><option <?php selected( hub_view_submission_value( $hub_page, 'slots[0][day]' ), 'Saturday' ); ?>>Saturday</option><option <?php selected( hub_view_submission_value( $hub_page, 'slots[0][day]' ), 'Sunday' ); ?>>Sunday</option></select></div>
            <div class="field"><label for="sl1-s">From</label><input class="input" id="sl1-s" type="time" name="slots[0][start]" data-preview="time" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'slots[0][start]' ) ); ?>"></div>
            <div class="field"><label for="sl1-e">To</label><input class="input" id="sl1-e" type="time" name="slots[0][end]" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'slots[0][end]' ) ); ?>"></div>
          </div>
          <p class="help">Runs on more than one day? Add the other days in the description and we&rsquo;ll set them up when we check your class.</p>

          <div class="field mt-4">
            <span class="field__label" id="cl-term-label">How long does it run?</span>
            <div class="choice-row" role="radiogroup" aria-labelledby="cl-term-label">
              <label class="choice choice--pill"><input type="radio" name="term_type" value="ongoing" <?php checked( hub_view_submission_value( $hub_page, 'term_type' ), 'ongoing' ); ?>><span>All year round</span></label>
              <label class="choice choice--pill"><input type="radio" name="term_type" value="term" data-reveal="#cl-term" <?php checked( hub_view_submission_value( $hub_page, 'term_type' ), 'term' ); ?>><span>For a term</span></label>
            </div>
          </div>
          <div id="cl-term">
            <div class="row row-3">
              <div class="field"><label for="cl-from">Term starts</label><input class="input" id="cl-from" type="date" name="term_start" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'term_start' ) ); ?>"></div>
              <div class="field"><label for="cl-until">Term ends</label><input class="input" id="cl-until" type="date" name="term_end" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'term_end' ) ); ?>"></div>
              <div class="field"><label for="cl-weeks">Or number of weeks</label><input class="input" id="cl-weeks" type="number" name="term_weeks" min="1" max="52" placeholder="e.g. 10" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'term_weeks' ) ); ?>"></div>
            </div>
            <label class="check mt-2"><input type="checkbox" name="no_half_term" value="1" <?php checked( hub_view_submission_value( $hub_page, 'no_half_term' ), '1' ); ?>> No class in half term</label>
          </div>
        </section>

        <section class="form-step" aria-labelledby="cs3">
          <h2 class="form-step__title" id="cs3"><span>3</span>Where?</h2>
          <div class="choice-row">
            <label class="choice choice--pill"><input type="radio" name="venue_is_listing" value="1" data-preview="where" <?php checked( hub_view_submission_value( $hub_page, 'venue_is_listing' ), '1' ); ?> <?php disabled( ! $hub_page['business'] ); ?>><span><svg class="ho-icon ho-icon--sm"><use href="#i-store"/></svg>At my business</span></label>
            <label class="choice choice--pill"><input type="radio" name="venue_is_listing" value="0" data-reveal="#ev-venue-box" data-preview="where" <?php checked( hub_view_submission_value( $hub_page, 'venue_is_listing' ), '0' ); ?>><span><svg class="ho-icon ho-icon--sm"><use href="#i-pin"/></svg>Somewhere else</span></label>
          </div>
          <p class="help mb-0" data-venue-home><?php echo esc_html( $hub_page['venue_home'] ); ?></p>
          <div id="ev-venue-box" hidden>
            <div class="row row-2">
              <div class="field"><label for="ev-venue">Venue name</label><input class="input" id="ev-venue" type="text" name="venue" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'venue' ) ); ?>"></div>
              <div class="field"><label for="ev-pc">Postcode</label><input class="input" id="ev-pc" type="text" name="venue_postcode" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'venue_postcode' ) ); ?>"></div>
            </div>
          </div>
        </section>

        <section class="form-step" aria-labelledby="cs4">
          <h2 class="form-step__title" id="cs4"><span>4</span>Who is it for?</h2>
          <div class="row row-2">
            <div class="field">
              <label for="ev-age">Ages</label>
              <select class="select" id="ev-age" name="age" data-preview="age"><option <?php selected( hub_view_submission_value( $hub_page, 'age' ), 'All ages' ); ?>>All ages</option><option <?php selected( hub_view_submission_value( $hub_page, 'age' ), 'Under 5s' ); ?>>Under 5s</option><option <?php selected( hub_view_submission_value( $hub_page, 'age' ), 'Ages 5 to 11' ); ?>>Ages 5 to 11</option><option <?php selected( hub_view_submission_value( $hub_page, 'age' ), 'Ages 12 to 17' ); ?>>Ages 12 to 17</option><option <?php selected( hub_view_submission_value( $hub_page, 'age' ), 'Ages 16+' ); ?>>Ages 16+</option><option <?php selected( hub_view_submission_value( $hub_page, 'age' ), 'Adults' ); ?>>Adults</option><option <?php selected( hub_view_submission_value( $hub_page, 'age' ), 'Over 60s' ); ?>>Over 60s</option></select>
            </div>
            <div class="field">
              <label for="cl-level">Level <span class="opt">(optional)</span></label>
              <select class="select" id="cl-level" name="level"><option <?php selected( hub_view_submission_value( $hub_page, 'level' ), 'All levels' ); ?>>All levels</option><option <?php selected( hub_view_submission_value( $hub_page, 'level' ), 'Beginners' ); ?>>Beginners</option><option <?php selected( hub_view_submission_value( $hub_page, 'level' ), 'Improvers' ); ?>>Improvers</option><option <?php selected( hub_view_submission_value( $hub_page, 'level' ), 'Advanced' ); ?>>Advanced</option></select>
            </div>
          </div>
        </section>

        <section class="form-step" aria-labelledby="cs5">
          <h2 class="form-step__title" id="cs5"><span>5</span>Price and booking</h2>
          <div class="row row-2">
            <div class="field">
              <label for="ev-price">Price <span class="opt">(leave blank if free)</span></label>
              <input class="input" id="ev-price" type="text" name="price" placeholder="e.g. &pound;8" data-preview="price" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'price' ) ); ?>">
            </div>
            <div class="field">
              <label for="cl-per">Per</label>
              <select class="select" id="cl-per" name="price_per" data-preview="per"><option value="session" <?php selected( hub_view_submission_value( $hub_page, 'price_per' ), 'session' ); ?>>Session</option><option value="term" <?php selected( hub_view_submission_value( $hub_page, 'price_per' ), 'term' ); ?>>Term</option><option value="month" <?php selected( hub_view_submission_value( $hub_page, 'price_per' ), 'month' ); ?>>Month</option></select>
            </div>
          </div>
          <label class="check"><input type="checkbox" name="free_trial" value="1" data-preview="trial" <?php checked( hub_view_submission_value( $hub_page, 'free_trial' ), '1' ); ?>> First class free (a free trial)</label>
          <div class="field mt-4">
            <span class="field__label" id="cl-book-label">Booking</span>
            <div class="choice-row" role="radiogroup" aria-labelledby="cl-book-label">
              <label class="choice choice--pill"><input type="radio" name="booking" value="turn-up" <?php checked( hub_view_submission_value( $hub_page, 'booking' ), 'turn-up' ); ?>><span>No booking needed, just turn up</span></label>
              <label class="choice choice--pill"><input type="radio" name="booking" value="book" data-reveal="#cl-book" <?php checked( hub_view_submission_value( $hub_page, 'booking' ), 'book' ); ?>><span>Book in advance</span></label>
            </div>
          </div>
          <div class="field" id="cl-book">
            <label for="ev-link">Booking link or phone number</label>
            <input class="input" id="ev-link" type="text" name="link" placeholder="https:// or 020 &hellip;" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'link' ) ); ?>">
          </div>
        </section>

        <div class="form-actions form-actions--between">
          <button class="ho-btn ho-btn--outline" type="submit" name="action" value="draft">Save draft</button>
          <button class="ho-btn ho-btn--primary" type="submit" name="action" value="submit"><svg class="ho-icon"><use href="#i-check"/></svg>Send for approval</button>
        </div>
      </form>
      <?php endif; ?>

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

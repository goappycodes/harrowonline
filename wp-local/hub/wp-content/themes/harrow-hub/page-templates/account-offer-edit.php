<?php
/**
 * Add an offer (converted from src/pages/account-offer-edit.html).
 * URL: /account/offers/edit/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Add a Local Offer to Harrow Online Hub: title, end date, redemption code, terms, subscriber-only option and newsletter feature.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'Add an offer',
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
      <li><a href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>" aria-current="true"><svg class="ho-icon"><use href="#i-tag"/></svg>Offers<span class="num">1<span class="ho-sr"> offer needs changes</span></span></a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>"><svg class="ho-icon"><use href="#i-calendar"/></svg>Events</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>"><svg class="ho-icon"><use href="#i-repeat"/></svg>Classes</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/newsletter/' ) ); ?>"><svg class="ho-icon"><use href="#i-megaphone"/></svg>Newsletter and promotions</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/billing/' ) ); ?>"><svg class="ho-icon"><use href="#i-card"/></svg>Plan and billing</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/settings/' ) ); ?>"><svg class="ho-icon"><use href="#i-settings"/></svg>Settings</a></li>
    </ul>
    <hr>
    <ul>
      <li><a href="<?php echo esc_url( hub_view_sign_out_url() ); ?>"><svg class="ho-icon"><use href="#i-logout"/></svg>Sign out</a></li>
    </ul>
  </aside>

  <div class="dash-main">
    <nav class="crumbs mb-4" aria-label="Breadcrumb">
      <ol><li><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">My account</a></li><li><a href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>">Offers</a></li><li><span aria-current="page">Add an offer</span></li></ol>
    </nav>
    <div class="dash-head">
      <div>
        <h1>Your offer</h1>
        <p>For Station Road Barbers. We check every offer before it goes live, usually within 2 working days.</p>
      </div>
    </div>

    <!-- Shown when the owner opens an offer our team sent back (?changes=1 in the preview; in WordPress: offer status "changes requested") -->
    <div class="notice notice--warn mb-5" data-offer-changes hidden>
      <svg class="ho-icon"><use href="#i-alert"/></svg>
      <div>
        <p><strong>Changes needed: &ldquo;Kids&rsquo; cuts &pound;10 on weekdays&rdquo;</strong></p>
        <p>Note from our team: Please add the days and times this applies, and the age limit, so customers know what to expect. Update the offer below, then send it again.</p>
      </div>
    </div>

    <!-- Simplified offer form (client feedback, Nov 2026): four short steps and a live preview. -->
    <div class="layout-aside offer-form">
      <form class="form-card form" action="#" method="post" data-offer-form>
        <input type="hidden" name="listing" value="station-road-barbers">

        <section class="form-step" aria-labelledby="st1">
          <h2 class="form-step__title" id="st1"><span>1</span>What&rsquo;s the offer?</h2>
          <div class="field">
            <label for="of-title">Headline</label>
            <input class="input" id="of-title" type="text" name="title" maxlength="70" value="Free beard trim with any haircut" required aria-describedby="of-title-help" data-preview="title">
            <p class="help" id="of-title-help">Lead with the deal, like &ldquo;20% off your first visit&rdquo;.</p>
          </div>
          <div class="field">
            <label for="of-desc">A line about it <span class="opt">(optional)</span></label>
            <input class="input" id="of-desc" type="text" name="description" maxlength="140" value="Book any haircut in December and we&rsquo;ll tidy up your beard for free.">
          </div>
        </section>

        <section class="form-step" aria-labelledby="st2">
          <h2 class="form-step__title" id="st2"><span>2</span>When does it run?</h2>
          <div class="row row-2">
            <div class="field">
              <label for="of-start">From</label>
              <input class="input" id="of-start" type="date" name="start" value="2026-12-01" required>
            </div>
            <div class="field">
              <label for="of-end">Until</label>
              <input class="input" id="of-end" type="date" name="end" value="2026-12-31" required data-preview="ends">
            </div>
          </div>
          <p class="help mb-0">It comes off the Hub automatically after the last day.</p>
        </section>

        <section class="form-step" aria-labelledby="st3">
          <h2 class="form-step__title" id="st3"><span>3</span>How do people claim it?</h2>
          <div class="row row-2">
            <div class="field">
              <label for="of-code">Code <span class="opt">(optional)</span></label>
              <input class="input" id="of-code" type="text" name="code" maxlength="20" placeholder="e.g. BEARD24" data-case="upper" data-preview="code">
            </div>
            <div class="field">
              <label for="of-redeem">How to claim</label>
              <input class="input" id="of-redeem" type="text" name="redeem" maxlength="100" value="Mention Harrow Online when you book." required>
            </div>
          </div>
          <div class="field">
            <label for="of-terms">Terms <span class="opt">(one per line)</span></label>
            <textarea class="textarea" id="of-terms" name="terms" rows="3" maxlength="500" required>December only, Monday to Friday.
One per customer.</textarea>
          </div>
        </section>

        <section class="form-step" aria-labelledby="st4">
          <h2 class="form-step__title" id="st4"><span>4</span>Who can see it?</h2>
          <div class="choice-row">
            <label class="choice choice--pill"><input type="radio" name="audience" value="everyone" checked data-preview="audience"><span>Everyone</span></label>
            <label class="choice choice--pill"><input type="radio" name="audience" value="subscribers" data-preview="audience"><span><svg class="ho-icon ho-icon--sm"><use href="#i-lock"/></svg>Newsletter subscribers</span></label>
          </div>
          <p class="help mb-0">Subscriber offers keep the code hidden until a reader confirms their email.</p>
        </section>

        <div class="form-actions form-actions--between">
          <button class="ho-btn ho-btn--outline" type="submit" name="action" value="draft">Save draft</button>
          <button class="ho-btn ho-btn--primary" type="submit" name="action" value="submit"><svg class="ho-icon"><use href="#i-check"/></svg>Send for approval</button>
        </div>
      </form>

      <aside class="sticky offer-form__aside" aria-label="Preview">
        <p class="eyebrow">Preview</p>
        <div class="offer-card offer-card--preview" aria-live="polite">
          <span class="offer-card__top"><span class="biz-card__cat">Hair &amp; Beauty</span><span class="card__tick card__tick--sub" data-preview-out="sub" hidden role="img" aria-label="Subscribers only" data-tip="Subscribers only"><svg class="ho-icon"><use href="#i-lock"/></svg></span></span>
          <strong class="offer-card__value" data-preview-out="value">FREE BEARD</strong>
          <span class="offer-card__title" data-preview-out="title">Free beard trim with any haircut</span>
          <span class="offer-card__biz">Station Road Barbers &middot; Harrow Town Centre</span>
          <span class="offer-card__foot"><span><svg class="ho-icon ho-icon--sm"><use href="#i-calendar"/></svg><span data-preview-out="ends">Ends 31 Dec</span></span><span data-preview-out="code">No code needed</span></span>
        </div>
        <p class="help mt-3">How it looks on Local Offers. We check every offer before it goes live, usually within 2 working days.</p>

        <div class="feature-box">
          <div class="feature-box__head">
            <span class="feature-box__icon"><svg class="ho-icon"><use href="#i-star"/></svg></span>
            <div><h2>Feature this offer</h2><p>Shown first on Local Offers and in the newsletter, with a Featured label.</p></div>
          </div>
          <div class="feature-box__buy">
            <p><strong>&pound;15</strong> for 7 days</p>
            <a class="ho-btn ho-btn--dark ho-btn--sm" href="#stripe-checkout">Feature it</a>
          </div>
          <p class="help mb-0">Pay now; the 7 days start when the offer is approved and live.</p>
        </div>
        <!-- WP: #stripe-checkout = one-off Checkout Session (mode=payment) for the "Featured offer, 7 days" price; brief 02 N7 -->
      </aside>
    </div>
  </div>
</div>
<?php get_footer();

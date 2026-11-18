<?php
/**
 * Claim your business (converted from src/pages/claim.html).
 * URL: /claim/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-claim',
	'description' => 'Claim your free business listing on Harrow Online Hub. Prove you own it and manage your details, hours and photos.',
	'hubnav' => 'directory',
	'robots' => 'noindex, follow',
	'title' => 'Claim your business',
) );
get_header();
?>
<!-- Claim (redesign, Nov 2026): numbered steps, the business being claimed at the top, the three ways to prove ownership
     as side-by-side cards (the chosen one's details open below), then About you. One side card: Basic benefits,
     upgrades and help. Every field, option and note from the previous version is kept. -->
<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><a href="<?php echo esc_url( hub_url( 'search/' ) ); ?>">Businesses</a></li><li><span aria-current="page">Claim a business</span></li></ol></nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">Claim your business</h1>
  <p class="page-head__lead">Show us you own or manage this business and it&rsquo;s yours to update, free. It takes about two minutes.</p>
</header>

<div class="ho-container">
  <ol class="sp-steps sp-steps--4" aria-label="Claim steps">
    <li class="is-done"><span class="sp-steps__n"><svg class="ho-icon ho-icon--sm"><use href="#i-check"/></svg><span class="ho-sr">Done:</span></span><span><small>Step 1</small>Sign in</span></li>
    <li class="is-current" aria-current="step"><span class="sp-steps__n">2</span><span><small>Step 2</small>Prove ownership</span></li>
    <li><span class="sp-steps__n">3</span><span><small>Step 3</small>Admin check</span></li>
    <li><span class="sp-steps__n">4</span><span><small>Step 4</small>Done</span></li>
  </ol>

  <div class="layout-aside claim-layout">
    <form class="form form-card claim-form" action="<?php echo esc_url( hub_url( 'claim/submitted/' ) ); ?>" method="get">
      <section class="claim-biz" aria-labelledby="claiming-title">
        <span class="claim-biz__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-store"/></svg></span>
        <div class="claim-biz__main">
          <h2 class="claim-biz__label" id="claiming-title">You&rsquo;re claiming</h2>
          <span class="biz-card__cat" data-listing-field="cat">Restaurants</span>
          <h3 class="claim-biz__name"><a href="<?php echo esc_url( hub_url( 'business/hill-street-kitchen-harrow-on-the-hill/' ) ); ?>" data-listing-field="link"><span data-listing-field="n">Hill Street Kitchen</span></a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg><span data-listing-field="ad">14 High Street</span>, <span data-listing-field="pc">HA1 3LE</span></span><span data-listing-field="area">Harrow on the Hill</span></p>
        </div>
        <p class="claim-biz__again">Not the right business? <a href="<?php echo esc_url( hub_url( 'search/' ) ); ?>">Search again</a></p>
      </section>

      <section class="form-step" aria-labelledby="method-title">
        <h2 class="form-step__title" id="method-title"><span>1</span>How can we check it&rsquo;s yours?</h2>
        <p class="help mt-0">Pick the quickest option for you. We list them fastest first.</p>
        <fieldset class="fieldset claim-methods">
          <legend class="ho-sr">How can we check it&rsquo;s yours?</legend>
          <div class="claim-methods__list">
            <label class="choice claim-method">
              <input type="radio" name="method" value="email" data-reveal="#m-email" checked>
              <span>
                <span class="claim-method__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-mail"/></svg></span>
                <span class="choice__title">Email on the business website <span class="badge">Fastest</span></span>
                <span class="choice__text">Use an email address on the business&rsquo;s own website domain. We send a link, you click it, and we can usually approve the same day.</span>
              </span>
            </label>
            <div class="claim-method__panel" id="m-email">
              <div class="field">
                <label for="c-email">Your email at the business</label>
                <div class="input-group input-group--end">
                  <input class="input" id="c-email" type="text" name="email_local" placeholder="yourname" autocomplete="off" aria-describedby="c-email-help">
                  <span class="input-group__addon">@hillstreetkitchen.co.uk</span>
                </div>
                <p class="help" id="c-email-help">The domain comes from the website on the listing. We&rsquo;ll email a confirmation link to this address.</p>
              </div>
            </div>
            <label class="choice claim-method">
              <input type="radio" name="method" value="phone" data-reveal="#m-phone">
              <span>
                <span class="claim-method__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-phone"/></svg></span>
                <span class="choice__title">We phone the business</span>
                <span class="choice__text">A member of the Harrow Online team calls the number on the listing and asks for you by name. Usually within 2 working days.</span>
              </span>
            </label>
            <div class="claim-method__panel" id="m-phone" hidden>
              <div class="stack">
                <p class="small mb-0">We&rsquo;ll call <strong>020 7946 0321</strong>, the number on the listing. If that number is wrong, choose the document option instead.</p>
                <div class="field">
                  <label for="c-askfor">Who should we ask for?</label>
                  <input class="input" id="c-askfor" type="text" name="ask_for" autocomplete="name" placeholder="Your name">
                </div>
                <fieldset class="fieldset">
                  <legend class="field__label">Best times to call <span class="opt">(choose any)</span></legend>
                  <ul class="check-list">
                    <li><label><input type="checkbox" name="call_times[]" value="weekday-morning"> Weekday mornings, 9am to 12pm</label></li>
                    <li><label><input type="checkbox" name="call_times[]" value="weekday-afternoon"> Weekday afternoons, 12pm to 5pm</label></li>
                    <li><label><input type="checkbox" name="call_times[]" value="saturday"> Saturday, 10am to 1pm</label></li>
                  </ul>
                </fieldset>
              </div>
            </div>
            <label class="choice claim-method">
              <input type="radio" name="method" value="document" data-reveal="#m-doc">
              <span>
                <span class="claim-method__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-upload"/></svg></span>
                <span class="choice__title">Upload a document</span>
                <span class="choice__text">A recent utility bill, business rates bill or Companies House record showing the business name and address. Only Harrow Online admins can see it.</span>
              </span>
            </label>
            <div class="claim-method__panel" id="m-doc" hidden>
              <div class="stack">
                <div class="field">
                  <label for="c-doctype">Type of document</label>
                  <select class="select" id="c-doctype" name="doc_type">
                    <option value="utility">Utility bill (last 3 months)</option>
                    <option value="rates">Business rates bill</option>
                    <option value="companies-house">Companies House record</option>
                  </select>
                </div>
                <label class="dropzone dropzone--compact" for="c-doc">
                  <svg class="ho-icon"><use href="#i-upload"/></svg>
                  <span><strong>Choose a file or drag it here</strong><small>PDF, JPG or PNG, up to 10MB. The business name and address must be readable.</small></span>
                  <input class="ho-sr" id="c-doc" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png">
                </label>
                <p class="help"><svg class="ho-icon ho-icon--sm"><use href="#i-lock"/></svg> Private. Your document is never shown on the Hub and only our admin team can open it.</p>
              </div>
            </div>
          </div>

        </fieldset>
      </section>

      <section class="form-step" aria-labelledby="about-you-title">
        <h2 class="form-step__title" id="about-you-title"><span>2</span>About you</h2>
        <p class="help mt-0">Signed in as <strong>sam.taylor@example.com</strong>. <a href="<?php echo esc_url( hub_url( 'sign-in/' ) ); ?>">Not you?</a></p>
        <div class="row row-2">
          <div class="field">
            <label for="c-role">Your role <span class="req" aria-hidden="true">*</span></label>
            <select class="select" id="c-role" name="role" required>
              <option value="">Choose one</option>
              <option value="owner">Owner</option>
              <option value="manager">Manager</option>
              <option value="staff">Staff member allowed to manage the listing</option>
              <option value="agency">Marketing agency acting for the owner</option>
            </select>
          </div>
          <div class="field">
            <label for="c-phone">Your phone <span class="opt">(optional)</span></label>
            <input class="input" id="c-phone" type="tel" name="phone" autocomplete="tel" placeholder="07700 900123">
            <p class="help">Only used if we have a question about your claim.</p>
          </div>
        </div>
        <div class="field">
          <label for="c-note">Anything we should know? <span class="opt">(optional)</span></label>
          <textarea class="textarea" id="c-note" name="note" maxlength="500" rows="3" data-counter placeholder="For example, the business has a new name, or someone else claimed it by mistake."></textarea>
        </div>
      </section>

      <div class="add-biz__submit">
        <label class="check">
          <input type="checkbox" name="declaration" value="1" required>
          <span>I confirm I own or manage this business, or have permission to manage its listing, and I agree to the <a href="<?php echo esc_url( hub_url( 'terms/' ) ); ?>">terms</a>. <small>False claims are rejected and may be reported.</small></span>
        </label>
        <div class="add-biz__actions">
          <button class="ho-btn ho-btn--primary" type="submit">Submit claim</button>
          <a class="ho-btn ho-btn--ghost" href="<?php echo esc_url( hub_url( 'search/' ) ); ?>">Cancel</a>
        </div>
      </div>
    </form>

    <aside class="sticky claim-side" aria-label="About claiming">
      <div class="add-biz__panel">
        <h2 class="add-biz__panel-title">Free on the Basic plan</h2>
        <p class="small">Once we approve your claim, you control the listing at Basic, free for as long as you like.</p>
        <ul class="ticks small">
          <li>Verified badge</li>
          <li>Edit your name, address, phone and website</li>
          <li>Add a description and opening hours</li>
          <li>Add a photo</li>
          <li>Ranked above unclaimed listings</li>
        </ul>
        <div class="claim-side__part">
          <h3 class="claim-side__h">Want to stand out?</h3>
          <p class="small">Claiming is the only step we check by hand. Paid upgrades go live as soon as your payment succeeds, with more photos, Local Offers, events and a place at the top of your category.</p>
          <a class="link-arrow" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Compare plans <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
        </div>
        <div class="claim-side__part">
          <h3 class="claim-side__h">Need help?</h3>
          <p class="small mb-0">Email <a href="mailto:hub@harrowonline.org">hub@harrowonline.org</a> and tell us the business name. We reply within 2 working days.</p>
        </div>
      </div>
    </aside>
  </div>
</div>
<?php get_footer();

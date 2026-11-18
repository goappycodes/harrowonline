<?php
/**
 * Claim received (converted from src/pages/claim-submitted.html).
 * URL: /claim/submitted/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-claim-submitted',
	'description' => 'Thanks for claiming your business on Harrow Online Hub. We\'re checking your claim and will email you either way.',
	'hubnav' => 'directory',
	'robots' => 'noindex, follow',
	'title' => 'Claim received',
) );
get_header();
?>
<!-- Claim received (redesign, Nov 2026): same step bar, business card and side card as the Claim page.
     Every note, step, plan and link from the previous version is kept. -->
<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><a href="<?php echo esc_url( hub_url( 'search/' ) ); ?>">Businesses</a></li><li><span aria-current="page">Claim received</span></li></ol></nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">Thanks, we&rsquo;re checking your claim</h1>
  <p class="page-head__lead">A member of the Harrow Online team will check it, usually within 2 working days.</p>
</header>

<div class="ho-container">
  <ol class="sp-steps sp-steps--4" aria-label="Claim steps">
    <li class="is-done"><span class="sp-steps__n"><svg class="ho-icon ho-icon--sm"><use href="#i-check"/></svg><span class="ho-sr">Done:</span></span><span><small>Step 1</small>Sign in</span></li>
    <li class="is-done"><span class="sp-steps__n"><svg class="ho-icon ho-icon--sm"><use href="#i-check"/></svg><span class="ho-sr">Done:</span></span><span><small>Step 2</small>Prove ownership</span></li>
    <li class="is-current" aria-current="step"><span class="sp-steps__n">3</span><span><small>Step 3</small>Admin check</span></li>
    <li><span class="sp-steps__n">4</span><span><small>Step 4</small>Done</span></li>
  </ol>

  <div class="layout-aside claim-layout">
    <div class="form-card claim-form">
      <section class="claim-biz claim-biz--sent" aria-labelledby="sent-title">
        <span class="claim-biz__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-check"/></svg></span>
        <div class="claim-biz__main">
          <h2 class="claim-biz__label" id="sent-title">Claim received for</h2>
          <span class="biz-card__cat">Restaurants</span>
          <h3 class="claim-biz__name"><a href="<?php echo esc_url( hub_url( 'business/hill-street-kitchen-harrow-on-the-hill/' ) ); ?>">Hill Street Kitchen</a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg>14 High Street, HA1 3LE</span><span>Harrow on the Hill</span></p>
        </div>
      </section>

      <section class="form-step" aria-labelledby="next-title">
        <h2 class="form-step__title" id="next-title">What happens next</h2>
        <ol class="next-steps">
          <li><span class="next-steps__n">1</span><div><h3>We check your proof</h3><p>We confirm your email link, call the business or look at your document, depending on the method you chose.</p></div></li>
          <li><span class="next-steps__n">2</span><div><h3>We email you either way</h3><p>You&rsquo;ll hear from us at <strong>sam.taylor@example.com</strong> whether we approve the claim or need something else from you.</p></div></li>
          <li><span class="next-steps__n">3</span><div><h3>You manage the listing</h3><p>Once approved, your account has a getting started checklist: your description, opening hours and photos, then your first event, class or offer. You get a verified badge straight away.</p></div></li>
        </ol>
      </section>

      <div class="notice notice--plain">
        <svg class="ho-icon"><use href="#i-mail"/></svg>
        <p>Chose email? Check your inbox for a link from <strong>hub@harrowonline.org</strong> and click it to confirm. It can take a few minutes to arrive, so check your spam folder too.</p>
      </div>

      <div class="add-biz__submit">
        <div class="add-biz__actions">
          <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'account/?welcome=1' ) ); ?>">Go to my account</a>
          <a class="ho-btn ho-btn--ghost" href="<?php echo esc_url( hub_url( '' ) ); ?>">Back to the Hub</a>
        </div>
      </div>
    </div>

    <aside class="sticky claim-side" aria-label="While you wait">
      <div class="add-biz__panel">
        <p class="claim-side__eyebrow">While you wait</p>
        <h2 class="add-biz__panel-title">Get to the top of your category</h2>
        <p class="small">Pick a paid plan now and it switches on as soon as your claim is approved and payment succeeds. Monthly or annual, cancel any time.</p>
        <div class="claim-side__part">
          <div class="side-plan"><h3 class="claim-side__h">Featured</h3><p class="side-plan__price"><strong>&pound;29</strong> a month, or &pound;290 a year</p></div>
          <p class="small mb-0">Priority placement, more photos and social links, Local Offers, events and classes, and featured positions within the Hub.</p>
        </div>
        <div class="claim-side__part">
          <div class="side-plan"><h3 class="claim-side__h">Local Partner</h3><p class="side-plan__price"><strong>&pound;79</strong> a month, or &pound;790 a year</p></div>
          <p class="small mb-0">Everything in Featured, plus one sponsored slot each month in the Daily News newsletter and one sponsored post each month in the Harrow Online Facebook Group.</p>
        </div>
        <div class="claim-side__part">
          <a class="ho-btn ho-btn--outline ho-btn--block" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See plans</a>
        </div>
      </div>
    </aside>
  </div>
</div>
<?php get_footer();

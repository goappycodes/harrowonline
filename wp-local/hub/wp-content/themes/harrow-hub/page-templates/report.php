<?php
/**
 * Report a problem with a listing (converted from src/pages/report.html).
 * URL: /report/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-report',
	'description' => 'Tell Harrow Online Hub about wrong details, a closed or moved business, a duplicate listing, or ask for a listing or your personal data to be removed.',
	'hubnav' => 'directory',
	'robots' => 'noindex, follow',
	'title' => 'Report a problem with a listing',
) );
get_header();
?>
<!-- Report a problem (redesign, Nov 2026): same layout as the Claim page: the listing at the top of one card,
     numbered sections, one side card. Every field, choice and note from the previous version is kept. -->
<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><a href="<?php echo esc_url( hub_url( 'search/' ) ); ?>">Businesses</a></li><li><span aria-current="page">Report a problem</span></li></ol></nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">Report a problem with a listing</h1>
  <p class="page-head__lead">Spotted wrong details, or want a listing removed? Tell us here. Our team checks every request.</p>
</header>

<div class="ho-container">
  <div class="layout-aside claim-layout">
    <form class="form form-card claim-form" action="<?php echo esc_url( hub_url( 'report/' ) ); ?>" method="post">
      <section class="claim-biz" aria-labelledby="listing-title">
        <span class="claim-biz__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-store"/></svg></span>
        <div class="claim-biz__main">
          <h2 class="claim-biz__label" id="listing-title">You&rsquo;re reporting</h2>
          <span class="biz-card__cat" data-listing-field="cat">Restaurants</span>
          <h3 class="claim-biz__name"><a href="<?php echo esc_url( hub_url( 'business/hill-street-kitchen-harrow-on-the-hill/' ) ); ?>" data-listing-field="link"><span data-listing-field="n">Hill Street Kitchen</span></a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg><span data-listing-field="ad">14 High Street</span>, <span data-listing-field="pc">HA1 3LE</span></span><span data-listing-field="area">Harrow on the Hill</span></p>
        </div>
        <p class="claim-biz__again claim-biz__again--below">Wrong business? <a href="<?php echo esc_url( hub_url( 'search/' ) ); ?>">Find the right one</a> and use the link at the bottom of its page.</p>
      </section>

      <section class="form-step" aria-labelledby="type-title">
        <h2 class="form-step__title" id="type-title"><span>1</span>What&rsquo;s the problem?</h2>
        <fieldset class="fieldset">
          <legend class="ho-sr">What&rsquo;s the problem?</legend>
          <div class="choice-grid choice-grid--2">
            <label class="choice">
              <input type="radio" name="type" value="wrong-details" checked>
              <span><span class="choice__title">Wrong details</span><span class="choice__text">The name, address, phone, website or category is wrong.</span></span>
            </label>
            <label class="choice">
              <input type="radio" name="type" value="closed">
              <span><span class="choice__title">Closed permanently</span><span class="choice__text">The business has shut for good.</span></span>
            </label>
            <label class="choice">
              <input type="radio" name="type" value="moved" data-reveal="#r-moved">
              <span><span class="choice__title">Moved</span><span class="choice__text">It&rsquo;s still trading, at a new address.</span></span>
            </label>
            <label class="choice">
              <input type="radio" name="type" value="duplicate" data-reveal="#r-dup">
              <span><span class="choice__title">Duplicate listing</span><span class="choice__text">The same business is listed more than once.</span></span>
            </label>
            <label class="choice">
              <input type="radio" name="type" value="remove">
              <span><span class="choice__title">Remove my business or personal data</span><span class="choice__text">You own the business, or the listing shows your personal details.</span></span>
            </label>
          </div>
        </fieldset>

        <div class="panel panel--soft" id="r-moved" hidden>
          <div class="row row-2">
            <div class="field"><label for="r-newaddr">New address</label><input class="input" id="r-newaddr" type="text" name="new_address" autocomplete="off"></div>
            <div class="field"><label for="r-newpc">New postcode</label><input class="input" id="r-newpc" type="text" name="new_postcode" autocomplete="off" placeholder="HA1 1AA"></div>
          </div>
        </div>
        <div class="panel panel--soft" id="r-dup" hidden>
          <div class="field">
            <label for="r-duplink">Link to the other listing <span class="opt">(if you have it)</span></label>
            <input class="input" id="r-duplink" type="url" name="duplicate_of" placeholder="https://harrowonline.org/hub/business/&hellip;">
          </div>
        </div>

        <div class="field">
          <label for="r-details">Tell us more <span class="req" aria-hidden="true">*</span></label>
          <textarea class="textarea" id="r-details" name="details" maxlength="1000" required data-counter placeholder="What&rsquo;s wrong, and what should it say instead?"></textarea>
        </div>
      </section>

      <section class="form-step" aria-labelledby="you-title">
        <h2 class="form-step__title" id="you-title"><span>2</span>About you</h2>
        <p class="help mt-0">So we can reply. We don&rsquo;t show this anywhere.</p>
        <div class="row row-2">
          <div class="field"><label for="r-name">Your name <span class="req" aria-hidden="true">*</span></label><input class="input" id="r-name" type="text" name="name" autocomplete="name" required></div>
          <div class="field"><label for="r-email">Your email <span class="req" aria-hidden="true">*</span></label><input class="input" id="r-email" type="email" name="email" autocomplete="email" required></div>
        </div>
        <div class="field">
          <label for="r-rel">Your connection to the business <span class="req" aria-hidden="true">*</span></label>
          <select class="select" id="r-rel" name="relationship" required>
            <option value="">Choose one</option>
            <option value="owner">I own or manage it</option>
            <option value="staff">I work there</option>
            <option value="customer">I&rsquo;m a customer or neighbour</option>
            <option value="named">The listing shows my personal details</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="hp" aria-hidden="true">
          <label for="r-website">Leave this empty</label>
          <input id="r-website" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>
      </section>

      <div class="add-biz__submit">
        <p class="help mb-0">We use these details only to deal with this request. See our <a href="<?php echo esc_url( hub_url( 'privacy/' ) ); ?>">privacy notice</a>.</p>
        <div class="add-biz__actions">
          <button class="ho-btn ho-btn--primary" type="submit">Send request</button>
          <a class="ho-btn ho-btn--ghost" href="<?php echo esc_url( hub_url( 'search/' ) ); ?>">Cancel</a>
        </div>
      </div>
    </form>

    <aside class="sticky claim-side" aria-label="About reports">
      <div class="add-biz__panel">
        <h2 class="add-biz__panel-title">What happens next</h2>
        <ul class="ticks small">
          <li>Your request goes to the Harrow Online admin team.</li>
          <li>We check it, usually within 5 working days, and update or remove the listing.</li>
          <li>We email you when it&rsquo;s done, or if we need more information.</li>
        </ul>
        <div class="claim-side__part">
          <h3 class="claim-side__h">Is this your business?</h3>
          <p class="small">Claim it free and fix the details yourself, any time.</p>
          <a class="link-arrow" href="<?php echo esc_url( hub_url( 'claim/?listing=hill-street-kitchen-harrow-on-the-hill' ) ); ?>" data-listing-field="claim">Claim this business <svg class="ho-icon"><use href="#i-arrow"/></svg></a>
        </div>
        <div class="claim-side__part">
          <h3 class="claim-side__h">Where our listings come from</h3>
          <p class="small mb-0">Unclaimed listings come from open map data. Read about <a href="<?php echo esc_url( hub_url( 'data-sources/' ) ); ?>">our data sources</a> and how to ask for removal under data protection law.</p>
        </div>
      </div>
    </aside>
  </div>
</div>
<?php get_footer();

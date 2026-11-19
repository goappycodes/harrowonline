<?php
/**
 * Claim received (from src/pages/claim-submitted.html; hand-edited: the account's latest claim from the claims
 * module). Signed-out visitors are sent to sign in first (app/setup/claims.php).
 * URL: /claim/submitted/   ?verified=1|0 after the business-email link
 */
defined( 'ABSPATH' ) || exit;
$hub_claim = hub_claim_mine();
if ( ! $hub_claim ) {
	wp_safe_redirect( hub_url( 'search/' ) );
	exit;
}
$hub_biz      = $hub_claim['business'];
$hub_verified = isset( $_GET['verified'] ) ? sanitize_key( $_GET['verified'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$hub_account  = hub_account_current();
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
  <?php if ( 'approved' === $hub_claim['status'] ) : ?>
  <h1 class="page-head__title">Your claim is approved</h1>
  <p class="page-head__lead">You manage this business now. Start with your description, opening hours and a photo.</p>
  <?php elseif ( 'rejected' === $hub_claim['status'] ) : ?>
  <h1 class="page-head__title">We couldn&rsquo;t approve your claim</h1>
  <p class="page-head__lead">We&rsquo;ve emailed you what would work instead. You can send a new claim with a different proof.</p>
  <?php else : ?>
  <h1 class="page-head__title">Thanks, we&rsquo;re checking your claim</h1>
  <p class="page-head__lead">A member of the Harrow Online team will check it, usually within 2 working days.</p>
  <?php endif; ?>
</header>

<div class="ho-container">
  <ol class="sp-steps sp-steps--4" aria-label="Claim steps">
    <li class="is-done"><span class="sp-steps__n"><svg class="ho-icon ho-icon--sm"><use href="#i-check"/></svg><span class="ho-sr">Done:</span></span><span><small>Step 1</small>Sign in</span></li>
    <li class="is-done"><span class="sp-steps__n"><svg class="ho-icon ho-icon--sm"><use href="#i-check"/></svg><span class="ho-sr">Done:</span></span><span><small>Step 2</small>Prove ownership</span></li>
    <?php if ( 'approved' === $hub_claim['status'] ) : ?>
    <li class="is-done"><span class="sp-steps__n"><svg class="ho-icon ho-icon--sm"><use href="#i-check"/></svg><span class="ho-sr">Done:</span></span><span><small>Step 3</small>Admin check</span></li>
    <li class="is-current" aria-current="step"><span class="sp-steps__n">4</span><span><small>Step 4</small>Done</span></li>
    <?php else : ?>
    <li class="is-current" aria-current="step"><span class="sp-steps__n">3</span><span><small>Step 3</small>Admin check</span></li>
    <li><span class="sp-steps__n">4</span><span><small>Step 4</small>Done</span></li>
    <?php endif; ?>
  </ol>

  <div class="layout-aside claim-layout">
    <div class="form-card claim-form">
      <section class="claim-biz claim-biz--sent" aria-labelledby="sent-title">
        <span class="claim-biz__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-check"/></svg></span>
        <div class="claim-biz__main">
          <h2 class="claim-biz__label" id="sent-title">Claim received for</h2>
          <span class="biz-card__cat"><?php echo esc_html( $hub_biz['category'] ); ?></span>
          <h3 class="claim-biz__name"><a href="<?php echo esc_url( $hub_biz['url'] ); ?>"><?php echo esc_html( $hub_biz['name'] ); ?></a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg><?php echo esc_html( trim( $hub_biz['address'] . ', ' . $hub_biz['postcode'], ', ' ) ); ?></span><span><?php echo esc_html( $hub_biz['area'] ); ?></span></p>
        </div>
      </section>

      <?php if ( '1' === $hub_verified ) : ?>
      <div class="notice notice--ok" role="status"><svg class="ho-icon"><use href="#i-check"/></svg><div><p class="mb-0"><strong>Business email confirmed.</strong> Thanks. We can usually approve it the same day.</p></div></div>
      <?php elseif ( '0' === $hub_verified ) : ?>
      <div class="notice notice--warn" role="status"><svg class="ho-icon"><use href="#i-alert"/></svg><div><p class="mb-0"><strong>That confirmation link didn&rsquo;t work.</strong> It may have expired. <a href="<?php echo esc_url( add_query_arg( 'listing', get_post_field( 'post_name', $hub_biz['id'] ), hub_url( 'claim/' ) ) ); ?>">Send the claim again</a> for a new link.</p></div></div>
      <?php endif; ?>
      <?php if ( 'rejected' === $hub_claim['status'] && '' !== $hub_claim['decision_note'] ) : ?>
      <div class="notice notice--warn" role="status"><svg class="ho-icon"><use href="#i-info"/></svg><div><p class="mb-0"><strong>Our note:</strong> <?php echo esc_html( $hub_claim['decision_note'] ); ?></p></div></div>
      <?php endif; ?>

      <section class="form-step" aria-labelledby="next-title">
        <h2 class="form-step__title" id="next-title">What happens next</h2>
        <ol class="next-steps">
          <li><span class="next-steps__n">1</span><div><h3>We check your proof</h3><p>We confirm your email link, call the business or look at your document, depending on the method you chose.</p></div></li>
          <li><span class="next-steps__n">2</span><div><h3>We email you either way</h3><p>You&rsquo;ll hear from us at <strong><?php echo esc_html( $hub_account['email'] ); ?></strong> whether we approve the claim or need something else from you.</p></div></li>
          <li><span class="next-steps__n">3</span><div><h3>You manage the listing</h3><p>Once approved, your account has a getting started checklist: your description, opening hours and photos, then your first event, class or offer. You get a verified badge straight away.</p></div></li>
        </ol>
      </section>

      <?php if ( 'email' === $hub_claim['method'] && 'pending' === $hub_claim['status'] && '1' !== $hub_verified ) : ?>
      <div class="notice notice--plain">
        <svg class="ho-icon"><use href="#i-mail"/></svg>
        <p>Check the business inbox for a link from <strong>hub@harrowonline.org</strong> and click it to confirm. It can take a few minutes to arrive, so check your spam folder too.</p>
      </div>
      <?php endif; ?>

      <div class="add-biz__submit">
        <div class="add-biz__actions">
          <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">Go to my account</a>
          <a class="ho-btn ho-btn--ghost" href="<?php echo esc_url( hub_url( '' ) ); ?>">Back to the Hub</a>
        </div>
      </div>
    </div>

    <aside class="sticky claim-side" aria-label="While you wait">
      <div class="add-biz__panel">
        <p class="claim-side__eyebrow">While you wait</p>
        <h2 class="add-biz__panel-title">Get to the top of your category</h2>
        <p class="small">Once your claim is approved you can choose a paid plan from your account, and it switches on as soon as your payment succeeds. Monthly or annual, cancel any time.</p>
        <div class="claim-side__part">
          <div class="side-plan"><h3 class="claim-side__h">Featured</h3><p class="side-plan__price"><strong>&pound;29</strong> a month, or &pound;290 a year</p></div>
          <p class="small mb-0">Priority placement, more photos and social links, Local Offers, events and classes, and featured positions within the Hub.</p>
        </div>
        <div class="claim-side__part">
          <div class="side-plan"><h3 class="claim-side__h">Local Partner</h3><p class="side-plan__price"><strong>&pound;79</strong> a month, or &pound;790 a year</p></div>
          <p class="small mb-0">Everything in Featured, plus one Daily News advert each month and one Harrow Online Facebook Group post each month.</p>
        </div>
        <div class="claim-side__part">
          <a class="ho-btn ho-btn--outline ho-btn--block" href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See plans</a>
        </div>
      </div>
    </aside>
  </div>
</div>
<?php get_footer();

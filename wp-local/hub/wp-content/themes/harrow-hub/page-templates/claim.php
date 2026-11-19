<?php
/**
 * Claim your business (from src/pages/claim.html; hand-edited: the real listing from ?listing={slug}, and the form
 * submits through the claims module). Signed-out visitors are sent to sign in first (app/setup/claims.php).
 * URL: /claim/?listing={slug}
 */
defined( 'ABSPATH' ) || exit;
$hub_result = null;
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$hub_result = hub_claim_submit( $_POST, $_FILES ); // phpcs:ignore WordPress.Security.NonceVerification -- the controller checks it
	if ( $hub_result['ok'] ) {
		wp_safe_redirect( $hub_result['redirect'] );
		exit;
	}
}
$hub_page  = hub_claim_page( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
$hub_biz   = $hub_page['business'];
$hub_ready = 'ready' === $hub_page['state'];
$hub_claim = $hub_page['claim'];
// The chosen method: what they just sent, else their waiting claim's, else the fastest one this listing allows
$hub_method = hub_view_posted( 'method' );
if ( '' === $hub_method ) {
	$hub_method = $hub_claim ? $hub_claim['method'] : ( '' !== $hub_page['domain'] ? 'email' : ( '' !== $hub_page['call_number'] ? 'phone' : 'document' ) );
}
$hub_err  = function ( $f ) use ( $hub_result ) { hub_view_field_error( $hub_result, $f ); };
$hub_cls  = function ( $f ) use ( $hub_result ) { echo esc_attr( hub_view_field_class( $hub_result, $f ) ); };
$hub_val  = function ( $f ) { echo esc_attr( hub_view_posted( $f ) ); };
$hub_list = isset( $_POST['call_times'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['call_times'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
hub_set_page( array(
	'body' => 'page-claim',
	'description' => 'Claim your free business listing on Harrow Online Hub. Prove you own it and manage your details, hours and photos.',
	'hubnav' => 'directory',
	'robots' => 'noindex, follow',
	'title' => $hub_biz ? 'Claim ' . html_entity_decode( $hub_biz['name'], ENT_QUOTES ) : 'Claim your business',
) );
get_header();
?>
<!-- Claim (redesign, Nov 2026): numbered steps, the business being claimed at the top, the three ways to prove ownership
     as side-by-side cards (the chosen one's details open below), then About you. One side card: Basic benefits,
     upgrades and help. -->
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
    <form class="form form-card claim-form" action="<?php echo esc_url( add_query_arg( 'listing', $hub_biz ? $hub_biz['slug'] : '', hub_url( 'claim/' ) ) ); ?>" method="post" enctype="multipart/form-data">
      <?php wp_nonce_field( 'hub_claim', '_hub_nonce' ); ?>
      <input type="hidden" name="listing" value="<?php echo esc_attr( $hub_biz ? $hub_biz['slug'] : '' ); ?>">

      <?php if ( $hub_biz ) : ?>
      <section class="claim-biz" aria-labelledby="claiming-title">
        <span class="claim-biz__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-store"/></svg></span>
        <div class="claim-biz__main">
          <h2 class="claim-biz__label" id="claiming-title">You&rsquo;re claiming</h2>
          <span class="biz-card__cat"><?php echo esc_html( $hub_biz['category']['name'] ); ?></span>
          <h3 class="claim-biz__name"><a href="<?php echo esc_url( $hub_biz['url'] ); ?>"><?php echo esc_html( $hub_biz['name'] ); ?></a></h3>
          <p class="card__meta"><span><svg class="ho-icon"><use href="#i-pin"/></svg><?php echo esc_html( trim( $hub_biz['address']['street'] . ', ' . $hub_biz['address']['postcode'], ', ' ) ); ?></span><span><?php echo esc_html( $hub_biz['area']['name'] ); ?></span></p>
        </div>
        <p class="claim-biz__again">Not the right business? <a href="<?php echo esc_url( hub_url( 'search/' ) ); ?>">Search again</a></p>
      </section>
      <?php endif; ?>

      <?php if ( ! $hub_ready ) : ?>
      <div class="notice notice--warn" role="status"><svg class="ho-icon"><use href="#i-alert"/></svg><div>
        <p><?php echo wp_kses( $hub_page['message'], array() ); ?></p>
        <?php if ( 'unconfirmed' === $hub_page['state'] ) : ?>
          <p class="mb-0"><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">Go to my account to resend the link</a></p>
        <?php elseif ( 'not_found' === $hub_page['state'] ) : ?>
          <p class="mb-0"><a href="<?php echo esc_url( hub_url( 'search/' ) ); ?>">Search for your business</a> &middot; <a href="<?php echo esc_url( hub_url( 'add-business/' ) ); ?>">Not listed? Add it</a></p>
        <?php else : ?>
          <p class="mb-0"><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">Go to my account</a></p>
        <?php endif; ?>
      </div></div>
      <?php else : ?>

      <?php if ( $hub_claim && ! $hub_result ) : ?>
      <div class="notice" role="status"><svg class="ho-icon"><use href="#i-info"/></svg><div>
        <p class="mb-0"><strong>You&rsquo;ve already sent a claim for this business.</strong> We&rsquo;re checking it. Sending this form again replaces it.</p>
      </div></div>
      <?php endif; ?>
      <?php hub_view_form_notice( $hub_result ); ?>

      <section class="form-step" aria-labelledby="method-title">
        <h2 class="form-step__title" id="method-title"><span>1</span>How can we check it&rsquo;s yours?</h2>
        <p class="help mt-0">Pick the quickest option for you. We list them fastest first.</p>
        <fieldset class="fieldset claim-methods<?php $hub_cls( 'method' ); ?>">
          <legend class="ho-sr">How can we check it&rsquo;s yours?</legend>
          <?php $hub_err( 'method' ); ?>
          <div class="claim-methods__list">
            <label class="choice claim-method">
              <input type="radio" name="method" value="email" data-reveal="#m-email" <?php checked( $hub_method, 'email' ); ?> <?php disabled( '' === $hub_page['domain'] ); ?>>
              <span>
                <span class="claim-method__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-mail"/></svg></span>
                <span class="choice__title">Email on the business website <span class="badge">Fastest</span></span>
                <span class="choice__text"><?php echo '' !== $hub_page['domain'] ? 'Use an email address on the business&rsquo;s own website domain. We send a link, you click it, and we can usually approve the same day.' : 'Not available: this listing has no website of its own for us to check an email against.'; ?></span>
              </span>
            </label>
            <div class="claim-method__panel" id="m-email"<?php echo 'email' === $hub_method ? '' : ' hidden'; ?>>
              <div class="field<?php $hub_cls( 'email_local' ); ?>">
                <label for="c-email">Your email at the business</label>
                <div class="input-group input-group--end">
                  <input class="input" id="c-email" type="text" name="email_local" placeholder="yourname" autocomplete="off" aria-describedby="c-email-help" value="<?php $hub_val( 'email_local' ); ?>">
                  <span class="input-group__addon">@<?php echo esc_html( $hub_page['domain'] ); ?></span>
                </div>
                <?php $hub_err( 'email_local' ); ?>
                <p class="help" id="c-email-help">The domain comes from the website on the listing. We&rsquo;ll email a confirmation link to this address.</p>
              </div>
            </div>
            <label class="choice claim-method">
              <input type="radio" name="method" value="phone" data-reveal="#m-phone" <?php checked( $hub_method, 'phone' ); ?> <?php disabled( '' === $hub_page['call_number'] ); ?>>
              <span>
                <span class="claim-method__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-phone"/></svg></span>
                <span class="choice__title">We phone the business</span>
                <span class="choice__text"><?php echo '' !== $hub_page['call_number'] ? 'A member of the Harrow Online team calls the number on the listing and asks for you by name. Usually within 2 working days.' : 'Not available: this listing has no phone number for us to call.'; ?></span>
              </span>
            </label>
            <div class="claim-method__panel" id="m-phone"<?php echo 'phone' === $hub_method ? '' : ' hidden'; ?>>
              <div class="stack">
                <p class="small mb-0">We&rsquo;ll call <strong><?php echo esc_html( $hub_page['call_number'] ); ?></strong>, the number on the listing. If that number is wrong, choose the document option instead.</p>
                <div class="field<?php $hub_cls( 'ask_for' ); ?>">
                  <label for="c-askfor">Who should we ask for?</label>
                  <input class="input" id="c-askfor" type="text" name="ask_for" autocomplete="name" placeholder="Your name" value="<?php echo esc_attr( '' !== hub_view_posted( 'ask_for' ) ? hub_view_posted( 'ask_for' ) : $hub_page['account']['name'] ); ?>">
                  <?php $hub_err( 'ask_for' ); ?>
                </div>
                <fieldset class="fieldset">
                  <legend class="field__label">Best times to call <span class="opt">(choose any)</span></legend>
                  <ul class="check-list">
                    <?php foreach ( HUB_CLAIMS_CALL_TIMES as $hub_v => $hub_l ) : ?>
                    <li><label><input type="checkbox" name="call_times[]" value="<?php echo esc_attr( $hub_v ); ?>" <?php checked( in_array( $hub_v, $hub_list, true ) ); ?>> <?php echo esc_html( $hub_l ); ?></label></li>
                    <?php endforeach; ?>
                  </ul>
                </fieldset>
              </div>
            </div>
            <label class="choice claim-method">
              <input type="radio" name="method" value="document" data-reveal="#m-doc" <?php checked( $hub_method, 'document' ); ?>>
              <span>
                <span class="claim-method__icon" aria-hidden="true"><svg class="ho-icon"><use href="#i-upload"/></svg></span>
                <span class="choice__title">Upload a document</span>
                <span class="choice__text">A recent utility bill, business rates bill or Companies House record showing the business name and address. Only Harrow Online admins can see it.</span>
              </span>
            </label>
            <div class="claim-method__panel" id="m-doc"<?php echo 'document' === $hub_method ? '' : ' hidden'; ?>>
              <div class="stack">
                <div class="field">
                  <label for="c-doctype">Type of document</label>
                  <select class="select" id="c-doctype" name="doc_type">
                    <?php foreach ( HUB_CLAIMS_DOC_TYPES as $hub_v => $hub_l ) : ?>
                    <option value="<?php echo esc_attr( $hub_v ); ?>" <?php selected( hub_view_posted( 'doc_type' ), $hub_v ); ?>><?php echo esc_html( $hub_l ); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field<?php $hub_cls( 'document' ); ?>">
                  <label class="dropzone dropzone--compact" for="c-doc">
                    <svg class="ho-icon"><use href="#i-upload"/></svg>
                    <span><strong>Choose a file or drag it here</strong><small>PDF, JPG or PNG, up to 10MB. The business name and address must be readable.</small></span>
                    <input class="ho-sr" id="c-doc" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png">
                  </label>
                  <?php $hub_err( 'document' ); ?>
                </div>
                <p class="help"><svg class="ho-icon ho-icon--sm"><use href="#i-lock"/></svg> Private. Your document is never shown on the Hub and only our admin team can open it.</p>
              </div>
            </div>
          </div>

        </fieldset>
      </section>

      <section class="form-step" aria-labelledby="about-you-title">
        <h2 class="form-step__title" id="about-you-title"><span>2</span>About you</h2>
        <p class="help mt-0">Signed in as <strong><?php echo esc_html( $hub_page['account']['email'] ); ?></strong>. <a href="<?php echo esc_url( hub_view_sign_out_url() ); ?>">Not you?</a></p>
        <div class="row row-2">
          <div class="field<?php $hub_cls( 'role' ); ?>">
            <label for="c-role">Your role <span class="req" aria-hidden="true">*</span></label>
            <select class="select" id="c-role" name="role" required>
              <option value="">Choose one</option>
              <?php foreach ( HUB_CLAIMS_ROLES as $hub_v => $hub_l ) : ?>
              <option value="<?php echo esc_attr( $hub_v ); ?>" <?php selected( hub_view_posted( 'role' ), $hub_v ); ?>><?php echo esc_html( $hub_l ); ?></option>
              <?php endforeach; ?>
            </select>
            <?php $hub_err( 'role' ); ?>
          </div>
          <div class="field">
            <label for="c-phone">Your phone <span class="opt">(optional)</span></label>
            <input class="input" id="c-phone" type="tel" name="phone" autocomplete="tel" placeholder="07700 900123" value="<?php $hub_val( 'phone' ); ?>">
            <p class="help">Only used if we have a question about your claim.</p>
          </div>
        </div>
        <div class="field">
          <label for="c-note">Anything we should know? <span class="opt">(optional)</span></label>
          <textarea class="textarea" id="c-note" name="note" maxlength="500" rows="3" data-counter placeholder="For example, the business has a new name, or someone else claimed it by mistake."><?php echo esc_textarea( hub_view_posted( 'note' ) ); ?></textarea>
        </div>
      </section>

      <div class="add-biz__submit">
        <label class="check<?php $hub_cls( 'declaration' ); ?>">
          <input type="checkbox" name="declaration" value="1" required <?php checked( '1', hub_view_posted( 'declaration' ) ); ?>>
          <span>I confirm I own or manage this business, or have permission to manage its listing, and I agree to the <a href="<?php echo esc_url( hub_url( 'terms/' ) ); ?>">terms</a>. <small>False claims are rejected and may be reported.</small></span>
        </label>
        <?php $hub_err( 'declaration' ); ?>
        <div class="add-biz__actions">
          <button class="ho-btn ho-btn--primary" type="submit">Submit claim</button>
          <a class="ho-btn ho-btn--ghost" href="<?php echo esc_url( $hub_biz ? $hub_biz['url'] : hub_url( 'search/' ) ); ?>">Cancel</a>
        </div>
      </div>
      <?php endif; ?>
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

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
<?php $hub_page = hub_submission_page( 'offer', $_GET ); $hub_result = hub_submission_result(); // phpcs:ignore WordPress.Security.NonceVerification ?>
<div class="ho-container dash">
  <?php hub_view_account_nav( 'offers', 'true' ); ?>

  <div class="dash-main">
    <nav class="crumbs mb-4" aria-label="Breadcrumb">
      <ol><li><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">My account</a></li><li><a href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>">Offers</a></li><li><span aria-current="page"><?php hub_view_submission_crumb( $hub_page ); ?></span></li></ol>
    </nav>
    <?php hub_view_submission_head( $hub_page ); ?>


    <!-- Simplified offer form (client feedback, Nov 2026): four short steps and a live preview. -->
    <?php hub_view_submission_notices( $hub_page, $hub_result ); ?>
    <div class="layout-aside offer-form">
      <?php if ( 'ready' === $hub_page['state'] ) : ?>
      <form class="form-card form" action="<?php echo esc_url( hub_view_submission_action( $hub_page ) ); ?>" method="post" enctype="multipart/form-data" data-offer-form>
        <?php wp_nonce_field( 'hub_submission', '_hub_nonce' ); ?>
        <?php if ( $hub_page['item'] ) : ?><input type="hidden" name="id" value="<?php echo (int) $hub_page['item']['id']; ?>"><?php endif; ?>

        <section class="form-step" aria-labelledby="st1">
          <h2 class="form-step__title" id="st1"><span>1</span>What&rsquo;s the offer?</h2>
          <div class="field">
            <label for="of-title">Headline</label>
            <input class="input" id="of-title" type="text" name="title" maxlength="70" required aria-describedby="of-title-help" data-preview="title" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'title' ) ); ?>">
            <p class="help" id="of-title-help">Lead with the deal, like &ldquo;20% off your first visit&rdquo;.</p>
          </div>
          <div class="field">
            <label for="of-desc">A line about it <span class="opt">(optional)</span></label>
            <input class="input" id="of-desc" type="text" name="description" maxlength="140" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'description' ) ); ?>">
          </div>
        </section>

        <section class="form-step" aria-labelledby="st2">
          <h2 class="form-step__title" id="st2"><span>2</span>When does it run?</h2>
          <div class="row row-2">
            <div class="field">
              <label for="of-start">From</label>
              <input class="input" id="of-start" type="date" name="start" required value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'start' ) ); ?>">
            </div>
            <div class="field">
              <label for="of-end">Until</label>
              <input class="input" id="of-end" type="date" name="end" required data-preview="ends" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'end' ) ); ?>">
            </div>
          </div>
          <p class="help mb-0">It comes off the Hub automatically after the last day.</p>
        </section>

        <section class="form-step" aria-labelledby="st3">
          <h2 class="form-step__title" id="st3"><span>3</span>How do people claim it?</h2>
          <div class="row row-2">
            <div class="field">
              <label for="of-code">Code <span class="opt">(optional)</span></label>
              <input class="input" id="of-code" type="text" name="code" maxlength="20" placeholder="e.g. BEARD24" data-case="upper" data-preview="code" value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'code' ) ); ?>">
            </div>
            <div class="field">
              <label for="of-redeem">How to claim</label>
              <input class="input" id="of-redeem" type="text" name="redeem" maxlength="100" required value="<?php echo esc_attr( hub_view_submission_value( $hub_page, 'redeem' ) ); ?>">
            </div>
          </div>
          <div class="field">
            <label for="of-terms">Terms <span class="opt">(one per line)</span></label>
            <textarea class="textarea" id="of-terms" name="terms" rows="3" maxlength="500" required><?php echo esc_textarea( hub_view_submission_value( $hub_page, 'terms' ) ); ?></textarea>
          </div>
        </section>

        <section class="form-step" aria-labelledby="st4">
          <h2 class="form-step__title" id="st4"><span>4</span>Who can see it?</h2>
          <div class="choice-row">
            <label class="choice choice--pill"><input type="radio" name="audience" value="everyone" data-preview="audience" <?php checked( hub_view_submission_value( $hub_page, 'audience' ), 'everyone' ); ?>><span>Everyone</span></label>
            <label class="choice choice--pill"><input type="radio" name="audience" value="subscribers" data-preview="audience" <?php checked( hub_view_submission_value( $hub_page, 'audience' ), 'subscribers' ); ?>><span><svg class="ho-icon ho-icon--sm"><use href="#i-lock"/></svg>Newsletter subscribers</span></label>
          </div>
          <p class="help mb-0">Subscriber offers keep the code hidden until a reader confirms their email.</p>
        </section>

        <div class="form-actions form-actions--between">
          <button class="ho-btn ho-btn--outline" type="submit" name="action" value="draft">Save draft</button>
          <button class="ho-btn ho-btn--primary" type="submit" name="action" value="submit"><svg class="ho-icon"><use href="#i-check"/></svg>Send for approval</button>
        </div>
      </form>
      <?php endif; ?>

      <aside class="sticky offer-form__aside" aria-label="Preview">
        <p class="eyebrow">Preview</p>
        <div class="offer-card offer-card--preview" aria-live="polite">
          <span class="offer-card__top"><span class="biz-card__cat">Hair &amp; Beauty</span><span class="card__tick card__tick--sub" data-preview-out="sub" hidden role="img" aria-label="Subscribers only" data-tip="Subscribers only"><svg class="ho-icon"><use href="#i-lock"/></svg></span></span>
          <strong class="offer-card__value" data-preview-out="value">FREE BEARD</strong>
          <span class="offer-card__title" data-preview-out="title">Free beard trim with any haircut</span>
          <span class="offer-card__biz"><?php echo esc_html( $hub_page['by'] ); ?></span>
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

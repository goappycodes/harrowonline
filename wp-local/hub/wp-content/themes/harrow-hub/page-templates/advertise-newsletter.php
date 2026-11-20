<?php
/**
 * Advertise in the Harrow Online newsletter: book an advert. URL: /advertise/newsletter/
 * Every text is the page's "Page text" box (Pages > Advertise in the newsletter); the prices come from the billing
 * module and the dates from today's date. Data: hub_pages_advert() (app/modules/pages).
 * The booking itself (holds, payment, the advert's review) isn't built yet: the form posts to /wp-json/ho/v1/sponsor/checkout.
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-sponsor',
	'description' => 'Book an advert in the Harrow Online Daily News, The Weekend or Harrow Business newsletter. Pick dates, add your logo and pay online.',
	'nav' => 'advertise',
	'title' => 'Advertise in the Harrow Online newsletter: book an advert',   // page title and description: Yoast SEO box, else these
) );
get_header();

$hub_a  = hub_pages_advert();
$hub_t  = $hub_a['text'];
$hub_pr = $hub_a['prices'];
$hub_ac = $hub_a['account'];
$hub_e  = function ( $k ) use ( $hub_t ) { return esc_html( $hub_t[ $k ] ); };   // a text field, escaped
$hub_u  = function ( $k ) use ( $hub_t ) { return esc_url( $hub_t[ $k ] ); };    // a link field, escaped
$hub_key = function () use ( $hub_e ) {   // the calendar key
	echo '<div class="cal-legend sp-legend" aria-label="Key">'
		. '<span><i class="a"></i>' . $hub_e( 'advertise_key_open' ) . '</span>'
		. '<span><i class="f"></i>' . $hub_e( 'advertise_key_selected' ) . '</span>'
		. '<span><i class="t"></i>' . $hub_e( 'advertise_key_booked' ) . '</span>'
		. '<span><i class="h"></i>' . $hub_e( 'advertise_key_held' ) . '</span></div>';
};
$hub_slots = function ( array $slots, array $price ) use ( $hub_t ) {   // one calendar's buttons
	foreach ( $slots as $s ) {
		$mon = ( $s['week'] ? 'w/c ' : '' ) . $s['mon'];
		if ( 'open' === $s['state'] ) {
			echo '<button type="button" class="slot" aria-pressed="false" aria-label="' . esc_attr( $s['label'] . ', ' . $price['label'] ) . '"><small>' . esc_html( $mon ) . '</small><b>' . esc_html( $s['day'] ) . '</b><small>' . esc_html( $price['label'] ) . "</small></button>\n";
		} else {
			$why = 'no_issue' === $s['state'] ? $hub_t['advertise_label_no_issue'] : $hub_t['advertise_label_closed'];
			echo '<button type="button" class="slot is-taken" aria-disabled="true" aria-label="' . esc_attr( $s['label'] . ', ' . strtolower( $why ) ) . '"><small>' . esc_html( $mon ) . '</small><b>' . esc_html( $s['day'] ) . '</b><small>' . esc_html( $why ) . "</small></button>\n";
		}
	}
};
?>
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><a href="<?php echo $hub_u( 'advertise_crumb_parent_link' ); ?>"><?php echo $hub_e( 'advertise_crumb_parent' ); ?></a></li><li><span aria-current="page"><?php echo $hub_e( 'advertise_crumb' ); ?></span></li></ol>
</nav>

<header class="page-head ho-container sp-head">
  <div>
    <h1 class="page-head__title"><?php echo $hub_e( 'advertise_title' ); ?></h1>
    <p class="page-head__lead"><?php echo $hub_e( 'advertise_lead' ); ?></p>
  </div>
  <ul class="sp-facts" aria-label="At a glance">
<?php foreach ( $hub_t['advertise_facts'] as $hub_f ) : ?>
    <li><svg class="ho-icon"><use href="#i-<?php echo esc_attr( sanitize_key( $hub_f['icon'] ) ); ?>"/></svg><span><strong><?php echo esc_html( $hub_f['title'] ); ?></strong><?php echo esc_html( $hub_f['text'] ); ?></span></li>
<?php endforeach; ?>
  </ul>
</header>

<div class="ho-container">
  <div class="sp-who">
<?php if ( $hub_ac['signed_in'] ) : ?>
    <p><svg class="ho-icon ho-icon--sm"><use href="#i-user"/></svg><?php echo $hub_e( 'advertise_signed_in' ); ?> <strong><?php echo esc_html( $hub_ac['name'] ); ?></strong><?php echo '' !== $hub_ac['plan_label'] ? ' (' . esc_html( $hub_ac['plan_label'] ) . ')' : ''; ?> &middot; <a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>"><?php echo $hub_e( 'advertise_account_label' ); ?></a></p>
<?php else : ?>
    <p><svg class="ho-icon ho-icon--sm"><use href="#i-user"/></svg><strong><?php echo $hub_e( 'advertise_signed_out_title' ); ?></strong> <?php echo $hub_e( 'advertise_signed_out_text' ); ?> <a href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( hub_url( 'advertise/newsletter/' ) ), hub_url( 'sign-in/' ) ) ); ?>"><?php echo $hub_e( 'advertise_sign_in_label' ); ?></a> or <a href="<?php echo esc_url( hub_url( 'register/' ) ); ?>"><?php echo $hub_e( 'advertise_register_label' ); ?></a>.</p>
<?php endif; ?>
    <span class="cluster"><a class="link-quiet" href="<?php echo $hub_u( 'advertise_sponsor_link' ); ?>"><?php echo $hub_e( 'advertise_sponsor_label' ); ?></a><a class="link-quiet" href="<?php echo $hub_u( 'advertise_other_link' ); ?>"><?php echo $hub_e( 'advertise_other_label' ); ?></a></span>
  </div>

  <ol class="sp-steps" aria-label="Booking steps">
    <li class="is-current" aria-current="step"><span class="sp-steps__n">1</span><span><small>Step 1</small><?php echo $hub_e( 'advertise_step1' ); ?></span></li>
    <li><span class="sp-steps__n">2</span><span><small>Step 2</small><?php echo $hub_e( 'advertise_step2' ); ?></span></li>
    <li><span class="sp-steps__n">3</span><span><small>Step 3</small><?php echo $hub_e( 'advertise_step3' ); ?></span></li>
  </ol>

  <form class="layout-aside sp-form" action="<?php echo esc_url( hub_url( 'wp-json/ho/v1/sponsor/checkout' ) ); ?>" method="post" enctype="multipart/form-data">
    <div class="form-card claim-form sp-card">
      <section class="form-step" aria-labelledby="s1-title">
        <h2 class="form-step__title sp-title" id="s1-title"><span>1</span><?php echo $hub_e( 'advertise_s1_title' ); ?></h2>
        <p class="help mt-0"><?php echo $hub_e( 'advertise_s1_help' ); ?></p>

        <fieldset class="fieldset">
          <legend><?php echo $hub_e( 'advertise_edition_legend' ); ?></legend>
          <div class="choice-grid choice-grid--3">
            <label class="choice">
              <input type="radio" name="edition" value="daily" checked data-reveal="#ed-daily">
              <span>
                <span class="choice__title"><?php echo $hub_e( 'advertise_daily_name' ); ?></span>
                <span class="choice__text"><?php echo $hub_e( 'advertise_daily_text' ); ?></span><span class="choice__price"><strong><?php echo esc_html( $hub_pr['day']['label'] ); ?></strong> <?php echo $hub_e( 'advertise_a_day' ); ?> &middot; <strong><?php echo esc_html( $hub_pr['week']['label'] ); ?></strong> <?php echo $hub_e( 'advertise_a_week' ); ?></span>
              </span>
            </label>
            <label class="choice">
              <input type="radio" name="edition" value="weekend" data-reveal="#ed-weekend">
              <span>
                <span class="choice__title"><?php echo $hub_e( 'advertise_weekend_name' ); ?></span>
                <span class="choice__text"><?php echo $hub_e( 'advertise_weekend_text' ); ?></span><span class="choice__price"><strong><?php echo esc_html( $hub_pr['weekend']['label'] ); ?></strong> <?php echo $hub_e( 'advertise_an_issue' ); ?></span>
              </span>
            </label>
            <label class="choice">
              <input type="radio" name="edition" value="business" data-reveal="#ed-business">
              <span>
                <span class="choice__title"><?php echo $hub_e( 'advertise_business_name' ); ?></span>
                <span class="choice__text"><?php echo $hub_e( 'advertise_business_text' ); ?></span><span class="choice__price"><strong><?php echo esc_html( $hub_pr['business']['label'] ); ?></strong> <?php echo $hub_e( 'advertise_an_issue' ); ?></span>
              </span>
            </label>
          </div>
        </fieldset>

        <!-- Panels are in this DOM order on purpose: hub.js updates the summary from each [data-slots] on load,
             so the default panel (Daily News, single days) comes last and sets the summary. -->
        <div id="ed-weekend" class="mt-5" hidden>
          <?php $hub_key(); ?>
          <h3><?php echo $hub_e( 'advertise_weekend_heading' ); ?></h3>
          <div class="slots" data-slots data-price="<?php echo esc_attr( $hub_pr['weekend']['number'] ); ?>" role="group" aria-label="<?php echo esc_attr( $hub_t['advertise_weekend_name'] ); ?> issues">
<?php $hub_slots( $hub_a['calendars']['weekend'], $hub_pr['weekend'] ); ?>
          </div>
          <p class="help mt-2"><?php echo $hub_e( 'advertise_weekend_note' ); ?></p>
        </div>

        <div id="ed-business" class="mt-5" hidden>
          <?php $hub_key(); ?>
          <h3><?php echo $hub_e( 'advertise_business_heading' ); ?></h3>
          <div class="slots" data-slots data-price="<?php echo esc_attr( $hub_pr['business']['number'] ); ?>" role="group" aria-label="<?php echo esc_attr( $hub_t['advertise_business_name'] ); ?> issues">
<?php $hub_slots( $hub_a['calendars']['business'], $hub_pr['business'] ); ?>
          </div>
          <p class="help mt-2"><?php echo $hub_e( 'advertise_business_note' ); ?></p>
        </div>

        <div id="ed-daily" class="mt-5">
          <fieldset class="fieldset">
            <legend class="ho-sr">Book by day or by week</legend>
            <div class="choice-grid choice-grid--2">
              <label class="choice">
                <input type="radio" name="length" value="day" checked data-reveal="#daily-days">
                <span><span class="choice__title"><?php echo $hub_e( 'advertise_days_title' ); ?></span><span class="choice__text"><?php echo $hub_e( 'advertise_days_text' ); ?></span></span>
              </label>
              <label class="choice">
                <input type="radio" name="length" value="week" data-reveal="#daily-weeks">
                <span><span class="choice__title"><?php echo $hub_e( 'advertise_week_title' ); ?> <span class="badge badge--red"><?php echo $hub_e( 'advertise_week_badge' ); ?></span></span><span class="choice__text"><?php echo $hub_e( 'advertise_week_text' ); ?></span></span>
              </label>
            </div>
          </fieldset>

          <div id="daily-weeks" class="mt-5" hidden>
            <?php $hub_key(); ?>
            <h3><?php echo $hub_e( 'advertise_weeks_heading' ); ?></h3>
            <div class="slots" data-slots data-price="<?php echo esc_attr( $hub_pr['week']['number'] ); ?>" role="group" aria-label="<?php echo esc_attr( $hub_t['advertise_daily_name'] ); ?> weeks">
<?php $hub_slots( $hub_a['calendars']['weeks'], $hub_pr['week'] ); ?>
            </div>
            <p class="help mt-2"><?php echo $hub_e( 'advertise_weeks_note' ); ?></p>
          </div>

          <div id="daily-days" class="mt-5">
            <?php $hub_key(); ?>
            <h3><?php echo $hub_e( 'advertise_days_heading' ); ?></h3>
            <div class="slots slots--weekdays" data-slots data-price="<?php echo esc_attr( $hub_pr['day']['number'] ); ?>" role="group" aria-label="<?php echo esc_attr( $hub_t['advertise_daily_name'] ); ?> dates">
            <span class="slots__dow" aria-hidden="true">Mon</span><span class="slots__dow" aria-hidden="true">Tue</span><span class="slots__dow" aria-hidden="true">Wed</span><span class="slots__dow" aria-hidden="true">Thu</span><span class="slots__dow" aria-hidden="true">Fri</span>
<?php $hub_slots( $hub_a['calendars']['days'], $hub_pr['day'] ); ?>
            </div>
            <p class="help mt-2"><?php echo $hub_e( 'advertise_days_note' ); ?></p>
          </div>
        </div>
      </section>

      <section class="form-step" aria-labelledby="s2-title">
        <h2 class="form-step__title sp-title" id="s2-title"><span>2</span><?php echo $hub_e( 'advertise_s2_title' ); ?></h2>
        <p class="help mt-0"><?php echo $hub_e( 'advertise_s2_help' ); ?></p>
        <div class="form">
          <div class="field">
            <label for="sp-name"><?php echo $hub_e( 'advertise_name_label' ); ?></label>
            <input class="input" id="sp-name" name="sponsor_name" type="text" value="<?php echo esc_attr( $hub_ac['name'] ); ?>" required>
          </div>
          <div class="field">
            <span class="field__label" id="sp-logo-label"><?php echo $hub_e( 'advertise_logo_label' ); ?></span>
            <label class="dropzone" for="sp-logo">
              <svg class="ho-icon"><use href="#i-upload"/></svg>
              <strong><?php echo $hub_e( 'advertise_logo_title' ); ?></strong>
              <p><?php echo $hub_e( 'advertise_logo_text' ); ?></p>
            </label>
            <input class="ho-sr" id="sp-logo" name="logo" type="file" accept="image/png,image/jpeg" aria-labelledby="sp-logo-label" required>
          </div>
          <div class="field">
            <label for="sp-msg"><?php echo $hub_e( 'advertise_message_label' ); ?> <span class="opt"><?php echo $hub_e( 'advertise_message_limit' ); ?></span></label>
            <textarea class="textarea" id="sp-msg" name="message" maxlength="140" rows="3" data-counter required></textarea>
            <p class="help"><?php echo $hub_e( 'advertise_message_help' ); ?></p>
          </div>
          <div class="field">
            <label for="sp-url"><?php echo $hub_e( 'advertise_link_label' ); ?></label>
            <input class="input" id="sp-url" name="link" type="url" value="<?php echo esc_attr( $hub_ac['website'] ); ?>" placeholder="https://" required>
            <p class="help"><?php echo $hub_e( 'advertise_link_help' ); ?></p>
          </div>
        </div>
      </section>
    </div>

    <aside class="sticky stack" aria-label="Preview and order summary">
      <div>
        <p class="eyebrow mb-2"><?php echo $hub_e( 'advertise_preview_label' ); ?></p>
        <div class="nl-preview" aria-label="How your advert looks in the newsletter">
          <div class="nl-preview__mast"><span><?php echo $hub_e( 'advertise_preview_mast' ); ?></span><small><?php echo esc_html( $hub_a['preview_date'] ); ?></small></div>
          <div class="nl-preview__body">
            <div class="nl-preview__sponsor">
              <div class="ph"><svg class="ho-icon"><use href="#i-<?php echo esc_attr( sanitize_key( $hub_ac['icon'] ) ); ?>"/></svg></div>
              <div><strong><?php echo esc_html( '' !== $hub_ac['name'] ? $hub_ac['name'] : $hub_t['advertise_preview_name'] ); ?></strong> &middot; <a href="#sp-url"><?php echo $hub_e( 'advertise_preview_message' ); ?> &rarr;</a></div>
              <span class="nl-preview__tag"><?php echo $hub_e( 'advertise_preview_tag' ); ?></span>
            </div>
            <p class="nl-preview__hello"><?php echo $hub_e( 'advertise_preview_hello' ); ?></p>
            <div aria-hidden="true"><div class="nl-preview__line"></div><div class="nl-preview__line"></div><div class="nl-preview__line"></div><div class="nl-preview__line"></div></div>
          </div>
        </div>
        <p class="help mt-2"><?php echo $hub_e( 'advertise_preview_help' ); ?></p>
      </div>

      <div class="add-biz__panel sp-order">
        <h2 class="form-step__title sp-title"><span>3</span><?php echo $hub_e( 'advertise_s3_title' ); ?></h2>
        <ul class="summary">
          <li><span><?php echo $hub_e( 'advertise_sum_edition' ); ?></span><strong><?php echo $hub_e( 'advertise_daily_name' ); ?></strong></li>
          <li><span><?php echo $hub_e( 'advertise_sum_dates' ); ?></span><strong><span data-slot-count>0</span></strong></li>
          <li><span><?php echo $hub_e( 'advertise_sum_each' ); ?></span><span><?php echo esc_html( $hub_pr['day']['each'] ); ?></span></li>
          <li class="total"><span><?php echo $hub_e( 'advertise_sum_total' ); ?></span><span data-slot-total>£0.00</span></li>
        </ul>
        <p class="help mt-2"><?php echo $hub_e( 'advertise_vat' ); ?></p>
        <p class="sp-tip"><svg class="ho-icon ho-icon--sm"><use href="#i-star"/></svg><span><strong><?php echo $hub_e( 'advertise_partner_title' ); ?></strong> <?php echo $hub_e( 'advertise_partner_text' ); ?></span></p>
        <button class="ho-btn ho-btn--primary ho-btn--block mt-4" type="submit"><svg class="ho-icon"><use href="#i-lock"/></svg><?php echo $hub_e( 'advertise_pay' ); ?></button>
        <ul class="ticks small mt-5 mb-0">
<?php foreach ( $hub_t['advertise_ticks'] as $hub_line ) : ?>
          <li><?php echo esc_html( $hub_line ); ?></li>
<?php endforeach; ?>
        </ul>
      </div>
      <p class="small muted"><?php echo $hub_e( 'advertise_questions' ); ?> <a href="<?php echo $hub_u( 'advertise_questions_link' ); ?>"><?php echo $hub_e( 'advertise_questions_label' ); ?></a>.</p>
    </aside>
  </form>
</div>

<?php if ( ! $hub_t['advertise_sponsor_hide'] ) : ?>
<section class="section section--soft" id="main-sponsor" aria-labelledby="ms-title">
  <div class="ho-container">
    <div class="cta-band">
      <div>
        <p class="eyebrow"><?php echo $hub_e( 'advertise_sponsor_eyebrow' ); ?></p>
        <h2 id="ms-title"><?php echo $hub_e( 'advertise_sponsor_title' ); ?></h2>
        <p><?php echo $hub_e( 'advertise_sponsor_text' ); ?></p>
      </div>
      <div class="cta-band__actions">
        <a class="ho-btn ho-btn--primary" href="<?php echo $hub_u( 'advertise_sponsor_button_link' ); ?>"><?php echo $hub_e( 'advertise_sponsor_button' ); ?></a>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
<?php get_footer();

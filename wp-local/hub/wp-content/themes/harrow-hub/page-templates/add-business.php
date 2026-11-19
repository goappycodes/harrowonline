<?php
/**
 * Add a business (from src/pages/add-business.html; hand-edited: the form creates a "Pending Review" business through
 * the businesses module, and staff publish it in wp-admin). Signed-out visitors sign in first (app/setup/add-business.php).
 * URL: /add-business/   ?recommend=1 a reader suggesting a business they don't own   ?sent=owner|recommend&added={name} after sending
 */
defined( 'ABSPATH' ) || exit;
$hub_result = null;
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$hub_result = hub_business_add( $_POST, $_FILES ); // phpcs:ignore WordPress.Security.NonceVerification -- the controller checks it
	if ( $hub_result['ok'] ) {
		wp_safe_redirect( $hub_result['redirect'] );
		exit;
	}
}
$hub_page  = hub_business_add_page( $_REQUEST ); // phpcs:ignore WordPress.Security.NonceVerification
$hub_rec   = $hub_page['recommend'];
$hub_owner = ! $hub_rec;
$hub_sent  = isset( $_GET['sent'] ) ? sanitize_key( $_GET['sent'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$hub_name  = isset( $_GET['added'] ) ? sanitize_text_field( wp_unslash( $_GET['added'] ) ) : '';   // not ?name=: WordPress reads it as a page slug // phpcs:ignore WordPress.Security.NonceVerification
$hub_err   = function ( $f ) use ( $hub_result ) { hub_view_field_error( $hub_result, $f ); };
$hub_cls   = function ( $f ) use ( $hub_result ) { echo esc_attr( hub_view_field_class( $hub_result, $f ) ); };
$hub_val   = function ( $f ) { echo esc_attr( hub_view_posted( $f ) ); };
$hub_post  = 'POST' === $_SERVER['REQUEST_METHOD']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
$hub_arr   = function ( $k ) { return isset( $_POST[ $k ] ) ? (array) wp_unslash( $_POST[ $k ] ) : array(); }; // phpcs:ignore WordPress.Security.NonceVerification
$hub_extra = $hub_arr( 'cat_extra' );
$hub_fac   = $hub_arr( 'facilities' );
$hub_hours = $hub_arr( 'hours' );
$hub_title = $hub_rec ? 'Recommend a business' : 'Add your business';
// Default opening hours on a fresh form, as in the prototype
$hub_default = array( 'sat' => array( '10:00', '16:00' ), 'sun' => array( '', '' ) );
$hub_cat_options = function ( $selected, $none ) use ( $hub_page ) {
	echo '<option value="">' . esc_html( $none ) . '</option>';
	foreach ( $hub_page['categories'] as $c ) {
		echo '<option value="' . esc_attr( $c['slug'] ) . '"' . selected( $selected, $c['slug'], false ) . '>' . esc_html( $c['name'] ) . '</option>';
	}
};
hub_set_page( array(
	'description' => 'Add your business to Harrow Online Hub for free. Our team checks every listing before it goes live.',
	'hubnav' => 'directory',
	'robots' => 'noindex, follow',
	'title' => $hub_title,
) );
get_header();
?>
<!-- Add a business (cleaned up, Nov 2026): search first, then five short numbered steps; optional extras folded away;
     "What happens next" beside the form. ?recommend=1 (homepage "Recommend a business") switches the wording for
     readers suggesting a business they don't own. -->
<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol><li><a href="<?php echo esc_url( hub_url( '' ) ); ?>">Hub</a></li><li><a href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Plans</a></li><li><span aria-current="page"><?php echo esc_html( $hub_title ); ?></span></li></ol></nav>

<header class="page-head ho-container">
  <h1 class="page-head__title"><?php echo esc_html( $hub_title ); ?></h1>
  <p class="page-head__lead"><?php echo $hub_rec ? 'Know a Harrow business that&rsquo;s missing? Tell us and we&rsquo;ll add it. Our team checks every one first.' : 'Free to list and takes about two minutes. Our team checks it before it goes live.'; ?></p>
</header>

<div class="ho-container add-biz">
  <div class="add-biz__main">
    <?php if ( '' !== $hub_sent ) : ?>
    <div class="form-card add-biz__done" role="status">
      <h2><?php echo 'recommend' === $hub_sent ? 'Thanks for the recommendation' : 'Thanks, we&rsquo;re checking it'; ?></h2>
      <p>We&rsquo;ve got <strong><?php echo esc_html( $hub_name ); ?></strong>. A member of the Harrow Online team will check it, usually within 2 working days, and email you either way.</p>
      <div class="cluster">
        <a class="ho-btn ho-btn--primary" href="<?php echo esc_url( hub_url( '' ) ); ?>">Back to the Hub</a>
        <?php if ( 'recommend' === $hub_sent ) : ?>
        <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'add-business/?recommend=1' ) ); ?>">Recommend another</a>
        <?php else : ?>
        <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">Go to my account</a>
        <?php endif; ?>
      </div>
    </div>
    <?php else : ?>

    <form class="add-biz__check" action="<?php echo esc_url( hub_url( 'search/' ) ); ?>" method="get" role="search">
      <input type="hidden" name="type" value="businesses">
      <div>
        <p class="add-biz__check-title"><svg class="ho-icon"><use href="#i-search"/></svg>Is it already on the Hub?</p>
        <p class="add-biz__check-text">Most Harrow businesses are listed. If yours is, claim it instead: it&rsquo;s quicker.</p>
      </div>
      <div class="add-biz__check-row">
        <label class="ho-sr" for="a-find">Business name</label>
        <input class="input" id="a-find" type="search" name="q" placeholder="Search by business name" autocomplete="off">
        <button class="ho-btn ho-btn--dark" type="submit">Search</button>
      </div>
    </form>

    <?php if ( 'ready' !== $hub_page['state'] ) : ?>
    <div class="notice notice--warn" role="status"><svg class="ho-icon"><use href="#i-alert"/></svg><div>
      <p><?php echo wp_kses( $hub_page['message'], array() ); ?></p>
      <p class="mb-0"><a href="<?php echo esc_url( hub_url( 'account/' ) ); ?>">Go to my account</a><?php if ( 'has_business' === $hub_page['state'] ) : ?> &middot; <a href="<?php echo esc_url( hub_url( 'add-business/?recommend=1' ) ); ?>">Recommend a business instead</a><?php endif; ?></p>
    </div></div>
    <?php else : ?>

    <form class="form form-card add-biz__form" action="<?php echo esc_url( hub_url( 'add-business/' . ( $hub_rec ? '?recommend=1' : '' ) ) ); ?>" method="post" enctype="multipart/form-data">
      <?php wp_nonce_field( 'hub_add_business', '_hub_nonce' ); ?>
      <?php if ( $hub_rec ) : ?><input type="hidden" name="recommend" value="1"><?php endif; ?>
      <?php hub_view_form_notice( $hub_result ); ?>
      <?php if ( $hub_result && ! empty( $hub_result['duplicate'] ) ) : $hub_d = $hub_result['duplicate']; ?>
      <div class="notice notice--warn" role="status"><svg class="ho-icon"><use href="#i-alert"/></svg><div>
        <p><strong>This looks like it&rsquo;s already listed:</strong> <a href="<?php echo esc_url( $hub_d['url'] ); ?>"><?php echo esc_html( $hub_d['name'] ); ?></a>.</p>
        <?php if ( $hub_owner ) : ?><p class="mb-0">If it&rsquo;s yours, <a href="<?php echo esc_url( $hub_d['claim'] ); ?>">claim it instead</a>.</p><?php endif; ?>
      </div></div>
      <?php endif; ?>

      <section class="form-step" aria-labelledby="ab1">
        <h2 class="form-step__title" id="ab1"><span>1</span>The basics</h2>
        <div class="field<?php $hub_cls( 'business_name' ); ?>">
          <label for="a-name">Business name</label>
          <input class="input" id="a-name" type="text" name="business_name" required maxlength="100" autocomplete="organization" placeholder="For example, Station Road Barbers" value="<?php $hub_val( 'business_name' ); ?>">
          <?php $hub_err( 'business_name' ); ?>
          <p class="help">The name on the sign, without the area.</p>
        </div>
        <div class="row row-2">
          <div class="field<?php $hub_cls( 'cat' ); ?>">
            <label for="a-cat">Category</label>
            <select class="select" id="a-cat" name="cat" required><?php $hub_cat_options( hub_view_posted( 'cat' ), 'Choose a category' ); ?></select>
            <?php $hub_err( 'cat' ); ?>
          </div>
          <div class="field<?php $hub_cls( 'business_type' ); ?>">
            <label for="a-type">Business type</label>
            <input class="input" id="a-type" type="text" name="business_type" required placeholder="For example, Barber shop" value="<?php $hub_val( 'business_type' ); ?>">
            <?php $hub_err( 'business_type' ); ?>
          </div>
        </div>
        <details class="add-biz__more"<?php echo array_filter( $hub_extra ) ? ' open' : ''; ?>>
          <summary><svg class="ho-icon ho-icon--sm"><use href="#i-plus"/></svg>Add more categories <span class="opt">(optional)</span></summary>
          <div class="row row-2 mt-3">
            <div class="field">
              <label for="a-cat2">Second category</label>
              <select class="select" id="a-cat2" name="cat_extra[]"><?php $hub_cat_options( isset( $hub_extra[0] ) ? $hub_extra[0] : '', 'None' ); ?></select>
            </div>
            <div class="field">
              <label for="a-cat3">Third category</label>
              <select class="select" id="a-cat3" name="cat_extra[]"><?php $hub_cat_options( isset( $hub_extra[1] ) ? $hub_extra[1] : '', 'None' ); ?></select>
            </div>
          </div>
        </details>
        <div class="field<?php $hub_cls( 'summary' ); ?>">
          <label for="a-summary">One-line summary</label>
          <input class="input" id="a-summary" type="text" name="summary" maxlength="120" required data-counter placeholder="For example, Classic cuts and hot towel shaves, six days a week." value="<?php $hub_val( 'summary' ); ?>">
          <?php $hub_err( 'summary' ); ?>
          <p class="help">Shown on the card in search and category pages.</p>
        </div>
      </section>

      <section class="form-step" aria-labelledby="ab2">
        <h2 class="form-step__title" id="ab2"><span>2</span>Where it is</h2>
        <div class="field<?php $hub_cls( 'address' ); ?>">
          <label for="a-addr">Street address</label>
          <input class="input" id="a-addr" type="text" name="address" required autocomplete="address-line1" placeholder="309 Station Road" value="<?php $hub_val( 'address' ); ?>">
          <?php $hub_err( 'address' ); ?>
        </div>
        <div class="row row-2">
          <div class="field<?php $hub_cls( 'postcode' ); ?>">
            <label for="a-pc">Postcode</label>
            <input class="input" id="a-pc" type="text" name="postcode" required autocomplete="postal-code" placeholder="HA1 2TA" value="<?php $hub_val( 'postcode' ); ?>">
            <?php $hub_err( 'postcode' ); ?>
          </div>
          <div class="field<?php $hub_cls( 'area' ); ?>">
            <label for="a-area">Area</label>
            <select class="select" id="a-area" name="area" required>
              <option value="">Choose an area</option>
              <?php foreach ( $hub_page['areas'] as $hub_a ) : ?>
              <option value="<?php echo esc_attr( $hub_a['slug'] ); ?>" <?php selected( hub_view_posted( 'area' ), $hub_a['slug'] ); ?>><?php echo esc_html( $hub_a['name'] ); ?></option>
              <?php endforeach; ?>
            </select>
            <?php $hub_err( 'area' ); ?>
          </div>
        </div>
      </section>

      <section class="form-step" aria-labelledby="ab3">
        <h2 class="form-step__title" id="ab3"><span>3</span>Contact details</h2>
        <div class="row row-2">
          <div class="field<?php $hub_cls( 'phone' ); ?>">
            <label for="a-phone">Phone</label>
            <input class="input" id="a-phone" type="tel" name="phone" required autocomplete="tel" placeholder="020 8123 4567" value="<?php $hub_val( 'phone' ); ?>">
            <?php $hub_err( 'phone' ); ?>
          </div>
          <div class="field<?php $hub_cls( 'website' ); ?>">
            <label for="a-web">Website <span class="opt">(optional)</span></label>
            <input class="input" id="a-web" type="url" name="website" autocomplete="url" placeholder="https://" value="<?php $hub_val( 'website' ); ?>">
            <?php $hub_err( 'website' ); ?>
          </div>
        </div>
        <?php if ( $hub_owner ) : ?>
        <div class="field<?php $hub_cls( 'email' ); ?>">
          <label for="a-email">Email for customers <span class="opt">(optional)</span></label>
          <input class="input" id="a-email" type="email" name="email" autocomplete="email" placeholder="hello@yourbusiness.co.uk" value="<?php $hub_val( 'email' ); ?>">
          <?php $hub_err( 'email' ); ?>
        </div>
        <?php endif; ?>
      </section>

      <section class="form-step" aria-labelledby="ab4">
        <h2 class="form-step__title" id="ab4"><span>4</span>About the business</h2>
        <div class="field<?php $hub_cls( 'description' ); ?>">
          <label for="a-desc">Description</label>
          <textarea class="textarea" id="a-desc" name="description" maxlength="1500" rows="5" required data-counter aria-describedby="a-desc-help" placeholder="What it does, who for and what makes it different."><?php echo esc_textarea( hub_view_posted( 'description' ) ); ?></textarea>
          <?php $hub_err( 'description' ); ?>
          <p class="help" id="a-desc-help">At least 50 words helps it show up in Google. No prices or deals here: those go in Local Offers.</p>
        </div>
        <?php if ( $hub_owner ) : ?>
        <fieldset class="facilities">
          <legend>Facilities <span class="opt">(tick all that apply)</span></legend>
          <ul class="check-list facilities__list">
            <?php foreach ( HUB_BUSINESSES_FACILITIES as $hub_v => $hub_l ) : ?>
            <li><label class="check"><input type="checkbox" name="facilities[]" value="<?php echo esc_attr( $hub_v ); ?>" <?php checked( in_array( $hub_v, $hub_fac, true ) ); ?>> <?php echo esc_html( $hub_l ); ?></label></li>
            <?php endforeach; ?>
          </ul>
        </fieldset>
        <?php endif; ?>
      </section>

      <?php if ( $hub_owner ) : ?>
      <section class="form-step" aria-labelledby="ab5">
        <h2 class="form-step__title" id="ab5"><span>5</span>Opening hours <small class="form-step__opt">(optional)</small></h2>
        <div class="hours-edit">
          <?php foreach ( HUB_BUSINESSES_DAYS as $hub_k => $hub_day ) :
            $hub_h      = isset( $hub_hours[ $hub_k ] ) ? (array) $hub_hours[ $hub_k ] : array();
            $hub_d      = isset( $hub_default[ $hub_k ] ) ? $hub_default[ $hub_k ] : array( '09:00', '17:30' );
            $hub_open   = $hub_post ? ( isset( $hub_h['open'] ) ? $hub_h['open'] : '' ) : $hub_d[0];
            $hub_close  = $hub_post ? ( isset( $hub_h['close'] ) ? $hub_h['close'] : '' ) : $hub_d[1];
            $hub_closed = $hub_post ? ! empty( $hub_h['closed'] ) : 'sun' === $hub_k;
            ?>
            <span class="day"><?php echo esc_html( $hub_day ); ?></span><input class="input input--sm" type="time" name="hours[<?php echo esc_attr( $hub_k ); ?>][open]" value="<?php echo esc_attr( $hub_open ); ?>" aria-label="<?php echo esc_attr( $hub_day ); ?> opening time"><input class="input input--sm" type="time" name="hours[<?php echo esc_attr( $hub_k ); ?>][close]" value="<?php echo esc_attr( $hub_close ); ?>" aria-label="<?php echo esc_attr( $hub_day ); ?> closing time"><label class="check"><input type="checkbox" name="hours[<?php echo esc_attr( $hub_k ); ?>][closed]" value="1" <?php checked( $hub_closed ); ?>> Closed</label>
          <?php endforeach; ?>
        </div>
        <p class="help">Tick Closed for days you don&rsquo;t open. Leave them out if they vary.</p>
      </section>

      <section class="form-step" aria-labelledby="ab6">
        <h2 class="form-step__title" id="ab6"><span>6</span>Photo <small class="form-step__opt">(optional)</small></h2>
        <div class="field<?php $hub_cls( 'photo' ); ?>">
          <label class="dropzone dropzone--compact" for="a-photo">
            <svg class="ho-icon"><use href="#i-upload"/></svg>
            <span><strong>Add a photo</strong><small>Shop front, team or your work. JPG or PNG, up to 8MB.</small></span>
            <input class="ho-sr" id="a-photo" type="file" name="photo" accept=".jpg,.jpeg,.png">
          </label>
          <?php $hub_err( 'photo' ); ?>
        </div>
        <p class="help">The free Basic listing shows one photo. A logo, more photos and social links come with Featured; add them from your account after you upgrade.</p>
      </section>
      <?php endif; ?>

      <div class="add-biz__submit">
        <label class="check<?php $hub_cls( 'declaration' ); ?>">
          <input type="checkbox" name="declaration" value="1" required <?php checked( '1', hub_view_posted( 'declaration' ) ); ?>>
          <span><?php if ( $hub_rec ) : ?>The details are correct as far as I know, and I agree to the <a href="<?php echo esc_url( hub_url( 'terms/' ) ); ?>">terms</a>.<?php else : ?>I own or manage this business, or have permission to list it, and I agree to the <a href="<?php echo esc_url( hub_url( 'terms/' ) ); ?>">terms</a>.<?php endif; ?></span>
        </label>
        <?php $hub_err( 'declaration' ); ?>
        <div class="add-biz__actions">
          <button class="ho-btn ho-btn--primary" type="submit"><?php echo $hub_rec ? 'Send recommendation' : 'Send for checking'; ?></button>
          <a class="ho-btn ho-btn--ghost" href="<?php echo esc_url( hub_url( $hub_rec ? '' : 'pricing/' ) ); ?>">Cancel</a>
        </div>
      </div>
    </form>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <aside class="add-biz__side" aria-label="What happens next">
    <div class="add-biz__panel">
      <h2 class="add-biz__panel-title">What happens next</h2>
      <ol class="add-biz__next">
        <li><span>1</span><div><strong>We check it</strong><small>Usually within 2 working days.</small></div></li>
        <li><span>2</span><div><strong>We email you</strong><small>Whether it&rsquo;s approved or we need something.</small></div></li>
        <?php if ( $hub_rec ) : ?>
        <li><span>3</span><div><strong>It goes live</strong><small>The owner can claim it free at any time.</small></div></li>
        <?php else : ?>
        <li><span>3</span><div><strong>It goes live, free</strong><small>Manage it from your account on the Basic plan.</small></div></li>
        <?php endif; ?>
      </ol>
      <?php if ( $hub_owner ) : ?><p class="add-biz__panel-note">Want offers, events and classes? <a href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">Upgrade any time</a>.</p><?php endif; ?>
    </div>
  </aside>
</div>
<?php get_footer();

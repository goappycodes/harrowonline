<?php
/**
 * Account settings (converted from src/pages/account-settings.html).
 * URL: /account/settings/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Your Harrow Online Hub account settings: name, email, password, newsletter preferences and account deletion.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'Account settings',
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
      <li><a href="<?php echo esc_url( hub_url( 'account/offers/' ) ); ?>"><svg class="ho-icon"><use href="#i-tag"/></svg>Offers<span class="num">1<span class="ho-sr"> offer needs changes</span></span></a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/events/' ) ); ?>"><svg class="ho-icon"><use href="#i-calendar"/></svg>Events</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/classes/' ) ); ?>"><svg class="ho-icon"><use href="#i-repeat"/></svg>Classes</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/newsletter/' ) ); ?>"><svg class="ho-icon"><use href="#i-megaphone"/></svg>Newsletter and promotions</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/billing/' ) ); ?>"><svg class="ho-icon"><use href="#i-card"/></svg>Plan and billing</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/settings/' ) ); ?>" aria-current="page"><svg class="ho-icon"><use href="#i-settings"/></svg>Settings</a></li>
    </ul>
    <hr>
    <ul>
      <li><a href="<?php echo esc_url( hub_view_sign_out_url() ); ?>"><svg class="ho-icon"><use href="#i-logout"/></svg>Sign out</a></li>
    </ul>
  </aside>

  <div class="dash-main">
    <div class="dash-head">
      <div>
        <h1>Settings</h1>
        <p>Your login details, emails and newsletter choices.</p>
      </div>
    </div>

    <?php
    get_template_part( 'parts/account/settings', null, array(
      'settings' => hub_account_settings(),
      'result'   => hub_account_settings_result(),
      'saved'    => isset( $_GET['saved'] ) ? sanitize_key( $_GET['saved'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification
      'email'    => isset( $_GET['email'] ) ? sanitize_key( $_GET['email'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification
    ) );
    ?>
  </div>
</div>
<?php get_footer();

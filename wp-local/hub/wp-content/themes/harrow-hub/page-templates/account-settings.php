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
  <?php hub_view_account_nav( 'settings' ); ?>

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

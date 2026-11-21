<?php
/**
 * Plan and billing (converted from src/pages/account-billing.html).
 * URL: /account/billing/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'is-logged-in',
	'description' => 'Your Harrow Online Hub plan and billing: change plan, switch to annual billing, update your card and download invoices.',
	'hubnav' => 'account',
	'nl' => 'off',
	'robots' => 'noindex, nofollow',
	'title' => 'Plan and billing',
) );
get_header();
?>
<div class="ho-container dash">
  <?php hub_view_account_nav( 'billing' ); ?>

  <div class="dash-main">
    <div class="dash-head">
      <div>
        <h1>Plan and billing</h1>
        <p><?php echo esc_html( hub_account_current()['business'] ? hub_account_current()['business']['name'] . '. ' : '' ); ?>Payments are handled securely by Stripe.</p>
      </div>
      <?php hub_view_billing_manage_button(); ?>
    </div>

    <?php hub_view_billing_failed_notice(); ?>

    <?php hub_view_billing_plan_card(); ?>
    <?php hub_view_billing_portal_note(); ?>
    <!-- WP: #stripe-portal = POST to our endpoint, create a Customer Portal session for this customer, redirect to session.url.
         Return URL: /hub/account/billing/. Plan changes arrive by webhook (customer.subscription.updated). -->

    <?php hub_view_billing_plans(); ?>

    <div class="grid grid-2 mt-6">
      <?php hub_view_billing_card(); ?>
      <section class="panel panel--soft" aria-labelledby="q-title">
        <h2 class="panel__title" id="q-title">Questions</h2>
        <details class="faq-mini">
          <summary>Have a discount code?</summary>
          <p class="small">Enter it on the Stripe payment page when you upgrade. Codes work on Hub plans only, not on featured spots or newsletter adverts. Already paying? <a href="https://harrowonline.org/contact-us/">Contact us</a> and we&rsquo;ll apply it to your next invoice.</p>
        </details>
        <details class="faq-mini">
          <summary>What happens if I cancel?</summary>
          <ul class="ticks ticks--dark small">
        <li><?php hub_view_billing_cancel_keep(); ?></li>
        <li>Then your listing moves to Basic. It stays verified and stays yours.</li>
        <li>Nothing is deleted. Extra photos, social links and offers are hidden, and come back if you upgrade again.</li>
        <li>Live offers end. Events stay published but lose any featured spot.</li>
      </ul>
          <a class="ho-btn ho-btn--sm ho-btn--outline mt-2" href="<?php echo esc_url( hub_url( 'billing/portal/' ) ); ?>">Cancel in Stripe</a>
        </details>
      </section>
    </div>

    <?php hub_view_billing_invoices(); ?>

  </div>
</div>
<?php get_footer();

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
      <li><a href="<?php echo esc_url( hub_url( 'account/billing/' ) ); ?>" aria-current="page"><svg class="ho-icon"><use href="#i-card"/></svg>Plan and billing</a></li>
      <li><a href="<?php echo esc_url( hub_url( 'account/settings/' ) ); ?>"><svg class="ho-icon"><use href="#i-settings"/></svg>Settings</a></li>
    </ul>
    <hr>
    <ul>
      <li><a href="<?php echo esc_url( hub_view_sign_out_url() ); ?>"><svg class="ho-icon"><use href="#i-logout"/></svg>Sign out</a></li>
    </ul>
  </aside>

  <div class="dash-main">
    <div class="dash-head">
      <div>
        <h1>Plan and billing</h1>
        <p><?php echo esc_html( hub_account_current()['business'] ? hub_account_current()['business']['name'] . '. ' : '' ); ?>Payments are handled securely by Stripe.</p>
      </div>
      <a class="ho-btn ho-btn--outline" href="<?php echo esc_url( hub_url( 'billing/portal/' ) ); ?>"><svg class="ho-icon"><use href="#i-card"/></svg>Manage billing</a>
    </div>

    <?php hub_view_billing_failed_notice(); ?>

    <?php hub_view_billing_plan_card(); ?>
    <p class="help mt-2"><strong>Manage billing</strong> opens the Stripe customer portal, where you can update your card, download invoices, switch between monthly and annual, change plan or cancel.</p>
    <!-- WP: #stripe-portal = POST to our endpoint, create a Customer Portal session for this customer, redirect to session.url.
         Return URL: /hub/account/billing/. Plan changes arrive by webhook (customer.subscription.updated). -->

    <section class="mt-6" aria-labelledby="plans-title" data-billing="monthly">
      <div class="split mb-4">
        <div>
          <h2 class="dash-h2" id="plans-title">Change plan</h2>
          <p class="small muted mb-0">Upgrade or downgrade any time. Stripe works out the difference for the rest of this billing period.</p>
        </div>
        <div class="billing-toggle" role="group" aria-label="Billing period">
          <button type="button" data-billing-set="monthly" aria-pressed="true">Monthly</button>
          <button type="button" data-billing-set="annual" aria-pressed="false">Annual <span class="save">2 months free</span></button>
        </div>
      </div>

      <div class="plans">
        <div class="plan">
          <h3 class="plan__name">Basic</h3>
          <p class="plan__for">For any Harrow business. Claim your listing and keep it up to date.</p>
          <p class="plan__price">&pound;0</p>
          <p class="plan__note">Free for ever</p>
          <a class="ho-btn ho-btn--outline ho-btn--block ho-btn--sm" href="<?php echo esc_url( hub_url( 'billing/portal/' ) ); ?>">Downgrade</a>
          <ul class="ticks small">
            <li>Verified business listing</li>
            <li>Business details and opening hours</li>
            <li>One photograph</li>
            <li>Keep the listing updated yourself</li>
          </ul>
        </div>
        <div class="plan plan--popular">
          <span class="plan__flag">Your plan</span>
          <h3 class="plan__name">Featured</h3>
          <p class="plan__for">For businesses that want to be seen first and keep customers coming back.</p>
          <p class="plan__price"><span class="price-monthly">&pound;29<small>/month</small></span><span class="price-annual">&pound;290<small>/year</small></span></p>
          <p class="plan__note"><span class="price-monthly">or &pound;290 a year</span><span class="price-annual">Save &pound;58</span></p>
          <span class="ho-btn ho-btn--dark ho-btn--block ho-btn--sm is-disabled" aria-disabled="true">Current plan</span>
          <ul class="ticks small">
            <li>Priority placement</li>
            <li>Social media links</li>
            <li>Additional photographs</li>
            <li>Publish Local Offers</li>
            <li>Add events and classes</li>
            <li>Featured positions within the Hub</li>
          </ul>
        </div>
        <div class="plan plan--partner">
          <h3 class="plan__name">Local Partner</h3>
          <p class="plan__for">Featured on the Hub, plus a monthly spot in the Daily News and the Facebook Group.</p>
          <p class="plan__price"><span class="price-monthly">&pound;79<small>/month</small></span><span class="price-annual">&pound;790<small>/year</small></span></p>
          <p class="plan__note"><span class="price-monthly">or &pound;790 a year</span><span class="price-annual">Save &pound;158</span></p>
          <a class="ho-btn ho-btn--primary ho-btn--block ho-btn--sm" href="<?php echo esc_url( hub_url( 'billing/portal/' ) ); ?>">Change plan</a>
          <ul class="ticks small">
            <li class="ticks__lead">Everything in Featured, plus:</li>
            <li>One sponsored slot each month in the Daily News newsletter</li>
            <li>One sponsored post each month in the Harrow Online Facebook Group</li>
          </ul>
        </div>
      </div>
      <p class="help mt-4">Your new plan switches on as soon as payment goes through. <a href="<?php echo esc_url( hub_url( 'pricing/' ) ); ?>">See everything in each plan</a>.</p>
      <!-- WP: existing subscribers change plan in the Portal (subscription update flow, prorated).
           Listings with no subscription (Basic) show "Upgrade" instead, which opens Stripe Checkout (#stripe-checkout) for the chosen Price ID. -->
    </section>

    <div class="grid grid-2 mt-6">
      <section class="panel" aria-labelledby="pm-title">
        <h2 class="panel__title" id="pm-title">Payment method</h2>
        <div class="cluster cluster--lg">
          <span class="mono mono--sm mono--soft" aria-hidden="true"><svg class="ho-icon"><use href="#i-card"/></svg></span>
          <div>
            <strong>Visa ending 4242</strong>
            <p class="small muted mb-0">Expires 08/2028</p>
          </div>
        </div>
        <p class="help mt-4">Shown from Stripe. We never see or store your full card number.</p>
        <a class="ho-btn ho-btn--sm ho-btn--outline mt-2" href="<?php echo esc_url( hub_url( 'billing/portal/' ) ); ?>">Update card in Stripe</a>
      </section>
      <section class="panel panel--soft" aria-labelledby="q-title">
        <h2 class="panel__title" id="q-title">Questions</h2>
        <details class="faq-mini">
          <summary>Have a discount code?</summary>
          <p class="small">Enter it on the Stripe payment page when you upgrade. Codes work on Hub plans only, not on featured spots or sponsor slots. Already paying? <a href="https://harrowonline.org/contact-us/">Contact us</a> and we&rsquo;ll apply it to your next invoice.</p>
        </details>
        <details class="faq-mini">
          <summary>What happens if I cancel?</summary>
          <ul class="ticks ticks--dark small">
        <li>You keep Featured until the end of the period you&rsquo;ve paid for (2 December 2026).</li>
        <li>Then your listing moves to Basic. It stays verified and stays yours.</li>
        <li>Nothing is deleted. Extra photos, social links and offers are hidden, and come back if you upgrade again.</li>
        <li>Live offers end. Events stay published but lose any featured spot.</li>
      </ul>
          <a class="ho-btn ho-btn--sm ho-btn--outline mt-2" href="<?php echo esc_url( hub_url( 'billing/portal/' ) ); ?>">Cancel in Stripe</a>
        </details>
      </section>
    </div>

    <section class="mt-6" aria-labelledby="inv-title">
      <h2 class="dash-h2 mb-4" id="inv-title">Invoices and receipts</h2>
      <div class="table-wrap">
        <table class="table table--stack">
          <thead>
            <tr><th scope="col">Date</th><th scope="col">Invoice</th><th scope="col">For</th><th scope="col">Amount</th><th scope="col">Status</th><th scope="col"><span class="ho-sr">Download</span></th></tr>
          </thead>
          <tbody>
            <tr>
              <td data-label="Date">11 Nov 2026</td><td data-label="Invoice">HOHUB-0009</td><td data-label="For">Sponsor slot: Daily News, week of 30 Nov</td><td data-label="Amount">&pound;180.00</td>
              <td data-label="Status"><span class="status status--paid">Paid</span></td>
              <td><div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--ghost" href="#invoice-pdf"><svg class="ho-icon"><use href="#i-download"/></svg>PDF</a></div></td>
            </tr>
            <tr>
              <td data-label="Date">10 Nov 2026</td><td data-label="Invoice">HOHUB-0008</td><td data-label="For">Feature this: 20% off your first cut, 7 days</td><td data-label="Amount">&pound;15.00</td>
              <td data-label="Status"><span class="status status--paid">Paid</span></td>
              <td><div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--ghost" href="#invoice-pdf"><svg class="ho-icon"><use href="#i-download"/></svg>PDF</a></div></td>
            </tr>
            <tr>
              <td data-label="Date">9 Nov 2026</td><td data-label="Invoice">HOHUB-0007</td><td data-label="For">Feature this: Barbering taster class, 7 days</td><td data-label="Amount">&pound;15.00</td>
              <td data-label="Status"><span class="status status--paid">Paid</span></td>
              <td><div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--ghost" href="#invoice-pdf"><svg class="ho-icon"><use href="#i-download"/></svg>PDF</a></div></td>
            </tr>
            <tr>
              <td data-label="Date">5 Nov 2026</td><td data-label="Invoice">HOHUB-0006</td><td data-label="For">Sponsor slot: The Weekend, 3 Dec</td><td data-label="Amount">&pound;60.00</td>
              <td data-label="Status"><span class="status status--paid">Paid</span></td>
              <td><div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--ghost" href="#invoice-pdf"><svg class="ho-icon"><use href="#i-download"/></svg>PDF</a></div></td>
            </tr>
            <tr>
              <td data-label="Date">2 Nov 2026</td><td data-label="Invoice">HOHUB-0005</td><td data-label="For">Featured, monthly</td><td data-label="Amount">&pound;29.00</td>
              <td data-label="Status"><span class="status status--paid">Paid</span></td>
              <td><div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--ghost" href="#invoice-pdf"><svg class="ho-icon"><use href="#i-download"/></svg>PDF</a></div></td>
            </tr>
            <tr>
              <td data-label="Date">24 Oct 2026</td><td data-label="Invoice">HOHUB-0004</td><td data-label="For">Feature this: Halloween kids&rsquo; cuts, 7 days</td><td data-label="Amount">&pound;15.00</td>
              <td data-label="Status"><span class="status status--paid">Paid</span></td>
              <td><div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--ghost" href="#invoice-pdf"><svg class="ho-icon"><use href="#i-download"/></svg>PDF</a></div></td>
            </tr>
            <tr>
              <td data-label="Date">20 Oct 2026</td><td data-label="Invoice">HOHUB-0003</td><td data-label="For">Sponsor slot: Harrow Business, 26 Oct</td><td data-label="Amount">&pound;50.00</td>
              <td data-label="Status"><span class="status status--ended">Refunded</span></td>
              <td><div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--ghost" href="#invoice-pdf"><svg class="ho-icon"><use href="#i-download"/></svg>PDF</a></div></td>
            </tr>
            <tr>
              <td data-label="Date">2 Oct 2026</td><td data-label="Invoice">HOHUB-0002</td><td data-label="For">Featured, monthly</td><td data-label="Amount">&pound;29.00</td>
              <td data-label="Status"><span class="status status--paid">Paid</span></td>
              <td><div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--ghost" href="#invoice-pdf"><svg class="ho-icon"><use href="#i-download"/></svg>PDF</a></div></td>
            </tr>
            <tr>
              <td data-label="Date">2 Sep 2026</td><td data-label="Invoice">HOHUB-0001</td><td data-label="For">Featured, monthly</td><td data-label="Amount">&pound;29.00</td>
              <td data-label="Status"><span class="status status--paid">Paid</span></td>
              <td><div class="table__actions"><a class="ho-btn ho-btn--sm ho-btn--ghost" href="#invoice-pdf"><svg class="ho-icon"><use href="#i-download"/></svg>PDF</a></div></td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="help mt-2">PDF invoices come from Stripe. Older invoices are in <a href="<?php echo esc_url( hub_url( 'billing/portal/' ) ); ?>">Manage billing</a>.</p>
      <!-- WP: list from Stripe (invoices.list + one-off payment receipts) for this customer; PDF link = invoice.invoice_pdf -->
    </section>

  </div>
</div>
<?php get_footer();

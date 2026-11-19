<?php
/**
 * Test the Stripe webhook without Stripe (CLI): signs sample events with a test secret, answers the webhook's
 * Stripe API calls itself, and checks what the Hub does with each one: plan changes, payment log, emails.
 * Uses a temporary business and account, deleted at the end. No keys or network needed.
 *
 *   C:\php\php.exe wp-local/hub/billing-webhook-test-local.php
 */
$_SERVER['HTTP_HOST']   = 'localhost:8020';
$_SERVER['REQUEST_URI'] = '/hub/';
// Fake keys: valid in shape, so billing counts as set up; every Stripe call is answered below, never sent
// (built here rather than written out, so secret scanners don't take them for real keys)
$fake = str_repeat( '0', 24 );
putenv( 'STRIPE_SECRET_KEY=sk_' . 'test_' . $fake );
putenv( 'STRIPE_PUBLISHABLE_KEY=pk_' . 'test_' . $fake );
putenv( 'STRIPE_WEBHOOK_SECRET=whsec_' . 'hubwebhooktest' );
require __DIR__ . '/wp-load.php';
require_once get_template_directory() . '/vendor/autoload.php';

/* ------------------------------------------------------------------ fake Stripe API and mailbox */
$GLOBALS['fake_subs']  = array();   // subscription id => array (what Stripe would return)
$GLOBALS['api_calls']  = array();
$GLOBALS['sent_mail']  = array();
\Stripe\ApiRequestor::setHttpClient( new class() implements \Stripe\HttpClient\ClientInterface {
	public function request( $method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null ) {
		$path                  = (string) parse_url( $absUrl, PHP_URL_PATH );
		$GLOBALS['api_calls'][] = strtoupper( $method ) . ' ' . $path;
		if ( 'get' === $method && preg_match( '#^/v1/subscriptions/(\w+)$#', $path, $m ) && isset( $GLOBALS['fake_subs'][ $m[1] ] ) ) {
			return array( json_encode( $GLOBALS['fake_subs'][ $m[1] ] ), 200, array() );
		}
		return array( json_encode( array( 'error' => array( 'type' => 'invalid_request_error', 'message' => 'Not faked: ' . $path ) ) ), 404, array() );
	}
} );
remove_all_filters( 'pre_wp_mail' );
add_filter( 'pre_wp_mail', function ( $null, $atts ) {
	$GLOBALS['sent_mail'][] = $atts;
	return true;
}, 10, 2 );
$items_paid = array();
add_action( 'hub_billing_item_paid', function ( $item, $ref, $user, $biz ) use ( &$items_paid ) {
	$items_paid[] = compact( 'item', 'ref', 'user', 'biz' );
}, 10, 4 );

/* ------------------------------------------------------------------ helpers */
$run    = substr( md5( microtime() ), 0, 8 );   // unique event IDs each run (the webhook ignores repeats)
$failed = 0;
function check( $label, $ok, $detail = '' ) {
	global $failed;
	$failed += $ok ? 0 : 1;
	echo ( $ok ? '  ok    ' : '  FAIL  ' ) . $label . ( '' !== $detail ? '  (' . $detail . ')' : '' ) . "\n";
}
function send_event( $id, $type, array $object, $secret = 'whsec_hubwebhooktest' ) {
	global $run;
	$payload = json_encode( array( 'id' => 'evt_' . $run . '_' . $id, 'object' => 'event', 'type' => $type, 'api_version' => \Stripe\Util\ApiVersion::CURRENT, 'data' => array( 'object' => $object ) ) );
	$t       = time();
	return hub_billing_webhook( $payload, 't=' . $t . ',v1=' . hash_hmac( 'sha256', $t . '.' . $payload, $secret ), HUB_WEB );
}
function fake_sub( $id, $lookup_key, $status, array $meta, $cancel_at_end = false ) {
	$GLOBALS['fake_subs'][ $id ] = array(
		'id' => $id, 'object' => 'subscription', 'status' => $status, 'customer' => 'cus_' . $GLOBALS['run'],
		'cancel_at_period_end' => $cancel_at_end, 'metadata' => $meta,
		'items' => array( 'object' => 'list', 'data' => array( array(
			'id' => 'si_1', 'object' => 'subscription_item', 'current_period_end' => strtotime( '+1 month' ),
			'price' => array( 'id' => 'price_' . $lookup_key, 'object' => 'price', 'lookup_key' => $lookup_key ),
		) ) ),
	);
	return $GLOBALS['fake_subs'][ $id ];
}
function plan_of( $user_id ) {
	$a = hub_account_for_user( $user_id );
	return $a['plan'];
}
function user_plan( $user_id ) {
	return (string) get_user_meta( $user_id, 'hub_plan', true );
}
function last_mail_subject() {
	$m = end( $GLOBALS['sent_mail'] );
	return $m ? $m['subject'] : '';
}

/* ------------------------------------------------------------------ temporary business and owner */
$biz  = wp_insert_post( array( 'post_type' => 'hub_business', 'post_status' => 'publish', 'post_title' => 'Webhook Test Bakery ' . $run ) );
// A claimed business with fields, like one an owner manages (unclaimed listings never show a plan)
hub_businesses_db_save_fields( $biz, array( 'biz_claimed' => '1' ) );
$user = wp_insert_user( array( 'user_login' => 'whtest-' . $run . '@harrowonline.test', 'user_email' => 'whtest-' . $run . '@harrowonline.test', 'user_pass' => wp_generate_password( 20 ), 'first_name' => 'Wendy', 'role' => 'subscriber' ) );
update_user_meta( $user, 'hub_account_type', 'business' );
update_user_meta( $user, 'hub_plan', 'basic' );
hub_account_link_business( $user, $biz );   // as staff would in wp-admin (CLI runs as no user, so link directly below if refused)
if ( hub_account_business_id( $user ) !== (int) $biz ) {
	update_user_meta( $user, 'hub_business_id', $biz );
	update_post_meta( $biz, '_hub_owner', $user );
}
hub_business_set_plan( $biz, 'basic' );
$meta = array( 'hub_user' => (string) $user, 'hub_business' => (string) $biz, 'hub_plan' => 'featured', 'hub_interval' => 'monthly' );
$cus  = 'cus_' . $run;
$sub  = 'sub_' . $run;
echo "Webhook test (business $biz, user $user)\n";

try {
	echo "\nSignature\n";
	$r = send_event( 'bad', 'invoice.paid', array( 'id' => 'in_x' ), 'whsec_wrong' );
	check( 'event signed with the wrong secret is refused', null === $r );

	echo "\nSubscribe to Featured, monthly (Stripe Checkout)\n";
	fake_sub( $sub, 'hub_featured_monthly', 'active', $meta );
	$r = send_event( 'co1', 'checkout.session.completed', array( 'id' => 'cs_' . $run, 'object' => 'checkout.session', 'mode' => 'subscription', 'customer' => $cus, 'subscription' => $sub, 'payment_status' => 'paid', 'metadata' => $meta ) );
	check( 'handled as a plan change', 'plan' === $r['handled'], $r['handled'] );
	check( 'business is now Featured (premium)', 'premium' === plan_of( $user ), plan_of( $user ) );
	check( 'the user Plan field is Featured, role still Subscriber', 'featured' === user_plan( $user ) && array( 'subscriber' ) === get_userdata( $user )->roles, user_plan( $user ) );
	check( 'Stripe customer saved on the account', hub_billing_db_customer_id( $user ) === $cus );
	$s = hub_billing_db_subscription( $biz );
	check( 'subscription saved: featured, monthly, active', 'featured' === $s['plan_key'] && 'monthly' === $s['interval'] && 'active' === $s['status'] );
	check( 'asked Stripe for the live subscription', in_array( 'GET /v1/subscriptions/' . $sub, $GLOBALS['api_calls'], true ) );
	$r = send_event( 'co1', 'checkout.session.completed', array( 'id' => 'cs_' . $run, 'mode' => 'subscription', 'customer' => $cus, 'subscription' => $sub, 'metadata' => $meta ) );
	check( 'the same event again is ignored', 'duplicate' === $r['handled'] );

	echo "\nFirst invoice paid\n";
	$r = send_event( 'in1', 'invoice.paid', array( 'id' => 'in_' . $run . '_1', 'object' => 'invoice', 'customer' => $cus, 'number' => 'HUB-0001', 'amount_paid' => 2900, 'amount_due' => 2900, 'attempt_count' => 1 ) );
	$log = get_posts( array( 'post_type' => 'hub_payment', 'post_status' => 'any', 'meta_key' => '_hub_stripe_id', 'meta_value' => 'in_' . $run . '_1', 'numberposts' => 1 ) );
	check( 'logged in wp-admin > Payments', $log && false !== strpos( $log[0]->post_title, 'HUB-0001' ), $log ? html_entity_decode( $log[0]->post_title ) : 'none' );
	check( 'no email for a successful payment', 0 === count( $GLOBALS['sent_mail'] ) );

	echo "\nSwitch to Local Partner, annual (customer portal)\n";
	$obj = fake_sub( $sub, 'hub_partner_annual', 'active', $meta );
	$r   = send_event( 'up1', 'customer.subscription.updated', $obj );
	check( 'business is now Local Partner', 'partner' === plan_of( $user ), plan_of( $user ) );
	check( 'the user Plan field is Local Partner', 'partner' === user_plan( $user ), user_plan( $user ) );
	$s = hub_billing_db_subscription( $biz );
	check( 'subscription saved: partner, annual', 'partner' === $s['plan_key'] && 'annual' === $s['interval'] );

	echo "\nRenewal payment fails\n";
	$r = send_event( 'pf1', 'invoice.payment_failed', array( 'id' => 'in_' . $run . '_2', 'object' => 'invoice', 'customer' => $cus, 'number' => 'HUB-0002', 'amount_paid' => 0, 'amount_due' => 79000, 'attempt_count' => 1 ) );
	check( 'one email sent', 1 === count( $GLOBALS['sent_mail'] ), count( $GLOBALS['sent_mail'] ) . ' sent' );
	$m = end( $GLOBALS['sent_mail'] );
	check( 'to the account holder', $m && 'whtest-' . $run . '@harrowonline.test' === ( is_array( $m['to'] ) ? $m['to'][0] : $m['to'] ) );
	check( '"We couldn\'t take your Harrow Online payment"', "We couldn't take your Harrow Online payment" === last_mail_subject(), last_mail_subject() );
	check( 'names the amount, plan, business and card link', $m && false !== strpos( $m['message'], '£790' ) && false !== strpos( $m['message'], 'Local Partner plan' ) && false !== strpos( $m['message'], 'Webhook Test Bakery' ) && false !== strpos( $m['message'], '/billing/portal/' ) );
	$obj = fake_sub( $sub, 'hub_partner_annual', 'past_due', $meta );
	send_event( 'up2', 'customer.subscription.updated', $obj );
	check( 'while Stripe retries, the business keeps Local Partner', 'partner' === plan_of( $user ) );
	check( 'billing page status is "Payment failed"', 'past_due' === hub_billing_db_subscription( $biz )['status'] );
	send_event( 'pf2', 'invoice.payment_failed', array( 'id' => 'in_' . $run . '_2', 'object' => 'invoice', 'customer' => $cus, 'number' => 'HUB-0002', 'amount_paid' => 0, 'amount_due' => 79000, 'attempt_count' => 2 ) );
	check( 'no second email on Stripe\'s retry', 1 === count( $GLOBALS['sent_mail'] ), count( $GLOBALS['sent_mail'] ) . ' sent' );

	echo "\nRetry succeeds\n";
	send_event( 'in2', 'invoice.paid', array( 'id' => 'in_' . $run . '_2', 'object' => 'invoice', 'customer' => $cus, 'number' => 'HUB-0002', 'amount_paid' => 79000, 'amount_due' => 79000, 'attempt_count' => 3 ) );
	$obj = fake_sub( $sub, 'hub_partner_annual', 'active', $meta );
	send_event( 'up3', 'customer.subscription.updated', $obj );
	$log = get_posts( array( 'post_type' => 'hub_payment', 'post_status' => 'any', 'meta_key' => '_hub_stripe_id', 'meta_value' => 'in_' . $run . '_2', 'numberposts' => 5 ) );
	check( 'the invoice\'s log entry now says paid (one entry, updated)', 1 === count( $log ) && false !== strpos( $log[0]->post_title, '(paid)' ), $log ? html_entity_decode( $log[0]->post_title ) : 'none' );
	check( 'status back to active', 'active' === hub_billing_db_subscription( $biz )['status'] );

	echo "\nCancel at the end of the period (customer portal)\n";
	$obj = fake_sub( $sub, 'hub_partner_annual', 'active', $meta, true );
	send_event( 'up4', 'customer.subscription.updated', $obj );
	check( 'still Local Partner until the period ends', 'partner' === plan_of( $user ) );
	check( 'marked "ends on" for the billing page', ! empty( hub_billing_db_subscription( $biz )['cancel_at_period_end'] ) );

	echo "\nPeriod ends: subscription deleted\n";
	$obj = fake_sub( $sub, 'hub_partner_annual', 'canceled', $meta, true );
	send_event( 'del', 'customer.subscription.deleted', $obj );
	check( 'business back on Basic', 'basic' === plan_of( $user ), plan_of( $user ) );
	check( 'the user Plan field is Basic', 'basic' === user_plan( $user ), user_plan( $user ) );
	check( '"Your Harrow Online Local Partner plan has ended"', 'Your Harrow Online Local Partner plan has ended' === last_mail_subject(), last_mail_subject() );
	$m = end( $GLOBALS['sent_mail'] );
	check( 'links to the pricing page', $m && false !== strpos( $m['message'], '/pricing/' ) );

	echo "\nOne-off payment (event listing)\n";
	$r = send_event( 'item', 'checkout.session.completed', array( 'id' => 'cs_' . $run . '_item', 'object' => 'checkout.session', 'mode' => 'payment', 'customer' => $cus, 'payment_status' => 'paid', 'amount_total' => 1500, 'metadata' => array( 'hub_user' => (string) $user, 'hub_business' => (string) $biz, 'hub_item' => 'event_listing', 'hub_ref' => '123' ) ) );
	check( 'handled as an item', 'item' === $r['handled'], $r['handled'] );
	check( 'hub_billing_item_paid fired with the item and its event', 1 === count( $items_paid ) && 'event_listing' === $items_paid[0]['item'] && '123' === $items_paid[0]['ref'] );

	echo "\nOther events\n";
	$r = send_event( 'other', 'customer.created', array( 'id' => $cus, 'object' => 'customer' ) );
	check( 'events the Hub doesn\'t use are accepted and ignored', 'ignored' === $r['handled'] );
} finally {
	// Clean up: the test business, account, payment log entries and the test's events
	foreach ( get_posts( array( 'post_type' => 'hub_payment', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_hub_business', 'meta_value' => $biz ) ) as $p ) {
		wp_delete_post( $p, true );
	}
	wp_delete_post( $biz, true );
	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $user );
}
echo "\n" . ( $failed ? $failed . ' FAILED' : 'All passed' ) . "\n";
exit( $failed ? 1 : 0 );

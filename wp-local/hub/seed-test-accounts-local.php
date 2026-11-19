<?php
/**
 * Test accounts with their own data, for trying the Hub's account pages locally (CLI). Safe to run again: it removes
 * what it made last time (accounts with user meta "hub_seed", posts with "_hub_seed") and makes it all afresh.
 * Passwords are written to LOCAL-ADMIN.txt (between the "Test accounts" markers), not printed. The existing test
 * owner (owner@harrowonline.test, AJ Pizza's, Featured) keeps its password and gets sample items too.
 *
 *   C:\php\php.exe wp-local/hub/seed-test-accounts-local.php
 */
$_SERVER['HTTP_HOST']   = 'localhost:8020';
$_SERVER['REQUEST_URI'] = '/hub/';
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
if ( 'local' !== wp_get_environment_type() ) {
	exit( "Local only (WP_ENVIRONMENT_TYPE is not local).\n" );
}
remove_all_filters( 'pre_wp_mail' );
add_filter( 'pre_wp_mail', '__return_true' );   // no emails while seeding
wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );

/* ------------------------------------------------------------------ remove last run's */
foreach ( get_posts( array( 'post_type' => array( 'hub_offer', 'hub_event', 'hub_class', 'hub_claim' ), 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_hub_seed', 'meta_value' => '1' ) ) as $p ) {
	wp_delete_post( $p->ID, true );   // an offer's or class's business link is stored on it, so it goes with it
}
foreach ( get_users( array( 'meta_key' => 'hub_seed', 'meta_value' => '1' ) ) as $u ) {
	$b = hub_account_business_id( $u->ID );
	if ( $b ) {
		hub_account_db_link( $u->ID, 0 );
	}
	wp_delete_user( $u->ID );
}

/* ------------------------------------------------------------------ helpers */
$today = hub_today();
$day   = function ( $n ) use ( $today ) { return $today->modify( ( $n >= 0 ? '+' : '' ) . $n . ' days' )->format( 'Y-m-d' ); };
$made  = array();

function seed_user( $email, $name, $type, array $meta = array() ) {
	$pass = wp_generate_password( 16, false );
	$id   = hub_account_db_create_user( $email, $pass, $name, HUB_ACCOUNT_ROLE, $meta + array(
		'hub_account_type'    => $type,
		'hub_plan'            => 'business' === $type ? 'basic' : '',
		'hub_email_confirmed' => '1',
		'hub_signup_source'   => 'seed',
		'hub_seed'            => '1',
	) );
	return array( $id, $pass );
}

function seed_mark( $post_id, $user_id, $status, $needs_pay = false ) {
	hub_submissions_db_mark( $post_id, $user_id, $needs_pay );
	update_post_meta( $post_id, '_hub_seed', '1' );
	if ( 'publish' === $status ) {
		wp_publish_post( $post_id );
	}
	return $post_id;
}

function seed_offer( $user, array $biz, $status, $title, $text, $start, $end, $code = '' ) {
	$f = array(
		'title' => $title, 'description' => $text, 'start' => $start, 'end' => $end,
		'ends_label' => DateTimeImmutable::createFromFormat( '!Y-m-d', $end )->format( 'j F Y' ), 'code' => $code,
		'redeem' => 'Mention Harrow Online when you book.', 'terms_text' => "One per customer.\nWhile stocks last.",
		'terms' => array( 'One per customer.', 'While stocks last.' ), 'audience' => 'everyone',
	);
	$id = hub_offer_save_submission( 0, 'publish' === $status ? 'pending' : $status, $f, array( 'id' => $biz['id'], 'slug' => $biz['slug'] ) );
	return seed_mark( $id, $user, $status );
}

function seed_event( $user, $by, $biz, $status, $title, $text, $cat, $date, $start, $end, $price = '', $needs_pay = false, $venue = null ) {
	list( $label, $icon ) = HUB_SUBMISSIONS_EVENT_CATS[ $cat ];
	$at = $biz && ! $venue;
	$f  = array(
		'title' => $title, 'description' => $text, 'category' => $cat, 'cat' => $label, 'icon' => $icon,
		'date' => $date, 'start' => $start, 'end' => $end, 'time_label' => hub_submissions_rule_time_label( $start ),
		'at_listing' => $at, 'venue_name' => $at ? '' : $venue[0], 'venue_postcode' => $at ? '' : $venue[1],
		'venue' => $at ? html_entity_decode( $biz['name'], ENT_QUOTES ) : $venue[0], 'area' => $at ? $biz['area']['name'] : $venue[2],
		'price_text' => $price, 'price' => '' === $price ? 'Free' : $price, 'age' => 'All ages', 'link' => '', 'org' => $by, 'image' => '',
	);
	$id = hub_event_save_submission( 0, 'publish' === $status ? 'pending' : $status, $f );
	return seed_mark( $id, $user, $status, $needs_pay );
}

function seed_class( $user, $by, $biz, $status, $title, $text, $type, $weekday, $start, $end, $price, $venue = null ) {
	list( $label, $icon ) = HUB_SUBMISSIONS_CLASS_TYPES[ $type ];
	$at = $biz && ! $venue;
	$f  = array(
		'title' => $title, 'description' => $text, 'category' => $type, 'type' => $label, 'icon' => $icon,
		'day' => $weekday, 'start' => $start, 'end' => $end,
		'time_label' => hub_submissions_rule_time_label( $start ) . ' to ' . hub_submissions_rule_time_label( $end ),
		'sort' => (int) substr( $start, 0, 2 ) + (int) substr( $start, 3, 2 ) / 60,
		'term' => 'ongoing', 'term_start' => '', 'term_end' => '', 'term_weeks' => 0, 'no_half_term' => false,
		'at_listing' => $at, 'venue_name' => $at ? '' : $venue[0], 'venue_postcode' => $at ? '' : $venue[1],
		'venue' => $at ? html_entity_decode( $biz['name'], ENT_QUOTES ) : $venue[0],
		'street' => $at ? $biz['address']['street'] : $venue[0], 'postcode' => $at ? $biz['address']['postcode'] : $venue[1],
		'area' => $at ? $biz['area']['name'] : $venue[2], 'age' => 'All ages', 'level' => 'All levels',
		'price_text' => $price, 'price_per' => 'session', 'free_trial' => false,
		'price' => hub_submissions_rule_class_price( $price, 'session', false ), 'booking' => 'turn-up', 'link' => '',
		'provider' => $by, 'business_id' => $biz ? (int) $biz['id'] : 0, 'image' => '',
	);
	$id = hub_class_save_submission( 0, 'publish' === $status ? 'pending' : $status, $f );
	return seed_mark( $id, $user, $status );
}

$business = function ( $id ) { return hub_businesses_summaries( array( $id ) )['items'][ $id ]; };

/* ------------------------------------------------------------------ Featured: the existing test owner (AJ Pizza's) */
$owner = get_user_by( 'email', 'owner@harrowonline.test' );
if ( $owner && hub_account_business_id( $owner->ID ) ) {
	$b = $business( hub_account_business_id( $owner->ID ) );
	seed_offer( $owner->ID, $b, 'publish', 'Free garlic bread with any large pizza', 'Collection and delivery, every day.', $day( 0 ), $day( 30 ) );
	seed_offer( $owner->ID, $b, 'pending', '2 for 1 pizzas on Tuesdays', 'Buy any pizza, get the second free.', $day( 2 ), $day( 45 ), 'TWOFOR1' );
	seed_offer( $owner->ID, $b, 'draft', 'Student discount: 15% off', 'Show a valid student card.', $day( 5 ), $day( 60 ) );
	seed_event( $owner->ID, html_entity_decode( $b['name'], ENT_QUOTES ), $b, 'pending', 'Pizza masterclass', 'Learn to stretch, top and bake a pizza with our chef.', 'food-and-drink', $day( 9 ), '18:00', '20:00', '£25' );
	seed_class( $owner->ID, html_entity_decode( $b['name'], ENT_QUOTES ), $b, 'publish', 'Kids pizza club', 'Children make and eat their own pizza.', 'childrens-activities', 5, '10:00', '11:00', '8' );
	$made[] = array( 'Featured owner (AJ Pizza\'s): live, pending and draft offers; a pending event; a live class', 'owner@harrowonline.test', null );
}

/* ------------------------------------------------------------------ Local Partner (Stanmore Family Dental) */
list( $u, $pw ) = seed_user( 'partner@harrowonline.test', 'Priya Partner', 'business' );
hub_account_link_business( $u, 54 );
$b = $business( 54 );
seed_offer( $u, $b, 'publish', 'Free check-up for new patients', 'Book before the end of the month.', $day( 0 ), $day( 25 ) );
seed_event( $u, html_entity_decode( $b['name'], ENT_QUOTES ), $b, 'pending', 'Family dental open morning', 'Meet the team and tour the practice.', 'family', $day( 6 ), '09:30', '12:00' );
seed_class( $u, html_entity_decode( $b['name'], ENT_QUOTES ), $b, 'pending', 'Brushing club for under 5s', 'A fun half hour on looking after teeth.', 'childrens-activities', 2, '16:00', '16:30', '' );
$made[] = array( 'Local Partner owner (Stanmore Family Dental): a live offer; a pending event and class', 'partner@harrowonline.test', $pw );

/* ------------------------------------------------------------------ Basic (Pinner Village Florist): £15 events */
list( $u, $pw ) = seed_user( 'basic@harrowonline.test', 'Bella Basic', 'business' );
hub_account_link_business( $u, 62 );
$b = $business( 62 );
seed_event( $u, html_entity_decode( $b['name'], ENT_QUOTES ), $b, 'pending', 'Christmas wreath workshop', 'Make your own wreath to take home.', 'community', $day( 12 ), '14:00', '16:00', '£35', true );
seed_event( $u, html_entity_decode( $b['name'], ENT_QUOTES ), $b, 'draft', 'Spring planting morning', 'Pot up spring bulbs with us.', 'family', $day( 20 ), '10:00', '12:00', '', true );
$made[] = array( 'Basic owner (Pinner Village Florist): a pending £15 event (unpaid) and a draft event; offers and classes ask to upgrade', 'basic@harrowonline.test', $pw );

/* ------------------------------------------------------------------ Organiser (no business) */
list( $u, $pw ) = seed_user( 'organiser@harrowonline.test', 'Owen Organiser', 'organiser', array( 'hub_organisation' => 'Harrow Chess Club', 'hub_area' => 'kenton' ) );
$venue = array( 'Kenton Library', 'HA3 8AH', 'Kenton' );
seed_event( $u, 'Harrow Chess Club', null, 'pending', 'Harrow junior chess tournament', 'A friendly tournament for under 16s.', 'community', $day( 10 ), '10:00', '15:00', '£5', false, $venue );
seed_class( $u, 'Harrow Chess Club', null, 'pending', 'Junior chess club', 'Weekly chess for beginners and improvers.', 'childrens-activities', 2, '16:00', '17:30', '5', $venue );
seed_class( $u, 'Harrow Chess Club', null, 'draft', 'Adult chess evening', 'Casual games and coaching for adults.', 'other', 3, '19:00', '21:00', '', $venue );
$made[] = array( 'Organiser (Harrow Chess Club): a pending event and class, a draft class; no offers', 'organiser@harrowonline.test', $pw );

/* ------------------------------------------------------------------ Newcomer (no business yet) */
list( $u, $pw ) = seed_user( 'newcomer@harrowonline.test', 'Nina Newcomer', 'business' );
$made[] = array( 'Newcomer: confirmed email, no business yet (try claiming one, or Add a business)', 'newcomer@harrowonline.test', $pw );

/* ------------------------------------------------------------------ Claimer (a claim waiting for staff) */
list( $u, $pw ) = seed_user( 'claimer@harrowonline.test', 'Carl Claimer', 'business' );
$b  = $business( 63 );
$cid = hub_claims_db_create( array(
	'business' => 63, 'user' => $u, 'method' => 'phone',
	'details'  => array( 'ask_for' => 'Carl', 'call_times' => array( 'weekday-morning' ), 'call_number' => $b['phones'] ? $b['phones'][0]['display'] : '' ),
	'role' => 'owner', 'phone' => '07700 900123', 'note' => 'Seeded test claim.', 'status' => 'pending', 'email_verified' => false,
), html_entity_decode( $b['name'], ENT_QUOTES ) . ' (claimer@harrowonline.test)' );
update_post_meta( $cid, '_hub_seed', '1' );
$made[] = array( 'Claimer: a claim for ' . html_entity_decode( $b['name'], ENT_QUOTES ) . ' waiting in wp-admin > Claims', 'claimer@harrowonline.test', $pw );

$GLOBALS['hub_submission_queue'] = array();   // no "it's live" emails for seeded items

/* ------------------------------------------------------------------ passwords -> LOCAL-ADMIN.txt */
$file  = __DIR__ . '/LOCAL-ADMIN.txt';
$text  = (string) file_get_contents( $file );
$start = '# Test accounts (made by seed-test-accounts-local.php; sign in at /hub/sign-in/)';
$end   = '# End of test accounts';
$block = $start . "\n";
foreach ( $made as $m ) {
	if ( $m[2] ) {
		$block .= $m[0] . "\nEmail: " . $m[1] . "\nPassword: " . $m[2] . "\n\n";
	}
}
$block .= $end . "\n";
$text = preg_match( '/' . preg_quote( $start, '/' ) . '.*?' . preg_quote( $end, '/' ) . "\n?/s", $text )
	? preg_replace( '/' . preg_quote( $start, '/' ) . '.*?' . preg_quote( $end, '/' ) . "\n?/s", str_replace( '$', '\\$', $block ), $text )
	: rtrim( $text ) . "\n\n" . $block;
file_put_contents( $file, $text );

echo "Test accounts (passwords in wp-local/hub/LOCAL-ADMIN.txt):\n";
foreach ( $made as $m ) {
	echo '  ' . str_pad( $m[1], 30 ) . $m[0] . "\n";
}

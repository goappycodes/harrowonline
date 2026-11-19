<?php
/**
 * Small markup helpers for parts/, working on the controllers' shapes (app/modules/{module}/shape.php).
 * They only print data they are given: no queries. Everything returned is escaped HTML.
 */
defined( 'ABSPATH' ) || exit;

/** The status mark next to a business name: Checked by Harrow Online, Verified by the business, or imported. */
function hub_view_tick( array $b, $focusable = true ) {
	$marks = array(
		'checked'  => array( 'Checked by Harrow Online', 'check' ),
		'verified' => array( 'Verified by the business', 'verified' ),
		'imported' => array( 'Information imported from public data', 'globe' ),
	);
	list( $label, $icon ) = $marks[ $b['status'] ];
	return '<span class="card__tick card__tick--' . esc_attr( $b['status'] ) . '"' . ( $focusable ? ' tabindex="0"' : '' ) . ' role="img" aria-label="' . esc_attr( $label ) . '" data-tip="' . esc_attr( $label ) . '"><svg class="ho-icon" aria-hidden="true"><use href="#i-' . $icon . '"/></svg></span>';
}

/**
 * The business name with its mark after the last word (kept together on one line). In links ($linked), imported
 * businesses show no mark and the mark is not focusable (the link is).
 */
function hub_view_name_with_tick( array $b, $linked = true ) {
	$name = $b['name'];
	if ( $linked && 'imported' === $b['status'] ) {
		return esc_html( $name );
	}
	$pos  = strrpos( $name, ' ' );
	$head = false === $pos ? '' : substr( $name, 0, $pos );
	$last = false === $pos ? $name : substr( $name, $pos + 1 );
	return ( '' !== $head ? esc_html( $head ) . ' ' : '' ) . '<span class="name-end">' . esc_html( $last ) . hub_view_tick( $b, ! $linked ) . '</span>';
}

/** "Open today" / "Closed today" / the business's own status line. */
function hub_view_open_today( array $b ) {
	if ( ! $b['open_today'] ) {
		return '';
	}
	$mod = array( 'open' => '', 'closed' => ' open-now--closed', 'info' => ' open-now--info' );
	return '<span class="open-now' . $mod[ $b['open_today']['state'] ] . '">' . esc_html( $b['open_today']['label'] ) . '</span>';
}

/** Photo $i of a business (wrapping round), or its icon when it has no photos. */
function hub_view_photo( array $b, $i = 0, $fallback = null, $alt = '', $eager = false ) {
	if ( ! $b['images'] ) {
		return hub_icon( $fallback ? $fallback : $b['icon'] );
	}
	$img  = $b['images'][ $i % count( $b['images'] ) ];
	$alt  = '' !== $alt ? $alt : $img['alt'];
	$load = $eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
	return '<img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $alt ) . '" width="1200" height="800" ' . $load . '>';
}

/** Class of a photo box: "ph ph--photo" when the business has photos. */
function hub_view_ph_cls( array $b, $base = 'ph' ) {
	return $base . ( $b['images'] ? ' ph--photo' : '' );
}

/** The business's logo in a box. */
function hub_view_logo( array $b, $cls = 'ph ph--logo', $alt = '' ) {
	return '<div class="' . esc_attr( $cls ) . '"><img src="' . esc_url( $b['logo'] ) . '" alt="' . esc_attr( $alt ) . '" width="480" height="480" loading="lazy" decoding="async"></div>';
}

/** Where a business is: "12 High St, Pinner, HA5 1AA" ($with_area) or "12 High St, HA5 1AA"; "Covers Harrow" for mobile businesses. */
function hub_view_address( array $b, $with_area = false ) {
	$a = $b['address'];
	if ( ! $a['has_premises'] ) {
		return 'Covers ' . ( $a['service_area'] ? $a['service_area'] : 'Harrow' );
	}
	return implode( ', ', array_filter( array( $a['street'], $with_area ? $b['area']['name'] : '', $a['postcode'] ) ) );
}

/** Google Maps search link. */
function hub_view_maps_url( $query ) {
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
}

/** A website address without the scheme and trailing slash ("example.co.uk"). */
function hub_view_short_url( $url ) {
	$parts = explode( '//', (string) $url, 2 );
	return rtrim( end( $parts ), '/' );
}

/** Text cut to about $n characters at a word, with "…" (meta descriptions). */
function hub_view_excerpt( $text, $n = 155 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	if ( mb_strlen( $text ) <= $n ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $n );
	$cut = mb_substr( $cut, 0, (int) mb_strrpos( $cut, ' ' ) );
	return rtrim( $cut, ',;:' ) . '…';
}

/* ------------------------------------------------------------------ events and classes */

/** Date tile: "Nov / 14 / Sat". */
function hub_view_date_tile( $date ) {
	$d = date_create_immutable( $date );
	return '<time class="date-tile" datetime="' . esc_attr( $d->format( 'Y-m-d' ) ) . '"><span class="date-tile__m">' . $d->format( 'M' ) . '</span><span class="date-tile__d">' . $d->format( 'j' ) . '</span><span class="date-tile__w">' . $d->format( 'D' ) . '</span></time>';
}

/** An event's photo, or its icon, in a box ($extra_cls added to the box's class). */
function hub_view_event_media( array $e, $extra_cls = '', $eager = false ) {
	if ( $e['image'] ) {
		$load = $eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
		return '<div class="ph ph--photo' . esc_attr( $extra_cls ) . '"><img src="' . esc_url( $e['image'] ) . '" alt="" width="1200" height="800" ' . $load . '></div>';
	}
	return '<div class="ph' . esc_attr( $extra_cls ) . '">' . hub_icon( $e['icon'] ) . '</div>';
}

/** A class's photo or logo (classes run by real businesses), or its icon, in a box of class $cls. */
function hub_view_class_media( array $c, $cls = 'ph', $eager = false ) {
	if ( ! $c['image'] ) {
		return '<div class="' . esc_attr( $cls ) . '">' . hub_icon( $c['icon'] ) . '</div>';
	}
	$load = $eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
	$logo = 'logo' === $c['image']['fit'];
	return '<div class="' . esc_attr( $cls ) . ( $logo ? ' ph--logo' : ' ph--photo' ) . '"><img src="' . esc_url( $c['image']['url'] ) . '" alt="' . esc_attr( $c['image']['alt'] ) . '" width="' . ( $logo ? 480 : 1200 ) . '" height="' . ( $logo ? 480 : 800 ) . '" ' . $load . '></div>';
}

/** data-* attributes the What's On filters read (hub.js 6b): category, area, age groups, free. */
function hub_view_event_filter_attrs( array $x ) {
	return ' data-cat="' . esc_attr( $x['category']['slug'] ) . '" data-area="' . esc_attr( $x['area']['slug'] ) . '" data-ages="' . esc_attr( implode( ' ', $x['age_groups'] ) ) . '" data-free="' . ( $x['free'] ? 1 : 0 ) . '"';
}

/** True when a UK local time ('Y-m-d\TH:i:s') is in British Summer Time (2026 dates, as the prototype). */
function hub_view_is_bst( $local ) {
	$naive = str_replace( 'T', ' ', $local );
	return $naive >= '2026-03-29 00:00:00' && $naive < '2026-10-25 01:00:00';
}

/** UTC offset of a UK local time: "+01:00" in summer, "+00:00" otherwise. */
function hub_view_uk_offset( $local ) {
	return hub_view_is_bst( $local ) ? '+01:00' : '+00:00';
}

/** A UK local time in UTC, in the format given. */
function hub_view_utc( $local, $format ) {
	$t = new DateTimeImmutable( $local, new DateTimeZone( 'UTC' ) );
	return ( hub_view_is_bst( $local ) ? $t->modify( '-1 hour' ) : $t )->format( $format );
}

/** "Add to calendar" links for an event: [ google, outlook, ics data URL ]. $url is the event's public address. */
function hub_view_calendar_links( array $e, $url ) {
	$place  = $e['venue'] . ', ' . $e['area']['name'] . ', Harrow';
	$s      = hub_view_utc( $e['start'], 'Ymd\THis\Z' );
	$f      = hub_view_utc( $e['end'], 'Ymd\THis\Z' );
	$google = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . hub_quote_plus( $e['title'] ) . "&dates={$s}/{$f}"
		. '&location=' . hub_quote_plus( $place ) . '&details=' . hub_quote_plus( $url );
	$outlook = 'https://outlook.live.com/calendar/0/deeplink/compose?path=%2Fcalendar%2Faction%2Fcompose&rru=addevent&subject=' . hub_quote_plus( $e['title'] )
		. '&startdt=' . hub_quote( hub_view_utc( $e['start'], 'Y-m-d\TH:i:s\Z' ), '' ) . '&enddt=' . hub_quote( hub_view_utc( $e['end'], 'Y-m-d\TH:i:s\Z' ), '' )
		. '&location=' . hub_quote_plus( $place );
	$ics = implode( "\r\n", array(
		'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Harrow Online Hub//EN', 'BEGIN:VEVENT',
		'UID:' . $e['slug'] . '-' . substr( $e['date'], 0, 4 ) . '@harrowonline.org', 'DTSTAMP:20261111T090000Z', "DTSTART:{$s}", "DTEND:{$f}",
		'SUMMARY:' . str_replace( ',', '\,', $e['title'] ), 'LOCATION:' . str_replace( ',', '\,', $place ), "URL:{$url}", 'END:VEVENT', 'END:VCALENDAR',
	) );
	return array( $google, $outlook, 'data:text/calendar;charset=utf-8,' . hub_quote( $ics, ':/@.-_ ' ) );
}

/** "Hatch End Bookshop" -> "HB" (organiser and provider boxes). */
function hub_view_two_initials( $name ) {
	$words = preg_split( '/\s+/', trim( (string) $name ) );
	return strtoupper( implode( '', array_map( function ( $w ) { return mb_substr( $w, 0, 1 ); }, array_slice( $words, 0, 2 ) ) ) );
}

/** ---------------------------------------------------------------- account forms */
/** The message box above a form after it was sent: { ok, message } from an account controller (nothing if null). */
function hub_view_form_notice( $result, $ok_title = '' ) {
	if ( ! $result || '' === $result['message'] ) {
		return;
	}
	$ok = ! empty( $result['ok'] );
	echo '<div class="notice ' . ( $ok ? 'notice--ok' : 'notice--error' ) . ' mb-5" role="' . ( $ok ? 'status' : 'alert' ) . '">'
		. '<svg class="ho-icon"><use href="#i-' . ( $ok ? 'mail' : 'alert' ) . '"/></svg><div>'
		. ( $ok && $ok_title ? '<p><strong>' . esc_html( $ok_title ) . '</strong></p>' : '' )
		. '<p>' . wp_kses( $result['message'], array() ) . '</p></div></div>'; // messages may hold &rsquo;
}

/** " has-error" for a field's wrapper when the controller flagged it. */
function hub_view_field_class( $result, $field ) {
	return ( $result && ! empty( $result['errors'][ $field ] ) ) ? ' has-error' : '';
}

/** The field's error line under its input (nothing when it's fine). */
function hub_view_field_error( $result, $field ) {
	if ( $result && ! empty( $result['errors'][ $field ] ) ) {
		echo '<p class="field__error" id="' . esc_attr( $field ) . '-error"><svg class="ho-icon"><use href="#i-alert"/></svg>' . wp_kses( $result['errors'][ $field ], array() ) . '</p>';
	}
}

/** A sent value, to put back in the field after an error (never passwords). */
function hub_view_posted( $key ) {
	return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- display only, after the controller checked the nonce
}

/** ---------------------------------------------------------------- account pages: who is signed in */
/** The account box at the top of the account menu: the business (or the person) and the plan. */
function hub_view_account_who() {
	$a     = hub_account_current();
	$b     = $a['business'];
	$title = $b ? $b['name'] : ( '' !== $a['name'] ? $a['name'] : 'Your account' );
	if ( $b ) {
		$sub = $a['plan_label'] . ' plan';
	} else {
		$sub = 'organiser' === $a['type'] ? 'Organiser' : 'No listing yet';
	}
	$words    = preg_split( '/\s+/', trim( preg_replace( '/[^\p{L}\p{N} ]/u', ' ', $title ) ) );
	$initials = strtoupper( implode( '', array_map( function ( $w ) { return mb_substr( $w, 0, 1 ); }, array_slice( array_filter( $words ), 0, 2 ) ) ) );
	echo '<span class="mono mono--sm" aria-hidden="true">' . esc_html( '' !== $initials ? $initials : '?' ) . '</span>'
		. '<div><strong>' . esc_html( $title ) . '</strong><span>' . esc_html( $sub ) . '</span></div>';
}

/** The dashboard greeting: "Good morning, Sam" and the business with a link to its page. */
function hub_view_account_hello() {
	$a    = hub_account_current();
	$h    = (int) current_time( 'G' );
	$when = $h < 12 ? 'Good morning' : ( $h < 18 ? 'Good afternoon' : 'Good evening' );
	$name = '' !== $a['first_name'] ? $a['first_name'] : 'there';
	echo '<h1 data-welcome-text="' . esc_attr( 'Welcome to the Hub, ' . $name ) . '">' . esc_html( $when . ', ' . $name ) . '</h1>';
	if ( $a['business'] ) {
		echo '<p>' . esc_html( $a['business']['name'] ) . ' &middot; <a href="' . esc_url( $a['business']['url'] ) . '">View my listing</a></p>';
	} else {
		$claim = hub_claim_mine();   // claims module: their latest claim, if any
		if ( $claim && 'pending' === $claim['status'] ) {
			echo '<p>Your claim for ' . esc_html( $claim['business']['name'] ) . ' is being checked &middot; <a href="' . esc_url( hub_url( 'claim/submitted/' ) ) . '">See your claim</a></p>';
		} elseif ( $claim && 'rejected' === $claim['status'] ) {
			echo '<p>We couldn&rsquo;t approve your claim for ' . esc_html( $claim['business']['name'] ) . ' &middot; <a href="' . esc_url( hub_url( 'claim/submitted/' ) ) . '">See why</a></p>';
		} else {
			echo '<p>No listing linked yet &middot; <a href="' . esc_url( hub_url( 'search/' ) ) . '">Find your business to claim it</a></p>';
		}
	}
	hub_view_account_email_notice( $a );
	if ( isset( $_GET['sign_out'] ) && 'confirm' === $_GET['sign_out'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice mt-4" role="status"><svg class="ho-icon"><use href="#i-logout"/></svg><div><p class="mb-0"><strong>Sign out?</strong> <a class="ho-btn ho-btn--outline ho-btn--sm ml-2" href="' . esc_url( hub_account_sign_out_url() ) . '">Sign out</a></p></div></div>';
	}
}

/** The "Sign out" link with its token (account sidebar, claim page). */
function hub_view_sign_out_url() {
	return hub_account_sign_out_url();
}

/** Dashboard: "Confirm your email" (with a resend button) until it's confirmed, and the result of the link or resend. */
function hub_view_account_email_notice( array $a ) {
	$q     = isset( $_GET['email'] ) ? sanitize_key( $_GET['email'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$gate  = isset( $_GET['confirm'] ) && 'needed' === $_GET['confirm']; // phpcs:ignore WordPress.Security.NonceVerification
	$notes = array(
		'confirmed'    => array( 'ok', 'check', 'Email address confirmed.', 'Thanks. You can now choose a plan for your business.' ),
		'link-invalid' => array( 'warn', 'alert', 'That confirmation link didn&rsquo;t work.', 'It may have expired or been replaced by a newer one. Send yourself a new link below.' ),
	);
	if ( isset( $notes[ $q ] ) && ( 'confirmed' === $q || ! $a['email_confirmed'] ) ) {
		list( $kind, $icon, $title, $text ) = $notes[ $q ];
		echo '<div class="notice notice--' . $kind . ' mt-4" role="status"><svg class="ho-icon"><use href="#i-' . $icon . '"/></svg><div><p><strong>' . $title . '</strong> ' . $text . '</p></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed strings
	}
	if ( $a['email_confirmed'] ) {
		return;
	}
	$sent = 'sent' === $q ? '<p class="mb-0"><strong>New link sent.</strong> Check your inbox and spam folder.</p>' : ( 'wait' === $q ? '<p class="mb-0"><strong>We&rsquo;ve sent several links already.</strong> Please check your spam folder, or try again in an hour.</p>' : '' );
	echo '<div class="notice notice--warn mt-4" role="status"><svg class="ho-icon"><use href="#i-mail"/></svg><div>'
		. '<p><strong>' . ( $gate ? 'Confirm your email before choosing a plan.' : 'Please confirm your email address.' ) . '</strong> We sent a link to ' . esc_html( $a['email'] ) . '.</p>'
		. $sent
		. '<form method="post" action="' . esc_url( hub_url( 'confirm-email/resend/' ) ) . '" class="mt-2">' . wp_nonce_field( 'hub_account', '_hub_nonce', true, false )
		. '<button class="ho-btn ho-btn--outline ho-btn--sm" type="submit">Resend the email</button></form>'
		. '</div></div>';
}

/** Account billing: the failed-payment warning, shown while Stripe is retrying (subscription past due). */
function hub_view_billing_failed_notice() {
	$b = hub_billing_status();
	if ( 'past_due' !== $b['status'] ) {
		return;
	}
	echo '<div class="notice notice--warn mb-5"><svg class="ho-icon"><use href="#i-alert"/></svg><div>'
		. '<p><strong>We couldn&rsquo;t take your last payment.</strong> Update your card to keep ' . esc_html( $b['plan_name'] ) . ' plan features. We&rsquo;ll try again automatically over the next few days.</p>'
		. '<p><a href="' . esc_url( hub_url( 'billing/portal/' ) ) . '">Update your card</a></p></div></div>';
}

/** ---------------------------------------------------------------- account billing: the current plan card */
function hub_view_billing_plan_card() {
	$b      = hub_billing_status();
	$notice = isset( $_GET['checkout'] ) ? sanitize_key( $_GET['checkout'] ) : ( isset( $_GET['billing'] ) ? sanitize_key( $_GET['billing'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
	$notes  = array(
		'success'     => array( 'ok', 'Thanks, your payment went through.', 'Your plan switches on as soon as Stripe confirms it, usually within a minute. Refresh this page to see it.' ),
		'cancelled'   => array( 'plain', 'Payment cancelled.', 'Nothing was charged. Choose a plan whenever you&rsquo;re ready.' ),
		'unavailable' => array( 'warn', 'Online payments aren&rsquo;t switched on yet.', 'Please contact hub@harrowonline.org to upgrade, and we&rsquo;ll set it up for you.' ),
		'error'       => array( 'warn', 'We couldn&rsquo;t reach the payment service.', 'Nothing was charged. Please try again in a minute.' ),
		'no_business' => array( 'warn', 'Plans are for a business listing.', 'Your account isn&rsquo;t linked to a business yet. <a href="' . esc_url( hub_url( 'search/' ) ) . '">Find your business</a> and claim it, then choose a plan.' ),
	);
	if ( isset( $notes[ $notice ] ) ) {
		list( $kind, $title, $text ) = $notes[ $notice ];
		echo '<div class="notice notice--' . esc_attr( $kind ) . ' mb-5" role="status"><svg class="ho-icon"><use href="#i-' . ( 'ok' === $kind ? 'check' : 'alert' ) . '"/></svg><div><p><strong>' . wp_kses( $title, array() ) . '</strong> ' . wp_kses( $text, array( 'a' => array( 'href' => true ) ) ) . '</p></div></div>';
	}
	if ( ! $b ) {
		return;
	}
	$portal = hub_url( 'billing/portal/' );
	if ( $b['has_subscription'] ) {
		$title = $b['plan_name'] . ', paid ' . ( 'annual' === $b['interval'] ? 'annually' : 'monthly' );
		$line  = esc_html( $b['price'] . ' a ' . $b['price_per'] );
		if ( $b['renews_on'] ) {
			$line .= ' &middot; ' . ( $b['cancels_at_period_end'] ? 'ends on ' : 'renews on ' ) . esc_html( wp_date( 'j F Y', strtotime( $b['renews_on'] ) ) );
		}
		$ok    = in_array( $b['status'], array( 'active', 'trialing' ), true );
		$line .= ' &middot; <span class="status status--' . ( $ok ? 'active' : 'pending' ) . '">' . esc_html( $b['status_label'] ) . '</span>';
		$extra = $b['annual_saving'] ? '<p class="small mb-0 mt-2">Switch to annual billing and save ' . esc_html( $b['annual_saving'] ) . ' a year.</p>' : '';
		$btn   = '<a class="ho-btn ho-btn--primary ho-btn--sm" href="' . esc_url( $portal ) . '">' . ( $b['annual_saving'] ? 'Switch to annual' : 'Manage billing' ) . '</a>';
	} elseif ( in_array( $b['plan'], array( 'enhanced', 'premium', 'partner' ), true ) ) {
		// A paid plan with no online subscription: arranged with Harrow Online (launch offer, invoice, or set in wp-admin)
		$title = $b['plan_name'];
		$line  = 'Arranged with Harrow Online, not billed online. Questions? Email hub@harrowonline.org.';
		$extra = '';
		$btn   = '';
	} else {
		$title = 'Basic, free';
		$line  = 'No subscription. Upgrade for Local Offers, events, more photos and a place at the top of your category.';
		$extra = '';
		$btn   = '<a class="ho-btn ho-btn--primary ho-btn--sm" href="' . esc_url( hub_url( 'billing/checkout/?plan=featured&billing=monthly' ) ) . '">Upgrade to Featured</a>';
	}
	echo '<section class="plan-card" aria-labelledby="cur-plan"><div><span class="eyebrow">Current plan</span><h2 id="cur-plan">' . esc_html( $title ) . '</h2>'
		. '<p class="mb-0">' . $line . '</p>' . $extra . '</div><div class="plan-card__actions">' . $btn . '</div></section>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above
}

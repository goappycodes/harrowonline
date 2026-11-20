<?php
/**
 * Per-page settings (the prototype's front matter: title, description, nav, hubnav, body, robots, layout, nl)
 * and the helpers the header, footer and templates use.
 */
defined( 'ABSPATH' ) || exit;

/** The Hub's public address. Shared links, canonical links and structured data always point here, whichever server renders the page. */
const HUB_ORIGIN = 'https://harrowonline.org/hub';

/** Hub URL from a path: hub_url( 'events/' ) => https://harrowonline.org/hub/events/ */
function hub_url( $path = '' ) {
	return home_url( '/' . ltrim( (string) $path, '/' ) );
}

/** Theme asset URL: hub_asset( 'img/hub-logo.svg' ) */
function hub_asset( $path ) {
	return get_theme_file_uri( 'assets/' . ltrim( (string) $path, '/' ) );
}

/**
 * A part's HTML as a string, for code that builds pages as strings:
 *   hub_part( 'offers/card-full', array( 'offer' => $offer ) )  ->  parts/offers/card-full.php
 * Templates call get_template_part( 'parts/...', null, $args ) directly.
 */
function hub_part( $slug, array $args = array() ) {
	ob_start();
	get_template_part( 'parts/' . $slug, null, $args );
	return ob_get_clean();
}

/** Set by each page template before get_header(). */
function hub_set_page( array $meta ) {
	$GLOBALS['hub_page_meta'] = $meta;
}

/** Read one page setting (or all of them). */
function hub_page( $key = null ) {
	$meta = isset( $GLOBALS['hub_page_meta'] ) ? $GLOBALS['hub_page_meta'] : array();
	if ( null === $key ) {
		return $meta;
	}
	return isset( $meta[ $key ] ) ? $meta[ $key ] : '';
}

/** Current main-menu item: search, category, area and business pages count as "businesses" (as build.py). */
function hub_current_nav() {
	if ( 'directory' === hub_page( 'hubnav' ) ) {
		return 'businesses';
	}
	return hub_page( 'nav' ) ? hub_page( 'nav' ) : 'hub';
}

/** Main menu: echoes ' class="current-menu-item"', ' current-menu-item' or ' aria-current="page"' on the current item. */
function hub_nav_attr( $item, $kind ) {
	if ( $item !== hub_current_nav() ) {
		return;
	}
	if ( 'class' === $kind ) {
		echo ' class="current-menu-item"';
	} elseif ( 'cls' === $kind ) {
		echo ' current-menu-item';
	} else {
		echo ' aria-current="page"';
	}
}

/** Hub sub-menu (Businesses dropdown). */
function hub_hubnav_attr( $item ) {
	if ( $item === hub_page( 'hubnav' ) ) {
		echo ' aria-current="page"';
	}
}

/** Phone tab bar. */
function hub_tab_attr( $item ) {
	$map = array( 'directory' => 'search', 'offers' => 'offers', 'events' => 'events', 'account' => 'account' );
	$tab = is_front_page() ? 'home' : ( isset( $map[ hub_page( 'hubnav' ) ] ) ? $map[ hub_page( 'hubnav' ) ] : '' );
	if ( $item === $tab ) {
		echo ' aria-current="page"';
	}
}

/** <html data-base="/hub" data-theme="..."> for hub.js (page links and theme assets). */
function hub_html_attrs() {
	$base = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	printf( ' data-base="%s" data-theme="%s"', esc_attr( $base ), esc_attr( untrailingslashit( get_template_directory_uri() ) ) );
}

/** <title>: page title + " | Harrow Online Hub" unless it already says Harrow Online (as build.py). Yoast SEO, when active, uses the same (inc/seo.php). */
add_filter( 'pre_get_document_title', function ( $title ) {
	$t = hub_page( 'title' );
	if ( ! $t ) {
		return $title;
	}
	return false === strpos( $t, 'Harrow Online' ) ? $t . ' | Harrow Online Hub' : $t;
} );

/** Meta description from the page settings (with Yoast SEO active, Yoast prints it: inc/seo.php). */
add_action( 'wp_head', function () {
	$d = hub_page( 'description' );
	if ( $d && ! defined( 'WPSEO_VERSION' ) ) {
		printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( wp_strip_all_tags( html_entity_decode( $d ) ) ) );
	}
}, 1 );

/**
 * Robots: the page's own rule (e.g. "noindex, follow" for imported listings) goes through WordPress's single
 * robots tag. When the site is set to discourage search engines (local and staging), WordPress's noindex wins.
 */
add_filter( 'wp_robots', function ( $robots ) {
	$r = hub_page( 'robots' );
	if ( $r && get_option( 'blog_public' ) ) {
		foreach ( array_map( 'trim', explode( ',', strtolower( $r ) ) ) as $rule ) {
			if ( '' !== $rule ) {
				$robots[ $rule ] = true;
			}
		}
	}
	return $robots;
} );

/** Body classes: site-hub plus the page's own (e.g. "page-pricing"). */
add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'site-hub';
	foreach ( preg_split( '/\s+/', (string) hub_page( 'body' ), -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
		$classes[] = sanitize_html_class( $c );
	}
	return $classes;
} );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
} );

/** Clean front end: no admin bar over the design, no emoji script. */
add_filter( 'show_admin_bar', '__return_false' );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/** Favicon and app icons: the Harrow Online symbol, as on harrowonline.org. */
add_action( 'wp_head', function () {
	$i = function ( $size ) {
		return esc_url( hub_asset( "img/favicon/harrow-online-{$size}.jpg" ) );
	};
	echo '<link rel="icon" href="' . $i( '32x32' ) . "\" sizes=\"32x32\">\n";
	echo '<link rel="icon" href="' . $i( '192x192' ) . "\" sizes=\"192x192\">\n";
	echo '<link rel="apple-touch-icon" href="' . $i( '180x180' ) . "\">\n";
	echo '<meta name="msapplication-TileImage" content="' . $i( '270x270' ) . "\">\n";
	echo "<meta name=\"theme-color\" content=\"#DF2028\">\n";
}, 2 );

/** Structured data (JSON-LD) added by the content templates goes in <head>, as in the prototype. */
function hub_add_ld( $json ) {
	$GLOBALS['hub_ld'][] = $json;
}
add_action( 'wp_head', function () {
	foreach ( isset( $GLOBALS['hub_ld'] ) ? $GLOBALS['hub_ld'] : array() as $json ) {
		echo "<script type=\"application/ld+json\">\n" . $json . "\n</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- built with wp_json_encode
	}
}, 30 );

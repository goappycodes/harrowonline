<?php
/**
 * Menus (Appearance > Menus):
 *   - "Main menu" (location: Main menu): the header and the phone drawer. Second-level items make a dropdown. An item's
 *     CSS classes (Screen Options > CSS Classes) are kept; "icon-plus" (or icon-star, icon-tag…) puts that icon first.
 *   - "Social links" (location: Social links): the icons in the drawer and the footer. The icon follows the address
 *     (facebook.com, x.com / twitter.com, instagram.com, tiktok.com, whatsapp.com…); the item's title is read out.
 * The theme draws them in its own markup and marks the current section as before (hub_current_nav()). Until a menu is
 * set to a location, today's links show (hub_menu_defaults()). inc/menus-setup.php creates the menus and widgets.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		'primary' => 'Main menu (header and phone drawer)',
		'social'  => 'Social links (drawer and footer icons)',
	) );
} );

/**
 * Today's menus: [ key => [ menu name, rows ] ]. A row: [ title, target, CSS classes, child rows ]. A target is
 * "page:{path}" (a WordPress Page, "page:" the front page), a Hub path ("/hub/#areas") or a full address.
 */
function hub_menu_defaults() {
	$nl = 'https://harrowonline.org/';
	return array(
		'primary'         => array( 'Main menu', array(
			array( 'Hub', 'page:' ),
			array( 'News', $nl . 'category/news/' ),
			array( 'What’s On', 'page:events' ),
			array( 'Classes', 'page:events/classes' ),
			array( 'Offers', 'page:offers' ),
			array( 'Businesses', 'page:search', '', array(
				array( 'Categories', 'page:categories' ),
				array( 'Areas', '/hub/#areas' ),
				array( 'Pricing', 'page:pricing' ),
				array( 'List your business', 'page:add-business', 'sub-menu__cta icon-plus' ),
			) ),
			array( 'Advertise', 'page:advertise/newsletter' ),
		) ),
		'social'          => array( 'Social links', array(
			array( 'Facebook', 'https://www.facebook.com/harrowonline' ),
			array( 'X (Twitter)', 'https://twitter.com/harrowonline' ),
			array( 'Instagram', 'https://www.instagram.com/harrowonline1/' ),
			array( 'TikTok', 'https://www.tiktok.com/@harrowonline' ),
			array( 'WhatsApp Channel', 'https://whatsapp.com/channel/0029Va9qE38HgZWZQVM1jc3S' ),
		) ),
		'footer-sections' => array( 'Footer: Sections', array(
			array( 'News', $nl . 'category/news/' ),
			array( 'Articles', $nl . 'category/articles/' ),
			array( 'Crime', $nl . 'category/crime/' ),
			array( 'Business', $nl . 'category/business/' ),
			array( 'Events', 'page:events' ),
		) ),
		'footer-about'    => array( 'Footer: About', array(
			array( 'About us', $nl . 'about-us/' ),
			array( 'Editorial team', $nl . 'editorial-team/' ),
			array( 'Newsletter', $nl . 'newsletter/' ),
			array( 'Advertise', $nl . 'advertise/' ),
			array( 'Contact us', $nl . 'contact-us/' ),
		) ),
		'footer-legal'    => array( 'Footer: Legal', array(
			array( 'Privacy policy', $nl . 'privacy-policy-2/' ),
			array( 'Policies', $nl . 'policies/' ),
			array( 'Complaints procedure', $nl . 'complaints-procedure/' ),
		) ),
		'footer-topics'   => array( 'Footer: Topics', array_map( function ( $t ) use ( $nl ) {
			return array( $t[0], $nl . 'category/' . $t[1] . '/' );
		}, array(
			array( 'Articles', 'articles' ), array( 'Business', 'business' ), array( 'Community', 'community' ), array( 'Crime', 'crime' ),
			array( 'Education', 'education' ), array( 'Events', 'events' ), array( 'Food', 'food' ), array( 'Harrow Council', 'harrow-council' ),
			array( 'Harrow Police Updates', 'harrow-police-updates' ), array( 'Health', 'health' ), array( 'History', 'history' ),
			array( 'London News', 'london-news' ), array( 'More News', 'more-news' ), array( 'News', 'news' ), array( 'Reviews', 'reviews' ),
			array( 'Sports', 'sports' ), array( 'Travel', 'travel' ), array( 'Weather', 'weather' ),
		) ) ),
	);
}

/** A default row's target as a full URL. */
function hub_menu_target_url( $target ) {
	if ( 0 === strpos( $target, 'page:' ) ) {
		$path = substr( $target, 5 );
		return hub_url( '' === $path ? '' : $path . '/' );
	}
	return hub_link( $target );
}

/** A default menu as menu items (the same objects wp_get_nav_menu_items() gives), for when no menu is set up. */
function hub_menu_default_items( $key ) {
	$defaults = hub_menu_defaults();
	$items    = array();
	$add      = function ( array $rows, $parent ) use ( &$add, &$items ) {
		foreach ( $rows as $r ) {
			$id      = 990000 + count( $items ) + 1;
			$items[] = (object) array(
				'ID' => $id, 'db_id' => $id, 'menu_item_parent' => $parent, 'title' => $r[0], 'url' => hub_menu_target_url( $r[1] ),
				'type' => 'custom', 'object' => 'custom', 'target' => '', 'attr_title' => '', 'xfn' => '', 'description' => '',
				'classes' => array(), 'hub_own_classes' => preg_split( '/\s+/', isset( $r[2] ) ? $r[2] : '', -1, PREG_SPLIT_NO_EMPTY ),
			);
			if ( ! empty( $r[3] ) ) {
				$add( $r[3], $id );
			}
		}
	};
	$add( isset( $defaults[ $key ] ) ? $defaults[ $key ][1] : array(), 0 );
	return $items;
}

/** A location's menu items, or today's links while no menu is set to it. */
function hub_menu_items( $location ) {
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations[ $location ] ) && wp_get_nav_menu_object( $locations[ $location ] ) ) {
		return (array) wp_get_nav_menu_items( $locations[ $location ] );
	}
	return hub_menu_default_items( $location );
}

/** An item's address: Pages and posts as WordPress gives them; typed links through hub_link() ("/hub/…" works anywhere). */
function hub_menu_item_url( $item ) {
	return 'custom' === $item->type ? hub_link( $item->url ) : (string) $item->url;
}

/** The CSS classes typed for an item in Appearance > Menus (not the ones WordPress adds). */
function hub_menu_own_classes( $item ) {
	$own = isset( $item->hub_own_classes ) ? $item->hub_own_classes : get_post_meta( $item->ID, '_menu_item_classes', true );
	return array_values( array_filter( array_map( 'sanitize_html_class', (array) $own ) ) );
}

/** Is this item the current page or section? Top level: the page's section (hub_current_nav()); below: hub_page( 'hubnav' ). */
function hub_menu_is_current( $url, $top ) {
	static $now = null;
	$norm = function ( $u ) {
		$u = (string) $u;
		return false !== strpos( $u, '#' ) ? '' : untrailingslashit( strtolower( strtok( $u, '?' ) ) );
	};
	if ( null === $now ) {
		$sections = array( 'hub' => '', 'events' => 'events/', 'classes' => 'events/classes/', 'offers' => 'offers/', 'businesses' => 'search/', 'advertise' => 'advertise/newsletter/' );
		$subs     = array( 'categories' => 'categories/', 'pricing' => 'pricing/' );
		$nav      = hub_current_nav();
		$sub      = hub_page( 'hubnav' );
		$now      = array(
			'top'  => isset( $sections[ $nav ] ) ? $norm( hub_url( $sections[ $nav ] ) ) : null,
			'sub'  => isset( $subs[ $sub ] ) ? $norm( hub_url( $subs[ $sub ] ) ) : null,
		);
	}
	$u = $norm( $url );
	return '' !== $u && $u === $now[ $top ? 'top' : 'sub' ];
}

/** The main menu's markup: top items, each dropdown with its toggle button, the current item marked. */
class Hub_Menu_Walker extends Walker_Nav_Menu {
	private $sub_id;
	private $subs = 0;

	public function __construct( $sub_id ) {
		$this->sub_id = $sub_id;
	}

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		// opened with its toggle button in start_el()
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item  = $data_object;
		$url   = hub_menu_item_url( $item );
		$top   = 0 === $depth;
		$cur   = hub_menu_is_current( $url, $top );
		$icon  = '';
		$own   = array();
		foreach ( hub_menu_own_classes( $item ) as $c ) {
			if ( 0 === strpos( $c, 'icon-' ) ) {
				$icon = '<svg class="ho-icon"><use href="#i-' . esc_attr( substr( $c, 5 ) ) . '"/></svg>';
			} else {
				$own[] = $c;
			}
		}
		if ( $top ) {
			$cls = array_merge( array( 'menu-item' ), $this->has_children ? array( 'menu-item-has-children' ) : array(), $cur ? array( 'current-menu-item' ) : array(), $own );
			$output .= '<li class="' . esc_attr( implode( ' ', $cls ) ) . '">';
		} else {
			$output .= '<li' . ( $own ? ' class="' . esc_attr( implode( ' ', $own ) ) . '"' : '' ) . '>';
		}
		$new_tab = '_blank' === $item->target ? ' target="_blank" rel="noopener"' : '';
		$output .= '<a href="' . esc_url( $url ) . '"' . ( $cur ? ' aria-current="page"' : '' ) . $new_tab . '>' . $icon . esc_html( $item->title ) . '</a>';
		if ( $this->has_children ) {
			$id = $this->sub_id . ( $this->subs ? '-' . ( $this->subs + 1 ) : '' );
			$this->subs++;
			$output .= '<button class="ho-nav__toggle" type="button" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '" aria-label="' . esc_attr( 'More under ' . $item->title ) . '" data-ho-sub-toggle><svg class="ho-icon"><use href="#i-chevron-down"/></svg></button><ul class="sub-menu" id="' . esc_attr( $id ) . '">';
		}
	}

	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		$output .= '</li>' . ( 0 === $depth ? "\n" : '' );
	}
}

/** Plain lists (footer menus): <li><a href="…">Title</a></li>, nothing else. */
class Hub_Plain_Menu_Walker extends Walker_Nav_Menu {
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '<ul>';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item    = $data_object;
		$new_tab = '_blank' === $item->target ? ' target="_blank" rel="noopener"' : '';
		$output .= '<li><a href="' . esc_url( hub_menu_item_url( $item ) ) . '"' . $new_tab . '>' . esc_html( $item->title ) . '</a>';
	}

	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}

/** The main menu: $where "header" (desktop) or "drawer" (phones). */
function hub_main_menu( $where ) {
	$drawer = 'drawer' === $where;
	$walker = new Hub_Menu_Walker( $drawer ? 'ho-hub-menu-m' : 'ho-hub-menu' );
	$ul     = '<ul id="' . ( $drawer ? 'ho-menu-mobile' : 'ho-menu' ) . '" class="' . ( $drawer ? 'ho-drawer__list' : 'ho-nav__list' ) . '">%s</ul>';
	$items  = hub_menu_items( 'primary' );
	printf( $ul, walk_nav_menu_tree( $items, 2, (object) array( 'walker' => $walker, 'depth' => 2 ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the walker
}

/** A footer menu as plain list items (no <ul>), from a menu ID or a default key ("footer-sections"). */
function hub_plain_menu_items( $menu ) {
	$items = is_numeric( $menu ) ? (array) wp_get_nav_menu_items( (int) $menu ) : hub_menu_default_items( $menu );
	return walk_nav_menu_tree( $items, 1, (object) array( 'walker' => new Hub_Plain_Menu_Walker(), 'depth' => 1 ) );
}

/** The social icons: $variant "dark" (drawer) or "light" (footer). */
function hub_social_links( $variant ) {
	$out = '';
	foreach ( hub_menu_items( 'social' ) as $item ) {
		if ( (int) $item->menu_item_parent ) {
			continue;
		}
		$url  = hub_menu_item_url( $item );
		$net  = hub_social_network( $url );
		$out .= '<li><a class="ho-social__link ho-social__link--' . esc_attr( $net ) . '" href="' . esc_url( $url ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $item->title ) . '">' . hub_social_icon( $net ) . '</a></li>';
	}
	return '' === $out ? '' : '<ul class="ho-social ho-social--' . esc_attr( $variant ) . '">' . $out . '</ul>';
}

/** The network a social link points to, from its address ("globe" for any other site). */
function hub_social_network( $url ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$host = preg_replace( '/^(www|m)\./', '', $host );
	$map  = array(
		'facebook.com' => 'facebook', 'fb.com' => 'facebook', 'x.com' => 'x', 'twitter.com' => 'x', 'instagram.com' => 'instagram',
		'tiktok.com' => 'tiktok', 'whatsapp.com' => 'whatsapp', 'wa.me' => 'whatsapp', 'linkedin.com' => 'linkedin',
	);
	return isset( $map[ $host ] ) ? $map[ $host ] : 'globe';
}

/** A social icon: the header's own drawings, or the icon sprite for others. */
function hub_social_icon( $net ) {
	$drawn = array(
		'facebook' => '<svg class="ho-icon ho-icon--facebook" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.5-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H8v3h2.6V21h2.9z"/></svg>',
		'x' => '<svg class="ho-icon ho-icon--x" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.8 3h3.1l-6.8 7.8L22 21h-6.2l-4.9-6.4L5.3 21H2.2l7.3-8.3L2 3h6.4l4.4 5.8L17.8 3zm-1.1 16.2h1.7L7.4 4.7H5.6l11.1 14.5z"/></svg>',
		'instagram' => '<svg class="ho-icon ho-icon--instagram" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.3" cy="6.7" r="1.2" fill="currentColor"/></svg>',
		'tiktok' => '<svg class="ho-icon ho-icon--tiktok" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16.6 3c.3 2.2 1.6 3.7 3.9 3.9v2.8c-1.4.1-2.7-.3-3.9-1.1v6c0 3.6-2.6 5.9-5.8 5.9-3.1 0-5.6-2.4-5.6-5.6 0-3.6 3.2-6.1 6.8-5.5v3c-1.6-.4-3.8.4-3.8 2.5 0 1.5 1.2 2.7 2.7 2.7 1.7 0 2.8-1 2.8-3V3h2.9z"/></svg>',
		'whatsapp' => '<svg class="ho-icon ho-icon--whatsapp" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.5a9.5 9.5 0 0 0-8.2 14.3L2.5 21.5l4.8-1.3A9.5 9.5 0 1 0 12 2.5zm0 17.2c-1.5 0-2.9-.4-4.1-1.1l-.3-.2-2.8.8.8-2.8-.2-.3A7.7 7.7 0 1 1 12 19.7zm4.3-5.8c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6.3 6.3 0 0 1-3.1-2.7c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.7-1.7c-.2-.4-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.4c.1.2 1.6 2.5 4 3.5 1.5.6 2.1.7 2.8.6.5-.1 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1l-.6-.3z"/></svg>',
	);
	return isset( $drawn[ $net ] ) ? $drawn[ $net ] : '<svg class="ho-icon ho-icon--' . esc_attr( $net ) . '" aria-hidden="true"><use href="#i-' . esc_attr( $net ) . '"/></svg>';
}

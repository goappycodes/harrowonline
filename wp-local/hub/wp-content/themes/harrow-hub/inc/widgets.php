<?php
/**
 * Footer widgets (Appearance > Widgets, the classic screen). Six footer areas; each is drawn in the footer's own
 * markup. An area with no widgets shows today's content (hub_footer_defaults()), so the footer never goes blank.
 *   Footer: under the logo   a Text widget (the line under the logo)
 *   Footer: link columns     Navigation Menu widgets, one per column; the widget title is the column heading
 *   Footer: newsletter       the "Hub: Newsletter" widget
 *   Footer: topics row       a Navigation Menu widget
 *   Footer: badges           the "Hub: Badges" widget (IMPRESS, ICNN…)
 *   Footer: legal text       a Text widget (copyright and company details; links like /hub/privacy/ work anywhere)
 */
defined( 'ABSPATH' ) || exit;

/** Today's footer: the defaults for the areas (and for new widgets). */
function hub_footer_defaults() {
	return array(
		'tagline'    => 'Local news for a stronger Harrow.',
		'columns'    => array( 'Sections' => 'footer-sections', 'About' => 'footer-about', 'Legal' => 'footer-legal' ),
		'topics'     => array( 'Topics', 'footer-topics' ),
		'newsletter' => array(
			'title'   => 'Newsletter',
			'text'    => 'Harrow’s local news, free in your inbox.',
			'button'  => 'Sign up free',
			'url'     => 'https://harrowonline.org/newsletter/',
			'wa_text' => 'Follow our WhatsApp Channel',
			'wa_url'  => 'https://whatsapp.com/channel/0029Va9qE38HgZWZQVM1jc3S',
		),
		'badges'     => array(
			array( 'image' => 'https://harrowonline.org/wp-content/uploads/2023/03/IMPRESS-Regulated-Member-Trustmark_White_RGB-300x300.png', 'width' => 300, 'height' => 300, 'link' => 'https://www.impressorg.com/', 'text' => 'Regulated by IMPRESS' ),
			array( 'image' => 'https://harrowonline.org/wp-content/uploads/2020/08/Untitled-design-60-1-300x166.png', 'width' => 300, 'height' => 166, 'link' => 'https://www.communityjournalism.co.uk/', 'text' => 'Proud member of the Independent Community News Network (ICNN)' ),
		),
		'legal'      => "<p>&copy; 2026 Harrow Online Ltd. All rights reserved.</p>\n<p>Harrow Online Ltd is registered in England and Wales. Company number: 16370670. Registered office: 79 College Road, Harrow, HA1 1BD.</p>\n<p>Hub business listings include data from <a href=\"/hub/data-sources/\">Overture Maps Foundation</a>. <a href=\"/hub/privacy/\">Hub privacy</a> &middot; <a href=\"/hub/terms/\">Hub terms</a></p>",
	);
}

/** The footer areas and their markup. */
function hub_footer_areas() {
	$title = array( 'before_title' => '<p class="ho-footer__title">', 'after_title' => '</p>' );
	return array(
		'footer-brand'      => array( 'Footer: under the logo', 'A Text widget: the line under the footer logo. The social icons are the “Social links” menu.', '<div class="ho-footer__tagline">', '</div>' ) + $title,
		'footer-columns'    => array( 'Footer: link columns', 'Navigation Menu widgets, one per column. The widget title is the column heading.', '<nav class="ho-footer__col" aria-label="{title}">', '</nav>' ) + $title,
		'footer-newsletter' => array( 'Footer: newsletter', 'The “Hub: Newsletter” widget.', '<div class="ho-footer__news">', '</div>' ) + $title,
		'footer-topics'     => array( 'Footer: topics row', 'A Navigation Menu widget. The widget title is the heading.', '<nav class="ho-footer__topics" aria-label="{title}">', '</nav>' ) + $title,
		'footer-badges'     => array( 'Footer: badges', 'The “Hub: Badges” widget.', '', '' ) + $title,
		'footer-legal'      => array( 'Footer: legal text', 'A Text widget: copyright and company details. Links like /hub/privacy/ work on every server.', '<div class="ho-footer__legal">', '</div>' ) + $title,
	);
}

// The classic Widgets screen (not the block editor): plain widgets that keep the footer's own look
add_action( 'after_setup_theme', function () {
	remove_theme_support( 'widgets-block-editor' );
}, 20 );

add_action( 'widgets_init', function () {
	foreach ( hub_footer_areas() as $id => $a ) {
		register_sidebar( array(
			'id' => $id, 'name' => $a[0], 'description' => $a[1], 'before_widget' => $a[2], 'after_widget' => $a[3],
			'before_title' => $a['before_title'], 'after_title' => $a['after_title'],
		) );
	}
	register_widget( 'Hub_Newsletter_Widget' );
	register_widget( 'Hub_Badges_Widget' );
} );

/** Print a footer area, or today's content while it has no widgets. */
function hub_footer_area( $id ) {
	if ( is_active_sidebar( $id ) ) {
		dynamic_sidebar( $id );
		return;
	}
	$d    = hub_footer_defaults();
	$a    = hub_footer_areas()[ $id ];
	$args = array( 'before_widget' => $a[2], 'after_widget' => $a[3], 'before_title' => $a['before_title'], 'after_title' => $a['after_title'] );
	switch ( $id ) {
		case 'footer-brand':
			echo '<p class="ho-footer__tagline">' . esc_html( $d['tagline'] ) . '</p>';
			break;
		case 'footer-columns':
			foreach ( $d['columns'] as $title => $menu ) {
				echo '<nav class="ho-footer__col" aria-label="' . esc_attr( $title ) . '"><p class="ho-footer__title">' . esc_html( $title ) . '</p><ul class="ho-footer__links">' . hub_plain_menu_items( $menu ) . '</ul></nav>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the walker
			}
			break;
		case 'footer-topics':
			echo '<nav class="ho-footer__topics" aria-label="' . esc_attr( $d['topics'][0] ) . '"><p class="ho-footer__title">' . esc_html( $d['topics'][0] ) . '</p><ul>' . hub_plain_menu_items( $d['topics'][1] ) . '</ul></nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'footer-newsletter':
			the_widget( 'Hub_Newsletter_Widget', $d['newsletter'], $args );
			break;
		case 'footer-badges':
			the_widget( 'Hub_Badges_Widget', array( 'badges' => $d['badges'] ), $args );
			break;
		case 'footer-legal':
			echo '<div class="ho-footer__legal">' . wp_kses_post( hub_html_links( $d['legal'] ) ) . '</div>';
			break;
	}
}

add_filter( 'widget_text_content', 'hub_html_links' );   // "/hub/privacy/" links work on every server (app/core/url.php)

/** Footer menus: plain lists in the footer's classes (no wrapper, no WordPress classes). */
add_filter( 'widget_nav_menu_args', function ( $nav_args, $nav_menu, $args ) {
	$id = isset( $args['id'] ) ? $args['id'] : '';
	if ( in_array( $id, array( 'footer-columns', 'footer-topics' ), true ) ) {
		$nav_args['container']  = false;
		$nav_args['items_wrap'] = 'footer-columns' === $id ? '<ul class="ho-footer__links">%3$s</ul>' : '<ul>%3$s</ul>';
		$nav_args['walker']     = new Hub_Plain_Menu_Walker();
		$nav_args['depth']      = 1;
	}
	return $nav_args;
}, 10, 3 );

/** The footer navs are named after their widget title (aria-label). */
add_filter( 'dynamic_sidebar_params', function ( $params ) {
	global $wp_registered_widgets;
	if ( false === strpos( $params[0]['before_widget'], '{title}' ) ) {
		return $params;
	}
	$title  = '';
	$widget = isset( $wp_registered_widgets[ $params[0]['widget_id'] ] ) ? $wp_registered_widgets[ $params[0]['widget_id'] ] : null;
	if ( $widget && is_array( $widget['callback'] ) && $widget['callback'][0] instanceof WP_Widget && isset( $params[1]['number'] ) ) {
		$settings = $widget['callback'][0]->get_settings();
		$title    = isset( $settings[ $params[1]['number'] ]['title'] ) ? (string) $settings[ $params[1]['number'] ]['title'] : '';
	}
	$params[0]['before_widget'] = '' !== $title
		? str_replace( '{title}', esc_attr( $title ), $params[0]['before_widget'] )
		: str_replace( ' aria-label="{title}"', '', $params[0]['before_widget'] );
	return $params;
} );

/** A Text widget's wrapper (div.textwidget) steps aside, so the footer's own layout applies to the paragraphs. */
add_action( 'wp_enqueue_scripts', function () {
	wp_add_inline_style( 'ho-hub', '.ho-footer__tagline p{margin:0}.ho-footer__legal>.textwidget{display:contents}' );
}, 30 );

/** Hub: Newsletter (the footer's newsletter column). */
class Hub_Newsletter_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct( 'hub_newsletter', 'Hub: Newsletter', array( 'description' => 'Title, short text, a sign-up button and a WhatsApp link.' ) );
	}

	private function fields() {
		return array(
			'title'   => array( 'Title', 'text' ),
			'text'    => array( 'Text', 'textarea' ),
			'button'  => array( 'Button text', 'text' ),
			'url'     => array( 'Button URL', 'url' ),
			'wa_text' => array( 'WhatsApp link text', 'text' ),
			'wa_url'  => array( 'WhatsApp URL', 'url' ),
		);
	}

	public function widget( $args, $instance ) {
		$i = wp_parse_args( $instance, array_fill_keys( array_keys( $this->fields() ), '' ) );
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput -- the area's own markup
		if ( '' !== $i['title'] ) {
			echo $args['before_title'] . esc_html( $i['title'] ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( '' !== $i['text'] ) {
			echo '<p class="ho-footer__text">' . esc_html( $i['text'] ) . '</p>';
		}
		if ( '' !== $i['button'] && '' !== $i['url'] ) {
			echo "\n" . '<a class="ho-btn ho-btn--primary" href="' . esc_url( hub_link( $i['url'] ) ) . '">' . '<svg class="ho-icon ho-icon--mail" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>' . '<span>' . esc_html( $i['button'] ) . '</span></a>';
		}
		if ( '' !== $i['wa_text'] && '' !== $i['wa_url'] ) {
			echo "\n" . '<a class="ho-footer__wa" href="' . esc_url( $i['wa_url'] ) . '" target="_blank" rel="noopener">' . '<svg class="ho-icon ho-icon--whatsapp" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.5a9.5 9.5 0 0 0-8.2 14.3L2.5 21.5l4.8-1.3A9.5 9.5 0 1 0 12 2.5zm0 17.2c-1.5 0-2.9-.4-4.1-1.1l-.3-.2-2.8.8.8-2.8-.2-.3A7.7 7.7 0 1 1 12 19.7zm4.3-5.8c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6.3 6.3 0 0 1-3.1-2.7c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.7-1.7c-.2-.4-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.4c.1.2 1.6 2.5 4 3.5 1.5.6 2.1.7 2.8.6.5-.1 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1l-.6-.3z"/></svg>' . '<span>' . esc_html( $i['wa_text'] ) . '</span></a>';
		}
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public function form( $instance ) {
		$i = wp_parse_args( $instance, hub_footer_defaults()['newsletter'] );
		foreach ( $this->fields() as $k => $f ) {
			$id = $this->get_field_id( $k );
			echo '<p><label for="' . esc_attr( $id ) . '">' . esc_html( $f[0] ) . '</label>';
			if ( 'textarea' === $f[1] ) {
				echo '<textarea class="widefat" rows="2" id="' . esc_attr( $id ) . '" name="' . esc_attr( $this->get_field_name( $k ) ) . '">' . esc_textarea( $i[ $k ] ) . '</textarea></p>';
			} else {
				echo '<input class="widefat" type="' . esc_attr( $f[1] ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $this->get_field_name( $k ) ) . '" value="' . esc_attr( $i[ $k ] ) . '"></p>';
			}
		}
		echo '<p class="description">Leave a button’s text or URL empty to hide it.</p>';
		return '';
	}

	public function update( $new, $old ) {
		$out = array();
		foreach ( $this->fields() as $k => $f ) {
			$v         = isset( $new[ $k ] ) ? wp_unslash( $new[ $k ] ) : '';
			$out[ $k ] = 'url' === $f[1] ? esc_url_raw( trim( $v ) ) : ( 'textarea' === $f[1] ? sanitize_textarea_field( $v ) : sanitize_text_field( $v ) );
		}
		return $out;
	}
}

/** Hub: Badges (accreditation logos with links, in the footer's bottom bar). */
class Hub_Badges_Widget extends WP_Widget {
	const SLOTS = 4;

	public function __construct() {
		parent::__construct( 'hub_badges', 'Hub: Badges', array( 'description' => 'Up to 4 logos with links (IMPRESS, ICNN…).' ) );
	}

	public function widget( $args, $instance ) {
		$rows = array_filter( isset( $instance['badges'] ) ? (array) $instance['badges'] : array(), function ( $b ) {
			return ! empty( $b['image'] );
		} );
		if ( ! $rows ) {
			return;
		}
		echo $args['before_widget'] . '<ul class="ho-badges" aria-label="Accreditations">'; // phpcs:ignore WordPress.Security.EscapeOutput
		foreach ( $rows as $b ) {
			$img = '<img src="' . esc_url( $b['image'] ) . '"' . ( ! empty( $b['width'] ) ? ' width="' . (int) $b['width'] . '"' : '' ) . ( ! empty( $b['height'] ) ? ' height="' . (int) $b['height'] . '"' : '' )
				. ' alt="' . esc_attr( $b['text'] ) . '" loading="lazy" decoding="async">';
			echo '<li>' . ( ! empty( $b['link'] ) ? '<a class="ho-badge" href="' . esc_url( $b['link'] ) . '" target="_blank" rel="noopener" title="' . esc_attr( $b['text'] ) . '">' . $img . '</a>' : $img ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</ul>' . $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public function form( $instance ) {
		$rows = isset( $instance['badges'] ) ? (array) $instance['badges'] : hub_footer_defaults()['badges'];
		for ( $n = 0; $n < self::SLOTS; $n++ ) {
			$b = wp_parse_args( isset( $rows[ $n ] ) ? $rows[ $n ] : array(), array( 'image' => '', 'width' => '', 'height' => '', 'link' => '', 'text' => '' ) );
			echo '<fieldset style="margin:0 0 12px;padding:8px 10px;border:1px solid #dcdcde;border-radius:4px"><legend><strong>Badge ' . ( $n + 1 ) . '</strong></legend>';
			foreach ( array( 'image' => 'Image URL', 'link' => 'Link', 'text' => 'Text (read out, shown on hover)' ) as $k => $label ) {
				echo '<p><label>' . esc_html( $label ) . '<input class="widefat" type="' . ( 'text' === $k ? 'text' : 'url' ) . '" name="' . esc_attr( $this->get_field_name( 'badges' ) ) . '[' . $n . '][' . $k . ']" value="' . esc_attr( $b[ $k ] ) . '"></label></p>';
			}
			echo '<p><label>Width <input type="number" min="0" style="width:80px" name="' . esc_attr( $this->get_field_name( 'badges' ) ) . '[' . $n . '][width]" value="' . esc_attr( $b['width'] ) . '"></label> ';
			echo '<label>Height <input type="number" min="0" style="width:80px" name="' . esc_attr( $this->get_field_name( 'badges' ) ) . '[' . $n . '][height]" value="' . esc_attr( $b['height'] ) . '"></label></p></fieldset>';
		}
		echo '<p class="description">Leave the image URL empty to drop a badge.</p>';
		return '';
	}

	public function update( $new, $old ) {
		$out = array();
		foreach ( isset( $new['badges'] ) ? (array) $new['badges'] : array() as $b ) {
			$b = wp_unslash( (array) $b );
			if ( empty( $b['image'] ) ) {
				continue;
			}
			$out[] = array(
				'image'  => esc_url_raw( trim( $b['image'] ) ),
				'width'  => absint( isset( $b['width'] ) ? $b['width'] : 0 ) ?: '',
				'height' => absint( isset( $b['height'] ) ? $b['height'] : 0 ) ?: '',
				'link'   => esc_url_raw( trim( isset( $b['link'] ) ? $b['link'] : '' ) ),
				'text'   => sanitize_text_field( isset( $b['text'] ) ? $b['text'] : '' ),
			);
		}
		return array( 'badges' => array_slice( $out, 0, self::SLOTS ) );
	}
}

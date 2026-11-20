<?php
/**
 * A small field toolkit for the Hub's wp-admin boxes (native WordPress, no plugin): draws a field from its definition
 * and cleans what was sent back. Used by business-box.php and newsletter-page.php; the browser side is
 * admin/hub-admin.js and admin/hub-admin.css.
 *
 * A field definition: array(
 *   'key'     => 'biz_phone',            meta key / option key
 *   'label'   => 'Phone',
 *   'type'    => text | textarea | html (a text box; links and bold kept) | url | email | number | date | time | select | checks | toggle | image | gallery | hours |
 *                list | businesses (several, picked by name; 'max' => 4) | note (an info line) | heading (starts a section)
 *   'help'    => 'Shown under the field',
 *   'width'   => 50,                     % of the row (fields sit side by side until they fill 100)
 *   'choices' => [ value => label ]      select ('other' => true keeps a saved value that is not in the list)
 *   'cols'    => [ key => [ label, type ] ]   list: one column per key, type text | url | check | html (a small text box,
 *                                       links and bold allowed); 'flat' => true stores plain strings
 *   'add'     => 'Add row'               list: button text
 *   'claimed' => true                    only for claimed businesses (hidden while "Claimed" is off)
 *   'unclaimed' => true                  only while "Claimed" is off (notes)
 *   'placeholder' => '…'
 * )
 */
defined( 'ABSPATH' ) || exit;

/** Draw one field. $name is the form name ("hub[biz_phone]"); $value is what is stored. */
function hub_admin_field( array $f, $value, $name ) {
	$type  = $f['type'];
	$id    = 'hub-' . str_replace( '_', '-', $f['key'] );
	$width = isset( $f['width'] ) ? (int) $f['width'] : 100;
	$attrs = ' style="--w:' . $width . '%"';
	if ( ! empty( $f['claimed'] ) ) {
		$attrs .= ' data-claimed-only';
	}
	if ( ! empty( $f['unclaimed'] ) ) {
		$attrs .= ' data-unclaimed-only';
	}
	$ph   = isset( $f['placeholder'] ) ? ' placeholder="' . esc_attr( $f['placeholder'] ) . '"' : '';
	$help = ! empty( $f['help'] ) ? '<p class="hub-f__help">' . wp_kses( $f['help'], array( 'strong' => array(), 'em' => array(), 'code' => array() ) ) . '</p>' : '';

	if ( 'note' === $type ) {
		echo '<div class="hub-f hub-f--note"' . $attrs . '><p>' . wp_kses( $f['label'], array( 'strong' => array(), 'em' => array() ) ) . '</p></div>';
		return;
	}
	if ( 'heading' === $type ) {
		echo '<div class="hub-f hub-f--heading"' . $attrs . '><h4>' . esc_html( $f['label'] ) . '</h4>' . $help . '</div>';
		return;
	}

	echo '<div class="hub-f hub-f--' . esc_attr( $type ) . '"' . $attrs . '>';
	echo '<label class="hub-f__label" for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . '</label>';

	switch ( $type ) {
		case 'html':
		case 'textarea':
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . ( isset( $f['rows'] ) ? (int) $f['rows'] : 2 ) . '"' . $ph . '>' . esc_textarea( hub_admin_plain( $value ) ) . '</textarea>';
			break;

		case 'select':
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
			if ( ! empty( $f['other'] ) && '' !== (string) $value && ! isset( $f['choices'][ (string) $value ] ) ) {
				$f['choices'][ (string) $value ] = hub_admin_plain( $value ) . ' (not in the list)';   // kept as it is
			}
			foreach ( $f['choices'] as $v => $label ) {
				echo '<option value="' . esc_attr( $v ) . '"' . selected( (string) $value, (string) $v, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
			break;

		case 'toggle':
			echo '<span class="hub-switch"><input type="hidden" name="' . esc_attr( $name ) . '" value=""><input id="' . esc_attr( $id ) . '" type="checkbox" role="switch" name="' . esc_attr( $name ) . '" value="1"' . checked( ! empty( $value ), true, false ) . '>'
				. '<span class="hub-switch__track" aria-hidden="true"></span><span class="hub-switch__state" aria-hidden="true"></span></span>';
			break;

		case 'image':
			$url = $value ? wp_get_attachment_image_url( (int) $value, 'thumbnail' ) : '';
			echo '<div class="hub-image" data-hub-image>';
			echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ? (int) $value : '' ) . '">';
			echo '<div class="hub-image__preview">' . ( $url ? '<img src="' . esc_url( $url ) . '" alt="">' : '' ) . '</div>';
			echo '<button type="button" class="button" data-hub-image-pick>' . ( $url ? 'Change image' : 'Choose image' ) . '</button> ';
			echo '<button type="button" class="button-link hub-image__remove" data-hub-image-remove' . ( $url ? '' : ' hidden' ) . '>Remove</button>';
			echo '</div>';
			break;

		case 'gallery':
			echo '<div class="hub-gallery" data-hub-gallery data-name="' . esc_attr( $name ) . '[]"><ul class="hub-gallery__list">';
			foreach ( is_array( $value ) ? $value : array() as $aid ) {
				$u = wp_get_attachment_image_url( (int) $aid, 'thumbnail' );
				if ( $u ) {
					echo '<li><img src="' . esc_url( $u ) . '" alt=""><input type="hidden" name="' . esc_attr( $name ) . '[]" value="' . (int) $aid . '"><button type="button" class="hub-gallery__remove" aria-label="Remove photo">&times;</button></li>';
				}
			}
			echo '</ul><button type="button" class="button" data-hub-gallery-add>Add photos</button> <span class="hub-f__hint">Drag to reorder.</span></div>';
			break;

		case 'checks':
			$on = array_map( 'strval', is_array( $value ) ? $value : array() );
			echo '<input type="hidden" name="' . esc_attr( $name ) . '[]" value=""><ul class="hub-checks">';
			foreach ( $f['choices'] as $v => $label ) {
				echo '<li><label><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $v ) . '"' . checked( in_array( (string) $v, $on, true ), true, false ) . '> ' . esc_html( $label ) . '</label></li>';
			}
			echo '</ul>';
			break;

		case 'businesses':
			$max = isset( $f['max'] ) ? (int) $f['max'] : 0;
			echo '<div class="hub-pick hub-pick--multi" data-hub-pick data-hub-multi="' . esc_attr( $name ) . '[]" data-max="' . $max . '">';
			echo '<input type="hidden" name="' . esc_attr( $name ) . '[]" value=""><ol class="hub-picked" data-hub-picked>';
			foreach ( is_array( $value ) ? $value : array() as $bid ) {
				$bid = (int) $bid;
				if ( $bid && get_post( $bid ) ) {
					echo '<li><input type="hidden" name="' . esc_attr( $name ) . '[]" value="' . $bid . '"><span>' . esc_html( html_entity_decode( get_the_title( $bid ), ENT_QUOTES, 'UTF-8' ) ) . '</span> <button type="button" class="button-link hub-picked__remove" aria-label="Remove">&times;</button></li>';
				}
			}
			echo '</ol><input type="search" class="widefat" placeholder="Search businesses by name to add one…" autocomplete="off" data-hub-pick-search>';
			echo '<ul class="hub-pick__results" data-hub-pick-results hidden></ul></div>';
			break;

		case 'hours':
			$days = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
			echo '<div class="hub-hours">';
			foreach ( $days as $i => $d ) {
				$v = is_array( $value ) && isset( $value[ $i ] ) ? $value[ $i ] : '';
				echo '<label><span>' . esc_html( $d ) . '</span><input type="text" name="' . esc_attr( $name ) . '[' . $i . ']" value="' . esc_attr( hub_admin_plain( $v ) ) . '" placeholder="9am to 5pm, or Closed"></label>';
			}
			echo '</div>';
			break;

		case 'list':
			$rows = is_array( $value ) ? array_values( $value ) : array();
			echo '<div class="hub-list" data-hub-list><table class="widefat striped"><thead><tr>';
			foreach ( $f['cols'] as $col ) {
				echo '<th>' . esc_html( $col[0] ) . '</th>';
			}
			echo '<th class="hub-list__x"></th></tr></thead><tbody>';
			foreach ( $rows as $i => $row ) {
				echo hub_admin_list_row( $f, $name, $i, $row ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside
			}
			echo '</tbody></table>';
			echo '<script type="text/html" data-hub-list-template>' . hub_admin_list_row( $f, $name, '__i__', array() ) . '</script>'; // phpcs:ignore
			echo '<p><button type="button" class="button" data-hub-list-add>' . esc_html( isset( $f['add'] ) ? $f['add'] : 'Add row' ) . '</button></p></div>';
			break;

		default: // text, url, email, number
			$t    = in_array( $type, array( 'url', 'email', 'number', 'date', 'time' ), true ) ? $type : 'text';
			$step = 'number' === $t ? ' step="any"' : '';
			echo '<input id="' . esc_attr( $id ) . '" type="' . $t . '"' . $step . ' name="' . esc_attr( $name ) . '" value="' . esc_attr( hub_admin_plain( $value ) ) . '"' . $ph . '>';
	}
	echo $help . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built with wp_kses above
}

/** Notes and headings only show text: they have no value to read or save. */
function hub_admin_is_display( array $f ) {
	return in_array( $f['type'], array( 'note', 'heading' ), true );
}

/** One row of a list field. */
function hub_admin_list_row( array $f, $name, $i, $row ) {
	$out = '<tr>';
	foreach ( $f['cols'] as $k => $col ) {
		$v    = ! empty( $f['flat'] ) ? ( is_array( $row ) ? '' : $row ) : ( is_array( $row ) && isset( $row[ $k ] ) ? $row[ $k ] : '' );
		$n    = $name . '[' . $i . ']' . ( ! empty( $f['flat'] ) ? '' : '[' . $k . ']' );
		if ( isset( $col[1] ) && 'check' === $col[1] ) {
			$out .= '<td class="hub-list__check"><input type="hidden" name="' . esc_attr( $n ) . '" value=""><input type="checkbox" name="' . esc_attr( $n ) . '" value="1"' . checked( ! empty( $v ), true, false ) . ' aria-label="' . esc_attr( $col[0] ) . '"></td>';
			continue;
		}
		if ( isset( $col[1] ) && 'html' === $col[1] ) {
			$out .= '<td><textarea rows="2" name="' . esc_attr( $n ) . '">' . esc_textarea( (string) $v ) . '</textarea></td>';
			continue;
		}
		$type = isset( $col[1] ) && 'url' === $col[1] ? 'url' : 'text';
		$out .= '<td><input type="' . $type . '" name="' . esc_attr( $n ) . '" value="' . esc_attr( hub_admin_plain( $v ) ) . '"></td>';
	}
	return $out . '<td class="hub-list__x"><button type="button" class="button-link hub-list__remove" aria-label="Remove row">&times;</button></td></tr>';
}

/** Stored text for an input box: entities (&rsquo;, &amp;) shown as the characters they stand for. */
function hub_admin_plain( $v ) {
	return is_scalar( $v ) ? html_entity_decode( (string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
}

/** Clean one sent value by its field type. */
function hub_admin_clean( array $f, $raw ) {
	switch ( $f['type'] ) {
		case 'textarea':
			return sanitize_textarea_field( wp_unslash( (string) $raw ) );
		case 'html':
			return hub_admin_inline_html( wp_unslash( (string) $raw ) );
		case 'url':
			return esc_url_raw( trim( wp_unslash( (string) $raw ) ) );
		case 'email':
			return sanitize_email( wp_unslash( (string) $raw ) );
		case 'number':
			$v = trim( wp_unslash( (string) $raw ) );
			return is_numeric( $v ) ? $v : '';
		case 'select':
			$v = sanitize_text_field( wp_unslash( (string) $raw ) );
			return isset( $f['choices'][ $v ] ) || ! empty( $f['other'] ) ? $v : '';
		case 'date':
			$v = trim( wp_unslash( (string) $raw ) );
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
		case 'time':
			$v = trim( wp_unslash( (string) $raw ) );
			return preg_match( '/^\d{2}:\d{2}/', $v ) ? substr( $v, 0, 5 ) : '';
		case 'toggle':
			return ! empty( $raw ) ? '1' : '';
		case 'image':
			return absint( $raw ) ? (string) absint( $raw ) : '';
		case 'gallery':
		case 'businesses':
			return array_values( array_unique( array_filter( array_map( 'absint', (array) $raw ) ) ) );
		case 'checks':
			$v = array_map( 'sanitize_key', array_map( 'wp_unslash', (array) $raw ) );
			return array_values( array_filter( $v, function ( $x ) use ( $f ) { return '' !== $x && isset( $f['choices'][ $x ] ); } ) );
		case 'hours':
			$out = array();
			for ( $i = 0; $i < 7; $i++ ) {
				$out[] = sanitize_text_field( wp_unslash( (string) ( isset( $raw[ $i ] ) ? $raw[ $i ] : '' ) ) );
			}
			return array_filter( $out, 'strlen' ) ? $out : array();
		case 'list':
			$rows = array();
			foreach ( (array) $raw as $row ) {
				if ( ! empty( $f['flat'] ) ) {
					$v = sanitize_text_field( wp_unslash( (string) $row ) );
					if ( '' !== $v ) {
						$rows[] = $v;
					}
					continue;
				}
				$clean = array();
				foreach ( $f['cols'] as $k => $col ) {
					$v    = isset( $row[ $k ] ) ? wp_unslash( (string) $row[ $k ] ) : '';
					$kind = isset( $col[1] ) ? $col[1] : 'text';
					$clean[ $k ] = 'url' === $kind ? esc_url_raw( trim( $v ) ) : ( 'check' === $kind ? ( '' !== $v ? '1' : '' ) : ( 'html' === $kind ? hub_admin_inline_html( $v ) : sanitize_text_field( $v ) ) );
				}
				$texts = array_diff_key( $clean, array_filter( $f['cols'], function ( $c ) { return isset( $c[1] ) && 'check' === $c[1]; } ) );
				if ( implode( '', $texts ) !== '' ) {
					$rows[] = $clean;
				}
			}
			return $rows;
		default:
			return sanitize_text_field( wp_unslash( (string) $raw ) );
	}
}

/** Clean every field of a definition list from $sent (e.g. $_POST['hub']). Notes are skipped. */
function hub_admin_clean_all( array $fields, array $sent ) {
	$out = array();
	foreach ( $fields as $f ) {
		if ( hub_admin_is_display( $f ) ) {
			continue;
		}
		$out[ $f['key'] ] = hub_admin_clean( $f, isset( $sent[ $f['key'] ] ) ? $sent[ $f['key'] ] : '' );
	}
	return $out;
}

/** Load the field toolkit's script and styles (and the media library) on the screens that use it. */
function hub_admin_enqueue() {
	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style( 'hub-admin', $uri . '/admin/hub-admin.css', array(), (string) filemtime( $dir . '/admin/hub-admin.css' ) );
	wp_enqueue_script( 'hub-admin', $uri . '/admin/hub-admin.js', array( 'jquery', 'jquery-ui-sortable' ), (string) filemtime( $dir . '/admin/hub-admin.js' ), true );
	wp_localize_script( 'hub-admin', 'hubAdmin', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'hub_business_search' ),
	) );
}

/**
 * A box of tabbed fields: $tabs = [ slug => [ tab label, fields[] ] ], $values = [ key => value ]. Each field is named
 * {$prefix}[key]. Used by the Homepage and Page text boxes.
 */
function hub_admin_tabs( array $tabs, array $values, $prefix = 'hub' ) {
	echo '<div class="hub-box" data-hub-box><nav class="hub-tabs" role="tablist">';
	$first = true;
	foreach ( $tabs as $slug => $tab ) {
		echo '<button type="button" role="tab" class="hub-tabs__tab' . ( $first ? ' is-active' : '' ) . '" data-hub-tab="' . esc_attr( $slug ) . '">' . esc_html( $tab[0] ) . '</button>';
		$first = false;
	}
	echo '</nav>';
	$first = true;
	foreach ( $tabs as $slug => $tab ) {
		echo '<div class="hub-panel" data-hub-panel="' . esc_attr( $slug ) . '"' . ( $first ? '' : ' hidden' ) . '><div class="hub-grid">';
		foreach ( $tab[1] as $f ) {
			$v = hub_admin_is_display( $f ) || ! isset( $values[ $f['key'] ] ) ? '' : $values[ $f['key'] ];
			if ( 'list' === $f['type'] && empty( $f['flat'] ) && is_array( $v ) ) {   // rows (a flat list keeps its plain lines)
				$v = array_map( function ( $row ) { return is_array( $row ) ? $row : array(); }, $v );
			}
			hub_admin_field( $f, $v, $prefix . '[' . $f['key'] . ']' );
		}
		echo '</div></div>';
		$first = false;
	}
	echo '</div>';
}

/** Every field of a set of tabs, in one list (for hub_admin_clean_all). */
function hub_admin_tab_fields( array $tabs ) {
	$fields = array();
	foreach ( $tabs as $tab ) {
		$fields = array_merge( $fields, $tab[1] );
	}
	return $fields;
}

/** Text with a little HTML kept (links, bold, italics, line breaks); everything else removed. */
function hub_admin_inline_html( $v ) {
	return trim( wp_kses( (string) $v, array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ), 'strong' => array(), 'em' => array(), 'br' => array() ) ) );
}

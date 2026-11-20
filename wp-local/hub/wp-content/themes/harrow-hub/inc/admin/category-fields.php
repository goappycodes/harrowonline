<?php
/**
 * wp-admin: Icon and Intro on a business category (Businesses > Categories, adding one and editing one), saved
 * through the categories module (hub_category_fields / hub_category_save_fields). The icon shows on the homepage's
 * Popular categories cards; the intro under the category page's heading. WordPress's own Description box isn't
 * used by the Hub, so it is hidden here.
 */
defined( 'ABSPATH' ) || exit;

/** Icons a category can use: the theme's sprite without the interface ones (arrows, menu…). */
function hub_admin_category_icons() {
	$ui = array( 'search', 'mail', 'menu', 'close', 'arrow', 'chevron', 'chevron-left', 'chevron-down', 'check', 'verified', 'lock', 'user', 'plus', 'edit',
		'upload', 'filter', 'list', 'grid', 'settings', 'logout', 'info', 'alert', 'download', 'external', 'facebook', 'x', 'instagram', 'tiktok', 'linkedin', 'whatsapp' );
	$names = array_values( array_diff( hub_icon_names(), $ui ) );
	sort( $names );
	return $names;
}

/** The two fields: $row = true draws table rows (the edit screen), false plain blocks (the add form). */
function hub_admin_category_fields( array $values, $row ) {
	$icons  = hub_admin_category_icons();
	$select = '<select name="hub_cat[icon]" id="hub-cat-icon" data-hub-icon-select><option value="">— None (a shop icon shows) —</option>';
	foreach ( $icons as $name ) {
		$select .= '<option value="' . esc_attr( $name ) . '"' . selected( $values['icon'], $name, false ) . '>' . esc_html( $name ) . '</option>';
	}
	$select .= '</select> <span class="hub-cat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><use href="#i-' . esc_attr( '' !== $values['icon'] ? $values['icon'] : 'store' ) . '" data-hub-icon-preview/></svg></span>';
	$fields = array(
		array( 'hub-cat-icon', 'Icon', $select, 'Shown on the homepage’s Popular categories cards.' ),
		array( 'hub-cat-intro', 'Intro', '<textarea name="hub_cat[intro]" id="hub-cat-intro" rows="4">' . esc_textarea( $values['intro'] ) . '</textarea>',
			'Shown under the category page’s heading. Empty: an automatic line with the number of businesses, and the page stays out of search engines.' ),
	);
	foreach ( $fields as $f ) {
		if ( $row ) {
			echo '<tr class="form-field ' . esc_attr( $f[0] ) . '-wrap"><th scope="row"><label for="' . esc_attr( $f[0] ) . '">' . esc_html( $f[1] ) . '</label></th><td>' . $f[2] . '<p class="description">' . esc_html( $f[3] ) . '</p></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped above
		} else {
			echo '<div class="form-field ' . esc_attr( $f[0] ) . '-wrap"><label for="' . esc_attr( $f[0] ) . '">' . esc_html( $f[1] ) . '</label>' . $f[2] . '<p>' . esc_html( $f[3] ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
	wp_nonce_field( 'hub_category_fields', 'hub_category_nonce' );
}

add_action( 'hub_category_add_form_fields', function () {
	hub_admin_category_fields( array( 'icon' => '', 'intro' => '' ), false );
} );

add_action( 'hub_category_edit_form_fields', function ( WP_Term $term ) {
	hub_admin_category_fields( hub_category_fields( $term->term_id ), true );
} );

/** Save on adding and on editing. */
foreach ( array( 'created_hub_category', 'edited_hub_category' ) as $hub_hook ) {
	add_action( $hub_hook, function ( $term_id ) {
		if ( ! isset( $_POST['hub_category_nonce'], $_POST['hub_cat'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hub_category_nonce'] ), 'hub_category_fields' ) || ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}
		$sent = wp_unslash( (array) $_POST['hub_cat'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned below
		$icon = sanitize_key( isset( $sent['icon'] ) ? $sent['icon'] : '' );
		hub_category_save_fields( $term_id, array(
			'icon'  => in_array( $icon, hub_admin_category_icons(), true ) ? $icon : '',
			'intro' => sanitize_textarea_field( isset( $sent['intro'] ) ? $sent['intro'] : '' ),
		) );
	} );
}
unset( $hub_hook );

/** The list of categories: an Icon column (after inc/admin/list-screens.php has set the other columns). */
add_filter( 'manage_edit-hub_category_columns', function ( $columns ) {
	unset( $columns['description'] );
	$out = array();
	foreach ( $columns as $k => $v ) {
		$out[ $k ] = $v;
		if ( 'cb' === $k ) {
			$out['hub_icon'] = 'Icon';
		}
	}
	return $out;
}, 20 );
add_filter( 'manage_hub_category_custom_column', function ( $content, $column, $term_id ) {
	if ( 'hub_icon' !== $column ) {
		return $content;
	}
	$icon = hub_category_fields( $term_id )['icon'];
	return '<span class="hub-cat-icon" title="' . esc_attr( '' !== $icon ? $icon : 'none' ) . '"><svg viewBox="0 0 24 24"><use href="#i-' . esc_attr( '' !== $icon ? $icon : 'store' ) . '"/></svg></span>';
}, 10, 3 );

/** On the category screens: the icon drawings, a live preview, and no Description box. */
function hub_admin_is_category_screen() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return $screen && 'hub_category' === $screen->taxonomy && in_array( $screen->base, array( 'edit-tags', 'term' ), true );
}
add_action( 'admin_head', function () {
	if ( hub_admin_is_category_screen() ) {
		echo '<style>.term-description-wrap{display:none}.hub-cat-icon{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;margin-left:6px;vertical-align:middle;border-radius:8px;background:rgba(var(--wp-admin-theme-color--rgb,223,32,40),.1);color:var(--wp-admin-theme-color,#df2028)}.hub-cat-icon svg{width:20px;height:20px;fill:currentColor}.column-hub_icon{width:56px}td.column-hub_icon .hub-cat-icon{margin:0}</style>';
	}
} );
add_action( 'admin_footer', function () {
	if ( ! hub_admin_is_category_screen() ) {
		return;
	}
	$file = get_template_directory() . '/assets/img/icons.svg';
	if ( is_readable( $file ) ) {
		echo str_replace( '<svg ', '<svg aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden" ', preg_replace( '/<\?xml[^>]*\?>/', '', (string) file_get_contents( $file ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- the theme's own file
	}
	echo "<script>document.addEventListener('change',function(e){var s=e.target.closest('[data-hub-icon-select]');if(!s)return;var u=document.querySelector('[data-hub-icon-preview]');if(u)u.setAttribute('href','#i-'+(s.value||'store'));});</script>";
} );

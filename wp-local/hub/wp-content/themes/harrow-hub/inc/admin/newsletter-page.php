<?php
/**
 * wp-admin: Hub settings > Newsletter (native admin page, no plugin). Saves the option "hub_newsletter", which
 * inc/newsletter.php reads for the sign-up band above the footer on every page.
 */
defined( 'ABSPATH' ) || exit;

function hub_admin_newsletter_fields() {
	$d = hub_newsletter_defaults();
	return array(
		array( 'key' => 'show', 'label' => 'Show', 'type' => 'toggle', 'help' => 'Off hides the band on every page.' ),
		array( 'key' => 'title', 'label' => 'Title', 'type' => 'text', 'placeholder' => $d['title'] ),
		array( 'key' => 'text', 'label' => 'Text', 'type' => 'textarea', 'placeholder' => $d['text'] ),
		array( 'key' => 'editions', 'label' => 'Newsletters', 'type' => 'list', 'add' => 'Add newsletter',
			'help' => 'One tick box each. <strong>Value</strong> is sent with the form (daily, weekend, business); <strong>Ticked</strong> ones start ticked.',
			'cols' => array( 0 => array( 'Value' ), 1 => array( 'Name' ), 2 => array( 'Description' ), 3 => array( 'Ticked', 'check' ) ) ),
		array( 'key' => 'placeholder', 'label' => 'Email placeholder', 'type' => 'text', 'width' => 50, 'placeholder' => $d['placeholder'] ),
		array( 'key' => 'button', 'label' => 'Button text', 'type' => 'text', 'width' => 50, 'placeholder' => $d['button'] ),
		array( 'key' => 'action', 'label' => 'Form URL', 'type' => 'url', 'width' => 70, 'placeholder' => $d['action'] ),
		array( 'key' => 'source', 'label' => 'Source', 'type' => 'text', 'width' => 30, 'placeholder' => $d['source'], 'help' => 'Sent with each sign-up, to see where it came from.' ),
	);
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Hub settings', 'Hub settings', 'manage_options', 'hub-settings', 'hub_admin_newsletter_page', 'dashicons-admin-generic', 59 );
	add_submenu_page( 'hub-settings', 'Newsletter sign-up', 'Newsletter', 'manage_options', 'hub-settings', 'hub_admin_newsletter_page' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_hub-settings' === $hook ) {
		hub_admin_enqueue();
	}
} );

function hub_admin_newsletter_page() {
	$saved = get_option( 'hub_newsletter', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$s     = hub_newsletter_settings();
	// The form shows what is saved; the defaults show as placeholders, except the list and the switch
	$values = array(
		'show'        => $s['show'] ? '1' : '',
		'title'       => isset( $saved['title'] ) ? $saved['title'] : '',
		'text'        => isset( $saved['text'] ) ? $saved['text'] : '',
		'editions'    => array_map( function ( $e ) { return array( $e[0], $e[1], $e[2], $e[3] ? '1' : '' ); }, $s['editions'] ),
		'placeholder' => isset( $saved['placeholder'] ) ? $saved['placeholder'] : '',
		'button'      => isset( $saved['button'] ) ? $saved['button'] : '',
		'action'      => isset( $saved['action'] ) ? $saved['action'] : '',
		'source'      => isset( $saved['source'] ) ? $saved['source'] : '',
	);
	?>
	<div class="wrap">
		<h1>Newsletter sign-up</h1>
		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Saved. The band on every Hub page now uses these settings.</p></div>
		<?php endif; ?>
		<p>The dark sign-up band shown above the footer on every Hub page. Leave a box empty to use the wording shown in grey.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hub-box hub-box--page">
			<input type="hidden" name="action" value="hub_newsletter_save">
			<?php wp_nonce_field( 'hub_newsletter_save' ); ?>
			<div class="hub-grid">
				<?php foreach ( hub_admin_newsletter_fields() as $f ) {
					hub_admin_field( $f, $values[ $f['key'] ], 'hub_nl[' . $f['key'] . ']' );
				} ?>
			</div>
			<?php submit_button( 'Save' ); ?>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_hub_newsletter_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You are not allowed to change these settings.' );
	}
	check_admin_referer( 'hub_newsletter_save' );
	$sent  = isset( $_POST['hub_nl'] ) && is_array( $_POST['hub_nl'] ) ? $_POST['hub_nl'] : array(); // phpcs:ignore -- cleaned below
	$clean = hub_admin_clean_all( hub_admin_newsletter_fields(), $sent );
	$clean['show']     = '1' === $clean['show'];
	$clean['editions'] = array_values( array_filter( array_map( function ( $r ) {
		return array( sanitize_key( $r[0] ), $r[1], $r[2], '1' === $r[3] );
	}, $clean['editions'] ), function ( $e ) { return '' !== $e[0] && '' !== $e[1]; } ) );
	update_option( 'hub_newsletter', $clean, false );
	wp_safe_redirect( admin_url( 'admin.php?page=hub-settings&updated=1' ) );
	exit;
} );

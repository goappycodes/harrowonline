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
		$css = get_template_directory() . '/admin/hub-admin-settings.css';
		$js  = get_template_directory() . '/admin/hub-admin-settings.js';
		wp_enqueue_style( 'hub-admin-settings', get_template_directory_uri() . '/admin/hub-admin-settings.css', array(), (string) filemtime( $css ) );
		wp_enqueue_script( 'hub-admin-settings', get_template_directory_uri() . '/admin/hub-admin-settings.js', array(), (string) filemtime( $js ), true );
	}
} );

/** The page's sections: [ title, what it's for, field keys ]. */
const HUB_ADMIN_NEWSLETTER_GROUPS = array(
	array( 'The band', 'The heading and the line under it. Leave a box empty to use the wording shown in grey.', array( 'show', 'title', 'text' ) ),
	array( 'Newsletters to choose from', 'One tick box each on the form. Readers can pick more than one.', array( 'editions' ) ),
	array( 'Sign-up form', 'The email box, the button, and where sign-ups are sent.', array( 'placeholder', 'button', 'action', 'source' ) ),
);

function hub_admin_newsletter_page() {
	$saved = get_option( 'hub_newsletter', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$s     = hub_newsletter_settings();
	$d     = hub_newsletter_defaults();
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
	$fields = array();
	foreach ( hub_admin_newsletter_fields() as $f ) {
		$fields[ $f['key'] ] = $f;
	}
	$plain = function ( $v ) {
		return wp_strip_all_tags( html_entity_decode( (string) $v, ENT_QUOTES, 'UTF-8' ) );
	};
	?>
	<div class="wrap hub-settings">
		<header class="hub-settings__head">
			<h1>Hub settings</h1>
			<p class="hub-settings__lead">The newsletter sign-up band, shown above the footer on every Hub page.</p>
			<nav class="hub-settings__tabs" aria-label="Hub settings">
				<span class="hub-settings__tab is-active" aria-current="page"><span class="dashicons dashicons-email-alt" aria-hidden="true"></span>Newsletter sign-up</span>
			</nav>
		</header>
		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><strong>Saved.</strong> Every Hub page now shows the band like this.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hub-settings__layout" id="hub-nl-form">
			<input type="hidden" name="action" value="hub_newsletter_save">
			<?php wp_nonce_field( 'hub_newsletter_save' ); ?>

			<div class="hub-settings__main">
				<?php foreach ( HUB_ADMIN_NEWSLETTER_GROUPS as $i => $g ) : ?>
					<section class="hub-settings__card">
						<div class="hub-settings__card-head">
							<span class="hub-settings__step"><?php echo (int) $i + 1; ?></span>
							<div>
								<h2><?php echo esc_html( $g[0] ); ?></h2>
								<p><?php echo esc_html( $g[1] ); ?></p>
							</div>
						</div>
						<div class="hub-box hub-grid">
							<?php foreach ( $g[2] as $key ) {
								hub_admin_field( $fields[ $key ], $values[ $key ], 'hub_nl[' . $key . ']' );
							} ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>

			<aside class="hub-settings__side">
				<div class="hub-settings__preview-wrap">
					<div class="hub-settings__preview-label"><span class="dashicons dashicons-visibility" aria-hidden="true"></span>Preview <span>updates as you type</span></div>
					<div class="hub-nl-preview" id="hub-nl-preview" data-off="<?php echo $s['show'] ? '' : '1'; ?>">
						<p class="hub-nl-preview__off">Hidden on every page. Turn on <strong>Show</strong> to put it back.</p>
						<div class="hub-nl-preview__band">
							<h3 data-preview="title" data-default="<?php echo esc_attr( $plain( $d['title'] ) ); ?>"><?php echo esc_html( $plain( $s['title'] ) ); ?></h3>
							<p data-preview="text" data-default="<?php echo esc_attr( $plain( $d['text'] ) ); ?>"><?php echo esc_html( $plain( $s['text'] ) ); ?></p>
							<ul class="hub-nl-preview__eds" data-preview="editions">
								<?php foreach ( $s['editions'] as $e ) : ?>
									<li><span class="hub-nl-preview__box<?php echo $e[3] ? ' is-on' : ''; ?>"></span><strong><?php echo esc_html( $plain( $e[1] ) ); ?></strong><?php echo '' !== (string) $e[2] ? ' <span>' . esc_html( $plain( $e[2] ) ) . '</span>' : ''; ?></li>
								<?php endforeach; ?>
							</ul>
							<div class="hub-nl-preview__form">
								<span class="hub-nl-preview__input" data-preview="placeholder" data-default="<?php echo esc_attr( $plain( $d['placeholder'] ) ); ?>"><?php echo esc_html( $plain( $s['placeholder'] ) ); ?></span>
								<span class="hub-nl-preview__btn" data-preview="button" data-default="<?php echo esc_attr( $plain( $d['button'] ) ); ?>"><?php echo esc_html( $plain( $s['button'] ) ); ?></span>
							</div>
						</div>
					</div>
					<p class="hub-settings__where"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span>Shows on every Hub page, above the footer. <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">Open the Hub &rarr;</a></p>
				</div>
			</aside>

			<div class="hub-settings__bar">
				<span class="hub-settings__dirty" id="hub-nl-dirty" hidden>You have changes that aren&rsquo;t saved yet.</span>
				<?php submit_button( 'Save changes', 'primary large', 'submit', false ); ?>
			</div>
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

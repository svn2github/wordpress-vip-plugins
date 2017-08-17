<?php

// Show PHP version compatibility warning
function jwplayer_admin_show_version_notice() {
	echo '<div class="error fade">';
	echo '<p>You are using PHP version <strong>' . esc_html( PHP_VERSION ) . '</strong>. ';
	echo 'You need at least version <strong>' . esc_html( JWPLAYER_MINIMUM_PHP_VERSION ) . '</strong> to use the JW Player plugin.<p>';
	echo '</div>';
}

// Show the login notice in the admin area if necessary
function jwplayer_admin_show_login_notice() {
	if ( isset( $_GET['page'] ) && 'jwplayer_login_page' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // Input var okay
		return;
	} else {
		$login_url = get_admin_url( null, 'admin.php?page=jwplayer_login_page' );
		echo '<div class="error fade"><p><strong>Don\'t forget to <a href="' . esc_url( $login_url ) . '">authorize</a> this plugin to access your JW Player account.</strong></p></div>';
	}
}

// Additions to the page head in the admin area
function jwplayer_admin_head() {

	$plugin_url = plugins_url( '', __FILE__ );
	$content_mask = jwplayer_get_content_mask();
	if ( $content_mask === JWPLAYER_CONTENT_MASK && is_ssl() ) {
		$content_mask = 'https://' . $content_mask;
	}
	$nr_videos = intval( get_option( 'jwplayer_nr_videos' ) );
	?>

	<script type="text/javascript">
		jwplayerwp.plugin_url = '<?php echo esc_url( $plugin_url ); ?>';
		jwplayerwp.content_mask = '<?php echo esc_url( $content_mask ); ?>';
		jwplayerwp.nr_videos = <?php echo esc_js( $nr_videos ); ?>;
		jwplayerwp.debug = <?php echo wp_json_encode( WP_DEBUG ); ?>;
	</script>
	<?php
}

// Add JQuery-UI Draggable to the included scripts, and other scripts needed for plugin
function jwplayer_admin_enqueue_scripts( $hook_suffix ) {

	// only enqueue on relevant admin pages
	$load_on_pages = array(
		'media-upload-popup',
		'post.php',
		'post-new.php',
	);
	if ( ! in_array( $hook_suffix, $load_on_pages, true ) ) {
		return;
	}

	$ajaxupload_url = plugins_url( '../static/js/upload.js', __FILE__ );
	$style_url = plugins_url( '../static/css/style.css', __FILE__ );
	$logic_url = plugins_url( '../static/js/logic.js', __FILE__ );

	wp_register_style( 'jwplayer_wp_admin_css', $style_url, false, JWPLAYER_PLUGIN_VERSION );
	wp_enqueue_style( 'jwplayer_wp_admin_css' );
	wp_enqueue_script( 'jquery-ui-draggable' );
	wp_enqueue_script( 'ajaxupload_script', $ajaxupload_url );
	wp_enqueue_script( 'logic_script', $logic_url );
}


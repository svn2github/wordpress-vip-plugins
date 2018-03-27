<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap" id="sailthru-admin">
	<div id="icon-sailthru" class="icon32"></div>
	<h2><?php esc_html_e( 'Sailthru for WordPress', 'sailthru-for-wordpress' ); ?></h2>

	<?php
	if ( isset( $_GET['page'] ) ) {
		$active_tab = sanitize_text_field( $_GET['page'] );
	} elseif ( 'concierge_configuration_page' === $active_tab ) {
		$active_tab = 'concierge_configuration_page';
	} elseif ( 'scout_configuration_page' === $active_tab ) {
		$active_tab = 'scout_configuration_page';
	} elseif ( 'settings_configuration_page' === $active_tab  ) {
		$active_tab = 'settings_configuration_page';
	} elseif ( 'customforms_configuration_page' === $active_tab ) {
		$active_tab = 'customforms_configuration_page';
	} else {
		$active_tab = 'customforms_configuration_page';
	} // End if/else.

	// Display errors from form submissions at the top.
	settings_errors();

	// Sailthru setup options.
	//$sailthru = get_option( 'sailthru_setup_options' );

	// Setup.
	$setup = get_option( 'sailthru_setup_options' );


	//Set defaults for setup to be false
	$show_concierge = false;
	$show_scout     = false;
	$list_signup    = false;


	if ( sailthru_verify_setup() ) {
		$list_signup = true;

		if ( isset( $setup['sailthru_js_type'] ) && 'horizon_js' === $setup['sailthru_js_type'] ) {
			$show_concierge = true;
			$show_scout     = true;
		}
	} else {
		$list_signup = false;
	}

?>

	<h2 class="nav-tab-wrapper">
			<a href="?page=sailthru_configuration_page" class="nav-tab <?php echo 'sailthru_configuration_page' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Configuration', 'sailthru-for-wordpress' ); ?></a>

			<?php if ( $show_concierge ) : ?>
			<a href="?page=concierge_configuration_page" class="nav-tab <?php echo 'concierge_configuration_page' === $active_tab  ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Concierge', 'sailthru-for-wordpress' ); ?></a>
			<?php endif; ?>
			<?php if ( $show_scout ) : ?>
			<a href="?page=scout_configuration_page" class="nav-tab <?php echo 'scout_configuration_page' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Scout', 'sailthru-for-wordpress' ); ?></a>
			<?php endif; ?>
			<?php if ( $list_signup ) : ?>
			<a href="?page=custom_fields_configuration_page" class="nav-tab <?php echo 'custom_fields_configuration_page' === $active_tab  ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'List Signup Options', 'sailthru-for-wordpress' ); ?></a>
			<?php endif; ?>
		</h2>

		<form method="post" action="options.php">

		<?php
		if ( 'sailthru_configuration_page' === $active_tab ) {
			require SAILTHRU_PLUGIN_PATH . 'views/settings.html.php';
		} elseif ( 'concierge_configuration_page' === $active_tab  ) {
			settings_fields( 'sailthru_concierge_options' );
			do_settings_sections( 'sailthru_concierge_options' );
		} elseif ( 'scout_configuration_page' === $active_tab ) {
			settings_fields( 'sailthru_scout_options' );
			do_settings_sections( 'sailthru_scout_options' );
		} elseif ( 'custom_fields_configuration_page' === $active_tab ) {
			settings_fields( 'sailthru_forms_options' );
			do_settings_sections( 'sailthru_forms_options' );
			echo '</div>'; // Ends the half column begun in delete_field().
			// Show welcome page.
		} else {
			require SAILTHRU_PLUGIN_PATH . 'views/settings.html.php';
		} // End if/else.

		echo '<div style="clear:both;">';
		submit_button();
		echo '</div>';
		echo '</form>'

		?>


	</div>

<?php
/**
 * Set up Media Explorer integration.
 *
 * @link  https://github.com/Automattic/media-explorer/
 */


/**
 * The following functionality is not required to run for regular user,
 * it should be available _only_ for WP Administration panel use.
*/
if ( is_admin() )
{

	/**
	 * Load the files required for Media Explorer integration (Shorthand)
	 *
	 *	(this function runs once on a page and should not run more than once,
	 *	it is thus done through a shorthand operation)
	 *
	 */
	add_action( 'mexp_init', 'anvato_load_dependencies', 10, 0 );
	function anvato_load_dependencies ()
	{
		require_once ANVATO_PATH . '/mexp/service.php';
		require_once ANVATO_PATH . '/mexp/template.php';
	}

	/**
	 * Register Anvato as a Media Explorer service (Shorthand).
	 *
	 *	(this function runs once on a page and should not run more than once,
	 *	it is thus done through a shorthand operation)
	 *
	 * @param array $services Associative array of Media Explorer services to load.
	 * @return array $services Services to load, including Anvato one.
	 */
	add_filter( 'mexp_services', 'anvato_mexp_services' );
	function anvato_mexp_services(array $services)
    {
		/*
			In case where this point is reached, but services and templates are not available
			but the MEXP endpoint is available...
			We can assume that the issue is with "mexp_init" action not executing automatically,
			as is the case on VIP.
			We can, however, tell the event to run and initiate the necessary functionality manually.
		*/
		if ( !class_exists('MEXP_Anvato_Service') && class_exists('MEXP_Service') )
		{
			anvato_load_dependencies();
		}

		// add Anvato to MEXP services, if applicable
		if ( class_exists('MEXP_Anvato_Service') && !array_key_exists(ANVATO_DOMAIN_SLUG, $services) )
		{
			$services[ANVATO_DOMAIN_SLUG] = new MEXP_Anvato_Service;
		}

		return $services;
	}
        
	/**
	 * Tell users with privileges about the Media Explorer plugin if it's missing.
	 */
	function anvato_add_mexp_notice()
	{
		if ( !class_exists('MEXP_Service') && current_user_can('install_plugins') )
		{
			add_action( 'admin_notices', 'anvato_mexp_nag' );
		}
	}

	add_action( 'load-settings_page_anvato', 'anvato_add_mexp_notice', 10, 1 );

	/**
	 * Display the notice about the Media Explorer plugin.
	 */
	function anvato_mexp_nag()
	{
		?>
		<div class="update-nag">
			<p><?php esc_html_e('<strong>Even easier embedding</strong>: 
						You can search for Anvato videos and add shortcodes directly from the Add Media screen 
						by installing the <a href="https://github.com/Automattic/media-explorer/">
						Media Explorer plugin</a>.', ANVATO_DOMAIN_SLUG);
			?></p>
		</div>
		<?php
	}
}

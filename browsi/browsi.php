<?php
/*
 Plugin Name: Browsi
 Plugin URI: http://getbrowsi.com
 Description: Improving your mobile browsing experience!
 Author: MySiteApp Ltd.
 Version: 0.1b
 Author URI: https://brow.si
 */

/** Options key **/
define( 'BROWSI_OPTIONS' , 'browsi_options' );
/** Base Browsi url **/
define( 'BROWSI_BASE_URL' , 'js.brow.si' );

/**
 * Returns the defined site_id of Browsi.
 * @param $notFound mixed   Not found marker
 * @return  mixed   The browsi site id, or the $notFound marker if it isn't defined
 */
function browsi_get_site_id( $notFound = false ) {
    $options = get_option( BROWSI_OPTIONS );
    return is_array( $options ) && array_key_exists( 'site_id' , $options ) && !is_null( $options['site_id'] ) ?
        $options['site_id'] : $notFound;
}

/**
 * 'wp_footer' hook - inject Browsi's javascript code to the page's footer.
 * @note    The javascript is loaded asynchronously and only after the rest of the page is loaded, so no need to worry
 *          about delaying the page load.
 * @note    Browsi is loaded only for supported mobile devices. The loader (br.js) returns the right javascript for the
 *          device based on the requester User-Agent, and "204 No Content" on non-supported devices (like Desktop).
 */
function browsi_footer() {
	$site_id = browsi_get_site_id();
?><script type="text/javascript">
    <?php if ($site_id): ?>
    window['_brSiteId'] = '<?php echo esc_js($site_id) ?>';
    <?php endif; ?>
    (function(d){
        var i='browsi-js'; if (d.getElementById(i)) {return;}
        var js=d.createElement("script"); js.id=i; js.async=true;
        js.src='//<?php echo BROWSI_BASE_URL ?>/br.js'; (d.head || d.getElementsByTagName('head')[0]).appendChild(js);
    })(document);
</script>
<?php
}


/********* Admin functions *********/
/**
 * Adds a text input for the Browsi site id editing
 */
function browsi_admin_options_siteid() {
    $site_id = browsi_get_site_id( '' );
?><input id="browsi_site_id" name="<?php echo esc_attr( BROWSI_OPTIONS ) ?>[site_id]" size="40" type="text" value="<?php echo esc_attr( $site_id ) ?>" /><br/>
    <em>(Leave empty if you don't have one)</em><?php
}

/**
 * Options page
 */
function browsi_admin_options_page() {
?><div class="wrap">
    <form action="options.php" method="post">
        <?php
            settings_fields( BROWSI_OPTIONS );
            do_settings_sections( __FILE__ );
            submit_button( __('Save Changes') );
        ?>
    </form>
</div><?php
}


/**
 * 'admin_menu' hook - adds Browsi under the 'Settings' panel
 */
function browsi_admin_menu() {
    add_options_page( __( 'Browsi', 'browsi' ) , __( 'Browsi', 'browsi' ) , 'manage_options' , 'browsi-settings' , 'browsi_admin_options_page' );
}

/**
 * 'admin_init' hook - adds Browsi configuration settings (currently just the site id)
 */
function browsi_admin_init() {
    register_setting( BROWSI_OPTIONS , BROWSI_OPTIONS , 'wp_kses_post' );
    add_settings_section( 'main_section' , esc_html__( 'Browsi Settings' , 'browsi' ) , '__return_false' , __FILE__ );
    add_settings_field( 'browsi_site_id' , esc_html__( 'Browsi Site Id', 'browsi' ), 'browsi_admin_options_siteid' , __FILE__ , 'main_section' );
}

/** Inject Browsi javascript on the page's footer */
add_action( 'wp_footer' , 'browsi_footer' , 1000 );

/** Add Browsi to the "Settings" menu */
add_action( 'admin_menu' , 'browsi_admin_menu' );
/** Init Browsi admin options */
add_action( 'admin_init' , 'browsi_admin_init' );
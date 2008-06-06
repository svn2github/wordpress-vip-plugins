<?php
/**
 * NAVT Word Press Plugin
 * Copyright (c) 2006-2008 Greg A. Bellucci/Atalaya Studio
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
 * LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package NAVT Word Press Plugin
 * @subpackage navt class
 * @author Greg A. Bellucci <greg[AT]gbellucci[DOT]us
 * @copyright Copyright &copy; 2006-2008 Greg A. Bellucci
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
require('navt_widget.php'); // includes the widget code @since 95.37


/**
 * @global $navt_map
 */
global $navt_map, $br;


/**
 * Navigation plugin class
 */
class NAVT {

    /**
    * plugin init
    *
    * @uses $navt_map
    */
    function init() {
        navt_write_debug(NAVT_INIT, sprintf("%s::%s - start\n", __CLASS__, __FUNCTION__));

        global $navt_map, $br;
        $navt_map = '';

        /**
         * Load the language domain
         */
        navt_loadtext_domain();

        if( class_exists('browser') ) {
            $br = new Browser();
            navt_write_debug(NAVT_INIT, sprintf("%s::%s Browser name: %s version: %s\n",
            __CLASS__, __FUNCTION__, $br->Name, $br->Version));
        }

        /**
         * Initialize the plugin for the backend
         */
        NAVT::install_check();
        NAVT::register_scripts();

        /**
         * Determine which page is being loaded
         */
        $uri = $_SERVER['REQUEST_URI'];

        if( stristr($uri, 'navt_restore.php') != false ) {
            add_action('wp_print_scripts', array('NAVT', 'restore_head'), 1);
        }

        if( stristr($uri, 'plugins.php') != false ) {
            add_action('admin_print_scripts', array('NAVT', 'plugin_page'), 1);
        }
        else {
            /**
             * Add header information to the backend header for
             * certain backend pages
             */
            if((stristr($uri,'page.php') !== false) || (stristr($uri, 'plugins.php') !== false) ||
            (stristr($uri, 'categories.php') !== false) || (stristr($uri, 'user-edit.php') !== false) ) {
                add_action('admin_print_scripts', array('NAVT', 'admin_head'), 1);
            }
        }

        /**
         * Set hooks for posts
         */
        add_action('save_post', array('NAVT_BE', 'save_post_wpcb'), 10, 2);
        add_action('delete_post', array('NAVT_BE', 'delete_post_wpcb'));

        /**
         * Set hooks for user profiles
         */
        add_action('profile_update', array('NAVT_BE', 'profile_update_wpcb'));
        add_action('user_register', array('NAVT_BE', 'user_register_wpcb'));
        add_action('delete_user', array('NAVT_BE', 'delete_user_wpcb'));

        /**
         * Set hooks for categories
         */
        add_action('created_category', array('NAVT_BE', 'created_category_wpcb')); // @since 95.30
        add_action('edited_category', array('NAVT_BE', 'edited_category_wpcb')); // @since 95.30
        add_action('delete_category', array('NAVT_BE', 'delete_category_wpcb'));

        /**
         * Admin menu
         */
        add_action('admin_menu', array('NAVT', 'admin_menu_wpcb'), 10);

        /**
         * category exclusions
         */
        add_filter('list_terms_exclusions', array('NAVT_BE', 'list_terms_exclusions_wpcb'));

        /**
         * Content filer hook
         */
        add_filter('the_content', array('NAVT_FE','the_content_wpcb'));

        /**
         * Attach the plugin to the theme
         */
        add_action('wp_head', array('NAVT_FE', 'wp_head_wpcb'));

        // kick off a backup?
        if(isset($_REQUEST['navtbackuprequest'])) {
            NAVT::do_backup();
            die();
        }
        // kick off a restore?
        if(isset($_REQUEST['navtrestorerequest'])) {
            $path = pathinfo(NAVT::get_url());
            $new_request = $_SERVER['PHP_SELF'] . '?page=' . $path['basename'] . '/app/navt_restore.php';
            wp_redirect($new_request);
        }

    }// end function

    /**
     * Registered callback function - adds the plugin options to the Management submenu
     *
     * @see Word Press function add_action('admin_menu')
     * @since 95.40 - moved plugin admin from plugin page to the bottom of the list page
     * this is because WordPress MU can block plugin page access by users.
     *
     */
    function admin_menu_wpcb() {
        // Add this menu item to the "Manage" menu to manage menus
        if ( function_exists('add_management_page') ) {
            // requires admin privedges
            $page = add_management_page('Menu Management', __('NAVT Lists', 'navt_domain') ,
            8, __FILE__, array('NAVT', 'configure'));

            // admin print script callback for NAVT List page
            add_action('admin_print_scripts-'. $page, array('NAVT', 'navt_list_page'));
        }
    }// end function

    /**
     * Registered Word Press callback that displays plugins administration page
     */
    function configure() {
        // includes the option-form script
        include(NAVT_PLUGINPATH . '/app/navt-display.php');

    }// end function

    /**
     * Registered WordPress callback that adds css and script information to the admin page header.
	 */
    function navt_list_page() {
        wp_deregister_script('jquery');
        wp_deregister_script('prototype');
        wp_deregister_script('scriptaculous');

        wp_print_scripts(array('navt_admin')); ?>

<script type="text/javascript">
//<![CDATA[
var navtpath = '<?php navt_output_url();?>';
//]]>
</script>
<!-- NAVT v<?php echo SCRIPTVERS;?> -->
<link type="text/css" rel="stylesheet" href="<?php navt_output_url(); ?>/css/navt.css" media="screen"/>
<!--[if lt IE 7]><link rel="stylesheet" href="<?php navt_output_url();?>/css/navtIe6.css" type="text/css" media="screen" /><![endif]-->
<!--[if IE 7]><link rel="stylesheet" href="<?php navt_output_url();?>/css/navtIe7.css" type="text/css" media="screen" /><![endif]-->
    <?php
    }// end function

    /**
     * Registered callback for the plugins page
     */
    function plugin_page() {
        wp_print_scripts(array('navt_options_js')); ?>
	   <?php
    }

    /**
     * Registered callback for backend page/category and user screens
     */
    function admin_head() {
    }

    /**
     * Checks NAVT installation
     */
    function install_check() {
        global $navt_map;

        // get the timestamp on this file and the last one we saved
        navt_write_debug(NAVT_INIT, sprintf("%s::%s - start\n", __CLASS__, __FUNCTION__));
        $dir_timestamp = filemtime(dirname(__FILE__));

        if( defined('NAVT2') ) {
            $this_version = (NAVT_MAJVER * 1000000) + (NAVT_MINVER * 100000) + (NAVT_BUILD * 10000);
        }
        else {
            $this_version  = intval( MAJVER.MINVER.BUILD, 10);
        }
        $installed_ver = intval(NAVT::get_installed_version(), 10);
        $is_installed  = NAVT::get_option(INSTALLED);
        $is_installed = (( empty($is_installed) || false === $is_installed ) ? 0: $is_installed);

        navt_write_debug(NAVT_INIT, sprintf("%s::%s installed: %s, running version: %s\n",
        __CLASS__, __FUNCTION__, $installed_ver, $this_version));

        /**
         * Check for a version upgrade
         */
        if( $is_installed && ($installed_ver < $this_version) ) {
            // convert the configuration if necessary
            NAVT::data_conversion($installed_ver, $this_version);
            NAVT::update_option(LASTMODIFIED, $dir_timestamp, 'last updated');
            NAVT::update_option(VERSIONID, $this_version);
        }
        else {
            if( !$is_installed ) {
                // install for the first time
                navt_write_debug(NAVT_INIT, sprintf("%s::%s - installing plugin\n", __CLASS__, __FUNCTION__));
                $m = NAVT::build_assets();

                NAVT::add_option(SCHEME, '1', SCRIPTNAME.' next group color scheme');
                NAVT::add_option(VERSIONID, $this_version, SCRIPTNAME . ' installed version');
                NAVT::add_option(VERCHECK, 0, SCRIPTNAME . ' automatic version checking (Off by default)');
                NAVT::add_option(DEF_ADD_GROUP, ID_DEFAULT_GROUP, SCRIPTNAME.' default navigation group.');
                NAVT::add_option(ASSETS, $m, SCRIPTNAME.' Navigation assets');
                NAVT::add_option(LASTMODIFIED, $dir_timestamp, SCRIPTNAME.' version tracking.');
                NAVT::add_option(INSTALLED, 1, SCRIPTNAME . ' installation status');
                NAVT::add_option(ICONFIG, array(), SCRIPTNAME . ' navigation item configuration');
                NAVT::add_option(GCONFIG, array(), SCRIPTNAME . ' group options');
            }
        }
        $navt_map = '';
        navt_write_debug(NAVT_INIT, sprintf("%s::%s - end\n", __CLASS__, __FUNCTION__));
    }

    /**
     * Plugin release conversions
     * Convert from the user's current release to the new one if necessary
     *
     * @uses $navt_map
     *
     * @param string $installed_version
     * @param string $this_version
     */
    function data_conversion($installed_version, $this_version ='' ) {
        global $navt_map;

        navt_write_debug(NAVT_INIT, sprintf("%s::%s - start\n",__CLASS__, __FUNCTION__));

        $installed_version = (empty($installed_version) || isBlank($installed_version) ? 1 : intval($installed_version, 10));
        $this_version = intval($this_version, 10);

        /**
         * For versions prior to .95.42
         */
        if( ($installed_version < 9542) && ($this_version >= 9542) ) {

            // .95.30 adds ext field
            // .95.42 adds ex2 field
            navt_write_debug(NAVT_INIT, sprintf("%s::%s 9452+ conversion\n", __CLASS__, __FUNCTION__));
            $new_map = array();
            $old_map = NAVT::load_map();

            for( $idx = 0; $idx < count($old_map); $idx++ ) {
                $ttl = $old_map[$idx][TTL];
                $nme = $old_map[$idx][NME];
                $ttl = str_replace('"', "'", $ttl);     // convert double quotes to single
                $nme = (isBlank($nme)) ? $ttl: $nme;    // make sure this is not empty
                $nme = str_replace('"', "'", $nme);     // convert double quotes to single
                $grp = strtoupper($old_map[$idx][GRP]); // forces names to upper case

                $new_map = array( GRP => $grp, TYP => $old_map[$idx][TYP], IDN => $old_map[$idx][IDN], TTL => $ttl,
                NME => $nme, OPT => $old_map[$idx][OPT], EXT => $old_map[$idx][EXT], LVL => '0', EX2 => '');
            }

            // Update the map and the installed version id
            NAVT::update_option(SERIALIZED_MAP, $new_map);
            $navt_map = '';
        }

        /**
         * For versions prior to .96
         */
        if( ($installed_version < 96000) && ($this_version >= 96000) ) {
            navt_write_debug(NAVT_INIT, sprintf("%s::%s 96000+ conversion\n",__CLASS__, __FUNCTION__));
            $map = NAVT::load_map();
            $avatar = NAVT::get_url() . '/' . IMG_AVATAR;

            $assets = array();        // indexed by item type and idn
            $config = array();        // indexed by group and css selector id
            $group_options = array(); // indexed by group name

            $assets[TYPE_ELINK][ELINKIDN] = array(
            TYP => TYPE_ELINK, IDN => ELINKIDN,
            TTL => 'http://',
            NME => __('User defined URI', 'navt_domain'),
            OPT => '0', EXT => '', LVL => '0', EX2 => '');

            $assets[TYPE_SEP][SEPIDN] = array(
            TYP => TYPE_SEP, IDN => SEPIDN,
            TTL => __('List divider', 'navt_domain'),
            NME => __('List divider', 'navt_domain'),
            OPT => '0', EXT => '', LVL => '0', EX2 => '');

            $assets[TYPE_LINK][HOMEIDN] = array(
            TYP => TYPE_LINK, IDN => HOMEIDN,
            TTL => __('Home', 'navt_domain'),
            NME => __('Home', 'navt_domain'),
            OPT => '0', EXT => '', LVL => '0', EX2 => '');

            $assets[TYPE_LINK][LOGINIDN] = array(
            TYP => TYPE_LINK, IDN => LOGINIDN,
            TTL => __('Sign in', 'navt_domain'),
            NME => __('Sign in', 'navt_domain'),
            OPT => '0', EXT => '', LVL => '0', EX2 => '');

            // Create the 'asset' array -
            // contains each wp asset
            for( $idx = 0; $idx < count($map); $idx++ ) {

                if( $map[$idx][TYP] === TYPE_PAGE || $map[$idx][TYP] === TYPE_CAT || $map[$idx][TYP] === TYPE_AUTHOR ) {

                    $opt = '0';
                    $ext = '';

                    if( TYPE_AUTHOR === $map[$idx][TYP] ) {
                        $opt = (SHOW_AVATAR | USE_DEF_AVATAR);
                        $ext = $avatar;
                    }
                    elseif( TYPE_PAGE === $map[$idx][TYP] ) {
                        $is_draft = (( intval($map[$idx][OPT], 10) & ISDRAFTPAGE ) ? 1: 0);
                        if($is_draft) {
                            $opt = ISDRAFTPAGE;
                        }
                    }

                    // indexed by type and idn
                    $assets[ $map[$idx][TYP] ][ $map[$idx][IDN] ] = array(
                    TYP => $map[$idx][TYP], IDN => $map[$idx][IDN], TTL => $map[$idx][TTL],
                    NME => $map[$idx][NME], OPT => $opt, LVL => '0', EXT => $ext, EX2 => ''
                    );
                }
            }

            // Create the 'config' array -
            // contains each configured item
            $seq = 1000;
            for( $idx = 0; $idx < count($map); $idx++ ) {
                $grp = $map[$idx][GRP];

                if( $grp != ID_DEFAULT_GROUP ) {
                    $item = $map[$idx];
                    $id = NAVT::make_id($item, $seq++);
                    $grp = strtolower($grp);
                    $grp = substr($grp, 0, MAX_GROUP_NAME);
                    $opt = intval($item[OPT], 10);

                    if(!array_key_exists($grp, $group_options) ) {
                        // add group to the group options array
                        //navt_write_debug(NAVT_INIT, sprintf("%s::%s group options created for: %s\n",
                        //__CLASS__, __FUNCTION__, $grp));
                        $group_options[$grp] = NAVT::mk_group_config();
                    }

                    if($opt & HAS_DD_OPTION) {
                        if( !($group_options[$grp]['options'] & HAS_DD_OPTION) ) {
                            // move this option to the group
                            $group_options[$grp]['options'] |= HAS_DD_OPTION;
                        }
                        // remove this option from the item
                        $opt -= HAS_DD_OPTION;
                    }

                    if($opt & HAS_NOSTYLE) {
                        if( !($group_options[$grp]['options'] & HAS_NOSTYLE) ) {
                            // move this option to the group
                            $group_options[$grp]['options'] |= HAS_NOSTYLE;
                        }
                        // remove this option from the item
                        $opt -= HAS_NOSTYLE;
                    }

                    // indexed by group and id
                    $config[$grp][ $id ] = array(
                    GRP => $grp, TYP => $item[TYP], IDN => $item[IDN], TTL => $item[TTL],
                    NME => $item[NME], OPT => $opt, LVL => $item[LVL], EXT => $item[EXT],
                    EX2 => $item[EX2]);

                    //navt_write_debug(NAVT_INIT, sprintf("%s::%s group: %s id: %s\n",
                    //__CLASS__, __FUNCTION__, $grp, $id));
                    //navt_write_debug(NAVT_INIT, sprintf("%s::%s group options\n",
                    //__CLASS__, __FUNCTION__), $config[$grp][$id]);
                }
            }

            //navt_write_debug(NAVT_INIT, sprintf("%s::%s assets\n",__CLASS__, __FUNCTION__), $assets);
            //navt_write_debug(NAVT_INIT, sprintf("%s::%s group options\n",__CLASS__, __FUNCTION__), $group_options);
            //navt_write_debug(NAVT_INIT, sprintf("%s::%s config\n",__CLASS__, __FUNCTION__), $config);

            NAVT::update_option(ICONFIG, $config);
            NAVT::update_option(ASSETS, $assets);
            NAVT::update_option(GCONFIG, $group_options);
            //NAVT::delete_option(SERIALIZED_MAP);  // no going back now
            $navt_map = '';
        }
        //navt_write_debug(NAVT_INIT, sprintf("%s::%s - end\n",__CLASS__, __FUNCTION__));
        return;
    }

    /**
     * Create a default group configuration
     *
     * @return array
     */
    function mk_group_config() {
        return( array(
        'options' => USE_NAVT_DEFAULTS,
        'select_size' => 1,
        'css' => array('ulid' => '' , 'ul' => '', 'li' => '', 'licurrent' => '', 'liparent' => '', 'liparent_active' => ''),
        'selector' => array('xpath' => '', 'before' => '', 'after' => '', 'option' => 0),
        'display' => array(
        'show_on' => (SHOW_ON_HOME | SHOW_ON_ARCHIVES | SHOW_ON_SEARCH | SHOW_ON_ERROR | SHOW_ON_PAGES | SHOW_ON_POSTS),
        'posts' => array('on_selected' => 'show', 'ids' => array()),
        'pages' => array('on_selected' => 'show', 'ids' => array())
        ))
        );
    }

    /**
     * Returns the version last saved in the data options
     *
     * @return integer
     */
    function get_installed_version() {

        $ver = NAVT::get_option(VERSIONID);
        if( empty($ver) || $ver === false ) {
            $ver = 1;
        }
        else {
            $ver = str_replace('.', '', $ver);
        }
        return(intval($ver, 10));
    }

    /**
     * Uninstalls the plugin - removes the plugin options from the database
     */
    function uninstall() {
        navt_write_debug(NAVT_INIT, sprintf("%s::%s - start\n", __CLASS__, __FUNCTION__));
        NAVT::delete_option(SERIALIZED_MAP); /* depreciated */
        NAVT::delete_option(LASTMODIFIED);
        NAVT::delete_option(SCHEME);
        NAVT::delete_option(VERSIONID);
        NAVT::delete_option(VERCHECK);
        NAVT::delete_option(DEF_ADD_GROUP);
        NAVT::delete_option(INSTALLED);
        NAVT::delete_option(TIPS); /* depreciated */
        NAVT::delete_option(ASSETS);
        NAVT::delete_option(ICONFIG);
        NAVT::delete_option(GCONFIG);
        navt_write_debug(NAVT_INIT, sprintf("%s::%s - end\n", __CLASS__, __FUNCTION__));
    }// end function

    /**
     * register scripts used by this plugin
     */
    function register_scripts() {

        //navt_write_debug(NAVT_INIT, sprintf("%s::%s - start\n", __CLASS__, __FUNCTION__));
        $url = NAVT::get_url();
        $jquery_ui_lib = '/js/jquery-ui';
        $jquery_plugins = '/js/plugins';

        wp_register_script('jquery121',       $url . '/js/jquery.js', array(), '1.2.1');
        wp_register_script('json',            $url . '/js/json.js', array(), '2.0');
        wp_register_script('rc_js',           $url . '/js/rc.js', array('jquery121'), '1.92');
        wp_register_script('navt_options_js', $url . '/js/navtoptions.js', array('jquery121'));

        // plugins
        wp_register_script('jquery_dim_ui',   $url . $jquery_plugins . '/jquery.dimensions.js', array('jquery121'), '1.0');
        wp_register_script('jquery_modal',    $url . $jquery_plugins . '/jquery.modal.js',  array('jquery_dim_ui'), '1.1.1');

        // ui
        wp_register_script('jquery_mouse_ui', $url . $jquery_ui_lib  . '/ui.mouse.js',  array('jquery_modal'), '1.0');
        wp_register_script('jquery_drag_ui',  $url . $jquery_ui_lib  . '/ui.draggable.js', array('jquery_mouse_ui'), '1.0');
        wp_register_script('jquery_drop_ui',  $url . $jquery_ui_lib  . '/ui.droppable.js', array('jquery_drag_ui'), '1.0');
        wp_register_script('jquery_sort_ui',  $url . $jquery_ui_lib  . '/ui.sortable.js',  array('jquery_drop_ui'), '1.0');

        wp_register_script('navt_admin', $url . '/js/navtadmin.js.php', array('jquery_sort_ui', 'rc_js', 'json'), SCRIPTVERS);
        //navt_write_debug(NAVT_INIT, sprintf("%s::%s - end\n", __CLASS__, __FUNCTION__));
    }

    /**
     * Computes this plugin's url
     *
     * @return string URL
     */
    function get_url() {
        global $navt_root_dir;
        return $navt_root_dir;
    }// end function

    /**
     * Creates the run time configuration array from the ICONFIG array stored
     * in the database.
     *
     * @return array menu map
     */
    function load_map() {
        global $navt_map;

        $installed_version = NAVT::get_installed_version();
        navt_write_debug(NAVT_INIT, sprintf("%s::%s\n", __CLASS__, __FUNCTION__));

        if( !is_array($navt_map) || !count($navt_map) ) {

            if( $installed_version < 96000 ) {
                navt_write_debug(NAVT_INIT, sprintf("%s::%s reading serialized map\n", __CLASS__, __FUNCTION__));
                $navt_map = NAVT::get_option(SERIALIZED_MAP);
            }
            else {
                // get the configuration data and convert it
                $groups = NAVT::get_option(ICONFIG);
                if( is_array($groups) ) {
                    foreach( $groups as $group => $members) {
                        foreach($members as $member ) {
                            $navt_map[] = array( GRP => $group, TYP => $member[TYP], IDN => $member[IDN],
                            TTL => $member[TTL], NME => $member[NME], OPT => $member[OPT], EXT => $member[EXT],
                            LVL => $member[LVL], EX2 => $member[EX2]);
                        }
                    }
                }
                else {
                    navt_write_debug(NAVT_INIT, sprintf("%s::%s - no configuration\n", __CLASS__, __FUNCTION__));
                }
            }
        }

        //navt_write_debug(NAVT_INIT, sprintf("%s::%s version: %s\n", __CLASS__, __FUNCTION__, $installed_version), $navt_map);
        return($navt_map);
    }

    /**
     * Builds the assets configuration from the currently defined pages, categories and users
     *
     * @return array
     * @version .96
     */
    function build_assets( $page_order='post_title', $cat_order='name', $user_order='user_nicename') {
        global $wpdb;
        $assets = array();

        $page_order  = ($page_order == 'default' ? 'menu_order' : $page_order);
        $cat_order   = ($cat_order == 'default'  ? 'menu_order' : $cat_order);
        $user_order  = ($user_order == 'default' ? 'user_nicename' : $user_order);

        //navt_write_debug(NAVT_INIT, sprintf("%s::%s - start\n", __CLASS__, __FUNCTION__));

        /**
         * Add default assets
         * @since .96.00
         */
        $assets[TYPE_ELINK][ELINKIDN] =  array(
        TYP => TYPE_ELINK, IDN => ELINKIDN,
        TTL => 'http://',
        NME => __('User defined URI', 'navt_domain'),
        OPT => '0', EXT => '', LVL => '0', EX2 => '',
        'asset_ttl' => __('User defined URI', 'navt_domain'));

        $assets[TYPE_SEP][SEPIDN] = array(
        TYP => TYPE_SEP, IDN => SEPIDN,
        TTL => __('List divider', 'navt_domain'),
        NME => __('List divider', 'navt_domain'),
        OPT => '0', EXT => '', LVL => '0', EX2 => '',
        'asset_ttl' => __('List divider', 'navt_domain'));

        $assets[TYPE_LINK][HOMEIDN] = array(
        TYP => TYPE_LINK, IDN => HOMEIDN,
        TTL => __('Home', 'navt_domain'),
        NME => __('Home', 'navt_domain'),
        OPT => '0', EXT => '', LVL => '0', EX2 => '',
        'asset_ttl' => __('Home', 'navt_domain'));

        $assets[TYPE_LINK][LOGINIDN] = array(
        TYP => TYPE_LINK, IDN => LOGINIDN,
        TTL => __('Sign in', 'navt_domain'),
        NME => __('Sign in', 'navt_domain'),
        OPT => '0', EXT => '', LVL => '0', EX2 => '',
        'asset_ttl' => __('Sign in', 'navt_domain'));

        /** Create the controls for each author
         */
        $users = get_users_of_blog();

        foreach($users as $user) {
            $u = get_userdata($user->ID);
            navt_write_debug(NAVT_INIT, sprintf("%s::%s user\n", __CLASS__, __FUNCTION__), $u);

            $avatar = NAVT::get_url() . '/' . IMG_AVATAR;
            $opt = (SHOW_AVATAR | USE_DEF_AVATAR);
            $nme = ( isBlank($u->display_name) ? $u->user_login : $u->display_name);
            //navt_write_debug(NAVT_INIT, sprintf("%s::%s USER\n", __CLASS__, __FUNCTION__), $u);

            // Set options
            $assets[TYPE_AUTHOR][$user->ID] = array(
            TYP => TYPE_AUTHOR, IDN => $user->ID, TTL => $u->user_login, NME => $nme,
            OPT => $opt, EXT => $avatar, LVL => '0', EX2 => '', 'asset_ttl' => $nme);
        }

        /**
         * Create the controls for each static page
         * page ordered by menu order showing wp hierarchy
         */
        $order = array();
        $pages = get_pages("sort_column=$page_order&hierarchical=false");
        $hier = get_page_hierarchy($pages);

        foreach( $hier as $page_id => $page_title ) {
            $p = get_page($page_id);
            $level = count($p->ancestors);
            $order[] = array('level' => $level, 'page' => $p);
        }

        foreach($order as $key => $page_data) {
            $page = $page_data['page'];
            $level = $page_data['level'];
            $id = (int) $page->ID;
            $ttl = $nme = $asset_ttl = (($page->post_title == '') ? __('no title', 'navt_domain') : $page->post_title);
            if($level > 0) { for($x = 0; $x < $level; $x++) { $asset_ttl = '&#8212;' . $asset_ttl; }}
            $opt = (($page->post_status == 'draft') ? ISDRAFTPAGE: 0);
            $assets[TYPE_PAGE][$id] = array(TYP => TYPE_PAGE, IDN => $id, TTL => $ttl,
            NME => $nme, OPT => $opt, EXT => '', LVL => 0, EX2 => '', 'asset_ttl' => $asset_ttl);
        }

        /**
         * Create the controls for each category
         * Ordered by category order showing wp hierarchy
         */
        $order = $t = array();
        $level = 0;
        $cats = (array) get_categories("type=category&orderby=$cat_order&order=ASC&hierarchical=1");

        // determine the category level
        foreach($cats as $cat) {
           if( $cat->parent == 0 ) {
               $t[$cat->cat_ID] = array('level' => 0, 'cat' => $cat);
           }
           else {
               $plevel = $t[$cat->parent]['level'] + 1;
               $t[$cat->cat_ID] = array('level' => $plevel, 'cat' => $cat);
           }
        }

        foreach( $t as $cat_id => $cat_data ) {
            $cat = $cat_data['cat'];
            $level = $cat_data['level'];
            $id = (int) $cat->cat_ID;
            $ttl = $nme = $asset_ttl = (($cat->name == '') ? __('no title', 'navt_domain') : $cat->name);
            if($level > 0) { for($x = 0; $x < $level; $x++) { $asset_ttl = '&#8212;' . $asset_ttl; }}
            $assets[TYPE_CAT][$id] = array(TYP => TYPE_CAT, IDN => $id, TTL => $ttl,
            NME => $nme, OPT => $opt, EXT => '', LVL => 0, EX2 => '', 'asset_ttl' => $asset_ttl);
            //navt_write_debug(NAVT_INIT, sprintf("%s::%s asset\n", __CLASS__, __FUNCTION__), $assets[TYPE_CAT][$id]);
        }

        //navt_write_debug(NAVT_INIT, sprintf("%s::%s assets\n", __CLASS__, __FUNCTION__), $assets);
        //navt_write_debug(NAVT_INIT, sprintf("%s::%s - end\n", __CLASS__, __FUNCTION__));
        return($assets);

    }// end function

    /**
     * Returns the value of the option identified by 'key'
     * Calls the Word Press function by the same name
     *
     * @param string_type $key
     * @return mixed value of key
     */
    function get_option($key) {
        return(get_option($key));
    }

    /**
     * Adds the key/Value and description to the database
     * Calls the Word Press function by the same name
     *
     * @param string $key
     * @param mixed $value
     * @param string $description of option
     */
    function add_option($key, $value, $description=NULL) {
        add_option($key, $value, $description, 'no');
    }

    /**
     * Updates the value of 'key' in the database
     * Calls the Word Press function by the same name
     *
     * @param string $key
     * @param mixed $value of key
     */
    function update_option($key, $value) {
        global $navt_map;

        if( $key == SERIALIZED_MAP ) {
            $navt_map = $value;
        }
        update_option($key, $value);
    }

    /**
     * Deletes the option identified by 'key' from the database
     * Calls the Word Press function by the same name
     *
     * @param string $key
     */
    function delete_option($key) {
        delete_option($key);
    }

    /**
     * Truncates text to $n length and adds an elipse
     *
     * @param string $text
     * @param integer $n
     */
    function truncate($text, $n) {
        $name = substr($text, 0, $n-3);
        $text = (($name != $text) ? $name .= '...' : $text);

        //navt_write_debug(NAVT_GEN, sprintf("%s::%s truncated to: %s\n", __CLASS__, __FUNCTION__, $text));
        return $text;
    }

    /**
     * Builds a HTML select containing the available avatars
     */
    function build_avatar_list($options_only=1, $set_to_disabled=0, $id='avatars', $picked='', $in=0) {

        $html = '';
        $url = NAVT::get_url();
        $path =  NAVT_PLUGINPATH . AVATAR_IMAGES . '/';
        $files = NAVT::files_scan($path, array('png','gif','jpg','jpeg','bmp'), 1, false);
        $state = $set_to_disabled ? "disabled='disabled'":'';

        if( count($files) > 0 ) {
            if(!$options_only) {
                $html = sprintf("%s<select id='%s' class='%s' %s>", _indentt($in), $id, CLS_AV_SELECT, $state);
            }
            foreach($files as $k => $filename ) {
                $pi = pathinfo($filename);
                $name = $pi['basename'];
                $file_url = $url . AVATAR_IMAGES . '/' . $name;
                $select = ($picked == $file_url) ? "selected='selected'":'';
                $html .= sprintf("%s<option value='%s' %s>%s</option>", _indentt($in+1), $file_url, $select, $name);
            }
            if( !$options_only ) {
                $html .= sprintf('%s</select>', _indentt($in));
            }
        }
        return($html);
    }

    /**
     * Scan for files to be included in th
     *
     * @param string $path  - where to begin the search
     * @param string $ext - type of file to search for, false == everything
     * @param integer $depth - depth of search
     * @param boolean $relative - true = scan is relative
     * @return array $files - array of files
     */
    function files_scan($path, $ext = false, $depth = 1, $relative = true) {
        $files = array();

        // Scan for all matching files
        NAVT::_files_scan($path, '', $ext, $depth, $relative, $files);
        return $files;
    }

    /**
     * Returns an array of filenames scanned in a directory
     *
     * @param string $base_path  - where to begin the search
     * @param string $path - directory path
     * @param string $ext - type of file to search for
     * @param integer $depth - depth of search
     * @param boolean $relative - true = scan is relative
     * @param  array $files - array of files
     */
    function _files_scan($base_path, $path, $ext, $depth, $relative, &$files) {
        if (!empty($ext)) {
            if (!is_array($ext)) {
                $ext = array($ext);
            }
            $ext_match = implode('|', $ext);
        }

        // Open the directory
        if(($dir = @dir($base_path . $path)) !== false) {
            // Get all the files
            while(($file = $dir->read()) !== false) {
                // Construct an absolute & relative file path
                $file_path = $path . $file;
                $file_full_path = $base_path . $file_path;

                // If this is a directory, and the depth of scan is greater than 1 then scan it
                if(is_dir($file_full_path) and $depth > 1 and !($file == '.' or $file == '..')) {
                    NAVT::_files_scan($base_path, $file_path . '/', $ext, $depth - 1, $relative, $files);

                    // If this is a matching file then add it to the list
                } elseif(is_file($file_full_path) and (empty($ext) or preg_match('/\.(' . $ext_match . ')$/i', $file))) {
                    $files[] = $relative ? $file_path : $file_full_path;
                }
            }

            // Close the directory
            $dir->close();
        }
    }

    /**
     * create a unique selector id
     *
     * @param array $item
     * @return string - selector id
     * @since version .96
     */
    function make_id($item, $seq='') {
        $id = 'a'.SEP.$item[TYP].SEP.$item[IDN];
        if( $seq != '' ) {
            $id .= '--'.$seq;
        }
        return($id);
    }

    /**
     * Create the html for a default asset
     *
     * @param integer $in
     * @param array $item
     * @return string
     * @since .96
     */
    function make_default_asset($in, $item, $is_alt) {

        $id  = NAVT::make_id($item);
        $alt = (($is_alt) ? 'alt' : '');
        $asset = wp_specialchars($item['asset_ttl']);
        $nme = wp_specialchars($item[NME]);
        $icon = NAVT::get_icon($item); // v1.0.5
        $html = _indentt($in) . "<option value='$id::$icon::$nme' class='asset $alt'>$asset</option>";
        return($html);
    }

    function get_icon($item) {
        $opts = intval($item[OPT], 10);
        $icon = '';
        if( $item[TYP] == TYPE_PAGE ) {
            $icon = (($opts & ISDRAFTPAGE) ? 'draftpage' : 'page');
        }
        if( $item[TYP] == TYPE_CAT )   { $icon = 'category'; }
        if( $item[TYP] == TYPE_SEP )   { $icon = 'divider'; }
        if( $item[TYP] == TYPE_AUTHOR) { $icon = 'user'; }
        if( $item[TYP] == TYPE_ELINK ) { $icon = 'elink'; }
        if( $item[TYP] == TYPE_LINK && $item[IDN] == HOMEIDN )  { $icon = 'home'; }
        if( $item[TYP] == TYPE_LINK && $item[IDN] == LOGINIDN ) { $icon = 'admin'; }
        return($icon);
    }

    /**
     * Returns a qualified group name
     *
     * @param string $n
     * @return string - cleaned string
     */
    function clean_group_name($n) {
        $n = trim($n);
        $n = attribute_escape(strip_tags($n));
        $n = sanitize_title_with_dashes($n);
        $n = stripslashes($n);
        $n = str_replace(':','-', $n); // can't allow ':' in the name v1.0.5
        return($n);
    }

    /**
     * Returns a clean item alias
     *
     * @param string $n - cleaned string
     */
    function clean_item_alias($n) {
        $n = trim($n);
        $n = attribute_escape(strip_tags($n));
        $n = stripslashes($n);
        return($n);
    }

    /**
     * Adds the NAVT uninstall and reset rows to the NAVT plugin on the plugins page
     *
     * How hard is this?
     * @param string - name of the plugin
     */
    function after_plugin_row_wpcb($plugin_name) {

        $is_navt = (( stristr($plugin_name, 'navt.php') === FALSE ) ? 0: 1);
        navt_write_debug(NAVT_GEN, sprintf("%s - %s, is_navt: %s\n", __FUNCTION__, $plugin_name, $is_navt));

        if( $is_navt ) {
            $uninstall_url = NAVT::get_url() . "/app/navt_utl.php?navt_action=uninstall&amp;plugin=$plugin_name&amp;_wpnonce=@once@";
            $reset_url = NAVT::get_url() . "/app/navt_utl.php?navt_action=reset";
            $t[0] = __(" - Sets all NAVT created database options to their default values. This will cause any previously created Navigation Groups to be removed.", 'navt_domain');
            $t[1] = __(" - Removes all NAVT created database options and automatically deactivates the plugin.", 'navt_domain');

            $html =
            "<div class='navtinfo' style='display:none;'>
                <div>
                    <fieldset style='border: 1px solid #ccc;padding:5px; margin-top: 10px;'>
                        <legend style='font-size: 1.1em; font-weight: bold; color:#666;'>Uninstall/Reset NAVT Plugin Options</legend>
                        <ul>
                            <li><a href='$reset_url' title='". __('Reset this plugin', 'navt_domain')."'>" .__('Reset', 'navt_domain') . "</a>" . $t[0] ."</li>
                            <li><a class='navt_uninstall' href='$uninstall_url' title='" . __('Uninstall this plugin', 'navt_domain') ."'>". __('Uninstall', 'navt_domain') . "</a>" . $t[1] ."</li>
                        </ul>
                    </fieldset>
                </div>
            </div>";
            echo $html;
        }
    }

    /**
     * Ruturns a HOME page navigation item
     */
    function make_home_link() {
        return(array(TYP => TYPE_LINK, IDN => HOMEIDN, TTL => HOMETXT,
        NME => HOMETXT, OPT => '0', EXT => '', LVL => '0', EX2 => ''));
    }

    /**
     * Backs up the current configuration and saves an XML file to the local drive
     */
    function do_backup() {

        // get the configuration data
        $groups = NAVT::get_option(ICONFIG);
        $group_options = NAVT::get_option(GCONFIG);
        $charset = get_option('blog_charset', true);
        $in = 1;
        $in0 = _indentt($in++);
        $in1 = _indentt($in++);
        $in2 = _indentt($in++);
        $in3 = _indentt($in++);
        $in4 = _indentt($in++);

        if( is_array($groups) && count($groups) > 0 ) {

            $filename = 'navt_plugin.' . date('Y-m-d') . '.xml';
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=$filename");
            header("Content-type: text/xml; charset='$charset'");
            printf("<?xml version='1.0' encoding='%s'?>\n", $charset);

            $html =
            "<!-- \n".
            $in1."generator='navt " . SCRIPTVERS ."\n".
            $in1."created='" . date('Y-m-d H:i') . "'\n".
            $in1."This file contains the backup information for the Navigation Tool For Word Press Plugin (NAVT)\n".
            $in1."This backup can be restored using the plugin restore facility\n".
            $in1."Copyright (c) 2006-2008 Greg Bellucci, The MIT License.\n".
            "-->\n".
            "\n<navt major_version='".MAJVER."' minor_version='".MINVER."'>\n";

            foreach( $groups as $group => $group_members) {

                $page_ids = $post_ids = array();
                $posts = $group_options[$group]['display']['posts']['ids'];
                $pages = $group_options[$group]['display']['pages']['ids'];
                foreach($posts as $key => $value ) { $post_ids[] = $key; }
                foreach($pages as $key => $value ) { $page_ids[] = $key; }
                $postids = ((count($post_ids) > 0) ? implode($post_ids, ',') : '');
                $pageids = ((count($page_ids) > 0) ? implode($page_ids, ',') : '');

                $html .=
                $in1."<group>\n" .
                $in2.NAVT::create_xml_entry('name', $group, true)."\n" .
                $in2.NAVT::create_xml_entry('options', $group_options[$group]['options'], false)."\n".
                $in2.NAVT::create_xml_entry('selectsize', $group_options[$group]['select_size'], false)."\n".
                $in2."<css>\n" .
                $in3.NAVT::create_xml_entry('ulid', $group_options[$group]['css']['ulid'], true). "\n" .
                $in3.NAVT::create_xml_entry('ulclass', $group_options[$group]['css']['ul'], true). "\n".
                $in3.NAVT::create_xml_entry('liclass', $group_options[$group]['css']['li'], true)."\n".
                $in3.NAVT::create_xml_entry('licurrent', $group_options[$group]['css']['licurrent'], true)."\n".
                $in3.NAVT::create_xml_entry('liparent', $group_options[$group]['css']['liparent'], true)."\n".
                $in3.NAVT::create_xml_entry('li_parent_active', $group_options[$group]['css']['liparent_active'], true)."\n".
                $in2."</css>\n" .
                $in2."<selector>\n" .
                $in3.NAVT::create_xml_entry('xpath', $group_options[$group]['selector']['xpath'], true)."\n".
                $in3.NAVT::create_xml_entry('before', $group_options[$group]['selector']['before'], true)."\n".
                $in3.NAVT::create_xml_entry('after', $group_options[$group]['selector']['after'], true)."\n".
                $in3.NAVT::create_xml_entry('seloption', $group_options[$group]['selector']['option'], false)."\n".
                $in2."</selector>\n" .
                $in2."<display>\n" .
                $in3.NAVT::create_xml_entry('show_on_options', $group_options[$group]['display']['show_on'], false) ."\n".
                $in3."<posts>\n" .
                $in4.NAVT::create_xml_entry('on_selected', $group_options[$group]['display']['posts']['on_selected'], false)."\n".
                $in4.NAVT::create_xml_entry('ids', $postids, false)."\n" .
                $in3."</posts>\n" .
                $in3."<pages>\n" .
                $in4.NAVT::create_xml_entry('on_selected', $group_options[$group]['display']['pages']['on_selected'], false)."\n".
                $in4.NAVT::create_xml_entry('ids', $pageids, false)."\n" .
                $in3."</pages>\n" .
                $in2."</display>\n";

                foreach($group_members as $itm ) {

                    $opt = intval($itm[OPT], 10);
                    $lvl = intval($itm[LVL], 10);
                    $itm[OPT] = (($opt > 0) ? $opt: '0');
                    $itm[LVL] = (($lvl > 0) ? $lvl: '0');
                    $str = $itm[TYP].$itm[IDN].$itm[TTL];
                    $v = md5($str);

                    if( !isBlank($itm[TYP]) && !isBlank($itm[IDN]) &&
                    !isBlank($itm[TTL]) && !isBlank($itm[NME]) ) {
                        $html .= $in2."<item>\n" .
                        $in3.NAVT::create_xml_entry('grp', $itm[GRP], true)."\n".
                        $in3.NAVT::create_xml_entry('typ', $itm[TYP], false)."\n".
                        $in3.NAVT::create_xml_entry('idn', $itm[IDN], false)."\n".
                        $in3.NAVT::create_xml_entry('ttl', $itm[TTL], true)."\n".
                        $in3.NAVT::create_xml_entry('nme', $itm[NME], true)."\n".
                        $in3.NAVT::create_xml_entry('opt', $itm[OPT], false)."\n".
                        $in3.NAVT::create_xml_entry('lvl', $itm[LVL], false)."\n".
                        $in3.NAVT::create_xml_entry('ext', $itm[EXT], true)."\n".
                        $in3.NAVT::create_xml_entry('ex2', $itm[EX2], true)."\n".
                        $in3.NAVT::create_xml_entry('ver', $v, false)."\n".
                        $in2."</item>\n";
                    }
                }
                $html .= $in1."</group>\n";
            }
            $html .= "</navt>\n";
            echo $html;
        }
    }

    /**
     * Create a n XML entry for the backup
     *
     * @param string $token
     * @param string $str
     * @param boolean $encap
     * @return string - XML tag
     */
    function create_xml_entry($token, $str, $encap) {

        $ret = '<' . $token;
        if( isBlank($str) ) {
            $ret .= '/>';
        }
        else {
            $ret .= '>' . (($encap == true) ? "<![CDATA[$str]]>" : $str);
            $ret .= '</' . $token . '>';
        }
        return($ret);
    }

    function restore_head() {?>
<link type="text/css" rel="stylesheet" href="<?php navt_output_url(); ?>/css/restore.css" media="screen"/>
    <?php }
}// end class NAVT

function navt_get_version() {
    global $wp_version;
    $v = split('.', $wp_version);
    $ar = array('major' => $v[0], 'minor' => $v[1], 'update' => $v[2]);
    return($ar);
}

/**
 * Test for an empty string
 *
 * @param string $string
 * @return boolean 1 = blank, 0 = not blank
 */
function isBlank($str) {
    $ret = 1; // assume it is blank
    if (!empty($str) && ($str != '')) {
        for ($i = 0; $i < strlen($str); $i++) {
            $c = substr($str, $i, 1);
            if ($c != " " ) {
                $ret = 0;
            }
        }
    }
    return($ret);
}

/**
 * Returns a string of spaces for HTML indentation
 *
 * @param integer $howmany_tabs- the number of indentation tabs to generate
 * @return string the indentation string
 */
function _indentt($howmany_tabs, $tabstring='    ') {
    $o = '';
    for($x = 0; $x < $howmany_tabs; $x++ )	$o .= $tabstring;
    return($o);
}

/**
 * Returns a comma delineated string containing the names
 * of all the menu groups.
 */
function navt_get_all_groups() {

    $gp = array();
    $gcfg = NAVT::get_option(GCONFIG); // group configurations
    foreach( $gcfg as $group => $group_data ) {
        $gp[] = $group;
    }
    return($gp);
}

/**
 * Load localization file
 *
 */
function navt_loadtext_domain() {

    $locale = 'en_US';

    if (defined('WPLANG')) {
        $locale = WPLANG;
    }

    if (empty($locale)) {
        $locale = 'en_US';
    }

    $mofile = NAVT_PLUGINPATH . "/app/lang/navt-$locale.mo";
    load_textdomain('navt_domain', $mofile);
    navt_write_debug(NAVT_INIT, sprintf("wplang %s, locale %s, mo: %s\n", WPLANG, $locale, $mofile));
}

/**
 * Avatar interface
 *
 * @param string $email_address
 */
function navt_get_avatar($email_address='') {

    if( function_exists('get_avatar') ) {
        $html = get_avatar($email_address);
    }
    else {
        $html = "<div class='avatar'><img src='".NAVT::get_url()."/images/default_avatar.jpg' alt='' /></div>\n";
    }

    return($html);
}

function navt_output_url() {
    echo(NAVT::get_url());
}

// Automatically install the navt_module if using K2SBM
add_action('k2_init', 'install_navt_sbm_module');
function install_navt_sbm_module() {
    if(function_exists('register_sidebar_module')) {
        require('navt_sbm_module.php');
    }
}

add_action( 'after_plugin_row', array('NAVT', 'after_plugin_row_wpcb'), 2, 1);
add_action('init', array('NAVT', 'init'));
?>

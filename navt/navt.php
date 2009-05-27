<?php
/**

Plugin Name: WordPress Navigation List Plugin NAVT
Plugin URI: http://atalayastudio.com
Description: Create, organize and manage your web site navigation by logically grouping your pages, categories and user's via a drag'n drop interface. Manage your navigation lists from the NAVT Lists menu tab in the Manage menu.
Version: 1.0.12
Author: Greg Bellucci
Author URI: http://gbellucci.us

**/

#$navt_plugindir = dirname(plugin_basename(__FILE__));
$navt_plugindir = 'navt';
$navt_root_dir = get_option('siteurl') . '/wp-content/themes/vip/plugins/'.$navt_plugindir;
@define('NAVT_PLUGINPATH', (DIRECTORY_SEPARATOR != '/') ? str_replace(DIRECTORY_SEPARATOR, '/', dirname(__FILE__)) : dirname(__FILE__));

require_once('includes/navtinc.php');
require_once('includes/browser.php');
require('app/navt.php');
require_once('app/navt_be.php');
require_once('app/navt_fe.php');

/**
 * API function call
 *
 * @param string $sNavGroupName - group name to be displayed
 * @param boolean $bEcho - (default is true=echo the HTML output)
 * @param string $sTitle - optional title
 * @param string $sBefore - opening tag (default=ul)
 * @param string $sAfter - closing tag (default=/ul)
 * @param string $sBeforeItem - open item tag (default=li)
 * @param string $sAfterItem - close item tag (default=/li)
 * @return string - HTML output (if bEcho is true)
 */
function navt_getlist($sNavGroupName, $bEcho=true, $sTitle='', $sBefore='ul', $sAfter='/ul', $sBeforeItem='li', $sAfterItem='/li') {

    $out = NAVT_FE::getlist($sNavGroupName, $sTitle, $sBefore, $sAfter, $sBeforeItem, $sAfterItem);
    if( $bEcho ) {
        echo $out;
        $out = null;
    }
    return($out);
}
?>

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
 * @author Greg A. Bellucci <greg[AT]gbellucci[DOT]us
 * @copyright Copyright &copy; 2006-2008 Greg A. Bellucci
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
require (dirname(__FILE__).'/../../../../wp-config.php');
$ref = get_option('wpurl').'/wp-admin/plugins.php';

// NAVT Uninstall from word press plugin panel
if( isset($_GET['navt_action']) ) {
    if( $_GET['navt_action'] == 'uninstall' ) {
        $plugin = $_GET['plugin'];
        $wpnonce = $_GET['_wpnonce'];
        if(basename($plugin) == 'navt.php' ) {
            if( substr('plugins.php', 0)) {
                NAVT::uninstall();
                wp_redirect("$ref?action=deactivate&plugin=$plugin&_wpnonce=$wpnonce");
            }
        }
    }
    elseif( $_GET['navt_action'] == 'reset' ) {
        NAVT::uninstall();
        wp_redirect($ref);
    }
}
?>
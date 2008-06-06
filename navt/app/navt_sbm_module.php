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
 * @subpackage navt module
 * @author Greg A. Bellucci <greg[AT]gbellucci[DOT]us
 * @copyright Copyright &copy; 2006-2008 Greg A. Bellucci
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

if( function_exists('navt_loadtext_domain') ) {
    navt_loadtext_domain();
}
define('NAVTMODULE', __('NAVT Module', 'navt_domain'));

function navt_module($args) {

    extract($args);

    if( function_exists('navt_getlist') ) {
        $listName = sbm_get_option('navt_list');
        $out = navt_getlist($listName, false);
        if( !empty($out) && '' != $out ) {
            echo($before_module . $before_title . $title . $after_title . $out . $after_module);
        }
    }
}

function navt_sidebar_module_control() {

    if(isset($_POST['navt_list'])) {
        sbm_update_option('navt_list', $_POST['navt_list']);
    }

    $no_groups = 1;
    $curItem = sbm_get_option('navt_list');

    if(function_exists('navt_get_all_groups')) {
        $groups = navt_get_all_groups();

        if( is_array($groups) ) {
            $ddlist = sprintf("<select id='navt_list' name='navt_list'>\n");
            $curItem = ($curItem == '' ? $groups[0]: $curItem);
            for( $i = 0; $i < count($groups); $i++ ) {
                $select = ($curItem == $groups[$i] ? " selected='selected'":'');
                $ddlist .= sprintf("<option value='%s' %s>%s</option>\n", $groups[$i], $select, $groups[$i]);
            }
            $no_groups = 0;
            $ddlist .= sprintf("</select>\n"); ?>
            <p><label for="navt_list"><?php _e('Navigation Group', 'navt_domain'); ?>:</label>
	        <?php echo $ddlist;?></p><?php
        }

        if( $no_groups ) {?><p style="color:red;">Navigation Lists have not been created.</p><?php }
    }
}

register_sidebar_module(NAVTMODULE, 'navt_module', 'sb_navt_list', array('navt_list' => '' ));
register_sidebar_module_control(NAVTMODULE, 'navt_sidebar_module_control');
?>
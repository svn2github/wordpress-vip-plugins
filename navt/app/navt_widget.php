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
 * @subpackage navt widget
 * @author Greg A. Bellucci <greg[AT]gbellucci[DOT]us
 * @copyright Copyright &copy; 2006-2008 Greg A. Bellucci
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

if( function_exists('navt_loadtext_domain') ) {
    navt_loadtext_domain();
}

define('NAVT_INSTANCES', 5); // this seems like enough...
define('NAVT_WIDGET_OPTIONS', 'widget_navt');
define('NAVT_GRPIDX', __('group'), 'navt_domain');
define('NAVT_TTLIDX', __('title'), 'navt_domain');

/**
 * NAVT Widget Init
 *
 */
function navt_widget_init() {

    // Check for the required API functions

    /* @since 95.35 */
    if ( function_exists('wp_register_sidebar_widget') && function_exists('wp_register_widget_control') ) {

        /* @since 95.35 */
        $dims = array('width' => 310, 'height' => 165);
        $class = array('classname' => 'widget_navt');
        $widget = __('NAVT Widget', 'navt_domain');

        for ($i = 1; $i <= NAVT_INSTANCES; $i++) {
            $name = sprintf("%s %d", $widget, $i);
            $id = "navt-$i";
            wp_register_sidebar_widget($id, $name, 'navt_widget', $class, $i);
            wp_register_widget_control($id, $name, 'navt_widget_control', $dims, $i);
        }

        $options = get_option(NAVT_WIDGET_OPTIONS);
        if(empty($options)) {
            for( $instance = 1; $instance <= NAVT_INSTANCES; $instance++ ) { $options[$instance][NAVT_TTLIDX] = $options[$instance][NAVT_GRPIDX] = '';}
            add_option(NAVT_WIDGET_OPTIONS, $options, 'NAVT Widget settings');
        }
    }
}

// Wait for the sidebar widget plugin to load
add_action('widgets_init', 'navt_widget_init');

// --------------
/**
 * Widget
 *
 * @param array $args - display classes/arrgs
 * @param integer $number - widget instance
 */
function navt_widget($args, $instance = 1) {
    extract($args);
    $options = get_option(NAVT_WIDGET_OPTIONS);

    if( function_exists('navt_getlist') ) {
        $widget_title = htmlspecialchars($options[$instance][NAVT_TTLIDX], ENT_QUOTES);
        $group  = $options[$instance][NAVT_GRPIDX];
        if ( !empty($widget_title) ) {$title = $before_title . $widget_title . $after_title;} else $title = '';
        $out = navt_getlist($group, false);
        if( !empty($out) && '' != $out ) {
            echo ($before_widget . $title . $out . $after_widget);
        }
    }
}

/**
 * Widget control
 *
 * @param integer $number - instance of the widget
 */
function navt_widget_control($instance = 1) {

    if( function_exists('navt_loadtext_domain') ) {
        navt_loadtext_domain();
    }
    $grps = array();
    $navt_submit = 'navt-submit-'.$instance;
    $navt_title  = 'navt-title-'.$instance;
    $navt_group  = 'navt-group-'.$instance;

    if( !function_exists('navt_getlist') ) { ?>
        <p style="color:red;"><?php _e('NAVT Plugin is not activated/installed', 'navt_domain');?>".</p><?php
    }
    else {

        if(function_exists('navt_get_all_groups')) {

            $groups = navt_get_all_groups();
            $newoptions = $options = get_option(NAVT_WIDGET_OPTIONS);
            if( !is_array($options) ) { $newoptions = $options = array(); }

            if(isset($_POST[$navt_submit])) {
                $newoptions[$instance][NAVT_GRPIDX] = $_POST[$navt_group];
                $newoptions[$instance][NAVT_TTLIDX] = $_POST[$navt_title];
            }
            if( $newoptions != $options ) {
                $options = $newoptions;
                update_option(NAVT_WIDGET_OPTIONS, $options);
            }

            $curItem = $options[$instance][NAVT_GRPIDX];
            $title = htmlspecialchars($options[$instance][NAVT_TTLIDX], ENT_QUOTES);

            if( count($groups) > 0 ) {
                $ddlist = sprintf("<select id='%s' name='%s'>\n", $navt_group, $navt_group);
                $curItem = (isBlank($curItem) ? $groups[0]: $curItem);
                for( $i = 0; $i < count($groups); $i++ ) {
                    $is_selected = ($curItem == $groups[$i] ? " selected='selected'":'');
                    $ddlist .= sprintf("<option value='%s' %s>%s</option>\n", $groups[$i], $is_selected, $groups[$i]);
                }
                $ddlist .= sprintf("</select>\n"); ?>
                <p><label for="<?php echo $navt_title; ?>" style="text-align:left;line-height:35px;"><?php _e('Title', 'navt_domain'); ?>: </label>
                <input style="width: 200px;" type="text" id="<?php echo $navt_title;?>" name="<?php echo $navt_title;?>" value="<?php echo $title;?>" /><br />
                <label for="<?php echo $navt_group;?>"><?php _e('Select Navigation Group:', 'navt_domain'); ?></label><?php echo $ddlist;?><br />
                <input type="hidden" id="<?php echo $navt_submit;?>" name="<?php echo $navt_submit;?>" value="1" /></p>
                <?php
            }
            else {?>
            <p style="color:red;">&bull; <?php _e('Navigation Lists have not been created.', 'navt_domain');?> &bull;</p>
            <?php
            }
        }
    }
}
?>
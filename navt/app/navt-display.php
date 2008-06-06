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
 * @subpackage navt admin display
 * @author Greg A. Bellucci greg[AT]gbellucci[DOT]us
 * @copyright Copyright &copy; 2006-2008 Greg A. Bellucci
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
global $gnavt_output, $gnavt_indent, $gnavt_config, $gnavt_groups;
global $gnavt_assets, $br, $ie, $browser;

/**
 * Class NAVT_DISPLAY
 * @since version .96
 *
 */
class NAVT_DISPLAY {

    /**
     * Iniailize the class
     * This routine creates all of the html for the groups and items
     * @since version .96
     *
     */
    function init() {
        navt_write_debug(NAVT_CFG, sprintf("%s::%s - start\n", __CLASS__, __FUNCTION__));
        global $gnavt_output, $gnavt_indent, $gnavt_config, $gnavt_groups, $br, $ie, $browser;
        $browser = "$br->Name:$br->Version";
        $ie = 'if IE';

        if( $br->Name == 'MSIE' && $br->Version == '6.0' ) {
            $ie .= ' 6';
        }
        elseif( $br->Name == 'MSIE' && $br->Version == '7.0' ) {
            $ie .= ' 7';
        }

        /**
         * Rebuild the asset information
         */
        $ar = NAVT::build_assets();
        NAVT::update_option(ASSETS, $ar);
        $ar = NAVT::get_option(ASSETS);

        $gnavt_indent = $seq = 0;
        $gnavt_output = '';
        $gnavt_config = array();
        $gnavt_groups = NAVT::get_option(GCONFIG);

        if(is_array($ar)) {
            // build the assets group
            $gnavt_output  = _indentt($gnavt_indent+1) . "<!--[$ie]> <div id='IEdiv'> <![endif]-->\n" .
            $gnavt_output .= _indentt($gnavt_indent+2) . "<div id='container'>\n";
            $gnavt_output .= NAVT_DISPLAY::make_asset_group($ar, $gnavt_indent+3);
        }

        $scheme = 0;
        $ar = NAVT::get_option(ICONFIG);
        if( is_array($ar) ) {
            // build each group
            foreach( $ar as $group_ar ) {
                $seq += 100;
                $scheme = ( $scheme + 1 > 6 ? 1: $scheme + 1 );
                $gnavt_output .= NAVT_DISPLAY::make_sortable_group($group_ar, $gnavt_indent+3, $scheme, $seq);
            }
        }

        $gnavt_output .= _indentt($gnavt_indent+2) . "</div><!--/#container-->\n";
        $gnavt_output .= _indentt($gnavt_indent+1) . "<!--[$ie]> </div> <![endif]-->\n\n";
        $gnavt_output .= NAVT_DISPLAY::make_sortable_group(null, $gnavt_indent+1, null, null, true);

        NAVT::update_option(ICONFIG, $gnavt_config);
        NAVT::update_option(GCONFIG, $gnavt_groups);
        NAVT::update_option(SCHEME, $scheme);

        //navt_write_debug(NAVT_CFG, sprintf("%s::%s gcfg:\n", __CLASS__, __FUNCTION__), $gnavt_groups);
        //navt_write_debug(NAVT_CFG, sprintf("%s::%s icfg:\n", __CLASS__, __FUNCTION__), $gnavt_config);
        //navt_write_debug(NAVT_CFG, sprintf("%s::%s - end\n", __CLASS__, __FUNCTION__));
    }

    /**
     * Create the default (assets) group
     *
     * @param string $name
     * @param array $ar
     * @param integer $in
     * @param integer $scheme
     * @return string
     * @since .96
     */
    function make_asset_group($ar, $in) {

        $in0 = _indentt($in); $in1 = _indentt($in+1); $in2 = _indentt($in+2);
        $in3 = _indentt($in+3); $in4 = _indentt($in+4); $in5 = _indentt($in+5);
        $name = 'ASSETS';
        $sel = array();

        if(is_array($ar) && count($ar) > 0) {

            $sel[TYPE_PAGE] = $in3 . "<div class='asset-type'><h4 class='page-assets'>".
            __("pages", 'navt_domain') . "</h4>\n" .
            $in4 . "<div class='sortby'>\n ".
            $in5 . "<input type='radio' id='page_sortby_title' name='page_sortby[]' checked='checked' value='title' />" . __('Sort By Title', 'navt_domain') . "\n" .
            $in5 . "<input type='radio' id='page_sortby_order' name='page_sortby[]' value='order' />" . __('Order', 'navt_domain') . "\n" .
            $in4 . "</div>\n" .
            $in4 . "<select id='asset-page' class='selects' size='5'>\n";

            $sel[TYPE_CAT] = $in3 . "<div class='asset-type'><h4 class='cat-assets'>".
            __("categories", 'navt_domain') . "</h4>\n" .
            $in4 . "<div class='sortby'>\n ".
            $in5 . "<input type='radio' id='cat_sortby_title' name='cat_sortby[]' checked='checked' value='title' />" . __('Name', 'navt_domain') . "\n" .
            $in5 . "<input type='radio' id='cat_sortby_order' name='cat_sortby[]' value='order' />" . __('Order', 'navt_domain') . "\n" .
            $in4 . "</div>\n" .
            $in4 . "<select id='asset-cat' class='selects' size='5'>\n";

            $sel[TYPE_AUTHOR] = $in3 . "<div class='asset-type'><h4 class='usr-assets'>".
            __("users", 'navt_domain') . "</h4>\n" .
            /*
            $in4 . "<div class='sortby'>\n ".
            $in5 . "<input type='radio' id='user_sortby_title' name='user_sortby[]' checked='checked' value='title' />" . __('Name', 'navt_domain') . "\n" .
            $in5 . "<input type='radio' id='user_sortby_order' name='user_sortby[]' value='order' />" . __('Order', 'navt_domain') . "\n" .
            $in4 . "</div>\n" .
            */
            $in4 . "<select id='asset-user' class='selects' size='5'>\n";

            $sel[TYPE_LINK] = $in3 . "<div class='asset-type'><h4 class='other-assets'>".
            __("other", 'navt_domain') . "</h4>\n" . $in4 . "<select id='asset-other' class='selects' size='5'>\n";

            $sc[TYPE_PAGE] = $sc[TYPE_CAT] = $sc[TYPE_AUTHOR] = $sc[TYPE_LINK] = 0;

            foreach( $ar as $type ) {
                foreach($type as $item) {
                    $typ = ( ( $item[TYP] != TYPE_ELINK  && $item[TYP] != TYPE_SEP ) ? $item[TYP] : TYPE_LINK );
                    $sc[$typ]++;
                    $sel[$typ] .= NAVT::make_default_asset($in+5, $item, ($sc[$typ]%2)) . "\n";
                }
            }

            $sel[TYPE_PAGE]   .= $in4 . "</select></div>\n";
            $sel[TYPE_CAT]    .= $in4 . "</select></div>\n";
            $sel[TYPE_AUTHOR] .= $in4 . "</select></div>\n";
            $sel[TYPE_LINK]   .= $in4 . "</select></div>\n";

            $html =
            $in0 . "<div id='$name' class='group-wrapper cs0 r4'>\n" .
            $in1 . "<h3 class='groupname r2'>".__('assets', 'navt_domain')."</h3>\n" .
            $in1 . "<div class='toolhelp'>\n" .
            $in2 . "<a href='#' id='help-assets' title='".__('asset panel help', 'navt_domain')."'><img src='@SRC@' alt=''/></a>\n" .
            $in1 . "</div>\n" .
            $in1 . "<div class='sub-wrapper'>\n" .
            $in2 . "<fieldset id='notassigned'> \n" .
            $in2 . "<legend>" . __("unassigned", 'navt_domain') . "</legend>\n" .
            $in2 . "<p>" . __("Move unassigned elements into a navigation group.", 'navt_domain') . "</p>\n" .
            $in2 . "<input id='notassigned-count' type='hidden' value='1' /> \n" .
            $in3 . "<ul id='notassigned-sort' class='sortgroup assets'>\n" .
            NAVT_DISPLAY::make_sortable_item($in+3, null, 0) .
            $in2 . "</ul>\n" .
            $in2 . "</fieldset>\n" .
            $in2 . "<p>" . __("To create a new item, click an element from one of the groups listed below.", 'navt_domain') . "</p>\n" .
            $in2 . "<div class='asset-lists'>\n" .
            $sel[TYPE_PAGE] .
            $sel[TYPE_CAT] .
            $sel[TYPE_AUTHOR] .
            $sel[TYPE_LINK] .
            $in2 . "</div><!--//asset-lists-->\n" .
            $in1 . "</div>\n" .
            $in0 . "</div>\n";
        }

        return($html);
    }

    /**
     * Create the html for a sortable group
     *
     * @param string $name
     * @param array $ar
     * @param integer $in
     * @param integer $scheme
     * @param integer $seq
     * @return string
     * @since .96
     */
    function make_sortable_group($group_ar, $in, $scheme, $seq, $create_template=false) {

        global $gnavt_groups;
        $in0 = _indentt($in); $in1 = _indentt($in+1); $in2 = _indentt($in+2); $in3 = _indentt($in+3);
        $hideThis = " style='display:none;'";
        $scheme_id = ((false === $create_template) ? 'csQ2719522Q' : '');
        $group_options = 0;
        $name = '';

        if((true === $create_template) || (is_array($group_ar) && count($group_ar) > 0)) {
            $html =
            $in0 . "<div id='Q2719520Q' class='group-wrapper r4 $scheme_id@LOCKED@@PRIVATE@'@HIDDEN@>\n" .
            $in1 . "<h3 class='r2'>QdisplaynameQ</h3><div class='group-spinner'></div>\n" .

            $in1 . "<a href='#' class='grpopts' title='".__('group options', 'navt_domain')."'".
            " onclick='return(@NS@.group_options(this));' ><img src='@SRC@' alt='' /></a>\n" .

            $in1 . "<a href='#' class='grprem' title='".__('remove group', 'navt_domain')."'".
            " onclick='return(@NS@.group_remove(this));' ><img src='@SRC@' alt='' /></a>\n" .

            $in1 . "<a href='#' class='grplock@LOCKED@' title='".__('click to lock/unlock group', 'navt_domain')."'".
            " onclick='return(@NS@.group_option(this, \"lock\"));' ><img src='@SRC@' alt='' /></a>\n" .

            $in1 . "<div id='Q2719521Q-drop' class='dropgroup'>\n" .
            $in2 . "<ul id='Q2719521Q:sort' class='sortgroup reorder'>\n"; // v1.0.5

            if( false === $create_template ) {
                foreach($group_ar as $member_id => $item) {
                    if( '' == $name ) {
                        // get the name of this group
                        $name = $item[GRP];
                        $upcase = strtoupper($name);
                        $locase = strtolower($name);

                        if( is_array($gnavt_groups) ) {
                            if( !array_key_exists($locase, $gnavt_groups ) ) {
                                $gnavt_groups[$locase] = NAVT::mk_group_config();
                            }
                            else {
                                $group_options = $gnavt_groups[$locase]['options'] & 0xffff;
                            }
                        }
                    }
                    // make this sortable
                    $locked = (($group_options & ISLOCKED) ? true: false);
                    $html .= NAVT_DISPLAY::make_sortable_item($in+3, $item, $seq++, $locked);
                }
            }

            // finish this off
            $html .=
            $in3 . "<li class='group-spacer navitem ui-enabled'>&nbsp;</li>\n" .
            $in2 . "</ul><!--/sortgroup-->\n" .
            $in1 . "</div><!--/drop-->\n" .
            $in0 . "</div><!--/group-wrapper-->\n\n";

            // If not creating the template
            if( false === $create_template ) {
                $display_name = NAVT::truncate($locase, MAX_GROUP_NAME);

                $html = str_replace('QdisplaynameQ', $display_name, $html);
                $html = str_replace('Q2719520Q', $upcase, $html);
                $html = str_replace('Q2719521Q', $locase, $html);
                $html = str_replace('Q2719522Q', $scheme, $html);
                $html = str_replace('@PRIVATE@', (($group_options & ISPRIVATE ) ? ' private ': ''), $html);
                $html = str_replace('@HIDDEN@', '', $html);
                $html = str_replace('@LOCKED@', (( $group_options & ISLOCKED ) ? ' locked ': ''), $html);
            }
            else {
                // creating the template
                $html = str_replace('@HIDDEN@', ' '.$hideThis, $html);
                $html = str_replace('@LOCKED@', '', $html);
                $html = str_replace('@PRIVATE@', '', $html);
            }
        }
        return($html);
    }

    /**
     * Create the html for a sortable item
     *
     * @param integer $in
     * @param array $item
     * @param integer $seq
     * @return string
     * @since .96
     */
    function make_sortable_item($in, $item, $seq, $locked=false) {

        global $gnavt_config;
        $show_class = $lockd = '';
        $isprivate = $isdraft = $isconnected = 0;

        if(is_array($item)) {
            $id  = NAVT::make_id($item, $seq);
            $opts = intval($item[OPT], 10);
            $show_class = (($opts & DO_NOT_DISPLAY) ? 'noshow' : '');
            $lockd = ($locked === false) ? 'ui-enabled':'ui-disabled';
            $isprivate = (($opts & ISPRIVATE) ? 1: 0);
            $isdraft = (($opts & ISDRAFTPAGE) ? 1: 0);
            $isconnected = (($opts & DISCONNECTED) ? 0: 1);
            $icon = NAVT::get_icon($item);

            if( TYPE_PAGE == $item[TYP] ) {
                if( $opts & ISDRAFTPAGE ) {
                    $isconnected = 0;
                }
                else {
                    $isconnected = (( $opts & DISCONNECTED ) ? 0: 1);
                }
            }

            if( TYPE_SEP == $item[TYP] ) {
                if( !($opts & (HRULE_OPTION | PLAIN_TEXT_OPTION)) ) {
                    $item[NME] = __('empty space', 'navt_domain');
                }
                else if( $opts & HRULE_OPTION ) {
                    $item[NME] = __('horizontal rule', 'navt_domain');
                }
            }
        }

        $anchor = ((is_array($item)) ? "<a class='alias-anchor' href='#' id='QIDQ-alias-anchor' title='QNMEQ'>QALIASQ</a>":"<p>QALIASQ</p>");
        $in0 = _indentt($in); $in1 = _indentt($in+1); $in2 = _indentt($in+2);
        $in3 = _indentt($in+3); $in4 = _indentt($in+4); $in5 = _indentt($in+5); $in6 = _indentt($in+6);

        $html =
        $in0 . "<li id='QIDQ' class='@CONNECT@ navitem $lockd $show_class level-QLEVELQ'@HIDDEN@>\n" .
        $in1 . "<div class='item-wrapper'>\n" .
        $in2 . "<div class='item-spinner'></div>\n" .
        $in2 . "<div class='asset-icon'><img alt='' src='@SRC@' class='icon @ICON@ @PRIVATE@ @DRAFT@' /></div>\n" .
        $in2 . "<div class='asset-name'>$anchor</div>\n".

        $in2 . "<input id='QIDQ-alias' type='hidden' value='QNMEQ' />\n" .
        $in2 . "<div class='asset-wrapper'>\n" .
        $in3 . "<div class='asset-hierarchy'>\n" .

        $in4 . "<div class='lc'><a class='dn' href='#' id='QIDQ-level-dn' title='" .
        __('move higher', 'navt_domain')."' onclick='return(@NS@.set_item_level(this));'>\n" .
        $in5 . "<img src='@SRC@' alt=''/></a>\n" .
        $in4 . "</div>\n" .
        $in4 . "<div class='lc'><a class='up' href='#' id='QIDQ-level-up' title='" .
        __('move lower', 'navt_domain')."' onclick='return(@NS@.set_item_level(this));'>\n" .
        $in5 . "<img src='@SRC@' alt=''/></a>\n" .
        $in4 . "</div>\n" .
        $in3 . "</div>\n" .
        $in3 . "<div class='asset-disc @CONNECT@'><a href='#' id='QIDQ-disc' title='" .
        __('click to connect/disconnect item from list', 'navt_domain')."' onclick='return(@NS@.disc_item(this));'>\n" .
        $in4 . "<img src='@SRC@' class='disc-button' alt=''/></a>\n" .
        $in3 . "</div>\n" .
        $in3 . "<div class='asset-remove'><a href='#' id='QIDQ-remove' title='" .
        __('click to remove item', 'navt_domain')."' onclick='return(@NS@.remove_item(this));'>\n" .
        $in4 . "<img src='@SRC@' class='remove-button' alt=''/></a>\n" .
        $in3 . "</div>\n" .
        $in2 . "</div>\n" .
        $in2 . "<div class='asset-options'><div class='options overlay' style='display:none;'/></div>\n" .
        $in1 . "</div>\n" .
        $in0 . "</li>\n";

        // local replacements
        $level = intval($item[LVL], 10);
        $level = (($level == '') ? 0 : $level);

        if( is_array($item) ) {
            $alias = NAVT::truncate($item[NME], (21 - $level));
            $qnmeq = attribute_escape($item[NME]);

            $html = str_replace('QIDQ', $id, $html);
            $html = str_replace('QNMEQ', wp_specialchars($qnmeq), $html);
            $html = str_replace('QALIASQ', wp_specialchars($alias), $html);
            $html = str_replace('QTYPEQ', $item[TYP], $html);
            $html = str_replace('QLEVELQ', $level, $html);
            $html = str_replace('@PRIVATE@', (($isprivate) ? 'private' : ''), $html);
            $html = str_replace('@DRAFT@', (($isdraft) ? 'draft' : ''), $html);
            $html = str_replace('@CONNECT@', (($isconnected) ? '' : 'disconnected'), $html);
            $html = str_replace('@ICON@', 'i-'.$icon, $html);
        }

        if( !is_array($item) || $show_class == 'noshow' ) {
            $html = str_replace('@HIDDEN@', ( !is_array($item) ? " style='display:none;'" : ''), $html);
            $html = str_replace('@DRAFT@', '', $html);
            $html = str_replace('@PRIVATE@', '', $html);
            $html = str_replace('@ICON@', '', $html);
            $html = str_replace('@CONNECT@', '', $html);
            $html = str_replace('QLEVELQ', '0', $html);
        }

        // add sortable item to the working configuration
        if(is_array($item)) {
            // part of the configuration
            $gnavt_config[ strtolower($item[GRP])][ $id ] = $item;
        }
        return($html);
    }

    /**
     * Returns all of the html created for the page
     *
     * @return string
     * @since version .96
     */
    function get_group_output() {
        global $gnavt_output;
        return($gnavt_output);
    }

}// end class



/** ---------------------------------
 * Create the page
 * ---------------------------------*/
$src = NAVT::get_url() . '/' . IMG_BLANK;
NAVT_DISPLAY::init();
$lang = ((defined('WPLANG')) ? WPLANG : 'en-US');
if(isBlank($lang) ) {$lang = 'en-US';}
$lang = str_replace('_', '-', $lang);

$html .= "\n

<div class='navt-wrap $lang'>\n" .
get_navt_topbar($gnavt_indent+1) .
"</div>

<div id='navt' class='navt-wrap'>\n" .
NAVT_DISPLAY::get_group_output() .
"</div>\n" .

navt_group_options_helper($gnavt_indent) .
get_navt_footer($gnavt_indent);

$html = str_replace('@SRC@', $src, $html);
$html = str_replace('@NS@', 'navt_ns', $html);
echo $html;


/**
 * creates the contents of the page sidebar
 *
 * @param integer $in
 * @return html string
 * @since .96
 */
function get_navt_topbar($in) {

    global $ie;
    $in0 = _indentt($in); $in1 = _indentt($in+1); $in2 = _indentt($in+2); $in3 = _indentt($in+3);

    $html =
    $in0 . "<!--[$ie]> <div class='IEtoolbar'> <![endif]-->\n".
    $in0 . "<h2 class='navt-header'>".__('WordPress Navigation Management', 'navt_domain')."</h2>\n" .
    $in0 . "<p>&bull; ". __('Navigation Tool for WordPress v', 'navt_domain') . SCRIPTVERS . " &bull;</p>\n" .
    $in0 . "<div id='navt-topbar'>\n" .

    // create new group toolbar
    $in1 . "<div class='toolbar r4 newgroup left'>\n" .
    $in2 . "<h3>".__('Navigation Group', 'navt_domain')."</h3>\n" .

    // help
    $in2 . "<div class='toolhelp'>\n" .
    $in3 . "<a href='#' id='help-grp' title='".__('group help', 'navt_domain').
    "'><img src='@SRC@' alt=''/></a>\n" .
    $in2 . "</div>\n" .

    // controls
    $in2 . "<p id='add-controls'>".__('group name', 'navt_domain')."<br />\n" .
    $in2 . "<input type='text' id='new-group-name' value=''/></p><div id='add-spinner'></div>\n" .
    $in2 . "<a href='#' class='bttn' id='grp-create' title='".
    __('create new group', 'navt_domain')."'>".__('create', 'navt_domain')."</a>\n" .
    $in2 . "<div class='errormsg' id='create-msg' style='display:none;'></div>\n" .
    $in1 . "</div>\n" .

    // backup-restore toolbar
    $in1 . "<div class='toolbar r4 backup-restore left'>\n" .
    $in2 . "<h3>".__('Backup-Restore', 'navt_domain')."</h3>\n" .

    // help
    $in2 . "<div class='toolhelp'>\n" .
    $in3 . "<a href='#' id='help-backup' title='".__('backup/restore help', 'navt_domain').
    "'><img src='@SRC@' alt=''/></a>\n" .
    $in2 . "</div>\n" .

    // controls
    $in2 . "<p id='br-controls'>".__('backup/restore navigation groups', 'navt_domain').".<br />\n" .
    $in2 . "<a href='#' class='bttn' id='do-restore' title='".__("restore", 'navt_domain')."'>".__('restore', 'navt_domain')."</a>\n" .
    $in2 . "<a href='#' class='bttn' id='do-backup' title='".__('backup', 'navt_domain')."'>".__('backup', 'navt_domain')."</a></p>\n" .
    $in1 . "</div>\n" .

    // updates
    $in1 . "<div class='toolbar r4 updates left'>\n" .
    $in2 . "<h3>".__('Change Log', 'navt_domain')."</h3>\n" .

    // controls
    $in2 . "<select id='list-updates' class='selects' size='4'><option value=''>&nbsp;</option></select>\n" .
    $in1 . "</div>\n" .

    $in0 . "</div>\n" .
    $in0 . "<!--[$ie]></div> <![endif]-->\n";

    return($html);
}

/**
 * Creates the group options helper
 *
 * @param integer $in
 */
function navt_group_options_helper($in) {
    global $ie;
    $in0 = _indentt($in);
    $html = "\n" . $in0 . "

<div id='window-mask'></div>
<div id='option-outer-wrapper'>
    <div id='w-h'>
        <div id='w-tc'></div>
        <div id='option-wrapper'>
            <div id='option-helper'>&nbsp;</div>
        </div>
    </div>
</div>

<div id='modalOverlay'></div>
<div id='dialog'>
    <div id='wintitle'>&nbsp;</div>
    <div id='target'>&nbsp;</div>
</div>";

    return($html);
}

/**
 * creates the contents of the page footer
 *
 * @param integer $in
 * @return html string
 * @since .96
 */
function get_navt_footer($in) {

    global $ie;

    $html =
    "\n\n" . _indentt($in) .
    "<!--[$ie]> <div class='IEfooter'> <![endif]-->\n" . _indentt($in) .
    "<br /><div id='navt-footer'>\n" ._indentt($in+1) .
    "<p>".__('copyright', 'navt_domain') . " &copy; 2007-2008 <a href='http://gbellucci.us'>g. a. bellucci, et ux</a> &bull;
    <a href='http://gbellucci.us/forums/'>".__('navt forum', 'navt_domain')."</a> &bull;
    <a href='http://atalayastudio.com'>".__('atalaya studio', 'navt_domain')."</a> &bull;
    <a rel='nofollow' href='http://en.wikipedia.org/wiki/MIT_License'>".__('the mit license', 'navt_domain')."</a><br /></p>
    <div class='badge'><img src='@SRC@' alt='NAVT Powered' /></div>\n" .
    _indentt($in) . "</div>\n".
    _indentt($in) . "<!--[$ie]></div> <![endif]-->\n\n";

    return($html);
}
?>

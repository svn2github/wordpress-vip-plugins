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

global $icfg;
global $gcfg;
global $navt_blank;
global $checkit;
global $selected;

$msg = __("unknown request", 'navt_domain');
$rsp = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'unknown', 'msg' => $msg));

/**
 * invoked from navtadmin.js for configuration changes
 * @since .96
 */
if( isset( $_POST['navtajx'] ) ) {

    // get the action to be taken and initialize the class
    $action = $_POST['navtajx']['action'];
    NAVTAJX::init();

    // all group related changes
    if( $action == 'add_group' ) {
        $rsp = NAVTAJX::add_new_group(
        $_POST['group']
        );
    }
    elseif( $action == 'remove_group' ) {
        $rsp = NAVTAJX::remove_group(
        $_POST['group']
        );
    }
    elseif( $action == 'ask_remove_group' ) {
        $rsp = NAVTAJX::ask_remove_group(
        $_POST['group']
        );
    }
    elseif( $action == 'reorder_groups') {
        $rsp = NAVTAJX::reorder_groups(
        $_POST['order']
        );
    }
    elseif( $action == 'get_group_options') {
        $rsp = NAVTAJX::get_group_options(
        $_POST['group']
        );
    }
    elseif( $action == 'toggle_group_option') {
        $rsp = NAVTAJX::toggle_group_option(
        $_POST['group'],
        $_POST['option']
        );
    }
    elseif( $action == 'set_group_option') {
        $rsp = NAVTAJX::set_group_option(
        $_POST['group'],
        $_POST['option'],
        $_POST['setting'],
        $_POST['type']
        );
    }
    elseif( $action == 'save_group_options') {
        $rsp = NAVTAJX::save_group_options(
        $_POST['group'],
        $_POST['group_options'],
        $_POST['display'],
        $_POST['page_list'],
        $_POST['post_list'],
        $_POST['savetype'],
        $_POST['activetab']
        );
    }
    elseif( $action == 'get_options_help' ) {
        $rsp = NAVTAJX::help('group_options',
        $_POST['activetab']);
    }
    elseif( $action == 'navt_help') {
        $rsp = NAVTAJX::help(
        $_POST['subject'],
        $_POST['ltIe7']);
    }

    // all item related changes
    elseif( $action == 'add_group_item' ) {
        $rsp = NAVTAJX::add_group_item(
        $_POST['id'],
        $_POST['group']
        );
    }
    elseif( $action == 'remove_group_item' ) {
        $rsp = NAVTAJX::remove_group_item(
        $_POST['id'],
        $_POST['group']
        );
    }
    elseif( $action == 'get_item_options') {
        $rsp = NAVTAJX::get_item_options(
        $_POST['id']
        );
    }
    elseif( $action == 'set_item_level') {
        $rsp = NAVTAJX::set_item_level(
        $_POST['id'],
        $_POST['dir']
        );
    }
    elseif( $action == 'set_item_options') {
        $rsp = NAVTAJX::set_item_options(
        $_POST['id'],
        $_POST['option']
        );
    }
    elseif( $action == 'set_item_disc') {
        $rsp = NAVTAJX::set_item_disc(
        $_POST['id']
        );
    }
    elseif( $action == 'item_help') {
        $rsp = NAVTAJX::item_help(
        $_POST['id'],
        $_POST['isIE']
        );
    }
    elseif( $action == 'verify') {
        $rsp = NAVTAJX::verify_item_options(
        $_POST['id']
        );
    }
    elseif( $action == 'ask_remove_item') {
        $rsp = NAVTAJX::ask_remove_item(
        $_POST['id']
        );
    }
    elseif( $action == 'restore' ) {
        $rsp = NAVTAJX::restore();
    }
    elseif( $action == 'sort_assets' ) {
        $rsp = NAVTAJX::sort_assets(
        $_POST['type'],
        $_POST['orderby']
        );
    }
    else {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s unhandled request: %s\n",
        __CLASS__, __FUNCTION__, $action), $_REQUEST);
    }
}

// send back a response to the request
navt_write_debug(NAVT_AJX, sprintf(" response: %s\n", $rsp));

header('Content-type: text/plain; charset: UTF-8');
header('Cache-Control: must-revalidate');
$expire_offset = 0;
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expire_offset) . ' GMT');
print $rsp;
wp_cache_flush();


/**
 * NAVT configuration class
 * @since .96
 */
class NAVTAJX {

    function init() {

        // initialize the class
        global $icfg, $gcfg, $navt_blank, $checkit, $selected;

        $icfg = NAVT::get_option(ICONFIG); // item configurations (includes group membership)
        $gcfg = NAVT::get_option(GCONFIG); // group configurations
        navt_loadtext_domain();

        $navt_blank = NAVT::get_url() ."/" . IMG_BLANK;
        $checkit = "checked='checked'";
        $selected = "selected='selected'";

        //navt_write_debug(NAVT_AJX, sprintf("%s\n", __FUNCTION__), $gcfg);
        //navt_write_debug(NAVT_AJX, sprintf("%s\n", __FUNCTION__), $icfg);
        navt_write_debug(NAVT_AJX, sprintf("\n%s::%s configurations loaded\n", __CLASS__, __FUNCTION__));
        return;
    }

    /**
     * Translate a string and send it back
     *
     * @param string $text
     * @return string json
     */
    function string_translate($text) {
        $o = __($text, 'navt_domain');
        return( NAVTAJX::mk_json_str(array('rc' => 'ok', 'text' => $o)) );
    }

    /**
     * Returns help text
     *
     * @param string $subject - help subject
     * @param string $tab - one of the group options tabs
     * @return string json
     */
    function help($subject, $ltIe7) {

        navt_write_debug(NAVT_AJX, sprintf("%s::%s subject %s\n", __CLASS__, __FUNCTION__, $subject ));

        global $navt_blank;
        $p1 = $p2 = $p3 = $p4 = $p5 = $p6 = '';
        $html = __('** Help does not exist for that subject **', 'navt_domain');
        $c = 'ok';
        $ex = (($ltIe7) ? 'gif' : 'png');
        $h = 550;
        $w = 750;

        if( $subject == 'assets' ) {
            $pages = 2;
            $title = __('NAVT Assets', 'navt_domain');

            // page 1
            $p1 = "<h3>" . __('The Asset Panel', 'navt_domain')."</h3><div class='left' style='width:49%'><p>" . __("The NAVT asset panel gives you the ability to create navigation items from the various resources you've created in Word Press. NAVT refers to your pages, categories, user's and other items as assets. The panel places each resource into the appropriate asset group. Groups are identified by type.", 'navt_domain')."</p><ol><li>".__("The location where unassigned assets are created. This area is typically grayed out until one or more navigation items are created. All newly created items will appear in this section.", 'navt_domain')."</li><li>".__("Icons indicate the type of resource contained in the listbox below it. Icons and listboxes exist for Pages, Categories, User's and Other items.", 'navt_domain')."</li><li>".__('The list of assets of the type indicated. Clicking an item within the listbox will create a copy of the item and place it in the unassigned area at the top.', 'navt_domain')."</li></ol></div><div class='left'><img src='@path@/images/apanel1.$ex'></div>";

            // page 2
            $p2 = "<div class='left' style='width:49%'><h3>".__('Creating Assets', 'navt_domain')."</h3><p>".__('This example illustrates what happened when the About page entry was selected.', 'navt_domain')."</p><ol><li>".__('About Page selected by clicking the name in the listbox.', 'navt_domain')."</li><li>".__('A copy of the About Page navigation item is created.', 'navt_domain')."</li><li>".__('The navigation item appears in the unassigned area.', 'navt_domain')."</li><li>".__("The About Page navigation item can be deleted by clicking the item's delete button.", 'navt_domain')."</li><li>".__('The navigation item can be dragged and dropped to a navigation group by moving the item from the unassigned group.', 'navt_domain')."</li></ol><p>".__('Any number of the same navigation items may be created and placed into different navigation groups. Each item (even if they represent identicle items) have separately configurable options. Item option setting for one item will not effect the option settings for another.', 'navt_domain')."</p></div><div class='right'><img src='@path@/images/apanel2.$ex'></div>";
        }

        elseif( $subject == 'new_group' ) {
            $pages = 2; $h = 575;
            $title = __('NAVT Groups and Items', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Navigation Groups', 'navt_domain')."</h3><p>".__("NAVT groups are a simple collection of navigation items composed from the pages, categories, users and other items that you've created in your WordPress blog. All available items are shown in the assets panel on the left side of the screen (see asset help). Items are divided into types and appear within separate lists.", 'navt_domain')."</p><h3>".__('Creating Groups', 'navt_domain')."</h3><p>".__("Navigation groups are created by entering a group name into the group name textbox in the NAVT toolbar at the top of the screen. After entering the name, click the create button. The group name you enter must begin with an alphabetic character and it cannot contain spaces. Group names are limited in size to no more than 10 characters. All group names must be unique. After creating the group, a new (and empty) group container will appear to the right of the assets panel. The group name will appear at the top of the group container.", 'navt_domain')."</p><div class='left' style='width: 70%;'><h3>".__('Group Toolbar', 'navt_domain')."</h3><p>".__('The group toolbar (shown on the right) contains three icons in addition to the area for the group name:', 'navt_domain')."</p><ol><li>".__('<strong>A lock icon</strong> - used to prevent group changes.', 'navt_domain')."</li><li>".__('The name of the navigation group.', 'navt_domain')."</li><li>".__('<strong>A trashcan</strong> - used to delete the entire group.', 'navt_domain')."</li><li>".__('<strong>A gear icon</strong> - used for setting group options.', 'navt_domain')."</li></ol></div><div style='float:left;'><img src='@path@/images/group_options.$ex' alt='".__('group toolbar', 'navt_domain')."' /></div>";

            // page 2
            $p2 = "<h3>".__('Creating Items', 'navt_domain')."</h3><p>".__("Navigation items are created by clicking on the name of an item listed in the Pages, Categories, User's and Others listboxes in the assets panel. When you select and item, a copy of the item will appear in unassigned section at the top of the assets panel. The item may then be dragged and dropped into any one of the group containers you previously created.", 'navt_domain')."</p><p>".__("More than one of the same item may be created and placed into the same or into a different group. Every item (even the same items) may be configured to have different options.", 'navt_domain')."</p><h3>".__('Item Tools', 'navt_domain')."</h3><p>".__('Each item contains a number of management tools.', 'navt_domain')."</p><div><img id='item-toolbar' src='@path@/images/item_options.$ex' alt='".__('item tools', 'navt_domain')."' /><ol><li>".__("<strong>The item alias</strong> - the name of the item as it will appear in the navigation list. Click the item alias to open the item's option box.", 'navt_domain')."</li><li>".__('<strong>The item icon</strong> - represents the type of item (page, category, user or other). The icon is used to move the item from group to group.', 'navt_domain')."</li><li>".__('<strong>Connect/Disconnect icon</strong> - disconnecting the item means that the item remains in the group, however, the item will not be included in the output navigation list created for the web site. Connecting the item means that the item will be included in the output navigation list. This feature is used for temporarily eliminating an item without removing it from the group.', 'navt_domain')."</li><li>".__('<strong>Item removal icon</strong> - this icon is used to delete the item from the group.', 'navt_domain')."</li><li>".__('<strong>Hierarchical control arrows</strong> - used for moving the item to the left or right to indicate parent/child relationships.', 'navt_domain')."</li></ol></div>";
        }

        elseif( $subject == 'backup' ) { // backup/restore
            $pages = 1; $h = 200;
            $title = __('Backup/Restore', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Backup/Restore', 'navt_domain')."</h3><p>".__("Selecting the <strong>Backup</strong> button will cause NAVT to create a XML file containing the configuration information for all of your navigation groups. The XML backup file is stored on your local computer. Restore the backup by selecting the <strong>Restore</strong> button. The restore function merges your current navigation groups with the contents of the backup file; restore can be used to create placeholders for missing categories, pages or users if they were removed after the backup was created. Full instructions are provided on the restore page.", 'navt_domain').'</p>';
        }

        elseif( $subject == 'group-options-tab-0' ) {

            $pages = 2; //$h = 625;
            $title = __('Configuring Group Options', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Show only top-level anchors on page', 'navt_domain')."</h3><p>".__("When checked, the option Show only top-level anchors on page (hide hierarchy) will display only the links to the child pages of the current page. If a child page contains children of it's own, those anchors will only be shown when the child page is viewed. For example, if you created the following navigation group:", 'navt_domain')."</p><ul><li>".__('Item 1', 'navt_domain')."<ul><li>".__('Item 1a', 'navt_domain')."</li><li>".__('Item 1b', 'navt_domain')."</li></ul></li><li>".__('Item 2', 'navt_domain')."<ul><li>".__('Item 2a', 'navt_domain')."</li></ul></li></ul><p>".__('The Home Page would only display a navigation list containing Item 1 and Item 2. Their respective child links would not be visible. If you clicked Item 1, the newly displayed blog page would show the navigation links for Item 1a and Item 1b. The navigation list shown for Item 2 would contain only Item 2a.', 'navt_domain')."</p><p>".__('The option has an additional feature: Add page return to list. If this is checked, NAVT will add an additional link to the child navigation list allowing the user to return to the parent page.', 'navt_domain')."</p><h3>".__('Show breadcrumb navigation', 'navt_domain')."</h3><p>".__('Selecting this option will cause NAVT to add a breadcrumb path to the currently displayed page above the navigation list. For example, a user currently viewing blog page Item 1a would see the breadcrumb trail Home  &raquo; Item 1 &raquo; Item 1a. Selecting a link in the breadcrumb trail will take the user to the previously viewed blog page. The path can be easily styled (using CSS) to appear anywhere on the page using absolute positioning.', 'navt_domain')."</p>";

            // page 2
            $p2 = "<h3>".__('Create navigation list using HTML select', 'navt_domain')."</h3><p>".__("Selecting this option will turn your navigation list into a Drop Down list of navigation items constructed using a HTML SELECT statement. This should not be confused with Drop Down lists produced by CSS. A typical Drop Down would appear as:", 'navt_domain')."</p><p style='padding-left: 25px'><select style='width: 125px; border: 1px solid #333; padding: 3px;'><option selected='selected'>".__('Item 1', 'navt_domain')."</option><option>".__('Item 2', 'navt_domain')."</option><option>".__('Item 3', 'navt_domain')."</option><option>".__('Item 4', 'navt_domain')."</option><option>".__('Item 5', 'navt_domain')."</option><option>".__('Item 6', 'navt_domain')."</option></select></p><p>".__("This option has the additional feature:  Display: 'N' item(s) in list. This is used to force the SELECT to show a maximum number of entries. The default is 1. For example, entering the number 3 in the box will cause the Drop Down to appear as:", 'navt_domain')."</p><p style='padding-left: 25px'><select size='3' style='width: 125px; border: 1px solid #333; padding: 3px;'><option selected='selected'>".__('Item 1', 'navt_domain')."</option><option>".__('Item 2', 'navt_domain')."</option><option>".__('Item 3', 'navt_domain')."</option><option>".__('Item 4', 'navt_domain')."</option><option>".__('Item 5', 'navt_domain')."</option><option>".__('Item 6', 'navt_domain')."</option></select></p><p>".__('Here we can see the first three available selections. In this case, the box contains more than three items so the browser adds a scrollbar on the right. This Drop Down box does not support multiple selections.', 'navt_domain')."</p><h3>".__('Set navigation group to private', 'navt_domain')."</h3><p>".__('This option enables you to hide all navigation items from casual viewing if the viewer is not signed into your web site. Hiding the members of navigation lists can also be done by selecting the individual privacy option for each of the navigation items. Selecting this option will also change the color of the group heading (only in the NAVT List Page) to indicate the group is set to private.', 'navt_domain')."</p>";
        }

        else if( $subject == 'group-options-tab-1' ) {
            $pages = 1;
            $title = __('Configuring Display Options', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Controlling Where Navigation Groups are Displayed', 'navt_domain')."</h3><p>".__('The display of any Navigation group can be controlled by specifying the page types where the navigation group should appear. The Show navigation group on... area allows you to indicate whether or not the navigation group is to appear on:', 'navt_domain')."</p><ul><li>".__('The Home Page', 'navt_domain')."</li><li>".__('A Static Front Page (if configured)', 'navt_domain')."</li><li>".__('An archive page (categories, user pages, date pages, etc)', 'navt_domain')."</li><li>".__('The search results page', 'navt_domain')."</li><li>".__("The 'page not found' page (404 errors)", 'navt_domain')."</li></ul><h3>".__('Settings for Available Posts/Pages', 'navt_domain')."</h3><p>".__('Navigation groups can also be displayed (or not displayed) on all or on individually selected posts and/or pages. To display a navigation group on one or more selected posts or pages:', 'navt_domain')."</p><ol><li>".__('Check Settings for available posts and/or Settings for available pages. If these options are not checked, the navigation group will be displayed on all single pages and single post pages.', 'navt_domain')."</li><li>".__('Select Display this navigation group only on selected posts (or pages) or Do Not Display this navigation group only on selected posts (or pages). Selecting one of these radio buttons determines whether or not the Navigation group is to be shown on or not shown on the posts or pages that you select in the relevant list box.', 'navt_domain')."</li></ol><p>".__('Pages and posts are listed by their respective titles.', 'navt_domain')."</p>";
        }

        else if( $subject == 'group-options-tab-2' ) {
            $pages = 1;
            $title = __('Styling Navigation Groups', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('CSS Styles', 'navt_domain')."</h3><p>".__("NAVT provides several CSS classes to the HTML it creates for your web site. Classes are used to assist you in styling your navigation groups. In addition, you have the choice of either applying native Word Press styles or not using any styles at all. Your own styles can exclusively used by selecting 'Do not apply CSS classes' and adding 'Apply the CSS information below to this navigation group", 'navt_domain')."</p><h3>".__('User Styles', 'navt_domain')."</h3><p>".__('By default, NAVT constructs navigation groups for your web site by building one or more unordered lists from the contents of the group. The number of unordered tag groups is dependent on whether or not parent/child relationships exist amoung the various navigation items in the list. Lists begin with a standard UL tag followed by one or more LI tags or nested UL tags contained within an LI tag.', 'navt_domain')."</p><p>".__('To add CSS classes, enter the class names in the textboxes provided. Multiple class names can be entered (per tag) by separating each class name with a space. No more than one selector name can be entered into the UL Selector ID textbox.', 'navt_domain')."</p><ul><li>".__('<strong>UL selector ID</strong> - The selector ID given to the parent UL tag.', 'navt_domain')."</li><li>".__('<strong>UL tag CSS class</strong> - The class applied to the parent UL tag.', 'navt_domain')."</li><li>".__('<strong>LI tag CSS class</strong> - The class applied to all LI tags.', 'navt_domain')."</li><li>".__('<strong>LI active page CSS class</strong> - The class applied to the active LI tag.', 'navt_domain')."</li><li>".__('<strong>LI tag parent container</strong> - The class applied to the LI parent of a nested UL tag.', 'navt_domain')."</li><li>".__('<strong>LI tag parent container (active)</strong> - The class applied to the parent container of the active LI tag.', 'navt_domain')."</li></ul>";
        }

        else if( $subject == 'group-options-tab-3' ) {
            $pages = 1; $h = 250;
            $title = __('Theme Integration', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Theme Integration', 'navt_domain')."</h3><p>".__("This section enables you to integrate navigation groups into a Word Press theme by combining an <a class='definition' href='http://docs.jquery.com/DOM/Traversing/Selectors' title='xpath expressions'>xpath expression</a> with one of the actions from the action list.</p><p>For example, if you wanted to insert the navigation group above a division in your theme where the id of the division were named <strong>header</strong> then you would enter: <strong>#header</strong> into the xpath textbox. A hash character <strong>must proceed the id</strong> to indicate that the xpath contains a selector id. The navigation group can be inserted above, below, at the top or at the bottom of the location expressed in the xpath text box.</p><p>The navigation group can be preceeded by any number of additional HTML tags. HTML tags can contain an id and/or CSS classes. Selector ids and class names <strong>must be enclosed within double quotes</strong>. Ending tags must be entered if beginning tags are used.", 'navt_domain')."</p>";
        }

        elseif( $subject == 'itemtype-1' ) { // pages
            $pages = 1; $h = 650;
            $title = __('Configuring Page Options', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Item Alias', 'navt_domain')."</h3><p>".__("The label that is given to a link is called a menu item alias. The alias is what the user sees in the navigation list on the web page. The alias may differ from the title of a page. In the diagram, A points to the item alias and B points to the item's title. The title is the name you gave the page when it was created. Changing the title will not change the alias you assigned nor will changing the alias change the page title. Item C is the icon used to signify a page.", 'navt_domain')."</p><h3>".__('Options for Pages', 'navt_domain')."</h3><img alt='page options' style='float:right; overflow:visible;' src='@path@/images/page-dia.$ex' /><ol><li>@aliasInfo@</li><li>@anchorInfo@</li><li>@cssClassInfo@</li><li>@privateInfo@</li><li>@noFollowInfo@</li></ol>";

        }

        elseif( $subject == 'itemtype-2' ) { // Categories
            $pages = 1; $h = 725;
            $title = __('Configuring Category Options', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Item Alias', 'navt_domain')."</h3><p>".__("The label that is given to a link is called a menu item alias. The alias is what the user sees in the navigation list on the web page. The alias may differ from the category name. In the diagram, A points to the item alias and B points to the item's title. The title is the name you gave the category when it was created. Changing the title will not change the alias you assigned nor will changing the alias change the category title. Item C is the icon used to signify a category.", 'navt_domain')."</p><h3>".__('Options for Categories', 'navt_domain')."</h3><img alt='category options' style='float:right; overflow:visible;' src='@path@/images/cat-dia.$ex' /><ol><li>@aliasInfo@</li><li>@anchorInfo@</li><li>@cssClassInfo@</li><li>".__('Show in WP category list. By default NAVT will prevent a category that has been used in a NAVT navigation list from also appearing in a category list generated by Word Press. Check this option if you want the category displayed in both locations.', 'navt_domain')."</li><li>".__('<strong>Append post count</strong> - NAVT appends the number of posts contained in the category to the category name.', 'navt_domain')."</li><li>".__("<strong>Show if Empty</strong> - causes the category link to appear even if it doesn't contain any posts.", 'navt_domain')."</li><li>".__('<strong>Use description as tooltip</strong> - Uses the description of the category as the mouse hover tooltip.', 'navt_domain')."</li><li>@privateInfo@</li><li>@noFollowInfo@</li></ol>";
        }

        elseif( $subject == 'itemtype-3-'.HOMEIDN ) { // home item
            $pages = 1; $w = 800;
            $title = __('Configuring Home Page Options', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Item Alias', 'navt_domain')."</h3><p>".__('The label that is given to a link is called a menu item alias. The alias is what the user sees in the navigation list on the web page. The alias may differ from the Home title. In the diagram, A points to the item alias and B points to the Home title.  Item C is the icon used to signify the Home navigation item.', 'navt_domain')."</p><h3>".__('Options', 'navt_domain')."</h3><img alt='".__('home options', 'navt_domain')."' style='float:right; overflow:visible;' src='@path@/images/home-dia.$ex' /><ol><li>@aliasInfo@</li><li>@anchorInfo@</li><li>@cssClassInfo@</li><li>@noFollowInfo@</li></ol>";
        }

        elseif( $subject == 'itemtype-3-'.LOGINIDN ) { // admin item
            $pages = 1; $h = 715; $w = 850;
            $title = __('Configuring SignIn Options', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Sign in/Register Item', 'navt_domain')."</h3><p>".__('This item is equivalent to the Word Press sign-on/register anchors as part of the Word Press meta data. The anchor presented depends on whether or not the user has signed into the web site. When signed in, the anchor allows them to sign out; if not signed in and the Word Press Membership option: anyone can register is checked, the anchor will allow the user to register or sign in.', 'navt_domain')."</p><h3>".__('Item Alias', 'navt_domain')."</h3><p>".__('The label that is given to a link is called a menu item alias. The alias is what the user sees in the navigation list on the web page. The alias may differ from the sign-in title. In the diagram, A points to the item alias and B points to the sign-in title. Item C is the icon used to signify the sign-in administration item.', 'navt_domain')."</p><h3>".__('Options for Site Administration', 'navt_domain')."</h3><img alt='".__('sign-in options', 'navt_domain')."' style='float:right; overflow:visible;' src='@path@/images/signin-dia.$ex' /><ol><li>@aliasInfo@</li><li>@anchorInfo@</li><li>@cssClassInfo@</li><li>".__('<strong>Create sign-in form</strong> - selecting this will create a small sign-in form on the web site.', 'navt_domain')."</li><li>".__('<strong>Use referral page redirect</strong> - After signing in, the user remains on the page where they signed in.', 'navt_domain')."</li><li>".__('<strong>Use URL redirect</strong> - When checked, the user is redirected to the URL that is contained in the redirect URL textbox.', 'navt_domain')."</li><li>".__('<strong>Redirect URL text box </strong>- enables you to send the signed in user to another page on your web site. Enter a complete URL in this box.', 'navt_domain')."</li><li>@privateInfo@</li><li>@noFollowInfo@</li></ol>";
        }

        elseif( $subject == 'itemtype-4' ) { // divider item
            $pages = 1; $h = 620;
            $title = __('Configuring Divider Options', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Divider Items', 'navt_domain')."</h3><p>".__("A divider is primarily designed to separate navigation lists into groups of related items. A divider can be inserted between elements to provide an empty space, a horizontal line or a plain text title. The menu item alias serves as the title when the divider type is set to 'plain text'.", 'navt_domain')."</p><h3>".__('Item Alias', 'navt_domain')."</h3><p>".__('The label that is given to a link is called a menu item alias. The alias is what the user sees in the navigation list on the web page. The alias is used as a title or header when the divider type is set to plain text. No alias is displayed when the divider type is set to either empty space horizontal rule. In the diagram, A points to the item alias and B points to the List Divider title. Item C is the icon used to signify the dvider navigation item.', 'navt_domain')." </p><h3>".__('Divider Options', 'navt_domain')."</h3><img alt='".__('home options', 'navt_domain')."' style='float:right; overflow:visible;' src='@path@/images/divider-dia.$ex' /><ol><li>@aliasInfo@</li><li>@anchorInfo@</li><li>@cssClassInfo@</li><li>".__('<strong>Divider type</strong> - the type of divider to create.', 'navt_domain')."</li><li>@privateInfo@</li></ol>";
        }

        elseif( $subject == 'itemtype-5' ) { // elink item
            $pages = 1; $h = 720;
            $title = __('Configuring URI Options', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('URI Items', 'navt_domain')."</h3><p>".__("A Uniform Resource Identifier (URI), is a compact string of characters used to identify or name a resource. The main purpose of this identification is to enable interaction with representations of the resource over a network, typically the World Wide Web, using specific protocols. URIs are defined in schemes defining a specific syntax and associated protocols. URI's supported by NAVT are limited to: http:// for identifying both internal or extern web site pages and mailto: for providing an anchor for sending email.", 'navt_domain')."</p><h3>".__('Item Alias', 'navt_domain')."</h3><p>".__("The label that is given to a link is called a menu item alias. The alias is what the user sees in the navigation list on the web page. In the diagram, A points to the item alias and B points to the item's title. The title will always be User defined URI. Item C is the icon used to signify a URI item.", 'navt_domain')."</p><h3>".__('URI Options','navt_domain')."</h3><img alt='".__('home options', 'navt_domain')."' style='float:right; overflow:visible;' src='@path@/images/elink-dia.$ex' /><ol><li>@aliasInfo@</li><li>@anchorInfo@</li><li>@cssClassInfo@</li><li>".__('<strong>URL text box</strong> - a complete resource identifier (i.e. http:// or mailto:)', 'navt_domain')."</li><li>".__('<strong>Open in same window</strong> - Opens in the same window, otherwise opens the resource in a new window.','navt_domain')."</li><li>@privateInfo@</li><li>@noFollowInfo@</li></ol>";
        }

        elseif( $subject == 'itemtype-6' ) { // author item
            $pages = 1; $h = 680; $w = 820;
            $title = __('Configuring Registered User Options', 'navt_domain');

            // page 1
            $p1 = "<h3>".__('Item Alias','navt_domain')."</h3><p>".__("The label that is given to a link is called a menu item alias. The alias is what the user sees in the navigation list on the web page. The alias may differ from the user display name. In the diagram, A points to the item alias and B points to the user's signin name. Changing the user's display name will not change the alias you assigned nor will changing the alias change the display name. Item C is the icon used to signify a registered user. ", 'navt_domain')."</p><h3>".__('Options for Users','navt_domain')."</h3><img alt='".__('category options','navt_domain')."' style='float:right; overflow:visible;' src='@path@/images/user-dia.$ex' /><ol><li>@aliasInfo@</li><li>".__('<strong>Show avatar</strong> - select this to place an avatar next to the user name','navt_domain')."</li><li>".__('<strong>Use default avatar</strong> - allows you to use one of the default avatars in the drop down list.','navt_domain')."</li><li>".__('A list of default avatars to choose from. The selected avatar is displayed above the drop down box.','navt_domain')."</li><li>".__('<strong>User avatar URI</strong> - this is active only if use default avatar is not checked. Enter a qualified URI for the avatar to be used for this user.', 'navt_domain')."</li><li>".__('<strong>Show if no posts</strong> - When selected, NAVT will show this user if the user has not posted on the web site.','navt_domain')."</li><li>".__('<strong>Append author post count</strong> - Appends the number of posts to the users anchor.','navt_domain')."</li><li>".__("<strong>Include web site</strong> - If you have entered a web site address to the user's profile, it will be included as an anchor.",'navt_domain')."</li><li>".__('<strong>Include user bio</strong> - If you have entered a bio for this user, the text will be included.','navt_domain')."</li><li>".__("<strong>Include user email</strong> - selecting this will add the user's email address (mailto:) format.", 'navt_domain')."</li><li>".__('<strong>Hide link text</strong> - Available only if you are using an avatar. Selecting this will hide all user anchor text.','navt_domain')."</li><li>@privateInfo@</li><li>@noFollowInfo@</li></ol>";
        }

        if( $c == 'ok' ) {
            $html = '';

            if( $pages ) {

                for( $p = 1; $p <= $pages; $p++ ) {

                    // add content
                    $html .= "<div id='p$p' class='hcontent' " . (($p > 1) ? "style='display:none;'" : '') . ">".
                    "@content-p$p"."@</div>";

                    $html .= "<div id='pgb-rap'>";

                    // add page buttons
                    if( $p < $pages ) {
                        $html .=
                        "<a id='p".$p."n' href='#' title='". __('go to next page', 'navt_domain') .
                        "' ".(($p != 1) ? "style='display:none;'":'') . ">".
                        "<div class='pgbutton'><p>" . __('Next', 'navt_domain') . "</p></div></a>";
                    }

                    if( $p > 1 ) {
                        $html .=
                        "<a id='p".$p."p' href='#' title='". __('go to previous page', 'navt_domain') . "' style='display:none;' >".
                        "<div class='pgbutton'><p>" . __('Previous', 'navt_domain') . "</p></div></a>";
                    }

                    $html .= "</div>";
                }
            }

            /**
             * Complete the page
             */
            $html = str_replace('@content-p1@', $p1, $html);
            $html = str_replace('@content-p2@', $p2, $html);
            $html = str_replace('@content-p3@', $p3, $html);
            $html = str_replace('@content-p4@', $p4, $html);
            $html = str_replace('@content-p5@', $p5, $html);
            $html = str_replace('@content-p6@', $p6, $html);
            $html = str_replace('@path@', NAVT::get_url(), $html);

            /**
             * Substitutions for common options
             */
            $html = str_replace('@aliasInfo@',
            __("The item alias edit box. Use this to change the name that appears in the navigation menu.", 'navt_domain'), $html);

            $html = str_replace('@anchorInfo@',
            __("<strong>Anchor type</strong> - Choose the type of link you want to appear on the web page. The choices are:", 'navt_domain')."<ul><li>".__('<strong>Standard Text Link</strong> - a link that only contains text.','navt_domain')."</li><li>".__('<strong>Text over Graphic</strong> - a link that contains text over a graphic background.','navt_domain')."</li><li>".__('<strong>Text with Side Graphic</strong> - a link that is accompanied by a graphic. Usually used with icons.','navt_domain')."</li><li>".__('<strong>Graphic link</strong> - a link that is represented by a graphic (no text). Usually used for creating buttons.','navt_domain')."</li></ul>".__('All links that contain graphics require a CSS class that will be applied to the graphic container.', 'navt_domain'), $html);

            $html = str_replace('@cssClassInfo@',
            __("<strong>CSS class for graphic</strong> - used only with anchors that use graphics. Enter the name of the class to be used.", 'navt_domain'), $html);

            $html = str_replace('@noFollowInfo@',
            __("<strong>Add rel=nofollow</strong> - nofollow is a non-standard HTML attribute value used to instruct search engines that a hyperlink should not influence the link target's ranking in the search engine's index.", 'navt_domain')." <a class='definition' href='http://en.wikipedia.org/wiki/Nofollow' title='".__('NoFollow Definition', 'navt_domain')." target='_blank'>".__('see definition', 'navt_domain')."</a>", $html);

            $html = str_replace('@privateInfo@',
            __("<strong>Set to private</strong> - if checked, the anchor is visible only to those that are signed into your web site.", 'navt_domain'), $html);
        }

        navt_write_debug(NAVT_AJX, sprintf("%s::%s %s\n", __CLASS__, __FUNCTION__, $html));
        $html = str_replace('"', '&quot;', $html);

        // open a window
        return( NAVTAJX::mk_json_str(array('rc' => $c, 'width' => $w, 'height' => $h, 'title' => $title, 'html' => $html)) );
    }

    /**
     * Add a new group
     *
     * @param string $group_name
     * @return string (json)
     * @since .96
     */
    function add_new_group($group_name) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s group %s\n", __CLASS__, __FUNCTION__, $group_name));

        global $icfg, $gcfg;
        $text = __('add group error', 'navt_domain');
        $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'group_add', 'msg' => $text));

        // sanitize the name
        $group_name = NAVT::clean_group_name($group_name);

        if('' == $group_name ) {
            $text = __('invalid name', 'navt_domain');
            $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'name', 'msg' => $text));
        }
        else {
            //first letter can't be numeric
            $c = substr($group_name, 0, 1);
            if(is_numeric($c)) {
                $text = __('invalid name', 'navt_domain');
                $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'name', 'msg' => $text));
            }
            else {
                // check for a duplicate
                if( NAVTAJX::check_for_duplicate_group($group_name) ) {
                    $text = __('duplicate name', 'navt_domain');
                    $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'name', 'msg' => $text));
                }
                else {

                    $groups = $icfg;
                    $n_icfg = array();

                    if(is_array($groups)) {
                        foreach( $groups as $group => $members ) {
                            $n_icfg[$group] = $members;
                        }

                        $scheme = NAVT::get_option(SCHEME);
                        $scheme = ($scheme + 1 > 6) ? 1: $scheme + 1;
                        $gcfg[$group_name] = NAVT::mk_group_config();
                        $n_icfg[$group_name] = array();
                        $display_name = NAVT::truncate($group_name, MAX_GROUP_NAME);

                        NAVT::update_option(SCHEME, $scheme);
                        NAVT::update_option(ICONFIG, $n_icfg);
                        NAVT::update_option(GCONFIG, $gcfg);
                        $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'name' => $group_name,
                        'display_name' => $display_name, 'scheme' => $scheme));
                    }
                }
            }
        }
        navt_write_debug(NAVT_AJX, sprintf("%s::%s rc=%s\n", __CLASS__, __FUNCTION__, $rc));
        return($rc);
    }

    /**
     * Rename a group
     *
     * @param string $target_group
     * @param string $new_name
     * @return string (json)
     * @since .96
     */
    function rename_group($target_group, $new_name) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s old %s, new %s\n", __CLASS__, __FUNCTION__, $target_group, $new_name));

        global $icfg, $gcfg;
        $n_gcfg = $n_icfg = array();
        $text = __("Problem renaming group");
        $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'rename', 'msg' => $text));

        // sanitize the name
        $new_name = NAVT::clean_group_name($new_name);
        if('' == $new_name ) {
            $text = __('invalid name', 'navt_domain');
            $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'rename', 'msg' => $text));
        }
        else {
            //first letter can't be numeric
            $c = substr($new_name, 0, 1);
            if(is_numeric($c)) {
                $text = __('invalid name', 'navt_domain');
                $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'rename', 'msg' => $text));
            }
            else {
                // check for a duplicate
                if( NAVTAJX::check_for_duplicate_group($new_name) ) {
                    $text = __('duplicate name', 'navt_domain');
                    $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'rename', 'msg' => $text, 'name' => $target_group));
                }
                else {
                    if(is_array($icfg)) {

                        $display_name = NAVT::truncate($new_name, MAX_GROUP_NAME);
                        $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'old_name' => $target_group,
                        'new_name' => $new_name, 'display_name' => $display_name));

                        foreach( $icfg as $group => $members ) {
                            if( $group != $target_group ) {
                                $n_icfg[$group] = $members;
                            }
                            else {
                                foreach($members as $id => $member) {
                                    $member[GRP] = $new_name;
                                    $n_icfg[$new_name][$id] = $member;
                                }
                            }
                        }

                        foreach( $gcfg as $key => $group_data ) {
                            if( $key != $target_group ) {
                                $n_gcfg[$key] = $group_data;
                            }
                            else {
                                $n_gcfg[$new_name] = $group_data;
                            }
                        }

                        if( count($n_icfg) > 0 ) {
                            NAVT::update_option(ICONFIG, $n_icfg);
                            NAVT::update_option(GCONFIG, $n_gcfg);
                        }
                    }// end if
                }// end else
            }// end else
        }// end else

        navt_write_debug(NAVT_AJX, sprintf("%s::%s rc=%s\n", __CLASS__, __FUNCTION__, $rc));
        return($rc);
    }

    /**
     * Ask to remove a group - returns a prompt for display
     *
     * @param string $group
     * @return string (json)
     * @since .96
     */
    function ask_remove_group($group) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s group %s\n", __CLASS__, __FUNCTION__, $group));

        global $navt_blank;
        $name = strtolower($group);
        $title = __('Delete this group?', 'navt_domain');

        $html =
        "<p>".__('Select Ok to remove group', 'navt_domain')." '$name' </p>".
        "<div class='bwrapper'>".
        "<a href='#' id='$name-remove-ok' title=''><div class='okbutton'><p>".__('Ok', 'navt_domain')."</p></div></a>".
        "<a href='#' id='$name-remove-can' title=''><div class='canbutton'><p>".__('Cancel', 'navt_domain')."</p></div></a>".
        "</div>";

        $html = str_replace('"', "&quot;", $html);

        $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'html' => $html, 'width' => 400, 'height' => 125,
        'title' => $title, 'group' => strtoupper($name)));
        return($rc);
    }

    /**
     * Remove a group
     *
     * @param string $group
     * @return string (json)
     * @since .96
     */
    function remove_group($group) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s group %s\n", __CLASS__, __FUNCTION__, $group));

        $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'group' => strtoupper($group)));
        NAVTAJX::remove_configured_group($group);
        return($rc);
    }

    /**
     * Reorder the items in all groups - rebuilds the configuration arrays
     * to represent item membership and position of each item. New items
     * are added here by way of discovery.
     *
     * @param array $order - 2d array indexed by group and selector id
     * @return string (json)
     * @since .96
     */
    function reorder_groups($order) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s\n", __CLASS__, __FUNCTION__), $order);

        global $icfg, $gcfg;
        $rc = NAVTAJX::mk_json_str(array('rc' => 'ok'));
        $n_icfg = $n_gcfg = array();

        if( !is_array($order) ) {
            // array is empty - no groups left
            NAVT::update_option(ICONFIG, array());
            NAVT::update_option(GCONFIG, array());
        }
        else {
            $configured_ids = NAVTAJX::get_configured_items();
            $assets = NAVT::get_option(ASSETS);
            navt_write_debug(NAVT_AJX, sprintf("\t%s::%s assets\n", __CLASS__, __FUNCTION__), $assets);

            foreach( $order as $group => $member ) {
                if( is_array($member) ) {
                    foreach( $member as $idx => $id ) {
                        if( !array_key_exists($id, $configured_ids) ) {
                            navt_write_debug(NAVT_AJX, sprintf("%s:%s new item: group %s, id %s\n",
                            __CLASS__, __FUNCTION__, $group, $id));

                            if( !in_array($id, $icfg) ) {
                                //   a-type-idn--xxxx  where 'xxxx' is a unique instance number
                                //$t[  0       ][  1 ]
                                $t = split('--', $id); // remove the instance number

                                //   a-type-idn
                                //$t[0][ 1 ][2]
                                $t = split('-', $t[0]); // get the type and idn

                                $typ  = $t[1];
                                $idn  = $t[2];
                                $asset = $assets[$typ][$idn];
                                $configured_ids[$id] = $asset;
                                navt_write_debug(NAVT_AJX, sprintf("\t%s::%s new item added\n", __CLASS__, __FUNCTION__));
                            }
                        }
                        // update this item
                        $item = $configured_ids[$id];
                        $item[GRP] = $group;
                        $n_icfg[$group][$id] = $item;
                        if( isset($gcfg[$group]) ) {
                            $n_gcfg[$group] = $gcfg[$group];
                        }
                    }
                }
            }
        }

        if( count($n_icfg) > 0 ) {
            NAVT::update_option(ICONFIG, $n_icfg);
            NAVT::update_option(GCONFIG, $n_gcfg);
            $icfg = $n_icfg;
            $gcfg = $n_gcfg;
        }

        navt_write_debug(NAVT_AJX, sprintf("%s::%s new\n", __CLASS__, __FUNCTION__), $n_icfg);
        navt_write_debug(NAVT_AJX, sprintf("%s::%s rc=%s\n", __CLASS__, __FUNCTION__, $rc));
        return($rc);
    }

    /**
     * Add an asset to a group
     *
     * @param string $id
     * @param string $group
     * @return string (json)
     * @since .96
     */
    function add_group_item($id, $group) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s group %s, id %s\n", __CLASS__, __FUNCTION__, $group, $id));

        global $icfg;
        $rc = NAVTAJX::mk_json_str(array('rc' => 'ok'));
        $assets = NAVT::get_option(ASSETS);

        if( !in_array($id, $icfg) ) {
            //  a-type-idn--xxxx  where 'xxxx' is a unique instance number
            //$t[ 0       ][  1 ]
            $t = split('--', $id);

            //   a-type-idn
            //$t[0][ 1 ][2]
            $t = split('-', $t[0]); // get the type and idn

            $typ  = $t[1];
            $idn  = $t[2];
            $asset = $assets[$typ][$idn];
            $n_icfg = NAVTAJX::insert_configured_item($group, $id, $asset);
            if( count($n_icfg) > 0 ) {
                NAVT::update_option(ICONFIG, $n_icfg);
            }
            navt_write_debug(NAVT_AJX, sprintf("%s::%s new\n", __CLASS__, __FUNCTION__), $n_icfg);
        }

        navt_write_debug(NAVT_AJX, sprintf("%s::%s rc=%s\n", __CLASS__, __FUNCTION__, $rc));
        return($rc);
    }

    /**
     * Toggles group locking option
     *
     * @param string $group
     * @param string $option
     * @return json string
     * @since .96
     */
    function toggle_group_option($group, $option) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s group %s, option %s\n", __CLASS__, __FUNCTION__, $group, $option));

        global $gcfg;
        $group = strtolower($group);
        $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'name' => $group, 'option' => '@STATE@'));
        $opts = intval($gcfg[$group]['options'],10) & 0xffff;
        $bit = 0;

        if( $option == 'lock') $bit = ISLOCKED;

        if( $bit != 0 ) {
            $opts = ($opts & $bit ? $opts - $bit: $opts + $bit);
            $gcfg[$group]['options']= $opts;
            NAVT::update_option(GCONFIG, $gcfg);
        }

        $rc = str_replace('@STATE@', (($opts & $bit) ? '1': '0'), $rc);
        return($rc);
    }

    /**
     * Returns group options
     *
     * @param string $group
     * @param string $option
     * @param string $value
     * @return json string
     * @since .96
     */
    function get_group_options($group, $active_tab='tab-0') {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s group %s\n", __CLASS__, __FUNCTION__, $group));

        global $gcfg, $navt_blank;

        $name = strtolower($group);
        $options = intval($gcfg[$name]['options'], 10) & 0xffff;
        $select_size = (intval($gcfg[$name]['select_size'], 10) & 0xffff);
        $display = $gcfg[$name]['display'];
        $css_id = $gcfg[$name]['css']['ulid'];
        $css_ul = $gcfg[$name]['css']['ul'];
        $css_li = $gcfg[$name]['css']['li'];
        $css_li_current = $gcfg[$name]['css']['licurrent'];
        $css_li_parent = $gcfg[$name]['css']['liparent'];
        $css_li_parent_active = $gcfg[$name]['css']['liparent_active'];
        $isprivate_group = ((intval($options, 10) & ISPRIVATE) ? 1: 0);
        $theme_xpath = $gcfg[$name]['selector']['xpath'];
        $theme_option = intval($gcfg[$name]['selector']['option'], 10) & 0xffff;
        $before_group = $gcfg[$name]['selector']['before'];
        $after_group = $gcfg[$name]['selector']['after'];
        $display_name = NAVT::truncate($name, MAX_GROUP_NAME);

        $html =
        "<div class='optheader'><div class='option-spinner'></div><h3>".__('Options for group ', 'navt_domain') ."'$name'</h3>" .
        "<div class='helpbox'><a class='optionhelp' href='#' title='".__('help', 'navt_domain')."'><img src='@SRC@' alt=''/></a></div>" .
        "<div class='closebox'><a href='#' title='".__('close', 'navt_domain')."'><img src='@SRC@' alt=''/></a></div></div>" .

        "<ul class='boxtabs'>" .
        "<li class='boxtab firsttab'><a href='#' title='".__('options', 'navt_domain') .
        "' onclick='return(@NS@.group_options_tab(0));'><h3 id='tab-0' class='r2 ".
        (($active_tab != 'tab-0') ? 'dormant': 'activetab')."'>" .
        __('Options', 'navt_domain')."</h3></a></li>".

        "<li class='boxtab'><a href='#' title='".__('display', 'navt_domain').
        "' onclick='return(@NS@.group_options_tab(1));'><h3 id='tab-1' class='r2 ".
        (($active_tab != 'tab-1') ? 'dormant': 'activetab')."'>" .
        __('Display', 'navt_domain')."</h3></a></li>".

        "<li class='boxtab'><a href='#' title='".__('group css styles', 'navt_domain').
        "' onclick='return(@NS@.group_options_tab(2));'><h3 id='tab-2' class='r2 ".
        (($active_tab != 'tab-2') ? 'dormant': 'activetab')."'>" .
        __('CSS', 'navt_domain')."</h3></a></li>".

        "<li class='boxtab'><a href='#' title='".__('theme integration', 'navt_domain').
        "' onclick='return(@NS@.group_options_tab(3));'><h3 id='tab-3' class='r2 ".
        (($active_tab != 'tab-3') ? 'dormant': 'activetab')."'>" .
        __('Theme', 'navt_domain')."</h3></a></li>".

        "</ul><br clear='all' />" .

        "<form id='option-form'>".
        "<input id='options-for-group' type='hidden' value='".$name."'/>" .

        "<div id='optionstab' ".(($active_tab == 'tab-0') ? '' : "style='display:none;'") .">" .
        NAVTAJX::get_options_for_display($options, $select_size) .
        "</div>" .

        "<div id='displaytab' ".(($active_tab == 'tab-1') ? '' : "style='display:none;'") .">" .
        NAVTAJX::get_other_for_display($display) .
        NAVTAJX::get_posts_for_display($display) .
        NAVTAJX::get_pages_for_display($display) .
        "</div>" .

        "<div id='csstab' ".(($active_tab == 'tab-2') ? '' : "style='display:none;'") .">" .
        NAVTAJX::get_css_for_display($options, $css_id, $css_ul, $css_li,
        $css_li_current, $css_li_parent, $css_li_parent_active) .
        "</div>" .

        "<div id='themetab' ".(($active_tab == 'tab-3') ? '' : "style='display:none;'") .">" .
        NAVTAJX::get_theme_for_display($options, $theme_xpath, $theme_option,
        $before_group, $after_group) .
        "</div>" .

        "<div class='option-buttons'>" .
        "<a id='saveonly' href='#' class='bttn' onclick='return(@NS@.group_option_save(0));' " .
        "title='".__('save options', 'navt_domain')."'>".
        __('save', 'navt_domain')."</a>" .

        "<a id='saveclose' href='#' class='bttn' onclick='return(@NS@.group_option_save(1));' ".
        "title='".__('save options and close', 'navt_domain')."'>".
        __('save/close', 'navt_domain') . "</a>" .
        "</div></form>";

        // substitutions
        $html = str_replace('@NS@', 'navt_ns', $html);
        $html = str_replace('@GROUPNAME@', $group, $html);
        $html = str_replace('@SRC@', $navt_blank, $html);
        $html = str_replace('"', "&quot;", $html);

        $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'name' => $name, 'display_name' => $display_name,
        'html' => $html, 'isprivate' => $isprivate_group, 'width' => 605));
        return($rc);
    }

    /**
     * Save group options
     *
     * @param string $group
     * @param array $display
     * @param array $options
     * @param array $page_list
     * @param array $post_list
     * @return string (json)
     * @since .96
     */
    function save_group_options($group, $group_options, $display, $page_list, $post_list, $save_type, $active_tab) {

        navt_write_debug(NAVT_AJX, sprintf("%s::%s group %s\n", __CLASS__, __FUNCTION__, $group));
        navt_write_debug(NAVT_AJX, sprintf("%s::%s group_options\n", __CLASS__,__FUNCTION__), $group_options);
        navt_write_debug(NAVT_AJX, sprintf("%s::%s display_options\n", __CLASS__, __FUNCTION__), $display);
        navt_write_debug(NAVT_AJX, sprintf("%s::%s pages\n", __CLASS__, __FUNCTION__), $page_list);
        navt_write_debug(NAVT_AJX, sprintf("%s::%s posts\n", __CLASS__, __FUNCTION__), $post_list);

        global $gcfg, $icfg;
        $name = $group;

        if( isset($group) ) {
            if( $group != $group_options['name'] ) {
                $rc = NAVTAJX::rename_group($group, $group_options['name']);
                navt_write_debug(NAVT_AJX, sprintf("%s::%s rc %s\n", __CLASS__, __FUNCTION__, $rc));
                if((strstr($rc, 'error')) !== false ) {
                    $err = $rc;
                }
                else {
                    // refresh these
                    $name = $group_options['name'];
                    $icfg = NAVT::get_option(ICONFIG);
                    $gcfg = NAVT::get_option(GCONFIG);
                    navt_write_debug(NAVT_AJX, sprintf("%s::%s group was renamed %s\n", __CLASS__, __FUNCTION__, $name));
                }
            }
        }

        if( !isset($err) && count($group_options) > 0 ) {

            $n_gcfg = $gcfg;
            $isprivate_group = 0;
            $opts = intval($n_gcfg[$group]['options'],10) & 0xffff;
            $lock_state = (($opts & ISLOCKED) ? ISLOCKED: 0);
            $new_name = $name;
            $n_gcfg[$name]['display']['show_on'] = 0;
            $n_gcfg[$name]['options'] = $lock_state;
            $n_gcfg[$name]['css']['ul'] = $n_gcfg[$name]['css']['li'] =
            $n_gcfg[$name]['css']['ulid'] = $n_gcfg[$name]['css']['licurrent'] =
            $n_gcfg[$name]['css']['liparent'] = $n_gcfg[$name]['css']['liparent_active'] = '';
            $n_gcfg[$name]['display']['posts']['ids'] = $n_gcfg[$name]['display']['pages']['ids'] = array();

            foreach($group_options as $key => $value) {

                switch( $key ) {

                    case 'style_selection': {
                        $n_gcfg[$name]['options'] |= (intval($value, 10) & 0xffff);
                        break;
                    }

                    case 'add_user_classes': {
                        $n_gcfg[$name]['options'] |= USE_USER_CLASSES;
                        break;
                    }

                    case 'is_private': {
                        $n_gcfg[$name]['options'] |= ISPRIVATE;
                        $isprivate_group = 1;
                        break;
                    }

                    case 'use_select': {
                        $n_gcfg[$name]['options'] |= HAS_DD_OPTION;
                        break;
                    }

                    case 'select_size': {
                        $n_gcfg[$name]['select_size'] = (intval($value, 10) & 0xffff);
                        break;
                    }

                    case 'show_breadcrumbs' : {
                        $n_gcfg[$name]['options'] |= ADD_BREADCRUMBS;
                        break;
                    }

                    case 'set_on_posts': {
                        $ids = array();
                        $n_gcfg[$name]['display']['show_on'] |= SET_ON_POSTS;
                        $v = $display['posts']['on_selected'];
                        $n_gcfg[$name]['display']['posts']['on_selected'] = $v;
                        $n_gcfg[$name]['display']['show_on'] |= ( ( $v == 'show' ) ? SHOW_ON_POSTS: HIDE_ON_POSTS);

                        if( count($post_list) > 0 ) {
                            foreach( $post_list as $id ) {
                                $ids[$id] = $v;
                            }
                            $n_gcfg[$name]['display']['posts']['ids'] = $ids;
                        }
                        else {
                            // no posts were specified - default is to show on all posts
                            // turn off SET_ON_POSTS bit.
                            $opt = (intval($n_gcfg[$name]['display']['show_on'], 10) & 0xffff);
                            $opt -= SET_ON_POSTS;
                            if( $opt & HIDE_ON_POSTS ) { $opt -= HIDE_ON_POSTS; }
                            $opt |= SHOW_ON_POSTS;
                            $n_gcfg[$name]['display']['show_on'] = $opt;
                            $n_gcfg[$name]['display']['posts']['on_selected'] = 'show';
                            navt_write_debug(NAVT_AJX, sprintf("%s::%s turning off set on posts\n", __CLASS__, __FUNCTION__));
                        }
                        break;
                    }

                    case 'set_on_pages': {
                        $ids = array();
                        $n_gcfg[$name]['display']['show_on'] |= SET_ON_PAGES;
                        $v = $display['pages']['on_selected'];
                        $n_gcfg[$name]['display']['pages']['on_selected'] = $v;
                        $n_gcfg[$name]['display']['show_on'] |= ( ( $v == 'show' ) ? SHOW_ON_PAGES: HIDE_ON_PAGES);

                        if( count($page_list) > 0 ) {
                            foreach( $page_list as $id ) {
                                $ids[$id] = $v;
                            }
                            $n_gcfg[$name]['display']['pages']['ids'] = $ids;
                        }
                        else {
                            // no pages were specified - default is to show on all pages
                            // turn off SET_ON_PAGES bit.
                            $opt = (intval($n_gcfg[$name]['display']['show_on'], 10) & 0xffff);
                            $opt -= SET_ON_PAGES;
                            if( $opt & HIDE_ON_PAGES ) { $opt -= HIDE_ON_PAGES; }
                            $opt |= SHOW_ON_PAGES;
                            $n_gcfg[$name]['display']['show_on'] = $opt;
                            $n_gcfg[$name]['display']['pages']['on_selected'] = 'show';
                            navt_write_debug(NAVT_AJX, sprintf("%s::%s turning off set on pages\n", __CLASS__, __FUNCTION__));
                        }
                        break;
                    }

                    case 'fold_pages': {
                        $n_gcfg[$name]['options'] |= PAGE_FOLDING;
                        break;
                    }

                    case 'add_page_return': {
                        $n_gcfg[$name]['options'] |= ADD_PAGE_RETURN;
                        break;
                    }

                    case 'ulselector': {
                        $ulid = strip_tags($value);
                        $ulid = stripslashes($ulid);
                        $n_gcfg[$name]['css']['ulid'] = $ulid;
                        break;
                    }

                    case 'licurrent': {
                        $li_current = strip_tags($value);
                        $li_current = stripslashes($li_current);
                        $n_gcfg[$name]['css']['licurrent'] = $li_current;
                        break;
                    }

                    case 'liparent': {
                        $li_parent = strip_tags($value);
                        $li_parent = stripslashes($li_parent);
                        $n_gcfg[$name]['css']['liparent'] = $li_parent;
                        break;
                    }

                    case 'liparent_active': {
                        $li_parent_active = strip_tags($value);
                        $li_parent_active = stripslashes($li_parent_active);
                        $n_gcfg[$name]['css']['liparent_active'] = $li_parent_active;
                        break;
                    }

                    case 'ulclass': {
                        $ulclass = strip_tags($value);
                        $ulclass = stripslashes($ulclass);
                        $n_gcfg[$name]['css']['ul']= $ulclass;
                        break;
                    }

                    case 'liclass': {
                        $liclass = strip_tags($value);
                        $liclass = stripslashes($liclass);
                        $n_gcfg[$name]['css']['li'] = $liclass;
                        break;
                    }

                    case 'theme_xpath': {
                        $n_gcfg[$name]['options'] |= HAS_XPATH;
                        $v = strip_tags($value);
                        $v = stripslashes($v);
                        $n_gcfg[$name]['selector']['xpath'] = $v;
                        break;
                    }

                    case 'selector_action': {
                        $n_gcfg[$name]['selector']['option'] = (intval($value, 10) & 0xffff);
                        break;
                    }

                    case 'before_group': {
                        $n_gcfg[$name]['selector']['before'] = html_entity_decode($value, ENT_QUOTES);
                        break;
                    }

                    case 'after_group': {
                        $n_gcfg[$name]['selector']['after'] = html_entity_decode($value, ENT_QUOTES);
                        break;
                    }

                }// end switch
            }


            if( !($n_gcfg[$name]['options'] & HAS_XPATH) ) {
                $n_gcfg[$name]['selector']['xpath'] =
                $n_gcfg[$name]['selector']['before'] =
                $n_gcfg[$name]['selector']['after'] = '';
            }

            $opt = (intval($n_gcfg[$name]['display']['show_on'], 10) & 0xffff );
            if( !($opt & SET_ON_PAGES) ) {$opt |= SHOW_ON_PAGES;}
            if( !($opt & SET_ON_POSTS) ) {$opt |= SHOW_ON_POSTS;}
            $n_gcfg[$name]['display']['show_on'] = $opt;

            if( !($n_gcfg[$name]['options'] & HAS_DD_OPTION) ) {
                $n_gcfg[$name]['select_size'] = 1;
            }

            if( count($display) > 0 ) {
                if( isset($display['other']) ) {
                    foreach( $display['other'] as $key => $value ) {
                        if( $key == 'home' )        $n_gcfg[$name]['display']['show_on'] |= SHOW_ON_HOME;
                        if( $key == 'search' )      $n_gcfg[$name]['display']['show_on'] |= SHOW_ON_SEARCH;
                        if( $key == 'error' )       $n_gcfg[$name]['display']['show_on'] |= SHOW_ON_ERROR;
                        if( $key == 'archive' )     $n_gcfg[$name]['display']['show_on'] |= SHOW_ON_ARCHIVES;
                        if( $key == 'posts_page')   $n_gcfg[$name]['display']['show_on'] |= SHOW_ON_PPAGE;
                        if( $key == 'static_page')  $n_gcfg[$name]['display']['show_on'] |= SHOW_ON_SFP;
                    }
                }
            }
        }

        if( !isset($err) ) {
            // update the configuration/send back an updated window
            NAVT::update_option(GCONFIG, $n_gcfg);
            $gcfg = NAVT::get_option(GCONFIG); // refresh this

            if( $save_type == 0 ) { // not closing
                // get the updated options html
                $rc = NAVTAJX::get_group_options($name, $active_tab);
            }
            else {
                // box will be closing
                // send back an ok with the [new?] name

                $display_name = NAVT::truncate($name, MAX_GROUP_NAME);
                $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'name' => $name,
                'display_name' => $display_name, 'isprivate' => $isprivate_group));
            }
        }

        navt_write_debug(NAVT_AJX, sprintf("%s::%s group settings\n", __CLASS__, __FUNCTION__), $n_gcfg[$name]);
        return($rc);
    }

    /**
     * Return the list of posts for the group display settings
     *
     * @param array $display
     * @return string (html)
     * @since .96
     */
    function get_posts_for_display($display) {
        global $post, $checkit, $selected;
        $html = '';
        $show_on_posts = ((intval($display['show_on'],10) & SET_ON_POSTS) ? 1: 0);
        $on_selected = ((intval($display['show_on'],10) & SHOW_ON_POSTS) ? 'show':'hide');

        // get published posts from the database
        $posts = get_posts(array('numberposts' => -1));

        //navt_write_debug(NAVT_AJX, sprintf("%s::%s \n", __CLASS__, __FUNCTION__), $posts);

        if($posts) {
            $html .=
            "<div id='post-options'>" .
            "<fieldset><legend>".
            "<input type='checkbox' value='1' class='optckbox cbox' id='set-on-posts' name='group_options[set_on_posts]' " .
            ($show_on_posts ? $checkit: '') . "/> ".
            __('Settings for available posts', 'navt_domain')." </legend> " .
            "<p class='show-hide @DISABLED_CLASS@'><input class='rb' type='radio' id='show-on-select-post' ".
            "name='display[posts][on_selected]' value='show' " .
            ($on_selected == 'show' ? $checkit: '') ."@DISABLED@/> " .
            __("Display this navigation group only on selected posts", 'navt_domain') .
            "<br />" . "<input class='rb' type='radio' id='show-on-unselect-post' name='display[posts][on_selected]' value='hide' " .
            ($on_selected == 'hide' ? $checkit: '')." @DISABLED@/> " .
            __("Do not display this navigation group on selected posts", 'navt_domain') .
            "</p>" .
            "<select id='post-ids' class='post-selectbox-list @DISABLED_CLASS@' size='5' multiple='multiple' @DISABLED@>";
            foreach($posts as $post) {
                $html .=
                "<option value='$post->ID' " .((isset($display['posts']['ids'][$post->ID])) ? $selected : '') . ">" .
                "&bull; " . NAVT::truncate($post->post_title, 95) . "</option>";
            }
            $html .= "</select></fieldset></div>";
            $html = str_replace('@DISABLED@', (($show_on_posts) ? '': "disabled='disabled'"), $html);
            $html = str_replace('@DISABLED_CLASS@', (($show_on_posts) ? '': "disabled"), $html);
            $html = str_replace('"', '&quot;', $html);
        }
        return($html);
    }

    /**
     * Return the list of pages for the group display settings
     *
     * @param array $display
     * @return string (html)
     * @since .96
     */
    function get_pages_for_display($display) {
        global $wpdb, $post, $checkit, $selected;
        $html = '';
        $show_on_pages = (intval($display['show_on'],10) & SET_ON_PAGES ? 1: 0);
        $on_selected = ((intval($display['show_on'],10) & SHOW_ON_PAGES) ? 'show':'hide');

        // get published pages from the database
        $pages = get_pages();

        //navt_write_debug(NAVT_AJX, sprintf("%s::%s \n", __CLASS__, __FUNCTION__), $posts);

        if($pages) {
            $html .=
            "<div id='page-options'>" .
            "<fieldset><legend>".
            "<input type='checkbox' value='1' class='optckbox cbox' id='set-on-pages' name='group_options[set_on_pages]'".
            ($show_on_pages ? $checkit:'') . "/> ".
            __('Settings for available pages', 'navt_domain')." </legend> " .
            "<p class='show-hide @DISABLED_CLASS@'><input class='rb' type='radio' id='show-on-select-page' name='display[pages][on_selected]' value='show' " .
            ($on_selected == 'show' ? $checkit: '') ."@DISABLED@/> " .
            __("Display this navigation group only on selected pages", 'navt_domain') .
            "<br /><input class='rb' type='radio' id='show-on-unselect-page' name='display[pages][on_selected]' value='hide' " .
            ($on_selected == 'hide' ? $checkit: '')." @DISABLED@/> " .
            __("Do not display this navigation group on selected pages", 'navt_domain') .
            "</p><select id='page-ids' class='page-selectbox-list @DISABLED_CLASS@' size='5' multiple='multiple' @DISABLED@>";

            foreach($pages as $post) {
                $html .=
                "<option value='$post->ID' " .((isset($display['pages']['ids'][$post->ID])) ? $selected : '') . ">" .
                "&bull; " . NAVT::truncate($post->post_title, 95) . "</option>";
            }

            $html .= "</select></fieldset></div>";
            $html = str_replace('@DISABLED@', (($show_on_pages) ? '': "disabled='disabled'"), $html);
            $html = str_replace('@DISABLED_CLASS@', (($show_on_pages) ? '': "disabled"), $html);
            $html = str_replace('"', '&quot;', $html);
        }
        return($html);
    }

    /**
     * Returns group css for option window
     *
     * @param integer $options
     * @return string (html)
     * @since .96
     */
    function get_css_for_display($options, $css_id, $css_ul, $css_li, $css_li_current, $css_li_parent, $css_li_parent_active) {

        global $checkit, $selected;
        $has_no_style = (($options & HAS_NOSTYLE) ? $checkit:'');
        $use_wp_defaults = (($options & USE_WP_DEFAULTS) ? $checkit:'');
        $use_navt_classes = (($options & USE_NAVT_DEFAULTS) ? $checkit:'');
        $add_user_classes = (($options & USE_USER_CLASSES) ? $checkit:'');

        $html =

        "<fieldset><legend>".__('Group CSS Class Selection', 'navt_domain')."</legend>" .
        "<ul>" .

        "<li><input class='rb' type='radio' name='group_options[style_selection]' value='".USE_NAVT_DEFAULTS."' $use_navt_classes /> " .
        __('Apply NAVT CSS classes', 'navt_domain') . "</li>" .
        "<li><input class='rb' type='radio' name='group_options[style_selection]' value='".USE_WP_DEFAULTS."' $use_wp_defaults /> " .
        __('Apply WordPress default CSS classes', 'navt_domain') . "</li>" .
        "<li><input class='rb' type='radio' name='group_options[style_selection]' value='".HAS_NOSTYLE."' $has_no_style /> " .
        __('Do not apply CSS classes', 'navt_domain') . "</li>" .
        "<li><input id='add_user_classes' class='cbox' type='checkbox' name='group_options[add_user_classes]' value='1' $add_user_classes /> " .
        __('Apply the CSS information below to this navigation group:', 'navt_domain') .

        "<ul>" .
        "<li class='indent'><span>" . __('UL tag selector id', 'navt_domain') . ": <span class='blue'>&lt;ul id= " .
        "<input id='option-ul-id' class='tbox' name='group_options[ulselector]' type='text' value='$css_id' size='20' maxlength='".
        MAX_LEN."' /> &gt;...</span></span></li>" .

        "<li class='indent'><span>" . __('UL tag CSS class', 'navt_domain') . ": <span class='blue'>&lt;ul class= " .
        "<input id='option-ul-class' class='tbox' name='group_options[ulclass]' type='text' value='$css_ul' size='20' maxlength='".
        MAX_LEN."' /> &gt;...</span></span></li>" .

        "<li class='indent'><span>" . __('LI tag CSS class', 'navt_domain') . ": <span class='blue'>&lt;li class= " .
        "<input id='option-li-class' class='tbox' name='group_options[liclass]' type='text' value='$css_li' size='20' maxlength='".
        MAX_LEN."' /> &gt;...</span></span></li>" .

        "<li class='indent'><span>" . __('LI active page CSS class', 'navt_domain') . ": <span class='blue'>&lt;li class= " .
        "<input id='option-li-class-current' class='tbox' name='group_options[licurrent]' type='text' value='$css_li_current' size='20' maxlength='".
        MAX_LEN."' /> &gt;...</span></span></li>" .

        "<li class='indent'><span>" . __('LI tag parent container', 'navt_domain') . ": <span class='blue'>&lt;li class= " .
        "<input id='option-li-class-parent' class='tbox' name='group_options[liparent]' type='text' value='$css_li_parent' size='20' maxlength='".
        MAX_LEN."' /> &gt;&lt;ul&gt;&lt;li&gt;...</span></span></li>" .

        "<li class='indent'><span>" . __('LI tag parent container (active)', 'navt_domain') . ": <span class='blue'>&lt;li class= " .
        "<input id='option-li-class-parent-active' class='tbox' name='group_options[liparent_active]' type='text' ".
        "value='$css_li_parent_active' size='20' maxlength='".MAX_LEN."' /> &gt;&lt;ul&gt;&lt;li&gt;...</span></span></li>" .
        "</ul></li></ul>".
        "</fieldset>";

        $html = str_replace('"', "&quot;", $html);
        return($html);
    }

    /**
     * Returns group options for option window
     *
     * @param integer $options
     * @return string (html)
     * @since .96
     */
    function get_theme_for_display($options, $theme_xpath, $theme_option, $before_group, $after_group) {

        global $checkit, $selected;

        $theme_insert_before = (($theme_option == INS_BEFORE) ? $selected:'');
        $theme_insert_after = (($theme_option == INS_AFTER) ? $selected:'');
        $theme_at_top = (($theme_option == INS_AT_TOP) ? $selected:'');
        $theme_at_bottom = (($theme_option == INS_AT_BOTTOM) ? $selected:'');
        $theme_replace_with = (($theme_option == REPLACE_WITH) ? $selected:'');

        $html =
        "<div><fieldset><legend>".__('Theme Integration', 'navt_domain')."</legend>" .
        "<span>".__('Theme xpath', 'navt_domain').": </span> " .
        "<input class='tbox' type='text' name='group_options[theme_xpath]' value='$theme_xpath' size='15' maxlength='".MAX_LEN."' />".

        "<span> ".__('Action', 'navt_domain').": </span>".
        "<select id='selector-action' name='group_options[selector_action]'>".
        "<option value='" .INS_BEFORE ."' $theme_insert_before >".__('Insert Before', 'navt_domain')."</option>".
        "<option value='" .INS_AFTER ."' $theme_insert_after >".__('Insert After', 'navt_domain')."</option>".
        "<option value='" .INS_AT_TOP ."' $theme_at_top >".__('Insert at top', 'navt_domain')."</option>".
        "<option value='" .INS_AT_BOTTOM ."' $theme_at_bottom >".__('Insert at bottom', 'navt_domain')."</option>".
        "<option value='" .REPLACE_WITH ."' $theme_replace_with >".__('Replace With', 'navt_domain')."</option>".
        "</select>".

        "<p>".__('Add tags before navigation group', 'navt_domain').": ".
        "<input class='tbox' type='text' name='group_options[before_group]' value='".
        htmlentities($before_group)."' size='65' maxlength='".MAX_LEN."' />".
        "</p><p>".__('Add tags after navigation group', 'navt_domain').": <br />".
        "<input class='tbox' type='text' name='group_options[after_group]' value='".
        htmlentities($after_group)."' size='40' maxlength='".MAX_LEN."' />".
        "</p></div>";

        $html = str_replace('"', "&quot;", $html);

        return($html);
    }


    /**
     * Returns group options for option window
     *
     * @param integer $options
     * @return string (html)
     * @since .96
     */
    function get_options_for_display($options, $select_size) {

        global $checkit, $selected;

        $is_select = (($options & HAS_DD_OPTION) ? $checkit:'');
        $is_private = (($options & ISPRIVATE) ? $checkit:'');
        $is_page_fold = (($options & PAGE_FOLDING) ? $checkit:'');
        $has_page_return = (($options & ADD_PAGE_RETURN) ? $checkit:'');
        $show_breadcrumbs = (($options & ADD_BREADCRUMBS) ? $checkit:'');

        $html = "<div class='groupname'>".__('Group Name', 'navt_domain') .": ".
        "<input id='option-name' class='tbox' name='group_options[name]' type='text' value='@GROUPNAME@' ". "maxlength='".MAX_LEN."' size='15' />".
        "<div class='rename-errormsg'></div></div>" .
        "<ul>" .

        "<li><input id='option-fold' class='cbox' name='group_options[fold_pages]' type='checkbox' $is_page_fold value='1' /> " .
        __('Show only top-level anchors on page (hide hierarchy)', 'navt_domain') . '&nbsp;&nbsp;' .
        "<input id='option-page-return' class='cbox' name='group_options[add_page_return]' type='checkbox' $has_page_return value='1' /> " .
        "<span id='option-page-return-label'>" . __('Add page return to list', 'navt_domain') . "</span>" .
        "</li>" .

        "<li><input id='show-breadcrumbs' class='cbox' name='group_options[show_breadcrumbs]' type='checkbox' $show_breadcrumbs value='1' /> " .
        __('Show breadcrumb navigation', 'navt_domain') . "</li>" .

        "<li><input id='option-select' class='cbox' name='group_options[use_select]' type='checkbox' $is_select value='1' /> " .
        __('Create navigation list using HTML select.', 'navt_domain') .
        "<p id='select-size-label-1' class='label'> " . __('Display', 'navt_domain') .": " .
        "<input id='select-size' class='tbox' name='group_options[select_size]' type='text' size='2' value='$select_size'/></p> " .
        "<p id='select-size-label-2' class='label'>".__('item(s) in list', 'navt_domain') . "</p><br/>" .
        "</li>" .

        "<li><input id='option-private' class='cbox' name='group_options[is_private]' type='checkbox' $is_private value='1' /> ".
        __('Set navigation group to private (visible only when users are signed in)', 'navt_domain')."</li>" .

        "</ul>";

        $html = str_replace('"', "&quot;", $html);

        return($html);
    }

    /**
     * Returns 'show on' options beyond pages/posts
     *
     * @param array $display
     * @return string (html)
     * @since .96
     */
    function get_other_for_display($display) {
        global $checkit;

        $show_on = array();
        $show_on['archives'] = intval($display['show_on'], 10) & SHOW_ON_ARCHIVES;
        $show_on['search'] = intval($display['show_on'], 10) & SHOW_ON_SEARCH;
        $show_on['home'] = intval($display['show_on'], 10) & SHOW_ON_HOME;
        $show_on['error'] = intval($display['show_on'], 10) & SHOW_ON_ERROR;
        $show_on['static_page'] = intval($display['show_on'], 10) & SHOW_ON_SFP;

        $posts_page = $static_front_page = "disabled='disabled'";
        if((get_option('show_on_front')) == 'page' ) {
            $static_front_page = '';
        }

        $html =
        "<div id='other-options'>" .
        "<fieldset><legend>".__('Show navigation group on', 'navt_domain')."... </legend><ul>" .
        "<li><input type='checkbox' class='cbox' id='show-on-home' name='display[other][home]' value='1' ".
        ($show_on['home'] ? $checkit: '') . "/> ".__('Home Page', 'navt_domain')."</li>" .

        "<li><input type='checkbox' class='cbox' id='show-on-sfp' name='display[other][static_page]' value='1' ".
        ($show_on['static_page'] ? $checkit: '') . " $static_front_page /> ".__('Static Front Page', 'navt_domain')."</li>" .

        "<li><input type='checkbox' class='cbox' id='show-on-archive' name='display[other][archive]' value='1' ".
        ($show_on['archives'] ? $checkit: '') . "/> ".__('Archive Pages', 'navt_domain')."</li>" .

        "<li><input type='checkbox' class='cbox' id='show-on-search' name='display[other][search]' value='1' ".
        ($show_on['search'] ? $checkit: '') . "/> ".__('Search Pages', 'navt_domain')."</li>" .

        "<li><input type='checkbox' class='cbox' id='show-on-error' name='display[other][error]' value='1' ".
        ($show_on['error'] ? $checkit: '') . "/> ".__('Error Pages', 'navt_domain')."</li>" .
        "</ul></fieldset></div>";

        $html = str_replace('"', "&quot;", $html);
        return($html);
    }

    /**
     * Returns help information for items
     *
     * @param string $id - selector id
     * @return string json
     */
    function item_help($id, $isIE) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s, isIE: %s\n", __CLASS__, __FUNCTION__, $id, $isIE));

        $subjects = array(
        TYPE_CAT => 'category-item',
        TYPE_PAGE => 'page-item',
        TYPE_AUTHOR => 'author-item',
        TYPE_ELINK => 'elink-item',
        TYPE_SEP => 'divider-item',
        'homepage' => 'home-item',
        'signin' => 'signin-item'
        );

        $item = NAVTAJX::get_item($id);

        if( $item[TYP] == TYPE_LINK && $item[IDN] == HOMEIDN ) {
            $typ = 'homepage';
        }
        elseif( $item[TYP] == TYPE_LINK && $item[IDN] == LOGINIDN ) {
            $typ = 'signin';
        }
        else {
            $typ = $item[TYP];
        }

        return( NAVTAJX::help($subjects[$typ], 0, $isIE) );
    }

    /**
     * Returns the configuration options for an item based on its type
     *
     * @param string $id
     * @return string (json)
     * @since .96
     */
    function get_item_options($id) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s\n", __CLASS__, __FUNCTION__, $id));

        global $navt_blank, $checkit, $selected;
        $text = __('item not found', 'navt_domain');

        $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'not_found', 'msg' => $text));
        $configured_ids = NAVTAJX::get_configured_items();
        $item = $configured_ids[$id];
        navt_write_debug(NAVT_AJX, sprintf("%s::%s item %s configuration:\n", __CLASS__, __FUNCTION__, $id), $item);

        if(is_array($item)) {
            navt_write_debug(NAVT_AJX, sprintf("%s::%s options for:\n", __CLASS__, __FUNCTION__), $item);

            $opts = intval($item[OPT], 10) & 0xffff;
            $is_stan = (($opts & (TEXT_OVER_GRAPHIC+TEXT_WITH_SIDE_GRAPHIC+GRAPHIC_LINK)) ? '': $selected);
            $is_tog =  (($opts & TEXT_OVER_GRAPHIC) ? $selected: '');
            $is_twsg = (($opts & TEXT_WITH_SIDE_GRAPHIC) ? $selected: '');
            $is_gl =   (($opts & GRAPHIC_LINK) ? $selected: '');
            $is_home = (TYPE_LINK == $item[TYP] && HOMEIDN == $item[IDN]) ? 1: 0;
            $is_author = ((TYPE_AUTHOR == $item[TYP]) ? 1: 0);
            $is_private = (($opts & ISPRIVATE)  ? $checkit:'');
            $add_nofollow = (($opts & NOFOLLOW) ? $checkit:'');

            // common stuff
            $ttl = (( TYPE_ELINK == $item[TYP] ) ? __('User defined URI', 'navt_domain') : $item[TTL]);

            $html =
            "<form id='@ID@-form'>" .
            "<p class='center item-title'>$ttl</p> " .
            "<div class='item-option-wrapper'>" .
            "<p id='@ID@-aliasbox-label' class='label'>" . __('Menu item alias', 'navt_domain').":</p>" .
            "<input type='text' id='@ID@-aliasbox' class='tbox' name='option[alias]' size='24' maxlength='".
            MAX_LEN."' value='@ALIAS@'/>";

            if( !$is_author ) {
                $html .=
                "<p>" . __('Anchor type', 'navt_domain') .
                ":</p><select class='selects' id='@ID@-anchor-type' name='option[anchortype]' class='anchor-type'> " .
                "<option value='".STANDARD_LINK."' $is_stan>" . __('Standard text link', 'navt_domain')."</option>" .
                "<option value='".TEXT_OVER_GRAPHIC."' $is_tog>" . __('Text over graphic', 'navt_domain')."</option> " .
                "<option value='".TEXT_WITH_SIDE_GRAPHIC."' $is_twsg>" . __('Text with side graphic', 'navt_domain')."</option> " .
                "<option value='".GRAPHIC_LINK."' $is_gl >" . __('Graphic link', 'navt_domain')."</option></select><br />" .
                "<p id='@ID@-label-anchor-class' class='label'>" . __('CSS class for graphic', 'navt_domain').":</p>" .
                "<input type='text' id='@ID@-anchor-class' class='tbox' name='option[anchorclass]' size='26' maxlength='".
                MAX_LEN."' value='@USERCLASS@'/>";
            }

            if( $item[TYP] == TYPE_PAGE ) {
                // nothing special here
            }

            elseif($item[TYP] == TYPE_CAT) {

                $show_in_list = (($opts & SHOW_IN_LIST) ? $checkit:'');
                $append_post_count = (($opts & APPEND_POST_COUNT) ? $checkit:'');
                $show_if_empty = (($opts & SHOW_IF_EMPTY) ? $checkit:'');
                $use_desc_as_tip = (($opts & USE_CAT_DESC) ? $checkit:'');

                $html .=
                "<input id='@ID@-cb1-com' name='option[show_in_wp_cat_list]' class='cbox' value='1' type='checkbox' $show_in_list/> " .
                "<p class='label'>". __('show in WP category list', 'navt_domain')."</p><br />" .
                "<input id='@ID@-cb2-com' name='option[append_cat_post_count]' class='cbox' value='1' type='checkbox' $append_post_count/> " .
                "<p id='@ID@-cb2-com-label' class='label'>" . __('append post count', 'navt_domain')."</p><br /> " .
                "<input id='@ID@-cb3-com' name='option[show_cat_if_empty]' class='cbox' value='1' type='checkbox' $show_if_empty/> " .
                "<p class='label'>".__('show if empty', 'navt_domain'). "</p><br /> " .
                "<input id='@ID@-cb4-com' name='option[use_cat_desc]' class='cbox' value='1' type='checkbox' $use_desc_as_tip/> " .
                "<p class='label'>".__('use description as tooltip', 'navt_domain') . "</p>";
            }

            elseif($item[TYP] == TYPE_AUTHOR) {

                $show_avatar = (($opts & SHOW_AVATAR) ? $checkit:'');
                $use_gravatar = (($opts & USE_GRAVATAR) ? $checkit:'');
                $use_default_avatar = (($opts & USE_DEF_AVATAR) ? $checkit:'');
                $append_post_count = (($opts & APPEND_AUTHOR_POST_COUNT) ? $checkit:'');
                $show_author_if_empty = (($opts & SHOW_AUTHOR_IF_EMPTY) ? $checkit:'');
                $inc_website = (($opts & INC_WEBSITE) ? $checkit:'');
                $inc_bio = (($opts & INC_BIO) ? $checkit:'');
                $inc_email = (($opts & INC_EMAIL) ? $checkit:'');
                $hide_link_text = (($opts & NO_LINK_TEXT) ? $checkit:'');
                $def_av = NAVT::get_url() . '/' . IMG_AVATAR;
                $av = ((isBlank($item[EX2]) ? $def_av: $item[EX2]));
                $av_list = NAVT::build_avatar_list(1, 0, null, $av);

                $html .=
                "<br /><ul><li class='img_avatar'><img src='$av' alt='". __('user avatar', 'navt_domain')."'/></li></ul>" .
                "<input id='@ID@-show-avatar' name='option[show_avatar]' class='cbox' value='1' type='checkbox' $show_avatar/> " .
                "<p class='label'>".__('show avatar', 'navt_domain')."</p><br />" .


                "<input id='@ID@-use-default-avatar' name='option[gravatar]' class='rbox' value='1' type='radio' $use_default_avatar/> " .
                "<p id='@ID@-use-default-avatar-label' class='label'>" . __('use default avatar', 'navt_domain')."</p><br />" .
                "<input id='@ID@-use-gravatar' name='option[gravatar]' class='rbox' value='2' type='radio' $use_gravatar/> " .
                "<p id='@ID@-use-gravatar-label' class='label'>" . __('use gravatar', 'navt_domain')."</p><br />" .
                "<input id='@ID@-use-other-avatar' name='option[gravatar]' class='rbox' value='3' type='radio' $use_users_avatar/> " .
                "<p id='@ID@-use-other-avatar-label' class='label'>" . __('use my avatar', 'navt_domain')."</p><br />" .

                "<p id='@ID@-select-label' class='label'>" . __('Avatar list', 'navt_domain') . ":</p>".
                "<select id='@ID@-select' class='selects' name='option[selected_avatar]' class='anchor-type'>" . $av_list . "</select>" .
                "<p id='@ID@-avbox-label' class='label'>" . __('User avatar URI', 'navt_domain').":</p>" .
                "<input type='text' id='@ID@-avbox' class='tbox' name='option[avatar_path]' size='24' maxlength='".MAXLEN."' value='$av'/><br /> " .
                "<input id='@ID@-show-if-empty' name='option[show_author_if_empty]' class='cbox' value='1' type='checkbox' $show_author_if_empty/> " .
                "<p id='@ID@-show-if-empty-label' class='label'>" . __('show if no posts', 'navt_domain')."</p><br /> " .
                "<input id='@ID@-append-post-count' name='option[append_author_post_count]' class='cbox' value='1' type='checkbox' $append_post_count/> " .
                "<p id='@ID@-append-post-count-label' class='label'>" . __('append author post count', 'navt_domain')."</p><br /> " .
                "<input id='@ID@-inc-website' name='option[inc_website]' class='cbox' value='1' type='checkbox' $inc_website/> " .
                "<p id='@ID@-inc-website-label' class='label'>" . __('include web site', 'navt_domain')."</p><br /> " .
                "<input id='@ID@-inc-bio' name='option[inc_bio]' class='cbox' value='1' type='checkbox' $inc_bio/> " .
                "<p id='@ID@-inc-bio-label' class='label'>" . __('include user bio', 'navt_domain')."</p><br /> " .
                "<input id='@ID@-inc-email' name='option[inc_email]' class='cbox' value='1' type='checkbox' $inc_email/> " .
                "<p id='@ID@-inc-email-label' class='label'>" . __('include user email', 'navt_domain')."</p><br /> " .
                "<input id='@ID@-hide-link-text' name='option[hide_link_text]' class='cbox' value='1' type='checkbox' $hide_link_text/> " .
                "<p id='@ID@-hide-link-text-label' class='label'>" . __('hide link text', 'navt_domain')."</p>";
            }

            elseif($item[TYP] == TYPE_ELINK) {

                $open_in_same_win = (($opts & OPEN_SAMEWIN) ? $checkit:'');
                $v = $item[TTL]; // uri

                $html .=
                "<p>" . __('URI', 'navt_domain') . ":</p>" .
                "<input type='text' id='@ID@-uribox' name='option[uri_path]' class='tbox' size='24' maxlength='".MAX_LEN."' value='$v'/><br /> " .
                "<input id='@ID@-cb1-elink' name='option[open_in_same_window]' class='cbox' value='1' type='checkbox' $open_in_same_win/> " .
                "<p class='label'>".__('open in same window', 'navt_domain')."</p>";
            }

            elseif($item[TYP] == TYPE_LINK) {

                if( $item[IDN] == LOGINIDN ) {
                    $create_login_form = (($opts & USE_FORM) ? $checkit:'');
                    $use_referral_direct = (($opts & REDIRECT_REFER) ? $checkit:'');
                    $use_url_redirect = (($opts & REDIRECT_URL) ? $checkit:'');
                    $v = $item[EX2];

                    $html .=
                    "<input id='@ID@-linktype' type='hidden' value='admin'/>" .
                    "<input id='@ID@-cb1-admin' name='option[create_login_form]' class='cbox' value='1' type='checkbox' $create_login_form/> " .
                    "<p id='@ID@-cb1-admin-label' class='label'>" . __('create sign-in form', 'navt_domain')."</p><br /> " .
                    "<input id='@ID@-cb2-admin' name='option[use_referral_redirect]' class='cbox' value='1' type='checkbox' $use_referral_direct/> " .
                    "<p id='@ID@-cb2-admin-label' class='label'>" . __('use referral page redirect', 'navt_domain')."</p><br /> " .
                    "<input id='@ID@-cb3-admin' name='option[use_url_redirect]' class='cbox' value='1' type='checkbox' $use_url_redirect/> " .
                    "<p id='@ID@-cb3-admin-label' class='label'>" . __('use URL redirect', 'navt_domain')."</p><br /> " .
                    "<p id='@ID@-url-redirect-label' class='label'>" . __('redirect URL', 'navt_domain').":</p>" .
                    "<input type='text' id='@ID@-url-redirect' name='option[url_redirect]' class='tbox' size='24' maxlength='".MAX_LEN."' value='$v'/>";
                }
                elseif( $item[IDN] == HOMEIDN ) {
                    $html .= "<input id='@ID@-linktype' type='hidden' value='home'/>";
                }
            }

            elseif($item[TYP] == TYPE_SEP) {

                $is_plain_text = (($opts & PLAIN_TEXT_OPTION) ? $selected:'');
                $is_hrule = (($opts & HRULE_OPTION) ? $selected:'');
                $is_empty_space = ((isBlank($is_plain_text) && isBlank($is_hrule)) ? $selected : '');

                if( !isBlank($is_hrule) ) {
                    $item[NME] = __('horizontal rule', 'navt_domain');
                }
                elseif( !isBlank($is_empty_space) ) {
                    $item[NME] = __('empty space', 'navt_domain');
                }

                $dv_list =
                "<option value='".PLAIN_TEXT_OPTION."' $is_plain_text>".__("Plain text divider", 'navt_domain')."</option>" .
                "<option value='".HRULE_OPTION."' $is_hrule>".__("Horizontal rule divider", 'navt_domain')."</option>" .
                "<option value='".EMPTY_SPACE_OPTION."' $is_empty_space>".__("Empty space divider", 'navt_domain')."</option>";

                $html .=
                "<p>" . __('Divider type', 'navt_domain') . ":</p>" .
                "<select id='@ID@-divider-select' class='selects' name='option[selected_divider]' class='divider-type'>" . $dv_list . "</select>";
            }

            if(!$is_home) {
                $html .=
                "<br /><input id='@ID@-cb5-com' name='option[private]' class='cbox' value='1' type='checkbox' $is_private /> " .
                "<p class='label'>" . __('set to private', 'navt_domain') . "</p>";
            }

            if($item[TYP] != TYPE_SEP) {
                $html .=
                "<br /><input id='@ID@-cb13-com' name='option[nofollow]' class='cbox' value='1' type='checkbox' $add_nofollow /> ".
                "<p class='label'>".__('add', 'navt_domain')." rel='nofollow' </p";
            }

            $html .=
            "<br clear='all'/>".
            "<div class='bwrapper'>".
            "<p id='@ID@-ok' class='okcan-button'><a class='ibtn-ok' href='#'><img src='@SRC@' alt='" .
            __('Ok', 'navt_domain')."' title='" . __('Click to save', 'navt_domain')."' /></a></p> " .
            "<p id='@ID@-can' class='okcan-button'><a class='ibtn-can' href='#'><img src='@SRC@' alt='" .
            __('Cancel', 'navt_domain') ."' title='" . __('Click to cancel and close', 'navt_domain')."' /></a></p>".
            "</div".
            "<p class='errormsg' id='@ID@-errormsg' style='display: none;'></p>" .
            "</div></form> ".

            "<p id='@ID@-help' class='helpitem-button'><a class='ibtn-help' href='#'><img src='@SRC@' alt='" .
            __('Help', 'navt_domain')."' title='" . __('Click for help', 'navt_domain')."' /></a></p> ";

            // substitutions
            $html = str_replace('@ID@', $id, $html);
            $html = str_replace('@ALIAS@', wp_specialchars($item[NME]), $html);
            $html = str_replace('@URI@', $item[EX2], $html);
            $html = str_replace('@USERCLASS@', $item[EXT], $html);
            $html = str_replace('@SRC@', $navt_blank, $html);
            $html = str_replace('"', "&quot;", $html);
            $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'html' => $html));
        }

        navt_write_debug(NAVT_AJX, sprintf("%s::%s rc=%s\n", __CLASS__, __FUNCTION__, $rc));
        return($rc);
    }

    /**
     * Returns the level option for an item
     *
     * @param string $id
     * @return string (json)
     * @since .96
     */
    function set_item_level($id, $direction) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s, direction %s\n", __CLASS__, __FUNCTION__, $id, $direction));

        global $icfg;

        $text = __('item not found', 'navt_domain');
        $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'not_found', 'msg' => $text));
        $configured_ids = NAVTAJX::get_configured_items();
        $item = $configured_ids[$id];

        if( is_array($item) ) {
            $level = intval($item[LVL],10);
            $clevel = 'level-'.$level;
            if( $direction == 'up' ) {
                $level = (($level + 1 > 5 ) ? 0: $level + 1);
            }
            else {
                $level = (($level - 1 < 0) ? 5: $level - 1);
            }
            $nlevel = 'level-'.$level;
            $item[LVL] = $level;
            NAVTAJX::update_item($id, $item);
            $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'id' => $id, 'cl' => $clevel, 'nl' => $nlevel));
        }
        return($rc);
    }

    /**
     * Connects or disconnects an item from the menu
     *
     * @param string $id
     * @param boolean $setting
     * @return string (json)
     * @since .96
     */
    function set_item_disc($id) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s \n", __CLASS__, __FUNCTION__, $id));

        global $icfg;

        $setting = 0;
        $text = __('item not found', 'navt_domain');
        $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'id' => $id, 'type' => 'not_found', 'msg' => $text));
        $configured_ids = NAVTAJX::get_configured_items();
        $item = $configured_ids[$id];
        $alias = $item[NME];
        $group = $item[GRP];
        $force_disc = 0;

        if( is_array($item) ) {
            $opts = intval($item[OPT], 10) & 0xffff;

            if( TYPE_PAGE == $item[TYP] ) {
                $force_disc = (($opts & ISDRAFTPAGE) ? 1: 0);
            }

            if( $opts & DISCONNECTED ) {
                $opts -= DISCONNECTED;
            }
            else {
                $opts += DISCONNECTED;
                $setting = 1;
            }

            if( $force_disc ) {
                if( !($opts & DISCONNECTED) ) {
                    $opts += DISCONNECTED;
                    $setting = 1;
                }
            }
            $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'id' => $id, 'disc' => $setting,
            'group' => $group, 'alias' => $alias));

            $item[OPT] = $opts;
            NAVTAJX::update_item($id, $item);
        }

        return($rc);
    }


    /**
     * Set item options
     *
     * @param string $id
     * @param array $options
     * @return string (json)
     * @since .96
     */
    function set_item_options($id, $options) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s, options: \n", __CLASS__, __FUNCTION__, $id), $options);

        global $icfg;

        $configured_ids = NAVTAJX::get_configured_items();
        $item = $configured_ids[$id];
        $group = $item[GRP];
        $private = $opt = 0;

        if( is_array($item) ) {
            $alias = $item[NME];
            $anchorclass = $avatar_path = $uri_path = $url_redirect = '';

            foreach($options as $option => $value) {
                navt_write_debug(NAVT_AJX, sprintf("%s::%s option: %s, value: %s \n", __CLASS__, __FUNCTION__, $option, $value));

                switch( $option ) {

                    case 'alias': {
                        $alias = NAVT::clean_item_alias($value);
                        if(isBlank($alias)) {
                            $errmsg = __('invalid alias name', 'navt_domain');
                            $errclass = '-aliasbox';
                        }
                        break;
                    }

                    case 'anchorclass': {
                        $anchorclass = strip_tags($value);
                        $anchorclass = stripslashes($anchorclass);
                        break;
                    }

                    case 'avatar_path': {
                        $avatar_path = strip_tags($value);
                        $avatar_path = stripslashes($avatar_path);
                        break;
                    }

                    case 'uri_path': {
                        $uri_path = strip_tags($value);
                        $uri_path = stripslashes($uri_path);
                        break;
                    }

                    case 'selected_avatar': { $default_avatar = $value; break; }
                    case 'anchortype': { $opt |= (intval($value,10) & 0xffff); break; }
                    case 'nofollow': { $opt |= NOFOLLOW; break; }
                    case 'private' : { $opt |= ISPRIVATE; $private = 1; break; }
                    case 'show_in_wp_cat_list': { $opt |= SHOW_IN_LIST; break; }
                    case 'append_author_post_count': { $opt |= APPEND_AUTHOR_POST_COUNT; break; }
                    case 'append_cat_post_count': { $opt |= APPEND_POST_COUNT; break; }
                    case 'show_author_if_empty': { $opt |= SHOW_AUTHOR_IF_EMPTY; break; }
                    case 'show_cat_if_empty': { $opt |= SHOW_IF_EMPTY; break; }
                    case 'use_cat_desc': { $opt |= USE_CAT_DESC; break; }
                    case 'show_avatar': { $opt |= SHOW_AVATAR; break; }
                    case 'gravatar':
                        if( $value == '1' ) $opt |= USE_DEF_AVATAR;
                        if( $value == '2' ) $opt |= USE_GRAVATAR;
                        if( $value == '3' ) $opt |= USE_OTHER_AVATAR;
                        break;
                    case 'inc_website': { $opt |= INC_WEBSITE; break; }
                    case 'inc_bio': { $opt |= INC_BIO; break; }
                    case 'inc_email': { $opt |= INC_EMAIL; break; }
                    case 'hide_link_text': { $opt |= NO_LINK_TEXT; break; }
                    case 'open_in_same_window': { $opt |= OPEN_SAMEWIN; break; }
                    case 'use_referral_redirect': { $opt |= REDIRECT_REFER; break; }
                    case 'use_url_redirect': { $opt |= REDIRECT_URL; break; }
                    case 'create_login_form': { $opt |= USE_FORM; break; }

                    case 'selected_divider' : {
                        $opt |= (intval($value, 10) & 0xffff);
                        if( $opt & HRULE_OPTION ) {
                            $alias = __('horizontal rule', 'navt_domain');
                        }
                        elseif( !($opt & PLAIN_TEXT_OPTION) ) {
                            $alias = __('empty space', 'navt_domain');
                        }
                        break;
                    }

                    case 'url_redirect': {
                        $url_redirect = strip_tags($value);
                        $url_redirect = stripslashes($url_redirect);
                        break;
                    }

                    default: {
                        break;
                    }
                }

                if( isset($errmsg) ) {
                    // option error occurred
                    $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'msg' => $errmsg, 'suffix' => $errclass));
                    break;
                }
            }// end for

            if( !isset($rc) ) {

                if( TYPE_AUTHOR == $item[TYP] ) {
                    if($opt & SHOW_AVATAR) {
                        if( !($opt & USE_DEF_AVATAR) && !($opt & USE_GRAVATAR) ) {
                            if(isBlank($avatar_path) ) {
                                // avatar is missing
                                $t = __('avatar is missing', 'navt_domain');
                                $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'msg' => $t, 'suffix' => '-avbox'));
                            }
                            else {
                                $item[EX2] = $avatar_path;
                            }
                        }
                        else {
                            $item[EX2] = $default_avatar;
                        }
                    }
                    else {
                        $item[EX2] = '';
                    }
                }

                else if( TYPE_ELINK == $item[TYP] ) {
                    if(isBlank($uri_path) ) {
                        // uri is missing
                        $t = __('URI is missing', 'navt_domain');
                        $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'msg' => $t, 'suffix' => '-uribox'));
                    }
                    else {
                        $item[TTL] = $uri_path;
                    }
                }

                else if( TYPE_LINK == $item[TYP] ) {
                    if( LOGINIDN == $item[IDN] ) {
                        if( $opt & REDIRECT_URL ) {
                            if(isBlank($url_redirect) ) {
                                // uri is missing
                                $t = __('URL is missing', 'navt_domain');
                                $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'msg' => $t, 'suffix' => '-url-redirect'));
                            }
                            else {
                                $item[EX2] = $url_redirect;
                            }
                        }
                        else {
                            $item[EX2] = '';
                        }
                    }
                }
            }

            if( TYPE_AUTHOR != $item[TYP] ) {
                if( $opt & (TEXT_WITH_SIDE_GRAPHIC | TEXT_OVER_GRAPHIC | GRAPHIC_LINK ) ) {
                    if(isBlank($anchorclass) ) {
                        $t = __('CSS class is missing', 'navt_domain');
                        $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'msg' => $t, 'suffix' => '-anchor-class'));
                    }
                    else {
                        $item[EXT] = $anchorclass;
                    }
                }
                else {
                    $item[EXT] = '';
                }
            }

            if( !isset($rc) ) {
                $item[NME] = $alias;
                $item[OPT] = $opt;
                $level = intval($item[LVL], 10);
                NAVTAJX::update_item($id, $item);
                $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'alias' => $alias, 'group' => $group,
                'isprivate' => $private, 'level' => $level));
            }
        }
        else {
            // item not found
            $t = __('item not found', 'navt_domain');
            $rc = NAVTAJX::mk_json_str(array('rc' => 'error', 'type' => 'not_found', 'msg' => $t));
        }

        return($rc);
    }

    /**
     * Verify that item option settings are not missing
     *
     * @param string $id
     * @return json string
     * @since .96
     * @internal not implemented
     */
    function verify_item_options($id) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s: \n",__CLASS__, __FUNCTION__, $id));

        $is_valid = 1;
        $configured_ids = NAVTAJX::get_configured_items();
        $item = $configured_ids[$id];

        if( !is_array($item) ) {
            // probably a new item
            $item_id = split('-', $id);
            $item[TYP] = $item_id[1];
            $item[IDN] = $item_id[2];
            $item[OPT] = 0;
            $item[EXT] = $item[EX2] = '';

            if( TYPE_AUTHOR == $item[TYP] ) {
                $item[OPT] |= (SHOW_AVATAR | USE_DEF_AVATAR);
            }

            if( TYPE_ELINK == $item[TYP] ) {
                $item[TTL] = 'http://';
            }
        }

        $opt = intval($item[OPT], 10) & 0xffff;

        if( $opt & TEXT_WITH_SIDE_GRAPHIC ) {
            if( isBlank($item[EXT]) ) {
                navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s twsg [anchor missing]\n",__CLASS__, __FUNCTION__, $id));
                // missing anchor class
                $is_valid = 0;
            }
        }

        if( TYPE_AUTHOR == $item[TYP] ) {
            if($opt & SHOW_AVATAR) {
                if( !($opt & USE_DEF_AVATAR) ) {
                    if( isBlank($item[EXT]) ) {
                        navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s [user avatar missing]\n",__CLASS__, __FUNCTION__, $id));
                        // avatar is missing
                        $is_valid = 0;
                    }
                }
            }
        }

        else if( TYPE_ELINK == $item[TYP] ) {
            if( isBlank($item[TTL]) ) {
                navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s [user uri missing]\n",__CLASS__, __FUNCTION__, $id));
                // uri is missing
                $is_valid = 0;
            }
        }

        else if( TYPE_LINK == $item[TYP] ) {
            if( LOGINIDN == $item[IDN] ) {
                if( $opt & REDIRECT_URL ) {
                    if( isBlank($item[EX2]) ) {
                        navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s [user redirect missing]\n",__CLASS__, __FUNCTION__, $id));
                        // uri is missing
                        $is_valid = 0;
                    }
                }
            }
        }

        $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'verify' => $is_valid));
        return($rc);
    }

    /**
     * Returns a prompt to remove an item
     *
     * @param string $id
     * @return string (json)
     * @since .96
     */
    function ask_remove_item($id) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s\n",__CLASS__, __FUNCTION__, $id));

        global $navt_blank;
        $alias = $group = '';
        $item = NAVTAJX::get_item($id);
        if( !empty($item) ) {
            $alias = $item[NME];
            $group = $item[GRP];
        }
        $title = __('Delete This Item?', 'navt_domain');
        $html =
        "<p>".__("Select Ok to remove", 'navt_domain') ." '$alias' ". __('from the group.', 'navt_domain') . "</p>" .
        "<div class='bwrapper'>".
        "<a href='#' id='@ID@-ok' title=''><div class='okbutton'><p>".__('Ok', 'navt_domain')."</p></div></a>" .
        "<a href='#' id='@ID@-can' title=''><div class='canbutton'><p>".__('Cancel', 'navt_domain')."</p></div></a>" .
        "</div>";

        // substitutions
        $html = str_replace('@ID@', $id, $html);
        $html = str_replace('"', "&quot;", $html);

        $rc = NAVTAJX::mk_json_str(array('rc' => 'ok', 'html' => $html, 'title' => $title,
        'width' => 400, 'height' => 140, 'group' => $group, 'alias' => $alias, 'id' => $id ));

        return($rc);
    }

    /**
     * Update item settings
     *
     * @param string $id
     * @param array $item
     * @since .96
     */
    function update_item($id, $item) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s id %s, item: \n",__CLASS__, __FUNCTION__, $id), $item);

        global $icfg;
        $group = $item[GRP];
        $icfg[$group][$id] = $item;
        NAVT::update_option(ICONFIG, $icfg);
        return;
    }

    /**
     * returns an array of all configured items
     * Each key is an item selector id
     *
     * @return array
     * @since .96
     */
    function get_configured_items() {

        global $icfg;
        $all_ids = array();
        $groups = $icfg;
        if( is_array($groups) ) {
            foreach( $groups as $group => $members ) {
                foreach( $members as $id => $data ) {
                    $all_ids[$id] = $data;
                }
            }
        }

        //navt_write_debug(NAVT_AJX, sprintf("%s::%s all ids\n",__CLASS__, __FUNCTION__), $all_ids);
        return( $all_ids );
    }

    /**
     * remove configured group
     *
     * @param string $target_group
     * @return array - new copy of the configuration
     * @since .96
     */
    function remove_configured_group($target_group) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s group %s\n",__CLASS__, __FUNCTION__, $target_group));

        global $icfg, $gcfg;
        $n_icfg = array();
        $target_group = strtolower($target_group);

        if(is_array($icfg)) {
            foreach( $icfg as $group => $members ) {
                if($target_group != $group ) {
                    $n_icfg[$group] = $members;
                }
            }

            $n_gcfg = array();
            foreach( $gcfg as $key => $group_data ) {
                if( $key != $target_group ) {
                    $n_gcfg[$key] = $group_data;
                }
            }
            $gcfg = $n_gcfg;
            if( count($n_icfg) > 0 ) {
                NAVT::update_option(ICONFIG, $n_icfg);
                NAVT::update_option(GCONFIG, $n_gcfg);
                $icfg = $n_icfg;
                $gcfg = $n_gcfg;
            }
            return;
        }
    }

    /**
     * Insert a item into a group
     *
     * @param string $target_group
     * @param string $target_id
     * @param array $item
     * @return array - new copy of the configuration
     * @since .96
     */
    function insert_configured_item($target_group, $target_id, $item) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s group %s, id %s, item:\n",
        __CLASS__, __FUNCTION__, $target_group, $target_id), $item);

        global $icfg;
        $item[GRP] = $target_group;
        $ngroups = array();
        $groups = $icfg;
        $target_group = strtolower($target_group);

        if(is_array($groups)) {
            foreach( $groups as $group => $members ) {
                if($target_group != $group ) {
                    $ngroups[$group] = $members;
                    continue;
                }
                else {
                    foreach( $members as $id => $member ) {
                        $ngroups[$group][$id] = $groups[$group][$id];
                    }
                    // add the new item to the bottom of this group
                    $ngroups[$target_group][$target_id] = $item;
                }
            }
        }
        return( $ngroups );
    }

    /**
     * remove an item from a group
     *
     * @param string $target_group
     * @param string $target_id
     * @return array - new copy of the configuration
     * @since .96
     */
    function remove_configured_item($target_group, $target_id) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s group %s, id %s\n",
        __CLASS__, __FUNCTION__, $target_group, $target_id));

        global $icfg;
        $ngroups = array();
        $groups = $icfg;
        $target_group = strtolower($target_group);

        if(is_array($groups)) {
            foreach( $groups as $group => $members ) {
                if($target_group == $group ) {
                    $ngroups[$group] = $members;
                    continue;
                }
                else {
                    foreach( $members as $id => $member ) {
                        if( $target_id != $id ) {
                            $ngroups[$group][$id] = $groups[$group][$id];
                        }
                    }
                }
            }
        }
        return( $ngroups );
    }

    /**
     * Sorts page or category by title or menu order
     * and returns the list in the form of a select
     *
     * @param string $type
     * @param string $orderby
     */
    function sort_assets($type, $orderby) {

        $result = 'ok';

        navt_write_debug(NAVT_AJX, sprintf("%s::%s type: %s, orderby: %s\n", __CLASS__, __FUNCTION__, $type, $orderby));

        switch( $type ) {

            case 'page': {
                $html = NAVTAJX::get_page_order($orderby);
                break;
            }

            case 'category' : {
                $html = NAVTAJX::get_cat_order($orderby);
                break;
            }

            case 'user' : {
                $html = NAVTAJX::get_user_order($orderby);
                break;
            }

            default: {
                $result = 'error';
                break;
            }
        }

        return(NAVTAJX::mk_json_str(array('rc' => $result, 'html' => $html)));
    }

    /**
     * Check for the existance of a group name
     *
     * @param string $name
     * @return boolean (1 = name exists)
     * @since .96
     */
    function check_for_duplicate_group($name) {

        global $gcfg;
        $name = strtolower($name);
        return( (array_key_exists($name, $gcfg) ? 1: 0 ) );
    }

    /**
     * Returns a specific item
     *
     * @param string $id
     */
    function get_item( $id ) {

        $configured_ids = NAVTAJX::get_configured_items();
        $item = $configured_ids[$id];
        return($item);
    }

    /**
     * Creates a json message from an array of keys/values
     *
     * @param array $msg_protocol
     * @return string
     * @since .96
     */
    function mk_json_str($msg_protocol) {
        $msg = '{';
        foreach( $msg_protocol as $key => $value ) {
            $msg .= sprintf('"%s":"%s",', $key, $value);
        }
        $msg = preg_replace('/,$/', '}', $msg);
        //navt_write_debug(NAVT_AJX, sprintf("%s::%s msg = %s\n",__CLASS__, __FUNCTION__, $msg));
        return($msg);
    }

    /**
     * returns a list of pages by title or menu order
     *
     * @param string $orderby
     */
    function get_page_order($orderby) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s page orderby %s\n",__CLASS__, __FUNCTION__, $orderby));
        $ar = NAVT::build_assets($orderby, 'default', 'default');

        $html = ''; $c = 0;
        foreach( $ar as $type ) {
            foreach($type as $item) {
                if( $item[TYP] == TYPE_PAGE ) {
                    $html .= NAVT::make_default_asset(0, $item, ($c%2));
                    $c++;
                }
            }
        }
        return($html);
    }

    /**
     * returns a list of categories by title or menu order
     *
     * @param string $orderby
     */
    function get_cat_order($orderby) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s cat orderby %s\n",__CLASS__, __FUNCTION__, $orderby));
        $ar = NAVT::build_assets('default', $orderby, 'default');

        $html = ''; $c = 0;
        foreach( $ar as $type ) {
            foreach($type as $item) {
                if( $item[TYP] == TYPE_CAT ) {
                    $html .= NAVT::make_default_asset(0, $item, ($c%2));
                    $c++;
                }
            }
        }
        return($html);
    }

    /**
     * returns a list of users by nice_name or order
     *
     * @param string $orderby
     */
    function get_user_order($orderby) {
        navt_write_debug(NAVT_AJX, sprintf("%s::%s user orderby %s\n",__CLASS__, __FUNCTION__, $orderby));
        $ar = NAVT::build_assets('default', 'default', $orderby);

        $html = ''; $c = 0;
        foreach( $ar as $type ) {
            foreach($type as $item) {
                if( $item[TYP] == TYPE_AUTHOR ) {
                    $html .= NAVT::make_default_asset(0, $item, ($c%2));
                    $c++;
                }
            }
        }
        return($html);
    }

}// end class
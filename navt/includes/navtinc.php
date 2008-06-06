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
if( !defined('navt_include') ) {
    define('navt_include', 1);
    $dbgfilter = 0;

    /**
     * Bit constants
     *
     */
    define( "BIT00", 0 );
    define( "BIT01", 1 );
    define( "BIT02", 2 );
    define( "BIT03", 4 );
    define( "BIT04", 8 );
    define( "BIT05", 16 );
    define( "BIT06", 32 );
    define( "BIT07", 64 );
    define( "BIT08", 128 );
    define( "BIT09", 256 );
    define( "BIT10", 512 );
    define( "BIT11", 1024 );
    define( "BIT12", 2048 );
    define( "BIT13", 4096 );
    define( "BIT14", 8192 );
    define( "BIT15", 16384 );

    @include('navt_debug.php');    // not shipped with plugin
    if( !function_exists('navt_write_debug') ) { function navt_write_debug($a=0, $b, $c=NULL) {} }

/**
 * Constants
 */
    define('NAVT_MAJVER', 1);
    define('NAVT_MINVER', 0);
    define('NAVT_BUILD',  12);

    define('SCRIPTVERS',   NAVT_MAJVER.'.'.NAVT_MINVER.'.'.NAVT_BUILD);  // version applies to all files
    define('SCRIPTNAME',  'NAVT');    // name of this script
    define('NAVT2', 1);

    define('DESCRIPTION',     'Navigation Tool for WordPress');
    define('SUBMENUTITLE',    'Navigation Tool for WordPress');
    define('PLUGIN_HOMEPAGE', "http://wordpress.org/extend/plugins/wordpress-navigation-list-plugin-navt/");
    define('PLUGIN_FORUM',    "http://www.atalayastudio.com/");

    // category options
    define('SHOW_IN_LIST',      BIT01); // category: show category in category list
    define('APPEND_POST_COUNT', BIT02); // category: append post count to category name
    define('SHOW_IF_EMPTY',     BIT03); // category: display even when empty
    define('USE_CAT_DESC',      BIT04); // category: use category description for link title text

    // login options
    define('USE_FORM',          BIT01); // login - create/use login form
    define('REDIRECT_REFER',    BIT02); // login - redirect to referring page
    define('REDIRECT_URL',      BIT03); // login - redirect to specified URL

    // separator options
    define('EMPTY_SPACE_OPTION',BIT00); // separator: empty space
    define('HRULE_OPTION',      BIT01); // separator: hrule
    define('PLAIN_TEXT_OPTION', BIT02); // separator: plaintext

    // page options
    define('ISDRAFTPAGE',       BIT01); // page: has draft status

    // elink options
    //
    define('RELFOLLOW',         BIT01); // elink: link follow option
    define('OPEN_SAMEWIN',      BIT02); // elink: the target URL will open in the same window

    // Author options
    define('SHOW_AVATAR',                 BIT01);
    define('APPEND_AUTHOR_POST_COUNT',    BIT02);
    define('SHOW_AUTHOR_IF_EMPTY',        BIT03);
    define('USE_DEF_AVATAR',    BIT04); // use the default avatar
    define('INC_WEBSITE',       BIT06); // include the user's website address
    define('INC_BIO',           BIT07); // include the user's bio
    define('INC_EMAIL',         BIT09); // include the user's email address
    define('USE_GRAVATAR',      BIT11); // use gravatar using user's email address
    define('USE_OTHER_AVATAR',  BIT12); // use user's avatar path

    // -- shared bits -- //
    define('ISPRIVATE',         BIT05); // private - user's must be logged in to see
    define('NO_LINK_TEXT',      BIT08); // Link text is not displayed
    define('DISCONNECTED',      BIT10); // item is part of a menu but is temporarily not used
    define('NOFOLLOW',          BIT13); // rel=nofollow for everything other than ELINKS

    /** Link style options **/
    define('STANDARD_LINK',          '0');
    define('TEXT_OVER_GRAPHIC',      BIT07);
    define('GRAPHIC_LINK',           BIT08);
    define('TEXT_WITH_SIDE_GRAPHIC', BIT09);

    /** Group options **/
    define('ISLOCKED',          BIT01);
    define('USE_WP_DEFAULTS',   BIT02);
    define('USE_USER_CLASSES',  BIT03);
    define('PAGE_FOLDING',      BIT04);
    //ISPRIVATE                 BIT05 -    backward compatible
    define('HAS_DD_OPTION',     BIT06); // backward compatible
    define('USE_NAVT_DEFAULTS', BIT07);
    define('ADD_PAGE_RETURN',   BIT08);
    define('ADD_BREADCRUMBS',   BIT09);
    define('MARK_CURRENT_PAGE', BIT10);
    define('HAS_XPATH',         BIT11);
    define('HAS_NOSTYLE',       BIT12); // backward compatible

    // xpath options
    define('INS_BEFORE',        0);
    define('INS_AFTER',         1);
    define('INS_AT_TOP',        2);
    define('INS_AT_BOTTOM',     3);
    define('REPLACE_WITH',      4);

    /** show on... **/
    define('SHOW_ON_HOME',      BIT01);
    define('SHOW_ON_ARCHIVES',  BIT02);
    define('SHOW_ON_SEARCH',    BIT03);
    define('SHOW_ON_ERROR',     BIT04);
    define('SET_ON_POSTS',      BIT05);
    define('SET_ON_PAGES',      BIT06);
    define('SHOW_ON_SFP',       BIT07);
    define('SHOW_ON_PAGES',     BIT08);
    define('SHOW_ON_POSTS',     BIT09);
    define('HIDE_ON_PAGES',     BIT10);
    define('HIDE_ON_POSTS',     BIT11);

/**
 * Options token names
 */
    define('OPTPREFIX'     , 'wpnavt_');
    define('SERIALIZED_MAP', OPTPREFIX.'map');
    define('LASTMODIFIED'  , OPTPREFIX.'last_modified');
    define('SCHEME'        , OPTPREFIX.'scheme');
    define('INSTALLED'     , OPTPREFIX.'installed');
    define('ACTIVATED'     , OPTPREFIX.'active');
    define('VERSIONID'     , OPTPREFIX.'version');
    define('VERCHECK'      , OPTPREFIX.'verchk');
    define('DEF_ADD_GROUP' , OPTPREFIX.'addgrp');
    define('TIPS'          , OPTPREFIX.'tips');
    define('ICONFIG'       , OPTPREFIX.'iconfig');
    define('GCONFIG'       , OPTPREFIX.'gconfig');
    define('ASSETS'        , OPTPREFIX.'assets');

/**
 * Associative array indicies
 */
    define('GRP', 'grp'); // item belongs to this group
    define('TYP', 'typ'); // item type
    define('IDN', 'idn'); // database id
    define('TTL', 'ttl'); // title or url
    define('NME', 'nme'); // alias name
    define('OPT', 'opt'); // item options
    define('EXT', 'ext'); // item extra options
    define('LVL', 'lvl'); // item's hierarchy level
    define('EX2', 'ex2'); // extended options
    define('VER', 'ver');

/**
 * Navigation Item Types
 */
    define('TYPE_PAGE',   '1'); // menu item is a "page"
    define('TYPE_CAT' ,   '2'); // menu item is a "category"
    define('TYPE_LINK',   '3'); // menu item is an "link" (internal)
    define('TYPE_SEP',    '4'); // menu item is a "divider"
    define('TYPE_ELINK',  '5'); // menu item is a "link" (user defined)
    define('TYPE_AUTHOR', '6'); // menu item is an author */

/**
 * Misc
 */
    define('EMPTYSTR',   '');	    // Empty or blank string constant
    define('HOMEIDN',    0); 	    // Identifier for the 'home' menu item
    define('LOGINIDN',   10000);    // Identifier for the 'login/register' link
    define('SEPIDN',     20000);    // Identifier for dividers
    define('ELINKIDN',   30000);    // external link idn

    define('MAX_GROUP_NAME', '10'); // maximum group name
    define('MAX_LEN',       '512'); // max length of textbox input fields

/**
 * Javascript/PHP constants for classes and control names
 * These suffixes and class names identify specific entities
 * in the DOM.
 */
    define('ID_DEFAULT_GROUP', 'UNASSIGNED'); // Depreciated

    define('SEP', '-');
    define('DELIM', '_');
    define('ISFX', 'im');

    // images with paths relative to the plugin css directory
    define('IMG_AVATAR',        'images/default_avatar.jpg');
    define('IMG_BLANK',         'images/blank.gif');
    define('IMG_SPINNER',       'images/spinner.gif');
    define('AVATAR_IMAGES',     '/avatars');

    // Tab types (CSS CLASSES)
    define('PAGEITEM',      'page_item');
    define('CURPAGEITEM',   'current_'.PAGEITEM);
    define('TAB_CATEGORY',  'categorytab');
    define('TAB_PAGE',      'pagetab');
    define('TAB_ADMIN',     'admintab');
    define('TAB_LINK',      'linktab');
    define('TAB_ELINK',     'elinktab');
    define('TAB_HOME',      'hometab');
    define('TAB_AUTHOR',    'authortab');
    define('TAB_SUBHEAD',   'h2tab');
    define('TAB_HRULE',     'hruletab');
    define('TAB_EMPTY',     'emptytab');
    define('CURCATITEM',    'current-cat');
    define('CATITEM',       'cat-item');
    define('RETURN_ANCHOR', 'return-anchor');

    define('MERGE_DISCARD_UNMATCHED',1);
    define('MERGE_CREATE_UNMATCHED', 2);


    global $sel;
    // jQuery functions
    $sel[INS_BEFORE]    = 'before';
    $sel[INS_AFTER]     = 'after';
    $sel[INS_AT_TOP]    = 'prepend';
    $sel[INS_AT_BOTTOM] = 'append';
    $sel[REPLACE_WITH]  = 'replaceWith';

    function q($t) {
        echo $t;
    }
}// end if !defined
?>
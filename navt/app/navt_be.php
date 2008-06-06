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
 * @subpackage navt backend class functions
 * @author Greg A. Bellucci <greg[AT]gbellucci[DOT]us
 * @copyright Copyright &copy; 2006-2008 Greg A. Bellucci
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

class NAVT_BE {

    /**
     * save post callback
     *
     * @param int $post_ID - post id
     * @param array $post - post record array
     */
    function save_post_wpcb($post_ID, $post) {
        navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s postid: %s\n", __CLASS__, __FUNCTION__, $post_ID), $post);

        $post_type = get_post_type($post_ID);

        if( $post_type == 'page' ) {

            $ar = NAVT::get_option(ASSETS);
            if( empty($ar[TYPE_PAGE][$post_ID]) ) {

                // this is a new page
                $item = array(TYP => TYPE_PAGE, IDN => $post_ID, TTL => $post->post_title,
                NME => $post->post_title, OPT => '0', EXT => '', LVL => '0', EX2 => '');

                // add the asset
                NAVT_BE::add_asset(TYPE_PAGE, $post_ID, $item);
            }
            else {
                // this is an update
                $args = array( TTL => $post->post_title, TYP => TYPE_PAGE, IDN => $post_ID);
                NAVT_BE::update_asset($args);
                NAVT_BE::update_item_in_all_groups($args);
            }
        }
    }

    /**
     * delete post callback
     *
     * @param int $post_ID
     */
    function delete_post_wpcb($post_ID) {
        navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s postid: %s\n", __CLASS__, __FUNCTION__, $post_ID));

        $post_type = get_post_type($post_ID);

        if( $post_type == 'page' ) {
            NAVT_BE::remove_from_assets(TYPE_PAGE, $post_ID);
            NAVT_BE::remove_item_from_all_groups(TYPE_PAGE, $post_ID);
        }
    }

    /**
     * new user registration callback
     *
     * @param int $user_id
     */
    function user_register_wpcb($user_id) {
        navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s userid: %s \n", __CLASS__, __FUNCTION__, $user_id));

        $u = get_userdata($user_id);

        if( !empty($u) ) {
            $item = array(TYP => TYPE_AUTHOR, IDN => $user_id, TTL => $u->user_login, NME => $u->display_name,
            OPT => (SHOW_AVATAR | USE_DEF_AVATAR), EXT => NAVT::get_url() . '/' . IMG_AVATAR, LVL => '0', EX2 => '');

            // add the asset
            NAVT_BE::add_asset(TYPE_AUTHOR, $user_id, $item);
        }
    }

    /**
     * user profile update callback
     *
     * @param int $user_id
     */
    function profile_update_wpcb($user_id) {
        //navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s user_id: %s\n", __CLASS__, __FUNCTION__, $user_id));

        $u = get_userdata($user_id);

        if( !empty($u) ) {
            $nme = (isBlank($u->display_name) ? $u->user_login: $u->display_name);
            $args = array(TYP => TYPE_AUTHOR, NME => $nme, IDN => $user_id);
            NAVT_BE::update_asset($args);
            NAVT_BE::update_item_in_all_groups($args);
        }
        else {
            //navt_write_debug(NAVT_WPHOOKS, sprintf("\t%s::%s user not found\n", __CLASS__, __FUNCTION__));
        }
    }

    /**
     * delete user callback
     *
     * @param int $user_id
     */
    function delete_user_wpcb($user_id) {
        //navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s user_id: %s\n", __CLASS__, __FUNCTION__, $user_id));
        NAVT_BE::remove_from_assets(TYPE_AUTHOR, $user_id);
        NAVT_BE::remove_item_from_all_groups(TYPE_AUTHOR, $user_id);
    }

    /**
     * create category callback
     *
     * @param int $term_id
     */
    function created_category_wpcb($term_id) {
        navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s term_id: %s \n", __CLASS__, __FUNCTION__, $term_id));

        $cat = get_category($term_id);
        if( !empty($cat) ) {

            $item = array(TYP => TYPE_CAT, IDN => $term_id, TTL => $cat->cat_name,
            NME => $cat->cat_name, OPT => '0', EXT => '', LVL => '0', EX2 => '');

            // add the asset
            NAVT_BE::add_asset(TYPE_CAT, $term_id, $item);
        }
    }

    /**
     * edit category callback
     *
     * @param int $term_id
     */
    function edited_category_wpcb($term_id) {
        navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s term_id: %s \n", __CLASS__, __FUNCTION__, $term_id));

        $cat = get_category($term_id);
        if( !empty($cat) ) {

            navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s category\n", __CLASS__, __FUNCTION__), $cat);

            $args = array(TYP => TYPE_CAT, IDN => $term_id, TTL => $cat->cat_name);
            NAVT_BE::update_asset($args);
            NAVT_BE::update_item_in_all_groups($args);
        }
    }

    /**
     * delete category callback
     *
     * @param int $term_id
     */
    function delete_category_wpcb($term_id) {
        navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s term_id: %s \n", __CLASS__, __FUNCTION__, $term_id));
        NAVT_BE::remove_from_assets(TYPE_CAT, $term_id);
        NAVT_BE::remove_item_from_all_groups(TYPE_CAT, $term_id);
    }

    /**
     * Append excluded categories to the database exlusions list
     *
     * @param string $exclusions (category AND statements from other plugins)
     * @return string $exclusions (AND statements appended to the existing string)
	 */
    function list_terms_exclusions_wpcb($exclusions) {

        //navt_write_debug(NAVT_WPHOOKS, (sprintf("%s::%s  exclusions:%s \n", __CLASS__, __FUNCTION__, $exclusions)));

        if( isset($type) && ($type == 'category' && !is_admin()) ) {
            // temporarily remove this filter to prevent recursion
            remove_filter('list_terms_exclusions', array('NAVT_BE','list_terms_exclusions_wpcb'));

            // load the map and get the categories to be excluded
            $map_array = NAVT::load_map();
            $cat_excl = NAVT_FE::get_exclusions($map_array);

            if( !empty($cat_excl) || !is_array($cat_excl) ) {
                $cats  = get_all_category_ids();
                for( $i = 0; $i < count($cats); $i++ ) {
                    if( in_array($cats[$i], $cat_excl) ) {
                        $exclusions .= ' AND t.term_id <> ' . intval($cats[$i],10);
                    }
                }// end for
            }// end if

            //navt_write_debug(NAVT_WPHOOKS, (sprintf("%s::%s returning: %s\n", __CLASS__, __FUNCTION__, $exclusions)));
            // put the filter back for the next time
            add_filter('list_terms_exclusions', array('NAVT_BE','list_terms_exclusions_wpcb'));

        }// end if
        else {
            //navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s - wrong cat or in admin/ignored\n", __CLASS__, __FUNCTION__));
        }
        return( $exclusions );
    }

    /**
     * Add asset
     *
     * @param int $typ
     * @param int $idn
     * @param array $item
     */
    function add_asset($typ, $idn, $item) {
        //navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s typ: %s, idn: %s\n", __CLASS__, __FUNCTION__, $typ, $idn), $item);

        $assets = NAVT::get_option(ASSETS);
        $assets[$typ][$idn] = $item;
        NAVT::update_option(ASSETS, $assets);
    }

    /**
     * Removes all occurences of an item from all groups
     *
     * @param int $type - item type
     * @param int $idn - item database id
     */
    function remove_item_from_all_groups($typ, $idn) {
        //navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s type: %s, idn: %s\n", __CLASS__, __FUNCTION__, $typ, $idn));

        $n_icfg = array();
        $icfg = NAVT::get_option(ICONFIG);
        $ityp = (intval($typ, 10) & 0xff);
        $iidn = (intval($idn, 10) & 0xff);

        foreach( $icfg as $group => $members ) {
            foreach( $members as $id => $item ) {
                //navt_write_debug(NAVT_WPHOOKS, sprintf("\t%s::%s checking id: %s\n", __CLASS__, __FUNCTION__, $id), $item);

                $i_typ = (intval($item[TYP]) & 0xff);
                $i_idn = (intval($item[IDN]) & 0xff);

                if( ($ityp == $i_typ) && ($iidn == $i_idn) ) {
                    //navt_write_debug(NAVT_WPHOOKS,sprintf("\t%s::%s typ: %s, idn: %s, found type: %s, idn: %s ttl: %s was removed from config\n",
                    //__CLASS__, __FUNCTION__, $ityp, $iidn, $i_typ, $i_idn, $item[TTL] ));
                }
                else {
                    $n_icfg[$group][$id] = $item;
                }
            }
        }

        // update the option
        //navt_write_debug(NAVT_WPHOOKS, sprintf("\t%s::%s updated icfg\n", __CLASS__, __FUNCTION__), $n_icfg);
        NAVT::update_option(ICONFIG, $n_icfg);
    }

    /**
     * Removes an asset from the asset array
     *
     * @param int $type - item type
     * @param int $idn - item database id
     */
    function remove_from_assets($typ, $idn) {
        //navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s type: %s, idn: %s\n", __CLASS__, __FUNCTION__, $typ, $idn));

        $n_assets = array();
        $assets = NAVT::get_option(ASSETS);
        $ityp = (intval($typ, 10) & 0xff);
        $iidn = (intval($idn, 10) & 0xff);

        foreach( $assets as $type ) {
            foreach( $type as $id => $item ) {
                $i_typ = (intval($item[TYP]) & 0xff);
                $i_idn = (intval($item[IDN]) & 0xff);

                if( $i_typ == $ityp && $i_idn == $iidn ) {
                    navt_write_debug(NAVT_WPHOOKS, sprintf("\t%s::%s asset item found - removed\n", __CLASS__, __FUNCTION__));
                }
                else {
                    // keep this
                    $n_assets[$i_typ][$i_idn] = $item;
                }
            }
        }
        // update the option
        //navt_write_debug(NAVT_WPHOOKS, sprintf("\t%s::%s updated assets\n", __CLASS__, __FUNCTION__), $n_assets);
        NAVT::update_option(ASSETS, $n_assets);
        return;
    }

    /**
     * Updates an asset
     *
     * @param array $args - item array
     */
    function update_asset($args) {
        //navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s\n", __CLASS__, __FUNCTION__), $args);
        $assets = NAVT::get_option(ASSETS);
        extract($args);

        $ityp = (intval($typ, 10) & 0xff);
        $iidn = (intval($idn, 10) & 0xff);

        $item = $assets[$typ][$idn];
        if( $typ == TYPE_AUTHOR ) {
            $item[NME] = $nme;
        }
        else {
            $item[TTL] = $item[NME] = $ttl;
        }

        $assets[$ityp][$iidn] = $item;

        NAVT::update_option(ASSETS, $assets);
        return;
    }

    /**
     * Updates all occurences of an item in all groups
     *
     * @param array $args - item array
     */
    function update_item_in_all_groups($args) {
        //navt_write_debug(NAVT_WPHOOKS, sprintf("%s::%s\n", __CLASS__, __FUNCTION__), $args);

        extract($args);
        $ityp = (intval($typ, 10) & 0xff);
        $iidn = (intval($idn, 10) & 0xff);

        $n_icfg = array();
        $icfg = NAVT::get_option(ICONFIG);

        foreach( $icfg as $group => $members ) {
            foreach( $members as $id => $item ) {
                $i_typ = (intval($item[TYP]) & 0xff);
                $i_idn = (intval($item[IDN]) & 0xff);

                if($iidn == $i_idn && $ityp == $i_typ) {
                    if( $typ == TYPE_AUTHOR ) {
                        $item[NME] = $nme;
                    }
                    else {
                        $makesame = ($item[TTL] == $item[NME] ? 1: 0);
                        $item[TTL] = $ttl;
                        $item[NME] = ($makesame ? $ttl: $item[NME]);
                    }
                }
                $n_icfg[$group][$id] = $item;
            }
        }

        NAVT::update_option(ICONFIG, $n_icfg);
    }
}// end class
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
 *
 * PHP Progressbar credit:
 * Juha Suni <juha.suni@sparecom.fi>
 */
$p = dirname(__FILE__);
$incpath = str_replace('app', '', $p);
if( DIRECTORY_SEPARATOR != '/') {
    $incpath = str_replace(DIRECTORY_SEPARATOR, '/', $incpath);
}
require_once (dirname(__FILE__) . '/../../../../wp-config.php');
require_once (dirname(__FILE__) . '/../../../../wp-admin/includes/taxonomy.php');
@include($incpath . 'includes/navt_debug.php');
$plugin_url = NAVT::get_url();

// various file upload problems
navt_loadtext_domain();

$err = array(
__('no error [0]', 'navt_domain'),
__('Restore file is too large. [1]', 'navt_domain'),
__('Restore file is too large. [2]', 'nat_domain'),
__('Restore file was only partially uploaded.', 'navt_domain'),
__('Please select a restore file.'),
__('Unknown Error. [5]', 'navt_domain'),
__('A temp directory is missing on your server.', 'navt_domain'),
__('Cannot write to temporary directory on server.', 'navt_domain'),
__('File upload failed. Restore could not be performed.', 'navt_domain')
);

// restore result messages
$res = array(
__('Restore completed', 'navt_domain'),
__('Incorrect file type. File must be an XML file.', 'navt_domain'),
__('Restore file is empty.', 'navt_domain'),
__('Restore failed, file is corrupt.', 'navt_domain'),
__('Restore failed, file is incomplete.', 'navt_domain')
);

if( isset( $_REQUEST['navt_action'] ) && $_REQUEST['navt_action'] == 'restore' ) {
    $error = $_FILES['restore_file']['error'];
    if( !empty($error) && $error != 0 ) {
        $_GET['message'] = $err[intval($error)];
    }
    else {
        $restore_how      = ((isset($_POST['restore_how']) ) ? $_POST['restore_how'] : RESTORE_IGNORE);
        $match_title      = ((isset($_POST['match']['title']) ) ? 1: 0 );
        $match_alias      = ((isset($_POST['match']['alias']) ) ? 1: 0 );
        $use_backup_alias = ((isset($_POST['match']['use_backup_alias'])) ? 1: 0);
        $publish_pages    = ((isset($_POST['match']['publish_pages'])) ? 1: 0);
        $discard_dups     = ((isset($_POST['discard_duplicates'])) ? 1: 0);
        $doin_the_deed    = 1;
    }
}// end if

if( $doin_the_deed ) {

    // read/parse the restore file
    global $r_gcfg, $r_icfg, $backup_version;
    $r_gcfg = $r_icfg = array();

    $retcode = get_navt_backup_version();

    if( $retcode == 0 ) {
        if( $backup_version > 9600 ) {
            $retcode = navt_restore_96();
        }
        else {
            $retcode = navt_restore_95();
        }
    }

    if( $retcode != 0 ) {
        // encountered an error of some kind while parsing the file
        $doin_the_deed = 0;
        $_GET['message'] = $res[$retcode];
    }

    else { ?>

    <div class="wrap">

        <h2><?php _e('NAVT Restore', 'navt_domain');?></h2>
        <p><?php _e('Restoring', 'navt_domain');?> <?php echo $_FILES['restore_file']['name'];?></p>
        <div class="progbar">
            <div class="bitem">
            <?php

            // start the restore
            ob_end_flush();
            flush();

            $pp = $pl = $iter = 0;
            $member_count = $inc = 0;
            if( count($r_icfg) ) {
                foreach( $r_icfg as $group => $members ) {
                    $member_count += count($members);
                }
                $inc = 100/$member_count;
            }

            //navt_write_debug(NAVT_RESTORE, sprintf("backup ver: %s\n", $backup_version));
            $icfg = NAVT::get_option(ICONFIG);
            $gcfg = NAVT::get_option(GCONFIG);
            $update_items = $update_groups = $restore_complete = 0;

            foreach($r_icfg as $group => $members) {
                foreach($members as $id => $data) {

                    $assets = NAVT::build_assets();
                    $new_item = navt_restore_item($data, $restore_how, $match_title,
                    $use_backup_alias, $publish_pages, $icfg, $assets, $discard_dups);
                    //navt_write_debug(NAVT_RESTORE, sprintf("item returned:\n"), $new_item);

                    if( count($new_item) > 0 ) {
                        /* add this group if necessary */
                        $group = $gcfg[$new_item[GRP]];
                        if( empty($group) ) {
                            $gcfg[$new_item[GRP]] = $r_gcfg[$new_item[GRP]];
                            //navt_write_debug(NAVT_RESTORE, sprintf("new group: %s \n",
                            //$new_item[GRP]), $gcfg[$new_item[GRP]]);
                            $update_groups++;
                        }
                        $id = NAVT::make_id($new_item, $member_count++);
                        $icfg[$new_item[GRP]][$id] = $new_item;
                        $update_items++;
                        //navt_write_debug(NAVT_RESTORE, sprintf("added item\n"), $icfg[$new_item[GRP]][$id]);
                    }

                    update_progress($iter, $inc, $pl, $plugin_url);
                    flush();
                }
            }
            $restore_complete = 1;
        } ?>
        </div>
    </div>
    <?php

    if( $restore_complete ) {
        echo "<p class='info'>";
        if( $update_groups ) {
            NAVT::update_option(GCONFIG, $gcfg);
            _e('New groups:', 'navt_domain'); printf("&nbsp;<span class='digit'>%s</span>&nbsp;&nbsp;", $update_groups);
        }
        if( $update_items ) {
            NAVT::update_option(ICONFIG, $icfg);
            _e('Updated items:', 'navt_domain'); printf("&nbsp;<span class='digit'>%s</span>", $update_items);
        }
        if( !$update_groups && !$update_items ) {
            _e('The configuration was not changed.', 'navt_domain');
        }
        echo "</p><div class='completemsg'><p>&bull;&nbsp;".__('Restore Complete', 'navt_domain')."&nbsp;&bull;</p></div>";
    }
} // end if $doin_the_deed

if( !$doin_the_deed ) {

// Displayed only if we're not doing the actual restore ?>
<?php if (isset($_GET['message'])) : ?>
<div id="message" class="updated fade"><p><?php echo esc_html( $_GET['message'] ); ?></p></div>
<?php endif; ?>

<div class="wrap">
  <h2><?php _e('NAVT Restore');?></h2>
  <form id="navt_restore" action='' method="post" enctype="multipart/form-data">
    <div class='instruct'><?php _e('NAVT Backup Restoration enables you to reconstruct a set of previously created navigation groups by loading a backup file created by the NAVT plugin. The information stored in the backup is merged with your current set of navigation items. The data merge is controlled by selecting one of the options in Step Two.', 'navt_domain');?>
    </div>

    <h3><?php _e('Duplicate Items in the same Group', 'navt_domain') ?></h3>
    <div class='instruct'><?php _e('If you merge a backup with existing navigation groups it is possible for a single item to appear multiple times within the same group. To avoid duplicating items, delete your current configuration before restoring the backup file or select the option to discard duplicates within the same group in Step Two.', 'navt_domain');?>

    <h3><?php _e('Terminology', 'navt_domain');?></h3>
    <?php _e('A <strong>matched</strong> item is defined on this page as a navigation item that exists in both the backup and within your current configuration. For example, a page or a category that is now in use on your web site that also existed at the time the backup was created. An <strong>unmatched</strong> item is a page, category or other item that exists in the backup but no longer exists on your web site. ', 'navt_domain');?>
   </div>
   <p class="warn center">&bull; <?php _e('Please backup any existing navigation groups before proceeding', 'navt_domain');?> &bull;</p>
   <hr />

   <h3><?php _e('Step One', 'navt_domain');?></h3>
   <p class='instruct'><?php _e('Select the file from your computer that contains the NAVT backup you wish to restore. This must be a backup file in XML format that was created by the NAVT plugin.', 'navt_domain');?></p>
<p class='padleft'><?php _e('File Name', 'navt_domain')?>: <input type="file" id="restore_file" name="restore_file" size="40" maxlength="80" value="" class="button" /></p><br />
<hr />
   <h3><?php _e('Step Two', 'navt_domain');?></h3>
   <p class='instruct'><?php _e('Select one of the merge options below.', 'navt_domain');?></p>
   <fieldset>
      <p class='padleft'><input type="radio" name="restore_how" value="<?php echo MERGE_DISCARD_UNMATCHED;?>" checked="checked" /> <?php _e('Ignore unmatched items', 'navt_domain');?><em> (<?php _e('Default setting','navt_domain');?>)</em></p>
      <p class="explain"><?php _e('Matched items are merged with existing navigation groups. Unmatched items are discarded.', 'navt_domain');?></p>
      <p class='padleft'><input type="radio" name="restore_how" value="<?php echo MERGE_CREATE_UNMATCHED;?>" /> <?php _e('Create unmatched items.', 'navt_domain');?></p>
      <p class="explain"><?php _e('Pages, categories or users are automatically created for unmatched items.', 'navt_domain');?><em> <?php _e('(Useful for creating new web sites from a backup)', 'navt_domain');?></em></p>
      <p style='padding: 0 0 0 35px; margin: 0 !important;'><input type="checkbox" name="match[publish_pages]" value="1" /> <?php _e('Publish pages created from restored page items.', 'navt_domain');?><em> (<?php _e('Default is to create a draft', 'navt_domain');?>)</em></p>
      <p class='padleft'><input type="checkbox" name="discard_duplicates" value="1" checked="checked" /> <?php _e('Discard duplicates in the same group','navt_domain');?> <em>(<?php _e('Default is checked', 'navt_domain');?>)</em></p>
   </fieldset>
<hr />

   <h3><?php _e('Step Three', 'navt_domain');?></h3>
   <p class='instruct'><?php _e('Item matching is primarily determined by the identifier assigned to an item by the database when the item was created. For example, a page item in the backup that has an identifier that is equal to a page identifier in your current set of pages is considered loosely matched. However, you can indicate that stricter matching be used by checking the option below.', 'navt_domain');?></p>
<p class='padleft'><input type="checkbox" name="match[title]" value="1" /> <?php _e('Backup item title must also equal matched item title.', 'navt_domain');?></p>
   <h4><?php _e('Additional Options', 'navt_domain');?></h4>
   <p class='padleft'><input type="checkbox" name="match[use_backup_alias]" value="1" /> <?php _e('Use the alias name in the backup as the preferred alias name', 'navt_domain');?> <em>(<?php _e('Default is current alias', 'navt_domain');?>)</em></p>
   <p class="submit"><input type="submit" id="navt_restore_plugin" name="navt_restore_plugin" value="<?php _e('Begin Restore &raquo;', 'navt_domain');?>" class="button delete" /></p>

   <input type='hidden' name='navt_action' value='restore' />
   </form>

<?php } ?>
</div><!-- wrap -->
<?php

/**
 * Get backup version from xml file
 */
function get_navt_backup_version() {

    global $backup_version;
    $retcode = 0;

    if( isset($_FILES['restore_file'])) {
        $file = $_FILES['restore_file']['tmp_name'];
        $user_file = $_FILES['restore_file']['name'];
        $mime_type = $_FILES['restore_file']['type'];

        if( $mime_type != 'text/xml' ) {
            $retcode = 1;// wrong type of file?
        }
        elseif(filesize($file) == 0 ) {
            $retcode = 2;// empty?
        }

        if( $retcode == 0 ) {
            $parse_error = $vals = $index = 0;
            $file_contents = file_get_contents($file);
            $p = xml_parser_create('UTF-8');
            $result = xml_parse_into_struct($p, $file_contents, $vals, $index);

            if( !$result ) {
                $parse_error = 1;
                //navt_write_debug(NAVT_RESTORE, sprintf("XML restore parse error: %s\n",
                //xml_error_string(xml_get_error_code($p))));
                $retcode = 3; // corrupt?
            }

            xml_parser_free($p);

            if(!$parse_error) {
                foreach ($vals as $k => $v) {
                    if( $v['tag'] == 'NAVT' && $v['type'] == 'open' ) {
                        $ver = $v['attributes']['MAJOR_VERSION'] . $v['attributes']['MINOR_VERSION'];
                        $backup_version = intval($ver, 10);
                    }
                    break;
                }
            }
        }
    }
    return($retcode);
}

/**
 * Read/Parse a restore file
 * versions < 96
 *
 */
function navt_restore_95() {

    global $r_gcfg, $r_icfg, $backup_version;
    $retcode = 0;

    if( isset($_FILES['restore_file'])) {
        $file = $_FILES['restore_file']['tmp_name'];
        $user_file = $_FILES['restore_file']['name'];
        $mime_type = $_FILES['restore_file']['type'];

        if( $mime_type != 'text/xml' ) {
            $retcode = 1;// wrong type of file?
        }
        elseif(filesize($file) == 0 ) {
            $retcode = 2;// empty?
        }
    }

    if( $retcode == 0 ) {
        $parser_error = $entries = $vals = $index = 0;
        $file_contents = file_get_contents($file);
        $p = xml_parser_create('UTF-8');
        $result = xml_parse_into_struct($p, $file_contents, $vals, $index);

        if( !$result ) {
            $parser_error = 1;
            //navt_write_debug(NAVT_RESTORE, sprintf("XML restore parse error: %s\n",
            //xml_error_string(xml_get_error_code($p))));
            $retcode = 3; // corrupt?
        }

        xml_parser_free($p);

        if( !$parser_error ) {
            //navt_write_debug(NAVT_RESTORE, sprintf("* Parsing restore file *\n"));

            $seq = 1000;
            $cur_group = '';

            foreach ($vals as $k => $v) {

                if( $v['tag'] == 'NAVT' ) {

                    if( $v['type'] == 'open' ) {
                        $in_map = 1;
                    }
                    elseif( $v['type'] == 'close' ) {
                        $in_map = 0;
                    }
                    continue;
                }

                if( $in_map ) {

                    if( $v['tag'] == 'ITEM' ) {
                        if( $v['type'] == 'open' ) {
                            $in_item = 1;
                            $item = array();
                        }
                        elseif( $v['type'] == 'close' ) {
                            $in_item = 0;
                            $cur_group = $item['GRP'];

                            if( $cur_group != 'UNASSIGNED' && $cur_group != 'unassigned' ) {
                                $id = NAVT::make_id($item, $seq++);
                                $item[VER] = md5($item[TYP].$item[IDN].$item[TTL]);

                                $r_icfg[$cur_group][ $id ] = array(
                                GRP => $item['GRP'], TYP => $item['TYP'], IDN => $item['IDN'], TTL => $item['TTL'],
                                NME => $item['NME'], OPT => $item['OPT'], LVL => $item['LVL'], EXT => $item['EXT'],
                                EX2 => $item['EX2'], VER => $item[VER]);

                                if( empty($r_gcfg[$cur_group]) ) {
                                    $r_gcfg[$cur_group] = NAVT::mk_group_config();
                                }
                                //navt_write_debug(NAVT_RESTORE, sprintf("\titem complete:"), $item);
                            }
                        }
                        continue;
                    }

                    if( $in_item ) {

                        if(( $v['tag'] == 'GRP' || $v['tag'] == 'TYP' || $v['tag'] == 'IDN' ||
                        $v['tag'] == 'OPT' || $v['tag'] == 'LVL' || $v['tag'] == 'TTL' || $v['tag'] == 'NME' ||
                        $v['tag'] == 'EXT' || $v['tag'] == 'EX2') && $v['type'] == 'complete') {
                            $t = $v['value'];
                            $item[$v['tag']] = $v['value'];
                            //navt_write_debug(NAVT_RESTORE, sprintf("\t\titem param: %s, value = %s\n", $v['tag'], $v['value']));
                        }
                    }// end in_item
                }// in_map
            }// end for

            //navt_write_debug(NAVT_RESTORE, sprintf("* Parsing restore end *\n"));
            //navt_write_debug(NAVT_RESTORE, sprintf("gcfg array\n"), $r_gcfg);
            //navt_write_debug(NAVT_RESTORE, sprintf("icfg array\n"), $r_icfg);
        }
    }
    return($retcode);
}


/**
 * Read/Parse a restore file
 * versions 96+
 */
function navt_restore_96() {

    global $r_gcfg, $r_icfg, $backup_version;
    $retcode = 0;

    if( isset($_FILES['restore_file'])) {
        $file = $_FILES['restore_file']['tmp_name'];
        $user_file = $_FILES['restore_file']['name'];
        $mime_type = $_FILES['restore_file']['type'];

        if( $mime_type != 'text/xml' ) {
            $retcode = 1;// wrong type of file?
        }
        elseif(filesize($file) == 0 ) {
            $retcode = 2;// empty?
        }
    }

    if( $retcode == 0 ) {
        $parser_error = $entries = $vals = $index = 0;
        $file_contents = file_get_contents($file);
        $p = xml_parser_create('UTF-8');
        $result = xml_parse_into_struct($p, $file_contents, $vals, $index);

        if( !$result ) {
            $parser_error = 1;
            //navt_write_debug(NAVT_RESTORE, sprintf("XML restore parse error: %s\n",
            //xml_error_string(xml_get_error_code($p))));
            $retcode = 3; // corrupt?
        }

        xml_parser_free($p);

        if( !$parser_error ) {
            //navt_write_debug(NAVT_RESTORE, sprintf("* Parsing restore file *\n"));

            $NAVT_state = $GROUP_state = '';
            $GROUP_posts = $GROUP_pages = $GROUP_open_ITEM = 0;
            $seq = 1000;
            $item = array();
            $cur_group = '';

            foreach ($vals as $k => $v) {
                //navt_write_debug(NAVT_RESTORE, sprintf("%s -> \n", $k), $v);

                if( $v['tag'] == 'NAVT' ) {
                    if( $v['type']  == 'open' ) {
                        $NAVT_state = 'open';
                        continue;
                    }
                    elseif( $v['type'] == 'close' || $v['type'] == 'complete' ) {
                        $NAVT_state = 'close';
                    }
                }

                if( $v['tag'] == 'GROUP' ) {
                    if( $v['type']  == 'open' ) {
                        $GROUP_state = 'open';
                    }
                    elseif( $v['type'] == 'close' || $v['type'] == 'complete' ) {
                        $GROUP_state = 'close';
                        $cur_group = '';
                    }
                }

                if( $NAVT_state == 'open' ) {

                    if( $GROUP_state == 'open' ) {

                        if( $v['tag'] == 'NAME' && $v['type'] == 'complete' ) {
                            $cur_group = $v['value'];
                            $r_gcfg[$cur_group] = NAVT::mk_group_config();
                        }
                        elseif($v['tag'] == 'OPTIONS' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['options'] = $v['value'];
                        }
                        elseif($v['tag'] == 'SELECTSIZE' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['select_size'] = $v['value'];
                        }
                        elseif($v['tag'] == 'ULID' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['css']['ulid'] = $v['value'];
                        }
                        elseif($v['tag'] == 'ULCLASS' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['css']['ul'] = $v['value'];
                        }
                        elseif($v['tag'] == 'LICLASS' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['css']['li'] = $v['value'];
                        }
                        elseif($v['tag'] == 'LIPARENT' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['css']['liparent'] = $v['value'];
                        }
                        elseif($v['tag'] == 'LI_PARENT_ACTIVE' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['css']['liparent_active'] = $v['value'];
                        }
                        elseif($v['tag'] == 'XPATH' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['selector']['xpath'] = $v['value'];
                        }
                        elseif($v['tag'] == 'BEFORE' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['selector']['before'] = $v['value'];
                        }
                        elseif($v['tag'] == 'AFTER' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['selector']['after'] = $v['value'];
                        }
                        elseif($v['tag'] == 'SELOPTION' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['selector']['option'] = $v['value'];
                        }
                        elseif($v['tag'] == 'SHOW_ON_OPTIONS' && $v['type'] == 'complete' ) {
                            $r_gcfg[$cur_group]['display']['show_on'] = $v['value'];
                        }
                        elseif($v['tag'] == 'POSTS' && $v['type'] == 'open' ) {
                            $GROUP_posts = 1;
                        }
                        elseif($v['tag'] == 'POSTS' && $v['type'] == 'close' ) {
                            $GROUP_posts = 0;
                        }
                        elseif($v['tag'] == 'ON_SELECTED' && $v['type'] == 'complete' && $GROUP_posts ) {
                            $r_gcfg[$cur_group]['posts']['on_selected'] = $v['value'];
                        }
                        elseif($v['tag'] == 'IDS' && $v['type'] == 'complete' && $GROUP_posts ) {
                            $r_gcfg[$cur_group]['posts']['ids'] = explode(',', $v['value']);
                        }
                        elseif($v['tag'] == 'PAGES' && $v['type'] == 'open' ) {
                            $GROUP_pages = 1;
                        }
                        elseif($v['tag'] == 'PAGES' && $v['type'] == 'close' ) {
                            $GROUP_pages = 0;
                        }
                        elseif($v['tag'] == 'ON_SELECTED' && $v['type'] == 'complete' && $GROUP_pages ) {
                            $r_gcfg[$cur_group]['pages']['on_selected'] = $v['value'];
                        }
                        elseif($v['tag'] == 'IDS' && $v['type'] == 'complete' && $GROUP_pages ) {
                            $r_gcfg[$cur_group]['pages']['ids'] = explode(',', $v['value']);
                        }
                        elseif($v['tag'] == 'ITEM' && $v['type'] == 'open' ) {
                            $GROUP_open_ITEM = 1;
                        }
                        elseif($v['tag'] == 'ITEM' && $v['type'] == 'close' ) {
                            $GROUP_open_ITEM = 0;
                            $id = NAVT::make_id($item, $seq++);

                            $r_icfg[$cur_group][ $id ] = array(
                            GRP => $item['GRP'], TYP => $item['TYP'], IDN => $item['IDN'], TTL => $item['TTL'],
                            NME => $item['NME'], OPT => $item['OPT'], LVL => $item['LVL'], EXT => $item['EXT'],
                            EX2 => $item['EX2'], VER => $item[VER]);
                            $item = array();
                        }
                        elseif( ($v['tag'] == 'GRP' || $v['tag'] == 'TYP' || $v['tag'] == 'IDN' ||
                        $v['tag'] == 'TTL' || $v['tag'] == 'NME' || $v['tag'] == 'OPT' || $v['tag'] == 'LVL' ||
                        $v['tag'] == 'EXT' || $v['tag'] == 'EX2') && $v['type'] == 'complete' && $GROUP_open_ITEM ) {
                            $item[$v['tag']] = $v['value'];
                        }
                    }
                }
            } // end for

            if( $NAVT_state == 'open' || $GROUP_state == 'open' ) {
                //navt_write_debug(NAVT_RESTORE, sprintf("navt state: %s, group state: %s\n", $NAVT_state, $GROUP_state));
                // items are still open
                $retcode = 3;
            }

            //navt_write_debug(NAVT_RESTORE, sprintf("* Parsing restore end *\n"));
            //navt_write_debug(NAVT_RESTORE, sprintf("gcfg array\n"), $r_gcfg);
            //navt_write_debug(NAVT_RESTORE, sprintf("icfg array\n"), $r_icfg);

        }// end if */
    }

    return($retcode);
}

/**
 * Restore an item
 *
 * @param unknown_type $item
 * @param unknown_type $restore_how
 * @param unknown_type $match_title
 * @param unknown_type $match_alias
 * @param unknown_type $use_backup_alias
 * @param unknown_type $publish_pages
 * @return unknown
 */
function navt_restore_item($item, $restore_how, $match_title, $use_backup_alias, $publish_pages, $icfg, $assets, $discard_dups) {

    //navt_write_debug(NAVT_RESTORE, sprintf("* Checking item from restore array\n"), $item);
    $strick_match = $matched_item = 0;
    $idn = $title = '';
    $new_item = null;

    if( ($item[IDN] != HOMEIDN) && ($item[IDN] != ELINKIDN) && ($item[IDN] != LOGINIDN) && ($item[IDN] != SEPIDN) ) {

        $asset = $assets[$item[TYP]][$item[IDN]]; // try the direct approach
        $v0 = md5($asset[TYP].$asset[IDN].$asset[TTL]);

        if( $v0 != $item[VER] ) {
            // try finding the item by type/title
            $ar = $assets[$item[TYP]];
            foreach( $ar as $idx => $data ) {
                //navt_write_debug(NAVT_RESTORE, sprintf("checking against\n"), $data);
                if( $item[TTL] == $data[TTL] ) {
                    $strick_match = $titles_match = $matched_item = 1;
                    $asset = $data;
                    break;
                }
            }
        }
        else {
            $strick_match = $titles_match = $matched_item = 1;
        }
    }
    else {
        // This must be a builtin asset
        $strick_match = $match_item = 1;
    }

    //navt_write_debug(NAVT_RESTORE, sprintf("item grp: %s, typ: %s, idn: %s, title: %s\n",
    //$item[GRP], $item[TYP], $item[IDN], $item[TTL]));
    //navt_write_debug(NAVT_RESTORE, sprintf("matched item = %s\n", $matched_item), $asset);

    if($restore_how == MERGE_DISCARD_UNMATCHED) {

        /* Merge item only if this asset exists in the asset list */
        if( $matched_item ) {
            if( !$discard_dups || ($discard_dups && !navt_is_duplicate($item, $icfg[$item[GRP]])) ) {
                $new_item = navt_mk_item($asset, $item[GRP], $item[NME], $use_backup_alias);
            }
        }
    }
    elseif( $restore_how == MERGE_CREATE_UNMATCHED ) {

        /* Merge item - create a new asset if the asset does not already exist */
        if( !$matched_item ) {

            if( !isBlank($item[TYP]) && !isBlank($item[TTL]) ) {

                // create this page
                if( $item[TYP] == TYPE_PAGE ) {
                    $item[IDN] = wp_insert_post( array(
                    'post_type' => 'page',
                    'post_title' => $item[TTL],
                    'post_name' => $item[TTL],
                    'post_content' => 'NAVT Restored page; temporary page contents',
                    'post_date' => current_time('mysql'),
                    'post_status' => (($publish_pages ) ? 'publish': 'draft')
                    ));
                }
                // create this category
                elseif( $item[TYP] == TYPE_CAT ) {
                    $item[IDN] = wp_create_category($item[TTL]);
                }
                // create this user
                elseif( $item[TTL] != 'admin' ) {
                    $item[IDN] = wp_create_user($item[TTL], 'navt_user', $email = '');
                }
            }
        }

        if( !isBlank($item[TYP]) && !isBlank($item[TTL]) ) {
            /* make this item */
            $asset = $item;
            $asset[NME] = $item[TTL];
            if( !$discard_dups || ($discard_dups && !navt_is_duplicate($item, $icfg[$item[GRP]])) ) {
                $new_item = navt_mk_item($asset, $item[GRP], $item[NME], $use_backup_alias);
            }
        }
    }

    return($new_item);
}


/**
 * Create an item from a backup
 *
 * @param array $asset
 * @param string $group
 * @param string $alias
 * @param boolean $use_alias
 * @return array
 */
function navt_mk_item($asset, $group, $alias, $use_alias) {
    $item = $asset;
    $item[GRP] = $group;
    $item[NME] = ( ($use_alias) ? $alias: $item[NME] );
    return($item);
}

/**
 * Determines if an item already appears in a group
 *
 * @param array $item
 * @param array $group
 * @return boolean
 */
function navt_is_duplicate($item, $group) {

    $is_dup = 0;

    if( is_array( $group ) ) {
        foreach( $group as $id => $member ) {
            if( $member[TYP] == $item[TYP] && $member[TTL] == $item[TTL] ) {
                $is_dup = 1;
                break;
            }
        }
    }
    return($is_dup);
}


/**
 * Update the progress bar
 *
 * @param integer $iter
 * @param integer $inc
 * @param integer $pl
 * @param string $plugin_url
 */
function update_progress(&$iter, $inc, &$pl, $plugin_url) {

    $pn = round(++$iter * $inc);

    if($pn != $pl) {
        print '<span class="pbox" style="z-index: ' . $pn . '; top: 265px;">' . $pn . '% </span>';
        $diff = $pn - $pl;
        for($j = 1; $j <= $diff; $j++) {
            print '<img src="' . $plugin_url . '/images/bar-single.gif" width="5" height="15" alt=""/>';
        }
        $pl = $pn;
    }
}
?>
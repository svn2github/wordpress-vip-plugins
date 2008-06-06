<?php
include('../includes/navtinc.php');
require (dirname(__FILE__).'/../../../../wp-config.php');
require('gzip-header-js.php'); ?>

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
*
*/

// program constants
var navtajx = '';
var last_id    = 0;
var is_page    = '<?php q(TYPE_PAGE);?>';
var is_cat     = '<?php q(TYPE_CAT);?>';
var is_link    = '<?php q(TYPE_LINK);?>';
var is_divider = '<?php q(TYPE_SEP);?>';
var is_elink   = '<?php q(TYPE_ELINK);?>';
var is_author  = '<?php q(TYPE_AUTHOR);?>';
var is_hrule = 1;
var is_plaintext = 2;
var is_empty_space = 0;
var standard_anchor = 0;
var text_over_graphic = <?php q(TEXT_OVER_GRAPHIC);?>;
var text_with_side_graphic = <?php q(TEXT_WITH_SIDE_GRAPHIC);?>;
var graphic_link = <?php q(GRAPHIC_LINK);?>;
var ltIe7 = 0;

// --------------------------
// navt namespace
// --------------------------

var navt_ns = {

    // initialize
    init: function(base_url) {

        navtajx = base_url + '/app/navtajx.php';
        ltIe7 = (jQuery.browser.msie && jQuery.browser.version < 7) ? 1: 0;

        if( !ltIe7 ) {
            jQuery('#navt-topbar div.r4').corner('6px');
            jQuery('#navt #container div.group-wrapper h3.r2').corner('tr tl 10px');
        }

        // buttons/toolbars
        navt_ns.init_buttons();
        navt_ns.init_toolbars();

        // initialize page
        navt_ns.init_assets();
        navt_ns.make_sortables(false);
        navt_ns.init_group_menus();
        navt_ns.init_items();

        jQuery(window).unload(
        function() {
            // update the group order when leaving the page
            navt_ns.update_group_order();
        });

        // update the group order on startup
        navt_ns.update_group_order();
        navt_ns.add_log_text("<?php _e('** plugin ready **', 'navt_domain');?>");
        console.log("navt => ready");
    },

    // initialize droppables/draggables
    init_assets: function() {

        // Create sortables from the select options
        jQuery('#asset-page, #asset-cat, #asset-user, #asset-other').click(
        function() {
            var v = jQuery(this).attr('value');
            var cl = v.split('::'); // v1.0.5
            var item_icon = 'i-' + cl[1];
            var id = cl[0] + '--' + last_id++;
            var alias = cl[2]; // v1.0.7
            var ar = navt_ns.crack_name(id);

            // create a clone and add it to the unassigned tray
            var clone = jQuery('#QIDQ').clone().attr('id', id);
            jQuery('#notassigned-sort').append(clone);
            jQuery('#container').sortableDestroy();
            if( cl[1] == 'draftpage') {
                jQuery('#'+id).addClass('disconnected');
                jQuery('#'+id+' .asset-disc').addClass('disconnected');
            }

            // make the necessary substitutions
            var nme = alias;
            alias = navt_ns.set_alias_len(alias, 0);
            var htm = jQuery('#'+id).html();
            htm = htm.replace(/QIDQ/gi, id);
            htm = htm.replace(/QALIASQ/gi, alias);
            htm = htm.replace(/QTYPEQ/gi, ar['type']);

            jQuery('#notassigned').fadeTo('slow', 1.0,
            function() {
                jQuery('#'+id).html(htm).slideDown('slow',
                function() {
                    jQuery('#' + id + '-alias').val(nme);
                    jQuery('#' + id + ' img.icon').addClass(item_icon);
                    jQuery(this).addClass('ui-enabled unassigned');
                    navt_ns.update_unassigned_count(id, 'inc');
                    navt_ns.make_sortables(false);
                    var txt = "<?php _e('item', 'navt_domain');?> " + alias + " <?php _e('was created', 'navt_domain');?>";
                    navt_ns.add_log_text(txt);
                    return;
                });
            });
        });

        // initialize this
        jQuery('#notassigned-count').val(1);

        if( ltIe7 ) {
            // show all selects in IE
            jQuery('#navt .selects').show();
        }
    },

    // initialize toolbars
    init_toolbars: function() {

        jQuery('#page_sortby_title, #page_sortby_order').click(
        function() {
            var by_order = (jQuery('#page_sortby_title').attr('checked') == true) ? 'post_title': 'menu_order';
            navt_ns.sort_assets('&type=page&orderby='+by_order, 'page');
            return(true);
        });

        jQuery('#cat_sortby_title, #cat_sortby_order').click(
        function() {
            var by_order = (jQuery('#cat_sortby_title').attr('checked') == true) ? 'name': 'menu_order';
            navt_ns.sort_assets('&type=category&orderby='+by_order, 'cat');
            return(true);
        });

        jQuery('#user_sortby_title, #user_sortby_order').click(
        function() {
            var by_order = (jQuery('#user_sortby_title').attr('checked') == true) ? 'user_nicename': 'user_login';
            navt_ns.sort_assets('&type=user&orderby='+by_order, 'user');
            return(true);
        });
    },

    // create sortables
    make_sortables: function(create_new) {

        if( create_new === true ) {
            jQuery('#container').sortableDestroy();
        }

        jQuery('#navt #container').sortable({
            items: 'div.group-wrapper ul.sortgroup li.ui-enabled',
            //items: 'li.ui-enabled',
            handle: 'img.icon',
            hoverClass: 'sort-guide',
            zIndex: 1000,
            tolerance: 'intersect',
            smooth: false,
            start: function(e, ui) {
                var t = jQuery(this).attr('class');
                var level = t.substr(t.indexOf('level-'));
                var id = jQuery(this).attr('id');
                var h = jQuery(this).css('height');

                jQuery(ui.helper).css({
                    opacity: .7 // workaround - no sortable option for this
                });
                jQuery('.sort-guide').addClass(level).css({zIndex:1000, height: h});
                return;
            },
            stop: function(el, ui) {
                var id = jQuery(this).attr('id');
                if( !(jQuery('#' + id + '-alias-anchor').hasClass('alias-anchor')) ) {
                    // add the anchor
                    navt_ns.item_alias(id, 'add');
                    navt_ns.update_unassigned_count(id, 'dec' );
                }
                navt_ns.update_group_order();
                return;
            }
        });
    },

    // keeps track of the number of unassigned items in the unassigned fieldset
    update_unassigned_count: function(el, what) {

        var c = parseInt( jQuery('#notassigned-count').val() & 0xff);
        if( jQuery('#'+el).hasClass('unassigned') ) {
            if( what == 'dec' ) {
                jQuery('#'+el).removeClass('unassigned');
                jQuery('#notassigned-count').val(--c);
                if( c == 1 ) {
                    jQuery('#notassigned').fadeTo('slow', 0.33);
                }
            }
            else if( what == 'inc' ) {
                jQuery('#notassigned-count').val(++c);
            }
        }
    },

    // initialize buttons
    init_buttons: function() {

        // toolbar buttons
        jQuery('#grp-create').click(function()    {return(navt_ns.create_group());});
        jQuery('#do-backup').click(function()     {return(navt_ns.backup_group());});
        jQuery('#do-restore').click(function()    {return(navt_ns.restore_group());});

        //help buttons
        jQuery('#help-grp').click(function()      {return(navt_ns.help('new_group'));});
        jQuery('#help-backup').click(function()   {return(navt_ns.help('backup'));});
        jQuery('#help-restore').click(function()  {return(navt_ns.help('restore'));});
        jQuery('#help-assets').click(function()   {return(navt_ns.help('assets'));});
    },

    // initialize the group options helper window
    init_group_helper_window: function() {

        // setup 'show on posts' and 'show on pages' checkbox selections
        jQuery('#option-wrapper input.optckbox').unbind().click(
        function() {
            var id = jQuery(this).attr('id');
            var selected = (jQuery(this).attr('checked') == true) ? 1: 0;
            var type = ((id == 'set-on-posts') ? 'post': 'page');

            if( id == 'set-on-posts' || id == 'set-on-pages' ) {
                if( selected ) {
                    jQuery('#'+type+'-options p.show-hide, #'+type+'-ids').removeClass('disabled');
                    jQuery('#show-on-unselect-'+type+', #show-on-select-'+type+', #'+type+'-ids').removeAttr('disabled');
                }
                else {
                    jQuery('#'+type+'-options p.show-hide, #'+type+'-ids').addClass('disabled');
                    jQuery('#show-on-unselect-'+type+', #show-on-select-'+type+', #'+type+'-ids').attr('disabled', 'disabled');
                }
            }
            return(true);
        });

        // set the close event
        navt_ns.set_page_fold_option();
        jQuery('#option-fold').unbind().
        click(function() {
            navt_ns.set_page_fold_option();
            return(true);
        });

        // add user classes
        navt_ns.add_user_classes();
        jQuery('#add_user_classes').unbind().
        click(function() {
            navt_ns.add_user_classes();
            return(true);
        });

        // closebox icon
        jQuery('#option-helper div.closebox a').unbind().
        click(function() {
            // close the box
            navt_ns.close_helper_window();
            return(false);
        });

        // help icon
        jQuery('#option-helper div.helpbox a').unbind().
        click(function() {
            // ask for option help
            var curtab = jQuery('#option-helper li.boxtab h3.activetab').attr('id');
            navt_ns.help('group-options-'+curtab);
            return(false);
        });

        // group select option
        navt_ns.set_group_select_option();
        jQuery('#optionstab #option-select').unbind().
        click(function() {
            navt_ns.set_group_select_option();
            return(true);
        });

        // avoid corners for IE
        if( !ltIe7 ) {
            jQuery('#option-helper .r2').corner('tr tl 10px');
        }

        if( (jQuery('#option-name').css('display')) == 'none' ) {
            jQuery('#option-name').focus();
        }
    },

    // Handles page folding selection
    set_page_fold_option: function() {

        var selected = (jQuery('#option-fold').attr('checked') == true) ? 1: 0;
        if(selected) {
            jQuery('#option-page-return, #option-page-return-label').removeClass('disabled');
            jQuery('#option-page-return').attr('disabled', '');
        }
        else {
            jQuery('#option-page-return, #option-page-return-label').addClass('disabled');
            jQuery('#option-page-return').attr('disabled', 'disabled');
        }
    },

    // Handles add user classes selection
    add_user_classes: function() {

        var selected = (jQuery('#add_user_classes').attr('checked') == true) ? 1: 0;
        var lbl = 'li.indent span';
        var tbx = '#option-ul-id, #option-ul-class, #option-li-class, #option-li-class-current,' +
        '#option-li-class-parent, #option-li-class-parent-active';

        if(selected) {
            jQuery(lbl).removeClass('disabled');
            jQuery(tbx).attr('disabled', '');
        }
        else {
            jQuery(lbl).addClass('disabled');
            jQuery(tbx).attr('disabled', 'disabled');
        }
    },

    // handles group select checkbox
    set_group_select_option: function() {

        jQuery('#optionstab #select-size').attr('disabled', 'disabled').addClass('disabled');
        jQuery('#optionstab #select-size-label-1, #optionstab #select-size-label-2').addClass('disabled');

        var is_checked = (jQuery('#optionstab #option-select').attr('checked') == true) ? 1: 0;
        if( is_checked ) {
            jQuery('#optionstab #select-size').attr('disabled', '').removeClass('disabled').focus();
            jQuery('#optionstab #select-size-label-1,#optionstab #select-size-label-2').removeClass('disabled');
        }
    },

    // -----------------------------------------------------------------------
    // Group functions
    // -----------------------------------------------------------------------

    init_group_menus: function() {

        jQuery('#container div.group-wrapper.locked').each(
        function() {
            var id = jQuery(this).attr('id');
            jQuery('#' + id + ' .grprem, #' + id + ' .grpopts').fadeOut('fast',
            function() {
                jQuery('#' + id + ' .sortgroup').slideUp('fast');
            });
            var group = id.toLowerCase();
            var txt = "<?php _e('group', 'navt_domain');?> '" + group +
            "' <?php _e('is locked', 'navt_domain');?>";
            navt_ns.add_log_text(txt);
        });
    },

    // get group options
    group_options: function(el) {

        var upcase_group_name = jQuery(el.parentNode).attr('id');
        var locase_group_name = upcase_group_name.toLowerCase();
        jQuery('#' + upcase_group_name + ' div.group-spinner').addClass('waiting');
        jQuery('#' + upcase_group_name + ' a.grpopts').addClass('noicon');

        // get the html for the contents of the options box that will be displayed
        jQuery.ajax({
            type: "POST",
            processData: false,
            url: navtajx,
            data: 'navtajx[action]=get_group_options&group='+locase_group_name,

            success: function(response, status) {
                var o = new Object();
                o = JSON.parse(response);

                if( o.rc == 'ok' ) {
                    jQuery('#option-helper').html(o.html);
                    var bw = (parseInt(o.width) & 0xffff);
                    var topOffset = jQuery('div.navt-wrap').offset().top;
                    var owidth = jQuery('#option-outer-wrapper').outerWidth();
                    var loffset = ((jQuery(window).width()) / 2 - (bw/2));

                    // IE z-index select bug
                    if( ltIe7 ) {
                        jQuery('#navt .selects').hide();
                    }

                    // make the background window opaque
                    jQuery('#window-mask').css({opacity: .5}).fadeIn(1000,
                    function() {
                        // position the wrapper and open it
                        jQuery('#option-outer-wrapper').css({left: loffset, width: bw}).fadeIn(500,
                        function() {
                            // init the helper window
                            navt_ns.init_group_helper_window();
                            jQuery('#' + upcase_group_name + ' div.group-spinner').removeClass('waiting');
                            jQuery('#' + upcase_group_name + ' .grpopts').removeClass('noicon');
                            console.log("navt => options were requested/returned for item id %s", locase_group_name);
                        });
                    });

                    // IE mouseovers
                    if( ltIe7 ) {
                        jQuery( '#option-helper div.helpbox,'+
                        '#option-help div.closeoptionhelp,'+
                        '#option-helper div.closeoptionhelp,'+
                        '#option-helper div.closebox').mouseover(
                        function() {
                            jQuery(this).css({backgroundPosition: '0 -16px'});
                        });

                        jQuery( '#option-helper div.helpbox,'+
                        '#option-help div.closeoptionhelp,'+
                        '#option-helper div.closeoptionhelp,'+
                        '#option-helper div.closebox').mouseout(
                        function() {
                            jQuery(this).css({backgroundPosition: '0 0'});
                        });
                    }
                }
                else {
                    // error ?
                }
            },

            error: function(error) {
                jQuery('#' + upcase_group_name + ' div.group-spinner').removeClass('waiting');
                jQuery('#' + upcase_group_name + ' .grpopts').removeClass('noicon');
                console.error("navt => e0C ajax error: - server returned %s", error);
                navt_ns.add_log_text("e0C ajax error: " + error);
            }
        });

        return(false);
    },

    // remove a group
    group_remove: function(el) {

        var upcase_group_name = jQuery(el.parentNode).attr('id');

        // get the html for the contents of the options box that will be displayed
        jQuery.ajax({
            type: "POST",
            processData: false,
            url: navtajx,
            data: 'navtajx[action]=ask_remove_group&group='+upcase_group_name.toLowerCase(),

            success: function(response, status) {
                var o = new Object();
                o = JSON.parse(response);

                if( o.rc == 'ok' ) {

                    jQuery('#wintitle').html('<h3>' + o.title + '</h3>');
                    jQuery('#target').html(o.html);
                    jQuery('#dialog').modal({close:false});
                    var ww = jQuery(window).width();
                    var loffset = (ww-o.width)/2;
                    jQuery('#modalContainer').animate({top:'40%', left:loffset, width:o.width+'px', height:o.height+'px'}, 'fast');
                    var name = o.group.toLowerCase();

                    // cancel
                    jQuery('#'+name+'-remove-can').unbind().click(
                    function() {
                        jQuery.modal.close();
                        navt_ns.add_log_text("<?php _e('** cancelled ** (group remove)', 'navt_domain');?>");
                        return(false);
                    });

                    // ok
                    jQuery('#'+name+'-remove-ok').unbind().click(
                    function() {
                        var group = jQuery(this).attr('id');
                        group = group.split('-');


                        jQuery.ajax({
                            type: "POST",
                            processData: false,
                            url: navtajx,
                            data: 'navtajx[action]=remove_group&group=' + group[0],

                            success: function(response, status) {
                                var o = new Object();
                                o = JSON.parse(response);

                                if( o.rc == 'ok' ) {
                                    var name = o.group;
                                    jQuery('#' + name).slideUp("slow",
                                    function() {
                                        jQuery('#'+name).remove();
                                        navt_ns.update_group_order();
                                        navt_ns.make_sortables(true);
                                        var txt = "<?php _e('group','navt_domain');?> '" +
                                        name.toLowerCase() + "' <?php _e('was removed', 'navt_domain');?>";

                                        navt_ns.add_log_text(txt);
                                        console.log("navt => group removed - %s", o.rc);
                                    });
                                    jQuery.modal.close();
                                    return(false);
                                }
                                else {
                                    // error?
                                }
                            },

                            error: function(error) {
                                console.error("navt => e01 ajax error: - server returned %s", error );
                                navt_ns.add_log_text("e01 ajax error: " + error);
                            }
                        });
                    });
                }
                else {
                    // error?
                }
            },

            error: function(error) {
                console.error("navt => e01 ajax error: - server returned %s", error );
                navt_ns.add_log_text("e01 ajax error: " + error);
            }
        });
        return(false);
    },

    // Create a new group
    create_group:function() {

        var gn = jQuery('#new-group-name').val();
        jQuery('#add-spinner').addClass('waiting');

        jQuery.ajax({
            type: "POST",
            processData: false,
            url: navtajx,
            data: 'navtajx[action]=add_group&group=' + gn,

            success: function(response, status) {
                jQuery('#add-spinner').removeClass('waiting');
                var o = new Object();
                o = JSON.parse(response);

                if( o.rc == 'ok' ) {
                    var name = o.name.toUpperCase();
                    jQuery('#create-msg').text('').fadeOut('slow');
                    var clone = jQuery('#Q2719520Q').clone().attr('id', name);
                    jQuery('#container').append(clone);

                    var htm = jQuery('#'+name).html();
                    htm = htm.replace(/Q2719521Q/gi, o.name);
                    htm = htm.replace(/QdisplaynameQ/gi, o.display_name);
                    jQuery('#'+name).html(htm).addClass('cs'+o.scheme).slideDown("slow");

                    navt_ns.init_toolbars();
                    navt_ns.make_sortables(true);
                    navt_ns.init_group_menus();

                    jQuery('#new-group-name').val('');
                    var txt = "<?php _e('group', 'navt_domain');?> '" + o.name + "' <?php _e('was created');?>";
                    navt_ns.add_log_text(txt);
                    console.log("navt => create group - %s", o.rc);
                }
                else {
                    // error msg
                    jQuery('#create-msg').text(':: ' + o.msg + ' ::').fadeIn('slow');
                }
            },

            error: function(error) {
                jQuery('#add-spinner').removeClass('waiting');
                console.error("navt => e02 ajax error: - server returned %s", error);
                navt_ns.add_log_text("e02 ajax error: " + error);
            }
        });

        return(false);
    },

    // save group order
    update_group_order: function() {

        var order = '';
        var group = jQuery('ul.reorder');
        var j;

        for(var x = 0; x < group.length; x++) {
            j = 0;
            var group_name = navt_ns.get_id(group[x], ':'); // v1.0.5
            var item = jQuery(group[x]).children();

            for(var i = 0; i < item.length; i++) {
                // ignore the group spacer
                if( !(jQuery(item[i]).hasClass('group-spacer')) && jQuery(item[i]).hasClass('navitem') ) {
                    order += 'order%5B' + group_name + '%5D%5B' + j + '%5D=' + jQuery(item[i]).attr('id');
                    if (j < item.length - 1) order += "&";
                    j++;
                }
            }
            if (x < group.length - 1) order += "&";
        }

        jQuery.ajax({
            type: "POST",
            processData: false,
            url: navtajx,
            data: 'navtajx[action]=reorder_groups&' + order,

            success: function(response, status) {
                var o = new Object();
                o = JSON.parse(response);

                if( o.rc == 'ok' ) {

                    navt_ns.set_hover();
                    console.log("navt => groups reordered - %s", o.rc);

                }
                else {
                    // error?
                    console.log("navt => group reorder problem: %s, msg %s", o.rc, o.msg);
                }
            },

            error: function(error) {
                console.error("navt => e03 ajax error: - server returned %s", error);
                navt_ns.add_log_text("e03 ajax error: " + error);
            }
        });

        return(false);
    },

    // lock/unlock group changes
    group_option: function(el, request) {

        switch( request ) {

            case 'lock': {
                var parent = el.offsetParent.id;
                var group = parent.toLowerCase();
                var selector = '#' + parent + ' a.grplock';

                jQuery.ajax({
                    type: "POST",
                    processData: false,
                    url: navtajx,
                    data: 'navtajx[action]=toggle_group_option&group='+group+'&option='+request,

                    success: function(response, status) {
                        var s = '#@id@ div.asset-wrapper,#@id@ a.grpopts,#@id@ a.grprem';
                        var o = new Object();
                        o = JSON.parse(response);

                        if( o.rc == 'ok' ) {
                            jQuery(selector).toggleClass('locked');
                            jQuery('#' + parent).toggleClass('locked');

                            if( o.option == '0' ) {
                                // unlock everything
                                s = s.replace(/@id@/gim, parent);
                                //jQuery('#container').sortableDestroy();

                                jQuery('#' + parent + ' a.grpopts,#' + parent + ' a.grprem').fadeIn('fast',
                                function() {
                                    jQuery('#' + parent + ' a.grpopts, #' + parent + ' a.grprem').each(
                                    function() {
                                        jQuery(this).css({opacity: 1});
                                    });
                                });

                                jQuery('#' + parent + ' li.navitem').each(
                                function() {
                                    var id = jQuery(this).attr('id');
                                    if( !(jQuery(this).hasClass('group-spacer')) ) {
                                        navt_ns.item_alias(id, 'set_open_event');
                                    }
                                    jQuery(this).addClass('ui-enabled').removeClass('ui-disabled');
                                });

                                jQuery(s).fadeIn('slow',
                                function() {
                                    jQuery('#' + parent + ' .sortgroup').slideDown('fast',
                                    function() {
                                        //jQuery(this).css({opacity: 1});
                                    });
                                });
                                //navt_ns.make_sortables(false);
                                var txt = "<?php _e('group','navt_domain');?> '" + group +
                                "' <?php _e('is now unlocked', 'navt_domain');?>";
                                navt_ns.add_log_text(txt);
                            }

                            if( o.option == '1' ) {
                                // lock everything from changing
                                s = s.replace(/@id@/gim, parent);
                                //jQuery('#container').sortableDestroy();

                                jQuery('#' + parent + ' li.openbox').each(
                                function() {
                                    var id = jQuery(this).attr('id');
                                    jQuery('#' + id + ' a.alias-anchor').trigger('click');
                                });

                                jQuery('#' + parent + ' li.navitem').each(
                                function() {
                                    var id = jQuery(this).attr('id');
                                    jQuery(this).addClass('ui-disabled').removeClass('ui-enabled');
                                    if( !(jQuery(this).hasClass('group-spacer')) ) {
                                        //navt_ns.item_alias(id, 'remove');
                                    }
                                });

                                jQuery(s).fadeOut('fast',
                                function() {
                                    jQuery('#' + parent + ' .sortgroup').slideUp('fast',
                                    function() {
                                        //navt_ns.make_sortables(true);
                                    });
                                });
                                var txt = "<?php _e('group','navt_domain');?> '" + group +
                                "' <?php _e('is now locked', 'navt_domain');?>";
                                navt_ns.add_log_text(txt);
                            }
                            console.log("navt => group option: %s - request: %s, setting: %s, group: %s",
                            o.rc, request, o.option, o.name);
                        }
                        else {
                            // error?
                            console.log("navt => group option: %s, msg %s", o.rc, o.msg);
                        }
                    },

                    error: function(error) {
                        console.error("navt => e04 ajax error: - server returned %s", error);
                        navt_ns.add_log_text("e04 ajax error: " + error);
                    }
                });
                break;
            }

            default: {
                break;
            }
        }// end switch

        return(false);
    },

    // activates a group options tab in the options helper
    group_options_tab: function(n_tab) {

        var curtab = jQuery('#option-helper li.boxtab h3.activetab').attr('id');
        var newtab = 'tab-' + n_tab;

        var new_section = (
        (n_tab == 0) ? '#optionstab' :
        (n_tab == 1) ? '#displaytab' :
        (n_tab == 2) ? '#csstab' : '#themetab'
        );

        var cur_section = (
        (curtab == 'tab-0') ? '#optionstab' :
        (curtab == 'tab-1') ? '#displaytab' :
        (curtab == 'tab-2') ? '#csstab' : '#themetab'
        );

        if( newtab != curtab ) {
            jQuery('#' + curtab).toggleClass('dormant').toggleClass('activetab');
            jQuery('#option-helper ' + cur_section).fadeOut('slow',
            function() {
                jQuery('#' + newtab).toggleClass('dormant').toggleClass('activetab');
                jQuery('#option-helper ' + new_section).fadeIn('slow');
            });
        }
        return(false);
    },

    // save group options
    group_option_save: function(savetype) {

        var activetab = jQuery('.activetab').attr('id');
        var group_name = jQuery('#options-for-group').val();
        var posts = jQuery('#post-ids').val();
        var pages = jQuery('#page-ids').val();
        var args = jQuery('#option-form').serialize();
        var ar = '';

        jQuery('#'+'option-helper .option-spinner').addClass('waiting');
        jQuery('#'+'option-helper div.rename-errormsg').text('').hide();

        if( pages != null && pages.length > 0 ) {
            for( var i = 0; i < pages.length; i++ ) {
                ar += 'page_list%5B' + i + '%5D=' + pages[i];
                ar += (i < pages.length - 1) ? '&' : '';
            }
        }

        if( posts != null && posts.length > 0 ) {
            if( ar != '' ) ar += '&';
            for( var i = 0; i < posts.length; i++ ) {
                ar += 'post_list%5B' + i + '%5D=' + posts[i];
                ar += (i < posts.length - 1) ? '&' : '';
            }
        }

        if( args.length != 0 || ar != '') {
            jQuery.ajax({
                type: "POST",
                processData: false,
                url: navtajx,
                data: "navtajx[action]=save_group_options&group="
                + group_name + ((args != '') ? '&' + args : '') + ((ar != '') ? '&' + ar: '') +
                '&savetype='+savetype+'&activetab='+activetab,

                success: function(response, status) {
                    jQuery('#option-helper .option-spinner').removeClass('waiting');
                    var o = new Object();
                    o = JSON.parse(response);

                    if( o.rc == 'ok' ) {
                        if( savetype == 0 ) {
                            // saving (not closing)
                            jQuery('#option-helper').html(o.html);
                            navt_ns.init_group_helper_window();
                        }
                        else {
                            // close the box
                            navt_ns.close_helper_window();
                        }

                        // check if renamed
                        var group_id = o.name.toUpperCase();
                        if( o.name != group_name ) {
                            navt_ns.rename_group(group_name, o.name, o.display_name);
                        }

                        // check if now private
                        if( o.isprivate == '1' ) {
                            jQuery('#' + group_id).addClass('private');
                        }
                        else {
                            jQuery('#' + group_id).removeClass('private');
                        }
                        var txt = "<?php _e('group','navt_domain');?> '" + o.name +
                        "' <?php _e('options were saved', 'navt_domain');?>";
                        navt_ns.add_log_text(txt);
                    }
                    else {
                        // error
                        if( o.type == 'rename' ) {
                            if( activetab == 'tab-1') {
                                navt_ns.group_options_tab(0);
                            }
                            jQuery('#option-helper div.rename-errormsg').text(':: ' + o.msg + ' ::').show('slow');
                        }
                    }
                },
                error: function() {
                    jQuery('#option-helper .option-spinner').removeClass('waiting');
                    console.error("navt => e05 ajax error: - server returned %s", error);
                    navt_ns.add_log_text("e05 ajax error: " + error);
                }
            });
        }

        return false;
    },

    // group rename
    rename_group: function(old_name, new_name, display_name)  {

        var oname = old_name.toUpperCase();
        var nname = new_name.toUpperCase();

        var htm = jQuery('#'+oname).html();
        htm = htm.replace(new RegExp(old_name+'-', 'gim'), new_name+'-');
        jQuery('#'+oname).html(htm);
        if( !ltIe7 ) {
            jQuery('#'+oname+' h3.r2').text(display_name).corner('tr tl 10px');
        }
        jQuery('#'+oname).attr('id', nname);

        navt_ns.make_sortables(true);
        navt_ns.init_group_menus();

        jQuery('#'+new_name+'-newname').val('');
        jQuery('#'+new_name+'-rename').slideToggle('slow');
        jQuery('#'+new_name+' p.errormsg').text('').hide();
        var txt = "<?php _e('group','navt_domain');?> '" + old_name +
        "' <?php _e('was renamed to', 'navt_domain');?>" + " '" + new_name + "'";
        navt_ns.add_log_text(txt);
        console.log("navt => renamed group - %s => %s", oname, nname);
        return;
    },

    // Close options helper window
    close_helper_window: function() {

        jQuery('#option-outer-wrapper').fadeOut(1000,
        function() {
            jQuery('#window-mask').fadeOut('fast',
            function() {
                if( ltIe7 ) {
                    // turn on selects in IE
                    jQuery('#navt .selects').show();
                }
                jQuery('#option-helper').html('<div>&nbsp;</div>');
            });
        });
    },

    // -----------------------------------------------------------------------
    //       Item functions
    // -----------------------------------------------------------------------

    init_items: function() {

        jQuery('#navt #container ul li.ui-enabled div.asset-name a.alias-anchor').unbind().click(
        function() {
            navt_ns.item_options(this);
            return(false);
        });

        jQuery('#container ul.sortgroup li.disconnected').each(
        function() {
            var id = jQuery(this).attr('id');
            var group = jQuery(this.offsetParent).attr('id');
            var alias = jQuery('#' + id + '-alias').val();
            var txt = "'" + alias + "' <?php _e('in group','navt_domain');?> '" +
            group.toLowerCase() + "' <?php _e('is disconnected', 'navt_domain');?>";
            navt_ns.add_log_text(txt);
        });

        navt_ns.set_hover();
    },

    // Set hover events for ie browsers
    set_hover: function() {

        // IE specific for hover behavior
        if( ltIe7 ) {
            jQuery('div.lc a.up, div.lc a.dn, img.disc-button, img.disc-button, img.remove-button').mouseover(
            function() {
                jQuery(this).css({backgroundPosition: '0 -16px'});
            });

            jQuery('div.lc a.up, div.lc a.dn, img.disc-button, img.disc-button, img.remove-button').mouseout(
            function() {
                jQuery(this).css({backgroundPosition: '0 0'});
            });
        }
    },

    // add or remove the alias text anchor
    item_alias: function(id, what) {

        var s = '#' + id;
        if( (id != undefined) && !(jQuery(id).hasClass('group-spacer')) ) {

            //var nme = new String(jQuery(s + '-alias').val()); // complete alias
            var nme = jQuery(s + '-alias').val();
            var alias = nme;
            var w = '';

            var t = jQuery(s).attr('class');
            var level = t.substr(t.indexOf('level-'));
            var cls = level.split(' ');
            level = 0;

            for( var i = 0; i < cls.length; i++ ) {
                w = cls[i];
                if( w.substr(w.indexOf('level-')) ) {
                    level = w.split('level-');
                    level = parseInt(level[1], 10);
                    break;
                }
            }

            alias = navt_ns.set_alias_len(nme, level);
            var html = new String();

            if( what == 'add' ) {
                // add the anchor
                html = "<a class='alias-anchor' href='#' id='" + id + "-alias-anchor' title='" + nme + "'>" + alias + "</a>";
                jQuery(s + ' div.asset-name').html(html);
                navt_ns.item_alias_event(id, 'set_open');
            }
            else if( what == 'remove' ) {
                // remove the anchor
                navt_ns.item_alias_event(id, what);
                jQuery(s + ' div.asset-name').html('<p>' + alias + '</p>');
            }
            else if( what == 'set_open_event' ) {
                navt_ns.item_alias_event(id, 'set_open');
            }
        }
    },

    // add or remove the item anchor click event
    item_alias_event: function(id, what) {

        var s = '#' + id;
        if( what == 'set_open' ) {
            // set the event to open the item box
            jQuery(s + ' a.alias-anchor').unbind().click(
            function() {
                navt_ns.item_options(this);
                return(false);
            });
        }

        else if( what == 'remove' ) {
            jQuery(s + ' a.alias-anchor').unbind();
        }

        else if( what == 'set_close' ) {
            // set the event to close the item box

            jQuery(s + ' a.alias-anchor').unbind().click(
            function() {
                var id = navt_ns.get_id(this, '-alias-anchor');
                var s = '#' + id;
                var h = jQuery(s + ' div.asset-options').outerHeight();

                jQuery(s + ' div.asset-options div.options').fadeOut('slow',
                function() {
                    jQuery(s).animate({height: '35px'}, 'slow',
                    function() {
                        jQuery(this).css({width:'', marginLeft:''});
                        jQuery(s + ' div.asset-options div.options').html('<div></div>');
                        jQuery(s).removeClass('openbox');
                        if( jQuery(this).hasClass('ui-enabled') ) {
                            jQuery(s + ' div.asset-wrapper').fadeIn('fast',
                            function() {
                                jQuery(s + ' div.asset-options div.options').html('<div></div>');
                                navt_ns.item_alias_event(id, 'set_open');
                            });
                        }
                    });
                });
                return(false);
            });
        }
    },

    // opens the item's option box
    open_optionsbox: function(id) {

        var s = '#' + id;
        jQuery(s + ' div.asset-wrapper').animate({opacity: 0}, 500,
        function() {
            jQuery(this).css({display: 'none', opacity: 1});
            jQuery(s).animate({marginLeft: '0', width: '190px'}, 'slow',
            function() {
                jQuery(this).addClass('openbox');
                jQuery(s + ' div.asset-options div.options').css({opacity: 0, display: ''});
                h = jQuery(s + ' div.asset-options').outerHeight();
                jQuery(s).animate({height: '+=' + h + 'px'}, 'slow');
                jQuery(s + ' div.asset-options div.options').animate({opacity: 1.0}, 1500);
            });
        });
    },

    // Item options
    item_options: function(el) {

        var id = navt_ns.get_id(el, '-alias-anchor');
        var options = new Array();

        jQuery('#' + id + ' div.item-spinner').addClass('waiting');
        jQuery.ajax({
            type: "POST",
            processData: false,
            url: navtajx,
            data: 'navtajx[action]=get_item_options&id='+id,

            success: function(response, status) {
                var s = '#' + id;
                jQuery(s + ' div.item-spinner').removeClass('waiting');
                var o = new Object();
                o = JSON.parse(response);

                if( o.rc == 'ok' ) {
                    jQuery(s + ' div.asset-options div.options').html(o.html);
                    //if( !ltIe7 ) {
                    //jQuery(s).uncorner();
                    //jQuery(s).corner('6px');
                    //}

                    navt_ns.item_alias_event(id, 'set_close');
                    navt_ns.init_form(id);
                    navt_ns.open_optionsbox(id);

                    // IE mouseovers
                    if( ltIe7 ) {
                        jQuery('a.ibtn-ok img, a.ibtn-can img, a.ibtn-help img').mouseover(
                        function() {
                            jQuery(this).css({backgroundPosition: '0 -16px'});
                        });
                        jQuery('a.ibtn-ok img, a.ibtn-can img, a.ibtn-help img').mouseout(
                        function() {
                            jQuery(this).css({backgroundPosition: '0 0'});
                        });
                    }

                    jQuery(s + '-ok').unbind().click(
                    function() {
                        var id = navt_ns.get_id(this, '-ok');
                        var s = '#' + id;
                        var opt = jQuery(s + '-form').serialize();

                        if( opt.length != 0 ) {
                            jQuery(s + ' div.item-spinner').addClass('waiting');
                            if(jQuery(s + '-errormsg').text() != '') {
                                jQuery(s + '-errormsg').text('').hide();
                                jQuery(s).animate({height: '-=25px'}, 500);
                                jQuery(s + ' input').removeClass('err');
                            }
                            jQuery.ajax({
                                type: "POST",
                                processData: false,
                                url: navtajx,
                                data: "navtajx[action]=set_item_options&id=" + id + '&' + opt,
                                success: function(response, status) {
                                    jQuery(s + ' div.item-spinner').removeClass('waiting');
                                    var o = new Object();
                                    o = JSON.parse(response);

                                    if( o.rc == 'ok' ) {
                                        if( o.isprivate == '1' ) {
                                            jQuery(s + ' div.asset-icon img.icon').addClass('private');
                                        }
                                        else {
                                            jQuery(s + ' div.asset-icon img.icon').removeClass('private');
                                        }
                                        var nme = navt_ns.set_alias_len(o.alias, o.level);
                                        jQuery(s + '-alias').val(o.alias);
                                        jQuery(s + ' div.asset-name p').text(o.alias);
                                        jQuery(s + ' a.alias-anchor').attr('title', o.alias).html(nme);
                                        jQuery(s + ' a.alias-anchor').trigger('click');
                                        var txt = "<?php _e('item options saved for','navt_domain');?> '" +
                                        o.alias + "' <?php _e('in group', 'navt_domain');?> '" + o.group + "'";
                                        navt_ns.add_log_text(txt);
                                    }
                                    else {
                                        // error
                                        jQuery(s).animate({height: '+=25px'}, 500,
                                        function() {
                                            jQuery(s + '-errormsg').text(':: ' + o.msg + ' ::').fadeIn('slow');
                                            jQuery(s + o.suffix).addClass('err').focus();
                                        });
                                    }
                                },
                                error: function() {
                                    jQuery(s + ' div.item-spinner').removeClass('waiting');
                                    navt_ns.add_log_text("e06 ajax error: " + error);
                                    console.error("navt => e06 ajax error: - server returned %s", error);
                                }
                            });
                        }
                        return(false);
                    });

                    jQuery(s + '-can').unbind().click(
                    function() {
                        jQuery(s + ' a.alias-anchor').trigger('click');
                        navt_ns.add_log_text("<?php _e('** cancelled ** (item save)', 'navt_domain');?>");
                        return(false);
                    });

                    jQuery(s + '-help a.ibtn-help').unbind().click(
                    function() {
                        var ar = navt_ns.crack_name(id);
                        navt_ns.help('itemtype-' + ar['type'] + ((ar['type'] == is_link) ? '-'+ar['idn']: ''));
                        return(false);
                    });

                    console.log("navt => options request for item: id %s", id);
                    return(false);
                }
                else {

                }
            },

            error: function(error) {
                jQuery('#' + id + ' div.item-spinner').removeClass('waiting');
                navt_ns.add_log_text("e07 ajax error: " + error);
                console.error("navt => e07 ajax error: - server returned %s", error);
            }
        });

        return(false);
    },

    // initialize the item options form
    init_form: function(id) {

        var ar = navt_ns.crack_name(id);
        var s = '#' + id;

        if( ar['type'] != is_author ) {
            jQuery(s + '-anchor-type').change(
            function() {
                var el = jQuery(this).attr('id');
                var t = navt_ns.crack_name(el);
                el = navt_ns.get_id(this, '-anchor-type');
                navt_ns.set_anchor_type(el);
                return(false);
            });
            navt_ns.set_anchor_type(id);
        }

        switch( ar['type'] ) {
            case is_page: {
                break;
            }

            case is_cat: {

                jQuery(s + '-anchor-type').unbind().change(
                function() {
                    var id = navt_ns.get_id(this, '-anchor-type');
                    navt_ns.set_cat_options(id);
                    navt_ns.set_anchor_type(id);
                    return(false);
                });
                navt_ns.set_cat_options(id);
                break;
            }

            case is_link: {

                var linktyp = jQuery(s + '-linktype').val();
                if( linktyp == 'admin' ) {
                    navt_ns.set_admin_options(id);
                    jQuery(s + '-cb2-admin').click(
                    function() {
                        var id = navt_ns.get_id(this, '-cb2-admin');
                        navt_ns.set_admin_options(id);
                        return(true);
                    });
                    jQuery(s + '-cb3-admin').click(
                    function() {
                        var id = navt_ns.get_id(this, '-cb3-admin');
                        navt_ns.set_admin_options(id);
                        return(true);
                    });
                    jQuery(s + '-anchor-type').unbind().change(
                    function() {
                        var id = navt_ns.get_id(this, '-anchor-type');
                        navt_ns.set_admin_options(id);
                        navt_ns.set_anchor_type(id);
                        return(false);
                    });
                }
                else if( linktyp == 'home' ) {
                }
                break;
            }

            case is_divider: {

                // initialize divider events
                navt_ns.set_divider_options(id);
                jQuery(s + '-divider-select').change(
                function() {
                    var id = navt_ns.get_id(this, '-divider-select');
                    navt_ns.set_divider_options(id);
                    return(false);
                });

                break;
            }
            case is_elink: {
                break;
            }
            case is_author: {

                // initialize author events
                navt_ns.set_author_options(id);

                jQuery(s + '-select').change(
                function() {
                    var id = navt_ns.get_id(this, '-select');
                    navt_ns.set_author_options(id);
                    return(false);
                });

                // show avatar
                jQuery(s + '-show-avatar').click(
                function() {
                    var id = navt_ns.get_id(this, '-show-avatar');
                    navt_ns.set_author_options(id);
                    return(true);
                });

                jQuery("input:radio").change(
                function() {
                    var s = jQuery(this).attr('value');
                    var id;

                    if( s == '1' ) {
                        id = navt_ns.get_id(this, '-use-default-avatar');
                    }
                    else if( s == '2' ) {
                        id = navt_ns.get_id(this, '-use-gravatar');
                    }
                    else if( s == '3' ) {
                        id = navt_ns.get_id(this, '-use-other-avatar');
                    }

                    navt_ns.set_author_options(id);
                    return(true);
                });

                // hide link text
                jQuery(s + '-hide-link-text').click(
                function() {
                    var id = navt_ns.get_id(this, '-hide-link-text');
                    navt_ns.set_author_options(id);
                    return(true);
                });

                break;
            }
            default: {
                break;
            }
        }
    },

    // sets anchor option
    set_anchor_type: function(id) {

        var s = '#' + id;
        var opt = parseInt( jQuery(s + '-anchor-type').val() ) & 0xffff;

        if( opt & (text_over_graphic | text_with_side_graphic | graphic_link) ) {
            jQuery(s + '-label-anchor-class').removeClass('disabled');
            jQuery(s + '-anchor-class').removeClass('disabled').attr('disabled', '').focus();
            if(ltIe7) {
                jQuery(s + '-anchor-class').css({backgroundColor: '#fefefe', border: '1px solid #888'});
            }
        }
        else {
            if(ltIe7) {
                jQuery(s + '-anchor-class').css({backgroundColor: '#eee', border: '1px solid #ccc'});
            }
            jQuery(s + '-label-anchor-class').addClass('disabled');
            jQuery(s + '-anchor-class').val('').addClass('disabled').attr('disabled', 'disabled');
        }
    },

    // set category options
    set_cat_options: function(id) {

        var s = '#' + id;
        var opt = parseInt( jQuery(s + '-anchor-type').val() ) & 0xffff;

        if( opt == graphic_link ) {
            jQuery(s + '-cb2-com-label').addClass('disabled');
            jQuery(s + '-cb2-com').attr('disabled', 'disabled').attr('checked', false);
        }
        else {
            jQuery(s + '-cb2-com-label').removeClass('disabled');
            jQuery(s + '-cb2-com').attr('disabled', '');
        }
    },

    // set divider options
    set_divider_options: function(id) {

        var str = '';
        var s = '#' + id;
        var opt = parseInt( jQuery(s + '-divider-select').val() ) & 0xffff;

        if( opt != is_plaintext ) {
            jQuery(s + '-aliasbox-label,' + s + '-aliasbox').addClass('disabled');
            jQuery(s + '-aliasbox').attr('disabled', 'disabled');
            if( opt == is_hrule ) {
                str = '<?php _e('horizontal rule', 'navt_domain'); ?>';
                jQuery(s + '-aliasbox').val(str);
            }
            else if( opt == is_empty_space ) {
                str = '<?php _e('empty space', 'navt_domain'); ?>';
                jQuery(s + '-aliasbox').val(str);
            }
        }
        else {
            jQuery(s + '-aliasbox-label,' + s + '-aliasbox').removeClass('disabled');
            jQuery(s + '-aliasbox').attr('disabled', '').val('').focus();
        }
    },

    // establish admin item options
    set_admin_options: function(id) {

        var s = '#' + id;
        var opt = parseInt( jQuery(s + '-anchor-type').val() ) & 0xffff;
        var use_referral_redirect = (jQuery(s + '-cb2-admin').attr('checked') == true) ? 1: 0;
        var use_url_redirect = (jQuery(s + '-cb3-admin').attr('checked') == true) ? 1: 0;

        if( opt == graphic_link ) {
            jQuery(s + '-cb1-admin').attr('checked', false).attr('disabled', 'disabled');
            jQuery(s + '-cb1-admin-label').addClass('disabled');
        }
        else {
            jQuery(s + '-cb1-admin-label').removeClass('disabled');
            jQuery(s + '-cb1-admin').attr('disabled', '');
        }

        // disable these first
        jQuery(s + '-url-redirect-label,' + s + '-url-redirect').addClass('disabled');
        jQuery(s + '-url-redirect').attr('disabled', 'disabled');

        if( use_referral_redirect ) {
            jQuery(s + '-cb3-admin').attr('checked', false).attr('disabled', 'disabled');
            jQuery(s + '-cb3-admin-label').addClass('disabled');
            jQuery(s + '-url-redirect').val('');
        }
        else if(use_url_redirect) {
            jQuery(s + '-cb2-admin').attr('checked', false).attr('disabled', 'disabled');
            jQuery(s + '-cb2-admin-label').addClass('disabled');
            jQuery(s + '-url-redirect-label,' + s + '-url-redirect').removeClass('disabled');
            jQuery(s + '-url-redirect').attr('disabled', '').focus();
        }
        else if( !use_referral_redirect && !use_url_redirect ) {
            jQuery(s + '-cb2-admin,'+ s + '-cb3-admin').attr('disabled', '');
            jQuery(s + '-cb2-admin-label,' + s + '-cb3-admin-label').removeClass('disabled');
            jQuery(s + '-url-redirect').val('');
        }
    },

    // establish author item options
    // fixed in v1.0.6 - radio button selections
    set_author_options: function(id) {

        var s = '#' + id;
        var show_avatar = (jQuery(s + '-show-avatar').attr('checked') == true) ? 1: 0;
        var use_default_avatar = (jQuery(s + '-use-default-avatar').attr('checked') == true) ? 1: 0;
        var use_gravatar = (jQuery(s + '-use-gravatar').attr('checked') == true) ? 1: 0;
        var use_other_avatar = (jQuery(s + '-use-other-avatar').attr('checked') == true) ? 1: 0;
        var hide_link_text = (jQuery(s + '-hide-link-text').attr('checked') == true) ? 1: 0;
        var img = jQuery(s + '-select').val();
        var opac = .3;

        var s1 = '@id@-append-post-count-label,@id@-inc-website-label,@id@-inc-bio-label,@id@-inc-email-label';
        var s2 = '@id@-append-post-count,@id@-inc-website,@id@-inc-bio,@id@-inc-email';
        var s3 = '@id@-hide-link-text-label,@id@-use-other-avatar-label,@id@-use-default-avatar-label,'+
        '@id@-use-gravatar-label,@id@-select-label,@id@-avbox-label';
        var s4 = '@id@-hide-link-text,@id@-use-default-avatar,@id@-use-other-avatar,@id@-use-gravatar,@id@-select,@id@-avbox';

        s1 = s1.replace(/@id@/gi, s);
        s2 = s2.replace(/@id@/gi, s);
        s3 = s3.replace(/@id@/gi, s);
        s4 = s4.replace(/@id@/gi, s);

        jQuery(s + ' li.img_avatar img').attr('src', img);

        if( !show_avatar ) {
            jQuery(s + '-use-default-avatar,' + s + '-hide-link-text,' + s + '-use-gravatar').attr('checked', false);
            jQuery(s3).addClass('disabled');
            jQuery(s4).attr('disabled', 'disabled');
        }
        else {
            if( !use_gravatar && !use_other_avatar ) {
                use_default_avatar = 1;
                jQuery(s + '-use-default-avatar').attr('checked', true);
            }

            jQuery(s4).attr('disabled', '');
            jQuery(s + '-hide-link-text-label,' + s + '-use-default-avatar-label').removeClass('disabled');

            if( use_default_avatar || use_gravatar ) {
                jQuery(s + '-select').attr('disabled', '');
                jQuery(s + '-select-label').removeClass('disabled');
                jQuery(s + '-avbox-label').addClass('disabled');
                jQuery(s + '-avbox').val('').attr('disabled', 'disabled');
                opac = 1;
            }
            else {
                jQuery(s + '-select').attr('disabled', 'disabled');
                jQuery(s + '-select-label').addClass('disabled');
                jQuery(s + '-avbox-label').removeClass('disabled');
                jQuery(s + '-avbox').attr('disabled', '').focus();
            }
        }

        jQuery(s + ' li.img_avatar img').css({opacity: opac});
        if( hide_link_text ) {
            jQuery(s1).addClass('disabled');
            jQuery(s2).attr('disabled', 'disabled').attr('checked', false);
        }
        else {
            jQuery(s1).removeClass('disabled');
            jQuery(s2).attr('disabled', '');
        }
    },

    // Change the item hierarchy
    set_item_level: function(el) {

        var tmp = jQuery(el).attr('id');
        tmp = tmp.split('-level-');

        jQuery.ajax({
            type: "POST",
            processData: false,
            url: navtajx,
            data: 'navtajx[action]=set_item_level&id='+tmp[0]+'&dir='+tmp[1],

            success: function(response, status) {
                var o = new Object();
                o = JSON.parse(response);

                if( o.rc == 'ok' ) {
                    var s = '#' + o.id;
                    var level = o.nl;
                    level = level.split('level-');
                    level = parseInt(level[1], 10);
                    var alias = jQuery(s + '-alias').val();
                    alias = navt_ns.set_alias_len(alias, level);
                    jQuery(s).removeClass(o.cl).addClass(o.nl);
                    jQuery(s + '-alias-anchor').text(alias);
                    console.log("navt => set item level: %s, cl: %s, nl: %s", o.id, o.cl, o.nl);
                }
                else {
                    // error?
                    console.log("navt => set item level - %s, msg %s", o.rc, o.msg);
                }
            },

            error: function(error) {
                navt_ns.add_log_text("e09 ajax error: " + error);
                console.error("navt => e09 ajax error: - server returned %s", error);
            }
        });

        return(false);
    },

    // Remove a navigation item
    remove_item: function(item) {

        var id = navt_ns.get_id(item, '-remove');
        if( jQuery('#' + id).hasClass('unassigned') ) {
            jQuery('#' + id).slideUp('slow',

            function() {
                navt_ns.update_unassigned_count(id, 'dec');
                navt_ns.add_log_text("<?php _e('unassigned item was removed', 'navt_domain');?>");
                jQuery('#' + id).remove();
                navt_ns.update_group_order();
                navt_ns.make_sortables(true);
            });
            return(false);
        }

        jQuery('#' + id + ' div.item-spinner').addClass('waiting');
        jQuery.ajax({
            type: "POST",
            processData: false,
            url: navtajx,
            data: 'navtajx[action]=ask_remove_item&id='+id,

            success: function(response, status) {
                jQuery('#' + id + ' div.item-spinner').removeClass('waiting');
                var o = new Object();
                o = JSON.parse(response);

                if( o.rc == 'ok' ) {

                    jQuery('#wintitle').html('<h3>' + o.title + '</h3>');
                    jQuery('#target').html(o.html);
                    jQuery('#dialog').modal({close:false});
                    var ww = jQuery(window).width();
                    var loffset = (ww-o.width)/2;
                    jQuery('#modalContainer').animate({top:'40%', left:loffset, width:o.width+'px', height:o.height+'px'}, 'fast');

                    jQuery('#' + o.id + '-ok').unbind().click(
                    function() {
                        var id = navt_ns.get_id(this, '-ok');
                        navt_ns.update_unassigned_count(id, 'dec');
                        jQuery('#'+id).slideUp("slow",
                        function() {
                            jQuery('#'+id).remove();
                            navt_ns.update_group_order();
                            navt_ns.make_sortables(true);
                            var txt = "'" + o.alias + "' <?php _e('in group','navt_domain');?> '" +
                            o.group + "' <?php _e('was removed', 'navt_domain');?>";
                            navt_ns.add_log_text(txt);
                        });
                        jQuery.modal.close();
                        return(false);
                    });

                    jQuery('#' + o.id + '-can').unbind().click(
                    function() {
                        jQuery.modal.close();
                        navt_ns.add_log_text("<?php _e('** cancelled ** (item remove)', 'navt_domain');?>");
                        return(false);
                    });
                }
                else {
                    jQuery('#' + id + ' div.item-spinner').removeClass('waiting');
                    // error ?
                }
            },

            error: function() {
                jQuery('#' + id + ' div.item-spinner').removeClass('waiting');
                console.error("navt => e0A ajax error: - server returned %s", error);
                navt_ns.add_log_text("e0A ajax error: " + error);
            }
        });

        return(false);
    },

    // disconnect an item
    disc_item: function(item) {

        var id = navt_ns.get_id(item, '-disc');
        jQuery.ajax({
            type: "POST",
            processData: false,
            url: navtajx,
            data: 'navtajx[action]=set_item_disc&id=' + id,

            success: function(response, status) {
                var o = new Object();
                o = JSON.parse(response);

                if( o.rc == 'ok' ) {
                    var s = '#' + o.id;
                    var what = "<?php _e('is disconnected', 'navt_domain');?>";

                    if( o.disc == '1' ) {
                        jQuery(s + ' .asset-disc').addClass('disconnected');
                        jQuery(s).addClass('disconnected');
                    }
                    else {
                        jQuery(s + ' .asset-disc').removeClass('disconnected');
                        jQuery(s).removeClass('disconnected');
                        what = "<?php _e('is connected', 'navt_domain');?>";
                    }
                    var name = jQuery(s + '-alias-anchor').text();
                    var txt = "'" + o.alias + "' <?php _e('in group','navt_domain');?> '" + o.group + "' " + what;
                    navt_ns.add_log_text(txt);
                    console.log("navt => id: %s, set disc: %s", o.id, o.disc);
                }
                else {
                    // error?
                    console.log("navt => set disc id %s, msg %s", o.id, o.msg);
                }
            },

            error: function(error) {
                navt_ns.add_log_text("e0B ajax error: " + error);
                console.error("navt => e0B ajax error: - server returned %s", error);
            }
        });

        return(false);
    },

    // -----------------------------------------------------------------------
    // Misc stuff
    // -----------------------------------------------------------------------

    sort_assets: function(args, type) {

        jQuery.ajax({
            type: "POST",
            processData: false,
            url: navtajx,
            data: 'navtajx[action]=sort_assets'+args,

            success: function(response, status) {
                var o = new Object();
                o = JSON.parse(response);

                if( o.rc == 'ok' ) {
                    var selector = 'notset';

                    if( type == 'cat' ) {
                        selector = '#asset-cat';
                    }
                    else if( type == 'page' ) {
                        selector = '#asset-page';
                    }
                    else if( type == 'user' ) {
                        selector = '#asset-usr';
                    }

                    if(selector != 'notset') {
                        var sel = jQuery(selector);
                        jQuery(selector).html(o.html);
                        jQuery(selector).attr('selectedIndex', 0);
                    }

                    console.log("navt => sorted assets");
                }
                else {
                    // error?
                    console.log("navt => sort_assets, msg %s", o.msg);
                }
            },

            error: function(error) {
                navt_ns.add_log_text("e2B ajax error: " + error);
                console.error("navt => e2B ajax error: - server returned %s", error);
            }
        });

    },

    // truncate the alias
    set_alias_len: function(alias, level) {

        var len = (21-level);
        var s = alias;
        if( alias.length > len ) {
            s = s.substring(0, len) + '...';
        }
        return(s);
    },

    // returns the root selector id
    get_id: function(el, str) {

        var id = jQuery(el).attr('id');
        id = id.split(str);
        id = id[0];
        return(id);
    },

    // crack_name -
    // returns an array containing the navigation type and idn number
    crack_name: function(name) {

        var ret = new Array();
        var ar1 = name.split('--');
        var ar2 = ar1[0].split('-');
        ret['type'] = ar2[1];
        ret['idn']  = ar2[2];
        return(ret);
    },

    get_base_id: function(name) {

        var id = name.split('--');
        return(id[0]);
    },

    // add a string to the lo
    add_log_text: function(str) {

        if( jQuery('#list-updates').css('display') != 'none' ) {
            var timestamp = navt_ns.get_date_time();
            jQuery('#list-updates option').each(
            function() {
                jQuery(this).removeClass('current');
            });
            var opt = "<option class='current' value=''>"+timestamp + '&nbsp;'+ str + "</option>";
            jQuery('#list-updates').prepend(opt);
        }
    },

    // returns a timestamp for the log
    get_date_time: function() {

        var currentTime = new Date();
        var hh = currentTime.getHours();   if( hh < 10 ) {hh = '0' + hh;}
        var mm = currentTime.getMinutes(); if( mm < 10 ) {mm = '0' + mm;}
        var ss = currentTime.getSeconds(); if( ss < 10 ) {ss = '0' + ss;}
        return( hh + ':' + mm + ':' + ss );
    },

    //
    /** -- Toolbar functions -- **/
    //

    // backup navigation groups
    backup_group: function() {
        var this_page = window.location;
        var page = this_page.href;
        page = page.replace(/#/gi,'');
        window.location = page + '&navtbackuprequest';
        return(false);
    },

    // restore navigation groups
    restore_group: function() {
        var this_page = window.location;
        var page = this_page.href;
        page = page.replace(/#/gi,'');
        window.location = page + '&navtrestorerequest';
        return(false);
    },

    // get help information
    help: function(subject) {

        jQuery.ajax({
            type: "POST",
            processData: false,
            url: navtajx,
            data: 'navtajx[action]=navt_help&subject='+subject+'&ltIe7='+ltIe7,

            success: function(response, status) {
                var o = new Object();
                o = JSON.parse(response);

                if( o.rc == 'ok' ) {
                    jQuery('#wintitle').html('<h3>' + o.title + '</h3>');
                    jQuery('#target').html(o.html);
                    jQuery('#dialog').modal();
                    var ww = jQuery(window).width();
                    var loffset = (ww-o.width)/2;

                    // setup paging
                    navt_ns.init_help_paging();
                    jQuery('#modalContainer').animate({left:loffset, width:o.width+'px', height:o.height+'px'}, 'fast');
                }
                else {
                    // error?
                    console.log("navt => help-%s: msg %s", subject, o.msg);
                }
            },

            error: function(error) {
                navt_ns.add_log_text("e12 ajax error: " + error);
                console.error("navt => e12 ajax error: - server returned %s", error);
            }
        });

        return false;
    },

    adjust_help_window: function() {
        jQuery(this).css({
            width: o.w+'px',
            height: o.h+'px'
        });
    },

    // Set paging for help pages
    init_help_paging: function() {

        for( var p = 1; p <= 6; p++ ) {
            navt_ns.page_forward(p);
            navt_ns.page_back(p);
        }
    },

    // back page event
    page_back: function(page) {

        if( page >= 2 ) {
            // page n => page n-1
            jQuery('#p'+page+'p').click(
            function() {
                if( jQuery('#p'+page+'n') ) {
                    jQuery('#p'+page+'n').fadeOut('slow');
                }
                jQuery('#p'+page+'p, #p'+page).fadeOut('slow',
                function() {
                    var ppage = page-1;
                    jQuery('#p'+ppage+'p, #p'+ppage+'n, #p'+ppage).fadeIn('slow');
                });
                return(false);
            });
        }
    },

    // page forward event
    page_forward: function(page) {

        if( page <= 5 ) {
            // page n => page n+1
            if( jQuery('#p'+page+'n') ) {
                jQuery('#p'+page+'n').click(
                function() {
                    jQuery('#p'+page+'p, #p'+page+'n, #p'+page).fadeOut('slow',
                    function() {
                        var npage = page+1;
                        jQuery('#p'+npage+'p, #p'+npage).fadeIn('slow');
                        if( jQuery('#p'+npage+'n') ) {
                            jQuery('#p'+npage+'n').fadeIn('slow');
                        }
                    });
                    return(false);
                });
            }
        }
    }


}// end name space

// Document ready
jQuery.noConflict();
jQuery(document).ready( function () {


    // Firebug
    if (!window.console || !console.firebug) {
        var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml",
        "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];

        window.console = {};
        for (var i = 0; i < names.length; ++i) {
            window.console[names[i]] = function() {}
        }
    }

    // it all starts here
    navt_ns.init(navtpath);
})
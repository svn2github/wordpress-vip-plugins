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
* @author Greg A. Bellucci greg[AT]gbellucci[DOT]us
* @copyright Copyright &copy; 2006-2008 Greg A. Bellucci
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
*/

jQuery.noConflict();
jQuery(document).ready(
function () {

   /* This routine steals the wpnonce hash value from the deactivate anchor for
    * NAVT and adds it to the NAVT uninstall anchor. The wpnonce value is needed
    * to successfully deactivate the plugin from the app/navt_utl.php script.
    * Probably wouldn't be required if the wp_nonce_ays function in wp-includes/functions.php
    * actually worked correctly.
    */
    function add_to_plugin_block(el) {

        var html = jQuery('div.navtinfo').html();
        var pn = el.parentNode;
        jQuery(pn).attr('id', 'tr_navt');

        // determine if the plugin is active
        var t = jQuery('#tr_navt .togl a').attr('title');
        var is_match = t.match(/Deactivate/gi);

        if(is_match != null) {
            jQuery(html).appendTo('#tr_navt td.desc');
            //jQuery('#tr_navt td.desc').appendTo(html);
            jQuery('#tr_navt td.togl a.delete').each(
            function() {
                // get the wpnonce from the deactivate anchor
                var anchor = jQuery(this).attr('href');
                var wpnonce = anchor.split('wpnonce=');

                jQuery('.navt_uninstall').each(
                function() {
                    // substitute @once@ with the hash value
                    var href = jQuery(this).attr('href');
                    href = href.replace(/@once@/gi, wpnonce[wpnonce.length-1]);
                    jQuery(this).attr('href', href);
                });
            });
        }
        jQuery('div.navtinfo').remove();
        jQuery('#tr_navt').attr('id','');
    }

    // wp 2.3+
    jQuery("table.plugins td.desc:contains('NAVT Lists')").each(
    function() {
        add_to_plugin_block(this);
    });

    // wp 2.5 */
    jQuery("#plugins td.desc:contains('NAVT Lists')").each(
    function() {
        add_to_plugin_block(this);
    });
});



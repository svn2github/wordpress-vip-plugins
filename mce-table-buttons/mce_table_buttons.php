<?php
/**
 Plugin Name: MCE Table Buttons
 Plugin URI: http://www.get10up.com/plugins-modules/wordpress-mce-table-buttons/
 Description: Add <strong>buttons for table editing</strong> to the WordPress WYSIWYG editor with this <strong>light weight</strong> plug-in.    
 Version: 1.5
 Author: Jake Goldman (10up LLC)
 Author URI: http://get10up.com

    Plugin: Copyright 2011 Jake Goldman (email : jake@get10up.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
    NOTE: Previous versions may have had their copyright incorrectly attributed 
	to employers of Mr. Goldman. The copyright belongs solely to Mr. Goldman,
	personally.
*/

class MCE_Table_Buttons {
	public function __construct() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'content_save_pre', array( $this, 'content_save_pre'), 100 );
		add_action( 'admin_footer', array( $this, 'admin_footer' ), 100 );
	}
	
	public function admin_init() {
		add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
		add_filter( 'mce_buttons_3', array( $this, 'mce_buttons_3' ) );
	}
	
	public function mce_external_plugins( $plugin_array ) {
		$plugin_array['table'] = plugin_dir_url( __FILE__ ) . '/table/editor_plugin.js';
   		return $plugin_array;
	}
	
	public function mce_buttons_3( $buttons ) {
		array_push( $buttons, 'tablecontrols' );
   		return $buttons;
	}
	
	public function admin_footer() {
		if ( ! wp_script_is( 'editor' ) )
			return;		
	?>
	<script type="text/javascript">
	jQuery(window).load(function(){ 
		jQuery('.mceToolbarRow2').each(function(){
			if(!jQuery(this).is(':visible')) jQuery(this).siblings('.mceToolbarRow3').hide();
		});
		jQuery('.mce_wp_adv').click(function(){ 
			var toolbar3 = jQuery(this).closest('table').siblings('.mceToolbarRow3');
			if ( jQuery(this).hasClass('mceButtonActive') ) toolbar3.show();
			else toolbar3.hide(); 
		});
	});
	</script>
	<?php
	}
	
	public function content_save_pre( $content ) {
		if ( substr( $content, -8 ) == '</table>' )
			$content = $content . "\n<br />";
		
		return $content;
	}
}

$mce_table_buttons = new MCE_Table_Buttons;

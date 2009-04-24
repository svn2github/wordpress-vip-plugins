<?php
/*
Plugin Name: Breadcrumb NavXT - Adminstration Interface
Plugin URI: http://mtekk.weblogs.us/code/breadcrumb-navxt/
Description: Adds a breadcrumb navigation showing the visitor&#39;s path to their current location. This enables the administrative interface for specifying the output of the breadcrumb trail. For details on how to use this plugin visit <a href="http://mtekk.weblogs.us/code/breadcrumb-navxt/">Breadcrumb NavXT</a>. 
Version: 3.1.0
Author: John Havlik
Author URI: http://mtekk.weblogs.us/
*/
/*  Copyright 2007-2009  John Havlik  (email : mtekkmonkey@gmail.com)

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
*/
//Include the breadcrumb class (if needed)
if(!class_exists('bcn_breadcrumb'))
{
	require_once(dirname(__FILE__) . '/breadcrumb_navxt_class.php');
}
//Include the supplemental functions
require_once(dirname(__FILE__) . '/breadcrumb_navxt_api.php');

//The administrative interface class
class bcn_admin
{
	private $version;
	private $breadcrumb_trail;
	/**
	 * bcn_admin
	 * 
	 * Administrative interface class default constructor
	 */
	function bcn_admin()
	{
		//Setup our internal version
		$this->version = "3.1.0";
		//We'll let it fail fataly if the class isn't there as we depend on it
		$this->breadcrumb_trail = new bcn_breadcrumb_trail;
		//Installation Script hook
		add_action('activate_breadcrumb-navxt/breadcrumb_navxt_admin.php', array(&$this, 'install'));
		//Uninstallation Script hook
		if(function_exists('register_uninstall_hook'))
		{
			register_uninstall_hook(__FILE__, array(&$this, 'uninstall'));
		}
		//WordPress Admin interface hook
		add_action('admin_menu', array(&$this, 'add_page'));
		//WordPress Admin headder hook
		add_action('admin_head', array(&$this, 'admin_head'));
		//WordPress JS enquery hook
		add_action('wp_print_scripts', array(&$this, 'javascript'));
		//WordPress Hook for the widget
		add_action('plugins_loaded', array(&$this, 'register_widget'));
		//Admin Options hook
		if(isset($_POST['bcn_admin_options']))
		{
			add_action('init', array(&$this, 'update'));
		}
	}
	/**
	 * security
	 * 
	 * Makes sure the current user can manage options to proceed
	 */
	function security()
	{
		//If the user can not manage options we will die on them
		if(!current_user_can('manage_options'))
		{
			_e('Insufficient privileges to proceed.', 'breadcrumb_navxt');
			die();
		}
	}
	/**
	 * install
	 * 
	 * This sets up and upgrades the database settings, runs on every activation
	 */
	function install()
	{
		//Call our little security function
		$this->security();
		//Initilize the options
		$this->breadcrumb_trail = new bcn_breadcrumb_trail;
		//Reduce db queries by saving this
		$db_version = $this->get_option('bcn_version');
		//If our version is not the same as in the db, time to update
		if($db_version !== $this->version)
		{
			//Split up the db version into it's components
			list($major, $minor, $release) = explode('.', $db_version);
			//For upgrading from 2.x.x
			if($major == 2)
			{
				//Upgrade to a current option
				$this->breadcrumb_trail->opt['home_title'] = $this->get_option('bcn_title_blog');
				$this->breadcrumb_trail->opt['home_display'] =  $this->get_option('bcn_home_display');
				$this->breadcrumb_trail->opt['404_title'] = $this->get_option('bcn_title_404');
				$this->breadcrumb_trail->opt['post_taxonomy_type'] = $this->get_option('bcn_singleblogpost_taxonomy');
				$this->breadcrumb_trail->opt['post_taxonomy_display'] = $this->get_option('bcn_singleblogpost_taxonomy_display');
				$this->breadcrumb_trail->opt['category_prefix'] = $this->get_option('bcn_singleblogpost_category_prefix');
				$this->breadcrumb_trail->opt['category_suffix'] = $this->get_option('bcn_singleblogpost_category_suffix');
				$this->breadcrumb_trail->opt['tag_prefix'] = $this->get_option('bcn_singleblogpost_tag_prefix');
				$this->breadcrumb_trail->opt['tag_suffix'] = $this->get_option('bcn_singleblogpost_tag_suffix');
				$this->breadcrumb_trail->opt['current_item_linked'] = $this->get_option('bcn_link_current_item');
				$this->breadcrumb_trail->opt['post_prefix'] = $this->get_option('bcn_singleblogpost_prefix');
				$this->breadcrumb_trail->opt['post_suffix'] = $this->get_option('bcn_singleblogpost_suffix');
				$this->breadcrumb_trail->opt['current_item_prefix'] = $this->get_option('bcn_singleblogpost_style_prefix');
				$this->breadcrumb_trail->opt['current_item_suffix'] = $this->get_option('bcn_singleblogpost_style_suffix');
				$this->breadcrumb_trail->opt['max_title_length'] = $this->get_option('bcn_posttitle_maxlen');
				$this->delete_option('bcn_preserve');
				$this->delete_option('bcn_static_frontpage');
				$this->delete_option('bcn_url_blog');
				$this->delete_option('bcn_home_display');
				$this->delete_option('bcn_home_link');
				$this->delete_option('bcn_title_home');
				$this->delete_option('bcn_title_blog');
				$this->delete_option('bcn_separator');
				$this->delete_option('bcn_search_prefix');
				$this->delete_option('bcn_search_suffix');
				$this->delete_option('bcn_author_prefix');
				$this->delete_option('bcn_author_suffix');
				$this->delete_option('bcn_author_display');
				$this->delete_option('bcn_singleblogpost_prefix');
				$this->delete_option('bcn_singleblogpost_suffix');
				$this->delete_option('bcn_page_prefix');
				$this->delete_option('bcn_page_suffix');
				$this->delete_option('bcn_urltitle_prefix');
				$this->delete_option('bcn_urltitle_suffix');
				$this->delete_option('bcn_archive_category_prefix');
				$this->delete_option('bcn_archive_category_suffix');
				$this->delete_option('bcn_archive_date_prefix');
				$this->delete_option('bcn_archive_date_suffix');
				$this->delete_option('bcn_archive_date_format');
				$this->delete_option('bcn_attachment_prefix');
				$this->delete_option('bcn_attachment_suffix');
				$this->delete_option('bcn_archive_tag_prefix');
				$this->delete_option('bcn_archive_tag_suffix');
				$this->delete_option('bcn_title_404');
				$this->delete_option('bcn_link_current_item');
				$this->delete_option('bcn_current_item_urltitle');
				$this->delete_option('bcn_current_item_style_prefix');
				$this->delete_option('bcn_current_item_style_suffix');
				$this->delete_option('bcn_posttitle_maxlen');
				$this->delete_option('bcn_paged_display');
				$this->delete_option('bcn_paged_prefix');
				$this->delete_option('bcn_paged_suffix');
				$this->delete_option('bcn_singleblogpost_taxonomy');
				$this->delete_option('bcn_singleblogpost_taxonomy_display');
				$this->delete_option('bcn_singleblogpost_category_prefix');
				$this->delete_option('bcn_singleblogpost_category_suffix');
				$this->delete_option('bcn_singleblogpost_tag_prefix');
				$this->delete_option('bcn_singleblogpost_tag_suffix');
			}
			else if($major == 3 && $minor == 0)
			{
				//Update our internal settings
				$this->breadcrumb_trail->opt = $this->get_option('bcn_options');
				$this->breadcrumb_trail->opt['search_anchor'] = __('<a title="Go to the first page of search results for %title%." href="%link%">','breadcrumb_navxt');
			}
			//Always have to update the version
			$this->update_option('bcn_version', $this->version);
			//Store the options
			$this->add_option('bcn_options', $this->breadcrumb_trail->opt);
		}
		//Check if we have valid anchors
		if($temp = $this->get_option('bcn_options'))
		{
			//Missing the blog anchor is a bug from 3.0.0/3.0.1 so we soft error that one
			if(strlen($temp['blog_anchor']) == 0)
			{
				$temp['blog_anchor'] = $this->breadcrumb_trail->opt['blog_anchor'];
				$this->update_option('bcn_options', $temp);
			}
			else if(strlen($temp['home_anchor']) == 0 || 
				strlen($temp['blog_anchor']) == 0 || 
				strlen($temp['page_anchor']) == 0 || 
				strlen($temp['post_anchor']) == 0 || 
				strlen($temp['tag_anchor']) == 0 ||
				strlen($temp['date_anchor']) == 0 ||
				strlen($temp['category_anchor']) == 0)
			{
				$this->delete_option('bcn_options');
				$this->add_option('bcn_options', $this->breadcrumb_trail->opt);
			}
		}
	}
	/**
	 * uninstall
	 * 
	 * This removes database settings upon deletion of the plugin from WordPress
	 */
	function uninstall()
	{
		//Call our little security function
		$this->security();
		//Remove the option array setting
		$this->delete_option('bcn_options');
		//Remove the version setting
		$this->delete_option('bcn_version');
	}
	/**
	 * update
	 * 
	 * Updates the database settings from the webform
	 */
	function update()
	{
		$this->security();
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer('bcn_admin_options');
		//Grab the options from the from post
		//Home page settings
		$this->breadcrumb_trail->opt['home_display'] = str2bool(bcn_get('home_display', 'false'));
		$this->breadcrumb_trail->opt['home_title'] = bcn_get('home_title');
		$this->breadcrumb_trail->opt['home_anchor'] = bcn_get('home_anchor');
		$this->breadcrumb_trail->opt['blog_anchor'] = bcn_get('blog_anchor');
		$this->breadcrumb_trail->opt['home_prefix'] = bcn_get('home_prefix');
		$this->breadcrumb_trail->opt['home_suffix'] = bcn_get('home_suffix');
		$this->breadcrumb_trail->opt['separator'] = bcn_get('separator');
		$this->breadcrumb_trail->opt['max_title_length'] = bcn_get('max_title_length');
		//Current item settings
		$this->breadcrumb_trail->opt['current_item_linked'] = str2bool(bcn_get('current_item_linked', 'false'));
		$this->breadcrumb_trail->opt['current_item_anchor'] = bcn_get('current_item_anchor');
		$this->breadcrumb_trail->opt['current_item_prefix'] = bcn_get('current_item_prefix');
		$this->breadcrumb_trail->opt['current_item_suffix'] = bcn_get('current_item_suffix');
		//Paged settings
		$this->breadcrumb_trail->opt['paged_prefix'] = bcn_get('paged_prefix');
		$this->breadcrumb_trail->opt['paged_suffix'] = bcn_get('paged_suffix');
		$this->breadcrumb_trail->opt['paged_display'] = str2bool(bcn_get('paged_display', 'false'));
		//Page settings
		$this->breadcrumb_trail->opt['page_prefix'] = bcn_get('page_prefix');
		$this->breadcrumb_trail->opt['page_suffix'] = bcn_get('page_suffix');
		$this->breadcrumb_trail->opt['page_anchor'] = bcn_get('page_anchor');
		//Post related options
		$this->breadcrumb_trail->opt['post_prefix'] = bcn_get('post_prefix');
		$this->breadcrumb_trail->opt['post_suffix'] = bcn_get('post_suffix');
		$this->breadcrumb_trail->opt['post_anchor'] = bcn_get('post_anchor');
		$this->breadcrumb_trail->opt['post_taxonomy_display'] = str2bool(bcn_get('post_taxonomy_display', 'false'));
		$this->breadcrumb_trail->opt['post_taxonomy_type'] = bcn_get('post_taxonomy_type');
		//Attachment settings
		$this->breadcrumb_trail->opt['attachment_prefix'] = bcn_get('attachment_prefix');
		$this->breadcrumb_trail->opt['attachment_suffix'] = bcn_get('attachment_suffix');
		//404 page settings
		$this->breadcrumb_trail->opt['404_prefix'] = bcn_get('404_prefix');
		$this->breadcrumb_trail->opt['404_suffix'] = bcn_get('404_suffix');
		$this->breadcrumb_trail->opt['404_title'] = bcn_get('404_title');
		//Search page settings
		$this->breadcrumb_trail->opt['search_prefix'] = bcn_get('search_prefix');
		$this->breadcrumb_trail->opt['search_suffix'] = bcn_get('search_suffix');
		$this->breadcrumb_trail->opt['search_anchor'] = bcn_get('search_anchor');
		//Tag settings
		$this->breadcrumb_trail->opt['tag_prefix'] = bcn_get('tag_prefix');
		$this->breadcrumb_trail->opt['tag_suffix'] = bcn_get('tag_suffix');
		$this->breadcrumb_trail->opt['tag_anchor'] = bcn_get('tag_anchor');
		//Author page settings
		$this->breadcrumb_trail->opt['author_prefix'] = bcn_get('author_prefix');
		$this->breadcrumb_trail->opt['author_suffix'] = bcn_get('author_suffix');
		$this->breadcrumb_trail->opt['author_display'] = bcn_get('author_display');
		//Category settings
		$this->breadcrumb_trail->opt['category_prefix'] = bcn_get('category_prefix');
		$this->breadcrumb_trail->opt['category_suffix'] = bcn_get('category_suffix');
		$this->breadcrumb_trail->opt['category_anchor'] = bcn_get('category_anchor');
		//Archive settings
		$this->breadcrumb_trail->opt['archive_category_prefix'] = bcn_get('archive_category_prefix');
		$this->breadcrumb_trail->opt['archive_category_suffix'] = bcn_get('archive_category_suffix');
		$this->breadcrumb_trail->opt['archive_tag_prefix'] = bcn_get('archive_tag_prefix');
		$this->breadcrumb_trail->opt['archive_tag_suffix'] = bcn_get('archive_tag_suffix');
		//Archive by date settings
		$this->breadcrumb_trail->opt['date_anchor'] = bcn_get('date_anchor');
		$this->breadcrumb_trail->opt['archive_date_prefix'] = bcn_get('archive_date_prefix');
		$this->breadcrumb_trail->opt['archive_date_suffix'] = bcn_get('archive_date_suffix');
		//Commit the option changes
		$this->update_option('bcn_options', $this->breadcrumb_trail->opt);
	}
	/**
	 * display
	 * 
	 * Outputs the breadcrumb trail
	 * 
	 * @param  (bool)   $return Whether to return or echo the trail.
	 * @param  (bool)   $linked Whether to allow hyperlinks in the trail or not.
	 */
	function display($return = false, $linked = true)
	{
		//Update our internal settings
		$this->breadcrumb_trail->opt = $this->get_option('bcn_options');
		//Generate the breadcrumb trail
		$this->breadcrumb_trail->fill();
		//Display the breadcrumb trail
		$this->breadcrumb_trail->display($return, $linked);
	}
	/**
	 * filter_plugin_actions
	 * 
	 * Places in a link to the settings page on the plugins listing
	 * 
	 * @param  (array)   $links An array of links that are output in the listing
	 * @param  (string)   $file The file that is currently in processing
	 */
	function filter_plugin_actions($links, $file)
	{
		static $this_plugin;
		if(!$this_plugin)
		{
			$this_plugin = plugin_basename(__FILE__);
		}
		//Make sure we are adding only for Breadcrumb NavXT
		if($file == $this_plugin)
		{
			//Setup the link string
			$settings_link = '<a href="options-general.php?page=breadcrumb-navxt">' . __('Settings') . '</a>';
			//Add it to the beginning of the array
			array_unshift($links, $settings_link);
		}
		return $links;
	}
	/**
	 * add_page
	 * 
	 * Adds the adminpage the menue and the nice little settings link
	 * 
	 */
	function add_page()
	{
		global $bcn_admin_req;
		//We did away with bcn_security in favor of this nice thing
		if(current_user_can('manage_options'))
		{
			//Add the submenu page to "settings", more robust than previous method
			add_submenu_page('options-general.php', 'Breadcrumb NavXT Settings', 'Breadcrumb NavXT', 'manage_options', 'breadcrumb-navxt', array(&$this, 'admin_panel'));
			//Add in the nice "settings" link to the plugins page
			add_filter('plugin_action_links', array(&$this, 'filter_plugin_actions'), 10, 2);
		}
	}
	/**
	 * admin_panel
	 * 
	 * The administrative panel for Breadcrumb NavXT
	 * 
	 */
	function admin_panel()
	{
		$this->security();
		//Update our internal options array, use form safe function
		$this->breadcrumb_trail->opt = $this->get_option('bcn_options', true);
		//Initilizes l10n domain	
		$this->local();
		//See if the administrative interface matches versions with the class, if not then warn the user
		list($bcn_plugin_major, $bcn_plugin_minor, $bcn_plugin_bugfix) = explode('.', $this->breadcrumb_trail->version);	
		list($bcn_admin_major,  $bcn_admin_minor,  $bcn_admin_bugfix)  = explode('.', $this->version);		
		if($bcn_plugin_major != $bcn_admin_major || $bcn_plugin_minor != $bcn_admin_minor)
		{
			?>
			<div id="message" class="updated fade">
				<p><?php _e('Warning, your version of Breadcrumb NavXT does not match the version supported by this administrative interface. As a result, settings may not work as expected.', 'breadcrumb_navxt'); ?></p>
				<p><?php _e('Your Breadcrumb NavXT Administration interface version is ', 'breadcrumb_navxt'); echo $this->version; ?>.</p>
				<p><?php _e('Your Breadcrumb NavXT version is ', 'breadcrumb_navxt'); echo $this->breadcrumb_trail->version; ?>.</p>
			</div>
			<?php 
		} ?>
		<div class="wrap"><h2><?php _e('Breadcrumb NavXT Settings', 'breadcrumb_navxt'); ?></h2>
		<p><?php 
			printf(__('Tips for the settings are located below select options. Please refer to the %sdocumentation%s for more information.', 'breadcrumb_navxt'), 
			'<a title="' . __('Go to the Breadcrumb NavXT online documentation', 'breadcrumb_navxt') . '" href="http://mtekk.weblogs.us/code/breadcrumb-navxt/breadcrumb-navxt-doc/">', '</a>'); 
		?></p>
		<form action="options-general.php?page=breadcrumb-navxt" method="post" id="bcn_admin_options">
			<?php wp_nonce_field('bcn_admin_options');?>
			<div id="hasadmintabs">
			<fieldset id="general" class="bcn_options">
				<h3><?php _e('General', 'breadcrumb_navxt'); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="separator"><?php _e('Breadcrumb Separator', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="separator" id="separator" value="<?php echo $this->breadcrumb_trail->opt['separator']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Placed in between each breadcrumb.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="max_title_length"><?php _e('Breadcrumb Max Title Length', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="max_title_length" id="max_title_length" value="<?php echo $this->breadcrumb_trail->opt['max_title_length'];?>" size="10" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Home Breadcrumb', 'breadcrumb_navxt'); ?>						
						</th>
						<td>
							<label>
								<input name="home_display" type="checkbox" id="current_item_linked" value="true" <?php checked(true, $this->breadcrumb_trail->opt['home_display']); ?> />
								<?php _e('Place the home breadcrumb in the trail.', 'breadcrumb_navxt'); ?>				
							</label><br />
							<ul>
								<li>
									<label for="home_title">
										<?php _e('Home Title: ','breadcrumb_navxt');?>
										<input type="text" name="home_title" id="home_title" value="<?php echo $this->breadcrumb_trail->opt['home_title']; ?>" size="20" />
									</label>
								</li>
							</ul>							
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="home_prefix"><?php _e('Home Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="home_prefix" id="home_prefix" value="<?php echo $this->breadcrumb_trail->opt['home_prefix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="home_suffix"><?php _e('Home Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="home_suffix" id="home_suffix" value="<?php echo $this->breadcrumb_trail->opt['home_suffix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="home_anchor"><?php _e('Home Anchor', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="home_anchor" id="home_anchor" value="<?php echo $this->breadcrumb_trail->opt['home_anchor']; ?>" size="60" /><br />
							<span class="setting-description"><?php _e('The anchor template for the home breadcrumb.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<?php 
					//We only need this if in a static front page condition
					if($this->get_option('show_on_front') == "page")
					{?>
					<tr valign="top">
						<th scope="row">
							<label for="blog_anchor"><?php _e('Blog Anchor', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="blog_anchor" id="blog_anchor" value="<?php echo $this->breadcrumb_trail->opt['blog_anchor']; ?>" size="60" /><br />
							<span class="setting-description"><?php _e('The anchor template for the blog breadcrumb, used only in static front page environments.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr> 
					<?php } ?>
				</table>
			</fieldset>
			<fieldset id="current" class="bcn_options">
				<h3><?php _e('Current Item', 'breadcrumb_navxt'); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="current_item_linked"><?php _e('Link Current Item', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<label>
								<input name="current_item_linked" type="checkbox" id="current_item_linked" value="true" <?php checked(true, $this->breadcrumb_trail->opt['current_item_linked']); ?> />
								<?php _e('Yes'); ?>							
							</label>					
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="current_item_prefix"><?php _e('Current Item Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="current_item_prefix" id="current_item_prefix" value="<?php echo $this->breadcrumb_trail->opt['current_item_prefix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('This is always placed in front of the last breadcrumb in the trail, before any other prefixes for that breadcrumb.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="current_item_suffix"><?php _e('Current Item Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="current_item_suffix" id="current_item_suffix" value="<?php echo $this->breadcrumb_trail->opt['current_item_suffix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('This is always placed after the last breadcrumb in the trail, and after any other prefixes for that breadcrumb.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="current_item_anchor"><?php _e('Current Item Anchor', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="current_item_anchor" id="current_item_anchor" value="<?php echo $this->breadcrumb_trail->opt['current_item_anchor']; ?>" size="60" /><br />
							<span class="setting-description"><?php _e('The anchor template for current item breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="paged_display"><?php _e('Paged Breadcrumb', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<label>
								<input name="paged_display" type="checkbox" id="paged_display" value="true" <?php checked(true, $this->breadcrumb_trail->opt['paged_display']); ?> />
								<?php _e('Include the paged breadcrumb in the breadcrumb trail.', 'breadcrumb_navxt'); ?>
							</label><br />
							<span class="setting-description"><?php _e('Indicates that the user is on a page other than the first on paginated posts/pages.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="paged_prefix"><?php _e('Paged Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="paged_prefix" id="paged_prefix" value="<?php echo $this->breadcrumb_trail->opt['paged_prefix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="paged_suffix"><?php _e('Paged Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="paged_suffix" id="paged_suffix" value="<?php echo $this->breadcrumb_trail->opt['paged_suffix']; ?>" size="32" />
						</td>
					</tr>
				</table>
			</fieldset>
			<fieldset id="single" class="bcn_options">
				<h3><?php _e('Posts &amp; Pages', 'breadcrumb_navxt'); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="post_prefix"><?php _e('Post Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="post_prefix" id="post_prefix" value="<?php echo $this->breadcrumb_trail->opt['post_prefix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="post_suffix"><?php _e('Post Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="post_suffix" id="post_suffix" value="<?php echo $this->breadcrumb_trail->opt['post_suffix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="post_anchor"><?php _e('Post Anchor', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="post_anchor" id="post_anchor" value="<?php echo $this->breadcrumb_trail->opt['post_anchor']; ?>" size="60" /><br />
							<span class="setting-description"><?php _e('The anchor template for post breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Post Taxonomy Display', 'breadcrumb_navxt'); ?>
						</th>
						<td>
							<label for="post_taxonomy_display">
								<input name="post_taxonomy_display" type="checkbox" id="post_taxonomy_display" value="true" <?php checked(true, $this->breadcrumb_trail->opt['post_taxonomy_display']); ?> />
								<?php _e('Show the taxonomy leading to a post in the breadcrumb trail.', 'breadcrumb_navxt'); ?>
							</label>							
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Post Taxonomy', 'breadcrumb_navxt'); ?>
						</th>
						<td>
							<label>
								<input name="post_taxonomy_type" type="radio" value="category" class="togx" <?php checked('category', $this->breadcrumb_trail->opt['post_taxonomy_type']); ?> />
								<?php _e('Categories'); ?>
							</label>
							<br/>
							<label>
								<input name="post_taxonomy_type" type="radio" value="tag" class="togx" <?php checked('tag', $this->breadcrumb_trail->opt['post_taxonomy_type']); ?> />
								<?php _e('Tags'); ?>								
							</label>
							<br/>
							<span class="setting-description"><?php _e('The taxonomy which the breadcrumb trail will show.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="page_prefix"><?php _e('Page Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="page_prefix" id="page_prefix" value="<?php echo $this->breadcrumb_trail->opt['page_prefix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="page_suffix"><?php _e('Page Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="page_suffix" id="page_suffix" value="<?php echo $this->breadcrumb_trail->opt['page_suffix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="page_anchor"><?php _e('Page Anchor', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="page_anchor" id="page_anchor" value="<?php echo $this->breadcrumb_trail->opt['page_anchor']; ?>" size="60" /><br />
							<span class="setting-description"><?php _e('The anchor template for page breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="attachment_prefix"><?php _e('Attachment Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="attachment_prefix" id="attachment_prefix" value="<?php echo $this->breadcrumb_trail->opt['attachment_prefix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="attachment_suffix"><?php _e('Attachment Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="attachment_suffix" id="attachment_suffix" value="<?php echo $this->breadcrumb_trail->opt['attachment_suffix']; ?>" size="32" />
						</td>
					</tr>
				</table>
			</fieldset>
			<fieldset id="category" class="bcn_options">
				<h3><?php _e('Categories', 'breadcrumb_navxt'); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="category_prefix"><?php _e('Category Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="category_prefix" id="category_prefix" value="<?php echo $this->breadcrumb_trail->opt['category_prefix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Applied before the anchor on all category breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="category_suffix"><?php _e('Category Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="category_suffix" id="category_suffix" value="<?php echo $this->breadcrumb_trail->opt['category_suffix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Applied after the anchor on all category breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="category_anchor"><?php _e('Category Anchor', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="category_anchor" id="category_anchor" value="<?php echo $this->breadcrumb_trail->opt['category_anchor']; ?>" size="60" /><br />
							<span class="setting-description"><?php _e('The anchor template for category breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="archive_category_prefix"><?php _e('Archive by Category Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="archive_category_prefix" id="archive_category_prefix" value="<?php echo $this->breadcrumb_trail->opt['archive_category_prefix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Applied before the title of the current item breadcrumb on an archive by cateogry page.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="archive_category_suffix"><?php _e('Archive by Category Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="archive_category_suffix" id="archive_category_suffix" value="<?php echo $this->breadcrumb_trail->opt['archive_category_suffix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Applied after the title of the current item breadcrumb on an archive by cateogry page.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
				</table>
			</fieldset>
			<fieldset id="tag" class="bcn_options">
				<h3><?php _e('Tags', 'breadcrumb_navxt'); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="tag_prefix"><?php _e('Tag Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="tag_prefix" id="tag_prefix" value="<?php echo $this->breadcrumb_trail->opt['tag_prefix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Applied before the anchor on all tag breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="tag_suffix"><?php _e('Tag Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="tag_suffix" id="tag_suffix" value="<?php echo $this->breadcrumb_trail->opt['tag_suffix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Applied after the anchor on all tag breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="tag_anchor"><?php _e('Tag Anchor', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="tag_anchor" id="tag_anchor" value="<?php echo $this->breadcrumb_trail->opt['tag_anchor']; ?>" size="60" /><br />
							<span class="setting-description"><?php _e('The anchor template for tag breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="archive_tag_prefix"><?php _e('Archive by Tag Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="archive_tag_prefix" id="archive_tag_prefix" value="<?php echo $this->breadcrumb_trail->opt['archive_tag_prefix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Applied before the title of the current item breadcrumb on an archive by tag page.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="archive_tag_suffix"><?php _e('Archive by Tag Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="archive_tag_suffix" id="archive_tag_suffix" value="<?php echo $this->breadcrumb_trail->opt['archive_tag_suffix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Applied after the title of the current item breadcrumb on an archive by tag page.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
				</table>
			</fieldset>
			<fieldset id="date" class="bcn_options">
				<h3><?php _e('Date Archives', 'breadcrumb_navxt'); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="archive_date_prefix"><?php _e('Archive by Date Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="archive_date_prefix" id="archive_date_prefix" value="<?php echo $this->breadcrumb_trail->opt['archive_date_prefix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Applied before the anchor on all date breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="archive_date_suffix"><?php _e('Archive by Date Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="archive_date_suffix" id="archive_date_suffix" value="<?php echo $this->breadcrumb_trail->opt['archive_date_suffix']; ?>" size="32" /><br />
							<span class="setting-description"><?php _e('Applied after the anchor on all date breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="date_anchor"><?php _e('Date Anchor', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="date_anchor" id="date_anchor" value="<?php echo $this->breadcrumb_trail->opt['date_anchor']; ?>" size="60" /><br />
							<span class="setting-description"><?php _e('The anchor template for date breadcrumbs.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
				</table>
			</fieldset>
			<fieldset id="miscellaneous" class="bcn_options">
				<h3><?php _e('Miscellaneous', 'breadcrumb_navxt'); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="author_prefix"><?php _e('Author Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="author_prefix" id="author_prefix" value="<?php echo $this->breadcrumb_trail->opt['author_prefix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="author_suffix"><?php _e('Author Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="author_suffix" id="author_suffix" value="<?php echo $this->breadcrumb_trail->opt['author_suffix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="author_display"><?php _e('Author Display Format', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<select name="author_display" id="author_display">
								<?php $this->select_options('author_display', array("display_name", "nickname", "first_name", "last_name")); ?>
							</select><br />
							<span class="setting-description"><?php _e('display_name uses the name specified in "Display name publicly as" under the user profile the others correspond to options in the user profile.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="search_prefix"><?php _e('Search Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="search_prefix" id="search_prefix" value="<?php echo $this->breadcrumb_trail->opt['search_prefix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="search_suffix"><?php _e('Search Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="search_suffix" id="search_suffix" value="<?php echo $this->breadcrumb_trail->opt['search_suffix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="search_anchor"><?php _e('Search Anchor', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="search_anchor" id="search_anchor" value="<?php echo $this->breadcrumb_trail->opt['search_anchor']; ?>" size="60" /><br />
							<span class="setting-description"><?php _e('The anchor template for search breadcrumbs, used only when the search results span several pages.', 'breadcrumb_navxt'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="404_title"><?php _e('404 Title', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="404_title" id="404_title" value="<?php echo $this->breadcrumb_trail->opt['404_title']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="404_prefix"><?php _e('404 Prefix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="404_prefix" id="404_prefix" value="<?php echo $this->breadcrumb_trail->opt['404_prefix']; ?>" size="32" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="404_suffix"><?php _e('404 Suffix', 'breadcrumb_navxt'); ?></label>
						</th>
						<td>
							<input type="text" name="404_suffix" id="404_suffix" value="<?php echo $this->breadcrumb_trail->opt['404_suffix']; ?>" size="32" />
						</td>
					</tr>
				</table>
			</fieldset>
			</div>
			<p class="submit"><input type="submit" class="button-primary" name="bcn_admin_options" value="<?php _e('Save Changes') ?>" /></p>
		</form>
		</div>
		<?php
	}
	/**
	 * widget
	 *
	 * The sidebar widget 
	 */
	function widget($args)
	{
		extract($args);
		//Manditory before widget junk
		echo $before_widget;
		//Display the breadcrumb trial
		if($this->breadcrumb_trail->trail[0] != NULL)
		{
			$this->breadcrumb_trail->display();
		}
		else
		{
			$this->display();
		}
		//Manditory after widget junk
		echo $after_widget;
	}
	/**
	 * register_widget
	 *
	 * Registers the sidebar widget 
	 */
	function register_widget()
	{
		register_sidebar_widget('Breadcrumb NavXT', array(&$this, 'widget'));
	}
	/**
	 * local
	 *
	 * Initilizes localization domain
	 */
	function local()
	{
		//Load breadcrumb-navxt translation
		load_plugin_textdomain($domain = 'breadcrumb_navxt', $path = PLUGINDIR . '/breadcrumb-navxt');
	}
	/**
	 * select_options
	 *
	 * Displays wordpress options as <seclect> options defaults to true/false
	 *
	 * @param (string) optionname name of wordpress options store
	 * @param (array) options array of options defaults to array('true','false')
	 */
	function select_options($optionname, $options = array('true','false'))
	{
		$value = $this->breadcrumb_trail->opt[$optionname];
		//First output the current value
		if ($value)
		{
			printf('<option>%s</option>', $value);
		}
		//Now do the rest
		foreach($options as $option)
		{
			//Don't want multiple occurance of the current value
			if($option != $value)
			{
				printf('<option>%s</option>', $option);
			}
		}
	}
	/**
	 * add_option
	 *
	 * This inserts the value into the option name, WPMU safe
	 *
	 * @param (string) key name where to save the value in $value
	 * @param (mixed) value to insert into the options db
	 * @return (bool)
	 */
	function add_option($key, $value)
	{
		return add_option($key, $value);
	}
	/**
	 * delete_option
	 *
	 * This removes the option name, WPMU safe
	 *
	 * @param (string) key name of the option to remove
	 * @return (bool)
	 */
	function delete_option($key)
	{
		return delete_option($key);
	}
	/**
	 * update_option
	 *
	 * This updates the value into the option name, WPMU safe
	 *
	 * @param (string) key name where to save the value in $value
	 * @param (mixed) value to insert into the options db
	 * @return (bool)
	 */
	function update_option($key, $value)
	{
		return update_option($key, $value);
	}
	/**
	 * get_option
	 *
	 * This grabs the the data from the db it is WPMU safe and can place the data 
	 * in a HTML form safe manner.
	 *
	 * @param (string) key name of the wordpress option to get
	 * @param (bool) safe output for HTML forms (default: false)
	 * @return (mixed)
	 */
	function get_option($key, $safe = false)
	{
		$db_data = get_option($key);
		if($safe)
		{
			//If we get an array, we should loop through all of its members
			if(is_array($db_data))
			{
				//Loop through all the members
				foreach($db_data as $key=>$item)
				{
					//We ignore anything but strings
					if(is_string($item))
					{
						$db_data[$key] = htmlentities($item, ENT_COMPAT, "UTF-8");
					}
				}
			}
			else
			{
				$db_data = htmlentities($item, ENT_COMPAT, "UTF-8");
			}
		}
		return $db_data;
	}
	/**
	 * admin_head
	 *
	 * Adds in the JavaScript and CSS for the tabs in the adminsitrative interface
	 *
	 */
	function admin_head()
	{
		?>
<style type="text/css">
	/**
	 * Tabbed Admin Page (CSS)
	 * 
	 * @see Breadcrumb NavXT (Wordpress Plugin)
	 * @author Tom Klingenberg 
	 * @colordef #c6d9e9 light-blue (older tabs border color, obsolete)
	 * @colordef #dfdfdf light-grey (tabs border color)
	 * @colordef #f9f9f9 very-light-grey (admin standard background color)
	 * @colordef #fff    white (active tab background color)
	 */
#hasadmintabs ul.ui-tabs-nav {border-bottom:1px solid #dfdfdf; font-size:12px; height:29px; list-style-image:none; list-style-position:outside; list-style-type:none; margin:13px 0 0; overflow:visible; padding:0 0 0 8px;}
#hasadmintabs ul.ui-tabs-nav li {display:block; float:left; line-height:200%; list-style-image:none; list-style-position:outside; list-style-type:none; margin:0; padding:0; position:relative; text-align:center; white-space:nowrap; width:auto;}
#hasadmintabs ul.ui-tabs-nav li a {background:transparent none no-repeat scroll 0 50%; border-bottom:1px solid #dfdfdf; display:block; float:left; line-height:28px; padding:1px 13px 0; position:relative; text-decoration:none;}
#hasadmintabs ul.ui-tabs-nav li.ui-tabs-selected a{-moz-border-radius-topleft:4px; -moz-border-radius-topright:4px;border:1px solid #dfdfdf; border-bottom-color:#f9f9f9; color:#333333; font-weight:normal; padding:0 12px;}
#hasadmintabs ul.ui-tabs-nav a:focus, a:active {outline-color:-moz-use-text-color; outline-style:none; outline-width:medium; }
</style>
<script type="text/javascript">
/* <![CDATA[ */
	/**
	 * Tabbed Admin Page (javascript/jQuery)
	 *
	 * unobtrusive approach to add tabbed forms into
	 * the wordpress admin panel
	 *
	 * @see Breadcrumb NavXT (Wordpress Plugin)
	 * @author Tom Klingenberg
	 * @uses jQuery
	 * @uses ui.core
	 * @uses ui.tabs
	 */
	 
	jQuery(function() 
	{
		bcn_tabulator_init();		
	 });
	 
	/**
	 * Tabulator Bootup
	 */
	function bcn_tabulator_init()
	{
		/* if this is not the breadcrumb admin page, quit */
		if (!jQuery("#hasadmintabs").length) return;

		/* init markup for tabs */
		jQuery('#hasadmintabs').prepend("<ul><\/ul>");
		jQuery('#hasadmintabs > fieldset').each(function(i)
		{
		    id      = jQuery(this).attr('id');
		    caption = jQuery(this).find('h3').text();
		    jQuery('#hasadmintabs > ul').append('<li><a href="#'+id+'"><span>'+caption+"<\/span><\/a><\/li>");
		    jQuery(this).find('h3').hide();
			// jQuery(this).addClass('tabs-container');
	    });
		
		/* init the tabs plugin */
	    jQuery("#hasadmintabs > ul").tabs();

		/* handler for openeing the last tab after submit (compability version) */
		jQuery('#hasadmintabs ul a').click(function(i){
			var form = jQuery('#bcn_admin_options');
			form.attr("action", (form.attr("action")).split('#', 1) + jQuery(this).attr('href'));
		});
	}
/* ]]> */
</script>
<?php
	}
	/**
	 * javascript
	 *
	 * Queues up JS dependencies (jquery) for the tabs
	 */
	function javascript()
	{
		//Only if we are in the dashboard do we need this
		if(is_admin())
		{
			wp_enqueue_script('jquery-ui-tabs');
		}
	}
}
//Let's make an instance of our object takes care of everything
$bcn_admin = new bcn_admin;
/**
 * A wrapper for the internal function in the class
 * 
 * @param  (bool)   $return Whether to return or echo the trail.
 * @param  (bool)   $linked Whether to allow hyperlinks in the trail or not.
 */
function bcn_display($return = false, $linked = true)
{
	global $bcn_admin;
	$bcn_admin->display($return, $linked);
}
?>

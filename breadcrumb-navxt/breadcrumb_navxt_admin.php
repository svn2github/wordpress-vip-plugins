<?php
/*
Plugin Name: Breadcrumb NavXT - Adminstration Interface
Plugin URI: http://mtekk.weblogs.us/code/breadcrumb-navxt/
Description: Adds a breadcrumb navigation showing the visitor&#39;s path to their current location. This enables the administrative interface for specifying the output of the breadcrumb. For details on how to use this plugin visit <a href="http://mtekk.weblogs.us/code/breadcrumb-navxt/">Breadcrumb NavXT</a>. 
Version: 2.1.4
Author: John Havlik
Author URI: http://mtekk.weblogs.us/
*/
/*
 *
 * @author John Havlik
 * @author Tom Klingenberg
 * 
 * @todo remove static frontpage options
 * @todo put main admin panel logic into one class to better seperate from
 *       global namespace and to provide better modularization for upgrades.
 */

//Configuration 

$bcn_admin_version = "2.1.4";
$bcn_admin_req = 8;

//Includes 

//Include the breadcrumb class (if needed)
if(!class_exists('bcn_breadcrumb'))
{
	require(dirname(__FILE__) . '/breadcrumb_navxt_class.php');
}
//Include the supplemental functions
require(dirname(__FILE__) . '/breadcrumb_navxt_api.php');

//Main

/**
 * Ensure the user has the proper permissions. Dies on failure.
 * 
 * @return void
 */
function bcn_security()
{
	global $userdata, $bcn_admin_req, $bcn_version, $wp_version;
	//Make sure $userdata is filled
	get_currentuserinfo();
	//If the user_levels aren't proper and the user is not an administrator via capabilities
	if($userdata->user_level < $bcn_admin_req && $userdata->wp_capabilities['administrator'] != true)
	{
		//If user_level is null which tends to cause problems for everyone
		if($userdata->user_level == NULL)
		{
			_e('<strong>Aborting: WordPress API Malfunction</strong><br /> For some reason the 
				function get_currentuserinfo() did not behave as expected. Your user_level seems to be null.
				This can be resolved by navigationg to the Users section of the WordPress administrative interface.
				In this section check the user that you use for administrative purposes. Then under the drop down
				labled "change role to..." select administrator. Now click the change button. Should you still 
				recieve this error please report this bug to the plug-in author. In your report please specify 
				your WordPress version, PHP version, Apache (or whatever HTTP server you are using) verion, and 
				the version of the plug-in you are using.<br />', 'breadcrumb_navxt');
			_e('WordPress version: ', 'breadcrumb_navxt');
			echo $wp_version . '<br />';
			_e('PHP version: ', 'breadcrumb_navxt');
			echo phpversion() . '<br />';
			_e('Plug-in version: ', 'breadcrumb_navxt');
			echo $bcn_version . "<br />";
		}
		//Otherwise we have an anauthorized acess attempt
		else
		{
			_e('<strong>Aborting: Insufficient Privleges</strong><br /> Your User Level: ', 'breadcrumb_navxt');
			echo $userdata->user_level;
			_e('<br /> Required User Level: ', 'breadcrumb_navxt');
			echo $bcn_admin_req . '<br />';
		}
		die(); 
	}
}
/**
 * Initilizes the administrative interface options if it is a new install, or an upgrade from an incompatible version
 *
 * @return void 
 */
function bcn_install()
{
	global $bcn_admin_req, $bcn_version;
	
	bcn_security();
	//Check if the database settings are for an old version
	if(get_option('bcn_version') != $bcn_admin_version)
	{
		//First we should clean up old options we don't use anymore
		list($major, $minor, $release) = explode('.', get_option('bcn_version'));
		//If the old DB version was prior to 2.1.3
		if($major <= 2 && $minor <= 1 && $release <= 3)
		{
			//Remove old crap
			delete_option('bcn_preserve');
			delete_option('bcn_static_frontpage');
		}
		//No need for using API hacks, we fully controol things here
		//We always want to update to our current version
		update_option('bcn_version', $bcn_admin_version);
		//Add in options if they didn't exist before, load defaults into them
		add_option('bcn_url_blog', '');
		add_option('bcn_home_display', 'true');
		add_option('bcn_home_link', 'true');
		add_option('bcn_title_home', 'Home');
		add_option('bcn_title_blog', 'Blog');
		add_option('bcn_separator', '&nbsp;&gt;&nbsp;');
		add_option('bcn_search_prefix', 'Search results for &#39;');
		add_option('bcn_search_suffix', '&#39;');
		add_option('bcn_author_prefix', 'Posts by ');
		add_option('bcn_author_suffix', '');
		add_option('bcn_author_display', 'display_name');
		add_option('bcn_singleblogpost_prefix', 'Blog article:&nbsp;');
		add_option('bcn_singleblogpost_suffix', '');
		add_option('bcn_page_prefix', '');
		add_option('bcn_page_suffix', '');
		add_option('bcn_urltitle_prefix', 'Browse to:&nbsp;');
		add_option('bcn_urltitle_suffix', '');
		add_option('bcn_archive_category_prefix', 'Archive by category &#39;');
		add_option('bcn_archive_category_suffix', '&#39;');
		add_option('bcn_archive_date_prefix', 'Archive: ');
		add_option('bcn_archive_date_suffix', '');
		add_option('bcn_archive_date_format', 'EU');
		add_option('bcn_attachment_prefix', 'Attachment:&nbsp;');
		add_option('bcn_attachment_suffix', '');
		add_option('bcn_archive_tag_prefix', 'Archive by tag &#39;');
		add_option('bcn_archive_tag_suffix', '&#39;');
		add_option('bcn_title_404', '404');
		add_option('bcn_link_current_item', 'false');
		add_option('bcn_current_item_urltitle', 'Link of current page (click to refresh)');
		add_option('bcn_current_item_style_prefix', '');
		add_option('bcn_current_item_style_suffix', '');
		add_option('bcn_posttitle_maxlen', 0);
		add_option('bcn_paged_display', 'false');
		add_option('bcn_paged_prefix', ', Page&nbsp;');
		add_option('bcn_paged_suffix', '');
		add_option('bcn_singleblogpost_taxonomy', 'category');
		add_option('bcn_singleblogpost_taxonomy_display', 'true');
		add_option('bcn_singleblogpost_category_prefix', '');
		add_option('bcn_singleblogpost_category_suffix', '');
		add_option('bcn_singleblogpost_tag_prefix', '');
		add_option('bcn_singleblogpost_tag_suffix', '');
	}
}
/**
 * An alias of bcn_display, exists for legacy compatibility. Use bcn_display instead of this.
 * 
 * @see bcn_display
 */
function breadcrumb_nav_xt_display()
{
	if(function_exists('_deprecated_function'))
	{
		_deprecated_function('breadcrumb_nav_xt_display','2.1','bcn_display');
	}
	bcn_display();
}
/**
 * Creates a bcn_breadcrumb object, sets the options per user specification in the 
 * administration interface and outputs the breadcrumb
 */
function bcn_display()
{
	//Playing things really safe here
	if(class_exists('bcn_breadcrumb'))
	{
		//Make new breadcrumb object
		$breadcrumb = new bcn_breadcrumb;
		//Set the settings
		// @todo clean removed in 2.1.3 $breadcrumb->opt['static_frontpage'] = get_option('bcn_static_frontpage');
		$breadcrumb->opt['url_blog'] = get_option('bcn_url_blog');
		$breadcrumb->opt['home_display'] = get_option('bcn_home_display');
		$breadcrumb->opt['home_link'] = get_option('bcn_home_link');
		$breadcrumb->opt['title_home'] = get_option('bcn_title_home');
		$breadcrumb->opt['title_blog'] = get_option('bcn_title_blog');
		$breadcrumb->opt['separator'] = get_option('bcn_separator');
		$breadcrumb->opt['search_prefix'] = get_option('bcn_search_prefix');
		$breadcrumb->opt['search_suffix'] = get_option('bcn_search_suffix');
		$breadcrumb->opt['author_prefix'] = get_option('bcn_author_prefix');
		$breadcrumb->opt['author_suffix'] = get_option('bcn_author_suffix');
		$breadcrumb->opt['author_display'] = get_option('bcn_author_display');
		$breadcrumb->opt['attachment_prefix'] = get_option('bcn_attachment_prefix');
		$breadcrumb->opt['attachment_suffix'] = get_option('bcn_attachment_suffix');
		$breadcrumb->opt['singleblogpost_prefix'] = get_option('bcn_singleblogpost_prefix');
		$breadcrumb->opt['singleblogpost_suffix'] = get_option('bcn_singleblogpost_suffix');
		$breadcrumb->opt['page_prefix'] = get_option('bcn_page_prefix');
		$breadcrumb->opt['page_suffix'] = get_option('bcn_page_suffix');
		$breadcrumb->opt['urltitle_prefix'] = get_option('bcn_urltitle_prefix');
		$breadcrumb->opt['urltitle_suffix'] = get_option('bcn_urltitle_suffix');
		$breadcrumb->opt['archive_category_prefix'] = get_option('bcn_archive_category_prefix');
		$breadcrumb->opt['archive_category_suffix'] = get_option('bcn_archive_category_suffix');
		$breadcrumb->opt['archive_date_prefix'] = get_option('bcn_archive_date_prefix');
		$breadcrumb->opt['archive_date_suffix'] = get_option('bcn_archive_date_suffix');
		$breadcrumb->opt['archive_date_format'] = get_option('bcn_archive_date_format');
		$breadcrumb->opt['archive_tag_prefix'] = get_option('bcn_archive_tag_prefix');
		$breadcrumb->opt['archive_tag_suffix'] = get_option('bcn_archive_tag_suffix');
		$breadcrumb->opt['title_404'] = get_option('bcn_title_404');
		$breadcrumb->opt['link_current_item'] = get_option('bcn_link_current_item', 'false');
		$breadcrumb->opt['current_item_urltitle'] = get_option('bcn_current_item_urltitle');
		$breadcrumb->opt['current_item_style_prefix'] = get_option('bcn_current_item_style_prefix');
		$breadcrumb->opt['current_item_style_suffix'] = get_option('bcn_current_item_style_suffix');
		$breadcrumb->opt['posttitle_maxlen'] = get_option('bcn_posttitle_maxlen');
		$breadcrumb->opt['paged_display'] = get_option('bcn_paged_display');
		$breadcrumb->opt['paged_prefix'] = get_option('bcn_paged_prefix');
		$breadcrumb->opt['paged_suffix'] = get_option('bcn_paged_suffix');
		$breadcrumb->opt['singleblogpost_taxonomy'] = get_option('bcn_singleblogpost_taxonomy');
		$breadcrumb->opt['singleblogpost_taxonomy_display'] = get_option('bcn_singleblogpost_taxonomy_display', 'false');
		$breadcrumb->opt['singleblogpost_category_prefix'] = get_option('bcn_singleblogpost_category_prefix');
		$breadcrumb->opt['singleblogpost_category_suffix'] = get_option('bcn_singleblogpost_category_suffix');
		$breadcrumb->opt['singleblogpost_tag_prefix'] = get_option('bcn_singleblogpost_tag_prefix');
		$breadcrumb->opt['singleblogpost_tag_suffix'] = get_option('bcn_singleblogpost_tag_suffix');
		//Generate the breadcrumb
		$breadcrumb->assemble();
		//Display the breadcrumb
		$breadcrumb->display();
	}
}
/**
 * bcn_admin_options
 *
 * Grabs and cleans updates to the settings from the administrative interface
 */
function bcn_admin_options()
{
	global $wpdb, $bcn_admin_req;
	
	bcn_security();
	
	//Do a nonce check, prevent malicious link/form problems
	check_admin_referer('bcn_admin_options');
		
	// @todo clean removed in 2.1.3 update_option('bcn_static_frontpage', bcn_get('static_frontpage'));
	bcn_update_option('bcn_url_blog', bcn_get('url_blog'));
	bcn_update_option('bcn_home_display', bcn_get('home_display', 'false'));
	bcn_update_option('bcn_home_link', bcn_get('home_link', 'false'));
	bcn_update_option('bcn_title_home', bcn_get('title_home'));
	bcn_update_option('bcn_title_blog', bcn_get('title_blog'));
	bcn_update_option('bcn_separator', bcn_get('separator'));
	bcn_update_option('bcn_search_prefix', bcn_get('search_prefix'));
	bcn_update_option('bcn_search_suffix', bcn_get('search_suffix'));
	bcn_update_option('bcn_author_prefix', bcn_get('author_prefix'));
	bcn_update_option('bcn_author_suffix', bcn_get('author_suffix'));
	bcn_update_option('bcn_author_display', bcn_get('author_display'));
	bcn_update_option('bcn_attachment_prefix', bcn_get('attachment_prefix'));
	bcn_update_option('bcn_attachment_suffix', bcn_get('attachment_suffix'));
	bcn_update_option('bcn_singleblogpost_prefix', bcn_get('singleblogpost_prefix'));
	bcn_update_option('bcn_singleblogpost_suffix', bcn_get('singleblogpost_suffix'));
	bcn_update_option('bcn_page_prefix', bcn_get('page_prefix'));
	bcn_update_option('bcn_page_suffix', bcn_get('page_suffix'));
	bcn_update_option('bcn_urltitle_prefix', bcn_get('urltitle_prefix'));
	bcn_update_option('bcn_urltitle_suffix',	bcn_get('urltitle_suffix'));
	bcn_update_option('bcn_archive_category_prefix', bcn_get('archive_category_prefix'));
	bcn_update_option('bcn_archive_category_suffix', bcn_get('archive_category_suffix'));
	bcn_update_option('bcn_archive_date_prefix', bcn_get('archive_date_prefix'));
	bcn_update_option('bcn_archive_date_suffix', bcn_get('archive_date_suffix'));
	bcn_update_option('bcn_archive_date_format', bcn_get('archive_date_format'));
	bcn_update_option('bcn_archive_tag_prefix', bcn_get('archive_tag_prefix'));
	bcn_update_option('bcn_archive_tag_suffix', bcn_get('archive_tag_suffix'));
	bcn_update_option('bcn_title_404', bcn_get('title_404'));
	bcn_update_option('bcn_link_current_item', bcn_get('link_current_item', 'false'));
	bcn_update_option('bcn_current_item_urltitle', bcn_get('current_item_urltitle'));
	bcn_update_option('bcn_current_item_style_prefix', bcn_get('current_item_style_prefix'));
	bcn_update_option('bcn_current_item_style_suffix', bcn_get('current_item_style_suffix'));
	bcn_update_option('bcn_posttitle_maxlen', bcn_get('posttitle_maxlen'));
	bcn_update_option('bcn_paged_display', bcn_get('paged_display'));
	bcn_update_option('bcn_paged_prefix', bcn_get('paged_prefix'));
	bcn_update_option('bcn_paged_suffix', bcn_get('paged_suffix'));
	bcn_update_option('bcn_singleblogpost_taxonomy', bcn_get('singleblogpost_taxonomy'));
	bcn_update_option('bcn_singleblogpost_taxonomy_display', bcn_get('singleblogpost_taxonomy_display'));
	bcn_update_option('bcn_singleblogpost_category_prefix', bcn_get('singleblogpost_category_prefix'));
	bcn_update_option('bcn_singleblogpost_category_suffix', bcn_get('singleblogpost_category_suffix'));
	bcn_update_option('bcn_singleblogpost_tag_prefix', bcn_get('singleblogpost_tag_prefix'));
	bcn_update_option('bcn_singleblogpost_tag_suffix', bcn_get('singleblogpost_tag_suffix'));
}
/**
 * bcn_add_page
 *
 * Creates link to admin interface
 */
function bcn_add_page()
{
	global $bcn_admin_req;
    add_options_page('Breadcrumb NavXT Settings', 'Breadcrumb NavXT', $bcn_admin_req, 'breadcrumb-nav-xt', 'bcn_admin');
}
/**
 * bcn_admin
 *
 * The actual administration interface
 */
function bcn_admin()
{
	global $bcn_admin_req, $bcn_admin_version, $bcn_version;
	
	//Makes sure the user has the proper permissions. Dies on failure.
	bcn_security();
	
	//Initilizes l10n domain	
	bcn_local();
	
	/*
	 * compare breadcrumb plugin and breadcrumb admin version with each other	 
	 * major and minor version numbering must both match, revision numbers are ignored  
	 */	
	list($bcn_plugin_major, $bcn_plugin_minor, $bcn_plugin_bugfix) = explode('.', $bcn_version);	
	list($bcn_admin_major,  $bcn_admin_minor,  $bcn_admin_bugfix)  = explode('.', $bcn_admin_version);		
	if($bcn_plugin_major != $bcn_admin_major || $bcn_plugin_minor != $bcn_admin_minor)
	{
		?>
		<div id="message" class="updated fade">
			<p><?php _e('Warning, your version of Breadcrumb NavXT does not match the version supported by this administrative interface. As a result, settings may not work as expected.', 'breadcrumb_navxt'); ?></p>
			<p><?php _e('Your Breadcrumb NavXT Administration interface version is ', 'breadcrumb_navxt'); echo $bcn_version; ?>.</p>
			<p><?php _e('Your Breadcrumb NavXT version is ', 'breadcrumb_navxt'); echo $bcn_admin_version; ?>.</p>
		</div>
		<?php 
	}
	//Output the administration panel (until end of function)
	?>
	<div class="wrap"><h2><?php _e('Breadcrumb NavXT Settings', 'breadcrumb_navxt'); ?></h2>
	<form action="options-general.php?page=breadcrumb-nav-xt" method="post" id="bcn_admin_options">
		<?php wp_nonce_field('bcn_admin_options');?>
		<div id="hasadmintabs">
		<fieldset id="general" class="bcn_options">
			<p><?php 
	printf(__(	'This administration interface allows the full customization of the breadcrumb output with no loss
	of functionality when compared to manual configuration. Each setting is the same as the corresponding
	class option, please refer to the 
	%sdocumentation%s 
	for more detailed explanation of each setting.', 'breadcrumb_navxt'), '<a title="Go to the Breadcrumb NavXT online documentation" href="http://mtekk.weblogs.us/code/breadcrumb-navxt/breadcrumb-navxt-doc/">', '</a>'); 
			?></p>
			<h3><?php _e('General', 'breadcrumb_navxt'); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="title_blog"><?php _e('Blog Title', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="title_blog" id="title_blog" value="<?php echo bcn_get_option_inputvalue('bcn_title_blog'); ?>" size="32" /><br />
						<?php _e('Will be displayed on the home page (when not using a static front page), always links to the main post page.', 'breadcrumb_navxt'); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="separator"><?php _e('Breadcrumb Separator', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="separator" id="separator" value="<?php echo bcn_get_option_inputvalue('bcn_separator'); ?>" size="32" /><br />
						<?php _e('Placed in between each breadcrumb.', 'breadcrumb_navxt'); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="urltitle_prefix"><?php _e('URL Title Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>					
						<input type="text" name="urltitle_prefix" id="urltitle_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_urltitle_prefix'); ?>" size="32" /><br />
						<?php _e('The prefix applied globally to the title field in the breadcrumb links.', 'breadcrumb_navxt'); ?>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="urltitle_suffix"><?php _e('URL Title Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="urltitle_suffix" id="urltitle_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_urltitle_suffix'); ?>" size="32" /><br />
						<?php _e('The suffix applied globally to the title field in the breadcrumb links.', 'breadcrumb_navxt'); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="search_prefix"><?php _e('Search Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="search_prefix" id="search_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_search_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="search_suffix"><?php _e('Search Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="search_suffix" id="search_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_search_suffix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="title_404"><?php _e('404 Title', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="title_404" id="title_404" value="<?php echo bcn_get_option_inputvalue('bcn_title_404'); ?>" size="32" />
					</td>
				</tr>
			</table>
		</fieldset>
		<fieldset id="static_front_page" class="bcn_options">
			<h3><?php _e('Front Page', 'breadcrumb_navxt'); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<?php _e('Static Front Page', 'breadcrumb_navxt'); ?>
					</th>
					<td>
						<span id="static_frontpage_ex"><?php echo __(bcn_wp_has_static_frontpage() ? 'Yes': 'No'); ?></span>																		
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="url_blog"><?php _e('Relative Blog URL', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="url_blog" id="url_blog" value="<?php echo bcn_get_option_inputvalue('bcn_url_blog'); ?>" size="32" /><br />
						<?php _e('The location of the page that contains posts relative to the WordPress Blog address.', 'breadcrumb_navxt'); ?>
					</td>
				</tr>				
				<tr valign="top">
					<th scope="row">
						<?php _e('Home Breadcrumb', 'breadcrumb_navxt'); ?>						
					</th>
					<td>					
						<label for="home_display">
							<input name="home_display" type="checkbox" id="home_display" value="true" <?php checked('true', get_option('bcn_home_display')); ?> />
							<?php _e('Is in trail', 'breadcrumb_navxt') ?> - <?php _e('Should the "Home" crumb be placed in the breadcrumb trail?', 'breadcrumb_navxt'); ?>							
						</label><br />
						<label for="home_link">
							<input name="home_link" type="checkbox" id="home_link" value="true" <?php checked('true', get_option('bcn_home_link')); ?> />
							<?php _e('Links to Homepage', 'breadcrumb_navxt') ?> - <?php _e('Should the "Home" crumb link to the home page?', 'breadcrumb_navxt'); ?>																							
						</label><br />						
						<?php printf(__('URL of the Homepage is %s', 'breadcrumb_navxt'), sprintf('<em>%s</em>',  bcn_wp_url_home())) ?>
					</td>
				</tr>				
				<tr valign="top">
					<th scope="row">
						<label for="title_home"><?php _e('Home Title', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="title_home" id="title_home" value="<?php echo bcn_get_option_inputvalue('bcn_title_home'); ?>" size="32" /><br />
						<?php _e('The title applied to the link to the static home page.', 'breadcrumb_navxt'); ?>
					</td>
				</tr>
			</table>
		</fieldset>
		<fieldset id="author" class="bcn_options">
			<h3><?php _e('Author Page', 'breadcrumb_navxt'); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="author_prefix"><?php _e('Author Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="author_prefix" id="author_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_author_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="author_suffix"><?php _e('Author Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="author_suffix" id="author_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_author_suffix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="author_display"><?php _e('Author Display Format', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<select name="author_display" id="author_display">
							<?php bcn_select_options('bcn_author_display', array("display_name", "nickname", "first_name", "last_name")); ?>
						</select>
					</td>
				</tr>
			</table>
		</fieldset>
		<fieldset id="category" class="bcn_options">
			<h3><?php _e('Archive Display', 'breadcrumb_navxt'); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="archive_category_prefix"><?php _e('Archive by Category Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="archive_category_prefix" id="archive_category_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_archive_category_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="archive_category_suffix"><?php _e('Archive by Category Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="archive_category_suffix" id="archive_category_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_archive_category_suffix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="archive_date_prefix"><?php _e('Archive by Date Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="archive_date_prefix" id="archive_date_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_archive_date_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="archive_date_suffix"><?php _e('Archive by Date Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="archive_date_suffix" id="archive_date_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_archive_date_suffix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="archive_date_format"><?php _e('Archive by Date Format', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<select name="archive_date_format" id="archive_date_format">
							<?php bcn_select_options('bcn_archive_date_format', array("EU", "US", "ISO")); ?>
						</select><br />
						<?php _e('e.g. EU: 14 May 2008, US: May 14, 2008, ISO: 2008 May 14', 'breadcrumb_navxt'); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="archive_tag_prefix"><?php _e('Archive by Tag Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="archive_tag_prefix" id="archive_tag_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_archive_tag_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="archive_tag_suffix"><?php _e('Archive by Tag Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="archive_tag_suffix" id="archive_tag_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_archive_tag_suffix'); ?>" size="32" />
					</td>
				</tr>
			</table>
		</fieldset>
		<fieldset id="current" class="bcn_options">
			<h3><?php _e('Current Item', 'breadcrumb_navxt'); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="link_current_item"><?php _e('Link Current Item', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<label>
							<input name="link_current_item" type="checkbox" id="link_current_item" value="true" <?php checked('true', bcn_get_option('bcn_link_current_item')); ?> />
							<?php _e('Yes'); ?>							
						</label>					
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="current_item_urltitle"><?php _e('Current Item URL Title', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="current_item_urltitle" id="current_item_urltitle" value="<?php echo bcn_get_option_inputvalue('bcn_current_item_urltitle'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="current_item_style_prefix"><?php _e('Current Item Style Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="current_item_style_prefix" id="current_item_style_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_current_item_style_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="current_item_style_suffix"><?php _e('Current Item Style Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="current_item_style_suffix" id="current_item_style_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_current_item_style_suffix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="paged_display"><?php _e('Display Paged Text', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<label>
							<input name="paged_display" type="checkbox" id="paged_display" value="true" <?php checked('true', bcn_get_option('bcn_paged_display')); ?> />
							<?php _e('Show that the user is on a page other than the first on posts/archives with multiple pages.', 'breadcrumb_navxt'); ?>							
						</label>	
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="paged_prefix"><?php _e('Paged Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="paged_prefix" id="paged_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_paged_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="paged_suffix"><?php _e('Paged Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="paged_suffix" id="paged_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_paged_suffix'); ?>" size="32" />
					</td>
				</tr>
			</table>
		</fieldset>
		<fieldset id="single" class="bcn_options">
			<h3><?php _e('Single Post', 'breadcrumb_navxt'); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="singleblogpost_prefix"><?php _e('Single Blogpost Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="singleblogpost_prefix" id="singleblogpost_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_singleblogpost_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="singleblogpost_suffix"><?php _e('Single Blogpost Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="singleblogpost_suffix" id="singleblogpost_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_singleblogpost_suffix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="page_prefix"><?php _e('Page Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="page_prefix" id="page_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_page_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="page_suffix"><?php _e('Page Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="page_suffix" id="page_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_page_suffix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="attachment_prefix"><?php _e('Post Attachment Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="attachment_prefix" id="attachment_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_attachment_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="attachment_suffix"><?php _e('Post Attachment Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="attachment_suffix" id="attachment_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_attachment_suffix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="title_home"><?php _e('Post Title Max Length', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="posttitle_maxlen" id="posttitle_maxlen" value="<?php echo bcn_get_option_inputvalue('bcn_posttitle_maxlen'); ?>" size="10" />
					</td>
				</tr>
			</table>
		</fieldset>
		<fieldset id="taxonomy" class="bcn_options">
			<h3><?php _e('Taxonomy', 'breadcrumb_navxt'); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<?php _e('Single Blog Post Taxonomy Display', 'breadcrumb_navxt'); ?>
					</th>
					<td>
						<label for="singleblogpost_taxonomy_display">
							<input name="singleblogpost_taxonomy_display" type="checkbox" id="singleblogpost_taxonomy_display" value="true" <?php checked('true', bcn_get_option('bcn_singleblogpost_taxonomy_display')); ?> />
							<?php _e('Show the taxonomy leading to a post in the breadcrumb trail.', 'breadcrumb_navxt'); ?>							
						</label>							
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<p><?php _e('Single Blog Post Taxonomy', 'breadcrumb_navxt'); ?></p>
					</th>
					<td>
						<p>
							<label>
								<input name="singleblogpost_taxonomy" type="radio" value="category" class="togx" <?php checked('category', bcn_get_option('bcn_singleblogpost_taxonomy')); ?> />
								<?php _e('Categories'); ?>
							</label>
						</p>
						<p>
							<label>
								<input name="singleblogpost_taxonomy" type="radio" value="tag" class="togx" <?php checked('tag', bcn_get_option('bcn_singleblogpost_taxonomy')); ?> />
								<?php _e('Tags'); ?>								
							</label>
						</p>
						<p>
							<?php _e('The taxonomy which the breadcrumb trail will show.', 'breadcrumb_navxt'); ?>
						</p>														
					</td>
				</tr>					
				<tr valign="top">
					<th scope="row">
						<label for="singleblogpost_category_prefix"><?php _e('Single Blog Post Category Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="singleblogpost_category_prefix" id="singleblogpost_category_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_singleblogpost_category_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="singleblogpost_category_suffix"><?php _e('Single Blog Post Category Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="singleblogpost_category_suffix" id="singleblogpost_category_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_singleblogpost_category_suffix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="singleblogpost_tag_prefix"><?php _e('Single Blog Post Tag Prefix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="singleblogpost_tag_prefix" id="singleblogpost_tag_prefix" value="<?php echo bcn_get_option_inputvalue('bcn_singleblogpost_tag_prefix'); ?>" size="32" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="singleblogpost_tag_suffix"><?php _e('Single Blog Post Tag Suffix', 'breadcrumb_navxt'); ?></label>
					</th>
					<td>
						<input type="text" name="singleblogpost_tag_suffix" id="singleblogpost_tag_suffix" value="<?php echo bcn_get_option_inputvalue('bcn_singleblogpost_tag_suffix'); ?>" size="32" />
					</td>
				</tr>
			</table>
		</fieldset>
		</div>
		<p class="submit"><input type="submit" name="bcn_admin_options" value="<?php _e('Save Changes') ?>" /></p>
	</form>
	</div>
	<?php
}
/**
 * bcn_select_options
 *
 * displays wordpress options as <seclect> options defaults to true/false
 *
 * @param (string) optionname name of wordpress options store
 * @param (array) options array of options defaults to array('true','false')
 */
function bcn_select_options($optionname, $options = array('true','false'))
{
	$value = get_option($optionname);
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
 * Additional styles and scripts for admin interface
 * 
 * @todo do not add to any admin page
 */
function bcn_options_style()
{
	//wp_version is needed for version checks performed in this function
	global $wp_version;
	
	//setup styles for admn and tabbed admin page
?>
<style type="text/css">
	.bcn_options{border: none;}	
	.form-table .form-table-inner td {border:none; height:normal; line-height:normal; margin:0; padding:0 8px 0 0; vertical-align: top;}
	.form-table .form-table-inner tr.spaced td {padding-bottom:6px;}
	/**
	 * Tabbed Admin Page (CSS)
	 *
	 * unobtrusive approach to add tabbed forms into
	 * the wordpress admin panel
	 *
	 * @note unstable
	 * @see http://www.artnorm.de/this-morning-in-bleeding,105,2008-06.html
	 * @see breadcrumb navxt
	 * @author Tom Klingenberg
	 * @cssdoc 1.0-pre
	 * @colordef #fff    white      (tab background) 
	 * @colordef #c6d9e9 grey-blue  (tab line)
	 * @colordef #d54e21 orange     (tab text of active tab)
	 * @colordef #d54e21 orange     (tab text of inactive tab hovered) external
	 * @colordef #2583ad dark-blue  (tab text of inactive tab) external	 	 
	 */
	ul.ui-tabs-nav {background:#fff; border-bottom:1px solid #c6d9e9; font-size:12px; height:29px; margin:13px 0 0; padding:0; padding-left:8px; list-style:none;}	
	ul.ui-tabs-nav li {display:inline; line-height: 200%; list-style:none; margin: 0; padding:0; position:relative; top:1px; text-align:center; white-space:nowrap;}
	ul.ui-tabs-nav li a {background:transparent none no-repeat scroll 0%; border:1px transparent #fff; border-bottom:1px solid #c6d9e9; display:block; float:left; line-height:28px; padding:1px 13px 0; position:relative; text-decoration:none;}
	ul.ui-tabs-nav li.ui-tabs-selected a {-moz-border-radius-topleft:4px; -moz-border-radius-topright:4px; background:#fff; border:1px solid #c6d9e9; border-bottom-color:#fff; color:#d54e21; font-weight:normal; padding:0 12px;}
	ul.ui-tabs-nav a:focus, a:active {outline: none;}
	#hasadmintabs fieldset {clear:both;}
</style>
<?php
	/* 
     * needed javascript libraries are included now
     * add own javascript code now 
     */
?>
<script type="text/javascript">
/* <![CDATA[ */
	/**
	 * Tabbed Admin Page (jQuery)
	 *
	 * unobtrusive approach to add tabbed forms into
	 * the wordpress admin panel
	 *
	 * @note unstable
	 * @see http://www.artnorm.de/this-morning-in-bleeding,105,2008-06.html
	 * @see breadcrumb navxt
	 * @author Tom Klingenberg
	 * @uses jQuery
	 * @uses ui.core
	 * @uses ui.tabs
	 */
	 
	jQuery(document).ready(function() 
	{
		bcn_tabulator_init();		
	 });
	 
	/**
	 * Tabulator Bootup
	 */
	function bcn_tabulator_init()
	{
		bcn_admin_init_tabs();	
		bcn_admin_gobal_tabs(); // comment out this like to disable tabs in admin 
					
	}
	
	/**
	 * inittialize tabs for admin panel pages (wordpress core)
	 *
	 * @todo add uniqueid somehow
	 */	
	function bcn_admin_gobal_tabs()
	{	
		/* if has already a special id quit the global try here */
		if (jQuery('#hasadmintabs').length > 0) return;
		
		jQuery('#wpbody .wrap form').each(function(f)
		{						
			var $formEle = jQuery(this).children();
		
			var $eleSets      = new Array();	
			var $eleSet       = new Array();
			var $eleSetIgnore = new Array();
								
			for (var i = 0; i < $formEle.size(); i++)
			{
				var curr = $formEle.get(i);
				var $curr = jQuery(curr);	
				// cut condition: h3 or stop				
				// stop: p.submit
				if ($curr.is('p.submit') || $curr.is('h3'))
				{
					if ($eleSet.length)
					{
						if ($eleSets.length == 0 && $eleSet.length == 1 && jQuery($eleSet).is('p'))	{
							$eleSetIgnore = $eleSetIgnore.concat($eleSet);
						} else {
							$eleSets.push($eleSet);
						}						
						$eleSet  = new Array();
					}
					if ($curr.is('p.submit')) break;
					$eleSet.push(curr);					
				} else {
					// handle ingnore bag - works only before the first set is created
					var pushto = $eleSet; 
					if ($eleSets.length == 0 && $curr.is("input[type='hidden']"))
					{
						pushto = $eleSetIgnore;					
					}															
					pushto.push(curr);
				}
			}		
			
			// if the page has only one set, quit
			if ($eleSets.length < 2) return;			
			
			// tabify
			formid = 'tabulator-tabs-form-' + f;
			jQuery($eleSetIgnore).filter(':last').after('<div id="' + formid + '"></div>');
			jQuery('#'+formid).prepend("<ul><\/ul>");
			var tabcounter = 0;			
			jQuery.each($eleSets, function() {
				tabcounter++;
				id      = formid + '-tab-' + tabcounter;
				hash3   = true;
				h3probe = jQuery(this).filter('h3').eq(0);			
				if (h3probe.is('h3')) {
					caption = h3probe.text();					
				} else {
					hash3   = false;
					caption = jQuery('#wpbody .wrap h2').eq(0).text();
				}
				if (caption == ''){
					caption = 'FALLBACK';
				} 	
				tabdiv = jQuery(this).wrapAll('<span id="'+id+'"></span>');
				jQuery('#'+formid+' > ul').append('<li><a href="#'+id+'"><span>'+caption+"<\/span><\/a><\/li>");
				if (hash3) h3probe.hide();
			});
			jQuery('#'+formid+' > ul').tabs();				
		});	
	}
	 
	/**
	 * inittialize tabs for breadcrumb navxt admin panel
	 */	 
	function bcn_admin_init_tabs()
	{
		jQuery('#hasadmintabs').prepend("<ul><\/ul>");
		jQuery('#hasadmintabs > fieldset').each(function(i)
		{
		    id      = jQuery(this).attr('id');
		    caption = jQuery(this).find('h3').text();
		    jQuery('#hasadmintabs > ul').append('<li><a href="#'+id+'"><span>'+caption+"<\/span><\/a><\/li>");
		    jQuery(this).find('h3').hide();
	    });    
	    jQuery("#hasadmintabs > ul").tabs();
	}
/* ]]> */
</script>
<?php
}

//WordPress hooks
if(function_exists('add_action')){
	//Installation Script hook
	add_action('activate_breadcrumb-navxt/breadcrumb_navxt_admin.php','bcn_install');
	//WordPress Admin interface hook
	add_action('admin_menu', 'bcn_add_page');
	//add_action('admin_head', 'bcn_options_style');
	//Enque javscript dependencies
	//wp_enqueue_script('jquery-ui-tabs');
	//Admin Options hook
	if(isset($_POST['bcn_admin_options']))
	{
		add_action('init', 'bcn_admin_options');
	}
}

?>
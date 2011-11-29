<?php
/*
	Plugin Name: Kapost Social Publishing Byline
	Plugin URI: http://www.kapost.com/
	Description: Kapost Social Publishing Byline
	Version: 1.0.3
	Author: Kapost
	Author URI: http://www.kapost.com
*/
define('KAPOST_BYLINE_VERSION', '1.0.3-WIP');

function kapost_byline_custom_fields($raw_custom_fields)
{
	if(!is_array($raw_custom_fields))
		return array();

	$custom_fields = array();
	foreach($raw_custom_fields as $i => $cf)
		$custom_fields[$cf['key']] = $cf['value'];

	return $custom_fields;
}

function kapost_byline_update_post($id, $custom_fields, $uid=false, $blog_id=false)
{
	$post = get_post($id);
	if(!is_object($post)) return false;

	$post_needs_update = false;

	// if this is a draft then clear the 'publish date'
	if($post->post_status == 'draft')
	{
		$post->post_date = '0000-00-00 00:00:00';
		$post->post_date_gmt = '0000-00-00 00:00:00';
		$post_needs_update = true;
	}

	// set our custom type
	if(isset($custom_fields['kapost_custom_type']))
	{
		$custom_type = $custom_fields['kapost_custom_type'];
		if(!empty($custom_type) && post_type_exists($custom_type))
		{
			$post->post_type = $custom_type;
			$post_needs_update = true;
		}
	}

	// set our featured image
	if(isset($custom_fields['kapost_featured_image']))
	{
		// look up the image by URL which is the GUID (too bad there's NO wp_ specific method to do this, oh well!)
		global $wpdb;
		$thumbnail = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s", $custom_fields['kapost_featured_image']));

		// if the image was found, set it as the featured image for the current post
		if(!empty($thumbnail))
		{
			// We support 2.9 and up so let's do this the old fashioned way
			// >= 3.0.1 and up has "set_post_thumbnail" available which does this little piece of mockery for us ...
			update_post_meta($id, '_thumbnail_id', $thumbnail->ID);
		}
	}
	
	// set our post author
	if($uid !== false && $post->post_author != $uid)
	{
		$post->post_author = $uid;
		$post_needs_update = true;
	}

	// if any changes has been made above update the post once
	if($post_needs_update)
		wp_update_post((array) $post);

	return true;
}

function kapost_byline_xmlrpc_version()
{
	return KAPOST_BYLINE_VERSION;
}

function kapost_byline_xmlrpc_newPost($args)
{
	global $wp_xmlrpc_server;
	    
	$wp_xmlrpc_server->escape($args);

	$blog_id	= intval($args[0]);
	$username	= $args[1];
	$password	= $args[2];
	$data		= $args[3];

	if(!$wp_xmlrpc_server->login($username, $password))
		return $wp_xmlrpc_server->error;

	if(!current_user_can('publish_posts'))
		return new IXR_Error(401, __('Sorry, you are not allowed to publish posts on this site.'));

	$uid = false;
	$custom_fields = kapost_byline_custom_fields($data['custom_fields']);
	if(isset($custom_fields['kapost_author_email']))
	{
		$uid = email_exists($custom_fields['kapost_author_email']);
		if(!$uid || (function_exists('is_user_member_of_blog') && !is_user_member_of_blog($uid, $blog_id)))
			return new IXR_Error(401, 'The author of the post does not exist in WordPress.');
	}
	
	$id = $wp_xmlrpc_server->mw_newPost($args);

	if(is_string($id))
		kapost_byline_update_post($id, $custom_fields, $uid, $blog_id);

	return $id;
}

function kapost_byline_xmlrpc($methods)
{
	$methods['kapost.version'] = 'kapost_byline_xmlrpc_version';
	$methods['kapost.newPost'] = 'kapost_byline_xmlrpc_newPost';
	return $methods;
}
add_filter('xmlrpc_methods', 'kapost_byline_xmlrpc');

?>
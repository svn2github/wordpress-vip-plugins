<?php
/*
	Plugin Name: Kapost Social Publishing Byline
	Plugin URI: http://www.kapost.com/
	Description: Kapost Social Publishing Byline
	Version: 1.5.0
	Author: Kapost
	Author URI: http://www.kapost.com
*/
define('KAPOST_BYLINE_VERSION', '1.5.0-WIP');

function kapost_byline_custom_fields($raw_custom_fields)
{
	if(!is_array($raw_custom_fields))
		return array();

	$custom_fields = array();
	foreach($raw_custom_fields as $i => $cf)
	{
		$k = sanitize_text_field($cf['key']);
		$v = sanitize_text_field($cf['value']);
		$custom_fields[$k] = $v;
	}

	return $custom_fields;
}

function kapost_is_protected_meta($protected_fields, $field)
{
    if(!in_array($field, $protected_fields))
        return false;

    if(function_exists('is_protected_meta'))
        return is_protected_meta($field, 'post');

    return ($field[0] == '_');
}

function kapost_byline_protected_custom_fields($custom_fields)
{       
    if(!isset($custom_fields['_kapost_protected']))
        return array();

    $protected_fields = array();
    foreach(explode('|', $custom_fields['_kapost_protected']) as $p)
    {
        list($prefix, $keywords) = explode(':', $p);

        $prefix = trim($prefix);
        foreach(explode(',', $keywords) as $k)
        {
            $kk = trim($k);
            $protected_fields[] = "_${prefix}_${kk}";
        }
    }   
        
    $pcf = array();
    foreach($custom_fields as $k => $v)
    {   
        if(kapost_is_protected_meta($protected_fields, $k))
            $pcf[$k] = $v;                                                                                                
    }
    
    return $pcf;
}

function kapost_byline_update_post($id, $custom_fields, $uid=false, $blog_id=false)
{
	$post = get_post($id);
	if(!is_object($post)) return false;

	$post_needs_update = false;

	// if this is a draft then clear the 'publish date' or set our own
	if($post->post_status == 'draft')
	{
		if(isset($custom_fields['kapost_publish_date']))
		{
			$post_date = $custom_fields['kapost_publish_date']; // UTC
			$post->post_date = get_date_from_gmt($post_date);
			$post->post_date_gmt = $post_date;
		}
		else
		{
			$post->post_date = '0000-00-00 00:00:00';
			$post->post_date_gmt = '0000-00-00 00:00:00';
		}

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
		$thumbnail = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s LIMIT 1", $custom_fields['kapost_featured_image']));

		// if the image was found, set it as the featured image for the current post
		if(!empty($thumbnail))
		{
			// We support 2.9 and up so let's do this the old fashioned way
			// >= 3.0.1 and up has "set_post_thumbnail" available which does this little piece of mockery for us ...
			update_post_meta($id, '_thumbnail_id', $thumbnail->ID);
		}
	}

	// store our protected custom field required by our analytics
	if(isset($custom_fields['_kapost_analytics_url']))
	{
		// join them into one for performance and speed
		$kapost_analytics = array();
		foreach($custom_fields as $k => $v)
		{
			// starts with?
			if(strpos($k, '_kapost_analytics_') === 0)
			{
				$kk = str_replace('_kapost_analytics_', '', $k);
				$kapost_analytics[$kk] = $v;
			}
		}

		add_post_meta($id, '_kapost_analytics', $kapost_analytics);
	}

	// store other implicitly 'allowed' protected custom fields
	if(isset($custom_fields['_kapost_protected']))
	{
		foreach(kapost_byline_protected_custom_fields($custom_fields) as $k => $v)
		{
			delete_post_meta($id, $k);
			if(!empty($v)) add_post_meta($id, $k, $v);
		}
	}

	// match custom fields to custom taxonomies if appropriate
	$taxonomies = array_keys(get_taxonomies(array('_builtin' => false), 'names'));
	if(!empty($taxonomies))
	{
		foreach($custom_fields as $k => $v)
		{                                                                                                       
			if(in_array($k, $taxonomies))
				wp_set_object_terms($id, explode(',', $v), $k);
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

function kapost_byline_has_analytics_code($content)
{
	return strpos($content, '<!-- END KAPOST ANALYTICS CODE -->') !== FALSE;
}

function kapost_byline_get_analytics_code($id)
{
	$kapost_analytics = get_post_meta($id, '_kapost_analytics', true);
	if(empty($kapost_analytics))
		return "";

	extract($kapost_analytics, EXTR_SKIP);

	$code = "
<!-- BEGIN KAPOST ANALYTICS CODE -->
<span id='kapostanalytics' pid='" . esc_attr($post_id) . "' aid='" . esc_attr($author_id) . "' nid='" . esc_attr($newsroom_id) . "' cats='" . esc_attr($categories) . "' url='" . $url . "'></span>
<!-- END KAPOST ANALYTICS CODE -->
";

	return $code;
}

function kapost_byline_the_content($content)
{
	global $post;

	if(isset($post) && !kapost_byline_has_analytics_code($content))
		return $content . kapost_byline_get_analytics_code($post->ID);

	return $content;
}

function kapost_inject_footer_script() {
  echo '<script><!--
var _kapost_data = _kapost_data || [];
m = document.getElementById("kapostanalytics");
_kapost_data.push([1, m.getAttribute("pid"), m.getAttribute("aid"), m.getAttribute("nid"), m.getAttribute("cats")]);
(function() {
var ka = document.createElement(\'script\'); 
ka.async=true; 
ka.id="kp_tracker"; 
ka.src=m.getAttribute("url") + "/javascripts/tracker.js";
var s = document.getElementsByTagName(\'script\')[0]; 
s.parentNode.insertBefore(ka, s);
})();
//--></script>';
}

add_filter('the_content', 'kapost_byline_the_content');
add_filter('the_content_feed', 'kapost_byline_the_content');
add_action('wp_footer', 'kapost_inject_footer_script');

function kapost_byline_xmlrpc_version()
{
	return KAPOST_BYLINE_VERSION;
}

function kapost_byline_xmlrpc_newPost($args)
{
	global $wp_xmlrpc_server;

	// create a copy of the arguments and escape that
	// in order to avoid any potential double escape issues
	$_args = $args;

	$wp_xmlrpc_server->escape($_args);

	$blog_id	= intval($_args[0]);
	$username	= $_args[1];
	$password	= $_args[2];
	$data		= $_args[3];

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

function kapost_byline_xmlrpc_newMediaObject($args)
{
	global $wpdb, $wp_xmlrpc_server;

	$_args = $args;

	$blog_id	= intval($_args[0]);
	$username	= $wpdb->escape($_args[1]);
	$password	= $wpdb->escape($_args[2]);
	$data		= $_args[3];

	if(!$user = $wp_xmlrpc_server->login($username, $password))
		return $wp_xmlrpc_server->error;

	if(!current_user_can('upload_files'))
		return new IXR_Error(401, __('You are not allowed to upload files to this site.'));

	$image = $wp_xmlrpc_server->mw_newMediaObject($args);
	if(!is_array($image) || empty($image['url']))
		return $image;

	$attachment = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s LIMIT 1", $image['url']));
	if(empty($attachment))
		return $image;

	$update_attachment = false;

	if(isset($data['description']))
	{
		$attachment->post_content = sanitize_text_field($data['description']);
		$update_attachment = true;
	}

	if(isset($data['title']))
	{
		$attachment->post_title	= sanitize_text_field($data['title']);
		$update_attachment = true;
	}

	if(isset($data['caption']))
	{
		$attachment->post_excerpt = sanitize_text_field($data['caption']);
		$update_attachment = true;
	}

	if($update_attachment) 
		wp_update_post($attachment);

	if(isset($data['alt'])) 
		add_post_meta($attachment->ID, '_wp_attachment_image_alt', sanitize_text_field($data['alt']));

	$image['id'] = $attachment->ID;
	return $image;
}

function kapost_byline_xmlrpc_getPermalink($args)
{
	global $wp_xmlrpc_server;
	$wp_xmlrpc_server->escape($args);

	$post_id	= intval($args[0]);
	$username	= $args[1];
	$password	= $args[2];

	if(!$user = $wp_xmlrpc_server->login($username, $password))
		return $wp_xmlrpc_server->error;
	
	if(!current_user_can('edit_post', $post_id))
		return new IXR_Error(401, __('Sorry, you cannot edit this post.'));

	$post = get_post($post_id);
	if(!is_object($post) || !isset($post->ID))
		return new IXR_Error(401, __('Sorry, you cannot edit this post.'));

	if(!empty($post->post_title) && in_array($post->post_status, array('draft', 'pending', 'auto-draft')))
	{
		$post->filter = 'sample';
		$post->post_status = 'publish';
		if(empty($post->post_date) || $post->post_date == '0000-00-00 00:00:00')
		{
			$post->post_date = current_time('mysql');
			$post->post_date_gmt = current_time('mysql', 1);
		}
		if(empty($post->post_name))
		{
			$post->post_name = wp_unique_post_slug(sanitize_title($post->post_title), 
																  $post->ID, 
																  $post->post_status, 
																  $post->post_type, 
																  $post->post_parent);
		}
		return get_permalink($post);
	}

	return get_permalink($post);
}

function kapost_byline_xmlrpc($methods)
{
	$methods['kapost.version'] = 'kapost_byline_xmlrpc_version';
	$methods['kapost.newPost'] = 'kapost_byline_xmlrpc_newPost';
	$methods['kapost.newMediaObject'] = 'kapost_byline_xmlrpc_newMediaObject';
	$methods['kapost.getPermalink']	= 'kapost_byline_xmlrpc_getPermalink';
	return $methods;
}
add_filter('xmlrpc_methods', 'kapost_byline_xmlrpc');

?>
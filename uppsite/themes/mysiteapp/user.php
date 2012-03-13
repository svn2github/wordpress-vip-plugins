<?php
$user = get_query_var('mysiteapp_user');
wp_set_auth_cookie($user->ID);
// handle avatar
if (function_exists('get_the_author_meta')) {
	$avatar = get_avatar($user->user_email);
} elseif (function_exists('get_the_author_id')) {
	$avatar = get_avatar($user->ID);
} else {
	$avatar = null;
}
$avatar_url = mysiteapp_extract_url($avatar);
?>
<mysiteapp>
	<user ID="<?php echo $user->ID ?>" user_level="<?php echo $user->wp_user_level ?>">
	<login><![CDATA[<?php echo $user->user_login ?>]]></login>
	<name><![CDATA[<?php echo $user->display_name ?>]]></name>
    <nickname><![CDATA[<?php echo $user->user_nicename ?>]]></nickname>
	<first_name><![CDATA[<?php echo $user->first_name ?>]]></first_name>
	<last_name><![CDATA[<?php echo $user->last_name ?>]]></last_name>
	<email><![CDATA[<?php echo $user->user_email ?>]]></email>
	<avatar><![CDATA[<?php echo $avatar_url ?>]]></avatar>
	<url><![CDATA[<?php echo $user->user_url ?>]]></url>
	<yim><![CDATA[<?php echo $user->yim ?>]]></yim>
	<aim><![CDATA[<?php echo $user->aim ?>]]></aim>
	<jabber><![CDATA[<?php echo $user->jabber ?>]]></jabber>
	<logout_url><![CDATA[<?php echo mysiteapp_logout_url_wrapper() ?>]]></logout_url>
	<login_url><![CDATA[<?php echo site_url('wp-login.php') ?>]]></login_url>
	<capabilities>
		<is_contributor><?php echo isset($user->wp_capabilities['contributor']) && $user->wp_capabilities['contributor'] ? "true" : "false" ?></is_contributor>
		<is_author><?php echo isset($user->wp_capabilities['author']) && $user->wp_capabilities['author'] ? "true" : "false" ?></is_author>
		<is_editor><?php echo isset($user->wp_capabilities['editor']) && $user->wp_capabilities['editor'] ? "true" : "false" ?></is_editor>
		<is_administrator><?php echo isset($user->wp_capabilities['administrator']) && $user->wp_capabilities['administrator'] ? "true": "false" ?></is_administrator>
	</capabilities>
	</user>
</mysiteapp>
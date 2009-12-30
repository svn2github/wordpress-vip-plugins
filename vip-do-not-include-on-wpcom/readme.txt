The files in this vip-do-not-include-on-wpcom folder are provided for VIP clients to use in your development environments.

Please do not include the files or declare functions or classes in your themes on WordPress.com. We do this for you.

You can use the functions in your themes but use the following example to be safe:
if ( function_exists( 'function_name' ) )
	function_name();

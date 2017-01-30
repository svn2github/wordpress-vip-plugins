<?php
/**
 * Civil Comments Template Tags
 *
 * @package  Civil_Comments
 */

/**
 * Output the Civil Comments script tags.
 *
 * This can be used in templates to display Civil Comments.
 */
function show_civil_comments() {
	global $post;
	$settings = Civil_Comments\get_settings( 'civil_comments' );
	$publication_slug = isset( $settings['publication_slug'] ) ? $settings['publication_slug'] : '';
	$lang = isset( $settings['lang'] ) ? $settings['lang'] : 'en_US';
	$enable_sso = isset( $settings['enable_sso'] ) ? (bool) $settings['enable_sso'] : false;
	$sso_secret = isset( $settings['sso_secret'] ) ? $settings['sso_secret'] : false;
	$hide = isset( $settings['hide'] ) ? (bool) $settings['hide'] : false;
	$current_user = null;

	// Attempt SSO if enabled, configured and we're logged in.
	if ( $enable_sso && ! empty( $sso_secret ) && is_user_logged_in() ) {
		$token = Civil_Comments\get_jwt_token( wp_get_current_user(), $sso_secret );
		$current_user = array(
			'token' => $token,
		);
	}

	/**
	 * Civil Login URL.
	 *
	 * Filter that contains the Civil Comments login url.  Can be overridden with this filter.
	 *
	 * @since 0.2.0
	 *
	 * @param string $login_url Login url.
	 */
	$login_url = apply_filters( 'civil_login_url', wp_login_url( get_permalink() ) );

	/**
	 * Civil Logout URL.
	 *
	 * Filter that contains the Civil Comments logout url.  Can be overridden with this filter.
	 *
	 * @see: https://core.trac.wordpress.org/ticket/34352
	 *
	 * @since 0.2.0
	 *
	 * @param string $logout_url Logout url.
	 */
	$logout_url = apply_filters( 'civil_logout_url', html_entity_decode( wp_logout_url( get_permalink() ) ) );

	$civil = array(
		'objectId'        => absint( $post->ID ),
		'publicationSlug' => $publication_slug,
		'lang'            => $lang,
		'enableSso'       => $enable_sso,
		'hide'            => $hide,
		'token'           => $current_user,
		'loginUrl'        => $login_url,
		'logoutUrl'       => $logout_url,
	);
	?>
	<script>
		/* <![CDATA[ */
		var CivilWp = <?php echo wp_json_encode( $civil ); ?>;
		/* ]]> */
	</script>
	<div id="civil-comments"></div>
	<script>
	(function(c, o, mm, e, n, t, s){
		c[n] = c[n] || function() {
			var args = [].slice.call(arguments);
			(c[n].q = c[n].q || []).push(args);
			if (c[n].r) return; t = o.createElement(mm); s = o.getElementsByTagName(mm)[0];
			t.async = 1; t.src = [e].concat(args.map(encodeURIComponent)).join("/");
			s.parentNode.insertBefore(t, s); c[n].r = 1;};
		c["CivilCommentsObject"] = c[n];
	})(window, document, "script", "https://ssr.civilcomments.com/v1", "Civil");

	Civil(CivilWp.objectId, CivilWp.publicationSlug, CivilWp.lang);

	<?php
	/**
	 * Add custom javascript to the Civil Comments initialization.
	 *
	 * Action runs right after the Civil() javascript function runs inside the <script> block.
	 * Users can add custom scripts such as analytics here.
	 *
	 * @since 0.2.0
	 */
	do_action( 'civil_custom_js' );
	?>

	if ( CivilWp.hide ) {
		Civil({ hideComments: true });
	}

	if ( CivilWp.enableSso ) {
		Civil({
			provider: 'jwt',
			getUser: function() {
				return CivilWp.token;
			},
			login: function() {
				window.location = CivilWp.loginUrl;
			},
			logout: function() {
				window.location = CivilWp.logoutUrl;
			}
		});
	}
	</script>
	<?php
}

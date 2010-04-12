<?php
// Custom IntenseDebate Comments template.
// Loads comments from the WordPress database using your own comments template,
// loads IntenseDebate UI for users with Javascript enabled.

if ( get_option( 'id_revertMobile' ) == 0 && id_is_mobile() ) :
	// Display the comments template from the active theme
	include( get_option( 'id_comment_template_file' ) );
else :
	global $id_cur_post;
	$id_cur_post = $post;
	$bits = parse_url( WP_CONTENT_URL );
	$xd_base = $bits['path'] . '/themes/vip/plugins';
	id_auto_login();
?>
	<div id='idc-container'></div>
	<div id="idc-noscript">
		<p id="idc-unavailable"><?php _e( 'This website uses <a href="http://intensedebate.com/">IntenseDebate comments</a>, but they are not currently loaded because either your browser doesn\'t support JavaScript, or they didn\'t load fast enough.', 'intensedebate' ); ?></p>
		<?php
		// Include your theme's comemnt template
		if ( is_readable( get_option( "id_comment_template_file" ) ) )
			include( get_option( "id_comment_template_file" ) );
		?>
	</div>
	<script type="text/javascript">
	/* <![CDATA[ */
	var idc_xd_receiver = '<?php echo $xd_base; ?>/intensedebate/xd_receiver.htm';
	function IDC_revert() { document.getElementById('idc-loading-comments').style.display='none'; if ( !document.getElementById('IDCommentsHead') ) { document.getElementById('idc-noscript').style.display='block'; document.getElementById('idc-comment-wrap-js').parentNode.removeChild(document.getElementById('idc-comment-wrap-js')); } else { document.getElementById('idc-noscript').style.display='none'; } }
	idc_ns = document.getElementById('idc-noscript');
	idc_ns.style.display='none'; idc_ld = document.createElement('div');
	idc_ld.id = 'idc-loading-comments'; idc_ld.style.verticalAlign='middle';
	idc_ld.innerHTML = "<img src='<?php echo WP_CONTENT_URL; ?>/themes/vip/plugins/intensedebate/loading.gif' alt='Loading' border='0' align='absmiddle' /> <?php _e( 'Loading IntenseDebate Comments...', 'intensedebate' ); ?>";
	idc_ns.parentNode.insertBefore(idc_ld, idc_ns);
	setTimeout( IDC_revert, 5000 );
	/* ]]> */
	</script>
<?php
// Queue up the comment UI to load now that everything else is in place
id_postload_js( ID_BASEURL . '/js/wordpressTemplateCommentWrapper2.php?acct=' . get_option( 'id_blogAcct' ) . '&postid=' . $id . '&title=' . urlencode( $post->post_title ) . '&url=' . urlencode( get_permalink( $id ) ) . '&posttime=' . urlencode( $post->post_date_gmt ) . '&postauthor=' . urlencode( get_author_name( $post->post_author ) ) . '&guid=' . urlencode( $post->guid ), 'idc-comment-wrap-js' );

endif; // revertMobile

function id_is_mobile() {
	$op = !empty( $_SERVER['HTTP_X_OPERAMINI_PHONE'] ) ? strtolower( $_SERVER['HTTP_X_OPERAMINI_PHONE'] ) : '';
	$ua = strtolower( $_SERVER['HTTP_USER_AGENT'] );
	$ac = !empty( $_SERVER['HTTP_ACCEPT'] ) ? strtolower( $_SERVER['HTTP_ACCEPT'] ) : '';
	$ip = $_SERVER['REMOTE_ADDR'];

	 $isMobile = strpos( $ac, 'application/vnd.wap.xhtml+xml' ) !== false
        || $op != ''
        || strpos( $ua, 'sony' ) !== false 
		|| strpos( $ua, 'webos/' ) !== false 
        || strpos( $ua, 'symbian' ) !== false 
        || strpos( $ua, 'nokia' ) !== false 
        || strpos( $ua, 'samsung' ) !== false 
        || strpos( $ua, 'mobile' ) !== false
        || strpos( $ua, 'windows ce' ) !== false
        || strpos( $ua, 'epoc' ) !== false
        || strpos( $ua, 'opera mini' ) !== false
        || strpos( $ua, 'nitro' ) !== false
        || strpos( $ua, 'j2me' ) !== false
        || strpos( $ua, 'midp-' ) !== false
        || strpos( $ua, 'cldc-' ) !== false
        || strpos( $ua, 'netfront' ) !== false
        || strpos( $ua, 'mot' ) !== false
        || strpos( $ua, 'up.browser' ) !== false
        || strpos( $ua, 'up.link' ) !== false
        || strpos( $ua, 'audiovox' ) !== false
        || strpos( $ua, 'blackberry' ) !== false
        || strpos( $ua, 'ericsson,' ) !== false
        || strpos( $ua, 'panasonic' ) !== false
        || strpos( $ua, 'philips' ) !== false
        || strpos( $ua, 'sanyo' ) !== false
        || strpos( $ua, 'sharp' ) !== false
        || strpos( $ua, 'sie-' ) !== false
        || strpos( $ua, 'portalmmm' ) !== false
        || strpos( $ua, 'blazer' ) !== false
        || strpos( $ua, 'avantgo' ) !== false
        || strpos( $ua, 'danger' ) !== false
        || strpos( $ua, 'palm' ) !== false
        || strpos( $ua, 'series60' ) !== false
        || strpos( $ua, 'palmsource' ) !== false
        || strpos( $ua, 'pocketpc' ) !== false
        || strpos( $ua, 'smartphone' ) !== false
        || strpos( $ua, 'rover' ) !== false
        || strpos( $ua, 'ipaq' ) !== false
        || strpos( $ua, 'au-mic,' ) !== false
        || strpos( $ua, 'alcatel' ) !== false
        || strpos( $ua, 'ericy' ) !== false
        || strpos( $ua, 'up.link' ) !== false
        || strpos( $ua, 'vodafone/' ) !== false
        || strpos( $ua, 'wap1.' ) !== false
        || strpos( $ua, 'wap2.' ) !== false;

	return $isMobile;
}
?>
<?php

if ( get_option( 'id_revertMobile' ) == 0 && id_is_mobile() ) {
	// check for mobile and output WP comments if so
	include( get_option( 'id_comment_template_file' ) );
} else {
	// output new ID comments
	global $id_cur_post;
	$id_cur_post = $post;
?>

	<div id='idc-container'></div>
	<script type='text/javascript' src='<?php echo ID_BASEURL ?>/js/wordpressTemplateCommentWrapper2.php?acct=<?php echo get_option( 'id_blogAcct' ) ?>&amp;postid=<?php echo $id ?>&amp;title=<?php echo urlencode( $post->post_title ) ?>&amp;url=<?php echo urlencode( the_permalink( $id ) ) ?>&amp;posttime=<?php echo urlencode( $post->post_date_gmt ) ?>&amp;postauthor=<?php echo urlencode( get_author_name( $post->post_author ) ) ?>&amp;guid=<?php echo urlencode( $post->guid ) ?>'></script>
	<noscript>

<?php
	$old_template = '';
	if ( file_exists( get_option( "id_comment_template_file" ) ) )
		$old_template = file_get_contents( get_option( "id_comment_template_file" ) );
	// check if the file contains any <noscript> tags...if it does we can't use it
	if ( !preg_match( "/<noscript>(.|\n|\r)*?<\/noscript>/i", $old_template, $matches ) ) {
		include ( get_option( "id_comment_template_file" ) );
	} else {
		// if we can't use theirs, use the default theme to output comments
		
		// Do not delete these lines
		if ( !empty( $_SERVER['SCRIPT_FILENAME'] ) && 'comments.php' == basename( $_SERVER['SCRIPT_FILENAME'] ) )
			die( __( 'Please do not load this page directly. Thanks!' ) );

	if ( !empty( $post->post_password ) ) { // if there's a password
		if ( $_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password ) {  // and it doesn't match the cookie
			?>
			<p class="nocomments"><?php _e( 'This post is password protected. Enter the password to view comments.' ) ?></p>
			<?php
			return;
		}
	}

	/* This variable is for alternating comment background */
	$oddcomment = 'class="alt" ';
?>

<!-- You can start editing here. -->

<?php if ( $comments ) : ?>
	<h3 id="comments"><?php comments_number( __( 'No Responses' ), __( 'One Response' ), __( '% Responses' ) ) ?> to &#8220;<?php the_title() ?>&#8221;</h3>

	<ol class="commentlist">
	<?php foreach ( $comments as $comment ) : ?>
		<li <?php echo $oddcomment ?>id="comment-<?php comment_ID() ?>">
			<?php echo get_avatar( $comment, 32 ) ?>
			<cite><?php comment_author_link() ?></cite> Says:
			<?php if ( '0' == $comment->comment_approved ) : ?>
			<em><?php _e( 'Your comment is awaiting moderation.' ) ?></em>
			<?php endif ?>
			<br />
			<small class="commentmetadata">
				<a href="#comment-<?php comment_ID() ?>" title=""><?php comment_date( 'F jS, Y' ) ?> at <?php comment_time() ?></a> <?php edit_comment_link( 'edit', '&nbsp;&nbsp;', '' ) ?>
			</small>
			<?php comment_text() ?>
		</li>
		<?php
			/* Changes every other comment to a different class */
			$oddcomment = ( empty( $oddcomment ) ) ? 'class="alt" ' : '';
		?>
	<?php endforeach; /* end for each comment */ ?>
	</ol>

<?php else : // this is displayed if there are no comments so far ?>
	<?php if ( 'open' == $post->comment_status ) : ?>
		<!-- If comments are open, but there are no comments. -->
	 <?php else : // comments are closed ?>
		<!-- If comments are closed. -->
		<p class="nocomments"><?php _e( 'Comments are closed.' ) ?></p>
	<?php endif ?>
<?php endif ?>

<?php if ( 'open' == $post->comment_status ) : ?>

	<h3 id="respond"><?php _e( 'Leave a Reply' ) ?></h3>

	<?php if ( get_option( 'comment_registration' ) && !$user_ID ) : ?>
		<p><?php sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.' ), get_option( 'siteurl' ) . '/wp-login.php?redirect_to=' . urlencode( get_permalink() ) ) ?></p>

	<?php else : ?>
		<form action="<?php echo get_option( 'siteurl' ) ?>/wp-comments-post.php" method="post" id="commentform">
		<?php if ( $user_ID ) : ?>
			<p><?php sprintf( __( 'Logged in as %s.' ), '<a href="' . get_option( 'siteurl' ) . '/wp-admin/profile.php">' . $user_identity . '</a>' ) ?> <a href="<?php echo get_option('siteurl') ?>/wp-login.php?action=logout" title="<?php _e( 'Log out of this account' ) ?>"><?php _e( 'Log out &raquo;' ) ?></a></p>
		<?php else : ?>
			<p><input type="text" name="author" id="author" value="<?php echo $comment_author ?>" size="22" tabindex="1" <?php if ( $req ) echo "aria-required='true'" ?> />
			<label for="author"><small>Name <?php if ( $req ) echo "(required)" ?></small></label></p>

			<p><input type="text" name="email" id="email" value="<?php echo $comment_author_email ?>" size="22" tabindex="2" <?php if ( $req ) echo "aria-required='true'" ?> />
			<label for="email"><small>Mail (will not be published) <?php if ( $req ) echo "(required)" ?></small></label></p>

			<p><input type="text" name="url" id="url" value="<?php echo $comment_author_url ?>" size="22" tabindex="3" />
			<label for="url"><small>Website</small></label></p>
		<?php endif ?>

		<!--<p><small><strong>XHTML:</strong> You can use these tags: <code><?php echo allowed_tags() ?></code></small></p>-->

		<p><textarea name="comment" id="comment" cols="100%" rows="10" tabindex="4"></textarea></p>

		<p><input name="submit" type="submit" id="submit" tabindex="5" value="Submit Comment" />
		<input type="hidden" name="comment_post_ID" value="<?php echo $id ?>" />
		</p>
		<?php do_action( 'comment_form', $post->ID ) ?>
		</form>

	<?php endif; // If registration required and not logged in ?>

<?php endif; // if you delete this the sky will fall on your head
	}
?>
	</noscript>
<?php
}

function id_is_mobile() {
	$op = strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']);
	$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
	$ac = strtolower($_SERVER['HTTP_ACCEPT']);
	$ip = $_SERVER['REMOTE_ADDR'];

	 $isMobile = strpos($ac, 'application/vnd.wap.xhtml+xml') !== false
        || $op != ''
        || strpos($ua, 'sony') !== false 
        || strpos($ua, 'symbian') !== false 
        || strpos($ua, 'nokia') !== false 
        || strpos($ua, 'samsung') !== false 
        || strpos($ua, 'mobile') !== false
        || strpos($ua, 'windows ce') !== false
        || strpos($ua, 'epoc') !== false
        || strpos($ua, 'opera mini') !== false
        || strpos($ua, 'nitro') !== false
        || strpos($ua, 'j2me') !== false
        || strpos($ua, 'midp-') !== false
        || strpos($ua, 'cldc-') !== false
        || strpos($ua, 'netfront') !== false
        || strpos($ua, 'mot') !== false
        || strpos($ua, 'up.browser') !== false
        || strpos($ua, 'up.link') !== false
        || strpos($ua, 'audiovox') !== false
        || strpos($ua, 'blackberry') !== false
        || strpos($ua, 'ericsson,') !== false
        || strpos($ua, 'panasonic') !== false
        || strpos($ua, 'philips') !== false
        || strpos($ua, 'sanyo') !== false
        || strpos($ua, 'sharp') !== false
        || strpos($ua, 'sie-') !== false
        || strpos($ua, 'portalmmm') !== false
        || strpos($ua, 'blazer') !== false
        || strpos($ua, 'avantgo') !== false
        || strpos($ua, 'danger') !== false
        || strpos($ua, 'palm') !== false
        || strpos($ua, 'series60') !== false
        || strpos($ua, 'palmsource') !== false
        || strpos($ua, 'pocketpc') !== false
        || strpos($ua, 'smartphone') !== false
        || strpos($ua, 'rover') !== false
        || strpos($ua, 'ipaq') !== false
        || strpos($ua, 'au-mic,') !== false
        || strpos($ua, 'alcatel') !== false
        || strpos($ua, 'ericy') !== false
        || strpos($ua, 'up.link') !== false
        || strpos($ua, 'vodafone/') !== false
        || strpos($ua, 'wap1.') !== false
        || strpos($ua, 'wap2.') !== false;

	return $isMobile;
}
?>
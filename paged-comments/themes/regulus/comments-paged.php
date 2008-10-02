<?php // Do not delete these lines
	if ('comments-paged.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');

	if (!empty($post->post_password)) { // if there's a password
		if ($_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) {  // and it doesn't match the cookie
			?>
			
			<p class="nocomments">This post is password protected. Enter the password to view comments.<p>
			
			<?php
			return;
		}
	}

	/* This variable is for alternating comment background */
	$oddcomment = 'alt';
?>

<!-- You can start editing here. -->

<div id="comments">

	<h2><?php _e('Comments'); if ( comments_open() ) : ?><a href="#postComment" title="leave a comment">&raquo;</a><?php endif; ?></h2>

	<?php if ($comments) : 
		//$commentCount = 1;
	?>
		<!-- h3 id="comments"><?php //comments_number('No Responses', 'One Response', '% Responses' );?> to &#8220;<?php //the_title(); ?>&#8221;</h3 --> 
	
			
		<!-- Comment page numbers -->
		<?php if ($paged_comments->pager->num_pages() > 1): ?>
		<p class="comment-page-numbers"><?php _e("Pages:"); ?> <?php paged_comments_print_pages(); ?></p>
		<?php endif; ?>
		<!-- End comment page numbers -->
	
		<!-- ol class="commentlist" style="list-style-type: none;" -->
		
		<dl>
	
		<?php foreach ($comments as $comment) : 
			$class = bm_author_highlight();
		?>
	
			<dt class="<?php echo $class; ?>" id="comment-<?php comment_ID() ?>">
				<a href="#<?php comment_ID() ?>"><?php echo $comment_number . "."; $comment_number += $comment_delta;?></a> <?php comment_author_link() ?> -
				
				<?php if ($comment->comment_approved == '0') : ?>
				<em>Your comment is awaiting moderation.</em>
				<?php endif; ?>
	
				<small class="commentmetadata"><a href="<?php echo paged_comments_url('comment-'.get_comment_ID()); ?>" title=""></a></small><?php comment_date(); ?> <?php edit_comment_link( "[Edit]" ); ?>
			</dt>
				<dd class="<?php echo $class; ?>">
				<?php 
					comment_text(); 
					//$commentCount ++; //leaving this uncommented will reset the count on every page
				?>
				</dd>
	
			</li>
	
		<?php /* Changes every other comment to a different class */	
			//if ('alt' == $oddcomment) $oddcomment = '';
			//else $oddcomment = 'alt';
		?>
	
		<?php endforeach; /* end for each comment */ ?>
	
		</dl>
	
	<!-- Comment page numbers -->
	<?php if ($paged_comments->pager->num_pages() > 1): ?>
	<p class="comment-page-numbers"><?php _e("Pages:"); ?> <?php paged_comments_print_pages(); ?></p>
	<?php endif; ?>
	<!-- End comment page numbers -->
	
	 <?php else : // this is displayed if there are no comments so far ?>
	
	  <?php if ('open' == $post->comment_status) : ?> 
			<!-- If comments are open, but there are no comments. -->
			<p>no comments yet - be the first?</p>
			
		 <?php else : // comments are closed ?>
			<!-- If comments are closed. -->
			<p>Sorry comments are closed for this entry</p>
			
		<?php endif; ?>
	<?php endif; ?>

</div> <!-- end <div id="comments"> -->

<?php if ('open' == $post->comment_status) : ?>

<!--h3 id="respond">Leave a Reply</h3-->

<?php if ( get_option('comment_registration') && !$user_ID ) : ?>
<p>You must be <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php the_permalink(); ?>">logged in</a> to post a comment.</p>
<?php else : ?>

<form action="<?php echo get_settings('siteurl'); ?>/wp-comments-post.php" method="post" id="postComment">

<input type="hidden" name="comment_post_ID" value="<?php echo $post->ID; ?>" />
<input type="hidden" name="redirect_to" value="<?php echo wp_specialchars($_SERVER['REQUEST_URI']); ?>" />

<label for="comment">message</label><br /><textarea name="comment" id="comment" tabindex="1"></textarea>

<?php if ( $user_ID ) : ?>

<p>Logged in as <a href="<?php echo get_option('siteurl'); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?action=logout" title="Log out of this account">Logout &raquo;</a></p>

<?php else : ?>

<label for="author">name</label><input name="author" id="author" value="<?php echo $comment_author; ?>" tabindex="2" />
<label for="email">email</label><input name="email" id="email" value="<?php echo $comment_author_email; ?>" tabindex="3" />
<label for="url">url</label><input name="url" id="url" value="<?php echo $comment_author_url; ?>" tabindex="4" />

<?php endif; ?>

<!--<p><small><strong>XHTML:</strong> You can use these tags: <?php echo allowed_tags(); ?></small></p>-->

<input class="button" name="submit" id="submit" type="submit" tabindex="5" value="say it!" />
</p>
<?php do_action('comment_form', $post->ID); ?>

</form>

<?php endif; // If registration required and not logged in ?>

<?php endif; // if you delete this the sky will fall on your head ?>
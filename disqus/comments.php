<?php
global $dsq_response, $dsq_version;

if ( ! function_exists( 'dsq_render_single_comment' ) ) {
	function dsq_render_single_comment( $comment, $args, $depth ) {
		$GLOBALS['comment'] = $comment;
		?>
		<li id="dsq-comment-<?php echo comment_ID(); ?>">
			<div id="dsq-comment-header-<?php echo comment_ID(); ?>" class="dsq-comment-header">
				<cite id="dsq-cite-<?php echo comment_ID(); ?>">
					<?php if(comment_author_url()) : ?>
						<a id="dsq-author-user-<?php echo comment_ID(); ?>" href="<?php echo comment_author_url(); ?>" target="_blank" rel="nofollow"><?php echo comment_author(); ?></a>
					<?php else : ?>
						<span id="dsq-author-user-<?php echo comment_ID(); ?>"><?php echo comment_author(); ?></span>
					<?php endif; ?>
				</cite>
			</div>
			<div id="dsq-comment-body-<?php echo comment_ID(); ?>" class="dsq-comment-body">
				<div id="dsq-comment-message-<?php echo comment_ID(); ?>" class="dsq-comment-message"><?php wp_filter_kses(comment_text()); ?></div>
			</div>
		</li>
		<?php
	}
}

?>
<div id="disqus_thread">
	<div id="dsq-content">
		<ul id="dsq-comments">
			<?php
			wp_list_comments( array(
				'callback' => 'dsq_render_single_comment',
				'per_page' => '25',
			) );
			?>
		</ul>
		<?php paginate_comments_links(); ?>
	</div>
</div>
		
<a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>

<script type="text/javascript" charset="utf-8">
	var disqus_url = '<?php echo get_permalink(); ?>';
	var disqus_identifier = '<?php echo dsq_identifier_for_post($post); ?>';
	var disqus_container_id = 'disqus_thread';
	var disqus_domain = '<?php echo DISQUS_DOMAIN; ?>';
	var disqus_shortname = '<?php echo strtolower(get_option('disqus_forum_url')); ?>';
	<?php if (false && get_option('disqus_developer')): ?>
		var disqus_developer = 1;
	<?php endif; ?>
	var disqus_config = function () {
	    var config = this; // Access to the config object

	    /* 
	       All currently supported events:
	        * preData — fires just before we request for initial data
	        * preInit - fires after we get initial data but before we load any dependencies
	        * onInit  - fires when all dependencies are resolved but before dtpl template is rendered
	        * afterRender - fires when template is rendered but before we show it
	        * onReady - everything is done
	     */

		config.callbacks.preData.push(function() {
			// clear out the container (its filled for SEO/legacy purposes)
			document.getElementById(disqus_container_id).innerHTML = '';
		})
		config.callbacks.onReady.push(function() {
/*
			// sync comments in the background so we don't block the page
			var req = new XMLHttpRequest();
			req.open('GET', '?cf_action=sync_comments&post_id=<?php echo $post->ID; ?>', true);
			req.send(null);
*/
		});
		
		<?php do_action('disqus_config_js'); // call action for custom Disqus config js ?>
	};
	
	var facebookXdReceiverPath = '<?php echo DSQ_PLUGIN_URL . '/xd_receiver.htm' ?>';
</script>

<script type="text/javascript" charset="utf-8">
	var DsqLocal = {
		'trackbacks': [
<?php
	$count = 0;
	foreach ($comments as $comment) {
		$comment_type = get_comment_type();
		if ( $comment_type != 'comment' ) {
			if( $count ) { echo ','; }
?>
			{
				'author_name':	'<?php echo htmlspecialchars(get_comment_author(), ENT_QUOTES); ?>',
				'author_url':	'<?php echo htmlspecialchars(get_comment_author_url(), ENT_QUOTES); ?>',
				'date':			'<?php comment_date('m/d/Y h:i A'); ?>',
				'excerpt':		'<?php echo str_replace(array("\r\n", "\n", "\r"), '<br />', htmlspecialchars(get_comment_excerpt(), ENT_QUOTES)); ?>',
				'type':			'<?php echo $comment_type; ?>'
			}
<?php
			$count++;
		}
	}
?>
		],
		'trackback_url': '<?php trackback_url(); ?>'
	};
</script>

<script type="text/javascript" charset="utf-8">
(function() {
	var dsq = document.createElement('script'); dsq.type = 'text/javascript';
	dsq.async = true;
	dsq.src = '//' + disqus_shortname + '.' + disqus_domain + '/embed.js?pname=wordpress&pver=<?php echo $dsq_version; ?>';
	(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
})();
</script>

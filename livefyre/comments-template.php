<?php 
	global $livefyre; 
	if ($livefyre->Display->livefyre_show_comments()) { 
		?>
		<div id='comments'>
			<div id='lf_comment_stream' livefyre_title="<?php echo $post->post_title?>">
			<?php
		
			if ( $parent_id = wp_is_post_revision( $post->ID ) ) {
				$post_id = $parent_id;
			} else {
				$post_id = $post->ID;
			}
			$transient_key = 'livefyre_bootstrap_' . $post_id;
			$cached_html = get_transient( $transient_key );
			if ( !$cached_html ) {
				$cached_html = '';
				$url = $livefyre->bootstrap_url . '/api/v1.1/public/bootstrap/html/' . get_option( 'livefyre_site_id' ) . '/'.base64_encode($post_id) . '.html?allow_comments=' . comments_open();
				$result = $livefyre->lf_domain_object->http->request( $url, array( 'method' => 'GET' ) );
				if ( is_array( $result ) && isset($result['response']) && $result['response']['code'] == 200 && strlen($result['body']) > 0 ) {
					$cached_html = $result['body'];
				}
				set_transient( $transient_key , $cached_html, 300 );
			}
			echo $cached_html;
			?>
			</div>
		</div>
		<?php 
	}

	echo "<!-- Livefyre Comments Version: " . $livefyre->plugin_version."-->";
	if ( pings_open() ) {
		$num_pings = count( get_comments( array( 'post_id' => $post->ID, 'type' => 'pingback', 'status' => 'approve' ) ) ) + count( get_comments( array( 'post_id'=>$post->ID, 'type'=>'trackback', 'status'=>'approve' ) ) );
		if ( $num_pings > 0 ):
		?>
		<div style="font-family: arial !important;" id="lf_pings">
			<h3>Trackbacks</h3>
			<ol class="commentlist">
				<?php wp_list_comments( array( 'type'=>'pings', 'reply_text' => '' ) ); ?>
			</ol>
		</div>
		<?php endif;
	}

<?php
	try {
		$feeds = Grabpress_API::get_feeds();
	} catch ( Exception $e ) {
		$feeds = array();
	}
	$num_feeds = count( $feeds );
	$active_feeds = 0;
?>
<fieldset id="manage-table" class="fieldset-manage">
	<legend><?php echo esc_html( isset( $form['action' ] ) && $form['action'] == 'modify' ? 'Current' : 'Manage' ); ?> Feeds</legend>
	<div>
		<table class="grabpress-table manage-table" cellspacing="0">
			<tr>
				<th>Active</th>
				<th>Name</th>
				<th>Video Categories</th>
				<th>Keywords</th>
				<th>Exclude<br />Keywords</th>
				<th>Exact<br />Phrase</th>
				<th>Any<br />keyword</th>
				<th>Content<br />Providers</th>
				<th>Schedule</th>
				<th>Max<br />Results</th>
				<th>Post<br />Categories</th>
				<th>Author</th>
				<th>Player<br />Mode</th>
				<th>Delivery<br />Mode</th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
			<?php
				for ( $n = 0; $n < $num_feeds; $n++ ) {
					$feed = $feeds[$n]->feed;
					$keywords[ html_entity_decode( $feed->name ) ] = $phrase[ html_entity_decode( $feed->name ) ] = '';
					$url = array();
					parse_str( parse_url( $feed->url, PHP_URL_QUERY ), $url );
					Grabpress::escape_params_template( $url );
					$feedId = $feed->id;
					$providers = explode( ',', $url['providers'] );
					$channels = explode( ',', $url['categories'] );
			?>
				<form id="form-<?php echo esc_attr( $feedId ); ?>" method="post">
				<input type="hidden" id="action-<?php echo esc_attr( $feedId ); ?>" name="action" value="" />
				<input type="hidden" name="referer" value="edit" />
				<input type="hidden" name="channels_total" value="<?php echo esc_attr( $channels_total ); ?>" id="channels_total" />
				<?php
					if ( isset( $form['action'], $_GET['feed_id'] ) && 'modify' == $form['action'] && $feedId == $_GET['feed_id'] ) {
						$row_class = "editing-feed";
					} else if ( ! $feed->active ) {
						$row_class = "inactive-row";
					} else {
						$row_class = "row-feed";
					}
					$display_keywords = true;
					if ( isset( $_GET['feed_id'] ) && $feed->id == $_GET['feed_id'] ) {
						$display_keywords = false;
					}
				?>
					<tr id="tr-<?php echo esc_attr( $feedId ); ?>" data-feed-id="<?php esc_attr( $feedId ); ?>" class="<?php echo esc_attr( $row_class ); ?>">
						<td>
							<?php
								if ( 'default' == $form['action'] && isset( $form['action'] )) {
								$checked = ( $feed->active  ) ? 'checked = "checked"' : '';
								echo '<input ' . $checked . ' type="checkbox" value="1" name="active" class="active-check" id="active-check-' . $feedId . '" />';
								}
								else if ( 'modify' == isset( $form['action'] ) ) {
									echo $checked = ( $feed->active  ) ? 'Yes' : 'No';
								}
							?>
						</td>
						<td>
							<?php echo urldecode( $feed->name ); ?>
						</td>
						<td>
							<?php
								$video_categories_array = explode( ',', $url['categories'] );
								$video_categories_num = count( $video_categories_array );
								$channels_num = ( ! empty( $list_channels ) ) ? count( $list_channels ) : 0;
								if ( ! $url['categories'] || $video_categories_num == $channels_num ) {
									echo "All Video Categories";
								} else if( 1 == $video_categories_num ) {
									echo $video_categories = ( $video_categories_num > 15 ) ? substr( $url['categories'], 0, 15) . '...' : $url['categories'];
								} else {
									echo $video_categories_num . " selected";
								}
							?>
						</td>
						<td>
							<?php
								if( isset( $url['keywords_and'] ) ) {
									$keywords_and_num = strlen( $url['keywords_and'] );
									$keywords_and = $url['keywords_and'];
									if ( ! empty( $keywords_and ) && $display_keywords ) {
										$keywords[ html_entity_decode( $feed->name ) ] .= ' '. $keywords_and;
									}
									echo ( $keywords_and_num > 15 ) ? substr( $keywords_and, 0, 15) .'...' : $keywords_and;
								}
							?>
						</td>
						<td>
							<?php
								if ( isset( $url['keywords_not'] ) ) {
									$keywords_not_num = strlen( $url['keywords_not'] );
									$keywords_not = $url['keywords_not'];
									echo ( $keywords_not_num > 15 ) ? substr( $keywords_not, 0, 15 ) . '...' : $keywords_not;
								}
							?>
						</td>
						<td>
							<?php
								if ( isset( $url['keywords_phrase'] ) ) {
									$keywords_phrase_num = strlen( $url['keywords_phrase'] );
									$keywords_phrase = $url['keywords_phrase'];
									if( ! empty( $keywords_phrase ) && $display_keywords ) {
										$phrase[ html_entity_decode( $feed->name ) ] .= '_' . trim( $keywords_phrase ) . '';
									}
									echo ( $keywords_phrase_num > 15 ) ? substr( $keywords_phrase, 0, 15) . '...' : $keywords_phrase;
								}
							?>
						</td>
						<td>
							<?php
								if ( isset( $url['keywords'] ) ) {
									$keywords_or_num = strlen( $url['keywords'] );
									$keywords_or = $url['keywords'];
									if ( ! empty( $keywords_or ) && $display_keywords ) {
										$keywords[ $feed->name ] .= ' ' . trim( $keywords_or );
									}
									echo ( $keywords_or_num > 15 ) ? substr( $keywords_or, 0, 15) . '...' : $keywords_or;
								}
							?>
						</td>
						<td>
							<input type="hidden" name="providers_total" value="<?php echo esc_attr( $providers_total ); ?>" class="providers_total" />
							<?php
								$providers_selected = count( $providers );
								if( 1 == $providers_selected ) {
									if ( empty( $providers ) ) {
										echo "All providers";
									} else {
										foreach ( $list_providers as $record_provider ) {
											$provider = $record_provider->provider;
											$provider_name = $provider->name;
											$provider_id = $provider->id;
											if ( in_array( $provider_id, $providers ) ) {
												echo $provider_name;
											}
										}
									}
								} else {
									echo $providers_selected . " selected";
								}
							?>
						</td>
						<td>
							<?php
								if ( DEVELOPMENT_ENV == Grabpress::$environment ) {
									$times = array(
										'15 mins',
										'30 mins',
										'45 mins',
										'01 hr',
										'02 hrs',
										'06 hrs',
										'12 hrs',
										'01 day',
										'02 days',
										'03 days',
									);
									$values = array(
										15,
										30,
										45,
										60,
										120,
										360,
										720,
										1440,
										2880,
										4320,
									);
								} else {
									$times = array(
										'06 hrs',
										'12 hrs',
										'01 day',
										'02 days',
										'03 days',
									);
									$values = array(
										360,
										720,
										1440,
										2880,
										4320,
									);
								}
								for ( $o = 0; $o < count( $times ); $o++ ) {
									$time = $times[ $o ];
									$value = $values[ $o ] * 60;
									if ( $value == $feed->update_frequency ) {
										echo $time;
									}
								}
							?>
						</td>
						<td>
							<?php echo $feed->posts_per_update; ?>
						</td>
						<td>
							<?php
							
							
								if ( isset( $feed->custom_options->category ) ) {
									
									$category_list_length = count((array)$feed->custom_options->category); 
								    
									if ( 0 == $category_list_length ) {
										echo 'Uncategorized';
									} else if ( 1 == $category_list_length ) {
										echo isset($category_list[0]) ? $category_list[0] : '';   
									} else {
										echo $category_list_length . ' selected';
									}
								}
							?>
						</td>
						<td>
							<?php
								foreach ( $blogusers as $user ) {
									$author_name = $user->display_name;
									$author_id = $user->ID;
									if ( $author_id == $feed->custom_options->author_id ) {
										echo $author_name = ( strlen( $author_name ) > 8 ) ? substr( $author_name, 0, 8 ) . '...' : $author_name;
									}
								}
							?>
						</td>
						<td>
							<?php echo $click_to_play = $feed->auto_play ? 'Auto' : 'Click'; ?>
						</td>
						<td>
							<?php echo $publish = $feed->custom_options->publish ? 'Publish' : 'Draft'; ?>
						</td>
						<?php
							if ( isset( $form['action'], $_GET['feed_id'] ) && 'modify' == $form['action'] && $feedId == $_GET['feed_id'] ) {
								$class_preview_button = "hide-button";
								$text_edit_button     = "editing";
								$class_edit_button    = "display-element";
								$class_delete_button  = "display-element";
							} else if ( isset( $form['action'] ) && 'modify' == $form['action'] ) {
								$class_preview_button = "hide-button";
								$text_edit_button     = "edit";
								$class_edit_button    = "hide-button";
								$class_delete_button  = "hide-button";
							} else {
								$class_preview_button = "display-element";
								$text_edit_button     = "edit";
								$class_edit_button    = "display-element";
								$class_delete_button  = "display-element";
							}
						?>
						<td>
							<a href="#"  data-id="<?php echo esc_attr( $feedId ); ?>" class="<?php echo esc_attr( $class_preview_button ); ?> btn-preview-feed" >preview</a>
						</td>
						<td>
							<?php
								if ( isset( $form['action'], $_GET['feed_id'] ) && 'modify' == $form['action'] && $feedId == $_GET['feed_id'] ) {
									echo $text_edit_button;
								} else {
							?>
								<a href="admin.php?page=gp-autoposter&action=edit-feed&feed_id=<?php echo $feedId; ?>" id="btn-update-<?php echo $feedId; ?>" class="<?php echo $class_edit_button; ?> btn-update-feed"><?php echo esc_html( $text_edit_button ); ?></a>
							<?php } ?>
						</td>
						<td>
							<input type="button" class="btn-delete <?php echo $class_delete_button; ?>" value="<?php esc_attr( _e( 'x' ) ); ?>" onclick="GrabPressAutoposter.deleteFeed( <?php echo esc_js( $feedId ); ?> );" />
						</td>
					</tr>
				</form>
			<?php } ?>
		</table>
	</div>
	<div class="result"></div>
	<p style="display:none" id="existing_keywords">
		<?php foreach ( $keywords as $key => $value ) { ?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
		<?php } ?>
	</p>
	<p style="display:none" id="exact_keywords">
		<?php foreach ( $phrase as $key => $value ) { ?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
		<?php }?>
	</p>
</fieldset>
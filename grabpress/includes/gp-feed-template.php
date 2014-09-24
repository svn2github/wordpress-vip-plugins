<?php
	$is_edit = 'edit-feed' == $form['action'] || 'modify' == $form['action'];
?>
<div class="wrap">
<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'images/logo-dark.png' ); ?>" alt="Logo" />
	<h2>GrabPress: Autopost Videos by Category and Keywords</h2>
	<p>Feed your blog with fresh video content.</p>
	<fieldset id="create-form" class="<?php echo esc_attr( $is_edit ? 'edit-mode' : '' )?>">
		<legend><?php echo esc_html( $is_edit ? 'Edit' : 'Create' ); ?> Feed</legend>
		<?php
			$rpc_url = get_bloginfo( 'url' ) . '/xmlrpc.php';
			$connector_id = Grabpress_API::get_connector_id();
		?>
		<form method="post" id="form-create-feed">
			<?php if ( isset( $form['feed_id'] ) && 0 < $form['feed_id'] ) {
				$feed_id = $form['feed_id'];
			?>
				<input type="hidden"  name="feed_id" value="<?php echo $feed_id; ?>" />
			<?php } ?>
			<?php if ( isset( $form['active'] ) ) {
				$active = $form['active'];
			?>
				<input type="hidden"  name="active" value="<?php echo esc_attr( $active ); ?>" />
			<?php } ?>
			<?php
				if ( isset( $form['referer'] ) ) {
					$referer = ($form['referer'] == 'edit') ? 'edit' : 'create';
				} else {
					$referer = 'create';
				}
				if ( $is_edit ) {
					$value = ( 'modify' == $form['action'] ) ? 'modify' : 'update';
				} else {
					$value = 'update';
				}
			?>
			<input type="hidden"  name="referer" value="<?php echo esc_attr( $referer ); ?>" />
			<input type="hidden"  name="action" value="<?php echo esc_attr( $value ); ?>" />
			<input type="hidden"  name="feed_id" value="<?php echo esc_attr( isset( $_GET['feed_id'] ) ? $_GET['feed_id'] : '' ); ?>" />
			<table class="form-table grabpress-table">
				<?php if ( DEVELOPMENT_ENV == Grabpress::$environment ) { ?>
					<tr valign="bottom">
						<th scope="row">Plug-in Version &amp; Build Number</th>
						<td><?php echo esc_html( Grabpress::$version ); ?></td>
					</tr>
				<?php } ?>
				<tr valign="bottom">
					<th scope="row">API Key</th>
						<td id="api-key"><?php echo Grabpress::$api_key; ?></td>
				</tr>
				<tr>
					<td style ="padding: 0 0;" ><h4>Search Criteria</h4></td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Feed Name</th>
					<td>
						<?php $feed_date = date( 'YmdHis' ); ?>
						<input type="hidden" name="feed_date" value="<?php echo esc_attr( $feed_date = isset( $form['feed_date'] ) ? $form['feed_date'] : $feed_date ); ?>" id="feed_date" />
						<?php $name = isset( $form['name'] ) ? urldecode( $form['name'] ) : $feed_date; ?>
						<input type="text" name="name" id="name" class="ui-autocomplete-input" value="<?php echo esc_attr( $name ); ?>" maxlength="14" />
						<span class="description">A unique name of 6-14 characters. We encourage customizing it.</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Grab Video Categories<span class="asterisk">*</span></th>
					<td>
						<input type="hidden" name="channels_total" value="<?php echo $channels_total; ?>" id="channels_total" />
						<select  style="<?php esc_attr( Grabpress::outline_invalid() ); ?>" name="channels[]" id="channel-select" class="channel-select multiselect" multiple="multiple" style="width:500px" >
							<?php
								if ( ! array_key_exists( 'channels', $form ) ) {
									$form['channels'] = array();
								}

								if ( is_array( $form['channels'] ) ) {
									$channels = $form['channels'];
								} else {
									$channels = explode( ',', rawurldecode( $form['channels'] ) );
								}

								// In the edit mode, video categories may return nothing if all options are selected
								$selectedAllVideoCategories = false;
								if( $is_edit && isset( $form['channels'] ) && count( $form['channels'] ) == 1 && empty( $form['channels'][0]) ) {
									$selectedAllVideoCategories = true;
								}

								foreach ( $list_channels as $record ) {
									$channel = $record->category;
									$name = $channel->name;
									$id = $channel->id;

									if ( $is_edit && $selectedAllVideoCategories == false ) {
										$selected = ( in_array( $name, $channels ) ) ? 'selected="selected"' : '';
									} else {
										$selected = 'selected="selected"';
									}
									echo '<option value = "' . $name . '" ' . $selected . '>' . $name . '</option>';
								}
							?>
						</select>
						<span class="description">Add or remove specific video categories from this feed</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Keywords</th>
					<td>
						<input type="text" name="keywords_and" id="keywords_and" class="ui-autocomplete-input" value="<?php echo esc_attr( $form['keywords_and'] ); ?>" maxlength="255" />
						<span class="description">Default search setting is 'all of these words'</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Exclude these keywords</th>
					<td>
						<input type="text" name="keywords_not" id="keywords_not" value="<?php echo esc_attr( $form['keywords_not'] ); ?>" maxlength="255" />
						<span class="description">Exclude these keywords</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Any of the keywords</th>
					<td>
						<input type="text" name="keywords_or" id="keywords_or" class="ui-autocomplete-input" value="<?php echo esc_attr( $form['keywords_or'] ); ?>" maxlength="255" />
						<span class="description">Any of these keywords</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Exact phrase</th>
					<td>
						<input type="text" name="keywords_phrase" id="keywords_phrase" class="ui-autocomplete-input" value="<?php echo esc_attr( $form['keywords_phrase'] ); ?>" maxlength="255" />
						<span class="description">Exact phrase</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Content Providers</th>
					<td>
						<input type="hidden" name="providers_total" value="<?php echo esc_attr( $providers_total ); ?>" class="providers_total" id="providers_total" />
						<select name="providers[]" id="provider-select" class="multiselect" multiple="multiple" style="<?php esc_attr( Grabpress::outline_invalid() ); ?>" onchange="GrabPressAutoposter.doValidation()">
							<?php

								// In the edit mode, content providers return nothing if all options are selected
								$selectedAllProvides = false;
								if( $is_edit && isset( $form['providers'] ) && count( $form['providers'] ) == 1 && empty( $form['providers'][0]) ) {
									$selectedAllProvides = true;
								}

								foreach ( $list_providers as $record_provider ) {
									$provider = $record_provider->provider;
									$provider_name = $provider->name;
									$provider_id = $provider->id;
									if ( $is_edit && $selectedAllProvides == false ) {
										$provider_selected = ( in_array( $provider_id, $form['providers'] ) ) ? 'selected="selected"' : '';
									} else {
										$provider_selected = 'selected="selected"';
									}
									echo '<option ' . $provider_selected . ' value = "' . $provider_id . '">' . $provider_name . '</option>\n';
								}
							?>
						</select>
						<span class="description">Add or remove specific providers content from this feed</span>
					</td>
				</tr>
				<tr valign="bottom">
					<td colspan="2" class="button-tip">
						<input type="button" onclick="GrabPressAutoposter.previewVideos()" class="button-secondary" disabled="disabled" value="<?php esc_attr( $is_edit ? _e( 'Preview Changes' ) : _e( 'Preview Feed' ) ); ?>" id="btn-preview-feed" />
						<span class="hide preview-btn-text">Click here to sample the kinds of videos that will be auto posted by this feed in the future.</span>
					</td>
				</tr>
				<tr>
					<td><h4>Publish Settings</h4></td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Schedule<span class="asterisk">*</span></th>
					<td>
						<select name="schedule" id="schedule-select" class="schedule-select" style="width:90px;" >
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
										15*60,
										30*60,
										45*60,
										60*60,
										120*60,
										360*60,
										720*60,
										1440*60,
										2880*60,
										4320*60,
									);
								}
								else {
									$times = array(
										'06 hrs',
										'12 hrs',
										'01 day',
										'02 days',
										'03 days',
									);
									$values = array(
										360*60,
										720*60,
										1440*60,
										2880*60,
										4320*60,
									);
								}

								if ( ! isset( $form['schedule'] ) ) {
									for ( $o = 0; $o < count( $times ); $o++ ) {
										$time = $times[ $o ];
										$value = $values[ $o ];
										echo '<option value="' . $value . '" >' . $time . '</option>\n';
									}
								} else {
									for ( $o = 0; $o < count( $times ); $o++ ) {
										$time = $times[ $o ];
										$value = $values[ $o ];
										$selected = $value == $form['schedule'] ? 'selected="selected"' : '';
										echo '<option value="' . $value . '"'.$selected.' >' . $time . '</option>\n';
									}
								}
							?>
						</select>
						<span class="description">Determine how often to search (posts created only if new matching videos have been added Grab's catalog)</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Max Results<span class="asterisk">*</span></th>
					<td>

						<select name="limit" id="limit-select" class="limit-select" style="width:60px;" >
							<?php
								for ( $o = 1; $o < 6; $o++ ) {
									$selected = ( ( isset( $form['limit'] ) ) && ( $o == $form['limit'] ) ) ? 'selected="selected"' : '';
									echo '<option value="' . $o . '" '.$selected.'>' . $o . '</option>\n';
								}
							?>
						</select>
						<span class="description">Indicate the maximum number of videos to grab at a time</span>
					</td>
				</tr>
				<?php 
					 @$cat_ids = get_all_category_ids();
					 /*var_dump($form);*/
					 $feed_categories = is_array( $form['category'] ) ? $form['category'] : '';
					 /*var_dump($form);*/

				?>
				<tr valign="bottom">
					<th scope="row">Post Categories</th>
					<td>
						<select name="category[]" id="cat" class="postform" multiple="multiple" >		
					
						 <?php
								foreach ( $cat_ids as $cat_id ) {
									$cat_selected = '';
									if ( in_array( $cat_id, $feed_categories ) ) {
											$cat_selected = 'selected="selected"';
									}
							?>
								<option class="level-0" value="<?php echo esc_attr( $cat_id ); ?>" <?php echo esc_html( $cat_selected ); ?> > <?php echo esc_html( get_cat_name( $cat_id ) ); ?></option>
							<?php } ?>
						</select>
						<span class="description">If no selection is made, your default category '<?php echo esc_attr( get_cat_name( '1' ) ); ?>' will be used.</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Post Author<span class="asterisk">*</span></th>
					<td>
						<select name="author" id="author_id" class="author-select" >
							<?php
								foreach ( $blogusers as $user ) {
									$author_name = $user->display_name;
									$author_id = $user->ID;
									if ( 'GrabPress' != $author_name ) {
										$selected = ( ( isset( $form['author'] ) ) && ( $form['author'] == $author_id ) ) ? 'selected="selected"' : '';
										echo '<option value = "' . $author_id . '" ' . $selected . '>' . $author_name . '</option>\n';
									}
								}
							?>
						</select>
						<span class="description">Select the default WordPress user to credit as author of the posts from this feed</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Player Mode<span class="asterisk">*</span></th>
					<td>
						<?php
							if ( isset( $form['click_to_play'] ) && '1' == $form['click_to_play'] ) {
								$ctp_checked_auto = 'checked="checked"';
								$ctp_checked_click = '';
							} else {
								$ctp_checked_auto = '';
								$ctp_checked_click = 'checked="checked"';
							}
						?>
						<input type="radio" name="click_to_play" value="1" <?php echo $ctp_checked_auto; ?> /> Auto-Play
						<input type="radio" name="click_to_play" value="0" <?php echo $ctp_checked_click; ?> /> Click-to-Play
						<span class="description">(this is likely to result in fewer ad impressions <a href="#" onclick='return false;' id="learn-more">learn more</a>)</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Delivery Mode<span class="asterisk">*</span></th>
					<td>
						<?php
							if ( isset( $form['publish'] ) && '1' == $form['publish'] ) {
								$publish_checked_draft = '';
								$publish_checked_automatic = 'checked="checked"';
							} else {
								$publish_checked_draft = 'checked="checked"';
								$publish_checked_automatic = '';
							}
						?>
						<input type="radio" name="publish" value="0" <?php echo $publish_checked_draft; ?> /> Create Drafts to be moderated and published manually
						<input type="radio" name="publish" value="1" <?php echo $publish_checked_automatic; ?> /> Publish Posts Automatically
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Exclude Tags</th>
					<td>
						<input type="text" maxlength="255" name="exclude_tags" id="exclude_tags" value="<?php echo esc_attr( isset( $form['exclude_tags'] ) ? $form['exclude_tags'] : '' ); ?>" />
						<span class="description">Enter tags you want to exclude from each post for this feed, separated by commas</span>
					</td>
				</tr>
				<tr valign="bottom">
					<th scope="row">Feed Tags</th>
					<td>
						<input type="text" maxlength="255" name="include_tags" id="include_tags" value="<?php echo esc_attr( isset( $form['include_tags'] ) ? $form['include_tags'] : '' ); ?>" />
						<span class="description">Enter tags you want add to each post for this feed, separated by commas</span>
					</td>
				</tr>
				<tr valign="bottom">
					<td class="button-tip" colspan="2">
						<?php $click = ( $is_edit ) ? 'onclick="GrabPressAutoposter.validateKeywords(\'update\');"' : 'onclick="GrabPressAutoposter.validateKeywords();"' ?>
						<input type="button" class="button-primary" disabled="disabled" value="<?php esc_attr( $is_edit ? _e( 'Save Changes' ) : _e( 'Create Feed' ) ); ?>" id="btn-create-feed" <?php echo $click; ?>  />
						<a id="reset-form" href="#">reset form</a>
						<?php if ( $is_edit ) { ?>
							<a href="#" id="cancel-editing" >cancel editing</a>
						<?php } ?>
						<span class="description" style="<?php esc_attr( Grabpress::outline_invalid() ); ?>color:red"> <?php echo esc_html( Grabpress::$feed_message ); ?> </span>
					</td>
				</tr>
			</table>
			<?php if ( $localhost = false ) { ?>
			<div class="form-overlay"></div>
			<div class="form-overlay-message">
				<h3>You need a static IP address or DNS/Domain name to create an Autoposter Connector for GrabPress</h3>
				<p>To use the GrabPress Autoposter functionality, your WordPress instance must be hosted with a static IP address or a web server with an domain (DNS) name. Autoposter is not compatible with a localhost environment. Please contact our support team if you have further questions.</p>
			</div>
		<?php } ?>
		</form>
	</fieldset>
	<?php if ( $is_edit ) { ?>
		<span class="edit-form-text display-element" >Please use the form above to edit the settings of the feed marked "editing" below</span>
	<?php } ?>
	<?php
		try {
			$feeds = Grabpress_API::get_feeds();
		} catch( Exception $e ) {
			$feeds = array();
		}
		$num_feeds = count( $feeds );
		if ( $num_feeds > 0 ) {
			echo Grabpress::render( 'includes/gp-manage-feeds.php',
				array(
					'form'            => $form,
					'list_providers'  => $list_providers,
					'providers_total' => $providers_total,
					'list_channels'   => $list_channels,
					'channels_total'  => $channels_total,
					'blogusers'       => $blogusers,
				)
			);
		}
	?>
</div>
<div id="dialog" title="Name your feed">
	<p style="color:red; font-size:14px;"><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 0 0;"></span>Please Name Your Feed</p>
	<p>You have not provided a custom feed name. You may keep it as-is, but we
	recommend customizing it below.</p>
	<input type="text" name="dialog-name" id="dialog-name" maxlength="14" />
</div>
<div id="keywords_dialog" title="Duplicated keyword">
	<input type="hidden" value="" id="edit_feed" />
	<p></p>
</div>
<?php if ( isset($show_auth_overlay) ) { ?>
<div id="wp-oauth-overlay" class="form-overlay">
	<div id="wp-oauth-content">
		<?php if ( $show_retry_message ) { ?>
			<p class="wp-oauth-retry-message">Something went wrong with your first WordPress authorization attempt. Please try again.</p>
		<?php } ?>
		<p>To use the Autoposter feature, you need to connect a WordPress Editor or Administrator account for this blog to the Grab Autoposter Application. </p>
		<div id="wp-connect-btn" class="wp-connect"></div>
		<p>You already have access to our diverse catalog of premium videos, now it's time to add content automation to your site. Once you authenticate via WordPress above, GrabPress will connect your WordPress account with your bog, and the Autoposter feature will be enabled.</p>
		<p>Note: Due to WordPress VIP code standards and requirements, we’re now using the WordPress API for WordPress user authentication and for remotely posting videos to your blog with Autoposter.</p>
		<h3>Why use Autoposter?</h3>
		<p>Creating an Autoposter connection to your GrabPress installation will allow the plugin to deliver video-centric posts directly to your WordPress site, based on search criteria you define. GrabPress Autoposter can publish new posts (as any author, or to any category) at selected intervals, either to a drafts folder, or automatically posted to your blog. Regardless of your posting preferences, your Autoposter feeds will keep you on top of the trends that matter to you with the latest videos from the best video producers in the industry.</p>
		<h3>WordPress Authentication for Autoposter</h3>
		<p>To use Autoposter, GrabPress needs access to your blog to generate new video posts or drafts. You can provide your own credentials, but we recommend that you create a new Editor account just for GrabPress. You can connect Autoposter to any WordPress user account with Editor or Administrator privileges.</p>
	</div>
</div>
<?php } ?>
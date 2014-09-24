<!--[if IE]>
 <style type="text/css">
	 .reveal-modal-bg {
			 background: transparent;
			 filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#70000000,endColorstr=#70000000);
			 zoom: 1;
		}
	</style>
<![endif]-->
<form method="post" id="form-dashboard">
	<input type="hidden" name="environment" value="<?php echo esc_attr( Grabpress::$environment ); ?>" id ="environment" />
	<input type="hidden" name="embed_id" value="<?php echo esc_attr( $embed_id ); ?>" id ="embed_id" />
	<div class="wrap" >
		<div id="t">
			<div id="b">
				<div class="container-fluid">
					<div class="row-fluid watchlist-wrap">
						<div class="span4 watchlist">
							<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'images/logo-light.png' ); ?>" alt="Logo" />
							<div class="tabbable panel">
								<ul class="nav nav-tabs">
									<li class="active">
										<a href="#watchlist-tab1" data-toggle="tab">Watchlist</a>
									</li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane active" id="watchlist-tab1">
										<div class="accordion" id="accordion2">
										<?php $i = 1; if ( empty( $watchlist ) ) { ?>
											<div class="accordion-group">
												<div class="accordion-heading">
													<div class="accordion-left"></div>
													<div class="accordion-center"><span class="accordion-toggle">&nbsp;</span></div>
													<div class="accordion-right"></div>
												</div>
												<div id="collapse<?php echo $i;?>" class="accordion-body" style="height:95px;">
													<div class="accordion-inner">
														<span class="accordion-warning">Add a feed to your watch list in the Feed Activity panel</span>
													</div>
												</div>
											</div>
										<?php } else { foreach ( $watchlist as $item ) { ?>
											<div class="accordion-group">
												<div class="accordion-heading">
													<div class="accordion-left"></div>
													<div class="accordion-center">
														<a class="accordion-toggle feed-title" data-guid="v<?php echo $item->video->guid;?>" data-toggle="collapse" data-parent="#accordion2" href="#collapse<?php echo $i;?>"><?php echo $item->video->title;?></a>
													</div>
													<div class="accordion-right"></div>
												</div>
												<div id="collapse<?php echo $i;?>" class="accordion-body collapse in" style="<?php echo esc_attr( 1 == $i ? '': 'display:none;' ); ?>">
													<div class="accordion-inner"></div>
												</div>
											</div>
										<?php $i++; } } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="span8 right-pane" >
						<div class="row-fluid">
							<div class="span4">
								<div class="row-fluid">
									<div class="span12 messages">
										<div class="tabbable panel" id="messages-tabs">
											<ul class="nav nav-tabs">
												<li><a href="#messages-tab1">Messages</a></li>
												<?php if ( ! empty( $alerts ) || ! empty( $errors ) ) { ?>
													<li><a href="#messages-tab2">Alerts</a></li>
												<?php } ?>
											</ul>
											<div class="tab-pane active nano" id="messages-tab1">
												<div class="content">
													<?php foreach ( $messages as $msg ) { ?>
														<p>
															<?php echo html_entity_decode( $msg->message->body, ENT_QUOTES, 'UTF-8' ); ?>
														</p>
													<?php }?>
												</div>
											</div>
											<?php if ( ! empty( $alerts ) || ! empty( $errors ) ) { ?>
												<div class="tab-pane active nano" id="messages-tab2">
													<div class="content">
														<?php if ( ! empty( $alerts ) ) {
															foreach ( $alerts as $alert ) { ?>
															<p id="<?php echo esc_attr( $alert->message->id ); ?>"><?php echo html_entity_decode( $alert->message->body, ENT_QUOTES, 'UTF-8' ); ?><a onclick="GrabPressDashboard.deleteAlert( <?php echo esc_js( $alert->message->id ); ?> );" href="#"><span class="delete-alert">&nbsp;</span></a><span style="clear:both; display: block"></span></p>
														<?php } } ?>
														<?php if ( ! empty( $errors ) ) {
															foreach( $errors as $err ) { ?>
															<p id="<?php echo esc_attr( $err->message->id ); ?>"><?php echo html_entity_decode( $err->message->body, ENT_QUOTES, 'UTF-8' ); ?><a onclick="GrabPressDashboard.deleteAlert( <?php echo esc_js( $err->message->id ); ?> );" href="#"><span class="delete-alert">&nbsp;</span></a><span style="clear:both; display: block"></span></p>
														<?php } } ?>
													</div>
												</div>
											<?php } ?>
										</div>
									</div>
								</div>
								<div class="row-fluid">
									<div class="span12 welcome">
										<div class="panel">
											<div class="tab-content">
												<div class="tab-pane active noscroll" id="messages-tab1">
													<div class="content">
													<?php
														$num_feeds = count( $feeds );
														if ( 'account-unlinked' == $publisher_status && Grabpress::check_permissions_for( 'gp-account' ) ) {
																$create = isset( $_REQUEST['page'], $_REQUEST['action'] ) && 'account' == $_REQUEST['page'] && 'create' == $_REQUEST['action'] ? 'Create' : '<a href="admin.php?page=gp-account&action=create">Create</a>';
																$link =  isset( $_REQUEST['page'], $_REQUEST[ 'action']) && 'account' == $_REQUEST['page'] && 'default' == $_REQUEST['action'] ? 'link an existing' : '<a href="admin.php?page=gp-account&action=default">link an existing</a>';
															echo 'Want to earn money?' . $create . ' or ' . $link . ' Grab Publisher account.';
														} else if ( 0 == $num_feeds && Grabpress::check_permissions_for( 'gp-autoposter' ) ) {
																$admin = get_admin_url();
																$admin_page = $admin . 'admin.php?page=gp-autoposter';
																$here = '<a href="' . $admin_page . '">here</a>';
																echo 'Thank you for activating GrabPress. Try creating your first Autoposter feed ' . $here . ".";
														} else {
															$p = count( $pills );
															$p--;
															$r = rand( 0, $p );
															echo html_entity_decode( $pills[ $r ]->message->body );
														} ?>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="feeds">
								<?php
									$admin = get_admin_url();
									$admin_page = $admin . 'admin.php?page=gp-account';
									if ( Grabpress::check_permissions_for( 'gp-account' ) ) {
								?>
									<div id="btn-account-settings">
										<div class="accordion-left">&nbsp;</div>
										<div class="accordion-center">
											<a href="#" class="big-link" data-reveal-id="AccoutDetails_Modal" data-animation="fade">Account Settings</a>
										</div>
										<div class="accordion-right">&nbsp;</div>
									</div>
								<?php } ?>
								<div id="publisher-account-status" value="Publisher Account Status" class="<?php echo esc_attr( $publisher_status ); ?>"></div>
								<div class="panel nano">
									<div class="content">
									<h3>Feed Activity (Latest Auto-post)</h3>
									<a href="#" id="help">Help</a>
									<table class="table table-hover">
										<thead>
											<tr>
												<th>Feed Name</th>
												<th>Schedule</th>
												<th>Health</th>
												<th>Watchlist</th>
												<th>&nbsp;</th>
											</tr>
										</thead>
										<tbody>
										<?php
											$times = array(
												15 * 60      => '15 mins',
												30 * 60      => '30  mins',
												45 * 60      => '45 mins',
												60 * 60      => '1 hr',
												120 * 60     => '2 hrs',
												360 * 60     => '6 hrs',
												720 * 60     => '12 hrs',
												24 * 60 * 60 => '1 day',
												48 * 60 * 60 => '2 days',
												72 * 60 * 60 => '3 days',
											);

											$num_feeds = count( $feeds );

											for ( $n = 0; $n < $num_feeds; $n++ ) {
												$feed = $feeds[ $n ]->feed;
												$feedId = $feed->id;
												$schedule = $feed->update_frequency;
												$schedule = $times[ $schedule ];
												$rowColor = ( $n % 2 ) == 1 ? 'odd' : 'even';
										?>
											<tr id="tr-<?php echo esc_attr( $feedId ); ?>" class="<?php echo esc_attr( $rowColor ); ?>">
												<td><?php echo urldecode( $feed->name ); ?></td>
												<td><?php echo esc_html( $schedule ); ?></td>
												<?php
													$feed_health_value = $feed->submissions . '/' . $feed->posts_per_update;
													if ( 0.8 < $feed->feed_health ) {
														$feed_health = 'feed-health-100';
													} else if ( 0.6 < $feed->feed_health ) {
														$feed_health = 'feed-health-80';
													} else if ( 0.4 < $feed->feed_health ) {
														$feed_health = 'feed-health-60';
													} else if ( 0.2 < $feed->feed_health ) {
														$feed_health = 'feed-health-40';
													} else if ( 0 < $feed->feed_health ) {
														$feed_health = 'feed-health-20';
													} else {
														$feed_health = 'feed-health-0';
													}
												?>
												<td class="<?php echo esc_attr( $feed_health ); ?>"><?php echo $feed_health_value; ?></td>
												<td class="watch">
													<?php
														if ( '1' == $feed->watchlist ) {
															echo '<input type="button" value="0" class="watchlist-check watch-on" id="watchlist-check-' . $feedId . '" >';
														} else {
															echo '<input type="button" value="1" class="watchlist-check watch-off" id="watchlist-check-'. $feedId . '" >';
														}
													?>
												</td>
												<td><a href="#" class="big-link" data-reveal-id="FeedDetails_Modal_<?php echo esc_attr( $feedId ); ?>" data-animation="fade">Details</a>
												<i class="icon-pencil"></i></td>
											</tr>
										<?php } ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
					<div clas="row-fluid">
						<div class="span12 faq">
							<div class="tabbable panel">
								<ul class="nav nav-tabs">
									<li class="active"><a href="#faq-tab1" data-toggle="tab">Resources</a></li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane active" id="faq-tab1">
										<p> Read more about GrabMedia and our GrabPress and Autoposter technology:</p>
										<?php foreach ( $resources as $msg ) { ?>
											<p><?php echo html_entity_decode( $msg->message->body, ENT_QUOTES, 'UTF-8' ); ?></p>
										<?php }?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
<?php
	for ( $n = 0; $n < $num_feeds; $n++ ) {
		$feed = $feeds[ $n ]->feed;
		$feedId = $feed->id;
?>
	<div id="FeedDetails_Modal_<?php echo esc_attr( $feedId ); ?>" class="reveal-modal">
		<p>Feed Details</p>
		<div class="infoBox">
			<h2 style="text-align:center;"><?php echo urldecode( $feed->name ); ?></h2>
			<p style="text-align:center;">Created at: <?php echo date( 'n/j/Y g:iA T', strtotime( $feed->created_at ) ); ?></p>
			<p class="bold">Search Criteria:</p>
			<?php
				$url = array();
				parse_str( parse_url( $feed->url, PHP_URL_QUERY ), $url );
				Grabpress::escape_params_template( $url );
			?>
			<p>
				Grab Video Categories:
				<?php
					if ( '' == $url['amp;categories'] ) {
						echo 'All Video Categories';
					} else {
						echo str_replace( ',', ', ', $url['amp;categories'] );
					}
				?>
				<br />
				Keywords (All):
				<?php
					if ( isset( $url['keywords_and'] ) ) {
						echo str_replace( ',', ', ', $url['keywords_and'] );
					}
				?>
				<br />
				Excluded Keywords:
				<?php
					if ( isset( $url['amp;keywords_not'] ) ) {
						echo str_replace( ',', ', ', $url['amp;keywords_not'] );
					}
				?>
				<br />
				Keywords (Any):
				<?php
					if ( isset( $url['amp;keywords'] ) ) {
						echo str_replace( ',', ', ', $url['amp;keywords'] );
					}
				?>
				<br />
				Keywords (Exact Phrase):
				<?php
					if( isset( $url['amp;keywords_phrase'] ) ) {
						echo str_replace( ',', ', ', $url['amp;keywords_phrase'] );
					}
				?>
				<br />
				Content Providers:
				<?php
					$providers = explode( ',' , $url['amp;providers'] ); // providers chosen by the user
					$providers_selected = count($providers);
					if ( $url['amp;providers'] == '' ) {
						echo 'All Content Providers';
					} else {
						$num_providers = count( $list_providers );
						$num_providers_selected = 0;
						$content_providers = '';
						foreach ( $list_providers as $record_provider ) {
							$provider = $record_provider->provider;
							$provider_name = $provider->name;
							$provider_id = $provider->id;
							if( in_array( $provider_id, $providers ) ) {
								$num_providers_selected++;
								$content_providers .= $provider_name . ', ';
							}
						}
						echo '<a href="#" title="' . $content_providers . '">' . $num_providers_selected . ' of ' . $num_providers . ' Providers Selected</a>';
					}
				?>
				<br />
			</p>
			<p class="bold">Publish Settings:</p>
			<p>
				Schedule: <?php echo isset( $feed->update_frequency ) ? $times[ $feed->update_frequency ] : ''; ?> (last update: <?php echo date( 'n/j/Y g:iA T', strtotime( $feed->updated_at ) ); ?>)<br />
				Maximum Posts per update: <?php echo $feed->posts_per_update; ?><br />
				Post Categories:
				<?php
				
					$category_list_length = isset($feed->custom_options->category ) ? 
					    count( $feed->custom_options->category ) : 0;
					if( 0 == $category_list_length ) {
						echo 'Uncategorized';
					} else {
						// Create iteration tracker
						$i = 0;
						foreach ( $feed->custom_options->category as $categ ) {
							$i++;
							if( $i == $category_list_length ) {
								echo $categ;
							} else {
								echo $categ . ', ';
							}
						}
					}
				?>
				<br />
				Author: <?php  the_author_meta( 'nickname' , $feed->custom_options->author_id ); ?>
				<br />
				Player Mode: <?php echo $auto_play = $feed->auto_play ? 'Auto-Play' : 'Click-to-Play'; ?>
				<br />
				Delivery Mode: <?php echo $publish = $feed->custom_options->publish ? 'Publish Posts Automatically' : 'Draft'; ?>
			</p>
		</div>
		<div class="btn-modal-box">
				<div class="accordion-left">&nbsp;</div>
				<div class="accordion-center">
					<a class="close-reveal-modal" href="#">Back to Dashboard</a>
				</div>
				<div class="accordion-right">&nbsp;</div>
		</div>
		<?php if ( Grabpress::check_permissions_for( 'gp-autoposter' ) ) { ?>
			<div class="btn-modal-box">
					<div class="accordion-left">&nbsp;</div>
					<div class="accordion-center">
						<a href="admin.php?page=gp-autoposter&action=edit-feed&feed_id=<?php echo esc_attr( $feedId ); ?>" id="btn-update-<?php echo esc_attr( $feedId ); ?>" class="btn-update-feed">Edit</a>
					</div>
					<div class="accordion-right">&nbsp;</div>
			</div>
		<?php } ?>
	</div>
<?php } ?>
<div id="AccoutDetails_Modal" class="reveal-modal">
	<p>Account Details</p>
	<div class="infoBox">
		<p>Linked Account Email Address: <br />
		<?php
			$user = Grabpress_API::get_user();
			$linked = isset( $user->email );
			if( $linked ) {
				 echo esc_html( $user->email );
			} else {
		?>
			<p>This installation is not linked to a Publisher account.<br />
			Linking GrabPress to your account allows us to keep track of the video ads displayed with your Grab content and make sure you get paid.</p>
		<?php } ?>
		</p>
		<p>API Key: <br /><?php echo esc_html( get_option( 'grabpress_key' ) ); ?><input type="hidden" value="<?php echo esc_attr( get_option( 'grabpress_key' ) ); ?>" id="fe_text" /></p>
	</div>
	<?php if ( Grabpress::check_permissions_for( 'gp-account' ) ) { ?>
		<div class="btn-modal-box">
			<div class="accordion-left">&nbsp;</div>
			<div class="accordion-center"><a href="<?php echo esc_url( $admin_page ); ?>" >Account Settings</a></div>
			<div class="accordion-right">&nbsp;</div>
		</div>
	<?php } ?>
	<div class="btn-modal-box">
			<div class="accordion-left">&nbsp;</div>
			<div class="accordion-center"><a class="close-reveal-modal" href="#">Back to Dashboard</a></div>
			<div class="accordion-right">&nbsp;</div>
	</div>
</div>
<div class="dashgear grabgear">
     <?php echo '<img src="' . plugin_dir_url( __FILE__ ) . 'images/grabgear.gif" alt="Grab">'; ?>
</div>
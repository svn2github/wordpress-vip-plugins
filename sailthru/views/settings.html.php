									
					<div id="dashboard-widgets-wrap">

						<div id="sailthru-template-choices" class="metabox-holder columns-2">

							<div class="postbox-container">
								<div class="meta-box-sortables">
									<div id="sailthru-choose-template" class="postbox">


										<div class="inside">
											<?php
												settings_fields( 'sailthru_setup_options' );
												do_settings_sections( 'sailthru_setup_options' );

											?>
										</div>

									</div>
								</div>
							</div>

						</div>

						<div class="clear"></div>
					</div>

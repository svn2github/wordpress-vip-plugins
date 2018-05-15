<script type="text/html" id="tmpl-getty-images-user">
	<# var loginClass = '';

		if(data.loggingIn) {
			loginClass += 'getty-prompt-login getty-logging-in ';
		}
		else if(data.promptLogin) {
			loginClass += 'getty-prompt-login';
		} #>
	<div class="getty-user-panel {{ loginClass }}">
		<span class="getty-user-chevron"><span></span></span>
		<# if(!data.loggedIn) { #>
		<div class="getty-images-login">
			<p class="getty-login-token">
				<textarea name="getty-login-token" />

				<p><?php esc_html_e( "By clicking CONTINUE, you accept our ", 'getty-images' ); ?>
				<a href="http://www.gettyimages.com/company/privacy-policy" target="_blank"><?php esc_html_e( "Privacy Policy", 'getty-images' ); ?></a>
				<?php esc_html_e( " (including Use of Cookies and Other Technologies) and  ", 'getty-images' ); ?>
				<a href="http://www.gettyimages.com/company/terms" target="_blank"><?php esc_html_e( "Terms of Use", 'getty-images' ); ?></a>.</p>

				<div>
					<input id="tracking-checkbox" type="checkbox"/>
					<label for="embed-tacking-opt-in-checkbox" class="embed-tacking-opt-in-checkbox-label">
						<?php esc_html_e( "Yes, I agree to Getty Images using tracking objects to verify user login status and generating cookies for tracking purposes through ", 'getty-images' ); ?>
						<a href="https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage?csw=1" target="_blank"><?php esc_html_e( "Google Analytics", 'getty-images' ); ?></a>
						<?php esc_html_e( " and ", 'getty-images' ); ?>
						<a href="http://www.adobe.com/privacy/marketing-cloud.html?f=2o7" target="_blank"><?php esc_html_e( "Omniture", 'getty-images' ); ?></a>.
					</label>
				</div>
			</p>
			<p class="getty-login-submit">
				<input type="button" class="button-primary getty-login-button" value="<?php esc_attr_e( "Continue", 'getty-images' ); ?>" />
				<a href="javascript:void(0);" class="getty-login-cancel-button"><?php esc_html_e( "Cancel", 'getty-images' ); ?></a>
				<span class="getty-login-spinner"></span>
			</p>
			<# if(data.error) { #>
			<div class="error-message">{{ data.error }}</div>
			<# } #>
		</div><#
		} else { #>
		<div class="logged-in-status">
			<p>
				<strong class="getty-images-username"><?php esc_html_e( "Logged in as: ", 'getty-images' ); ?> {{ data.username }}</strong>
			</p>
			<# if(data.products && data.products.length) { #>
			<p>
				<strong class="getty-images-product-offerings"><?php esc_html_e( "Products: ", 'getty-images' ); ?></strong>
				<# for(var i in data.products) { #>
					<span class="getty-images-product-offering">{{ data.products[i].name }}</span>{{ i < data.products.length - 1 ? ', ' : '' }}
				<# } #>
			</p>
			<# } #>
			<p>
				<a href="#" class="getty-images-logout"><?php esc_html_e( "log out", 'getty-images' ); ?></a>
			</p>
		</div>
		<# } #>
	</div>
</script>

<script type="text/html" id="tmpl-getty-title-bar">
	<h1 class="getty-images-title"><?php esc_html_e( "Getty Images", 'getty-images' ); ?></h1>

	<div class="getty-title-links">
		<# var loggedIn = gettyImages.user.get('loggedIn'); #>
		<# if(data.mode == 'login' && loggedIn) { #>
		<span class="getty-title-link">
			<a class="getty-login-toggle getty-title-link {{ loggedIn ? 'getty-logged-in' : '' }}">{{ loggedIn ? gettyImages.user.get('username') : "<?php esc_html_e( "Log in", 'getty-images' ); ?>" }}</a>
			<div class="getty-user-session"></div>
		</span>
		<# } #>
		<# if(data.mode == 'login' && !loggedIn) { #>
			<a class="getty-title-link getty-mode-change">Change Mode</a>
		<# } else if(data.mode == 'embed') { #>
			<a class="getty-title-link getty-mode-change">Go to Customer Login</a>
		<# } #>

		<a class="getty-title-link getty-about-link"><?php esc_html_e( "About", 'getty-images' ); ?></a>
		<a class="getty-title-link getty-privacy-link" target="_getty" href="http://www.gettyimages.com/Corporate/PrivacyPolicy.aspx"><?php esc_html_e( "Privacy Policy", 'getty-images' ); ?></a>
	</div>
</script>

<script type="text/html" id="tmpl-getty-about-text">
	<a class="getty-about-close getty-about-close-x">X</a>

<?php
	/* TODO: Localize this text. */
	include( __DIR__ . '/getty-about-en-us.html' );
?>

	<p style="text-align: right">
		<a class="getty-about-close"><?php esc_html_e( "Close", 'getty-images' ); ?></a>
	</p>
</script>

<script type="text/html" id="tmpl-getty-attachments-browser">
<div class="getty-session-expired-popup" style="display: none">
	<div class="getty-popup-background"></div>
	<div class="getty-popup-window">
	
	<span class="text">Your previous login has expired, please log in again.</span>
	<div>
		<input type="button" class="button-primary download-button go-to-login-button" value="Go to Login Page"></input>
	</div>
	</div>
</div>
<div class="getty-refine"></div>
<div class="getty-browser-container">
	<table class="getty-browser-flex-container">
		<tbody style="height: 100%">
			<tr>
				<td class="getty-search-toolbar"></td>
			</tr>
			<tr>
				<td class="getty-browser-container">
					<div class="getty-browser" style="display: none">

						<div class="getty-results"></div>
						<div class="getty-search-spinner"></div>
					</div>
					<div  class="getty-landing-container">
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</div>
<div class="getty-sidebar-container">
	<div class="getty-sidebar">
	</div>
</div>
</script>

<script type="text/html" id="tmpl-getty-attachment">
<# var classes = [];
	classes.push('style-' + data.GraphicStyle);
	classes.push('licensing-' + data.LicensingModel);
	classes.push('viewMode-' + data.viewMode);
	#>

<# if ( data.viewMode === 'gridView' ) { #>
	<div class="attachment-preview js--select-attachment getty-attachment {{ classes.join(' ') }}">
		<# if ( data.uploading ) { #>
			<div class="media-progress-bar"><div></div></div>
		<# } else { #>
			<div class="thumbnail">
				<div class="centered">
					<img src="{{ data.url_thumb }}" draggable="false" />
				</div>
			</div>
			<div class="image-details">
				<div class="image-title">{{ data.title }}</div>
				<div class="image-family">{{ data.asset_family }}</div>
				<div class="image-date">{{ data.DateSubmittedString }}</div>
				<div class="image-artist">{{ data.artist }}</div>
				<div class="image-collection">{{ data.collection_name }}</div>
				<div class="image-id">{{ data.id }}</div>
			</div>
		<# } #>

		<# if ( data.buttons.close ) { #>
			<a class="close media-modal-icon" href="#" title="<?php esc_attr_e('Remove', 'getty-images'); ?>"></a>
		<# } #>

		<# if ( data.buttons.check ) { #>
			<a class="check" href="#" title="<?php esc_attr_e('Deselect', 'getty-images'); ?>"><div class="media-modal-icon"></div></a>
		<# } #>
	</div>
<# } else { #>
	<# if ( data.uploading ) { #>
		<div class="media-progress-bar"><div></div></div>
	<# } else { #>
		<div class='js--select-attachment {{ classes.join(' ') }}' style="height:{{ data.height }}px;">
			<img src="{{ data.url_comp }}" draggable="false" height="{{ data.height }}" />
		</div>
	<# } #>

	<# if ( data.buttons.close ) { #>
		<a class="close media-modal-icon" href="#" title="<?php esc_attr_e('Remove', 'getty-images'); ?>"></a>
	<# } #>

	<# if ( data.buttons.check ) { #>
		<a class="check" href="#" title="<?php esc_attr_e('Deselect', 'getty-images'); ?>"><div class="media-modal-icon"></div></a>
	<# } #>
<# } #>
</script>

<script type="text/html" id="tmpl-getty-download-authorizations">
<# if(gettyImages.user.settings.get('mode') != 'embed' && !gettyImages.user.get('loggedIn')) { #>
	<p><?php esc_html_e( "Log in to download images", 'getty-images' ); ?></p>
<# }
else if(gettyImages.user.get('loggedIn') && data.sizesByAgreement) { #>
	<div class="getty-download-authorizations">
		<ul class="getty-download-with">
		<h3>Download options</h3>
		<#
		for(var agreement in data.sizesByAgreement) {
		#>
			<li class="getty-download-auth">
				<h3>
				<# if(_.size(data.sizesByAgreement) > 1) {
					var selected = data.ProductOffering == agreement ? 'checked="checked"' : '';
				#>
					<input type="radio" name="DownloadProductOffering" value="{{ agreement }}" {{ selected }} />
				<# } #>
					{{ gettyImages.getProductName(agreement) }}
				</h3>
			</li>
		<#  } #>
		</ul>

		<select name="DownloadSizeKey">
			<#
			var attachment = data.attachment;

			var sizes = _.sortBy(data.sizesByAgreement[data.ProductOffering], 'bytes');

			for(var i = 0; i < sizes.length; i++) {
				var size = sizes[i];
				var attrs = '';
				var note = '';
				var overageText = '';

				if (size.overage && size.overage.overages_reached) {
					if (size.overage.remaining) {
						overageText = ' (' + gettyImages.text.inOverage + ')';
					} else {
						// Skip this option entirely if there are no remaining overage downloads
						continue;
					}
				}
				
				if(size.downloads_remaining) {
					note = '(' + size.downloads_remaining + ' ' + gettyImages.text.remaining + ')';
				}
				
				if(size.id === data.SelectedDownloadSize.id ) {
					attrs = 'selected="selected"';
				}
				if(attachment && attachment.get('width') == size.width && attachment.get('height') == size.height) {
					attrs = attrs + ' data-downloaded="true"';
					note = <?php echo wp_json_encode( __( "(downloaded)", 'getty-images' ) ); ?>;
				} #>
				<option {{ attrs }} value="{{ size.id }}">
					<#
					if (size.agreement_name) {
					#>	{{ size.agreement_name}}{{ overageText }}: <#
					}
					else {
					#>	{{ gettyImages.getProductName(data.ProductOffering) }}{{ overageText }}:  <#
					}
					#>
					
					{{ size.width }} &times; {{ size.height }}
					<#
						var size = size.bytes;

						if(size > 1024 * 1024) {
							size = new Number(Math.round(size / (1024 * 1024) * 10) / 10).commaString() + '\xA0MB';
						}
						else if(size > 1024) {
							size = new Number(Math.round(size / 1024 * 10) / 10).commaString() + '\xA0KB';
						}
						else {
							size = size + '\xA0B';
						}
					#>
					({{ size }}) {{ note }}
				</option>
			<# } #>
		</select>
	</div>


	<# if(data.SelectedDownloadSize) { 
		var products = gettyImages.user.get('products');
		var product;
		
		for (var i=0; i < products.length; i++) {
			if (products[i].id == data.SelectedDownloadSize.product_id) {
				product = products[i];
				break;
			}
		}
		
		if (product) {
			var requirements = product.download_requirements || {};
			#><div class="getty-download-notes-container">
				<h3><?php esc_html_e("Add notes", 'getty-images'); ?></h3>
				<input id="getty-download-notes" name="getty-download-notes" />
			</div><#
			if (requirements.is_project_code_required) { #>
				<div class="getty-project-code-container">
					<h3><?php esc_html_e("Project codes", 'getty-images'); ?></h3>
					<select id="getty-project-code" name="getty-project-code">
						<option selected disabled><?php esc_html_e("Select project code", 'getty-images'); ?></option>
						<# 
							for (var i=0; i < requirements.project_codes.length; i++) {
								var code = requirements.project_codes[i];
								#><option value="{{code}}">{{code}}</option><#
							}
						#>
					</select>
				</div>
			<# }
		} #>
	<div id="download-options-container" class="getty-filter">
		<div><input id="download_option_media_only" name="download-option" value="media-only" type="radio" checked="checked" <# if (data.SelectedDownloadOption === "media-only"){ #> checked <# } #> /><label for="download_option_media_only"><?php esc_html_e( "Download to Media Library only", 'getty-images' ); ?></label></div>
		<div><input id="download_option_download_as_featured" name="download-option" value="download-as-featured" type="radio" <# if (data.SelectedDownloadOption === "download-as-featured"){ #> checked <# } #>/><label for="download_option_download_as_featured"><?php esc_html_e( "Download and set as featured image", 'getty-images' ); ?></label></div>
		<div><input id="download_option_download_and_insert" name="download-option" value="download-and-insert" type="radio" <# if (data.SelectedDownloadOption === "download-and-insert"){ #> checked <# } #>/><label for="download_option_download_and_insert"><?php esc_html_e( "Download and insert image into post", 'getty-images' ); ?></label></div>
		<div><input id="download_option_download_and_insert_as_featured" name="download-option" value="download-and-insert-as-featured" type="radio" <# if (data.SelectedDownloadOption === "download-and-insert-as-featured"){ #> checked <# } #> /><label for="download_option_download_and_insert_as_featured"><?php esc_html_e( "Download, insert image into post and set as featured image", 'getty-images' ); ?></label></div>
	</div>

	<div class="getty-download">
		<#
			var disabled = data.downloading ? 'disabled="disabled"' : '';
			var text = gettyImages.text.downloadImage;

			if(data.attachment) {
				text = gettyImages.text.reDownloadImage;
			}

			if(data.downloading) {
				text = gettyImages.text.downloading;
			}
		#>
		<div class="getty-download-spinner"></div>
	</div><#
	}
}
else if(data.authorizing) { #>
	<p class="description"><?php esc_html_e( "Downloading authorizations...", 'getty-images' ); ?></p>
<# } #>


	<div id="getty-comp-license-dialog">
		<p class="getty-comp-please-read"><?php esc_html_e( "Please read and accept the following terms before using image comps in your website:", 'getty-images' ); ?></p>
		<div class="getty-comp-license-frame"><?php
			/* TODO: Localize this text. */
			include( __DIR__ . '/getty-comp-license.html' );
		?></div>
	</div>

	</div>
		<div class="getty-comp-buttons">

		<# var canLicense = data.download_sizes && data.download_sizes.length > 0;

		if(gettyImages.user.get('loggedIn')) {
			if(canLicense) {  #>
				<input type="button" class="button-primary download-button" value="<?php esc_attr_e( "Download", 'getty-images' ); ?>" />
				<a class="insert-comp-button"><?php esc_attr_e( "Insert Comp", 'getty-images' ); ?></a>
			<# }
		} else { #>
			<input type="button" class="button-primary insert-comp-button" value="<?php esc_attr_e( "Insert into Post", 'getty-images' ); ?>" />
		<# } #>

		</div>
		<div class="getty-comp-license-chevron"></div>
	</div>


</script>

<script type="text/html" id="tmpl-getty-image-details-list">
	<div class="getty-title">{{ data.title }}</div>

	<div class="getty-artist">Credit: {{ data.artist }}</div>

	<div class="getty-image-id">Image #: {{ data.id }}</div>



	<div class="separator"></div>
	<div class="getty-image-accordion">
		<h3 class="license-info-accordion-header accordion-header"><span class="accordion-title">License Info</span><span class="collapse-icon">+</span></h3>
		<div class="license-info-accordion-content accordion-closed">
			<# var licenseTypeText;
				switch (data.license_model) {
				case 'royaltyfree':
					licenseTypeText = "Creative Royalty Free";
					break;
				case 'rightsmanaged':
					licenseTypeText = "Creative Rights Managed";
					break;
				}
			#>

			<div class="getty-license-info">License type: {{ data.asset_family == "editorial" ? "Editorial" : licenseTypeText }}</div>

			<# if(data.allowed_use && data.allowed_use.how_can_i_use_it) { #>
				<div class="getty-how-can-i-use-it">{{ data.allowed_use.how_can_i_use_it.replace(/\|/g, "").replace(/Learn more/g, "") }}</div>
			<# }#>

		</div>

	</div>

	<div class="separator"></div>
	<div class="getty-image-accordion">
		<h3 class="details-accordion-header accordion-header"><span class="accordion-title">Details</span><span class="collapse-icon">+</span></h3>
		<div class="details-accordion-content accordion-closed">
			<div class="getty-collection">Collection: {{ data.collection_name }}</div>


			<# if(data.allowed_use && data.allowed_use.release_info) { #>
				<div class="getty-release-info">Release Info: {{ data.allowed_use.release_info }}</div>
			<# } #>

			<# if(data.downloadingDetails) { #>
			<dt><?php esc_html_e( "Downloading Details...", 'getty-images' ); ?></dt>
			<# } else if(!data.haveDetails) { #>
			<dt><?php esc_html_e( "Could not not get image details.", 'getty-images' ); ?></dt>
			<# }#>

			<# if(data.ReleaseMessage) { #>
			<div class="getty-release-info"><p class="description">{{ data.ReleaseMessage.gettyLinkifyText() }}</p></div>
			<# } #>

			<# if(data.allowed_use && data.allowed_use.usage_restrictions) { #>
				<# for(var i in data.allowed_use.usage_restrictions) { #>
				<div class="getty-restrictions"><p class="description">{{ data.allowed_use.usage_restrictions[i].gettyLinkifyText() }}</p></div>
				<# }
			}#>

		</div>

	</div>
	<div class="separator"></div>
	<div class="getty-image-accordion">

		<h3 class="keywords-accordion-header accordion-header"><span class="accordion-title">Keywords</span><span class="collapse-icon">+</span></h3>
		<div class="keywords-accordion-content accordion-closed">
		<# if(data.keywords) {
			var filter = function(kw) { return kw.Type == 'SpecificPeople'; };

			var people = _.filter(data.keywords, filter);
			var keywords = _.reject(data.keywords, filter);

			if(people.length) {
		#>
		<div class="getty-keywords"><?php esc_html_e( "People: ", 'getty-images' ); ?></div>
		<dd class="getty-keywords">
			<ul>
			<# for(var i = 0; i < people.length; i++) { #>
				<li class="getty-keyword"><a href="#keyword-{{ people[i].Id }}">{{ people[i].Text }}</a></li>
			<# } #>
			</ul>
		</dd>
		<# }
			if(keywords.length) {
		#>
		<dd class="getty-keywords">
			<ul>
			<# for(var i = 0; i < keywords.length; i++) { #>
				<li class="getty-keyword"><a href="#keyword-{{ keywords[i].keyword_id }}">{{ keywords[i].text }}</a></li>
			<# } #>
			</ul>
		</dd>

		<# } #>

		</div>
	</div>
	<div class="separator"></div>


	<# // Specific for non logged in users (i.e. embedable images)
	if (!gettyImages.user.get('loggedIn') ) { #>
		<dt class="getty-image-caption"><?php esc_html_e( "Caption Text: ", 'getty-images' ); ?></dt>
		<dd class="getty-image-caption"><p class="description">{{ data.caption }}</p></dd>

		<dt class="getty-image-alt"><?php esc_html_e( "Alt Text: ", 'getty-images' ); ?></dt>
		<dd class="getty-image-alt"><p class="description">{{ data.title }}</p></dd>
	<#
	}
} #>
</script>

<script type="text/html" id="tmpl-getty-detail-image">
<# if(data.id) {
	var attachment = data.attachment ? data.attachment.attributes : false;
	var thumbUrl = '';

	if(attachment) {
		thumbUrl = attachment.sizes.medium.url;
	}
	else if(data.url_comp != "unavailable") {
		thumbUrl = data.url_comp;
	}
	#>

	<div class="thumbnail">
		<# if(thumbUrl) { #>
		<img src="{{ thumbUrl }}" class="icon" draggable="false" />
		<# } else { #>
		<h3><?php esc_html_e( "(Thumbnail Unavailable)", 'getty-images' ); ?></h3>
		<# } #>
	</div>

	<# if(attachment) { #>
		<div class="filename"><?php esc_html_e( "Filename", 'getty-images' ); ?>: {{ attachment.filename }}</div>
		<div class="uploaded"><?php esc_html_e( "Downloaded", 'getty-images' ); ?>: {{ attachment.dateFormatted }}</div>

		<div class="dimensions">{{ attachment.width }} &times; {{ attachment.height }}</div>
<# }
}	#>
</script>

<script type="text/html" id="tmpl-getty-image-details">
<# if(data.id) {
	var attachment = data.attachment ? data.attachment.attributes : false; #>
	<br/>
	<div class="attachment-info getty-attachment-details {{ data.downloading ? 'downloading' : '' }}">
		<div class="getty-image-thumbnail"></div>
		<div class="getty-image-details-list"></div>

		<div class="getty-display-settings"></div>
		<div class="getty-image-sizes"></div>
		<div class="getty-download-authorizations"></div>
	</div>

	<# } #>
</script>

<script type="text/html" id="tmpl-getty-display-settings">
	<h3><?php esc_html_e( "Display options", 'getty-images' ); ?></h3>

	<div class="attachment-info">
		<div class="setting align">
			<span><?php esc_html_e( 'Align', 'getty-images' ); ?></span>
			<select data-setting="align" data-user-setting="getty_align">
				<# _(gettyImages.text.alignments).each(function(text,value){ #>
					<option value="{{ value }}" {{ ( data.model.align === value ) ? 'selected="selected"' : '' }}>
						{{ text }}
					</option>
				<# }); #>
			</select>
		</div>

		<label class="setting">
			<span><?php esc_html_e('Size', 'getty-images'); ?></span>
		<# if (data.model.downloadingSizes) { #>
			<em><?php esc_html_e( 'Downloading sizes...', 'getty-images' ); ?></em>
		<# } else { #>
			<select class="size" name="size" data-setting="size" data-user-setting="getty_imgsize">
			<# _.each(data.model.sizes, function(size, value) {
				if (size.label && size.width && size.height && size.width > 0  && size.height > 0) {
					var selected = data.model.size == size ? 'selected="selected"' : '';
					#>
					<option value="{{ value }}" {{ selected }}>{{ size.label }} &ndash; {{ parseInt(size.width) }} &times; {{ parseInt(size.height) }}</option>
				<# } #>
			<# }); #>
			</select>
		<# } #>
		</label>

	<# if(gettyImages.user.get('loggedIn')) { #>
		<label class="setting alt-text">
			<span><?php esc_html_e('Alt Text', 'getty-images'); ?></span>
			<input type="text" data-setting="alt" value="{{ data.model.alt }}" data-user-setting="getty_alt" />
		</label>

		<label class="setting caption">
			<span><?php esc_html_e('Caption Text', 'getty-images'); ?></span>
			<textarea data-setting="caption">{{ data.model.caption }}</textarea>
		</label>
	<# } #>
	</div><!--// .attachment-info -->
</script>

<script type="text/html" id="tmpl-getty-result-refinement-category">
	<div class="getty-refinement-category-name">
		{{ data.id.reverseCamelGirl() }}
		<span class="getty-refinement-category-arrow"></span>
	</div>
	<ul class="getty-refinement-list"></ul>
</script>

<script type="text/html" id="tmpl-getty-result-refinement-option">
	<# if(!data.active) { #>
	<a href="#" title="{{ data.text }}">{{ data.text }} <span class="count">{{ new Number(data.count).commaString() }}</span></a>
	<# } #>
</script>

<script type="text/html" id="tmpl-getty-result-refinement">
	<span class="getty-remove-refinement">X</span>
	<# if(data.category) { #>
		<strong>{{ data.category.reverseCamelGirl() }}</strong>: <span>{{ data.text }}</span>
	<# } else { #>
		<em>{{ data.text }}</em>
	<# } #>
</script>

<script type="text/html" id="tmpl-getty-images-more">
	<div class="attachment-preview getty-attachment">
		<div class="getty-more-spinner">
		</div>
		<div class="getty-more-text-container">
			<span class="getty-number-remaining"></span>
			<span class="getty-more-text"><?php esc_html_e( "more images", 'getty-images' ); ?></span>
		</div>
		<div class="getty-load-more-container">
			<?php esc_html_e( "Load more", 'getty-images' ); ?>
		&nbsp;>
		</div>
	</div>
</script>

<script type="text/html" id="tmpl-getty-comp-license-agreement">
	<p class="getty-comp-please-read"><?php esc_html_e( "Please read and accept the following terms before using image comps in your website:", 'getty-images' ); ?></p>
	<div class="getty-comp-license-frame"><?php
		/* TODO: Localize this text. */
		include( __DIR__ . '/getty-comp-license.html' );
	?></div>
	<div class="getty-comp-buttons">
	<input type="button" class="button-primary agree-insert-comp" value="<?php esc_attr_e( "Agree", 'getty-images' ); ?>" />
		&nbsp;
		&nbsp;
		&nbsp;
		<a href="javascript:void(0);" class="getty-cancel-link">Cancel</a>
	</div>
	<div class="getty-comp-license-chevron"></div>
</script>




<script type="text/html" id="tmpl-getty-unsupported-browser">
	<h1><?php esc_html_e( "Sorry, this browser is unsupported!", 'getty-images' ); ?></h1>

	<p><?php esc_html_e( "The Getty Images plugin requires at least Internet Explorer 10 to function. This plugin also supports other modern browsers with proper CORS support such as Firefox, Chrome, Safari, Opera.", 'getty-images' ); ?></p>
</script>

<script type="text/html" id="tmpl-getty-welcome">
	<h1>Welcome</h1>

	<p><?php
		// can't use esc_html_e since it would break the HTML tags in the string to be translated.
		echo wp_kses_post(
			__( "Getty Images tracks usage of this plugin via a third party tool that sets cookies in your browser. We use the statistics collected this way to help improve the plugin. However, you may opt out of this tracking, which will not affect the operation of this plugin. For more information, please <a href=\"http://www.gettyimages.com/Corporate/PrivacyPolicy.aspx\" target=\"_getty\">refer to our privacy policy</a>.", 'getty-images' )
		);
	?></p>

	<p class="getty-welcome-opt-in">
		<label><input type="checkbox" name="getty-images-omniture-opt-in" value="1" <# if(data.optIn) { #>checked="checked"<# } #> /> Agree to third-party tracking</label>
	</p>

	<p class="getty-welcome-continue">
		<button class="button-primary">Continue</button>
	</p>

</script>

<script type="text/html" id="tmpl-getty-choose-mode">
	<# if(data.mode != 'login') { #>
	<div class="getty-split-panel getty-embedded-mode">
		<div class="getty-panel">
			<div class="getty-panel-content">
			<h1><?php esc_html_e( "Access Embeddable Images", 'getty-images' ); ?></h1>

				<p><?php echo wp_kses_post(
					__("Choose from over <strong>50 million</strong> high-quality hosted images, available for free, non-commercial use in your WordPress site.", 'getty-images' )
				);
				?></p>

				<div class="legal-text">
					<p class="stop-propagation"><?php esc_html_e( "By clicking CONTINUE, you accept our ", 'getty-images' ); ?>
					<a class="stop-propagation" href="http://www.gettyimages.com/company/privacy-policy" target="_blank"><?php esc_html_e( "Privacy Policy", 'getty-images' ); ?></a>
					<?php esc_html_e( " (including Use of Cookies and Other Technologies) and  ", 'getty-images' ); ?>
					<a class="stop-propagation" href="http://www.gettyimages.com/company/terms" target="_blank"><?php esc_html_e( "Terms of Use", 'getty-images' ); ?></a>.</p>

					<div class="embed-tacking-opt-in-checkbox-container">
						<input id="embed-tacking-opt-in-checkbox" class="embed-tacking-opt-in-checkbox" type="checkbox" {{(data.enableTracking)?"checked":""}}>
							<label class="stop-propagation" for="embed-tacking-opt-in-checkbox">
								<?php esc_html_e( "Yes, I agree to Getty Images using tracking objects to verify user login status and generating cookies for tracking purposes through ", 'getty-images' ); ?>
								<a class="stop-propagation" href="https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage?csw=1" target="_blank"><?php esc_html_e( "Google Analytics", 'getty-images' ); ?></a>
								<?php esc_html_e( " and ", 'getty-images' ); ?>
								<a class="stop-propagation" href="http://www.adobe.com/privacy/marketing-cloud.html?f=2o7" target="_blank"><?php esc_html_e( "Omniture", 'getty-images' ); ?></a>.
							</label>
						</input>
					</div>
				</div>

				<p><a href="#"><?php esc_html_e( "Continue to Embeddable Images >", 'getty-images' ); ?></a></p>
			</div>
			<span class="getty-icon icon-image"></span>
		</div>
	</div>

	<div class="getty-split-panel getty-login-mode">
	<# } #>
		<div class="getty-panel">
			<div class="getty-panel-content">
			<h1><?php esc_html_e( "Getty Images Customer", 'getty-images' ); ?></h1>
				<# if(data.mode !== 'login') { #>
					<p><?php esc_html_e( "Log into your Getty Images account to access all content and usage rights available in your subscription.", 'getty-images' ); ?></p>
				<# } else { #>
					<p><?php esc_html_e( "Please paste your Getty Images plugin code here to continue.", 'getty-images' ); ?></p>
					<div class="getty-login-panel">
					</div>
				<# } #>
			</div>
			<# if(data.mode !== 'login') { #>
				<span class="getty-icon icon-unlocked"></span>
			<# } #>
		</div>

	<# if(data.mode != 'login') { #>
	</div>
	<# } #>
	</div>
	<div class="version-number">
		<?php $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . '/getty-images.php' );  ?>
		<?php esc_html_e('v' . $plugin_data['Version']); ?>
	</div>
</script>

<script type="text/html" id="tmpl-getty-landing-page">
	<ul class='landing-tabs'>
		<li class='landing-tab creative-tab' data-key='creative'>Creative</li>
		<li class='landing-tab featured-tab' data-key='featured'>Featured</li>
		<li class='landing-tab events-tab' data-key='events'>Events</li>
	</ul>
	<div class='landing-content'>
	</div>
</script>

<script type="text/html" id="tmpl-getty-landing-page-tab-creative">

</script>

<script type="text/html" id="tmpl-getty-landing-page-tab-featured">

</script>

<script type="text/html" id="tmpl-getty-landing-page-tab-creative-item">
	<# if (data) { #>
		<div class="image-container" style="background-image: url({{data.get('imgUrl')}})">

		</div>
		<div class="display-title">
			<p class="display-title-text">{{data.get('displayTitle')}}<p>
		</div>
	<# } #>
</script>

<script type="text/html" id="tmpl-getty-landing-page-tab-events-item">
	<# if (data) { #>
		<div class="image-container" style="background-image: url({{data.get('imgUrl')}})">

		</div>
		<div class="display-title">
			<p class="display-title-text">{{data.get('eventName')}}<p>
		</div>
	<# } #>
</script>

<script type="text/html" id="tmpl-getty-landing-page-tab-featured-item">
	<# if (data) { #>
		<div class="image-container" style="background-image: url({{data.get('imgUrl')}})">

		</div>
		<div class="display-title">
			<p class="display-title-text">{{data.get('title')}}<p>
		</div>
	<# } #>
</script>

<script type="text/html" id="tmpl-getty-landing-page-tab-events">
	<div class="events-dropdowns-container">
		<select id="editorial-segment-dropdown">
			<option value="entertainment" <# if (data.editorialSegment == 'entertainment') { #>selected<# } #>>Entertainment</option>
			<option value="news" <# if (data.editorialSegment == 'news') { #>selected<# } #>>News</option>
			<option value="sport" <# if (data.editorialSegment == 'sport') { #>selected<# } #>>Sports</option>
			<option value="archival" <# if (data.editorialSegment == 'archival') { #>selected<# } #>>Archival</option>
			<option value="royalty" <# if (data.editorialSegment == 'royalty') { #>selected<# } #>>Royalty</option>
		</select>
	</div>
	<div class="items"></div>
</script>

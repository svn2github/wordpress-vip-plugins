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
				<?php esc_html_e( " (including Use of Coockies and Other Technologies) and  ", 'getty-images' ); ?>
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
		<# if((gettyImages.isWPcom && gettyImages.user.settings.get('omniture-opt-in') !== undefined) || data.mode == 'login' && loggedIn) { #>
		<span class="getty-title-link">
			<a class="getty-login-toggle getty-title-link {{ loggedIn ? 'getty-logged-in' : '' }}">{{ loggedIn ? gettyImages.user.get('username') : "<?php esc_html_e( "Log in", 'getty-images' ); ?>" }}</a>
			<div class="getty-user-session"></div>
		</span>
		<# } #>
		<# if(!gettyImages.isWPcom && data.mode == 'login' && !loggedIn) { #>
			<a class="getty-title-link getty-mode-change">Change Mode</a>
		<# } else if(!gettyImages.isWPcom && data.mode == 'embed') { #>
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
<div class="getty-browser-container">
	<table class="getty-browser-flex-container">
		<tbody>
			<tr>
				<td class="getty-search-toolbar"></td>
			</tr>
			<tr>
				<td class="getty-browser-container">
					<div class="getty-browser">
					
						<div class="getty-refine"></div>
						<div class="getty-results"></div>
						<div class="getty-search-spinner"></div>
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
	#>

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
				<span class="image-id">#{{ data.id }}</span>
				<span class="image-collection">{{ data.collection_name }}</span>
			</div>
		<# } #>

		<# if ( data.buttons.close ) { #>
			<a class="close media-modal-icon" href="#" title="<?php esc_attr_e('Remove', 'getty-images'); ?>"></a>
		<# } #>

		<# if ( data.buttons.check ) { #>
			<a class="check" href="#" title="<?php esc_attr_e('Deselect', 'getty-images'); ?>"><div class="media-modal-icon"></div></a>
		<# } #>
	</div>
</script>

<script type="text/html" id="tmpl-getty-download-authorizations">
<# if(gettyImages.user.settings.get('mode') != 'embed' && !gettyImages.user.get('loggedIn')) { #>
	<p><?php esc_html_e( "Log in to download images", 'getty-images' ); ?></p>
<# }
else if(gettyImages.user.get('loggedIn') && data.sizesByAgreement) { #>
	<div class="getty-download-authorizations">
		<ul class="getty-download-with">
		Download With:
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
					{{ agreement }}
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
				var note;

				if(size.downloads_remaining) {
					note = '(' + size.downloads_remaining + ' ' + <?php echo wp_json_encode( __( "remaining", 'getty-images' ) ); ?> + ')';
				}
				else {
					note = '';
				}
				if(size.name === data.SelectedDownloadSize.name ) {
					attrs = 'selected="selected"';
				}
				if(attachment && attachment.get('width') == size.width && attachment.get('height') == size.height) {
					attrs = attrs + ' data-downloaded="true"';
					note = <?php echo wp_json_encode( __( "(downloaded)", 'getty-images' ) ); ?>;
				} #>
				<option {{{ attrs }}} value="{{ size.name }}">
					{{ data.ProductOffering.toUpperCase() }}:
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

	<# if(data.SelectedDownloadSize) { #>
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
		<input type="submit" class="button-primary download-image-button" value="{{ text }}" {{ disabled }} />
		<div class="getty-download-spinner"></div>
	</div><#
	}
} #>
</script>

<script type="text/html" id="tmpl-getty-image-details-list">
<dl class="getty-image-details-list">
	<dt class="getty-title"><?php esc_html_e( "Title: ", 'getty-images' ); ?></dt>
	<dd class="getty-title">{{ data.title }}</dd>

	<dt class="getty-image-id"><?php esc_html_e( "Image #: ", 'getty-images' ); ?></dt>
	<dd class="getty-image-id">{{ data.id }}</dd>

	<dt class="getty-artist"><?php esc_html_e( "Artist: ", 'getty-images' ); ?></dt>
	<dd class="getty-artist">{{ data.artist }}</dd>

	<dt class="getty-collection"><?php esc_html_e( "Collection: ", 'getty-images' ); ?></dt>
	<dd class="getty-collection">{{ data.collection_name }}</dd>

	<# if(data.downloadingDetails) { #>
	<dt><?php esc_html_e( "Downloading Details...", 'getty-images' ); ?></dt>
	<# } else if(!data.haveDetails) { #>
	<dt><?php esc_html_e( "Could not not get image details.", 'getty-images' ); ?></dt>
	<# }#>

	<# if(data.allowed_use) { #>
	<dt class="getty-release-info"><?php esc_html_e( "Release Information: ", 'getty-images' ); ?></dt>
	<dd class="getty-release-info"><p class="description">{{{ data.allowed_use.release_info.gettyLinkifyText() }}}</p></dd>
	<# } #>

	<# if(data.allowed_use && data.allowed_use.usage_restrictions && data.allowed_use.usage_restrictions.length) { #>
		<dt class="getty-release-info"><?php esc_html_e( "Restrictions: ", 'getty-images' ); ?></dt>
		<# for(var i in data.allowed_use.usage_restrictions) { #>
		<dd class="getty-restrictions"><p class="description">{{{ data.allowed_use.usage_restrictions[i].gettyLinkifyText() }}}</p></dd>
		<# }
	}#>

	<dd class="getty-licensing">{{ data.licensing }}</dd>
	<# if(data.keywords) {
		var filter = function(kw) { return kw.type == 'SpecificPeople'; };

		var people = _.filter(data.keywords, filter);
		var keywords = _.reject(data.keywords, filter);

		if(people.length) {
	#>
	<dt class="getty-keywords"><?php esc_html_e( "People: ", 'getty-images' ); ?></dt>
	<dd class="getty-keywords">
		<ul>
		<# for(var i = 0; i < people.length; i++) { #>
			<li class="getty-keyword"><a href="#keyword-{{ people[i].Id }}">{{ people[i].text }}</a></li>
		<# } #>
		</ul>
	</dd>
	<# }
		if(keywords.length) {
	#>
	<dt class="getty-keywords"><?php esc_html_e( "Keywords: ", 'getty-images' ); ?></dt>
	<dd class="getty-keywords">
		<ul>
		<# for(var i = 0; i < keywords.length; i++) { #>
			<li class="getty-keyword"><a href="#keyword-{{ keywords[i].Id }}">{{ keywords[i].text }}</a></li>
		<# } #>
		</ul>
	</dd>
	<# }
	// Specific for non logged in users (i.e. embedable images)
	if ( !gettyImages.isWPcom && !gettyImages.user.get('loggedIn') ) { #>
		<dt class="getty-image-caption"><?php esc_html_e( "Caption: ", 'getty-images' ); ?></dt>
		<dd class="getty-image-caption"><p class="description">{{ data.caption }}</p></dd>

		<dt class="getty-image-alt"><?php esc_html_e( "Alt Text: ", 'getty-images' ); ?></dt>
		<dd class="getty-image-alt"><p class="description">{{ data.title }}</p></dd>
	<#
	}
} #>
</dl>
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
	<h3>
		<?php esc_html_e('Image Details', 'getty-images'); ?>
	</h3>

	<div class="attachment-info getty-attachment-details {{ data.downloading ? 'downloading' : '' }}">
		<div class="getty-image-thumbnail"></div>
		<div class="getty-image-details-list"></div>
		<div class="getty-image-sizes"></div>
		<div class="getty-download-authorizations"></div>
	</div>

	<# } #>
</script>

<script type="text/html" id="tmpl-getty-display-settings">
	<h3><?php esc_html_e( "Display Options", 'getty-images' ); ?></h3>

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
				var selected = data.model.size == size ? 'selected="selected"' : '';
				#>
				<option value="{{ value }}" {{{ selected }}}>{{ size.label }} &ndash; {{ parseInt(size.width) }} &times; {{ parseInt(size.height) }}</option>
			<# }); #>
			</select>
		<# } #>
		</label>

	<# if(gettyImages.isWPcom || gettyImages.user.get('loggedIn')) { #>
		<label class="setting alt-text">
			<span><?php esc_html_e('Alt Text', 'getty-images'); ?></span>
			<input type="text" data-setting="alt" value="{{ data.model.alt }}" data-user-setting="getty_alt" />
		</label>

		<label class="setting caption">
			<span><?php esc_html_e('Caption', 'getty-images'); ?></span>
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
	<div class="attachment-preview">
		<div class="getty-more-spinner">
		</div>
		<div class="getty-more-text-container">
			<span class="getty-number-remaining"></span>
			<span class="getty-more-text"><?php esc_html_e( "More", 'getty-images' ); ?></span>
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
					<?php esc_html_e( " (including Use of Coockies and Other Technologies) and  ", 'getty-images' ); ?>
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
</script>

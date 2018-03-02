<!-- Plugin title bar -->
<script type="text/html" id="tmpl-ooyala-title-bar">
	<h1 class="ooyala-title">Ooyala</h1>

	<div class="ooyala-title-links">
		<a class="ooyala-title-link ooyala-browse-link ooyala-browsing"><?php esc_html_e( "Back to Browse", 'ooyala' ); ?></a>
		<?php
		$settings = get_option( 'ooyala' );
		if ( !empty( $settings['alt_accounts'] ) ):
		?>
			<label for="ooyala-accounts"><?php esc_html_e( 'Account:', 'ooyala' ); ?></label>
			<select name="ooyala-accounts" id="ooyala-accounts">
				<option value=""><?php esc_html_e( 'Default', 'ooyala' ); ?></option>
				<?php if ( is_array( $settings['alt_accounts'] ) ): ?>
					<?php foreach ( $settings['alt_accounts'] as $nickname => $value ): ?>
						<option value="<?php echo esc_attr( $nickname ); ?>"><?php echo esc_html( $nickname ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		<?php endif; ?>
		<# if (data.playlistsIsActive) { #>
			<a class="ooyala-playlists-link ooyala-title-link"><?php esc_html_e( 'Playlists', 'ooyala' ); ?></a>
		<# } #>
		<a class="ooyala-upload-toggle ooyala-title-link"><?php esc_html_e( "Upload", 'ooyala' ); ?></a>
		<a class="ooyala-title-link ooyala-about-link"><?php esc_html_e( "About", 'ooyala' ); ?></a>
		<a class="ooyala-title-link ooyala-privacy-link" target="_ooyala" href="http://www.ooyala.com/privacy"><?php esc_html_e( "Privacy Policy", 'ooyala' ); ?></a>
	</div>
</script>

<!-- About panel -->
<script type="text/html" id="tmpl-ooyala-about-text">
<?php
	/* TODO: Localize this text. */
	include( __DIR__ . '/ooyala-about-en-us.html' );
?>

	<p style="text-align: right">
		<a class="ooyala-close" href="#"><?php esc_html_e( "Close", 'ooyala' ); ?></a>
	</p>
</script>

<!-- Main attachments browser -->
<script type="text/html" id="tmpl-ooyala-attachments-browser">
<div class="ooyala-browser-container">
	<table class="ooyala-browser-flex-container">
		<tbody>
			<tr>
				<td class="ooyala-search-toolbar"></td>
			</tr>
			<tr>
				<td class="ooyala-browser-container">
					<div class="ooyala-browser">
						<div class="ooyala-results"></div>
						<div class="ooyala-search-spinner"></div>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</div>
<div class="ooyala-sidebar-container">
	<div class="ooyala-sidebar">
	</div>
</div>
</script>

<!--Playlists browser -->
<script type="text/html" id="tmpl-ooyala-playlists-browser">
<# if (data.playlists.isFetching) { #>
	<div><?php esc_html_e( 'Loading...', 'ooyala' ); ?></div>
<# } else {
	if (data.playlists.length) { #>
		<ul class="ooyala-playlists-list">
			<# data.playlists.each(function(item) { #>
				<li data-id="{{ item.get('id') }}" class="{{ item.get('id') === data.model.get('playlist') ? 'selected' : '' }}">{{ item.get('name') }}</li>
			<# }); #>
		</ul>
	<# } else { #>
		<div><?php esc_html_e( 'There are no playlists for this account.', 'ooyala' ); ?></div>
	<# }
} #>
</script>

<script type="text/html" id="tmpl-ooyala-playlist-detail">
<# if (data.id) { #>
<dl class="ooyala-image-details-list">

	<dt class="ooyala-title"><?php esc_html_e( 'Name', 'ooyala' ); ?>:</dt>
	<dd class="ooyala-title">{{ data.name }}</dd>

</dl>
<# } #>
</script>

<!-- Single attachment -->
<script type="text/html" id="tmpl-ooyala-attachment">
<# var classes = [];
	classes.push('type-' + data.asset_type);
	#>
	<div class="attachment-preview js--select-attachment ooyala-attachment {{ classes.join(' ') }}">
		<#  // if the status is uploading and WE are actually uploading it right now (will have a percent field)
			// i.e. assets can have the status of uploading if the upload was started and abandoned (or still in progress elswhere)
			if ( data.status === 'uploading' && 'percent' in data ) { #>
			<div class="thumbnail"><div class="media-progress-bar"><div></div></div></div>
		<# } else { #>
			<div class="thumbnail">
				<div class="centered">
				<# if (data.preview_image_url) { #>
					<img src="{{ data.preview_image_url }}" draggable="false" />
				<# } #>
				</div>
			</div>
		<# } #>
			<div class="asset-details">
				<span class="asset-name">{{ data.name }}</span>
			</div>

		<# if ( data.buttons.close ) { #>
			<a class="close media-modal-icon" href="#" title="<?php esc_attr_e( 'Remove', 'ooyala' ); ?>"></a>
		<# } #>

		<# if ( data.buttons.check ) { #>
			<a class="check" href="#" title="<?php esc_attr_e( 'Deselect', 'ooyala' ); ?>"><div class="media-modal-icon"></div></a>
		<# } #>
	</div>
</script>

<!-- Main sidebar details for single attachment -->
<script type="text/html" id="tmpl-ooyala-details">
<div class="ooyala-image-details">
	<div class="thumbnail">
		<# if(data.preview_image_url) { #>
			<img src="{{ data.preview_image_url }}" class="icon" draggable="false" />
			<div class="ooyala-thumbnail-action">
			<# if(typeof data.attachment_id != 'undefined') { #>
				<# if(data.attachment_id && data.attachment_id === wp.media.view.settings.post.featuredImageId) { #>
					<span class="ooyala-status-text ooyala-status-featured"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Featured Image Set to Thumbnail', 'ooyala' ); ?></span>
				<# } else { #>
				<button class="ooyala-set-featured button-secondary" {{ data.attachment_id && data.attachment_id === wp.media.view.settings.post.featuredImageId ? disabled="disabled" : '' }}><?php esc_html_e( 'Set Thumbnail as Featured Image', 'ooyala' ); ?></button>
				<# } #>
			<# } else { #>
				<span class="ooyala-status-text ooyala-status-checking loading"><?php esc_html_e( 'Checking image', 'ooyala' ); ?></span>
			<# } #>
		<# } #>
		</div>
	</div>
</div>
<dl class="ooyala-image-details-list">

	<dt class="ooyala-title"><?php esc_html_e( 'Title:', 'ooyala' ); ?></dt>
	<dd class="ooyala-title">{{ data.name }}</dd>

	<# if (data.duration) { #>
	<dt class="ooyala-duration"><?php esc_html_e( 'Duration:', 'ooyala' ); ?></dt>
	<dd class="ooyala-duration">{{ data.duration_string }}</dd>
	<# } #>

	<dt class="ooyala-status"><?php esc_html_e( 'Status:', 'ooyala' ); ?></dt>
	<dd class="ooyala-status ooyala-status-{{ data.status }} {{ data.status == 'processing' ? 'loading' : '' }}">{{ data.status }}
	<# if (data.status=='uploading' && data.percent !== undefined) { #>
		<em class="progress">(<span>{{ data.percent }}</span>%)</em>
	<# } #>
	</dd>

	<# if ( data.description ) { #>
	<dt class="ooyala-description"><?php esc_html_e( 'Description:', 'ooyala' ); ?></dt>
	<#  if ( data.description.length > ( data.descriptionMaxLen + data.maxLenThreshold ) ) {
			var trunc = data.description.lastIndexOf(" ", data.descriptionMaxLen);
			if (trunc==-1) trunc = data.descriptionMaxLen;
			#>
	<dd class="ooyala-description">{{ data.description.slice(0,trunc) }}<span class="more">{{ data.description.slice(trunc) }}</span> <a href="#" class="show-more">(show&nbsp;more)</a></dd>
		<# } else { #>
	<dd class="ooyala-description">{{ data.description }}</dd>
		<# }
	 } #>
</dl>

	<div class="ooyala-labels-container"></div>
</script>

<!-- Player display options -->
<script type="text/html" id="tmpl-ooyala-display-settings">
<h3><?php esc_html_e( 'Player Display Settings', 'ooyala' ); ?></h3>

<div class="ooyala-display-settings {{ (data.model.forceEmbed || data.model.attachment.canEmbed()) ? '' : 'embed-warning' }}">
<div class="message"><?php esc_html_e( 'This asset may not display correctly due to its current status. Do you wish to embed it anyway?', 'ooyala' ); ?><a href="#">Show Player Settings</a></div>
<label class="setting">
	<span><?php esc_html_e( 'Player', 'ooyala' ); ?></span>
	<# if ( data.players.isFetching ) { #>
		<em class="loading"><?php esc_html_e( 'Retrieving players', 'ooyala' ); ?></em>
	<# } else { #>
		<select data-setting="player_id">
		<# data.players.each( function(item) { #>
			<option value="{{ item.get('id') }}">{{ item.get('name') }}</option>
		<# }); #>
			<option value=""><?php esc_html_e( 'Default', 'ooyala' ); ?></option>
		</select>
	<# } #>
</label>

<label class="setting ooyala-initial-time ooyala-numeric-input">
	<span><?php esc_html_e( 'Initial Time', 'ooyala' ); ?></span>
	<input type="text" data-setting="initialTime" min="0" max="{{ data.model.attachment.get('duration') / 1000 }}"> <?php esc_html_e( 'sec', 'ooyala' ); ?>
</label>

<label class="setting ooyala-initial-volume ooyala-numeric-input">
	<span><?php esc_html_e( 'Initial Volume', 'ooyala' ); ?></span>
	<input type="text" data-setting="initialVolume" min="0" max="1" step="0.05"> <?php esc_html_e( '0.00 - 1.00', 'ooyala' ); ?>
</label>
<label class="setting">
	<span><?php esc_html_e( 'Autoplay', 'ooyala' ); ?></span>
	<input type="checkbox" data-setting="autoplay"/>
</label>

<label class="setting">
	<span><?php esc_html_e( 'Loop', 'ooyala' ); ?></span>
	<input type="checkbox" data-setting="loop"/>
</label>

<div class="setting ooyala-setting ooyala-additional-parameters">
	<label for="ooyala-additional-params"><?php esc_html_e( 'Additional Player Parameters', 'ooyala' ); ?></label>
	<em class="ooyala-error-message"><?php esc_html_e( 'There is an error in your syntax:', 'ooyala' ); ?></em>
	<textarea id="ooyala-additional-params" data-setting="additional_params_raw" placeholder="<?php esc_attr_e( 'Key/value pairs in JSON or JavaScript object literal notation', 'ooyala' ); ?>">{{ data.model.additional_params }}</textarea>
</div>
</div>
</script>

<!-- Playlist embed options -->
<script type="text/html" id="tmpl-ooyala-playlist-settings">
	<h3><?php esc_html_e( 'Playlist Settings', 'ooyala' ); ?></h3>
	<div class="ooyala-playlist-select-wrapper">
		<label class="setting ooyala-playlists-select">
			<span><?php esc_html_e( 'Attached Playlist', 'ooyala' ); ?></span>
			<# if (data.playlists.isFetching) { #>
				<em class="loading"><?php esc_html_e( 'Retrieving playlists', 'ooyala' ); ?></em>
			<# } else { #>
				<select data-setting="playlist">
					<option value=""><?php esc_html_e( 'None', 'ooyala' ); ?></option>
				<# data.playlists.each(function(item) { #>
					<option value="{{ item.get('id') }}">{{ item.get('name') }}</option>
				<# }); #>
				</select>
			<# } #>
		</label>
	</div>

	<# if (data.model.playlist) { #>
		<em class="ooyala-playlist-help"><?php echo wp_kses_post( sprintf( __( 'Leave values blank or set to "Default" to inherit <a href="%s" target="_blank">global default settings</a>.', 'ooyala' ), esc_url( admin_url( 'admin.php?page=' . Ooyala::player_settings_slug . '#playlists' ) ) ) ); ?></em>
		<div class="setting">
			<span><?php esc_html_e( 'Caption', 'ooyala' ); ?></span>
			<div class="settings-group">
				<select data-setting="playlist_captionType" class="js--caption">
					<option><?php esc_html_e( 'Default', 'ooyala' ); ?></option>
					<option value="none"><?php esc_html_e( 'None', 'ooyala' ); ?></option>
					<option value="custom"><?php esc_html_e( 'Custom', 'ooyala' ); ?></option>
				</select>

				<label for="ooyala-playlists-caption-title">
					<input type="checkbox" data-setting="playlist_caption[title]" id="ooyala-playlists-caption-title" class="js--caption"/>
					<?php esc_html_e( 'Title', 'ooyala' ); ?>
				</label>

				<label for="ooyala-playlists-caption-description">
					<input type="checkbox" data-setting="playlist_caption[description]" id="ooyala-playlists-caption-description" class="js--caption"/>
					<?php esc_html_e( 'Description', 'ooyala' ); ?>
				</label>

				<label for="ooyala-playlists-caption-duration">
					<input type="checkbox" data-setting="playlist_caption[duration]" id="ooyala-playlists-caption-duration" class="js--caption"/>
					<?php esc_html_e( 'Duration', 'ooyala' ); ?>
				</label>
			</div>
		</div>

		<div class="setting">
			<span><?php esc_html_e( 'Caption Position', 'ooyala' ); ?></span>

			<div class="settings-group">
				<label for="ooyala-playlists-caption-position-default">
					<input type="radio" name="playlist_captionPosition" data-setting="playlist_captionPosition" id="ooyala-playlists-caption-position-default" value="" checked/>
					<?php esc_html_e( 'Default', 'ooyala' ); ?>
				</label>


				<label for="ooyala-playlists-caption-position-inside">
					<input type="radio" name="playlist_captionPosition" data-setting="playlist_captionPosition" id="ooyala-playlists-caption-position-inside" value="inside"/>
					<?php esc_html_e( 'Inside', 'ooyala' ); ?>
				</label>


				<label for="ooyala-playlists-caption-position-outside">
					<input type="radio" name="playlist_captionPosition" data-setting="playlist_captionPosition" id="ooyala-playlists-caption-position-outside" value="outside"/>
					<?php esc_html_e( 'Outside', 'ooyala' ); ?>
				</label>
			</div>
		</div>

		<div class="setting">
			<span><?php esc_html_e( 'Orientation', 'ooyala' ); ?></span>

			<div class="settings-group">
				<label for="ooyala-playlists-orientation-default">
					<input type="radio" name="playlist_orientation" data-setting="playlist_orientation" id="ooyala-playlists-orientation-default" value="" checked/>
					<?php esc_html_e( 'Default', 'ooyala' ); ?>
				</label>


				<label for="ooyala-playlists-orientation-vertical">
					<input type="radio" name="playlist_orientation" data-setting="playlist_orientation" id="ooyala-playlists-orientation-vertical" value="vertical"/>
					<?php esc_html_e( 'Vertical', 'ooyala' ); ?>
				</label>


				<label for="ooyala-playlists-orientation-horizontal">
					<input type="radio" name="playlist_orientation" data-setting="playlist_orientation" id="ooyala-playlists-orientation-horizontal" value="horizontal"/>
					<?php esc_html_e( 'Horizontal', 'ooyala' ); ?>
				</label>
			</div>
		</div>

		<div class="setting">
			<span><?php esc_html_e( 'Pod Type', 'ooyala' ); ?></span>

			<div class="settings-group">
				<label for="ooyala-playlists-pod-type-default">
					<input type="radio" name="playlist_podType" data-setting="playlist_podType" id="ooyala-playlists-pod-type-default" value="" checked/>
					<?php esc_html_e( 'Default', 'ooyala' ); ?>
				</label>


				<label for="ooyala-playlists-pod-type-scrolling">
					<input type="radio" name="playlist_podType" data-setting="playlist_podType" id="ooyala-playlists-pod-type-scrolling" value="scrolling"/>
					<?php esc_html_e( 'Scrolling', 'ooyala' ); ?>
				</label>


				<label for="ooyala-playlists-pod-type-paging">
					<input type="radio" name="playlist_podType" data-setting="playlist_podType" id="ooyala-playlists-pod-type-paging" value="paging"/>
					<?php esc_html_e( 'Paging', 'ooyala' ); ?>
				</label>
			</div>
		</div>

		<div class="setting">
			<span><?php esc_html_e( 'Position', 'ooyala' ); ?></span>

			<div class="settings-group">
				<label for="ooyala-playlists-position-default">
					<input type="radio" name="playlist_position" data-setting="playlist_position" id="ooyala-playlists-position-default" value="" checked/>
					<?php esc_html_e( 'Default', 'ooyala' ); ?>
				</label>


				<label for="ooyala-playlists-position-left">
					<input type="radio" name="playlist_position" data-setting="playlist_position" id="ooyala-playlists-position-left" value="left"/>
					<?php esc_html_e( 'Left', 'ooyala' ); ?>
				</label>


				<label for="ooyala-playlists-position-right">
					<input type="radio" name="playlist_position" data-setting="playlist_position" id="ooyala-playlists-position-right" value="right"/>
					<?php esc_html_e( 'Right', 'ooyala' ); ?>
				</label>


				<label for="ooyala-playlists-position-top">
					<input type="radio" name="playlist_position" data-setting="playlist_position" id="ooyala-playlists-position-top" value="top"/>
					<?php esc_html_e( 'Top', 'ooyala' ); ?>
				</label>


				<label for="ooyala-playlists-position-bottom">
					<input type="radio" name="playlist_position" data-setting="playlist_position" id="ooyala-playlists-position-bottom" value="bottom"/>
					<?php esc_html_e( 'Bottom', 'ooyala' ); ?>
				</label>
			</div>
		</div>

		<label class="setting ooyala-numeric-input" for="ooyala-playlists-thumbnails-size">
			<span><?php esc_html_e( 'Thumbnails Size', 'ooyala' ); ?></span>
			<input type="text" data-setting="playlist_thumbnailsSize" id="ooyala-playlists-thumbnails-size" placeholder="150" /> px
		</label>

		<label class="setting ooyala-numeric-input" for="ooyala-playlists-thumbnails-spacing">
			<span><?php esc_html_e( 'Thumbnails Spacing', 'ooyala' ); ?></span>
			<input type="text" data-setting="playlist_thumbnailsSpacing" id="ooyala-playlists-thumbnails-spacing" placeholder="3" /> px
		</label>

		<label class="setting ooyala-numeric-input" for="ooyala-playlists-wrapper-font-size">
			<span><?php esc_html_e( 'Wrapper Font Size', 'ooyala' ); ?></span>
			<input type="text" data-setting="playlist_wrapperFontSize" id="ooyala-playlists-wrapper-font-size" placeholder="14" />
		</label>

	<# } #>
</script>

<!-- The square "More" button -->
<script type="text/html" id="tmpl-ooyala-more">
	<div class="attachment-preview">
		<div class="ooyala-more-spinner">
		</div>
		<div class="ooyala-more-text-container">
			<!--// <span class="ooyala-number-remaining"></span> //-->
			<span class="ooyala-more-text"><?php esc_html_e( 'More', 'ooyala' ); ?></span>
		</div>
	</div>
</script>

<!-- Unsupported browser message -->
<script type="text/html" id="tmpl-ooyala-unsupported-browser">
	<h1><?php esc_html_e( "Sorry, this browser is unsupported!", 'ooyala' ); ?></h1>

	<p><?php esc_html_e( "The Ooyala plugin requires at least Internet Explorer 10 to function. This plugin also supports other modern browsers with proper CORS support such as Firefox, Chrome, Safari, and Opera.", 'ooyala' ); ?></p>
</script>

<!-- Edit labels -->
<script type="text/html" id="tmpl-ooyala-edit-labels">
<#
var $labels;

(function($) {
	var labels = data.model.get('labels') || new Backbone.Collection();

	$labels = $('<div>');

	labels.forEach(function(label) {
		var $link = $('<a>').text(label.get('name'))
		  , $label = $('<span class="ooyala-label">')
		  , $dismiss = $('<a class="ooyala-label-remove dashicons">')
		;

		if(label.get('id')) {
			$label.attr('data-label-id', label.get('id'));

			if(data.linkable) {
				$link.attr('href', 'label-' + label.get('id'));
				$link.attr('title', <?php echo wp_json_encode( __( 'Refine by this label', 'ooyala' ) ); ?>);
			}
		}

		// Surface any errors to the user via a tooltip, but allow them to dismiss
		// the label whether or not it saved
		if(label.get('error')) {
			$dismiss.addClass('dashicons-warning').attr('title', label.get('error'));
		} else {
			$dismiss.addClass('dashicons-dismiss').attr('title', <?php echo wp_json_encode( __( 'Remove label', 'ooyala' ) ); ?>);
		}

		$label.append($link, $dismiss);

		$labels.append($label);
	});
})(jQuery);
#>

	<div class="ooyala-label-input-container ui-front">
		<label class="setting"><span><?php esc_html_e( 'Labels', 'ooyala' ); ?></span><input class="ooyala-label-input" type="text" /></label>
	</div>

	<div class="ooyala-label-list">
		{{{ $labels.html() }}}
	</div>
</script>

<!-- Asset upload panel -->
<script type="text/html" id="tmpl-ooyala-upload-panel">
	<# if ( data.controller.uploader.files.length ) {
		var file = data.controller.uploader.files[0];
		var isUploading = data.controller.uploader.state === ooyala.plupload.STARTED;
		#>
		<div class="file-name"><?php esc_html_e( 'File:', 'ooyala' ); ?> {{ file.name }} <em class="file-size">({{ new Number( file.size ).bytesToString() }})</em>
		<# if( !isUploading ) { #>
			<a class="button ooyala-upload-browser" tabindex="10"><?php esc_html_e( 'Change', 'ooyala' ); ?></a>
		<# } #>
		</div>
		<label class="setting"><?php esc_html_e( 'Title', 'ooyala' ); ?><input type="text" value="{{ file.model.get('name') }}" data-setting="name" tabindex="20"></label>
		<label class="setting"><?php esc_html_e( 'Description', 'ooyala' ); ?><textarea data-setting="description" tabindex="30">{{ file.model.get('description') }}</textarea></label>
		<div class="ooyala-labels-container"></div>
		<label class="setting"><?php esc_html_e( 'Post-processing Status', 'ooyala' ); ?>
		<select data-setting="futureStatus" tabindex="40">
		<# var status = ['live','paused'];
			for( var i = 0; i < status.length; i++) { #>
				<option value="{{ status[i] }}" {{{ status[i] == file.model.get('futureStatus') ? ' selected="selected"' : '' }}}>{{ status[i] }}</option>
		<# } #>
		</select></label>
		<div class="ooyala-upload-controls {{ isUploading ? 'uploading' : '' }}">
			<div class="progress"><span>{{ ( file.model.asset && file.model.asset.get('percent') ) || 0 }}</span>%</div>
			<a class="button ooyala-stop-upload" tabindex="60"><?php esc_html_e( 'Cancel Upload', 'ooyala' ); ?></a>
			<a class="button ooyala-start-upload" tabindex="50"><?php esc_html_e( 'Start Upload', 'ooyala' ); ?></a>
		</div>
	<# } else { #>
		<div class="ooyala-upload-browser-container">
			<h4><?php esc_html_e( 'Upload an asset to your account.', 'ooyala' ); ?></h4>
		<a class="button button-hero ooyala-upload-browser"><?php esc_html_e( 'Select File', 'ooyala' ); ?></a>
		</div>
	<# } #>
</script>

<!-- Current label refinement for search secondary toolbar -->
<script type="text/html" id="tmpl-ooyala-label-search">
	<?php esc_html_e( 'Refining by Label:', 'ooyala' ); ?>
	<span class="ooyala-selected-label"></span>
	<a href="#" title="<?php esc_attr_e( 'Clear Label', 'ooyala' ); ?>" class="ooyala-clear-label dashicons dashicons-dismiss"></a>
</script>

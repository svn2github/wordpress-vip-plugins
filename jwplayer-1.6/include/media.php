<?php

function jwplayer_media_attachment_fields_to_edit( $form_fields, $media ) {
	if ( in_array( $media->post_mime_type, json_decode( JWPLAYER_MEDIA_MIME_TYPES ), true ) ) {
		$form_fields['jwplayer_media_sync'] = array(
			'label' => 'JW Player',
			'input' => 'html',
			'html' => jwplayer_media_sync_form_html( $media ),
		);
	}
	return $form_fields;
}


function jwplayer_media_sync_form_html( $media ) {
	$hash = get_post_meta( $media->ID, 'jwplayer_media_hash', true );
	$html = '';
	if ( $hash ) {
		$html .= "Changes to this media's title and description will be synced to JW Player";
		$html .= '<p class="description">';
		$html .= 'Disabling sync is currently not possible, because it would break your media embeds. ';
		$html .= '</p>';
	} else {
		$html .= "<label for='attachments[$media->ID][jwplayer_media_sync]'>";
		$html .= "<input type='checkbox' value='sync' name='attachments[$media->ID][jwplayer_media_sync]' />";
		$html .= '&nbsp;&nbsp;Sync to JW Player';
		$html .= '</label>';
		$html .= '<p class="description">';
		$html .= 'Enabling sync to JW Player adds this media file to your JW Player ';
		$html .= 'library which allows you to get analytics about this media.<br />It ';
		$html .= 'also syncs changes you make to title and description to JW Player.';
		$html .= '</p>';
	}
	return $html;
}


function jwplayer_media_attachment_fields_to_save( $media, $attachment ) {
	if ( in_array( $media['post_mime_type'], json_decode( JWPLAYER_MEDIA_MIME_TYPES ), true ) ) {
		$sync = ( isset( $attachment['jwplayer_media_sync'] ) && $attachment['jwplayer_media_sync'] ) ? true : false;
		if ( $sync ) {
			jwplayer_media_init_sync( $media['ID'], $media['post_mime_type'], $media['post_title'], $media['content'] );
		}
	}
	return $media;
}


function jwplayer_media_delete_attachment( $media_id ) {
	$hash = get_post_meta( $media_id, 'jwplayer_media_hash', true );
	if ( ! $hash ) {
		return;
	}
	jwplayer_api_call( '/videos/delete', array( 'video_key' => $hash ) );
}


function jwplayer_media_edit_attachment( $post_id ) {
	$hash = get_post_meta( $post_id, 'jwplayer_media_hash', true );
	if ( $hash ) {
		$post = get_post( $post_id );
		jwplayer_media_sync( $hash, $post_id, $post->mime_type, $post->post_title, $post->description );
	}
}


function jwplayer_media_hash( $media_id, $create_if_none = true ) {
	$hash = get_post_meta( $media_id, 'jwplayer_media_hash', true );
	if ( ! $hash && $create_if_none ) {
		$post = get_post( $media_id );
		if ( ! $post ) {
			return null;
		}
		$hash = jwplayer_media_init_sync( $post->ID, $post->mime_type, $post->post_title, $post->description );
	}
	return $hash;
}


function jwplayer_media_init_sync( $media_id, $mime_type, $title, $description ) {
  $mime_type_parts = ( $mime_type ) ? preg_split( '/\//', $mime_type ): false;
  $sourceformat = ( count( $mime_type_parts ) > 1 ) ? $mime_type_parts[1] : 'mp4';
	$params = array(
		'sourcetype' => 'url',
		'sourceurl' => wp_get_attachment_url( $media_id ),
		'sourceformat' => $sourceformat,
		'tags' => 'wp_media',
	);
	if ( $title ) {
		$params['title'] = $title;
	}
	if ( $description ) {
		$params['description'] = $description;
	}
	$response = jwplayer_api_call( '/videos/create', $params );
	if ( jwplayer_api_response_ok( $response ) ) {
		add_post_meta( $media_id, 'jwplayer_media_hash', $response['video']['key'] );
		return $response['video']['key'];
	}
	return null;
}


function jwplayer_media_sync( $hash, $media_id, $mime_type, $title, $description ) {

	$params = array(
		'video_key' => $hash,
		'title' => $title,
		'description' => $description,
	);

	if ( '' === $title || '' === $description ) {
		// double checking because API only excepts empty parameters if it's
		// resetting a value back to empty
		$video_resp = jwplayer_api_call( '/videos/show', array( 'video_key' => $hash ) );
		if ( jwplayer_api_response_ok( $video_resp ) ) {
			if ( '' === $title && ! $video_resp['video']['title'] ) {
				unset( $params['title'] );
			}
			if ( '' === $description && ! $video_resp['video']['description'] ) {
				unset( $params['description'] );
			}
		}
	}
	$response = jwplayer_api_call( '/videos/update', $params );
	if ( jwplayer_api_response_ok( $response ) ) {
		return $hash;
	}
	return false;
}

// Add the JW Player tab to the menu of the "Add media" window
function jwplayer_media_menu( $tabs ) {
	if ( get_option( 'jwplayer_api_key' ) ) {
		$newtab = array( 'jwplayer' => 'JW Player' );
		return array_merge( $tabs, $newtab );
	}
	return $tabs;
}

// output the contents of the JW Player tab in the "Add media" page
function jwplayer_media_page() {
	media_upload_header();

	?>
	<form class="media-upload-form type-form validate" id="video-form" enctype="multipart/form-data" method="post"
				action="">
		<h3 class="media-title jwplayer-media-title">
			Choose a <strong>player</strong> and a <strong>video</strong>
		</h3>

		<div class="media-items">
			<div id="jwplayer-video-box" class="media-item">
				<?php jwplayer_media_widget_body( true ); ?>
			</div>
		</div>
		<input type="hidden" name="_wpnonce-widget"
					 value="<?php echo esc_attr( wp_create_nonce( 'jwplayer-widget-nonce' ) ); ?>">
	</form>
	<?php
}

// Make our iframe show up in the "Add media" page
function jwplayer_media_handle() {
	return wp_iframe( 'jwplayer_media_page' );
}

// Add the video widget to the authoring page, if enabled in the settings
function jwplayer_media_add_video_box() {
	if ( get_option( 'jwplayer_show_widget', JWPLAYER_SHOW_WIDGET ) && get_option( 'jwplayer_api_key' ) ) {
		add_meta_box( 'jwplayer-video-box', 'Insert media with JW Player', 'jwplayer_media_widget_body', 'post', 'side', 'high' );
		add_meta_box( 'jwplayer-video-box', 'Insert media with JW Player', 'jwplayer_media_widget_body', 'page', 'side', 'high' );
	}
}

// The body of the widget
function jwplayer_media_widget_body() {
	?>
	<div class="jwplayer-widget-div" id="jwplayer-player-div">
		<h4>Player</h4>
		<div>
			<input type="hidden" name="_wpnonce-widget" value="<?php echo esc_attr( wp_create_nonce( 'jwplayer-widget-nonce' ) ); ?>">
			<select id="jwplayer-player-select">
				<option value="">Default Player</option>
			</select>
		</div>
	</div>
	<div class="jwplayer-widget-div" id="jwplayer-video-div">
		<h4>Video</h4>
		<p id="jwplayer-account-login-link"><span>Choose content from</span> your <a href="<?php echo esc_url( JWPLAYER_DASHBOARD ); ?>" title="open your dashboard">JW Player Account</a>
		<ul class="jwplayer-tab-select">
			<li id="jwplayer-tab-select-choose">Choose</li>
			<li id="jwplayer-tab-select-add" class="jwplayer-off">Add New</li>
		</ul>
		<div class="jwplayer-tab" id="jwplayer-tab-choose">
			<div class="jwplayer-tab-search">
				<input type="text" value="" placeholder="Search videos (use 'pl:' for playlists)" id="jwplayer-search-box"/>
			</div>
			<ul id="jwplayer-video-list" class="jwplayer-loading"></ul>
		</div>
		<div class="jwplayer-tab jwplayer-off" id="jwplayer-tab-add">
			<p>
				Which type of content would you like to add?
			</p>
			<div>
				<a class="jwplayer-button button-primary" id="jwplayer-button-upload">upload</a>
				<span>or</span>
				<a class="jwplayer-button button-primary" id="jwplayer-button-url">url</a>
			</div>
		</div>
	</div>
	<?php
}

function jwplayer_media_legacy_external_source( $url, $title = null ) {
	$external_media = jwplayer_get_json_option( 'jwplayer_legacy_external_media' );
	if ( !$external_media ) {
		add_option( 'jwplayer_legacy_external_media', wp_json_encode( array() ) );
		$external_media = array();
	}
	$file_hash = md5( $url );
	if ( array_key_exists( $file_hash, $external_media ) ) {
		return $external_media[ $file_hash ];
	} else {
		$hash = jwplayer_media_add_external_source( $url, $title );
		$external_media[ $file_hash ] = $hash;
		update_option( 'jwplayer_legacy_external_media', wp_json_encode( $external_media ) );
		return $hash;
	}
}

function jwplayer_media_add_external_source( $url, $title = null ) {
	$extension = pathinfo( $url, PATHINFO_EXTENSION );
	$sourceformat = 'mp4';
	foreach ( json_decode( JWPLAYER_SOURCE_FORMAT_EXTENSIONS ) as $format => $extensions ) {
		if ( in_array( $extension, $extensions, true ) ) {
			$sourceformat = $format;
			break;
		}
	}
	$params = array(
		'sourcetype' => 'url',
		'sourceurl' => $url,
		'sourceformat' => $sourceformat,
		'tags' => 'wp_media',
	);
	if ( null !== $title ) {
		$params['title'] = $title;
	}
	$response = jwplayer_api_call( '/videos/create', $params );
	if ( jwplayer_api_response_ok( $response ) ) {
		return $response['video']['key'];
	}
	return null;
}

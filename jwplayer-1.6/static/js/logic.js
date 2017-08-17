(function( $ ){

	jwplayerwp = {
		// Perform search call after user has stopped typing for this many milliseconds.
		search_timeout:1000,

		// Poll server every given number of milliseconds for upload progress info.
		upload_poll_interval:2000,

		// The chunk size for resumable uploads.
		upload_chunk_size:2 * 1024 * 1024,

		// Poll API every given number of milliseconds for thumbnail status.
		thumb_poll_interval:5000,

		// Width of video thumbnails.
		thumb_width:40,

		// Timers.
		search_timer_id:null,
		thumb_timer_id:null,

		use_button_html: function () {
			return $( '<p>' ).addClass( 'button-primary' ).append(
				$( '<span>' ).addClass( 'jwplayer-narrow' ).text( 'Use' )
			).append(
				$( '<span>' ).addClass( 'jwplayer-wide' ).text( 'Click to use this video' )
			);
		},

		// File extensions.
		accepted_extensions: {
			'aac': ['aac','m4a','f4a'],
			'flv': ['flv'],
			'm3u8': ['m3u', 'm3u8'],
			'mp3': ['mp3'],
			'mp4': ['mp4','m4v','f4v','mov'],
			'rtmp': ['rtmp', 'rtmpt', 'rtmpe', 'rtmpte'],
			'smil': ['smil'],
			'vorbis': ['ogg','oga'],
			'webm': ['webm']
		},

		// Apparently, there's no built-in javascript method to escape html entities.
		html_escape:function( str ){
			return $( '<div/>' ).text( str ).html();
		},

		// Test if a string starts with a given prefix.
		starts_with:function( str, prefix ){
			return str.substr( 0, prefix.length ) === prefix;
		},

		// Strip a given prefix from a string.
		lstrip:function( str, prefix ){
			if( jwplayerwp.starts_with( str, prefix ) ){
				return str.substr( prefix.length );
			}
			else{
				return str;
			}
		},

		// Construct a thumbnail url for a given video.
		make_thumb_url:function( video_hash, width ){
			if( width === undefined ){
				width = jwplayerwp.thumb_width;
			}
			return encodeURI( jwplayerwp.content_mask + '/thumbs/' + video_hash + '-' + width + '.jpg' );
		},

		// Insert the quicktag into the editor box.
		insert_quicktag:function( video_hash ){
			var hashes = video_hash;
			if( jwplayerwp.widgets.playerselect.val() ){
				hashes += '-' + jwplayerwp.widgets.playerselect.val();
			}
			var quicktag = '[jwplayer ' + hashes + ']';
			if( jwplayerwp.mediaPage ){
				parent.send_to_editor( quicktag );
			}
			else{
				window.send_to_editor( quicktag );
			}
			return false;
		},

		/* Make a list item for a video.
		 * The `video` parameter must be a dict as returned by the /videos/list call.
		 */
		make_video_list_item:function( video ){
			var thumb_url, js, make_quicktag;
			var css_class = jwplayerwp.widgets.list.children().length % 2 ? 'jwplayer-odd' : 'jwplayer-even';
			if( video.status === 'ready' ){
				thumb_url = jwplayerwp.make_thumb_url( video.key );
				make_quicktag = function( video_key ){
					return function(){
						jwplayerwp.insert_quicktag( video_key );
					}
				}( video.key );
			}
			else if( video.status === 'processing' ){
				thumb_url = encodeURI( jwplayerwp.plugin_url + '/../static/img/processing.gif' );
				make_quicktag = function( video_key ){
					return function(){
						jwplayerwp.insert_quicktag( video_key );
					}
				}( video.key );
				css_class += ' jwplayer-processing';
			}
			else if( video.status === 'failed' ){
				thumb_url = encodeURI( jwplayerwp.plugin_url + '/../static/img/video-error-' + jwplayerwp.thumb_width + '.gif' );
				make_quicktag = null;
				css_class += ' jwplayer-failed';
			}
			// Create the list item
			var elt = $( '<li>' )
				.attr( 'id', 'jwplayer-video-' + video.key )
				.addClass( css_class )
				.append(
					$( '<div>' ).text( video.title ).append(
						jwplayerwp.use_button_html()
					).css( 'background-image', 'url(' + thumb_url + ')' )
				)
			;

			if( make_quicktag ){
				// If we can embed, add the functionality to the item
				$( 'p.button-primary', elt ).click( make_quicktag );
			}

			return elt;
		},

		make_channel_list_item:function( channel ){
			var thumb_url, js, make_quicktag;
			var css_class = jwplayerwp.widgets.list.children().length % 2 ? 'jwplayer-odd' : 'jwplayer-even';
			thumb_url = encodeURI( jwplayerwp.plugin_url + '/../static/img/channel-' + jwplayerwp.thumb_width + '.png' );
			make_quicktag = function( video_key ){
				return function(){
					jwplayerwp.insert_quicktag( video_key );
				}
			}( channel.key );

			// Create the list item
			var elt = $( '<li>' )
				.attr( 'id', 'jwplayer-channel-' + channel.key )
				.addClass( css_class )
				.append(
					$( '<div>' ).text( channel.title )
						.append( $( '<em>' ).text( '(playlist)' ) )
						.append( jwplayerwp.use_button_html() )
						.css( 'background-image', 'url(' + thumb_url + ')' )
				)
			;

			if( make_quicktag ){
				// If we can embed, add the functionality to the item
				$( 'p.button-primary', elt ).click( make_quicktag );
			}

			return elt;
		},

		show_wait_cursor:function(){
			jwplayerwp.widgets.box.addClass( 'jwplayer-busy' );
		},

		show_normal_cursor:function(){
			jwplayerwp.widgets.box.removeClass( 'jwplayer-busy' );
		},

		/* List the most recently uploaded videos. If query is supplied, we will only show
		 * those that match the given string.
		 */
		list_videos:function( query, nr_videos, callback ){
			jwplayerwp.show_wait_cursor();

			if( query === undefined ){
				query = '';
			}

			if( nr_videos === undefined ){
				nr_videos = jwplayerwp.nr_videos;
			}

			var params = {
				action:"jwp_api_proxy",
				method:'/videos/list',
				result_limit:nr_videos,
				order_by:'date:desc',
				random:Math.random(),
				token:( $( 'input[name=_wpnonce-widget]' ).length > 0 ) ? $( 'input[name=_wpnonce-widget]' ).val() : ''
			};

			if( query !== '' ){
				params['text'] = query;
			}

			$.ajax( {
				type:'GET',
				url:ajaxurl,
				data:params,
				dataType:'json',
				success:function( data ){
					jwplayerwp.widgets.list.removeClass( 'jwplayer-loading' );
					if( data && data.status === 'ok' ){
						if( data.videos.length ){
							for( var i = 0; i < data.videos.length; i += 1 ){
								var elt = jwplayerwp.make_video_list_item( data.videos[i] );
								jwplayerwp.widgets.list.append( elt );
							}

							if( jwplayerwp.thumb_timer_id === null ){
								jwplayerwp.thumb_timer_id = window.setInterval( jwplayerwp.poll_thumb_progress, jwplayerwp.thumb_poll_interval );
							}
						}

						if( callback !== undefined ){
							callback( data.videos.length );
						}
					}
					else{
						var msg = data ? 'API error: ' + data.message : 'No response from API.';
						jwplayerwp.widgets.list.empty().append( $( '<li>' ).text( msg ) );
					}

					jwplayerwp.show_normal_cursor();
				},
				error:function( request, message, error ){
					jwplayerwp.widgets.list.empty().append( $( '<p>' ).text( 'AJAX error: ' + message ) );
					jwplayerwp.show_normal_cursor();
				}
			} );
		},

		list_channels:function( query, nr_videos, callback ){
			jwplayerwp.show_wait_cursor();

			if( query === undefined ){
				query = '';
			}

			if( nr_videos === undefined ){
				nr_videos = jwplayerwp.nr_videos;
			}

			var params = {
				action:"jwp_api_proxy",
				method:'/channels/list',
				result_limit:nr_videos,
				random:Math.random(),
				token:( $( 'input[name=_wpnonce-widget]' ).length > 0 ) ? $( 'input[name=_wpnonce-widget]' ).val() : ''
			};

			if( query !== '' ){
				params['text'] = query;
			}

			$.ajax( {
				type:'GET',
				url:ajaxurl,
				data:params,
				dataType:'json',
				success:function( data ){
					jwplayerwp.widgets.list.removeClass( 'jwplayer-loading' );
					if( data && data.status === 'ok' ){
						if( data.channels.length ){
							for( var i = 0; i < data.channels.length; i += 1 ){
								var elt = jwplayerwp.make_channel_list_item( data.channels[i] );
								jwplayerwp.widgets.list.append( elt );
							}
						}

						if( callback !== undefined ){
							callback( data.channels.length );
						}
					}
					else{
						var msg = data ? 'API error: ' + data.message : 'No response from API.';
						jwplayerwp.widgets.list.empty().append( $( '<li>' ).text( msg ) );
					}

					jwplayerwp.show_normal_cursor();
				},
				error:function( request, message, error ){
					jwplayerwp.widgets.list.empty().append( $( '<p>' ).text( 'AJAX error: ' + message ) );
					jwplayerwp.show_normal_cursor();
				}
			} );
		},

		list:function( query, channels, videos, nr_videos ){
			if( query === undefined ){
				query = $.trim( jwplayerwp.widgets.search.val() );
			}
			if( nr_videos === undefined ){
				nr_videos = jwplayerwp.nr_videos;
			}
			if( channels === undefined ){
				channels = true;
			}
			if( videos === undefined ){
				videos = true;
			}
			// Handle the "playlist:" syntax
			var m;
			if( m = query.match( /(playlist|channel|pl):\s*(.*)/ ) ){
				videos = false;
				channels = true;
				query = m[2];
			}

			jwplayerwp.widgets.list.empty().addClass( 'jwplayer-loading' );

			var doDescribeEmpty = function(){
				if( jwplayerwp.widgets.list.children().length === 0 ){
					if( channels && videos ){
						jwplayerwp.widgets.list.text( 'No playlists or videos have been found.' );
					}
					else if( channels ){
						jwplayerwp.widgets.list.text( 'No playlists have been found.' );
					}
					else if( videos ){
						jwplayerwp.widgets.list.text( 'No videos have been found.' );
					}
					else{
						jwplayerwp.widgets.list.text( 'Please search for videos or playlists.' );
					}
				}
			};
			var doChannels = function( num ){
				if( num < nr_videos ){
					jwplayerwp.list_channels( query, nr_videos - num, doDescribeEmpty );
				}
			};
			var doVideos = function( num ){
				if( num < nr_videos ){
					jwplayerwp.list_videos( query, nr_videos - num, doChannels );
				}
			};
			if( videos ){
				doVideos( 0 );
			}
			else if( channels ){
				doChannels( 0 );
			}
			else{
				doDescribeEmpty();
			}
		},

		list_players:function(){
			var params = {
				action:"jwp_api_proxy",
				method:'/players/list',
				random:Math.random(),
				token:( $( 'input[name=_wpnonce-widget]' ).length > 0 ) ? $( 'input[name=_wpnonce-widget]' ).val() : ''
			};

			$.ajax( {
				type:'GET',
				url:ajaxurl,
				data:params,
				dataType:'json',
				success:function( data ){
					if( data && data.status === 'ok' ){
						jwplayerwp.widgets.playerselect.empty().append( $( '<option>' ).val( '' ).text( "Default player" ) );
						for( var p in data.players ){
							var player = data.players[p];
							jwplayerwp.widgets.playerselect.append( $( '<option>' ).val( player.key ).text( player.name ) );
						}
					}
				}
			} );
		},

		// Poll API for status of thumbnails.
		poll_thumb_progress:function(){
			var processing = jwplayerwp.widgets.list.children( 'li.jwplayer-processing' );

			if( processing.length ){
				processing.each( function(){
					var item = $( this );
					var video_key = jwplayerwp.lstrip( item.attr( 'id' ), 'jwplayer-video-' );

					$.ajax( {
						type:'GET',
						url:ajaxurl,
						data:{
							action:"jwp_api_proxy",
							method:'/videos/thumbnails/show',
							video_key:video_key,
							token:( $( 'input[name=_wpnonce-widget]' ).length > 0 ) ? $( 'input[name=_wpnonce-widget]' ).val() : ''
						},
						dataType:'json',
						success:function( data ){
							if( data && data.status === 'ok' ){
								var thumb_url;
								switch(data.thumbnail.status){
									case 'ready':
										thumb_url = jwplayerwp.make_thumb_url( video_key );
										break;

									case 'failed':
										thumb_url = encodeURI( jwplayerwp.plugin_url + '/../static/img/thumb-error-' + jwplayerwp.thumb_width + '.gif' );
										break;

									case 'not build':
									case 'processing':
									default:
										// Don't update thumb.
										thumb_url = null;
										break;
								}

								if( thumb_url ){
									item.removeClass( 'jwplayer-processing' );
									$( 'div', item ).css( 'background-image', 'url(' + thumb_url + ')' );
								}
							}
						},
						error:function(){
						}
					} );
				} );
			}
			else{
				window.clearTimeout( jwplayerwp.thumb_timer_id );
				jwplayerwp.thumb_timer_id = null;
			}
		},

		// Open a small window for file uploads or for external media
		open_upload_window:function() {
			var win = $( '<div>' )
				.addClass( 'jwplayer-upload-window postbox' )
				.hide()
				.appendTo( 'body' )
				.html(
				' <div class="handlediv"><br /></div>\
					<h3 class="hndle"><span>Add Media to JW Player</span></h3>\
					<div class="inside">\
						<form action="" method="post" enctype="multipart/form-data">\
							<p>\
								<label>Title (optional): </label>\
								<input type="text" class="jwplayer-upload-title" name="title">\
							</p>\
							<p>\
								<label>Video file: </label>\
								<input type="file" class="jwplayer-upload-file" name="file">\
							</p>\
							<input type="submit" class="jwplayer-upload-submit button-primary" disabled="disabled" value="Upload">\
							<div class="jwplayer-progress-bar">\
								<div class="jwplayer-progress"></div>\
							</div>\
							<div class="clear"></div>\
							<div class="jwplayer-message"></div>\
						</form>\
					</div>\
				' )
				.fadeIn();
			win.dim = jwplayerwp.dimmer( win );
			win.find( 'form' )
				.submit( function( e ){
					if( win.find( 'input[type="submit"]' ).attr( 'disabled' ) === 'disabled' ){
						// User probably pressed enter before selecting a file
						return false;
					}
					jwplayerwp.upload_video( win );
					return false;
				} )
				.find( '.jwplayer-upload-file' ).change( function(){
					$( this ).parents( ':eq(1)' ).find( '.jwplayer-upload-submit' ).removeAttr( 'disabled' );
				} );
			win.children( '.handlediv' ).click( function(){
				var upload = win.data( 'upload' );
				if( upload ){
					upload.cancel();
					if( ! upload.isResumable() ){
						$( upload.getIframe() ).remove();
					}
				}
				// win.remove();
				win.dim.close();
			} );
			win.draggable( {handle:'.hndle'} );
			return false;
		},

		// Overlay to dim and block the background
		dimmer:function( win ) {
			var dim = $( '<div>')
				.addClass( 'jwplayer-dimmer')
				.css( 'height', $( document ).height() + 'px' )
				.css( 'width', $( document ).width() + 'px' )
				.appendTo( 'body' );

			dim.close = function() {
				$( 'body' ).unbind( 'keyup.jwplayer-dimmer' );
				dim.remove();
				win.fadeOut( 400, function () { win.remove(); } );
			};
			$( 'body' ).bind( 'keyup.jwplayer-dimmer', function ( e ) {
				if ( e.keyCode == 27 ) dim.close();
			} );
			dim.bind( 'click.jwplayer-dimmer', dim.close );
			return dim;
		},

		// Open a small window for file uploads or for external media
		open_addmedia_window:function() {
			var win = $( '<div>' )
				.addClass( 'jwplayer-upload-window postbox' )
				.hide()
				.appendTo( 'body' )
				.html(
				' <div class="handlediv"><br /></div>\
					<h3 class="hndle"><span>Add Media to JW Player</span></h3>\
					<div class="inside">\
						<form action="" method="post" enctype="multipart/form-data">\
							<p>\
								<label>Title (optional): </label>\
								<input type="text" class="jwplayer-addmedia-title" name="title">\
							</p>\
							<p>\
								<label>Media URL: </label>\
								<input type="text" class="jwplayer-addmedia-sourceurl" name="sourceurl">\
							</p>\
							<input type="submit" class="jwplayer-addmedia-submit button-primary" disabled="disabled" value="Add Media Reference">\
							<div class="jwplayer-message"></div>\
						</form>\
					</div>\
				' )
				.fadeIn();
			win.dim = jwplayerwp.dimmer( win );
			win.find( 'form' )
				.submit( function( e ){
					if( win.find( 'input[type="submit"]' ).attr( 'disabled' ) === 'disabled' ){
						// User probably pressed enter before selecting a file
						return false;
					}
					jwplayerwp.add_media( win );
					return false;
				} )
				.find( '.jwplayer-addmedia-sourceurl' ).change( function(){
					$( this ).parents( ':eq(1)' ).find( '.jwplayer-addmedia-submit' ).removeAttr( 'disabled' );
				} );
			win.children( '.handlediv' ).click( function(){
				// win.remove();
				win.dim.close();
			} );
			win.draggable( {handle:'.hndle'} );
			return false;
		},

		// Reset upload timer and widgets.
		reset_addmedia:function( win ){
			win.find( '.jwplayer-addmedia-title' ).val( '' ).removeAttr( 'disabled' );
			win.find( '.jwplayer-addmedia-sourceurl' ).val( '' ).removeAttr( 'disabled' );
			win.find( '.jwplayer-message').text('').hide();
			win.removeClass( 'jwplayer-busy' );
		},

		// Reset upload timer and widgets.
		reset_upload:function( win ){
			win.find( '.jwplayer-upload-title' ).val( '' ).removeAttr( 'disabled' );
			win.find( '.jwplayer-upload-file' ).val( '' ).removeAttr( 'disabled' );
			win.find( '.jwplayer-upload-submit' ).show();
			win.find( '.jwplayer-pause' ).remove();
			win.find( '.jwplayer-message').text('').hide();
			win.removeClass( 'jwplayer-busy' );
		},
		// Upload a new video. First, we do a /videos/create call, then we start uploading.
		upload_video:function( win ){
			var title = $( win.find( 'input' ).get( 0 ) );
			win.addClass( 'jwplayer-busy' );

			if( ! $.browser.msie ){
				// IE (at least until 8) will not submit the form if even one attribute of the file input has changed.
				win.find( 'input' ).attr( 'disabled', 'disabled' );
			}
			else{
				win.find( 'input[type!="file"]' ).attr( 'disabled', 'disabled' );
			}

			win.find( '.jwplayer-message' ).text( "" ).hide();

			var data = {
				action:"jwp_api_proxy",
				method:'/videos/create',
				// IE tends to cache too much
				random:Math.random(),
				token:( $( 'input[name=_wpnonce-widget]' ).length > 0 ) ? $( 'input[name=_wpnonce-widget]' ).val() : ''
			};
			if( JWPlayerUpload.resumeSupported() ){
				data.resumable = 'true';
			}
			title = $.trim( title.val() );

			if( title !== '' ){
				data.title = title;
			} else {
				data.title = win.find( '.jwplayer-upload-file' ).get( 0 ).files[0].name;
			}

			$.ajax( {
				type:'GET',
				url:ajaxurl,
				data:data,
				dataType:'json',
				success:function( data ){
					if( data && data.status === 'ok' ){
						var upload = new JWPlayerUpload( data.link, data.session_id );
						win.data( 'upload', upload );
						upload.useForm( win.find( '.jwplayer-upload-file' ).get( 0 ) );
						win.append( upload.getIframe() );
						upload.pollInterval = jwplayerwp.upload_poll_interval;
						upload.chunkSize = jwplayerwp.upload_chunk_size;
						upload.onProgress = function( bytes, total ){
							var ratio = bytes / total;
							var pct = Math.round( ratio * 1000 ) / 10;
							var txt = "Uploading: " + pct + "%";
							if( ! upload._running ){
								txt += " (paused)";
							}
							win.find( '.jwplayer-message' ).text( txt ).show();
							var progress = win.find( '.jwplayer-progress' );
							progress.stop().animate( {'width':(progress.parent().width() * ratio)}, 400 );
						};
						upload.onError = function( msg ){
							win.find( '.jwplayer-message' ).text( 'Upload failed: ' + msg ).show();
							jwplayerwp.reset_upload( win );
						};
						upload.onCompleted = function(){
							win.remove();
							win.dim.remove();
							jwplayerwp.list();
							$( '#jwplayer-tab-select-choose' ).click();
						};
						win.find( '.jwplayer-message' ).text( 'Uploading...' ).show();
						win.find( '.jwplayer-progress-bar' ).show();

						// Add the pause / resume button
						if( data.session_id ){
							var pause = $( '<button>' ).addClass( 'jwplayer-pause button-secondary' ).text( 'Pause' );
							pause.click( function(){
								if( ! upload._completed ){
									if( upload._running ){
										upload.pause();
										win.removeClass( 'jwplayer-busy' );
										pause.text( 'Resume' );
									}
									else{
										upload.start();
										win.addClass( 'jwplayer-busy' );
										pause.text( 'Pause' );
									}
								}
								return false;
							} );
							win.find( '.jwplayer-upload-submit' ).hide().after( pause );
						}

						setTimeout( function(){
							upload.start()
						}, 0 );
					}
					else{
						var msg = data ? 'API error: ' + data.message : 'No response from API.';
						win.find( '.jwplayer-message' ).text( msg ).show();
						jwplayerwp.reset_upload( win );
					}
				},
				error:function( request, message, error ){
					win.find( '.jwplayer-message' ).text( "AJAX error: " + message ).show();
					jwplayerwp.reset_upload( win );
				}
			} );
			return false;
		},

		// Add new media with an external reference.
		add_media:function( win ){
			var title = $.trim( $( win.find( 'input[name=title]' ).get( 0 ) ).val() );
			var sourceurl = $.trim( $( win.find( 'input[name=sourceurl]' ).get( 0 ) ).val() );
			win.addClass( 'jwplayer-busy' );

			if( ! $.browser.msie ){
				// IE (at least until 8) will not submit the form if even one attribute of the file input has changed.
				win.find( 'input' ).attr( 'disabled', 'disabled' );
			}
			else{
				win.find( 'input[type!="file"]' ).attr( 'disabled', 'disabled' );
			}

			win.find( '.jwplayer-message' ).text( "" ).hide();

			var data = {
				action:"jwp_api_proxy",
				method:'/videos/create',
				// IE tends to cache too much
				random:Math.random(),
				token:( $( 'input[name=_wpnonce-widget]' ).length > 0 ) ? $( 'input[name=_wpnonce-widget]' ).val() : ''
			};


			if( title !== '' ){
				data.title = title;
			}

			data.sourcetype = 'url';
			data.sourceurl = sourceurl;

			data.sourceformat = 'mp4';
			var tmp = sourceurl.split( '.' ), extension = tmp[tmp.length-1];
			for ( format in jwplayerwp.accepted_extensions ) {
				if ( jwplayerwp.accepted_extensions[format].indexOf( extension ) >= 0 ) {
					data.sourceformat = format;
					break;
				}
			}

			$.ajax( {
				type:'GET',
				url:ajaxurl,
				data:data,
				dataType:'json',
				success:function( data ){
					if( data && data.status === 'ok' ){
						// win.remove();
						win.dim.close();
						setTimeout( function () {
							jwplayerwp.list();
							$( '#jwplayer-tab-select-choose' ).click();
						}, 1000 );
					}
					else{
						var msg = data ? 'API error: ' + data.message : 'No response from API.';
						win.find( '.jwplayer-message' ).text( msg ).show();
						jwplayerwp.reset_addmedia( win );
					}
				},
				error:function( request, message, error ){
					win.find( '.jwplayer-message' ).text( "AJAX error: " + message ).show();
					jwplayerwp.reset_addmedia( win );
				}
			} );
			return false;
		}
	};

	$( function(){
		jwplayerwp.widgets = {
			box:$( '#jwplayer-video-box' ),
			search:$( '#jwplayer-search-box' ),
			list:$( '#jwplayer-video-list' ),
			uploadlink:$( '#jwplayer-button-upload' ),
			addmedialink:$( '#jwplayer-button-url' ),
			playerselect:$( '#jwplayer-player-select' ),
			tabs:$( '.jwplayer-tab-select li' ),
		};
		// Check whether we are on the insert page or on the media page.
		jwplayerwp.mediaPage = jwplayerwp.widgets.box.hasClass( 'media-item' );

		if( jwplayerwp.widgets.box.length === 0 ){
			return;
		}

		jwplayerwp.widgets.search.click( function(){
			var query = $.trim( $( this ).val() );
			$( this ).select();
		} );

		jwplayerwp.widgets.search.keydown( function( e ){
			// Ignore enter, but immediately submit
			if( e.keyCode === 13 ){
				var query = $.trim( $( this ).val() );
				if( jwplayerwp.search_timer_id !== null ){
					window.clearTimeout( jwplayerwp.search_timer_id );
				}
				jwplayerwp.list( query );
				return false;
			}
		} );

		jwplayerwp.widgets.search.keyup( function( e ){
			if( e.keyCode !== 13 ){
				var query = $.trim( $( this ).val() );

				if( jwplayerwp.search_timer_id !== null ){
					window.clearTimeout( jwplayerwp.search_timer_id );
				}

				jwplayerwp.search_timer_id = window.setTimeout( function(){
					jwplayerwp.search_timer_id = null;
					jwplayerwp.list( query );
				}, jwplayerwp.search_timeout );
			}
		} );

		jwplayerwp.widgets.search.blur( function(){
			var query = $.trim( $( this ).val() );
		} );

		jwplayerwp.widgets.tabs.each( function () {
			var tab, id, accountText;
			tab = this;
			$( tab ).click( function () {
				if ( $( tab ).hasClass( 'jwplayer-off' ) ) {
					jwplayerwp.widgets.tabs.each( function () {
						$( '#' + this.id.replace( '-select', '' ) ).addClass( 'jwplayer-off' );
						$( this ).addClass( 'jwplayer-off' );
					});
					id = tab.id.replace( '-select', '' );
					$( tab ).removeClass( 'jwplayer-off' );
					$( '#' + id ).removeClass( 'jwplayer-off' );
					accountText = ( 'jwplayer-tab-add' == id ) ? 'Add content to' : 'Choose content from';
					$( '#jwplayer-account-login-link span' ).text(accountText);
				}
			} );
		} );

		jwplayerwp.widgets.uploadlink.click( jwplayerwp.open_upload_window );
		jwplayerwp.widgets.addmedialink.click( jwplayerwp.open_addmedia_window );

		jwplayerwp.list();
		jwplayerwp.list_players();
	} );

})( jQuery );

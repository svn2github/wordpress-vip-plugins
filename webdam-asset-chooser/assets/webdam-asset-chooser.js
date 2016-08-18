/* global ajaxurl, webdam */
( function( $ ) {
	tinymce.create('tinymce.plugins.WebDAMAssetChooser', {
		init: function(ed, assetsUrl) {

			var pluginUrl = assetsUrl.replace(/assets/i, '');

			ed.addButton('btnWebDAMAssetChooser',
			{
				title: 'Asset Chooser',
				image: assetsUrl + '/webdam-icon.png',
				cmd: 'showAssetChooser',
				classes: 'widget btn btnWebDAMAssetChooser',
				onclick: function() { }
			});

			ed.addCommand('showAssetChooser', function() {
				var params = [ {
					label: 'Embed the link',
					action: 'getAssetId',
					showEmbedLink: 'true',
					showAddLink: 'false',
					sessionMode: 'session',
					allowMultipleSelect: 'true'
				} ];

				// Build the WebDAM Asset Chooser iFrame URL
				var webdam_asset_chooser_url  = webdam.asset_chooser_domain;
					webdam_asset_chooser_url += '/assetpicker/assetpicker.plugin.php';
					webdam_asset_chooser_url += '?returnUrl=' + encodeURIComponent(webdam.return_url);

				// If API login has been enabled, use the oauth session mode
				// and provide a URL which WebDAM can GET our current access
				// and refresh tokens via JSON.
				// @todo WebDAM serverside needs to allow us to simply send an access token
				if ( webdam.api_login_enabled ) {
					params[0].sessionMode = 'oauth';
					webdam_asset_chooser_url += '&tokenpath=' + encodeURIComponent(webdam.get_current_api_response_url);
				}

				webdam_asset_chooser_url += '&params=' + encodeURIComponent(JSON.stringify(params));

				var windowReference = ed.windowManager.open({
					title: 'WebDAM Asset Chooser',
					url: webdam_asset_chooser_url,
					width: 940,
					height: 600,
					onclose: function() {}
				});

				// also initiate the method that checks cookie and inserts the image when set
				var mainInterval = window.setInterval(function() {
					var webDAMHTMLPath = webdam.asset_chooser_domain;
					var re = new RegExp("widgetEmbedValue=([^;]+)");
					var value = re.exec(document.cookie);
					var currentCookieValue = (value != null) ? unescape(value[1]) : null;

					if (currentCookieValue != '' && currentCookieValue != null) {
						// clear the cookie value
						document.cookie = "widgetEmbedValue=;path=/;";
						clearInterval(mainInterval);

						var returnedAssets = JSON.parse( currentCookieValue );

						for ( var i = 0; i < returnedAssets.length; i++ ) {

							var asset = returnedAssets[ i ];

							if ( asset.embedType != 'dismiss' ) {
								if ( asset.embedType == 'preview' || asset.embedType == undefined ) {
									if ( 'undefined' != typeof webdam.enable_sideloading && 1 == webdam.enable_sideloading ) {
										// Display waiting animation
										$( '.webdam-asset-chooser-status' ).addClass( 'visible' );

										// POST the image URL to the server via AJAX
										// Server side—sideload the image into our media library
										// embed the copied version of the image (from our ML)
										$.post(
											ajaxurl,
											{
												action: 'pmc-webdam-sideload-image',
												nonce: webdam.sideload_nonce,
												post_id: webdam.post_id,
												webdam_asset_id: asset.id,
												webdam_asset_url: asset.url,
												webdam_asset_filename: asset.filename
											},
											function( response ) {

												if ( response.success ) {

													var image_template = _.template( $( 'script#webdam-insert-image-template' ).html() );

													ed.execCommand( 'mceInsertContent', 0, image_template( response.data ) );

												}

												// Hide waiting animation
												$( '.webdam-asset-chooser-status' ).removeClass( 'visible' );

												// Close the WebDAM modal window
												windowReference.close();
											}
										);
									} else {
										ed.execCommand( 'mceInsertContent', 0, '<img src="' + asset.url + '" alt="' + asset.filename + '" />' );

										// Close the WebDAM modal window
										windowReference.close();
									}
								} else {
									var textLink = prompt('Please enter the label of your link', asset.filename);

									var elem_anchor = jQuery( '<a></a>' ).attr( 'href', webDAMHTMLPath + '/download.php?id=' + asset.id ).text( textLink );

									ed.execCommand( 'mceInsertContent', 0, elem_anchor.prop( 'outerHTML' ) );
									// Close the WebDAM modal window
									windowReference.close();
								}
							}
						}

						currentCookieValue = null;
					}
				}, 500);
			});
		},

		getInfo: function() {
			return {
				longname: "WebDAM Asset Chooser",
				author: 'WebDAM',
				authorurl: 'http://webdam.com',
				infourl: 'http://webdam.com',
				version: "1.0"
			};
		}
	});
	// Register plugin
	tinymce.PluginManager.add('webdam_asset_chooser', tinymce.plugins.WebDAMAssetChooser);
} )( jQuery );
/**
 * Plugin Name: GrabPress
 * Plugin URI: http://www.grab-media.com/publisher/grabpress
 * Description: Configure Grab's AutoPoster software to deliver fresh video
 * direct to your Blog. Link a Grab Media Publisher account to get paid!
 * Version: 2.4.0
 * Author: Grab Media
 * Author URI: http://www.grab-media.com
 * License: GPLv2 or later
 */

/**
 * Copyright 2014 blinkx, Inc.
 * (email: support@grab-media.com)
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// TODO: Cut down on jQuery factories used by implementing traversing

var GrabPressDashboard;

// Avoid jQuery conflicts with other plugins
(function($) {

	/**
	 * Class for handling the GrabPress Dashboard on the client side.
	 *
	 * @class GrabPressDashboard
	 * @constructor
	 */
	GrabPressDashboard = {
		// Define properties
		activeVideo: null,

		/* Watchlist accordion-like behavior */
		/**
		 * Handles accordion collapse/expand behavior
		 * @param  {String} env     Environment
		 * @param  {String} embedID Embed ID for player
		 * @return {Boolean}         Returns false to prevent bubbling
		 */
		accordionBinding: function( env, embedID ) {
			// Define vars
			var accordionLock = false,
					accordionToggle = $( '.accordion-toggle' ),
					self = this
			;

			// On accordion toggle click
			accordionToggle.die( 'click' ).live( 'click', function( e ) {
				// Define vars
				var monitor, slideDownCurrent,
						anchor = $( this ),
						panel = $( anchor.attr( 'href' ) ),
						openPanels = $( '.accordion-group .accordion-body' ).not( '.collapse' )
				;

				// If accordion lock is true
				if ( accordionLock ) {
					// Prevent default behavior
					e.preventDefault();
					return false;
				}

				// If panel has collapse class
				if ( panel.hasClass( 'collapse' ) ) {
					// Set lock to true and monitor to false
					accordionLock = true;
					monitor = 0;

					// Create slide down for current embed ID
					slideDownCurrent = function( panel, onfinish ) {
						// Define vars
						var embed = $( '#gcontainer' + embedID );

						// Remove embed but keep data stored locally in jQuery
						embed.detach();

						// Slide down
						panel.slideDown( 400, 'linear', function() {
							// Append embed HTML to accordion inner
							panel.find('.accordion-inner').append( embed );

							// Toggle collapse status
							panel.toggleClass("collapse");

							// Increment monitor
							monitor++;

							// Callback
							onfinish( monitor );
						});
					};

					// If open panels exist
					if ( 0 < openPanels.length ) {
						//
						slideDownCurrent( panel, function() {
							// After 100ms
							setTimeout( function() {
								// If monitor is 2
								if ( 2 === monitor ) {
									// Append grabDiv to embed
									var embed = $( "#gcontainer" + embedID ).append( '<div id="grabDiv' + embedID + '"></div>' );

									// Configure active video player
									self.activeVideo = new com.grabnetworks.Player({
										'target': embed,
										'id': embedID,
										'width': '100%',
										'height': '100%',
										'content': anchor.data( 'guid' ),
										'autoPlay': true
									});

									// Set accordion lock to false
									accordionLock = false;
								}
							}, 100 );
						});

						// Slide up
						openPanels.slideUp( 400, 'linear', function() {
							// If active video exists
							if ( self.activeVideo ) {
								// Destroy active video
								$( "#gcontainer" + embedID ).html("");
							}

							// Toggle collapse
							$( this ).toggleClass( 'collapse' );

							// Increment monitor
							monitor++;
						});
					} else { // No open panels
						slideDownCurrent( panel, function() {
							// Set accordion lock to false
							accordionLock = false;
						});
					}
				}

				// Prevent default behavior
				e.preventDefault();
				return false;
			});
		},

		/**
		 * Handles Wordpress menu collapse event
		 */
		collapseMenu: function() {
			// Define vars
			var collapseMenu = $( '#collapse-menu' ),
					rightPane = $( '#t #b .watchlist-wrap .right-pane' ),
					watchlist = $( '#t #b .watchlist' )
			;

			// If browser is Safari or Chrome
			if ( GrabPressUtils.browserIsSafari || GrabPressUtils.browserIsChrome ) {
				// On collapse menu click
				collapseMenu.on( 'click', function() {
					// After 150ms
					setTimeout( function() {
						// Add margins to right pane
						rightPane.css({
							'margin-left': watchlist.width(),
							'margin-top': -( watchlist.height() )
						});
					}, 150 );
				});
			}

			// On collapse menu click
			collapseMenu.on( 'click', function() {
				// Define vars
				var topRight = '-122px',
						smallWidth = 1265,
						adminMenuWrap = $( '#adminmenuwrap' ),
						windowWidth = $( window ).width(),
						watchlistPanel = $( '#t #b .watchlist .panel:first' ),
						watchlist = $( '#t #b .watchlist' )
				;

				// After 300ms
				setTimeout( function() {
					// If admin menu wrap wider than 34px
					if ( 34 > adminMenuWrap.width() ) {
						smallWidth = 1147;
					}

					// If IE8
					if ( GrabPressUtils.browserIsIE() && 8.0 === GrabPressUtils.getIEVersion() ) {
						// Add 16px top margin to right pane
						topRight = '16px';
					}

					// If window width smaller than  min width
					if( windowWidth < smallWidth ) {
						// Hide watchlist panel
						watchlistPanel.hide();

						// After 150ms
						setTimeout( function() {
							// Update right pane margins
							rightPane.css({
								'margin-left': '8px',
								'margin-top': topRight
							});
						}, 150 );
					} else { // Window larger enough to accommodate watchlist panel
						// Show watchlist panel
						watchlistPanel.show();

						// After 150ms
						setTimeout( function() {
							// Update right pane margins
							rightPane.css({
								'margin-left': watchlist.width() + 8,
								'margin-top': -( watchlist.height() )
							});
						}, 150 );
					}
				}, 300 );
			});
		},

		/* Delete alert or error message from alerts tab in dashboard */
		/**
		 * Deletes an alert or error message from the alerts tab in dashboard by
		 * ID
		 * @param  {String} id ID of the alert or error message to be deleted
		 * @return {Boolean}    Returns false if the action is not confirmed
		 */
		deleteAlert: function( id ) {
			// Define vars
			var data,
					confirmed = confirm( 'Are you sure you want to delete this alert?' ),
					messageTab = $( '#t #b #messages-tab2 .content #' + id )
			;

			// If delete action is confirmed by user
			if( confirmed ) {
				// Build data object
				data = {
					action: 'gp_delete_alert',
					alert_id: id
				};

				// Delete alert via AJAX
				$.post( ajaxurl, data, function( response ) {
					// Hide message tab
					messageTab.css( 'visibility', 'hidden' );
				});
			} else { // Not confirmed
				// Return false to prevent deletion from happening
				return false;
			}
		},

		/**
		 * Initialize dashboard
		 */
		init: function() {
			// Define vars
		    $('.grabgear').css({display:'none'});
			var $window = $( window ),
					windowWidth = $window.width(),
					rightPane = $( '#t #b .watchlist-wrap .right-pane' ),
					watchlist = $( '#t #b .watchlist' ),
					accountSettingsBtn = $( '#t #b #btn-account-settings a' ),
					accountSettingsBtnCenter = $( '#t #b #btn-account-settings .accordion-center' ),
					accountSettingsBtnLeft = $( '#t #b #btn-account-settings .accordion-left' ),
					accountSettingsBtnRight = $( '#t #b #btn-account-settings .accordion-right' ),
					accordionRight = $( '#t #b .watchlist .accordion-left' ),
					accordionCenter = $( '#t #b .watchlist .accordion-center' ),
					embedID = $( '#embed_id' ).val(),
					env = $( '#environment' ).val(),
					nano = $( '.nano' ),
					feedTitle = $( '.feed-title' ),
					message = $( '#message' ),
					messageTabs = $( '#messages-tabs' ),
					help = $( '#help' )
			;

			// If IE9+
			if ( GrabPressUtils.browserIsIE() && 8.0 < GrabPressUtils.getIEVersion() ) {
				// If window width is less than 1283 right pane position top not = 0
				if ( 1283 > windowWidth && rightPane.position().top ) {
					// After 150ms
					setTimeout( function() {
						// Update rightpane margins
						rightPane.css({
							'margin-left': watchlist.width(),
							'margin-top': -( watchlist.height() )
						});
					}, 150 );
				}

				// Set max/min width for watchlist
				watchlist.css({
					'max-width': '1392px',
					'min-width': '1072px'
				});

				// On account settings button hover
				accountSettingsBtn.on( 'hover', function() {
					// Remove left margin from button
					$( this ).css( 'margin-left', '0' );
				});

				// Remove filters from center of account settings button
				accountSettingsBtnCenter.css( 'filter', 'none' );

				// Update account settings button dimensions
				accountSettingsBtn.css({
					width: 'auto',
					height: 'auto'
				});

				// On account settings button center hover
				accountSettingsBtnCenter.on( 'hover', function() { // In
					// Define vars
					var $this = $( this );

					$this.css({
						width: '99px',
						filter: 'none',
						'padding-right': '6px',
						'margin-left': '0'
					});
				}, function() { // Out
					$( this ).css( 'padding-right', '3px' );
				});

				// Remove top position from account settings button left/right
				accountSettingsBtnLeft.css( 'top', '0' );
				accountSettingsBtnRight.css( 'top', '0' );
			} else if ( 7.0 !== GrabPressUtils.getIEVersion ) { // If not IE7
				// Update accordion right position
				accordionRight.css( 'right', '-1px' );

				// Update accordion center height
				accordionCenter.css( 'height', 'auto' );
			}

			// Attach event bindings
			this.watchlistBinding( env, embedID );

			// Load video
			this.onloadOpenVideo( embedID );

			// Setup nanoscroller (OS X style scrollbars)
			nano.nanoScroller({
				preventPageScrolling: true,
				alwaysVisible: true
			});

			// Auto-ellipsis title and remove title class
			feedTitle
				.ellipsis( 0, true, '', '' )
				.removeAttr( 'title' ) // To disable tool tips
			;

			// Labeled as "hack" in original code
			message.hide();

			// Attach simpletip to help
			help.simpletip({
				content: 'Health displays "results/max results" per the latest feed update. <br /> Feeds in danger of not producing updates display in red or orange, feeds at risk of not producing updates display in yellow, and healthy feeds display in green.  <br /><br />',
				position: [ 0, 30 ]
			});

			// Attach resize browser and collapse menu bindings
			this.resizeBrowserInit();
			this.collapseMenu();

			// Attach Bootstrap tabs UI to message tabs
			messageTabs.tabs();

			// Attach playlist event
			this.accordionBinding( env, embedID );
		},

		/**
		 * Displays the first video from the watchlist
		 * @param  {String} embedID Embed ID of the video
		 * @return {Boolean}         Returns false if accordion warning exists
		 */
		onloadOpenVideo: function( embedID ) {
			// Define vars
			var accordionWarning = $( '.accordion-warning' ),
					accordionToggle = $( '.accordion-toggle[href="#collapse1"]' ),
					embed = '',
					anchor = $( accordionToggle[0] ),
					collapse1 = $( '#collapse1' ),
					accordionInner = collapse1.find( '.accordion-inner' )
			;

			// If accordion warning exists
			if ( 1 == accordionWarning.length ) {
				// Do not open video
				return false;
			}

			// Build embed HTML
			embed  = '<div id="gcontainer' + embedID + '" style="display: table-cell; height: 100%;">\n';
			embed += '	<div id="grabDiv' +embedID + '"></div>\n';
			embed += '</div>';

			// Append embed HTML into accordion inner
			accordionInner.append( embed );

			// Configure active video player
			this.activeVideo = new com.grabnetworks.Player({
				'id': embedID,
				'width': '100%',
				'height': '100%',
				'content': anchor.data( 'guid' ),
				'autoPlay': false
			});

			// Toggle collapse class on collapse1
			collapse1.toggleClass( 'collapse' );
		},

		/**
		 * Resize watchlist accordion height based on its width maintaining aspect
		 * ratio
		 * @return {[type]} [description]
		 */
		resizeAccordion: function() {
			// Define vars
			var accordionCenter = $( '.accordion-center' ),
					accordionInner = $( '.accordion-inner' ),
					width = accordionCenter.filter( ':first' ).width()
			;

			// Resize accordion
			accordionInner.css( 'height', width * 0.5625 );
		},

		/**
		 * Handle window resize events
		 */
		resizeBrowserInit: function() {
			// Define vars
			var adminMenuWrap = $( '#adminmenuwrap' ),
					nano = $( '.nano' ),
					$window = $( window ),
					windowWidth = $window.width(),
					rightPane = $( '#t #b .watchlist-wrap .right-pane' ),
					watchlist = $( '#t #b .watchlist' ),
					self = this
			;

			// On window resize
			$window.on( 'resize', function() {
				// Define vars
				var left,
						smallWidth = 1265,
						topRight = '-122px'
				;

				// After 150ms
				setTimeout( function() {
					// Resize accordion
					self.resizeAccordion();
				}, 150 );

				// If admin menu wrap width is less than 34 (collapsed)
				if ( 34 > adminMenuWrap.width() ) {
					// Set small width to 1147px
					smallWidth = 1147;
				}

				// If IE8
				if ( GrabPressUtils.browserIsIE() && 8.0 === GrabPressUtils.getIEVersion() ) {
					// Set watchlist as left
					left = $( '#t #b .watchlist' );

					// Update right pane top margin
					topRight = '16px';

					// After 150ms
					setTimeout( function() {
						// Setup nanoscroller (OS X style scrollbars)
						nano.nanoScroller({
							preventPageScrolling: true,
							alwaysVisible: true
						});
					});
				} else { // Not IE8
					// Set watchlist panel as left
					left = $( '#t #b .watchlist .panel:first' );
				}

				// If window width less than small width (min)
				if ( windowWidth < smallWidth ) {
					// Hide watchlist
					left.hide();

					// If active video is defined
					if( self.activeVideo ) {
						// Pause video
						self.activeVideo.pauseVideo();
					}

					// After 150ms
					setTimeout( function() {
						// Update right pane margins
						rightPane.css({
							'margin-left': '8px',
							'margin-top': topRight
						});
					}, 150 );
					// If browser is, IE9+, Chrome, Safari or Opera and window width is
					// less than 1283px
				} else if ( ( ( GrabPressUtils.browserIsIE() && 8.0 < GrabPressUtils.getIEVersion() ) || GrabPressUtils.browserIsChrome || GrabPressUtils.browserIsSafari || GrabPressUtils.browserIsOpera ) && 1283 > windowWidth && rightPane.position().top ) {
					// Show watchlist
					left.show();

					// If active video is defined
					if ( self.activeVideo ) {
						//XXX: do NOT Play video why would we? -rs
						//self.activeVideo.playVideo();
					}

					// After 150ms
					setTimeout( function() {
						// Update right pane margins
						rightPane.css({
							'margin-left': watchlist.width() + 8,
							'margin-top': -( watchlist.height() )
						});
					}, 150 );
				} else { // If IE7 or lower, or a non-major browser
					// Show watchlist
					left.show();

					// If active video not undefined
					if ( self.activeVideo ) {
						//XXX: do NOT Play video, why would we the user hasnt asked for playback -rs
						//self.activeVideo.playVideo();
					}

					// After 150ms
					setTimeout( function() {
						// Update right pane margins
						rightPane.css({
							'margin-left': watchlist.width() + 8,
							'margin-top': -( watchlist.height() )
						});
					}, 150 );
				}
			}).resize(); // Trigger resize event
		},

		/**
		 * Setup display/hide bindings for watchlist button
		 * @param  {String} embedID Embed ID for video
		 */
		watchlistBinding: function( env, embedID ) {
			// Define vars
			var watchlistCheck = $( '.watchlist-check' );

			// On watchlist check click
			watchlistCheck.on( 'click', function() {
			    $('.grabgear').css({display:'table-cell'});
				// Define vars
				var watchlist, data,
						id = this.id.replace( 'watchlist-check-', '' ),
						$this = $( this )
				;

				// If clicked watchlist value is 1
				if ( "1" == $this.val() ) {
					// Set watchlist true
					watchlist = 1;
				} else {
					// Set watchlist false
					watchlist = 0;
				}

				// Build data object
				data = {
					action: 'gp_toggle_watchlist',
					feed_id: id,
					watchlist: watchlist
				};

				// Get accordion content via AJAX
				$.post( ajaxurl, data, function( response ) {
				    $('.grabgear').css({display: 'none'});
					// Define vars
					var parsedJSON = $.parseJSON( response ),
							accordion = '',
							results = parsedJSON.results,
							style = '',
							embed = '',
							collapse = 'collapse',
							accordion2 = $( '#accordion2' ),
							rightPane = $( '#t #b .right-pane' ),
							watchlist = $( '#t #b .watchlist' )
					;

					// If results exist
					if ( results && results.length > 0 ) {
						// Loop through each result
						for ( var i = 0; i < results.length; i++ ) {
							// If valid
							if ( results[ i ] ) {
								// reset value
								style = "";
								embed = "";
								collapse = "collapse";

								// If not first result
								if ( 0 != i ) {
									// Don't display
									style = 'style="display:none;"';
								} else { // Is first result
									// Build embed HTML
									embed  = '<div id="gcontainer' + embedID + '" style="height: 100%;">\n';
									embed += '	<div id="grabDiv' + embedID + '"></div>\n';
									embed += '</div>\n';

									// Update collapse
									collapse = '';
								}

								// Build accordion HTML
								accordion += '<div class="accordion-group">\n';
								accordion += '	<div class="accordion-heading">\n';
								accordion += '		<div class="accordion-left"></div>\n';
								accordion += '		<div class="accordion-center">\n';
								accordion += '			<a class="accordion-toggle feed-title" data-guid="v' + results[ i ].video.guid + '" data-toggle="collapse" data-parent="#accordion2" href="#collapse' + ( i + 1 ) + '">' + results[ i ].video.title + '</a>\n';
								accordion += '		</div>\n';
								accordion	+= '		<div class="accordion-right"></div>\n';
								accordion += '	</div>\n';
								accordion += '	<div id="collapse' + ( i + 1 ) + '" class="accordion-body ' + collapse + ' in" ' + style + '>\n';
								accordion += '		<div class="accordion-inner">\n';
								accordion += '			' + embed;
								accordion += '		</div>\n';
								accordion += '	</div>\n';
								accordion += '</div>\n';
							}
						}

						// Add accordion HTML to accordion2
						accordion2.html( accordion );

						// Configure active video player
						this.activeVideo = new com.grabnetworks.Player({
							'id': embedID,
							'width': '100%',
							'height': '100%',
							'content': results[ 0 ].video.guid,
							'autoPlay': false
						});

						// Trigger window resize event
						$( window ).resize();

						// Toggle visiblity of active video
						$( '#gcontainer' + embedID + ' object' ).css('visibility','visible');
					} else { // No results
						// Build accordion with warning
						accordion += '<div class="accordion-group">\n';
						accordion += '	<div class="accordion-heading">\n';
						accordion += '		<div class="accordion-left"></div>\n';
						accordion += '		<div class="accordion-center">\n';
						accordion += '			&nbsp;\n';
						accordion += '		</div>\n';
						accordion += '		<div class="accordion-right"></div>\n';
						accordion += '	</div>\n';
						accordion += '	<div id="collapse1" class="accordion-body" style="height:95px;">\n';
						accordion += '		<div class="accordion-inner">\n';
						accordion += '			<span class="accordion-warning">Add a feed to your watch list in the Feed Activity panel</span>\n';
						accordion += '		</div>\n';
						accordion += '	</div>\n';
						accordion += '</div>\n';

						// Add accordion html to accordion2
						accordion2.html( accordion );
					}

					// After 300ms
					setTimeout( function() {
						$( window ).resize();
						// Update right pane dimensions based on watchlist dimensions
						rightPane.css({
							'margin-left': watchlist.width(),
							'margin-top': watchlist.height()
						});
					}, 300 );

					// If watchlist check is true
					if ( $this.val() == "1" ) {
						// Toggle watch on
						$this
							.val( '0' )
							.addClass( 'watch-on' )
							.removeClass( 'watch-off' )
						;
					} else { // Watchlist false
						// Toggle watch off
						$this
							.val( '1' )
							.addClass( 'watch-off' )
							.removeClass( 'watch-on' )
						;
					}

					// Update the binding
					GrabPressDashboard.accordionBinding( env, embedID );
				});
			});
		}
	}; // End GrabPressDashboard

	// DOM ready
	$(function() {
		// Initialize GrabPressDahsboard class
		GrabPressDashboard.init();

		// Adjust dashboard height after DOM loads
		$( '#form-dashboard' ).height( $( document ).height() );
	});

})(jQuery); // End $ scope
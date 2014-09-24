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

var GrabPressAutoposter;

// Avoid jQuery conflicts with other plugins
(function($) {

	/**
	 * Class for handling the GrabPress Autoposter form on the client side.
	 *
	 * @class GrabPressAutoposter
	 * @constructor
	 */
	GrabPressAutoposter = {
		/**
		 * Configure providers multi-select options
		 * @type {Object}
		 */
		multiSelectOptions: {
			noneSelectedText: 'Select providers',
			selectedText: function( selectedCount, totalCount ) {
				// If all providers selected
				if ( totalCount === selectedCount ) {
					// Return appropriate all string
					return 'All providers selected';
				} else { // Not all
					// Return # of # string
					return selectedCount + ' providers selected of ' + totalCount;
				}
			}
		},

		/**
		 * Configure categories multi-selection options
		 * @type {Object}
		 */
		multiSelectOptionsCategories: {
			noneSelectedText: 'Select categories',
			selectedText: '# of # selected'
		},

		/**
		 * Configure video categories multi-select options
		 * @type {Object}
		 */
		multiSelectOptionsChannels: {
			noneSelectedText: 'Select Video Categories',
			selectedText: function( selectedCount, totalCount ) {
				// If all video categories selected
				if ( totalCount === selectedCount ) {
					// Return appropriate all string
					return 'All Video Categories';
				} else { // Not all
					// Return # of # string
					return selectedCount + ' of ' + totalCount + ' Video Categories';
				}
			}
		},

		/**
		 * Configure preview modal panel
		 * @type {Object}
		 */
		previewDialogConf: {
			modal: true,
			width: 910,
			height: $( window ).height() * 0.9, // 90% of window height
			draggable: false,
			close: function() {
				// Run validation on Autoposter form
				GrabPressAutoposter.doValidation();

				// Close modal panel
				$( '#preview-modal' ).remove();
			}
		},

		/**
		 * Delete feed by ID using AJAX
		 * @param  {Integer} id ID of feed to delete
		 * @return {Boolean}    Returns false if deletion is not confirmed
		 */
		deleteFeed: function( id ) {
			var data,
					td = $( '#tr-' + id + ' td' ),
					bgColor = td.css( 'background-color' ),
					confirmDelete = confirm( 'Are you sure you want to delete this feed? You will no longer receive videos based on its settings. Existing video posts will not be deleted.' )
			;

			// Temporarily change BG color to red
			td.css( 'background-color', '#f00' );

			// If user confirms they want to delete the feed
			if ( confirmDelete ) {
				// Build data array for feed to delete
				data = {
					action: 'gp_delete_feed',
					feed_id: id
				};

				// Delete feed using AJAX post request
				$.post( ajaxurl, data, function() {
					// Return user to GP Autoposter page when request completes
					window.location = 'admin.php?page=gp-autoposter';
				});
			} else { // User does not confirm
				// Change BG color back to normal
				td.css( 'background-color', bgColor );

				// Prevent default browser behavior
				return false;
			}
		},

		/**
		 * Validates Autoposter create feed form
		 */
		doValidation: function() {
			// Define vars
			var errors = GrabPressAutoposter.hasValidationErrors(),
					createFeedBtn = $( '#btn-create-feed' ),
					previewFeedBtn = $( '#btn-preview-feed' ),
					previewBtnText = $( '.hide' ),
					allFormInputs = $( ':input' )
			;

			// If no validation errors exist
			if ( ! errors ) {
				// Enable create and preview buttons
				createFeedBtn.removeAttr( 'disabled' );
				previewFeedBtn
					.removeAttr( 'disabled' )
					.off( 'click' ) // Unbind click event
				;

				// Display preview button text
				previewBtnText.show();
			} else {
				// Disable create and preview buttons
				createFeedBtn.attr( 'disabled', 'disabled' );
				previewFeedBtn
					.attr( 'disabled', 'disabled' )
					.off( 'click' ) // Unbind click event
				;

				// Hide preview button text
				previewBtnText.hide();
			}

			// Loop through each form input
			allFormInputs.each( function() {
				// Define vars
				var thisInput = $( 'this' );

				// If placeholder text is 'Enter keywords'
				if ( 'Enter keywords' === thisInput.attr( 'placeholder' ) ) {
					// Apply maxlength attribute
					thisInput.attr( 'maxlength', '32' );
				}
			});
		},

		/**
		 * Redirects to a edit feed page for a provided ID
		 * @param  {Integer} id ID of feed to edit
		 */
		editFeed: function( id ) {
			// Redirect to edit feed page for provided ID
			window.location = 'admin.php?page=gp-autoposter&action=edit-feed&feed_id=' + id;
		},

		/* Check for matching keyword and alert user */
		/**
		 * Searches through keywords in existing feeds for a specific keyword
		 * @param  {String} keyword Keyword to search for
		 * @return {Mixed}         Feed if match found or false if no match found
		 */
		findMatchingKeyword: function( keyword ) {
			// Define vars
			var keywords = this.getKeywords();

			// Loop through feeds in keywords
			for ( var feed in keywords ) {
				// If keyword found in feed
				if ( -1 != $.inArray( keyword, keywords[ feed ] ) ) {
					// Return feed
					return feed;
				}
			}

			// No matching keyword
			return false;
		},

		/**
		 * Get keywords from existing feeds
		 * @return {Object} Object containing keywords
		 */
		getKeywords: function() {
			// Define vars
			var existingInputs = $( '#existing_keywords input ' ),
					exactInputs = $( '#exact_keywords input' ),
					keywords = {},
					exactKeywords = {}
			;

			// Loop through existing keyword inputs
			existingInputs.each( function() {
				// Push keyword into keywords object
				keywords[ this.name ] = $.trim( this.value ).split( ' ' );
			});

			// Loop through exact keyword inputs
			exactInputs.each( function() {
				// Push keyword into exact keywords object
				exactKeywords[ this.name ] = $.trim( this.value ).split( ' ' );
				// If first item in array is empty
				if ( 0 === exactKeywords[ this.name ][0].length ) {
					// Remove item
					exactKeywords[ this.name ].splice( 0, 1 );
				}
			});

			// Loop through keyword objects
			for ( var i = 0; i < keywords.length; i++ ) {
				// If exact keywords length is not 0
				if ( exactKeywords[ i ].length ) {
					$.merge( keywords[ i ], exactKeywords[ i ] );
				}

				// If first item in array length is 0
				if ( ! keywords[ i ][0].length ) {
					// Remove item
					keywords[ i ].splice( 0, 1 );
				}
			}

			// Return keywords object
			return keywords;
		},

		/**
		 * Determines appropriate action to take based on contents/length of feed
		 * name
		 * @param  {String} feedName Name of feed
		 */
		handleFeedNameValidation: function( feedName ) {
			// Define vars
			var feedNameInput = $( '#name' ),
					createFeedForm = $( '#form-create-feed' ),
					regex = /^[a-zA-Z0-9,\s]+$/
			;

			// Feed name contains invalid chars
			if ( ! regex.test( feedName ) ) {
				// Popup alert message notifying the user
				alert( 'The name entered contains special characters or starts/ends with spaces. Please enter a different name.' );
			} else if ( feedName.length < 6 ) { // Name is too short
				// Popup alert message in regards to short name
				alert( 'The name entered is less than 6 characters. Please enter a name between 6 and 14 characters' );
			} else if ( feedName.length > 14 ) { // Name is too long
				// Popup alert message in regards to long name
				alert( 'The name entered is more than 14 characters. Please enter a name between 6 and 14 characters' );
			} else {
				// Update feed name in input field
				feedNameInput.val( feedName );

				// Pick a default post category if none have selected
				this.validateCategory();

				// Submit form
				createFeedForm.submit();
			}
		},

		/**
		 * Checks whether form has validation errors, based on current message and
		 * channel and provider select length
		 * @return {Boolean} Form has validation errors
		 */
		hasValidationErrors: function () {
			// Define vars
			var message = $( '#message p' ).text(),
					errorMessage = 'There was an error connecting to the API! Please try again later!',
					channelSelectLength = $( '#channel-select :selected' ).length,
					providerSelectLength = $( '#provider-select :selected' ).length
			;

			// If the displayed message is a predetermined error message
			if ( errorMessage === message ) {
				// Form has validation errors
				return true;
			}

			// If channel or provider select length is equal to 0
			if ( ! channelSelectLength || ! providerSelectLength ) {
				// Form has validation errors
				return true;
			}

			// No validation errors
			return false;
		},

		// TODO: This method is excessively long and overcomplicated, simplify
		/**
		 * Initialize create form feed and all event listeners
		 */
		initSearchForm: function() {
			// Define vars
			var confirm,
					previewFeedBtn = $( '.btn-preview-feed' ),
					previewModal = $( '<div id="preview-modal">' ),
					resetForm = $( '#reset-form' ),
					refererInput = $( 'input[name="referer"]' ),
					feedIDInput = $( 'input[name="feed_id"]' ),
					createFeedForm = $( '#form-create-feed' ),
					createFormInputs = createFeedForm.find( 'input'),
					allProviders = $( '#provider-select option' ),
					//selectedProviders = allProviders.find( ':selected' ),
					allChannels = $( '#channel-select option' ),
					//selectedChannels = allChannels.find( ':selected' ),
					postCategoryDropdown = $( '#cat' ),
					allPostCategories = $( '#cat option' ),
					providersDropdown = $( '#provider-select' ),
					providersDropdownUpdate = $( '#provider-select-update' ),
					updateBtn = $( '.btn-update' ),
					scheduleDropdown = $( '.schedule-select' ),
					activeFeed = $( '.active-feed' ),
					limitDropdown = $( '.limit-select' ),
					authorDropdown = $( '.author-select' ),
					learnMore = $( '#learn-more' ),
					allFormInputs = $( 'input, textarea' ),
					allInputsAndForm = $( ':input', 'form' ),
					activeChecks = $( '.active-check' ),
					formChanged = false,
					cancelEditing = $( '#cancel-editing' ),
					uiSelectMenus = $( '.ui-selectmenu' ),
					uiMultiSelectMenus = $( '.ui-multiselect-menu' ),
					channelDropdown = $( '#channel-select' ),
					modal = $( '#dialog' ),
					modalKeywords = $( '#keywords_dialog' ),
					updateFeedBtns = $( '.btn-update-feed' ),
					message = $( '#message p' ),
					keywordsAnd = $( '#keywords_and' ).val(),
					keywordsNot = $( '#keywords_not' ).val(),
					keywordsOr = $( '#keywords_or' ).val(),
					keywordsPhrase = $( '#keywords_phrase' ).val(),
					wpOauthOverlay = $( '#wp-oauth-overlay' ),
					wpConnectBtn = $( '#wp-connect-btn' ),
					self = this
			;

			// Attach click listener to preview feed button
			previewFeedBtn.on( 'click', function( e ) {
				// Define vars
				var data, dialog
						id = $( this ).data( 'id' )
				;

				// Build data array
				data = {
					action: 'gp_get_preview',
					'feed_id': id
				};

				// Attach config to dialog modal panel
				dialog = previewModal.dialog( self.previewDialogConf );

				// Load modal panel
				dialog.load( ajaxurl, data, function() {
					// Once loading finishes, remove the loading class
					dialog.removeClass( 'loading' );
				});

				// Prevent default browser behavior
				e.preventDefault();
				return false;
			});

			// Attach click listener to reset form
			resetForm.on( 'click', function() {
				// Define vars
				var referer = refererInput.val(),
						feedID = feedIDInput.val()
				;

				// Disable onbeforeunload
				window.onbeforeunload = null;

				// If referer is create
				if ( 'create' === referer ) {
					// Redirect to default Autoposter page
					window.location = "admin.php?page=gp-autoposter";
				} else { // Other referer
					// Redirect to edit feed page using feed ID
					window.location = "admin.php?page=gp-autoposter&action=edit-feed&feed_id=" + feedID+"&reset_form=1";
				}
			});

			// Attach key press listener to create feed form inputs
			createFormInputs.keypress( function( e ) {
				// If 'Enter' key pressed
				if ( 13 === e.which ) {
					// Prevent default behavior
					e.preventDefault();
					return false;
				}
			});

			wpConnectBtn.on( 'click', function() {
				var data;
				if ( $( this ).hasClass( 'wp-connect') ) {
					// TODO: Figure out way to make app ID dynamic for QA vs PRODUCTION
					window.open( 'https://public-api.wordpress.com/oauth2/authorize?client_id=34069&redirect_uri=http://www.grab-media.com/grabpressauth&response_type=code' );
					$( this )
						.removeClass( 'wp-connect' )
						.addClass( 'wp-verify' )
					;
				} else if ( $( this ).hasClass( 'wp-verify' ) ) {
					// Update verify_wp_clicked to true in WPDB
					data = {
						action: 'gp_verify_wp_clicked'
					};
					$.post( ajaxurl, data, function( response ) {
						// Refresh Autoposter tab
						window.location = "admin.php?page=gp-autoposter";
					});
				}
			});

			// Attach jQuery multiselect plugin functionality to providers drop down
			// menu
			providersDropdown.multiselect( this.multiSelectOptions, {
				// Add uncheck all option
				uncheckAll: function( e, ui ) {
					self.doValidation();
				},

				// Add check all option
				checkAll: function( e, ui ) {
					self.doValidation();
				}
			}).multiselectfilter();

			// Attach jQuery multiselect plugin functionality to providers dropdown
			// update
			providersDropdownUpdate.multiselect( this.multiSelectOptions, {
				// Add uncheckAll option
				uncheckAll: function(e, ui){
					// Remove update ID from element
					var id = this.id.replace( 'provider-select-update-', '' );
				},

				// Add check all option
				checkAll: function(e, ui){
					// Remove update ID from element
					var id = this.id.replace( 'provider-select-update-', '' );
				}
			}).multiselectfilter();

			// Add click listener to update button
			updateBtn.on( 'click', function( e ) {
				// Define vars
				var id = $( this ).attr( 'name' ),
						form = $( '#form-' + id ),
						action = $( '#action-' + id )
				;

				// If no updates selected for this ID
				if ( 0 === $('#provider-select-update-' + id + ' :selected').length ) {
					// Popup alert message to tell user to select a min of one provider
					alert('Please select at least one provider');

					// Prevent form submission
					e.preventDefault();
				} else {
					// Else update action to modify
					action.val( 'modify' );

					// Submit update form
					form.submit();
				}
			});

			// Attach multiselect to post category drop down
			postCategoryDropdown.multiselect( this.multiSelectOptionsCategories, {
				header: false // Disable widget header
			});

			// Attach jQuery UI Selectmenu widget to publish settings dropdowns
			scheduleDropdown.selectmenu();
			limitDropdown.selectmenu();
			authorDropdown.selectmenu();

			// Attach a tool tip to click to play "Learn More" link
			learnMore.simpletip({
				content: 'Please be aware that selecting a click-to-play player can negatively impact your revenue, <br />as not all users will generate an ad impression. If you are looking to optimize revenue <br />through Grabpress, all feeds should be set to autoplay.',
				fixed: true,
				position: 'bottom'
			});

			// Enable shim for HTML5 placeholder behavior for inputs and text areas
			// in older browsers
			allFormInputs.placeholder();

			activeChecks.on( 'click', function( e ) {
				// Parse ID from element ID
				var active, data,
						id = this.id.replace( 'active-check-', '' ),
						td = $( '#tr-' + id + ' td' ),
						activeCheck = $( this )
				;

				// If this element is checked
				if ( activeCheck.is( ':checked' ) ) {
					// Active true
					active = 1;

					// Update background of table data cell
					td.css( 'background-color', '#ffe4c4' );
				} else {
					// Active false
					active = 0;

					// Update background of table data cell
					td.css( 'background-color', '#dcdcdc' );
				}

				// Build data object
				data = {
					action: 'gp_toggle_feed',
					feed_id: id,
					active: active
				};

				// Toggle feed active via AJAX
				$.post( ajaxurl, data, function( response ) {
					var substr = response.split( '-' ),
							numActiveFeeds = substr[0],
							numActiveFeedsText = $('#num-active-feeds'),
							numFeeds = substr[1],
							noun = 'feed',
							nounActiveFeedsText = $( '#noun-active-feeds' ),
							autoposterStatus = 'ON',
							autoposterStatusText = $('#autoposter-status'),
							feedsStatus = 'active',
							feedsStatusText = $('#feeds-status')
					;

					// If no active feeds
					if ( ! numActiveFeeds ) {
						// Toggle status
						autoposterStatus = 'OFF';
						feedsStatus = 'inactive';
						response = '';

						// Set active feeds # to overall feeds
						numActiveFeeds = numFeeds;

						// If more than one feed
						if( 1 < numFeeds ) {
							// Pluralize noun
							noun += 's';
						}
					} else { // If active feeds
						// If more than 1 active feed
						if ( 1 < numActiveFeeds ) {
							// Pluralize noun
							noun += 's';
						}
					}

					// Update feeds status text
					numActiveFeedsText.text( numActiveFeeds );
					nounActiveFeedsText.text( noun );
					autoposterStatusText.text( autoposterStatus );
					feedsStatusText.text( feedsStatus );
				});
			});

			// Listen for change across the entire form and all of its inputs
			allInputsAndForm.on( 'change', function() {
				// Form has changed
				formChanged = true;

				// Enable confirm unload (onbeforeunload)
				GrabPressAutoposter.setConfirmUnload( true );
			});

			// If cancel editing is clicked
			cancelEditing.on( 'click', function() {
				// Define vars
				var confirmed;

				if ( formChanged ) {
					// Disable onbeforeunload
					window.onbeforeunload = null;

					// Confirm cancel editing
					confirmed = window.confirm( 'Are you sure you want to cancel editing? You will continue to receive videos based on its settings. All of your changes will be lost.' );

					// If confirmed
					if ( confirmed ) {
						// Redirect to main Autoposter page
						window.location = 'admin.php?page=gp-autoposter';
					} else { // Not confirmed
						// Prevent default behavior
						return false;
					}
				} else {
					// Redirect to main Autoposter page
					window.location = 'admin.php?page=gp-autoposter';
				}
			});

			// If a UI select menu is clicked
			uiSelectMenus.on( 'click', function() {
				// Hide UI multi-select menus
				uiMultiSelectMenus.hide();
			});

			// Attach multiselect to channel dropdown menu
			channelDropdown.multiselect( this.multiSelectOptionsChannels, {
				uncheckAll: function( e, ui ) {
					// Do nothing
				},

				checkAll: function( e, ui ) {
					// Do nothing
				}
			});

			// Run validation each time change happens on form
			createFeedForm.on( 'change', this.doValidation );

			// Configure modal panel
			modal.dialog({
				autoOpen: false,
				width: 400,
				modal: true,
				resizable: false,
				buttons: {
					'Cancel': function() {
						$( this ).dialog( 'close' );
					},
					'Create Feed': function() {
						var name = $( '#dialog-name' ).val();
						$( '#name' ).val( name );
						self.validateFeedName( 'edit' );
					}
				}
			});

			// Confirgure keywords modal panel
			modalKeywords.dialog({
				autoOpen: false,
				width: 400,
				modal: true,
				resizable: false,
				buttons: {
					'Cancel': function() {
							$( this ).dialog( 'close' );
					},
					'Create Feed': function() {
							var edit = $( '#keywords_dialog #edit_feed' ).val();
							self.validateFeedName( edit );
							$( this ).dialog( 'close' );
					}
				}
			});

			// TODO: This code effectively does nothing because of return false
			// placement, figure out if needed and if so fix
			// updateFeedBtns.on( 'mousedown', function( e ) {
			// 	// If middle mouse button
			// 	if ( 2 == e.which ) {
			// 		// Get ID from element ID
			// 		id = this.id.replace( 'btn-update-', '' );
			// 		self.editFeed( id );
			// 	}
			// });

			updateFeedBtns.on( 'click', function( e ) {
				// Get ID from element ID
				var id = this.id.replace( 'btn-update-', '' );

				// Edit feed for ID
				self.editFeed( id );

				// Prevent default behavior
				return false;
			});

			//right click behavior - comment this line to fix auto559 - right click acting like left click
			/*
			$('.btn-update-feed').on("contextmenu",function(e){
					id = this.id.replace('btn-update-','');
					GrabPressAutoposter.editFeed(id);
					return false;
			});
			*/

			// If there is an API error displayed
			if ( 'There was an error connecting to the API! Please try again later!' === message.text() ) {
				// Disable form inputs
				allFormInputs.attr( 'disabled', 'disabled' );
			}

			// If referred by create and and any keyword types exist
			if( 'create' === refererInput.val() && ( keywordsAnd || keywordsNot || keywordsOr || keywordsPhrase ) ) {
				// Enable conform unload (onbeforeunload)
				this.setConfirmUnload( true );
			}

			// When form is submitted
			createFeedForm.on( 'submit', function() {
				// Disable confirm unload (onbeforeunload)
				self.setConfirmUnload( false );
			});
		},

		/**
		 * Add videos to the modal preview window via AJAX request using entered
		 * keywords
		 * @return {Boolean} Always returns false to prevent default browser behavior
		 */
		previewVideos: function () {
			// Determine if validation errors exist
			var errors = GrabPressAutoposter.hasValidationErrors();

			// If no validation errors
			if ( ! errors ) {

				// Define vars
				var data, dialog,
						form = $( '#form-create-feed' ),
						keywordsAnd = form.find( 'input[name="keywords_and"]' ).val(),
						keywordsOr = form.find( 'input[name="keywords_or"]' ).val(),
						keywordsNot = form.find( 'input[name="keywords_not"]' ).val(),
						keywordsPhrase = form.find( 'input[name="keywords_phrase"]' ).val(),
						providersSelect = form.find( '#provider-select' ).val(),
						channelsSelect = form.find( '#channel-select' ).val(),
						previewModal = $( '<div id="preview-modal">' )
				;

				// Build data object
				data = {
					'action': 'gp_get_preview',
					'keywords_and': keywordsAnd,
					'keywords_or': keywordsOr,
					'keywords_not': keywordsNot,
					'keywords_phrase': keywordsPhrase,
					'providers': providersSelect,
					'channels': channelsSelect
				};

				// Attach config to dialog modal panel
				dialog = previewModal.dialog( this.previewDialogConf );

				// Load modal panel
				dialog.load( ajaxurl, data, function() {
					// Once loading finishes, remove the loading class
					dialog.removeClass( 'loading' );
				});

			} else { // Has validation errors
				// Popup up an alert with the errors
				alert( errors );
			}

			// Prevent the browser from following the link
			return false;
		},

		/**
		 * Presents a message to the user to confirm that the window should unload
		 * its resources even though changes have not been saved.
		 * @param {Boolean} shouldDisplay Should display unload message
		 */
		setConfirmUnload: function( shouldDisplay ) {
			window.onbeforeunload = ( shouldDisplay ) ? this.unloadMessage : null;
		},

		/**
		 * Returns unload confirm message
		 * @return {String} Unload confirm message
		 */
		unloadMessage: function() {
			return 'You have entered new data on this page. If you navigate away ' +
			'from this page without first saving your data, the changes will be ' +
			'lost.';
		},

		/**
		 * Validates feed name based on contents/length/ and creates a feed if
		 * required
		 * @param  {String} edit Action to take
		 */
		// TODO: This function is all over the place, should separate update/edit
		// check from actual validation functionality
		validateFeedName: function( edit ) {
			// Define vars
			var data,
					feedDate = $( '#feed_date' ).val(),
					feedNameInput = $( '#name' ),
					feedName = feedNameInput.val(),
					createFeedForm = $( '#form-create-feed' ),
					modalName = $( '#dialog-name' ),
					modal = $( '#dialog' ),
					self = this
			;

			// If no feed name specified
			if ( '' === feedName ) {
				// Set name to feedDate
				feedNameInput.val( feedDate );

				// Pick a default post category if none have selected
				this.validateCategory();

				// Submit form
				createFeedForm.submit();
			}

			// Trim feed name
			feedName = $.trim( feedName );

			// Build data array
			data = {
				action: 'gp_feed_name_unique',
				name: feedName,
				id: $( '#name' ).val()
			};
/*console.log(data.id);*/
			// If update is required
			if ( 'update' === edit ) {
				// Determine which action to take based on valid/invalid feed name
				this.handleFeedNameValidation( feedName );
			} else { // Create feed
				// Create feed via AJAX
				$.post( ajaxurl, data, function( response ) {
					// If creation fails
					if ( 'true' !== response ) {
						// If feed is named with its feed date and no edit string passed
						if ( feedDate === feedName && ! edit ) {
							// Config and open modal panel
							modalName.val( feedName );
							modal.dialog( 'open' );
						} else { // Custom name provided and edit string exists
							// Determine which action to take based on valid/invalid feed name
							self.handleFeedNameValidation( feedName );
						}
					} else { // Creation success
						// Popup alert message letting the user know name is already taken
						alert( 'The name entered is already in use. Please select a different name.' );
					}
				});
			}
		},

		/**
		 * Check if any keywords have previously been saved in a created feed
		 * @param  {String} edit String specifying whether to update or create
		 */
		validateKeywords: function( edit ) {
			// Define vars
			var keywords = [],
					keys = 0,
					textKeywords = '',
					text = '',
					feed = null,
					keywordsAnd = $( '#keywords_and' ).val(),
					keywordsOr = $( '#keywords_or' ).val(),
					keywordsPhrase = $( '#keywords_phrase' ).val(),
					modalKeywords = $( '#keywords_dialog' ),
					self = this
			;

			// Trim keywords
			keywordsAnd = $.trim( keywordsAnd );
			keywordsOr = $.trim( keywordsOr );
			keywordsPhrase = $.trim( keywordsPhrase );

			// If and keywords string not empty
			if ( keywordsAnd.length ) {
				// Convert to array
				keywordsAnd = keywordsAnd.split( ' ' );

				// Merge into keywords array
				$.merge( keywords, keywordsAnd );
			}

			// If or keywords string not empty
			if ( keywordsOr.length ) {
				// Convert to array
				keywordsOr = keywordsOr.split( ' ' );

				// Merge into keywords array
				$.merge( keywords, keywordsOr );
			}

			// If keywords phrase string not empty
			if ( keywordsPhrase.length ) {
				// Check to see if phrases exists in a feed
				feed = this.findMatchingKeyword( keywordsPhrase );

				// If feed found with phrase
				if ( feed ) {
					// Append phrase to keywords string
					textKeywords += ' <strong>"' + keywordsPhrase + '"</strong>(exact phrase), ';

					// Increment keys
					keys++;
				}
			}

			// Loop through keywords
			$.each( keywords, function( i, value ) {
				// Check to see if keyword match found in existing feeds
				feed = self.findMatchingKeyword( value );

				// If feed found with keyword
				if ( feed ) {
					// Append phrase to keywords string
					textKeywords += '<strong>"' + value + '"</strong> (exact phrase), ';

					// Increment keys
					keys++;
				}
			});

			// If no keys
			if ( ! keys ) {
				// Make sure feed name is valid
				this.validateFeedName( edit );
			} else { // Keys exist
				// Trim last 2 chars from end of string
				textKeywords = textKeywords.slice( 0, -2 );

				// If only one key
				if ( 1 === keys ) {
					// Generate singular text
					text = 'The keyword ' + textKeywords + ' is ';
				} else { // More than one
					// Generate plural text
					text = 'The keywords' + textKeywords + ' are ';
				}

				// Finish text message
				text += 'already used by previously created feeds.<br />The videos ';
				text += 'matching a keyword will show only in the feed that was created first.';

				// Setup and open keywords modal panel
				modalKeywords.find( 'p' ).html( text );
				modalKeywords.find( '#edit-feed' ).val( edit );
				modalKeywords.dialog( 'open' );
			}
		},

		validateCategory: function() {
			var cat = $( '#cat' );

			// If none of the post category has selected, assign a default one
			if ( cat.val() == null ) {
				var idx = null;
				$( "#cat > option" ).each( function() {
					if ( idx == null || $( this ).val() < idx ) {
						idx = $( this ).val();
					}
				});

				// Checked the default post category
				if ( idx != null ) {
					$( '#cat > option[ value=' + idx + ']' ).prop( 'selected', true );
				}
			}
		}
	}; // End GrabPressAutoposter

	// DOM ready
	$(function() {
		// Initialize form
		GrabPressAutoposter.initSearchForm();
	});

	// Page full loaded, including graphics
	$( window ).load( function() {
		// Run validation on the form
		GrabPressAutoposter.doValidation();
	});

})(jQuery); // End $ scope
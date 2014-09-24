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

var GrabPressCatalog;

// Avoid jQuery conflicts with other plugins
(function($) {

	/**
	 * Class for handling the GrabPress Catalog form on the client side.
	 *
	 * @class GrabPressCatalog
	 * @constructor
	 */
	GrabPressCatalog = {
		// Define properties
		message: $( '#message p' ),

		/**
		 * Configure providers multi-select options
		 * @type {Object}
		 */
		multiSelectOptions: {
			noneSelectedText: 'Select at least one Provider',
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
		 * Configure video categories multi-select options
		 * @type {Object}
		 */
		multiSelectOptionsChannels: {
			noneSelectedText: 'Select at least one Video Category',
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
		 * Attaches even listener and callback actions to "clear search" link
		 * @param  {String} action Action to take
		 */
		clearSearch: function( action ) {
			// Define vars
			var clearSearchLink = $( '#clear-search' ),
					keywords = $( '#keywords' ),
					providers = $( '#provider-select option' ),
					providersDropdown = $( '#provider-select' ),
					channels = $( '#channel-select option' ),
					channelDropdown = $( '#channel-select' ),
					sortByRelevance = $( '.sort_by[value="relevance"]' ),
					sortByDate = $( '.sort_by[value="created_at"]' ),
					createdBefore = $( '#created_before' ),
					createdAfter = $( '#created_after' ),
					pagination = $( '#pagination' ),
					paginationBottom = $( '#pagination-bottom' ),
					self = this
			;

			// Attach click listener to "clear search" link
			clearSearchLink.on( 'click', function() {
				// Reset search form
				keywords.val( '' );
				providers.attr( 'selected', 'selected' );
				providersDropdown
					.multiselect("refresh")
					.multiselect({
						selectedText: 'All providers selected'
					})
				;
				channels.attr( 'selected', 'selected' );
				channelDropdown
					.multiselect( 'refresh' )
					.multiselect({
							selectedText: 'All Video Categories'
					})
				;
				sortByRelevance.removeAttr( 'checked' );
				sortByDate.attr( 'checked', 'checked' );
				createdBefore.val( '' );
				createdAfter.val( '' );
				self.submitClear( action );
				pagination.children().remove();
				paginationBottom.children().remove();
			});
		},

		/**
		 * Validates Catalog search form
		 */
		doValidation: function( preview ) {
			// Define vars
			var errors = this.hasValidationErrors( preview ),
					createFeedBtn = $( '#btn-create-feed' ),
					updateSearchBtn = $( '#update-search' ),
					allFormInputs = $( ':input' ),
					hidden = $( '.hide' )
			;

			// If no errors exist
			if ( ! errors ) {
				// Enable create feed and update search buttons
				createFeedBtn.removeAttr( 'disabled' );
				updateSearchBtn
					.removeAttr( 'disabled' )
					.off( 'click' ) // Unbind click event
				;

				// Show hidden
				hidden.show();
			} else {
				// Disable create feed and update search buttons
				createFeedBtn.attr( 'disabled', 'disabled' );
				updateSearchBtn
					.attr( 'disabled', 'disabled' )
					.off( 'click' ) // Unbind click event
				;

				// Hide hidden
				hidden.hide();
			}

			allFormInputs.each( function() {
				// Define vars
				var $this = $( this );

				// If placeholder is "Enter keywords"
				if ( 'Enter keywords' === $this.attr( 'placeholder' ) ) {
					// Apply maxlength attribute
					$this.attr( 'maxlength', '32' );
				}
			});
		},

		/**
		 * Checks whether form has validation errors, based on current message and
		 * channel and provider select length
		 * @return {Boolean} Form has validation errors
		 */
		hasValidationErrors: function( preview ) {
			// Define vars
			var errorMessage = 'There was an error connecting to the API! Please try again later!',
					selectedChannels = $( '#channel-select :selected' ),
					selectedProviders = $( '#provider-select :selected' )
			;

			// If error message is currently being displayed
			if ( errorMessage === this.message.text() ) {
				// Validation errors exist
				return true;
			}

			// If preview
			if ( preview ) {
				// Pull selected channels and providers from preview elements
				selectedChannels = $( '#channel-select-preview :selected' );
				selectedProviders = $( '#provider-select-preview :selected' );
			}

			// If no selected channels or providers
			if ( ! selectedChannels.length || ! selectedProviders.length ) {
				// Validation errors exist
				return true;
			}

			// No validation errors
			return false;
		},

		/**
		 * Initialize catalog search forms
		 */
		initSearchForm: function() {
			// Define vars
			var preview,
					howItWorksLink = $( '#how-it-works' ),
					helpLink = $( '#help' ),
					providers = $( '#provider-select option' ),
					selectedProviders = $( '#provider-select option:selected' ),
					previewProviders = $( '#provider-select-preview option' ),
					selectedPreviewProviders = $( '#provider-select-preview option:selected' ),
					providerDropdown = $( '#provider-select' ),
					providerPreviewDropdown = $( '#provider-select-preview' ),
					channels = $( '#channel-select option' ),
					selectedChannels = $( '#channel-select option:selected' ),
					previewChannels = $( '#channel-select-preview option' ),
					selectedPreviewChannels = $( '#channel-select-preview option:selected' ),
					channelDropdown = $( '#channel-select' ),
					channelPreviewDropdown = $( '#channel-select-preview' ),
					url = window.location.href,
					host = url.split( '/wp-admin/' )[0],
					datepicker = $( '.datepicker' ),
					clearDates = $( '#clearDates' ),
					createdBefore = $( '#created_before' ),
					createdAfter = $( '#created_after' ),
					catalogForm = $( '#form-catalog-page' ),
					message = $( '#message p' ).text(),
					allFormInputs = $( ':input' ),
					videoSummaries = $( '.video-summary' ),
					env = $( '#environment' ).val(),
					modalID = '1720202',
					self = this
			;

			// Attach Simpletip to "how it works" link
			howItWorksLink.simpletip({
				content: 'The Grabpress plugin gives your editors the power of our constantly updating video catalog from the dashboard of your Wordpress CMS. Leveraging automated delivery, along with keyword feed curation, the Grabpress plugin delivers article templates featuring video articles that compliment the organic content creation your site offers.<br /><br /> As an administrator, you may use Grabpress to set up as many feeds as you desire, delivering content based on intervals you specify. You may also assign these feeds to various owners, if your site has multiple editors, and the articles will wait in your drafts folder until you see a need to publish. Additionally, for smaller sites, you can automate the entire process, publishing automatically and extending the reach of your site without adding work to your busy day. <br /><br /> To get started, select a channel from our catalog, hone your feed by adding keywords, set your posting interval, and check the posting options (post interval, player style, save as draft or publish) for that feed to make sure the specifications meet your needs. Click the preview feed button to see make sure your feed will generate enough content and that the content is what you are looking for. If the feed seems to be right for you, save the feed and you will start getting new articles delivered to your site at the interval you specified. <br /><br />',
				fixed: true,
				position: 'bottom'
			});

			// Attach Simpletip to help link
			helpLink.simpletip({
				content: "This search input supports Google syntax for advanced search:<br /><b>Every</b> term separated only by a space will be required in your results.<br />At least one of any terms separated by an ' OR ' will be included in your results.<br />Add a '-' before any term that must be <b>excluded</b>.<br /> Add quotes around any \"exact phrase\" to look for.<br /><br />",
				fixed: true,
				position: 'bottom'
			});

			// If no selected providers
			if( ! selectedProviders.length ) {
				// Select all providers
				providers.attr( 'selected', 'selected' );
			}

			// If no selected preview providers
			if( ! selectedPreviewProviders.length ) {
				// Select all preview providers
				previewProviders.attr( 'selected', 'selected' );
			}

			// If no selected channels
			if( ! selectedChannels.length ) {
				// Select all channels
				channels.attr( 'selected', 'selected' );
			}

			// If no selected preview channels
			if( ! selectedPreviewChannels.length ) {
				// Select all preview channels
				previewChannels.attr( 'selected', 'selected' );
			}

			// Attach multiselect to channel dropdown
			channelDropdown.multiselect( this.multiSelectOptionsChannels, {
				uncheckAll: function( e, ui ) {
					self.doValidation();
				},
				checkAll: function( e, ui ) {
					self.doValidation();
				}
			});

			// Attach multiselect to channel preview dropdown
			channelPreviewDropdown.multiselect( this.multiSelectOptionsChannels, {
				uncheckAll: function( e, ui ) {
					self.doValidation( 1 );
				},
				checkAll: function( e, ui ) {
					self.doValidation( 1 );
				}
			});

			// Attach multiselect to provider dropdown
			providerDropdown.multiselect( this.multiSelectOptions, {
				uncheckAll: function( e, ui ) {
					self.doValidation();
				},
				checkAll: function( e, ui ) {
					self.doValidation();
				}
			}).multiselectfilter();

			// Attach multiselect to provider preview dropdown
			providerPreviewDropdown.multiselect( this.multiSelectOptions, {
				uncheckAll: function( e, ui ) {
					self.doValidation( 1 );
				},
				checkAll: function( e, ui ) {
					self.doValidation( 1 );
				}
			}).multiselectfilter();

			// Configure "From" datepicker
			createdAfter.datepicker({
				showOn: 'both',
				buttonImage: host + '/wp-content/plugins/grabpress/images/icon-calendar.gif',
				buttonImageOnly: true,
				changeMonth: true,
				changeYear: true,
				showAnim: 'slideDown',
				duration: 'fast',
				maxDate: 0,
				numberOfMonths: 3,
				onClose: function( selectedDate ) {
					if ( selectedDate ) {
						createdBefore.datepicker( 'option', 'minDate', selectedDate );
					}
				}
			});

			// Configure "To" datepicker
			createdBefore.datepicker({
				showOn: 'both',
				buttonImage: host + '/wp-content/plugins/grabpress/images/icon-calendar.gif',
				buttonImageOnly: true,
				changeMonth: true,
				changeYear: true,
				showAnim: 'slideDown',
				duration: 'fast',
				maxDate: 0,
				numberOfMonths: 3,
				onClose: function( selectedDate ) {
					if ( selectedDate ) {
						createdAfter.datepicker( 'option', 'maxDate', selectedDate );
					}
				}
			});

			// On clear dates click
			clearDates.on( 'click', function() {
				// Clear created before and after dates
				createdAfter.val( '' );
				createdBefore.val( '' );
			});

			// If channel preview dropdown
			if( channelPreviewDropdown ) {
				// Set preview to true
				preview = 1;
			}

			// On change do validation on preview
			catalogForm.on( 'change', this.doValidation( preview ) );

			// If API connection error message
			if ( 'There was an error connecting to the API! Please try again later!' === message ) {
				// Disable all form inputs
				allFormInputs.attr( 'disabled' , 'disabled' );
			}

			// Attach jQuery ellipsis plugin (auto-ellipsis) functionality to video
			// summaries
			videoSummaries.ellipsis( 5, true, 'more', 'less' );

			// If grabModal global does not exist
			if ( ! window.grabModal ) {
				try {
					// Create it with defaults
					window.grabModal = new com.grabnetworks.Modal({
						id: modalID,
						tgt: env,
						width: 800,
						height: 450
					});
				} catch( err ) {
					// Do nothing
				}
			}
		},

		/**
		 * Pagination
		 * @param  {String} action Action to take
		 */
		pagination: function( action ) {
			// Define vars
			var pagination = $( '#pagination' ),
					paginationBottom = $( '#pagination-bottom' ),
					feedCount = $( '#feed_count' ).val(),
					createFeedBtn = $( '#btn-create-feed' ),
					keywords = $( '#keywords' ),
					self = this
			;

			// Configure pagination
			pagination.pagination({
				items: feedCount,
				itemsOnPage: 20,
				cssStyle: 'light-theme',
				displayedPages: 10,
				onPageClick: function( pagenumber , event ) {
					// If create feeds and keywords is visible
					if ( createFeedBtn.is( ':visible' ) && keywords.is( ':visible' ) ) {
						// Update action to get catalog tab
						action = 'gp_get_catalog_tab';
					}

					// Submit search based on action and page number
					self.submitSearch( action, pagenumber );

					// If bottom pagination is not empty
					if ( paginationBottom.children().length ) {
						// Empty out all children elements
						paginationBottom.children().remove();
					}

					// Clone pagination into bottom pagination
					pagination.children().clone( true ).appendTo( paginationBottom );
				}
			});

			// If pagination contains less than 4 children ( only 1 page )
			if ( 4 > pagination.children().length ) {
				// Remove content from both top and bottom pagination
				pagination.children().remove();
				paginationBottom.children().remove();
			}
		},

		/**
		 * Initialize insert into post AJAX search form
		 * @return {[type]} [description]
		 */
		postSearchForm: function() {
			// Define var
			var currentTop,
					window = $( window ),
					multiselect = $( '.ui-multiselect' ),
					mulltiselectMenu = $( '.ui-multiselect-menu' ),
					thickboxContent = $( '#tb-ajax-content' ),
					catalogForm = $( '#form-catalog-page' ),
					sortBys = $( '.sort_by' ),
					insertIntoPost = $( '.insert_into_post' ),
					self = this
			;

			// On window scroll
			window.on( 'scroll', function() {
				// If multiselect exists
				if ( multiselect.length ) {
					// Get top position of multiselect
					currentTop = multiselect.position().top;
				}

				// Update position of multiselect menu
				mulltiselectMenu.css({
					top: currentTop + 61,
					position: 'fixed'
				});
			});

			// On thickbox scroll
			thickboxContent.on( 'scroll', function() {
				// If multiselect exists
				if ( multiselect.length ) {
					// Get top position of multiselect
					currentTop = multiselect.position().top;
				}

				// Update position of multiselect menu
				mulltiselectMenu.css({
					top: currentTop + 61,
					overflow: 'auto',
					'z-index': 103
				});
			});

			// On catalog form submit
			catalogForm.on( 'submit', function( e ) {
				// Prevent default behavior of submit form
				e.preventDefault();

				// Submit search
				self.submitSearch( 'gp_get_catalog' );

				// Prevent double submit my extra clicks
				return false;
			});

			// On sort by change
			sortBys.on( 'change', function() {
				// Submit search
				self.submitSearch( 'gp_get_catalog' );
			});

			// On insert into post click
			insertIntoPost.on( 'click', function() {
				// Define vars
				var data,
						videoID = this.id.replace( /btn-create-feed-single-/gi, '' )
				;

				// Build data object
				data = {
					action: 'gp_insert_video',
					format: 'embed',
					video_id: videoID
				};

				// Insert video via AJAX
				$.post( ajaxurl, data, function( response ) {
					// If response is ok
					if ( 'ok' === response.status ) {
						// Add response to input box
						send_to_editor( response.content );
					}

					// Update global Thickbox position from global backup position
					window.tb_position = window.backup_tb_position;

					// Prevent insert from occurring multiple times
					return false;
				}, 'json' );
			});

			// Prime pagination
			this.setupPagination( 'gp_get_catalog' );

			// Clear search form
			this.clearSearch( 'gp_get_catalog' );
		},

		/**
		 * Submit search request for preview
		 */
		previewSearchForm: function() {
			// Define vars
			var catalogForm = $( '#form-catalog-page' ),
					sortBys = $( '.sort_by' ),
					self = this
			;

			// On catalog form submit
			catalogForm.on( 'submit', function(e) {
				// Prevent default form submit
				e.preventDefault();

				// Submit search
				self.submitSearch('gp_get_preview');

				// Prevent multiple submits
				return false;
			});

			// On sort by change
			sortBys.on( 'change', function() {
				// Submit search
				self.submitSearch( 'gp_get_preview' );
			});

			// Prime pagination
			self.setupPagination( 'gp_get_preview' );

			// Clear search form
			self.clearSearch( 'gp_get_preview' );
		},

		/**
		 * Handles initial setup of pagination
		 * @param  {String} action Action to take
		 */
		setupPagination: function( action ) {
			// Define vars
			var content, top, results,
					pagination = $( '#pagination' ),
					paginationBottom = $( '#pagination-bottom' ),
					catalogAction = $( '#action-catalog' ),
					position = ''
			;

			// If no pagination does not exist
			if ( ! pagination.length ) {
				// If action is catalog search
				if( 'catalog-search' === catalogAction ) {
					// Content area is catalog page form
					content = $( '#form-catalog-page' );
				} else { // Other acton
					// Content area is catalog container
					content = $( '#gp-catalog-container' );
				}

				// Insert pagination before content
				$( '<div id="pagination"></div>' ).insertBefore( content );

				// Get newly created pagination div
				pagination = $( '#pagination' );

				// Set top position to 260px
				top = '260px';

				// If browser is IE
				if ( GrabPressUtils.browserIsIE() ) {
					// Set top position to 265px
					top = '265px';
				}

				// If action is get catalog tab
				if ( 'gp_get_catalog_tab' === action ) {
					// Set top position to 500px
					top = '500px';
				}

				// Set position to relative
				position = 'relative';

				if ( 'gp_get_preview' === action ) {
					position = 'static';
				}

				// Set position for pagination
				pagination.css({
					position: position,
					top: top,
					left: '10px'
				});

				// Generate pagination
				this.pagination( action );

				// Check if bottom pagination exists
				if ( ! paginationBottom.length ) {
					// Grab results from parent element of content
					results = content.parent();

					// If results exists
					if ( results.length ) {
						// Append bottom pagination div to results
						results.append( '<div id="pagination-bottom"></div>' );

						// Apply margins and class to bottom pagination
						paginationBottom
							.css({
								'margin-top': '10px',
								'margin-bottom': '15px'
							})
							.addClass( 'light-theme' )
						;

						// Clone top pagination into bottom pagination
						pagination.children().clone( true ).appendTo( paginationBottom );
					}
				}
			}
		},

		/**
		 * Submit clear searc form
		 * @param  {String} action Action to take
		 */
		submitClear: function( action ) {
			// Define vars
			var data,
					keywords = $( '#keywords' ).val(),
					providersDropdown = $( '#provider-select' ),
					channelDropdown = $( '#channel-select' ),
					sortBy = $( '.sort_by:checked' ).val(),
					createdBefore = $( '#created_before' ).val(),
					createdAfter = $( '#created_after' ).val(),
					catalogContainer = $( '#gp-catalog-container' ),
					pagination = $( '#pagination' ),
					paginationBottom = $( '#pagination-bottom' ),
					self = this
			;

			// Build data object
			data = {
				action: action,
				empty: true,
				keywords: keywords,
				providers: providersDropdown.val(),
				channels: channelDropdown.val(),
				sort_by: sortBy,
				'created_before': createdBefore,
				'created_after': createdAfter
			};

			// Submit clear via AJAX
			$.post( ajaxurl, data, function( response ) {
				// Replace content of catalog container with response
				catalogContainer.replaceWith( response );

				// Update pagination
				self.pagination( action );

				// Remove contents of bottom pagination
				paginationBottom.children().remove();

				// Clone top pagination into bottom pagination
				pagination.children().clone( true ).appendTo( paginationBottom );
			});
		},

		/**
		 * Handle search submission from modal window
		 * @param  {String} action Action to be taken
		 * @param  {String|Integer} page   Page number
		 */
		submitSearch: function( action, page ) {
			// Define vars
			var content,
					data = {},
					display = '',
					channel = '',
					provider = '',
					channelSelectPreview = $( '#channel-select-preview' ),
					channelDropdown = $( '#channel-select' ),
					providerSelectPreview = $( '#provider-select-preview' ),
					providerDropdown = $( '#provider-select' ),
					catalogAction = $( '#action-catalog' ).val(),
					pagination = $( '#pagination' ),
					paginationBottom = $( '#pagination-bottom' ),
					self = this
			;

			// If action is get catalog tab
			if ( 'gp_get_catalog_tab' === action ) {
				// Change action to get catalog
				action = 'gp_get_catalog';

				// Update display
				display = 'Tab';
			}

			// If channel select preview is undefined
			if ( ! channelSelectPreview.val() ) {
				// Set channel from channel dropdown
				channel = channelDropdown.val();
			} else { // Is defined
				channel = channelSelectPreview.val();
			}

			// If provider select preview is undefined
			if ( ! providerSelectPreview.val() ) {
				// Set provider from provider dropdown
				provider = providerDropdown.val();
			} else { // Is defined
				provider = providerSelectPreview.val();
			}

			data = {
				'action': action,
				'empty': false,
				'keywords': $( '#keywords' ).val(),
				'providers': provider,
				'channels': channel,
				'sort_by': $( '.sort_by:checked' ).val(),
				'created_before': $( '#created_before' ).val(),
				'created_after': $( '#created_after' ).val(),
				'page': page,
				'display': display
			};


			// If catalog action is catalog search
			if ( 'catalog-search' === catalogAction ) {
				// Content area is preview feed
				content = $( '#preview-feed' );
			} else {
				// Content area is catalog container
				content = $( '#gp-catalog-container' );
			}

			// Run search via AJAX
			$.post( ajaxurl, data, function( response ) {
				// Replace content with response
				content.replaceWith( response );

				// If page is undefined
				if ( ! page ) {
					// Generate pagination
					self.pagination( action );

					// Clear bottom pagination
					paginationBottom.children().remove();

					// Clone top pagination into bottom pagination with attached event
					// handlers
					pagination.children().clone( true ).appendTo( paginationBottom );
				}

				// If display exists
				if ( display ) {
					// Position pagination 500px from top
					pagination.css( 'top', '500px' );
				}
			});
		},

		/* Initialization specific to Catalog tab template page */
		/**
		 * Initialize Catalog tab template page
		 * @param  {String} action Action to be taken
		 */
		tabSearchForm: function( action ) {
			// Define vars
			var closePreviews = $( '.close-preview' ),
					createFeedBtn = $( '#btn-create-feed' ),
					createSingleFeedBtns = $( '.btn-create-feed-single' ),
					clearSearchLink = $( '#clear-search' ),
					sortBys = $( '.sort_by' ),
					env = $( '#environment' ),
					modalID = '1720202',
					self = this
			;

			// Attach click event listener to close preview buttons
			closePreviews.on( 'click', function() {
				// Define vars
				var previewFeedForm = $( '#preview-feed' ),
						previewAction = $( '#action-preview-feed' )
				;

				// TODO: Figure out where feed_action should come from
				//action.val( feed_action );

				// Submit preview feed form
				previewFeedForm.submit();
			});

			// Attach click event listener to create feed button
			createFeedBtn.on( 'click', function() {
				// Define vars
				var catalogForm = $( '#form-catalog-page' ),
						catalogAction = $( '#action-catalog' )
				;

				// Update action
				catalogAction.val( 'prefill' );
				catalogForm.attr( 'action', 'admin.php?page=gp-autoposter' );

				// Submit catalog form
				catalogForm.submit();
			});

			createSingleFeedBtns.on( 'click', function() {
				// Define vars
				var data,
						videoID = this.id.replace( /btn-create-feed-single-/gi, '' )
				;

				// Build data object
				data = {
					action: 'gp_insert_video',
					format: 'post',
					video_id: videoID
				};

				// Insert video via AJAX
				$.post( ajaxurl, data, function( response ) {
					// If redirect required
					if ( 'redirect' === response.status ) {
						// Redirect to reponse URL
						window.location = response.url;
					}
				}, 'json' );
			});

			clearSearchLink.on( 'click', function() {
				// Refresh page
				// TODO: Is a full page refresh required? Couldn't we just reset the
				// the form?
				window.location = 'admin.php?page=gp-catalog';
			});

			sortBys.on( 'change', function() {
				// Define vars
				var catalogForm = $( '#form-catalog-page' );

				// If action is get catalog
				if ( 'gp_get_catalog' !== action ) {
					// Submit catalog form
					catalogForm.submit();
				} else { // Not get catalog
					// Load catalog tab
					self.submitSearch( 'gp_get_catalog_tab' );
				}
			});

			// Setup pagination
			this.setupPagination( 'gp_get_catalog_tab' );

			// If no grabModal global exists
			if ( ! window.grabModal ) {
				try {
					// Create and configure global modal panel
					window.grabModal = new com.grabnetworks.Modal({
						id: modalID,
						tgt: env,
						width: 800,
						height: 450
					});

					// Hide modal panel
					window.grabModal.hide();
				} catch ( error ) {
					// Do nothing
				}
			}
		},

		/**
		 * Calculate position for Thickbox based on current window size
		 */
		getTBPosition: function() {
			// Define vars
			var spartaPaymentWidth = 930,
					windowWidth = $( window ).width(),
					windowHeight = $( window ).height(),
					tbNewWidth = windowWidth < spartaPaymentWidth + 40 ? windowWidth - 40 : spartaPaymentWidth,
					tbNewHeight = windowHeight - 70,
					tbNewMargin = ( windowWidth - spartaPaymentWidth ) / 2,
					tbWindow = $( '#TB_window' ),
					tbWindowAndIFrame = $( '#TB_window, #TB_iframeContent' ),
					tbAJAXContent = $( '#TB_ajaxContent' )
			;

			// Set position
			tbWindow.css({
				'margin-left': -( tbNewWidth / 2 ),
				'margin-top': -( tbNewHeight / 2 )
			});
			tbWindowAndIFrame
				.width( tbNewWidth )
				.height( tbNewHeight )
			;
			tbAJAXContent
				.width( tbNewWidth - 33 )
				.height( tbNewHeight - 29 )
			;
		}

	}; // End GrabPressCatalog

})(jQuery); // End $ scope
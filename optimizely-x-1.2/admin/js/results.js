(
	function ( $, window, document, undefined ) {
		'use strict';

		// Define an object to encapsulate results page functionality.
		var OptimizelyResults = {

			// Template for a button replacement during ajax calls
			spinner: '<span class="button ajax"><span class="spinner is-active"></span></span>',

			/**
			 * Initializes the functionality of the Optimizely results page.
			 */
			init: function () {
				// Event handler for the Start and Pause buttons
				$( '.optimizely-actions .button.status' ).on( 'click', OptimizelyResults.updateStatus );

				// Event handler for the Archive button
				$( '.optimizely-actions .button.archive' ).on( 'click', OptimizelyResults.archiveExperiment );

				// Event handler for the Launch buttons
				$( '.experiment-wrapper .button.launch' ).on( 'click', OptimizelyResults.launchExperiment );
			},

			/**
			 * Toggles the status of the experiment based on which button was clicked.
			 */
			updateStatus( e ) {
				e.preventDefault();

				var $button = $(this);
				var status = $button.data( 'status' );
				var newStatus;
				var experimentId = $button.parents( '.experiment-wrapper' ).data( 'experimentId' );

				// Determine the toggled status of the experiment
				if ( 'start' === status ) {
					newStatus = 'pause';
				} else {
					newStatus = 'start';
				}

				$button.after( OptimizelyResults.spinner );
				$button.toggle();

				// Send the AJAX request to change the experiment status.
				$.ajax( {
					url: ajaxurl,
					data: {
						action: 'optimizely_x_update_experiment_status',
						nonce: optimizely_results_nonce.nonce,
						status: status,
						experimentId: experimentId
					},
					method: 'POST'
				} ).done( function ( response ) {
					// Handle error state.
					if ( ! response.success ) {
						OptimizelyResults.displayError( experimentId, response );
						return;
					}
					// Toggle the new button to reflect the new status
					$( '.' + experimentId + ' .optimizely-actions .' + newStatus ).toggle();
					$( '.' + experimentId + ' .button.ajax' ).remove();

					switch ( newStatus ) {
						case 'pause':
						case 'start':
							break;
						case 'archive':
							$( '.experiment-wrapper.' + experimentId ).remove();
							break;
					}
				} );
			},

			/**
			 * Archives an experiment when the archive button is clicked
			 */
			archiveExperiment( e ) {
				e.preventDefault();

				var $button = $(this);
				var experimentId = $button.parents( '.experiment-wrapper' ).data( 'experimentId' );

				$button.after( OptimizelyResults.spinner );
				$button.toggle();

				// Send the AJAX request to archive the experiment
				$.ajax( {
					url: ajaxurl,
					data: {
						action: 'optimizely_x_archive_experiment',
						nonce: optimizely_results_nonce.nonce,
						experimentId: experimentId
					},
					method: 'POST'
				} ).done( function ( response ) {
					// Handle error state.
					if ( ! response.success ) {
						OptimizelyResults.displayError( experimentId, response );
						return;
					}
					$( '.experiment-wrapper.' + experimentId ).remove();
					OptimizelyResults.adminNotice(
						'Experiment ID: ' + experimentId + ' was successfully archived.',
						'success'
					);
				} );
			},

			/**
			 * Selected a winner for the post and archives the experiment
			 */
			launchExperiment( e ) {
				e.preventDefault();

				var $button = $(this);
				var experimentId = $button.parents( '.experiment-wrapper' ).data( 'experimentId' );
				var variationText = $button.parents('tr').find('.variation').text();

				$button.after( OptimizelyResults.spinner );
				$('.' + experimentId + ' .button.launch').toggle();

				// Send the AJAX request to launch the variation and archive the experiment
				$.ajax( {
					url: ajaxurl,
					data: {
						action: 'optimizely_x_launch_variation',
						nonce: optimizely_results_nonce.nonce,
						experimentId: experimentId,
						variationText: variationText
					},
					method: 'POST'
				} ).done( function ( response ) {
					// Handle error state.
					if ( ! response.success ) {
						OptimizelyResults.displayError( experimentId, response );
						return;
					}
					$( '.experiment-wrapper.' + experimentId ).remove();
					OptimizelyResults.adminNotice(
						'The variation "' + variationText + '" was successfully launched.',
						'success'
					);
				} );
			},

			/**
			 * Helper method to insert an admin notice on the page
			 */
			adminNotice( message, type = 'info' ) {
				var $notice = $('<div class="notice is-dismissible"></div>');
				var $content = $('<p></p>');
				$notice.addClass('notice-' + type);
				$content.text(message);
				$notice.append($content);
				$('.optimizely-admin h1').after( $notice );
			},

			/**
			 * Helper method to insert an error admin notice on the page.
			 *
			 * @param experimentId
			 * @param response
			 */
			displayError( experimentId, response ) {
				var errorMsg = '';
				if ( response.data[0]['message'] ) {
						errorMsg = response.data[0]['message'];
				} else if ( response.data.length ) {
					errorMsg = response.data;
				} else {
					errorMsg = 'Unspecified Error';
				}
				OptimizelyResults.adminNotice(
					'Error for Experiment ID ' + experimentId + ' : ' + errorMsg,
					'error'
				);
			}

		};

		// Initialize the config page when ready.
		$( document ).ready( function () {
			OptimizelyResults.init();
		} );
	}
)( jQuery, window, document );

(
	function ( $, window, document, undefined ) {
		'use strict';

		// Define an object to encapsulate metabox functionality.
		var OptimizelyMetabox = {

			/**
			 * A click handler for the change status button.
			 */
			changeStatus: function () {
				var $experiment = $( '#optimizely-experiment-container' );

				// Clear any existing errors before beginning.
				OptimizelyMetabox.clearError();

				// Show the loading indicator and hide the experiment status.
				$( '.optimizely-loading' ).removeClass( 'hidden' );
				$( '.optimizely-running-experiment' ).addClass( 'hidden' );

				// Send the AJAX request to change the experiment status.
				$.ajax( {
					url: ajaxurl,
					data: {
						action: 'optimizely_x_change_status',
						nonce: optimizely_metabox_nonce.nonce,
						status: $experiment.attr( 'data-optimizely-experiment-status' ),
						entity_id: $experiment.attr( 'data-optimizely-entity-id' )
					},
					method: 'POST'
				} ).done( function ( response ) {

					// Hide the loading animation and show the status.
					$( '.optimizely-loading' ).addClass( 'hidden' );
					$( '.optimizely-running-experiment' ).removeClass( 'hidden' );

					// Handle error state.
					if ( ! response.success ) {
						OptimizelyMetabox.showError( optimizely_metabox_strings.status_error );
						return;
					}

					// Update state.
					$( '#optimizely-experiment-container' ).attr(
						'data-optimizely-experiment-status',
						response.data.experiment_status
					);
					$( '#optimizely-experiment-status-text' ).text(
						response.data.experiment_status
					);
					if ( 'paused' === response.data.experiment_status ) {
						$( '.optimizely-toggle-running-pause' ).addClass( 'hidden' );
						$( '.optimizely-toggle-running-start' ).removeClass( 'hidden' );
					} else {
						$( '.optimizely-toggle-running-pause' ).removeClass( 'hidden' );
						$( '.optimizely-toggle-running-start' ).addClass( 'hidden' );
					}
				} );
			},

			/**
			 * Clears an error message, if one exists.
			 */
			clearError: function () {
				$( '#optimizely-metabox-error' ).remove();
			},

			/**
			 * A click handler for the create experiment button.
			 */
			createExperiment: function () {
				var errors = [],
					variations = [];

				// Clear any existing errors before beginning.
				OptimizelyMetabox.clearError();

				// Loop through variations and add to the array.
				$( '.optimizely-variation input[type="text"]' ).each( function ( i, e ) {
					var value = $( e ).val();

					// Check for blank titles.
					if ( '' === value ) {
						errors.push( optimizely_metabox_strings.no_title.replace( '%d', (
						i + 1
						) ) );
						return;
					}

					// Store the variation value in the consolidated array.
					variations.push( value );
				} );

				// Handle validation errors.
				if ( errors.length > 0 ) {
					OptimizelyMetabox.showError( errors.join( "\n" ) );
					return;
				}

				// Swap the new experiment block for the loading block.
				$( '.optimizely-loading' ).removeClass( 'hidden' );
				$( '.optimizely-new-experiment' ).addClass( 'hidden' );
				$( '.optimizely-variation input' ).removeAttr( 'required' );

				// Send the variation data via AJAX.
				$.ajax( {
					url: ajaxurl,
					data: {
						action: 'optimizely_x_create_experiment',
						nonce: optimizely_metabox_nonce.nonce,
						variations: JSON.stringify( variations ),
						entity_id: $( '#optimizely-experiment-container' ).attr( 'data-optimizely-entity-id' )
					},
					method: 'POST'
				} ).done( function ( response ) {
					var $runningExperiment = $( '.optimizely-running-experiment' );

					// Hide the loading animation.
					$( '.optimizely-loading' ).addClass( 'hidden' );

					// Handle error state.
					if ( ! response.success ) {
						OptimizelyMetabox.showError( optimizely_metabox_strings.experiment_error );
						$( '.optimizely-new-experiment' ).removeClass( 'hidden' );
						$( '.optimizely-variation input' ).attr( 'required', true );
						return;
					}

					// Show the experiment status box and update values.
					$runningExperiment.removeClass( 'hidden' );
					$( '.optimizely-view-link' ).attr( 'href', response.data.editor_link );
					$( '#optimizely-experiment-id' ).text( response.data.experiment_id );
					$( '#optimizely-experiment-container' ).attr( 'data-optimizely-experiment-status', 'paused' );
					$( '#optimizely-experiment-status-text' ).text( 'not_started' );
					$( '.optimizely-variation-title' ).each( function ( i, e ) {
						$( e ).text( variations[i] );
					} );
				} );
			},

			/**
			 * Initializes the functionality of the Optimizely metabox.
			 */
			init: function () {
				$( '#optimizely-create' ).on( 'click', this.createExperiment );
				$( '#optimizely-created' ).on( 'click', '.optimizely-toggle-running', this.changeStatus );
			},

			/**
			 * Displays the provided error message.
			 *
			 * @param {string} message The message to display.
			 */
			showError: function ( message ) {
				var $error = $( '<div id="optimizely-metabox-error">' ),
					$experiment = $( '#optimizely-experiment-container' );

				// Clear existing error messages, if any.
				this.clearError();

				// Build the error message and add it to the metabox.
				$error.addClass( 'optimizely-error-message' );
				$error.text( message );
				$experiment.prepend( $error );
			}
		};

		// Initialize the metabox when ready.
		$( document ).ready( function () {
			OptimizelyMetabox.init();
		} );
	}
)( jQuery, window, document );

(
	function ( $, window, document, undefined ) {
		'use strict';

		// Define an object to encapsulate config page functionality.
		var OptimizelyConfig = {

			/**
			 * Runs js_beautify on the text of an object matching the selector.
			 *
			 * @param {string} selector The selector for the element to beautify.
			 */
			beautify: function ( selector ) {
				var $object = $( selector );

				// Ensure js_beautify is loaded.
				if ( 'function' !== typeof js_beautify ) {
					return;
				}

				// Beautify the text.
				$object.text( js_beautify( $object.text() ) );
			},

			/**
			 * Handles a change event for the activation mode radio buttons.
			 */
			changeActivationMode: function () {
				var $selected = $( 'input[name="optimizely_activation_mode"]:checked' ),
					$parentTR = $( '#optimizely-conditional-activation-code' ).closest( 'tr' );

				// Fork for selected activation mode.
				if ( 'conditional' === $selected.val() ) {
					$parentTR.show();
				} else {
					$parentTR.hide();
				}
			},

			/**
			 * Hides the row for a field given a selector.
			 *
			 * @param {string} selector A selector contained in the row to be hidden.
			 */
			hideRow: function ( selector ) {
				$( selector ).closest( 'tr' ).addClass( 'hidden' );
			},

			/**
			 * Initializes the functionality of the Optimizely configuration page.
			 */
			init: function () {

				// Beautify the textareas containing compressed JavaScript.
				this.beautify( '#optimizely-conditional-activation-code' );
				this.beautify( '#optimizely-variation-template' );

				// Set up listeners for form value changes.
				this.setupConditionalActivation();

				// Set up initial view state.
				this.setupInitialState();

				// Load the project list using the API.
				this.loadProjects();
			},

			/**
			 * Attempts to load the projects list via AJAX.
			 */
			loadProjects: function () {
				var $loading = $( '#optimizely-config' ).find( '.optimizely-loading' );

				// Ensure there is a token set.
				if ( ! $( '#optimizely-token' ).val() ) {
					return;
				}

				// Show loading message while AJAX happens.
				$loading.removeClass( 'hidden' );

				// Load the project list via AJAX.
				$.ajax( {
					url: ajaxurl,
					data: {
						action: 'optimizely_x_get_projects'
					},
					dataType: 'json'
				} ).done( function ( response ) {

					// Hide loading message.
					$loading.addClass( 'hidden' );

					// Handle errors.
					if ( ! response.data || $.isEmptyObject( response.data ) ) {
						$( '.optimizely-invalid-token' ).removeClass( 'hidden' );
						return;
					}

					// Show rows that require authentication to view.
					OptimizelyConfig.showRow( '.optimizely-requires-authentication' );

					// Populate project list on success.
					OptimizelyConfig.populateProjects( response.data );
				} );
			},

			/**
			 * Populates the projects dropdown with data returned from the API.
			 *
			 * @param {array} projects An array of projects to add to the dropdown.
			 */
			populateProjects: function ( projects ) {
				var name,
					$currentOption,
					$projectList = $( '#optimizely-project-id' ),
					$newOption;

				// Loop through projects and add each.
				for ( name in projects ) {

					// Ensure name exists in projects.
					if ( ! projects.hasOwnProperty( name ) ) {
						continue;
					}

					// If the option already exists (was selected) update name from API.
					$currentOption = $projectList.find( '[value="' + projects[name] + '"]' );
					if ( $currentOption.length ) {
						$currentOption.text( name );
					} else {
						$newOption = $( '<option value="' + parseInt( projects[name] ) + '"></option>' );
						$newOption.text( name );
						$projectList.append( $newOption );
					}
				}

				// Set up change listener for the dropdown.
				$projectList.on( 'change', this.updateProject );

				// Update the project info using the currently selected value.
				this.updateProject();
			},

			/**
			 * Sets change listeners on activation modes to control code visibility.
			 */
			setupConditionalActivation: function () {

				// Setup listener for activation mode radio button changes.
				$( '#optimizely-activation-mode' ).on(
					'change',
					'[name="optimizely_activation_mode"]',
					this.changeActivationMode
				);

				// Manually trigger the change event to set initial state.
				this.changeActivationMode();
			},

			/**
			 * Sets up the initial state by hiding certain fields.
			 */
			setupInitialState: function () {
				this.hideRow( '.optimizely-field-hidden' );
				this.hideRow( '.optimizely-requires-authentication' );
			},

			/**
			 * Shows the row for a field given a selector.
			 *
			 * @param {string} selector A selector contained in the row to be shown.
			 */
			showRow: function ( selector ) {
				$( selector ).closest( 'tr' ).removeClass( 'hidden' );
			},

			/**
			 * Updates the project info based on the value of the dropdown.
			 */
			updateProject: function () {
				var $projectCode = $( '#optimizely-project-code' ),
					$projectId = $( '#optimizely-project-id' ).find( ':selected' ),
					$projectName = $( '#optimizely-project-name' ),
					$projectCodePreview;

				// Remove the existing project code.
				$projectCode.find( '.optimizely-project-code-preview' ).remove();

				// Update or remove the project name and code based on the selected value.
				if ( $projectId.val() ) {
					$projectName.val( $projectId.text() );
					$projectCodePreview = $( '<div class="optimizely-project-code-preview code"></div>' );
					$projectCodePreview.text( '<script src="https://cdn.optimizely.com/js/'
					                          + parseInt( $projectId.val() )
					                          + '.js"></script>'
					);
					$projectCode.append( $projectCodePreview );
					$projectCode.removeClass( 'hidden' );
				} else {
					$projectName.val( '' );
					$projectCode.addClass( 'hidden' );
				}
			}
		};

		// Initialize the config page when ready.
		$( document ).ready( function () {
			OptimizelyConfig.init();
		} );
	}
)( jQuery, window, document );

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

var GrabPressTemplate;

// Avoid jQuery conflicts with other plugins
(function($) {

	/**
	 * Class for handling the GrabPress template form on the client side.
	 *
	 * @class GrabPressTemplate
	 * @constructor
	 */
	GrabPressTemplate = {
		// Define class properties
		allFormInputs: $( ':input' ),
		displayMessage: $( '#message p' ),
		defaultPlayerWidth: $( 'form input[name="width_orig"]' ).val(),
		playerWidthInput: $( '#player_width' ),
		modal300: $( '#dialog_300' ),
		modal640: $( '#dialog_640' ),

		/* initialization */
		init: function() {
			// Define vars
			var errorMessage = 'There was an error connecting to the API! Please try again later!',
					self = this,
					formAndInputs = $( ':input', 'form' ),
					templateForm = $( '.template-form' ),
					playerWidth = $( '#player_width' ),
					playerRatio = $( '#ratiowide' ),
					playerRatiostand = $( '#ratiostand' )
					
					
			;

			// If current message displayed is predetermined API connection error
			// message
			if( errorMessage === this.displayMessage ) {
				// Disable all form inputs
				this.allFormInputs.attr( 'disabled', 'disabled' );
			}

			// Configure default options for 400px message modal
			this.modal300.dialog({
				autoOpen: false,
				width: 400,
				modal: true,
				resizable: false,
				buttons: {
					'Continue': function() {
						$( this ).dialog( 'close' );
					}
				}
			});

			// Configure default options for 1200px message modal
			this.modal640.dialog({
				autoOpen: false,
				width: 400,
				modal: true,
				resizable: false,
				buttons: {
					'Continue': function() {
						$(this).dialog( 'close' );
					},
					'Preserve Native Size': function() {
						self.preserveNativeSize();
						$(this).dialog( 'close' );
					}
				}
			});

			// Update height in template preview
			this.updateHeightValue();

			// Whenever width or ratio changed validate width
			playerWidth.on( 'change', this.validateWidthValue );
			playerRatio.on( 'change', this.validateWidthValue );
			playerRatiostand.on( 'click', this.validateWidthValue );

			// When any change is made in the form or any of its inputs set the
			// display of confirm message to true
			formAndInputs.on( 'change', this.setConfirmUnload( true ) );

			// Once form is submitted, turn off display of confirm message
			templateForm.on( 'submit', this.setConfirmUnload( false ) );
		},

		/**
		 * Sets the width to 640px in the form and template preview
		 */
		preserveNativeSize: function() {
			this.playerWidthInput.value = 1200;
			this.updateHeightValue();
		},

		/**
		 * Resets the height in the form and the template preview
		 */
		resetHeight: function() {
			this.playerWidthInput.value = this.defaultPlayerWidth;
			this.updateHeightValue();
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
		 * Updates the height value and the template preview
		 */
		updateHeightValue: function() {
			// Define vars
			var height, standardHeight, widescreenWidth, marginLeft, marginTop,
					form = $( 'form' ),
					playerWidth = form.find( 'input[name="width"]' ).val(),
					playerRatio = form.find( 'input[name="ratio"]:checked' ).val(),
					templatePreview = $( '.template-preview' ),
					widescreenPreview = templatePreview.children( '.widescreen' ),
					standardPreview = templatePreview.children( '.standard' ),
					heightText = $( '.height' )
			;

			// Set template preview width to player width value
			templatePreview.width( playerWidth );

			// If widescreen 16:9 ratio selected
			if ( 'widescreen' === playerRatio ) {
				// Show widescreen preview, hide standard
				widescreenPreview.show();
				standardPreview.hide();

				// Calculate layout dimensions
				height =  ( playerWidth/16 ) * 9;
				height =   parseInt( height );
				widescreenWidth = ( playerWidth * 3 ) / 4;
				marginLeft = ( playerWidth - widescreenWidth ) / 2;

				// Apply dimensions to template preview CSS, along with some defaults
				widescreenPreview.css({
					width: widescreenWidth,
					height: height,
					'border-top': 'none',
					'border-bottom': 'none',
					'margin-left': marginLeft
				});
			} else { // Is standard 4:3 ratio
				// Show standard preview, hide widescreen
				standardPreview.show();
				widescreenPreview.hide();

				// Calculate layout dimensions
				height = ( playerWidth / 4 ) * 3;
				height = parseInt( height );
				standardHeight = ( height * 3 ) / 4;
				marginTop = ( height - standardHeight ) / 2;

				// Apply dimensions to template preview CSS, along with some defaults
				standardPreview.css({
					width: playerWidth,
					height: standardHeight,
					'border-left': 'none',
					'border-right': 'none',
					'margin-top': marginTop
				});
			}

			// Set height text and preview height
			heightText.text( height );
			templatePreview.height( height );
		},

		/**
		 * Checks width to make sure it is within a good range for current aspect
		 * ratio
		 * @return {Boolean} Width is a valid number, returned only if NaN detected
		 */
		validateWidthValue: function() {
			// Define vars
			var message, dimensionText,
					form = $( 'form' ),
					playerWidth = form.find( 'input[name="width"]' ).val(),
					playerRatio = form.find( 'input[name="ratio"]:checked' ).val(),
					savebutton  =  $('#btn-create-feed')
			;

			// If player width is NaN
			if ( isNaN( playerWidth ) ) {
				// Create alert popup
				alert( 'Please enter a numeric value!' );

				// Reset width to default
				this.resetHeight();

				// Prevent browser default behavior
				return false;
			}

			// If player width less than 400px (min)
			if ( playerWidth < 400  ) {
				// Set message text
				message = 'The minimum width for a Grab Video Player is 400px wide.';
				alert(message);
				savebutton.attr( 'disabled', 'disabled' );
				return false;
			}
			else{
			savebutton.removeAttr( 'disabled' )
			}

			// If player width greater than 640px (soft-max)
			if ( playerWidth > 1200 ) {
				// If widescreen ratio selected
				if ( 'widescreen' === playerRatio ) {
					// Set dimension text w/ 360px height
					dimensionText = '(1200px x 480px)';
				} else {
					// Set dimension text w/ 480px height
					dimensionText = '(1200px x 480px)';
				}

				// Set message text using dynamic dimension text
				message = 'Creating an embed larger than the video\'s native size ' + dimensionText + ' may result in pixelated video.';
				alert(message);
				savebutton.attr( 'disabled', 'disabled' );
				return false;
			}
			else{
			savebutton.removeAttr( 'disabled' )
			}

			// Update height in template preview
			GrabPressTemplate.updateHeightValue();
		}
	}; // End GrabPressTemplate

	// Document ready
	$(function() {
		// Init GrabPressTemplate class
		GrabPressTemplate.init();
	});

})(jQuery); // End $ scope
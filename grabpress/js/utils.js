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

var GrabPressUtils;

// Avoid jQuery conflicts with other plugins
(function($) {

	/**
	 * Class for containing utility functions for use by GrabPress.
	 *
	 * @class GrabPressUtils
	 * @constructor
	 */
	GrabPressUtils = {
		/**
		 * Checks whether the current browser is Chrome
		 * @return {Boolean} The browser is Chrome
		 */
		browserIsChrome: function() {
			var isChrome = !!window.chrome && !!window.chrome.webstore;
			return isChrome;
		},

		/**
		 * Checks whether the current browser is Internet Explorer
		 * @return {Boolean} The browser is IE
		 */
		browserIsIE: function() {
			// Define vars
			var version = this.getIEVersion();

			// If version is above -1
			if ( -1 < version ) {
				// The browser is IE
				return true;
			}

			// Else it is not IE
			return false;
		},

		/**
		 * Checks whether the current browser is Opera
		 * @return {Boolean} The browser is Opera
		 */
		browserIsOpera: function() {
			var isOpera = window.opera && window.opera.version() == X;
			return isOpera;
		},

		/**
		 * Checks whether the current browser is Safari
		 * @return {Boolean} The browser is Safari
		 */
		browserIsSafari: function() {
			var isSafari = /Constructor/.test( window.HTMLElement );
			return isSafari;
		},

		/**
		 * Returns the version of Internet Explorer or a -1 (indicating the use of
		 * another browser).
		 * @return {Integer} Version of the browser or a -1 if not IE
		 */
		getIEVersion: function() {
			// Define vars
			var version = -1, // This return value assumes failure
					userAgent = ''
			;

			// If navigator app name indicates IE
			if ( 'Microsoft Internet Explorer' === navigator.appName ) {
				// Get useragent string
				userAgent = navigator.userAgent;

				// If match found for IE using regex
				if ( userAgent.match(/MSIE ([0-9]{1,}[\.0-9]{0,})/) ) {
					// Parse version as float, i.e. 10.0
					var ver	= userAgent.match(/MSIE ([0-9]{1,}[\.0-9]{0,})/);
					version = parseFloat( ver[0].replace( 'MSIE ', '' ) );
				}
			}

			// Return version
			return version;
		}
	}; // End GrabPressUtils

})(jQuery); // End $ scope
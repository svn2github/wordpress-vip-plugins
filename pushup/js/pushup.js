( function( window, settings, undefined ) {

	"use strict";
	var document = window.document;

	/**
	 * An internal cache used to store settings for our web service.
	 *
	 * @type {{websitePushID: string, webServiceURL: string, userID: int, domain: string}}
	 */
	var Cache = {
		websitePushID : '',
		webServiceURL : '',
		userID : -1,
		domain : ''
	};

	/**
	 * Handles checking safari for permissions to receive push notifications.
	 *
	 * @param permissionData
	 */
	function checkRemotePermission( permissionData ) {
		var userInfo = {
			userID : Cache.userID,
			domain : Cache.domain
		};

		if ( permissionData.permission === 'default' ) {
			window.safari.pushNotification.requestPermission( Cache.webServiceURL, Cache.websitePushID, userInfo, checkRemotePermission );
		} else if ( permissionData.permission === 'denied' ) {
			// The user said no.
		} else if ( permissionData.permission === 'granted' ) {
			// The web service URL is a valid push provider, and the user said yes.
			// `permissionData.deviceToken` is now available to use.
		}
	}

	/**
	 * Handles initializing our library and ensuring that things are properly setup.
	 */
	function initialize() {
		Cache.userID = settings.userID;
		Cache.domain = settings.domain;
		Cache.websitePushID = settings.websitePushID;
		Cache.webServiceURL = settings.webServiceURL;

		// Ensure that the user can receive Safari Push Notifications.
		if ( 'safari' in window && 'pushNotification' in window.safari ) {
			var permissionData = window.safari.pushNotification.permission( Cache.websitePushID );

			checkRemotePermission( permissionData );
		}
	}

	// since our file is included in the footer, we know it's safe to call initialize() when we're ready.
	initialize();

} )( window, PushUpNotificationSettings );
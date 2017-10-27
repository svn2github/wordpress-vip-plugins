<?php

// VIP Temporary allow subdomain redirects with proper logging to
// ensure that follow-up is performed to have the subdomains added to client
// themes. Also log domains that aren't white-listed.
function wpcom_legacy_redirector_allow_subdomain( $hosts, $loc_host ) {
	// Bail if location already in allowed hosts
	if ( ! in_array( $loc_host, $hosts, true ) ) {
		$http_host = wpcom_vip_get_home_host();
		$prefixed_http_host = '.' . $http_host;

		// verify location is a subdomain of primary host
		if ( wp_endswith( $loc_host, $prefixed_http_host ) ) {
			$hosts[] = $loc_host;

			// Log Warning for not white listed sub domain
			trigger_error( 'wpcom-legacy-redirector: subdomain ( ' . $loc_host . ' ) of site is not whitelisted in `allowed_redirect_hosts` for ' . $http_host . '; temporarily allowing redirect.', E_USER_WARNING );
		} else {
			// Log Warning for not white listed domains
			trigger_error( 'wpcom-legacy-redirector: domain ( ' . $loc_host . ' ) is not whitelisted in `allowed_redirect_hosts` for ' . $http_host . '; redirect blocked.', E_USER_WARNING );
		}
	}
	return $hosts;
}
add_filter( 'allowed_redirect_hosts', 'wpcom_legacy_redirector_allow_subdomain', 9999, 2 );

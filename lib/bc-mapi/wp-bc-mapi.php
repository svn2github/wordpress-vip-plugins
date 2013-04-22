<?php

require_once( __DIR__ . '/bc-mapi.php' );

class WP_BCMAPI extends BCMAPI {

	/**
	 * Makes a cURL request.
	 * @access Private
	 * @since 1.0.0
	 * @param mixed [$request] URL to fetch or the data to send via POST
	 * @param boolean [$get_request] If false, send POST params
	 * @return void
	 */
	protected function curlRequest( $request, $get_request = FALSE ) {
		if ( $get_request ) {
			$response = wp_remote_get( $request );
		} else {
			$url = $this->getUrl('write');
			
			$response = wp_remote_post( $url, array( 'body' => $request ) );
		}

		$this->api_calls++;

		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();

			if ( $get_request ) {
				throw new BCMAPITransactionError( $this, self::ERROR_READ_API_TRANSACTION_FAILED, $error );
			} else {
				throw new BCMAPITransactionError( $this, self::ERROR_WRITE_API_TRANSACTION_FAILED, $error );
			}
		}

		return $this->bit32clean( wp_remote_retrieve_body( $response ) );
	}
}

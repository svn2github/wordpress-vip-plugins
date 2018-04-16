<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Uninstall Hooks
 *
 * @since     1.0.0
 * @package   StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
class StackCommerce_WP_Maintenance {

	/**
	 * Perform activation tasks
	 *
	 * @since    1.0.0
	 */
	public function activation() {
		global $pagenow;
		$connection_status = get_option( 'stackcommerce_wp_connection_status' );

		if ( 'plugins.php' === $pagenow && 'connected' !== $connection_status ) {
			add_action( 'admin_notices', array( $this, 'notice' ) );
		}

		$this->add_endpoint();
	}

	/**
	 * Perform deactivation tasks
	 *
	 * @since    1.0.0
	 */
	public function deactivate() {
		if ( current_user_can( 'activate_plugins' ) ) {
			self::notify();
			self::disconnect();

			flush_rewrite_rules();
		} else {
			return;
		}
	}

	/**
	 * Perform tasks on plugin activation
	 *
	 * @since    1.0.0
	 */
	protected function setup() {
		add_action( 'admin_notices', array( $this, 'activate_notice' ) );
	}

	/**
	* Create a rewrite rule for our API
	*
	* @since    1.6.5
	*/
	protected function add_endpoint() {
		add_rewrite_rule(
			'^stackcommerce-connect/v([1])/([\w]*)?',
			'index.php?sc-api-version=$matches[1]&sc-api-route=$matches[2]',
			'top'
		);

		flush_rewrite_rules();
	}

	/**
	 * Trigger a success activation notice
	 *
	 * @since    1.0.0
	 */
	public function notice() {
		require_once( dirname( dirname( __FILE__ ) ) . '/views/stackcommerce-wp-activate.php' );
	}

	/**
	 * Notify API to disconnect
	 *
	 * @since    1.3.0
	 */
	protected function notify() {
		$account_id   = get_option( 'stackcommerce_wp_account_id' );
		$secret       = get_option( 'stackcommerce_wp_secret' );
		$api_endpoint = SCWP_CMS_API_ENDPOINT . '/api/wordpress/?id=' . $account_id . '&secret=' . $secret;

		$data = wp_json_encode( array(
			'data' => [
				'type'       => 'partner_wordpress_settings',
				'id'         => $account_id,
				'attributes' => [
					'installed' => false,
				],
			],
		) );

		wp_remote_post( $api_endpoint, array(
			'method'  => 'PUT',
			'timeout' => 15,
			'headers' => array(
				'Content-Type' => 'application/json; charset=utf-8',
			),
			'body'    => $data,
		) );
	}

	/**
	* Clean up fields created by the plugin
	*
	* @since    1.0.0
	*/
	protected function disconnect() {
		return update_option( 'stackcommerce_wp_connection_status', 'disconnected' );
	}
}

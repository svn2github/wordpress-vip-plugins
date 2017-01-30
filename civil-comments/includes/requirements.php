<?php
/**
 * Check for required PHP and WordPress versions.
 *
 * @package Civil_Comments
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check plugin requirements class.
 */
class Civil_Requirements_Check {

	/**
	 * File path of main plugin file.
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Plugin name for error messages.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Minimum PHP Version.
	 *
	 * @var string
	 */
	public $min_php_version;

	/**
	 * Minimum WP Version.
	 *
	 * @var string
	 */
	public $min_wp_version;

	/**
	 * Compatibility errors
	 *
	 * @var WP_Error
	 */
	public $errors;

	/**
	 * Constructor.
	 *
	 * @param string $file            Plugin file path.
	 * @param string $name            Plugin name.
	 * @param string $min_php_version Minimum PHP version required.
	 * @param string $min_wp_version  Minimum WP version required.
	 */
	public function __construct( $file, $name, $min_php_version, $min_wp_version ) {
		$this->file = $file;
		$this->name = $name;
		$this->min_php_version = $min_php_version;
		$this->min_wp_version = $min_wp_version;
		$this->errors = new WP_Error();
	}

	/**
	 * Check for valid dependencies.
	 *
	 * @return bool
	 */
	public function check() {
		$this->meets_minimum_php_version();
		$this->meets_minimum_wp_version();

		if ( $this->has_errors() ) {
			unset( $_GET['activate'] ); // Input var okay.
			$this->deactivate();
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'network_admin_notices', array( $this, 'admin_notices' ) );
			return false;
		}

		return true;
	}

	/**
	 * Check for valid minimum PHP version.
	 */
	public function meets_minimum_php_version() {
		if ( version_compare( PHP_VERSION, $this->min_php_version, '<' ) ) {
			$this->errors->add( 'php_version', sprintf(
				__( '%1$s requires PHP %2$s or later. You are currently running version %3$s.', 'civil-comments' ),
				$this->name,
				$this->min_php_version,
				PHP_VERSION
			) );
		}
	}

	/**
	 * Check for valid minimum WP version.
	 */
	public function meets_minimum_wp_version() {
		global $wp_version;
		if ( version_compare( $wp_version, $this->min_wp_version, '<' ) ) {
			$this->errors->add( 'wp_version', sprintf(
				__( '%1$s requires WordPress %2$s or later. You are currently running version %3$s.', 'civil-comments' ),
				$this->name,
				$this->min_wp_version,
				$wp_version
			) );
		}
	}

	/**
	 * Deactivate the plugin.
	 */
	function deactivate() {
		// If on WP.com VIP, do not attempt to deactivate.
		if ( defined( 'WPCOM_IS_VIP_ENV' ) && true === WPCOM_IS_VIP_ENV ) {
			return;
		}

		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->file );
		}
	}

	/**
	 * Check for errors.
	 *
	 * @return boolean
	 */
	public function has_errors() {
		return (bool) count( $this->errors->errors );
	}

	/**
	 * Output admin notices.
	 */
	public function admin_notices() {
		?>
		<div class="error">
		<?php foreach ( $this->errors->get_error_messages() as $msg ) { ?>
			<p><?php echo esc_html( $msg ); ?></p>
		<?php } ?>
			<p>
			<?php
			printf(
				wp_kses(
					__( 'The <strong>%s</strong> plugin has been deactivated.</p>', 'civil-comments' ),
					array( 'strong' )
				),
				esc_html( $this->name )
			); ?>
			</p>
		</div>
		<?php
	}
}

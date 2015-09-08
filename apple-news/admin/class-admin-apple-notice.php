<?php
/**
 * This class manages admin notices.
 *
 * @since 0.6.0
 */
class Admin_Apple_Notice {

	/**
	 * Key for admin notices.
	 *
	 * @access public
	 */
	const KEY = 'apple_news_notice';

	/**
	 * Constructor.
	 */
	function __construct() {
		add_action( 'admin_notices', array( $this, 'show' ) );
	}

	/**
	 * Add a notice message to be displayed.
	 *
	 * @param string $message
	 * @param string $type
	 * @static
	 * @access public
	 */
	public static function message( $message, $type ) {
		update_user_meta( get_current_user_id(), self::KEY, array(
			'message' => sanitize_text_field( $message ),
			'type' => sanitize_text_field( $type )
		) );
	}

	/**
	 * Add an info message.
	 *
	 * @param string $message
	 * @static
	 * @access public
	 */
	public static function info( $message ) {
		self::message( $message, 'updated' );
	}

	/**
	 * Add a success message.
	 *
	 * @param string $message
	 * @static
	 * @access public
	 */
	public static function success( $message ) {
		self::message( $message, 'updated' );
	}

	/**
	 * Add an error message.
	 *
	 * @param string $message
	 * @static
	 * @access public
	 */
	public static function error( $message ) {
		self::message( $message, 'error' );
	}

	/**
	 * Check if a notice exists.
	 *
	 * @param string $message
	 * @param string $type
	 * @static
	 * @access public
	 */
	public static function has_notice() {
		return ! empty( get_user_meta( get_current_user_id(), self::KEY . 'message', true ) );
	}

	/**
	 * Show the admin notice.
	 *
	 * @static
	 * @access public
	 */
	public static function show() {
		// Only execute on Apple admin pages
		$current_screen = get_current_screen();
		if ( false === stripos( $current_screen->base, '_apple' ) ) {
			return;
		}

		// Check for notices
		$notice = get_user_meta( get_current_user_id(), self::KEY, true );
		if ( empty( $notice['message'] ) ) {
			return;
		}

		// Show the notice
		$type = isset( $notice['type'] ) ? $notice['type'] : 'updated';
		self::show_notice( $notice['message'], $type );

		// Clear the notice
		delete_user_meta( get_current_user_id(), self::KEY );
	}

	/**
	 * Display the admin notice template.
	 *
	 * @param string $message
	 * @param string $type
	 * @static
	 * @access private
	 */
	private static function show_notice( $message, $type ) {
		?>
		<div class="notice <?php echo esc_attr( $type ) ?> is-dismissible">
			<p><strong><?php echo wp_kses_post( apply_filters( 'apple_news_notice_message', $message, $type ) ) ?></strong></p>
		</div>
		<?php
	}

}

<?php
/**
 * Template for displaying the note about using shortcode for my account
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>
<?php if ( $this::$tinypass->myAccountAvailable() ): ?>
	<h3><?php esc_html_e( 'My account shortcode', 'tinypass' ) ?></h3>
	<div class="postbox">
		<div class="inside">
			<p><?php echo sprintf( __( 'Use <code>%s</code> shortcode to display "My account" module.', 'tinypass' ), '[' . $this::WP_SHORTCODE_MY_ACCOUNT . ']' ) ?></p>
		</div>
	</div>
<?php endif ?>
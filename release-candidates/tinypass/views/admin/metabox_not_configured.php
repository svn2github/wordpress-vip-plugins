<?php
/**
 * Template for metabox when tinypass was not configured or was configured properly
 * Rendered by @see WPTinypassAdmin
 */
?>
<p><?php echo wp_kses_post( sprintf( __( 'Tinypass is not configured. Click %s to go to Tinypass plugin configuration page.', 'tinypass' ),
		'<a href="' . esc_url( admin_url( 'admin.php?page=' . $this::SETTINGS_PAGE_SLUG ) ) . '">' . __( 'here', 'tinypass' ) . '</a>' ) ) ?>
</p>
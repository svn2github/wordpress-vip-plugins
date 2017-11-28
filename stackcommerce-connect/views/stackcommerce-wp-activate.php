<?php
if ( ! defined( 'ABSPATH' ) ) {
  die( 'Access denied.' );
}
?>

<div class="notice notice-success is-dismissible">
  <p><b><?php echo esc_html( SCWP_NAME ); ?></b> has been activated. Visit the <a href="<?php echo admin_url( esc_url( 'admin.php?page=stackcommerce_wp_page_general_settings' ) ); ?>">General Settings</a> page to complete the plugin setup.</p>
</div>

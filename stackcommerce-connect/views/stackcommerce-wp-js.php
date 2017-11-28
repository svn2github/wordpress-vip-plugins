<?php
$scwp_account_id = get_option( 'stackcommerce_wp_account_id' );
$scwp_connection_status = ( get_option( 'stackcommerce_wp_connection_status' ) === 'connected' ) ? true : false;

if ( $scwp_connection_status ):
?>
<script>
  (function (s,o,n,a,r,i,z,e) {s['StackSonarObject']=r;s[r]=s[r]||function(){
  (s[r].q=s[r].q||[]).push(arguments)},s[r].l=1*new Date();i=o.createElement(n),
  z=o.getElementsByTagName(n)[0];i.async=1;i.src=a;z.parentNode.insertBefore(i,z)
  })(window,document,'script','https://www.stack-sonar.com/ping.js','stackSonar');

  stackSonar('stack-connect-wp', <?php echo wp_json_encode( $scwp_account_id ); ?>);
</script>
<?php endif; ?>

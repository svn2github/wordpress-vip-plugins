<?php
/* Check to see if everything is set up correctly */
$verify_setup = sailthru_verify_setup();
?>

<?php if ( isset( $verify_setup['errormessage'] ) && $verify_setup['errormessage'] == 'select a template' ) : ?>
  <h2>Almost there, just one more step</h2>
  <?php
    //create_default_template();
    settings_fields( 'sailthru_setup_options' );
    do_settings_sections( 'sailthru_setup_options' );
  ?>
<?php else: ?>
  <!-- Welcome Screen for configured users -->
  <div id="dashboard-widgets-wrap wrap">
    <div id="sailthru-template-choices" class="metabox-holder columns-2">
      <div class="welcome-panel" id="welcome-screen-header">
          Smart Data
      </div>
      <div class="row options">
          <div class="option-box">
              <h3>Sailthru Documentation</h3>
                <ul>
                    <li><a href="http://docs.sailthru.com/">Documentation</a></li>
                    <li><a href="http://docs.sailthru.com/wordpress-vip-plugin">WordPress Documentation</a></li>
                    <li><a href="http://docs.sailthru.com/documentation/products/scout">Scout Documentation</a></li>
                    <li><a href="http://docs.sailthru.com/documentation/products/concierge">Concierge Documentation</a></li>
          </div>

          <div class="option-box" id="docs">
              <h3>Quick Links</h3>
                <ul>
                    <li><a href="https://my.sailthru.com/">Analytics</a></li>
                    <li><a href="https://my.sailthru.com/blasts">Communications</a></li>
                    <li><a href="https://my.sailthru.com/lists">Users</a></li>
                    <li><a href="https://my.sailthru.com/content">Content</a></li>
          </div>

          <div class="option-box" id="support">
              <h3>Support</h3>
                <p>If you have any immediate questions, please don't hesitate to contact us at
        877-812-8689  or <a href="mailto:support@sailthru.com">support@sailthru.com</a></p>
<p>For support, dial ext. 1 <br />
For sales, dial ext. 2</p>
          </div>
      </div>
    </div>
  </div>
<script type="text/javascript">
jQuery(document).ready( function() {

  jQuery("#submit").hide();

});
</script>
<?php endif;


<div class="wrap" id="wp-graphiq-search">
  <h2><?php esc_html_e( 'Graphiq Search', 'graphiq-search' ); ?></h2>
  <p style="width:600px;"><?php
		esc_html_e( 'Premium partners can enter their API key below. This will give you priority access to new features and the ability to request custom visuals from our team of graphics specialists. Interested in becoming a premium partner?', 'graphiq-search' );
    ?> <a href="https://www.graphiq.com/journalist-solutions" target="_blank"><?php esc_html_e( 'Learn more about our Journalist Solutions', 'graphiq-search' ); ?></a>
  </p>

  <form action="options.php" method="post" class="settings-form">
    <?php settings_fields( 'graphiq_search_options' ); ?>
    <?php do_settings_sections( 'graphiq-search-options' ); ?>
    <?php submit_button(); ?>
  </form>

</div>

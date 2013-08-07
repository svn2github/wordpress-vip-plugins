	<div id="icon-sailthru" class="icon32"></div>
	<h2><?php _e( 'Sailthru Subscribe', 'sailthru-for-wordpress' ); ?></h2>

	<?php

		$sailthru = get_option('sailthru_setup_options');

		if( !is_array($sailthru) )
		{

			echo '<p>Please return to the <a href="' . esc_url( menu_page_url( 'sailthru_configuration_menu', false ) ) . '">Sailthru Settings screen</a> and set up your API key and secret before setting up this widget.</p>';
			return;

		}
		$api_key = $sailthru['sailthru_api_key'];
		$api_secret = $sailthru['sailthru_api_secret'];

		//$client = new Sailthru_Client( $api_key, $api_secret );
		$client = new WP_Sailthru_Client( $api_key, $api_secret);
			try {
				if ($client) {
					$res = $client->getLists();
				}
			}
			catch (Sailthru_Client_Exception $e) {
				//silently fail
				return;
			}
		
			
			$lists = $res['lists'];

	?>

        
        <div id="<?php echo $this->get_field_id('title'); ?>_div" style="display: block;">
            <p>
            	<label for="<?php echo $this->get_field_id('title'); ?>">
            		<?php _e('Widget Title:'); ?> 
            		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            	</label>
            </p>
			<p>
				<label for="<?php echo $this->get_field_id('show_first_name'); ?>">
					<?php _e('Show first name field:'); ?> 
					<input id="<?php echo $this->get_field_id('show_first_name'); ?>" name="<?php echo $this->get_field_name('show_first_name'); ?>" type="checkbox" <?php echo (($show_first_name) ? ' checked' : ''); ?> /> Yes.
				</label>
			</p> 
			<p>
				<label for="<?php echo $this->get_field_id('show_last_name'); ?>">
					<?php _e('Show last name field:'); ?> 
					<input id="<?php echo $this->get_field_id('show_last_name'); ?>" name="<?php echo $this->get_field_name('show_last_name'); ?>" type="checkbox" <?php echo (($show_last_name) ? ' checked' : ''); ?> /> Yes.
				</label>
			</p>

			<p>			
				<?php _e('Subscribe to list(s): '); ?>
				<?php
					foreach( $lists as $key => $list )
					{ 
						?>
						<br />
						<input type="checkbox" value="<?php echo esc_attr( $list['name'] ); ?>" name="<?php echo $this->get_field_name('sailthru_list'); ?>[<?php echo $key; ?>]" id="<?php echo esc_attr( $this->get_field_id('sailthru_list') . '-' . $key ); ?>" <?php checked($instance['sailthru_list'][$key], $list['name']); ?>  /> 
						<label for="<?php // echo esc_attr( $this->get_field_id('sailthru_list') . '-' . $key ); ?>"><?php echo esc_html( $list['name'] ); ?></label>
						<?php
					}
				?>
			</p>						
        </div>



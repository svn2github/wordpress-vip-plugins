<?php

function vs_display_text_field($value, $setting, $args){
	?>
	<input name="<?php echo esc_attr( $setting->get_field_name() ); ?>" id="<?php echo esc_attr( $setting->get_field_id() ) ?>" value="<?php echo esc_attr($value) ?>" class="regular-text" type="text">
	<?php if(!empty($args['description'])) : ?>
		<br/><span class="description"><?php echo wp_kses_post( $args['description'] ); ?></span>
	<?php endif;
}

function vs_display_dropdown($value, $setting, $args) {
	if(!isset($args['options'])) {
		?>
		<p class="error">An options argument is required in the $args array to use vs_display_dropdown()</p>
		<?php
		return;
	} else {
		?>
		<select id="<?php echo esc_attr( $setting->get_field_id() ); ?>" name="<?php echo esc_attr( $setting->get_field_name() ) ?>">
			<?php
			foreach( $args['options'] as $option_value => $option_text ) {
				echo sprintf( "<option value='%s' %s>%s</option>", esc_attr( $option_value ), selected( $option_value, $value, false ), esc_html( $option_text ) );
			}
			?>
		</select>
		<?php if(!empty($args['description'])) : ?>
			<br/><span class="description"><?php echo wp_kses_post( $args['description'] ); ?></span>
		<?php endif;
	}
}

function vs_display_textarea($value, $setting, $args) {
	?>
	<textarea id="<?php echo esc_attr( $setting->get_field_id() ); ?>" name="<?php echo esc_attr( $setting->get_field_name() ) ?>" rows='7' cols='50' type='textarea'><?php echo esc_html($value) ?></textarea>
	<?php if(!empty($args['description'])) : ?>
		<br/><span class="description"><?php echo wp_kses_post( $args['description'] ); ?></span>
	<?php endif; ?>
	<?php
}

function vs_display_checkbox($value, $setting, $args) {
	$value = in_array($value, array('on', true), true);
	?>
	<input type="checkbox" id="<?php echo esc_attr( $setting->get_field_id() ); ?>" name="<?php echo esc_attr( $setting->get_field_name() ) ?>" <?php checked( $value ) ?> />
	<?php if(!empty($args['description'])) : ?>
		<br/><span class="description"><?php echo wp_kses_post( $args['description'] ); ?></span>
	<?php endif;
}

<input id="zemanta_options_<?php echo esc_attr( $field ); ?>" name="zemanta_options[<?php echo esc_attr( $field ); ?>]" size="40" type="text" value="<?php echo isset($option) && !empty($option) ? esc_attr( $option ) : (isset($default_value) ? esc_attr( $default_value ) : ''); ?>" <?php disabled( $disabled ); ?> />

<?php if (isset($description)): ?>

  <span class="description">
    <?php echo $description; ?>
  </span>

<?php endif; ?>
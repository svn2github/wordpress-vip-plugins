<?php if(isset($vars['title'])): ?>
	<label class="option-label" for="graphiq_search_options_<?php echo esc_attr( $vars['field'] ); ?>"><?php echo esc_html( $vars['title'] ); ?></label>
<?php endif; ?>

	<input id="graphiq_search_options_<?php echo esc_attr( $vars['field'] ); ?>" class="regular-text code" name="graphiq_search_options[<?php echo esc_attr( $vars['field'] ); ?>]" size="40" type="text" value="<?php echo isset($vars['option']) && !empty($vars['option']) ? esc_attr( $vars['option'] ) : (isset($vars['default_value']) ? esc_attr( $vars['default_value'] ) : ''); ?>" <?php disabled( $vars['disabled'] ); ?>  />

<?php if (isset($vars['description'])): ?>

	<p class="description"><?php echo wp_kses_post( $vars['description'] ); ?></p>

<?php endif; ?>

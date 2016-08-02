<amp-iframe src="<?php echo esc_attr( $vars['url'] ); ?>" width="<?php echo esc_attr( $vars['width'] ); ?>" height="<?php echo esc_attr( $vars['height'] ); ?>" layout="responsive" resizable sandbox="allow-scripts allow-same-origin allow-popups" frameborder="0" scrolling="no" class="graphiq-amp-iframe">
<?php if ( ! empty( $vars['title'] ) ) : ?>
	<div overflow tabindex=0 role=button aria-label="<?php echo esc_attr( $vars['title'] ); ?>" placeholder><?php echo esc_html( $vars['title'] ); ?></div>
<?php endif; ?>
</amp-iframe>
<?php if ( ! empty( $vars['link'] ) && ! empty( $vars['link_text'] ) ) : ?>
	<div class="graphiq-amp-link"><a target="_blank" href="<?php echo esc_url( $vars['link'] ); ?>"><?php echo esc_html( $vars['link_text'] ); ?></a></div>
<?php endif; ?>
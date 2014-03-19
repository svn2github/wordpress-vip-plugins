<div style="width: <?php echo intval( $arguments[ 'width' ] ); ?>px; margin: 0 auto;">
	<iframe width="<?php echo intval( $arguments[ 'width' ] ); ?>" height="<?php echo intval( $arguments[ 'height' ] ); ?>" frameborder="0" scrolling="no" style="vertical-align:top;" src="<?php echo esc_url( $arguments[ 'url' ] ); ?>"></iframe>
	<div style="text-align: center;">
		<a target="_blank" href="<?php echo esc_url( $arguments[ 'link' ] ); ?>" style="font: 10px/14px arial; color:#3d3d3d;"><?php echo esc_html( $arguments[ 'name' ] ); ?></a>
	</div>
</div>
<br />

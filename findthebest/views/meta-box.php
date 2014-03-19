<div id="ftb-sidebar">
	<div class="nav-bar">
		<div class="static-bar">
			<div class="search-bar">
				<a class="button" id="ftb-search" title="<?php esc_attr_e( 'Search for content', 'findthebest' ); ?>"><img src="<?php echo esc_url( $arguments[ 'image_dir' ] ); ?>search.svg" class="button-image svg"><img src="<?php echo esc_url( $arguments[ 'image_dir' ] ); ?>search.png" class="button-image no-svg"></a><div id="ftb-term-container"><input type="text" id="ftb-term" placeholder="<?php esc_attr_e( 'Ski resorts, laptops, cars&hellip;', 'findthebest' ); ?>"></div>
			</div>
			<a class="button" id="ftb-edit" style="display: none;"><?php esc_html_e( 'Edit Selected Widget', 'findthebest' ); ?></a>
		</div>
	</div>
	<div id="ftb-content">
		<div id="ftb-info"><?php esc_html_e( 'Search for a topic in the box above and FindTheBest will suggest interactive content that is relevant.', 'findthebest' ); ?></div>
		<div id="ftb-no-results"></div>
		<div id="ftb-loading">
			<div class="load">
				<img src="<?php echo esc_url( $arguments[ 'image_dir' ] ); ?>load.gif">
				<br>
				<?php esc_html_e( 'Loading related content', 'findthebest' ); ?>
			</div>
		</div>
		<div id="ftb-suggestions"></div>
	</div>
</div>

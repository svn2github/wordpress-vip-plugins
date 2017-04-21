<?php $current_screen = get_current_screen(); ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Apple News', 'apple-news' ) ?></h1>
	<form method="get">
		<?php do_action( 'apple_news_before_index_table' ); ?>
		<?php if ( ! empty( $current_screen->parent_base ) ): ?>
		<input type="hidden" name="page" value="<?php echo esc_attr( $current_screen->parent_base ) ?>">
		<?php endif; ?>
		<?php
			$table->search_box( __( 'Search', 'apple-news' ), 'apple-news-search' );
			$table->display();
		?>
		<?php do_action( 'apple_news_after_index_table' ); ?>
	</form>
</div>

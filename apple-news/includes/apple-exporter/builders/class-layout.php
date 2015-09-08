<?php
namespace Apple_Exporter\Builders;

use \Apple_Exporter\Exporter as Exporter;

/**
 * Manage the article layout.
 *
 * @since 0.4.0
 */
class Layout extends Builder {

	/**
	 * Build the layout
	 *
	 * @return array
	 * @access protected
	 */
	protected function build() {
		return apply_filters( 'apple_news_layout', array(
			'columns' => $this->get_setting( 'layout_columns' ),
			'width'   => $this->get_setting( 'layout_width' ),
			'margin'  => $this->get_setting( 'layout_margin' ),  // Defaults to 100
			'gutter'  => $this->get_setting( 'layout_gutter' ),  // Defaults to 20
		), $this->content_id() );
	}

}

<?php
namespace Apple_Exporter\Builders;

/**
 * @since 0.4.0
 */
class Advertising_Settings extends Builder {

	/**
	 * Build the component.
	 *
	 * @param string $text
	 * @access protected
	 */
	protected function build() {
		$advertising_settings = array();

		// Get advertising settings
		$enable_advertisement = $this->get_setting( 'enable_advertisement' );
		$ad_frequency = intval( $this->get_setting( 'ad_frequency' ) );

		if ( 'yes' === $enable_advertisement && $ad_frequency > 0 ) {
			$advertising_settings['frequency'] = $ad_frequency;
			$ad_margin = intval( $this->get_setting( 'ad_margin' ) );
			if ( ! empty( $ad_margin ) ) {
				$advertising_settings['layout'] = array(
					'margin' => array(
						'top' => $ad_margin,
						'bottom' => $ad_margin,
					),
				);
			}
		}

		return apply_filters( 'apple_news_advertising_settings', $advertising_settings, $this->content_id() );
	}
}

<?php
namespace Apple_Exporter\Builders;

/**
 * @since 0.4.0
 */
class Metadata extends Builder {

	/**
	 * Build the component.
	 *
	 * @param string $text
	 * @access protected
	 */
	protected function build() {
		$meta = array();

		// The content's intro is optional. In WordPress, it's a post's
		// excerpt. It's an introduction to the article.
		if ( $this->content_intro() ) {
			$meta['excerpt'] = $this->content_intro();
		}

		// If the content has a cover, use it as thumb.
		if ( $this->content_cover() ) {
			$filename  = basename( $this->content_cover() );
			$thumb_url = 'bundle://' . $filename;
			$meta['thumbnailURL'] = $thumb_url;
		}

		return apply_filters( 'apple_news_metadata', $meta, $this->content_id() );
	}

}

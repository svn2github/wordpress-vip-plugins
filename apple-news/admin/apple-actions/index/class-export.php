<?php

namespace Apple_Actions\Index;

require_once plugin_dir_path( __FILE__ ) . '../class-action.php';
require_once plugin_dir_path( __FILE__ ) . '../../../includes/apple-exporter/autoload.php';

use Apple_Actions\Action as Action;
use Apple_Exporter\Exporter as Exporter;
use Apple_Exporter\Exporter_Content as Exporter_Content;
use Apple_Exporter\Exporter_Content_Settings as Exporter_Content_Settings;

class Export extends Action {

	/**
	 * ID of the post being exported.
	 *
	 * @var int
	 * @access private
	 */
	private $id;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings
	 * @param int $id
	 */
	function __construct( $settings, $id ) {
		parent::__construct( $settings );
		$this->id = $id;
	}

	/**
	 * Perform the export and return the results.
	 *
	 * @return string The JSON data
	 * @access public
	 */
	public function perform() {
		$exporter = $this->fetch_exporter();
		return $exporter->export();
	}

	/**
	 * Fetches an instance of Exporter.
	 *
	 * @return Exporter
	 * @access public
	 */
	public function fetch_exporter() {

		do_action( 'apple_news_do_fetch_exporter', $this->id );

		// Fetch WP_Post object, and all required post information to fill up the
		// Exporter_Content instance.
		$post       = get_post( $this->id );
		$post_thumb = wp_get_attachment_url( get_post_thumbnail_id( $this->id ) ) ?: null;
		$author     = get_the_author_meta( 'display_name', $post->post_author );
		$date       = date( 'M j, Y | g:i A', strtotime( $post->post_date ) );
		$byline     = 'by ' . ucfirst( $author ) . ' | ' . $date ;

		// Filter each of our items before passing into the exporter class.
		$title      = apply_filters( 'apple_news_exporter_title', $post->post_title, $post->ID );
		$excerpt    = apply_filters( 'apple_news_exporter_excerpt', $post->post_excerpt, $post->ID );
		$post_thumb = apply_filters( 'apple_news_exporter_post_thumb', $post_thumb, $post->ID );
		$date       = apply_filters( 'apple_news_exporter_date', $date, $post->ID );
		$byline     = apply_filters( 'apple_news_exporter_byline', $byline, $post->ID );

		// The post_content is not raw HTML, as WordPress editor cleans up
		// paragraphs and new lines, so we need to transform the content to
		// HTML. We use 'the_content' filter for that.
		$content    = apply_filters( 'apple_news_exporter_content_pre', $post->post_content, $post->ID );
		$content    = apply_filters( 'the_content', $content );
		$content    = apply_filters( 'apple_news_exporter_content', $content, $post->ID );

		// Now pass all the variables into the Exporter_Content array.
		$base_content = new Exporter_Content(
			$post->ID,
			$title,
			$content,
			$excerpt,
			$post_thumb,
			$byline,
			$this->fetch_content_settings()
		);

		return new Exporter( $base_content, null, $this->settings );
	}

	/**
	 * Loads settings for the Exporter_Content from the WordPress post metadata.
	 *
	 * @since 0.4.0
	 * @return Settings
	 * @access private
	 */
	private function fetch_content_settings() {
		$settings = new Exporter_Content_Settings();
		foreach ( get_post_meta( $this->id ) as $name => $value ) {
			if ( 0 === strpos( $name, 'apple_news_' ) ) {
				$name  = str_replace( 'apple_news_', '', $name );
				$value = $value[0];
				$settings->set( $name, $value );
			}
		}
		return apply_filters( 'apple_news_content_settings', $settings );
	}

}

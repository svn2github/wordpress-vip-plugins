<?php
namespace Apple_Exporter;

/**
 * Manage the exporter's workspace.
 * For WordPress, this is entirely handled using meta fields
 * since the filesystem is unavailable on WordPress VIP and
 * potentially other enterprise WordPress hosts.
 *
 * @author  Federico Ramirez
 * @author	Bradford Campeau-Laurion
 * @since   0.2.0
 */
class Workspace {

	/**
	 * Meta key used to store the json content with the post.
	 *
	 * @var string
	 * @since 0.9.0
	 */
	const JSON_META_KEY = 'apple_news_api_json';

	/**
	 * Meta key used to store bundled assets with the post.
	 *
	 * @var string
	 * @since 0.9.0
	 */
	const BUNDLE_META_KEY = 'apple_news_api_bundle';

	/**
	 * Current ID of the content we are constructing a workspace for.
	 *
	 * @var int
	 * @since 0.9.0
	 */
	public $content_id;

	/**
	 * Initialize.
	 *
	 * @param int $content_id
	 * @since 0.2.0
	 */
	function __construct( $content_id ) {
		$this->content_id = $content_id;
	}

	/**
	 * Delete all bundle data from the post.
	 *
	 * @param int $content_id
	 * @since 0.2.0
	 */
	public function clean_up() {
		do_action( 'apple_news_before_clean_up' );
		delete_post_meta( $this->content_id, self::JSON_META_KEY );
		delete_post_meta( $this->content_id, self::BUNDLE_META_KEY );
		do_action( 'apple_news_after_clean_up' );
	}

	/**
	 * Adds a source file to be included later in the bundle.
	 *
	 * @param string $filename
	 * @param string $source
	 * @since 0.9.0
	 */
	public function bundle_source( $filename, $source ) {
		add_post_meta( $this->content_id, self::BUNDLE_META_KEY, esc_url_raw( apply_filters( 'apple_news_bundle_source', $source, $filename, $this->content_id ) ) );
	}

	/**
	 * Stores the JSON file for this workspace to be included in the bundle.
	 *
	 * @param string $content
	 * @since 0.9.0
	 */
	public function write_json( $content ) {
		$json = apply_filters( 'apple_news_write_json', $content, $this->content_id );
		if ( null === json_decode( $json ) ) {
			// This is invalid JSON.
			// Store as an empty string to be handled later down the line.
			$json = '';
		}
		update_post_meta( $this->content_id, self::JSON_META_KEY, sanitize_text_field( $json ) );
	}

	/**
	 * Gets the JSON content.
	 *
	 * @return string
	 * @since 0.9.0
	 */
	public function get_json() {
		return apply_filters( 'apple_news_get_json', get_post_meta( $this->content_id, self::JSON_META_KEY, true ), $this->content_id );
	}

	/**
	 * Gets any bundles.
	 *
	 * @return array
	 * @since 0.9.0
	 */
	public function get_bundles() {
		return apply_filters( 'apple_news_get_bundles', get_post_meta( $this->content_id, self::BUNDLE_META_KEY ), $this->content_id );
	}
}

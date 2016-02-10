<?php
namespace Apple_Exporter\Builders;

/**
 * A base abstract builder. All builders must implement a build method, in
 * which they return an array to represent a part of the final article.
 *
 * @since 0.4.0
 */
abstract class Builder {

	/**
	 * The content object to be exported.
	 *
	 * @var Exporter_Content
	 * @access private
	 * @since 0.4.0
	 */
	private $content;

	/**
	 * Exporter settings object.
	 *
	 * @var Exporter_Content_Settings
	 * @access private
	 * @since 0.4.0
	 */
	private $settings;

	/**
	 * Constructor.
	 */
	function __construct( $content, $settings ) {
		$this->content  = $content;
		$this->settings = $settings;
	}

	/**
	 * Returns an array of the content.
	 *
	 * @access public
	 * @return array
	 */
	public function to_array() {
		return $this->build();
	}

	/**
	 * Builds the content.
	 *
	 * @abstract
	 * @access protected
	 */
	protected abstract function build();

	// Isolate dependencies
	// ------------------------------------------------------------------------

	/**
	 * Gets the content ID.
	 *
	 * @access protected
	 * @return mixed
	 */
	protected function content_id() {
		return $this->content->id();
	}

	/**
	 * Gets the content title.
	 *
	 * @access protected
	 * @return string
	 */
	protected function content_title() {
		return $this->content->title() ?: __( 'Untitled Article', 'apple-news' );
	}

	/**
	 * Gets the content body.
	 *
	 * @access protected
	 * @return string
	 */
	protected function content_text() {
		return $this->content->content();
	}

	/**
	 * Gets the content intro.
	 *
	 * @access protected
	 * @return Intro
	 */
	protected function content_intro() {
		return $this->content->intro();
	}

	/**
	 * Gets the content cover.
	 *
	 * @access protected
	 * @return Cover
	 */
	protected function content_cover() {
		return $this->content->cover();
	}

	/**
	 * Gets a content setting.
	 * @access protected
	 * @param string $name
	 * @return string
	 */
	protected function content_setting( $name ) {
		return $this->content->get_setting( $name );
	}

	/**
	 * Gets the content byline.
	 *
	 * @access protected
	 * @return Byline
	 */
	protected function content_byline() {
		return $this->content->byline();
	}

	/**
	 * Gets the content nodes.
	 *
	 * @access protected
	 * @return array
	 */
	protected function content_nodes() {
		return $this->content->nodes();
	}

	/**
	 * Updates a content property.
	 *
	 * @access protected
	 */
	protected function set_content_property( $name, $value ) {
		return $this->content->set_property( $name, $value );
	}

	/**
	 * Gets a content setting by key.
	 *
	 * @access protected
	 * @param string $name
	 * @return mixed
	 */
	protected function get_setting( $name ) {
		return $this->settings->get( $name );
	}

}

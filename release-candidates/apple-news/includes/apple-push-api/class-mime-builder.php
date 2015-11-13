<?php
namespace Apple_Push_API;

class MIME_Builder {

	/**
	 * Boundary to separate bundle items in the MIME request.
	 *
	 * @var string
	 * @access private
	 */
	private $boundary;

	/**
	 * Valid MIME types for Apple News bundles.
	 *
	 * @var array
	 * @static
	 * @access private
	 */
	private static $valid_mime_types = array (
		'image/jpeg',
		'image/png',
		'image/gif',
		'application/font-sfnt',
		'application/x-font-truetype',
		'application/font-truetype',
		'application/vnd.ms-opentype',
		'application/x-font-opentype',
		'application/font-opentype',
		'application/octet-stream',
	);

	/**
	 * Constructor.
	 */
	function __construct() {
		$this->boundary = md5( microtime() );
	}

	/**
	 * Get the boundary.
	 *
	 * @return string
	 * @access public
	 */
	public function boundary() {
		return $this->boundary;
	}

	/**
	 * Add metadata to the MIME request.
	 *
	 * @param mixed $meta
	 * @return string
	 * @access public
	 */
	public function add_metadata( $meta ) {
		$eol  = "\r\n";

		$attachment  = '--' . $this->boundary . $eol;
		$attachment .= 'Content-Type: application/json' . $eol;
		$attachment .= 'Content-Disposition: form-data; name=metadata' . $eol . $eol;
		$attachment .= json_encode( $meta ) . $eol;

		return $attachment;
	}

	/**
	 * Add a JSON string to the MIME request.
	 *
	 * @param string $name
	 * @param string $filename
	 * @param string $content
	 * @return string
	 * @access public
	 */
	public function add_json_string( $name, $filename, $content ) {
		return $this->build_attachment( $name, $filename, $content, 'application/json' );
	}

	/**
	 * Add file contents to the MIME request.
	 *
	 * @param string $filepath
	 * @param string $name
	 * @return string
	 * @access public
	 */
	public function add_content_from_file( $filepath, $name = 'a_file' ) {
		$filename     = \Apple_News::get_filename( $filepath );
		$file_content = file_get_contents( $filepath );
		$file_mime    = $this->get_mime_type_for( $filepath );

		return $this->build_attachment( $name, $filename, $file_content, $file_mime );
	}

	/**
	 * Close a file added to the MIME request.
	 *
	 * @return string
	 * @access public
	 */
	public function close() {
		return '--' . $this->boundary . '--';
	}

	/**
	 * Build an attachment in the MIME request.
	 *
	 * @param string $name
	 * @param string $filename
	 * @param string $content
	 * @param string $mime_type
	 * @return string
	 * @access private
	 */
	private function build_attachment( $name, $filename, $content, $mime_type ) {
		$eol  = "\r\n";
		$size = strlen( $content );

		$attachment  = '--' . $this->boundary . $eol;
		$attachment .= 'Content-Type: ' . $mime_type . $eol;
		$attachment .= 'Content-Disposition: form-data; name=' . $name . '; filename=' . $filename . '; size=' . $size . $eol . $eol;
		$attachment .= $content . $eol;

		return $attachment;
	}

	/**
	 * Get the MIME type for a file.
	 *
	 * @todo replace with the proper WordPress function.
	 * @param string $filepath
	 * @return string
	 * @access private
	 */
	private function get_mime_type_for( $filepath ) {
		// TODO: rethink this for better integration with WordPress
		return 'application/octet-stream';
	}

	/**
	 * Check if this file is a valid MIME type to be included in the bundle.
	 *
	 * @param string $type
	 * @return boolean
	 * @access private
	 */
	private function is_valid_mime_type( $type ) {
		return in_array( $type, self::$valid_mime_types );
	}

}

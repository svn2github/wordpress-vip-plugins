<?php
namespace Apple_Push_API;

use Apple_Push_API\Request\Request_Exception as Request_Exception;

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
		return $this->build_attachment(
			$name,
			$filename,
			$content,
			'application/json',
			strlen( $content )
		);
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
		// Get the file size
		$size = 0;
		if ( filter_var( $filepath, FILTER_VALIDATE_URL ) ) {
			$headers = get_headers( $filepath );
			foreach ( $headers as $header ) {
				if ( preg_match( '/Content-Length: ([0-9]+)/i', $header, $matches ) ) {
					$size = intval( $matches[1] );
				}
			}
		} else {
			$size = filesize( $filepath );
		}

		return $this->build_attachment(
			$name,
			\Apple_News::get_filename( $filepath ),
			file_get_contents( $filepath ),
			$this->get_mime_type_for( $filepath ),
			$size
		);
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
	 * @param int $size
	 * @return string
	 * @access private
	 */
	private function build_attachment( $name, $filename, $content, $mime_type, $size ) {
		// Ensure the file isn't empty
		if ( empty( $content ) ) {
			throw new Request_Exception( sprintf(
				__( 'The attachment %s could not be included in the request because it was empty.', 'apple-news' ),
				esc_html( $filename )
			) );
		}

		// Ensure a valid size was provided
		if ( 0 >= intval( $size ) ) {
			throw new Request_Exception( sprintf(
				__( 'The attachment %s could not be included in the request because its size was %s.', 'apple-news' ),
				esc_html( $filename ),
				esc_html( $size )
			) );
		}

		// Build the attachment
		$eol  = "\r\n";

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

<?php
namespace Apple_Exporter\Components;

/**
 * A tweet embed code consists of a blockquote followed by a script tag. Parse
 * the blockquote only and ignore the script tag, as all we need is the URL.
 *
 * @since 0.2.0
 */
class Tweet extends Component {

	/**
	 * Look for node matches for this component.
	 *
	 * @param DomNode $node
	 * @return mixed
	 * @static
	 * @access public
	 */
	public static function node_matches( $node ) {
		// Check if the body of a node is solely a tweet URL
		$is_twitter_url = $node->nodeName == 'p' && preg_match(
			'#https?://(www\.)?twitter\.com/.+?/status(es)?/.*#i',
			trim( $node->nodeValue ) );

		if ( self::node_has_class( $node, 'twitter-tweet' ) || $is_twitter_url ) {
			return $node;
		}

		return null;
	}

	/**
	 * Build the component.
	 *
	 * @param string $text
	 * @access protected
	 */
	protected function build( $text ) {
		// Find tweeter URL in HTML string
		if ( ! preg_match_all( '/https?:\/\/(?:www\.)?twitter.com\/(?:#!\/)?([^\/]*)\/status(?:es)?\/(\d+)/', $text, $matches, PREG_SET_ORDER ) ) {
			return null;
		}

		$matches = array_pop( $matches );

		$url = 'https://twitter.com/' . $matches[1] . '/status/' . $matches[2];
		$this->json = array(
			'role' => 'tweet',
			'URL'  => $url,
		);

		$this->set_layout();
	}

	/**
	 * Set the layout for the component.
	 *
	 * @access private
	 */
	private function set_layout() {
		$this->json['layout'] = 'tweet-layout';
		$this->register_full_width_layout( 'tweet-layout', array(
			'margin' => array( 'top' => 30, 'bottom' => 30 )
		) );
	}

}

<?php
namespace Apple_Exporter;

/**
 * This class transforms HTML into Article Format Markdown, which is a subset
 * of Markdown.
 *
 * For elements that are not supported, just skip them and add the contents of
 * the tag.
 *
 * @since 0.2.0
 */
class Markdown {

	/**
	 * List mode for the markdown.
	 *
	 * @var string
	 * @access private
	 */
	private $list_mode;

	/**
	 * List index for the markdown.
	 *
	 * @var int
	 * @access private
	 */
	private $list_index;

	/**
	 * Constructor.
	 */
	function __construct() {
		$this->list_mode = 'ul';
		$this->list_index = 1;
	}

	/**
	 * Transforms HTML into Article Format Markdown.
	 *
	 * @param string $html
	 * @return string
	 * @access public
	 */
	public function parse( $html ) {
		if ( empty( $html ) ) {
			return '';
		}

		// PHP's DomDocument doesn't like HTML5 so we must ignore errors, we'll
		// manually handle all tags anyways.
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		// A trick to load string as UTF-8
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors( true );

		// Find the first-level nodes of the body tag.
		$nodes = $dom->getElementsByTagName( 'body' )->item( 0 )->childNodes;

		// Parse them and return result
		return apply_filters( 'apple_news_parse_markdown', $this->parse_nodes( $nodes ), $nodes );
	}

	/**
	 * Parse an array of nodes for markdown.
	 *
	 * @param array $nodes
	 * @return string
	 * @access private
	 */
	private function parse_nodes( $nodes ) {
		$result = '';
		foreach ( $nodes as $node ) {
			$result .= $this->parse_node( $node );
		}

		return $result;
	}

	/**
	 * Parse an individual node for markdown.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_node( $node ) {
		switch ( $node->nodeName ) {
		case 'strong':
			return $this->parse_strong_node( $node );
		case  'i':
		case 'em':
			return $this->parse_emphasis_node( $node );
		case 'br':
			return $this->parse_linebreak_node( $node );
		case 'p':
			return $this->parse_paragraph_node( $node );
		case 'a':
			return $this->parse_hyperlink_node( $node );
		case 'ul':
			return $this->parse_unordered_list_node( $node );
		case 'ol':
			return $this->parse_ordered_list_node( $node );
		case 'li':
			return $this->parse_list_item_node( $node );
		case 'h1':
		case 'h2':
		case 'h3':
		case 'h4':
		case 'h5':
		case 'h6':
			return $this->parse_heading_node( $node );
		case '#comment':
			return '';
		case '#text':
		default:
			return $this->parse_text_node( $node );
		}
	}

	/**
	 * Convert a text node to markdown.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_text_node( $node ) {
		return str_replace( '!', '\\!', $node->nodeValue );
	}

	/**
	 * Convert a linebreak node to markdown.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_linebreak_node( $node ) {
		return "  \n";
	}

	/**
	 * Convert a strong node to markdown.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_strong_node( $node ) {
		return '**' . $this->parse_nodes( $node->childNodes ) . '**';
	}

	/**
	 * Convert a emphasis node to markdown.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_emphasis_node( $node ) {
		return '_' . $this->parse_nodes( $node->childNodes ) . '_';
	}

	/**
	 * Convert a paragraph node to markdown.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_paragraph_node( $node ) {
		return $this->parse_nodes( $node->childNodes ) . "\n\n";
	}

	/**
	 * Hyperlinks are not yet supported in Article Format markdown. Ignore for
	 * now.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_hyperlink_node( $node ) {
		$url = esc_url_raw( apply_filters( 'apple_news_markdown_hyperlink', $node->getAttribute( 'href' ) ) );
		return '[' . $this->parse_nodes( $node->childNodes ) . '](' . $url . ')';
	}

	/**
	 * Convert an unordered list node to markdown.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_unordered_list_node( $node ) {
		$this->list_mode = 'ul';
		return $this->parse_nodes( $node->childNodes ) . "\n\n";
	}

	/**
	 * Convert an ordered list node to markdown.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_ordered_list_node( $node ) {
		$this->list_mode = 'ol';
		$this->list_index = 1;
		return $this->parse_nodes( $node->childNodes ) . "\n\n";
	}

	/**
	 * Convert a list item node to markdown.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_list_item_node( $node ) {
		if ( 'ol' == $this->list_mode ) {
			return $this->list_index . '. ' . $this->parse_nodes( $node->childNodes );
			$this->list_index += 1;
		}

		return "- " . $this->parse_nodes( $node->childNodes );
	}

	/**
	 * Convert a heading node to markdown.
	 *
	 * @param DomNode $node
	 * @return string
	 * @access private
	 */
	private function parse_heading_node( $node ) {
		preg_match( '#h(\d)#', $node->nodeName, $matches );
		$level = $matches[1];
		$output = '';
		for( $i = 0; $i < $level; $i++ ) {
			$output .= '#';
		}
		$output .= ' ' . $this->parse_nodes( $node->childNodes ) . "\n";

		return $output;
	}

}

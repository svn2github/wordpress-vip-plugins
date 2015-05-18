<?php

function simplechart_mexp_init( $services ){

	//should we remove all other Media Explorer services?
	global $simplechart;
	if ( true === $simplechart->get_config( 'clear_mexp_default_svcs' ) ){
		$services = array();
	}

	$services['simplechart_mexp_service'] = new Simplechart_MEXP_Service;
	return $services;
}

/**
 * Backbone templates for various views for your new service
 */
class Simplechart_MEXP_Template extends MEXP_Template {

	/**
	 * Outputs the Backbone template for an item within search results.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 */
	public function item( $id, $tab ) {
	?>
		<div id="mexp-item-<?php echo esc_attr( $tab ); ?>-{{ data.id }}" class="mexp-item-area mexp-item" data-id="{{ data.id }}">
			<div class="mexp-item-container clearfix">
				<div class="mexp-item-thumb">
					<img style="max-width: 150px" src="{{ data.meta.img }}">
				</div>

				<div class="mexp-item-main">
					<div class="mexp-item-content">
						<h3>{{ data.content }}</h3>
					</div>
					<div class="mexp-item-date">
						{{ data.date }}
					</div>
				</div>

				<a href="#" id="mexp-check-{{ data.id }}" class="check" title="<?php esc_attr_e( 'Deselect', 'mexp' ); ?>">
					<div class="media-modal-icon"></div>
				</a>
			</div>
		</div>
	<?php
	}

	/**
	 * Outputs the Backbone template for a select item's thumbnail in the footer toolbar.
	 *
	 * @param string $id The template ID.
	 */
	public function thumbnail( $id ) {
	?>
		<div class="mexp-item-thumb">
			<h4>{{ data.content }}</h4>
		</div>
	<?php
	}

	/**
	 * Outputs the Backbone template for a tab's search fields.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 */
	public function search( $id, $tab ) {
	?>
		<form action="#" class="mexp-toolbar-container clearfix tab-all">
			<input
				type="text"
				name="q"
				value="{{ data.params.q }}"
				class="mexp-input-text mexp-input-search"
				size="40"
				placeholder="<?php esc_attr_e( 'Search for anything!', 'mexp' ); ?>"
			>
			<input class="button button-large" type="submit" value="<?php esc_attr_e( 'Search', 'mexp' ); ?>">

			<div class="spinner"></div>
		</form>
	<?php
	}
}

class Simplechart_MEXP_Service extends MEXP_Service {

	/**
	 * Constructor.
	 *
	 * Creates the Backbone view template.
	 */
	public function __construct() {
		$this->set_template( new Simplechart_MEXP_Template );
	}

	/**
	 * Fired when the service is loaded.
	 *
	 * Allows the service to enqueue JS/CSS only when it's required. Akin to WordPress' load action.
	 */
	public function load() {
		add_action( 'mexp_enqueue',array( $this, 'enqueue_statics' ), 10, 1 );
		add_filter( 'mexp_tabs',   array( $this, 'tabs' ),   10, 1 );
		add_filter( 'mexp_labels', array( $this, 'labels' ), 10, 1 );
	}

	/**
	 * Handles the AJAX request and returns an appropriate response. This should be used, for example, to perform an API request to the service provider and return the results.
	 *
	 * @param array $request The request parameters.
	 * @return MEXP_Response|bool|WP_Error A MEXP_Response object should be returned on success, boolean false should be returned if there are no results to show, and a WP_Error should be returned if there is an error.
	 */
	public function request( array $request ) {

		// You'll want to handle connection errors to your service here. Look at the Twitter and YouTube implementations for how you could do this.

		// Create the response for the API
		$response = new MEXP_Response();

		$query_args = array(
			'post_type' => 'simplechart',
		);

		// pagination
		if ( isset( $request['page'] ) && absint( $request['page'] ) > 1 ){
			$query_args['paged'] = absint( $request['page'] );
		}

		// search query
		if ( isset( $request['params']['q'] ) ){
			$query_args['s'] = sanitize_text_field( $request['params']['q'] );
		}

		$simplechart_query = new WP_Query( $query_args );

		if ( $simplechart_query->have_posts() ){
			while ( $simplechart_query->have_posts() ) : $simplechart_query->the_post();
				global $post;

				$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), array( 150, 150 ) );
				$thumb_url = isset( $thumb[0] ) ? $thumb[0] : '';

				$item = new MEXP_Response_Item();
				$item->set_date( intval( get_the_time( 'U' ) ) );
				$item->set_date_format( 'g:i A - j M y' );
				$item->set_id( absint( $post->ID ) );
				$item->set_content( esc_html( get_the_title() ) );
				$item->add_meta( 'img', esc_url( $thumb_url ) );
				$response->add_item( $item );
			endwhile;
		} else {
			return false;
		}

		return $response;
	}

	public function enqueue_statics(){

		global $simplechart;

		wp_enqueue_script(
			'simplechart-mexp-service',
			$simplechart->get_plugin_url() . 'js/simplechart-mexp-service.js',
			array( 'jquery', 'mexp' )
		);

		wp_enqueue_style(
			'simplechart-mexp-service',
			$simplechart->get_plugin_url() . 'css/simplechart-mexp-service.css'
		);

	}

	/**
	 * Returns an array of tabs (routers) for the service's media manager panel.
	 *
	 * @param array $tabs Associative array of default tab items.
	 * @return array Associative array of tabs. The key is the tab ID and the value is an array of tab attributes.
	 */
	public function tabs( array $tabs ) {
		$tabs['simplechart_mexp_service'] = array(
			'all' => array(
				'defaultTab' => true,
				'text'       => _x( 'All', 'Tab title', 'mexp' ),
				'fetchOnRender' => true,
			),
		);

		return $tabs;
	}

	/**
	 * Returns an array of custom text labels for this service.
	 *
	 * @param array $labels Associative array of default labels.
	 * @return array Associative array of labels.
	 */
	public function labels( array $labels ) {
		$labels['simplechart_mexp_service'] = array(
			'insert'    => __( 'Insert Chart', 'simplechart' ),
			'noresults' => __( 'No charts matched your search query.', 'simplechart' ),
			'title'     => __( 'Insert Chart', 'simplechart' ),
		);

		return $labels;
	}
}
<?php

/**
 * Class to render tinypass debugger. Is only visible to site's admins
 * Class WPTinypassDebugger
 */
class WPTinypassDebugger extends WPTinypass {
	const FIELD_BUSINESS_MODEL = 'bm';
	const FIELD_VX_API_CALLS = 'vx_api';
	const FIELD_CHARGE_OPTIONS = 'charge_options';
	const FIELD_ACCESS_STATUS = 'access';
	const FIELD_PAYWALL_VIEWS_LEFT = 'paywall_views_left';
	const FIELD_PAYWALL_MAX_VIEWS = 'paywall_max_views';
	const FIELD_PAYWALL_STATE = 'paywall_state';
	const FIELD_TINYPASS_FATAL = 'tinypass_fatal';

	private $titles = array(
		self::FIELD_BUSINESS_MODEL     => 'Business model',
		self::FIELD_VX_API_CALLS       => 'API calls',
		self::FIELD_CHARGE_OPTIONS     => 'Charge options',
		self::FIELD_ACCESS_STATUS      => 'Access status',
		self::FIELD_PAYWALL_VIEWS_LEFT => 'Views left',
		self::FIELD_PAYWALL_MAX_VIEWS  => 'Max views',
		self::FIELD_PAYWALL_STATE      => 'Paywall state',
		self::FIELD_TINYPASS_FATAL     => 'Fatal error'
	);
	private $types = array(
		self::FIELD_BUSINESS_MODEL     => 'string',
		self::FIELD_VX_API_CALLS       => 'array',
		self::FIELD_CHARGE_OPTIONS     => 'string',
		self::FIELD_ACCESS_STATUS      => 'string',
		self::FIELD_PAYWALL_VIEWS_LEFT => 'string',
		self::FIELD_PAYWALL_MAX_VIEWS  => 'string',
		self::FIELD_PAYWALL_STATE      => 'string',
		self::FIELD_TINYPASS_FATAL     => 'error'
	);

	public function __construct() {
		add_action( 'wp_footer', array( $this, 'render' ) );
		wp_register_style( 'tinypass-debugger', plugin_dir_url( TINYPASS_PLUGIN_FILE_PATH ) . 'css/debugger.css' );
		wp_enqueue_style( 'tinypass-debugger' );
	}

	public function render() {
		require_once( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . '/views/debugger/_header.php' );
		$this->renderFields();
		require_once( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . '/views/debugger/_footer.php' );
	}

	private function renderFields() {
		$fields = array(
			self::FIELD_BUSINESS_MODEL,
			self::FIELD_CHARGE_OPTIONS
		);

		if ( self::$business_model == TinypassConfig::BUSINESS_MODEL_METERED ) {
			$fields[] = self::FIELD_PAYWALL_MAX_VIEWS;
			$fields[] = self::FIELD_PAYWALL_VIEWS_LEFT;
			$fields[] = self::FIELD_PAYWALL_STATE;
		}
		foreach ( $fields as $field ) {
			$this->renderField( $field );
		}
		if ( $this->isValueSet( self::FIELD_TINYPASS_FATAL ) ) {
			$this->renderField( self::FIELD_TINYPASS_FATAL );
		}

	}

	private function renderField( $name ) {
		$type = 'string';
		if ( array_key_exists( $name, $this->types ) ) {
			$type = $this->types[ $name ];
		}
		$title = "{{{$name}}}";
		if ( array_key_exists( $name, $this->titles ) ) {
			$title = $this->titles[ $name ];
		}
		$value = isset( self::$_debugData[ $name ] ) ? self::$_debugData[ $name ] : null;

		require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . '/views/debugger/_' . sanitize_file_name( $type ) . '.php' );
	}

	private function isValueSet( $field ) {
		return isset( self::$_debugData[ $field ] ) &&
		       ! empty( self::$_debugData[ $field ] );
	}
}
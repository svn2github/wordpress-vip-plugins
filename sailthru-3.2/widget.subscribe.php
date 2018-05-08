<?php

function sailthru_attributes( $attribute_list ) {
	if ( ! empty( $attribute_list ) ) {
		$attributes = explode( ',', $attribute_list );
		$list       = '';
		foreach ( $attributes as $attribute ) {
			$split = explode( ':', esc_attr( $attribute ) );
			$list .= $split[0] . '="' . $split[1] . '" ';

		}
		return $list;
	}
	return '';
}

function sailthru_field_class( $class, $type = '' ) {
	if ( ! empty( $class ) ) {
		return 'class="form-control ' . esc_attr( $class ) . '"';
	}

	return '';
}

function sailthru_field_id( $id ) {
	if ( ! empty( $id ) ) {
		return 'id="' . esc_attr( $id ) . '"';
	}
	return '';
}
if ( ! defined( 'SAILTHRU_PLUGIN_PATH' ) ) {
	define( 'SAILTHRU_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SAILTHRU_PLUGIN_URL' ) ) {
	define( 'SAILTHRU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}


class Sailthru_Subscribe_Widget extends WP_Widget {

	/*--------------------------------------------------*/
	/* Constructor
	/*--------------------------------------------------*/

	/**
	 * Instantiates the widget,
	 * loads localization files, and includes necessary stylesheets and JavaScript.
	 */
	public function __construct() {

		// load plugin text domain
		add_action( 'init', array( $this, 'load_widget_text_domain' ) );

		parent::__construct(
			'sailthru-subscribe-id',
			__( 'Sailthru Subscribe Widget', 'sailthru-for-wordpress' ),
			array(
				'classname'   => 'Sailthru_Subscribe',
				'description' => __( 'A widget to allow your visitors to subscirbe to your Sailthru lists.', 'sailthru-for-wordpress' ),
			)
		);

		// Only register widget scripts, styles, and ajax when widget is active
		if ( is_active_widget( false, false, $this->id_base, true ) ) {
			// Register admin styles and scripts
			// According to documentation: admin_print_styles should not be used to enqueue styles or scripts on the admin pages. Use admin_enqueue_scripts instead.
			add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

			// Register site styles and scripts
			add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_scripts' ) );

			// Include the Ajax library on the front end
			add_action( 'wp_head', array( $this, 'add_ajax_library' ) );

			// Method to subscribe a user
			add_action( 'wp_ajax_nopriv_add_subscriber', array( $this, 'add_subscriber' ) );
			add_action( 'wp_ajax_add_subscriber', array( $this, 'add_subscriber' ) );
		}

	} // end constructor

	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array   args  The array of form elements
	 * @param array   instance The current instance of the widget
	 */
	public function widget( $args, $instance ) {

		if ( empty( $instance['sailthru_list'] ) ) {
			return false;
		}

		extract( $args, EXTR_SKIP );
		if ( isset( $before_widget ) ) {
			echo wp_kses_post( $before_widget );
		}
		include SAILTHRU_PLUGIN_PATH . 'views/widget.subscribe.display.php';
		if ( isset( $after_widget ) ) {
			echo wp_kses_post( $after_widget );
		}

	} // end widget

	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param array   new_instance The previous instance of values before the update.
	 * @param array   old_instance The new instance of values to be generated via the update.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = array(
			'title'         => filter_var( $new_instance['title'], FILTER_SANITIZE_STRING ),
			'source'        => filter_var( $new_instance['source'], FILTER_SANITIZE_STRING ),
			'lo_event_name' => filter_var( $new_instance['lo_event_name'], FILTER_SANITIZE_STRING ),
		);

		$customfields = get_option( 'sailthru_forms_options' );
		$key          = get_option( 'sailthru_forms_key' );

		for ( $i = 0; $i < $key; $i++ ) {
			$field_key     = $i + 1;
			$name_stripped = preg_replace( '/[^\da-z]/i', '_', $customfields[ $field_key ]['sailthru_customfield_name'] );
			//setup instance variables
			$instance[ 'show_' . $name_stripped . '_name' ]     = (bool) $new_instance[ 'show_' . $name_stripped . '_name' ];
			$instance[ 'show_' . $name_stripped . '_required' ] = (bool) $new_instance[ 'show_' . $name_stripped . '_required' ];
			$instance[ 'show_' . $name_stripped . '_type' ]     = $new_instance[ 'show_' . $name_stripped . '_type' ];
			$instance['field_order']                            = $new_instance['field_order'];
			//$instance['sailthru_customfields_order_widget'] = sanitize_text_field($new_instance['field_order']);

		}
		$instance['sailthru_list'] = is_array( $new_instance['sailthru_list'] ) ? array_map( 'sanitize_text_field', $new_instance['sailthru_list'] ) : '';

		//if ( isset($new_instance['field_order']) && $new_instance['field_order'] != '' ){
		// update_option( 'sailthru_customfields_order_widget', sanitize_text_field($new_instance['field_order']));
		//}
		return $instance;

	} // end widget

	/**
	 * Generates the administration form for the widget.
	 *
	 * @param array   instance The array of keys and values for the widget.
	 */
	public function form( $instance ) {

		// Default values for a widget instance
		$instance = wp_parse_args(
			(array) $instance, array(
				'title'         => '',
				'source'        => '',
				'lo_event_name' => '',
				'sailthru_list' => array( '' ),
				'field_order'   => '',
			)
		);

		$title         = $instance['title'];
		$source        = $instance['source'];
		$lo_event_name = $instance['lo_event_name'];
		$sailthru_list = $instance['sailthru_list'];
		$order         = $field_order = $instance['field_order'];
		$widget_id     = $this->id;

		// Display the admin form
		include SAILTHRU_PLUGIN_PATH . 'views/widget.subscribe.admin.php';

	} // end form

	/*--------------------------------------------*
	 * Action Functions
	 *--------------------------------------------*/


	/**
	 * If enabled then a user is created in WordPress when a newsletter subscription is processed.
	 * This will only fire when the first_name is present so we can create a username
	 * it's recommended to uses a first and last name
	 */
	function create_wp_account( $email, $options, $template = '' ) {

		if ( false === apply_filters( 'sailthru_user_registration_enable', true ) ) {
			return;
		}

		if ( isset( $options['vars']['first_name'] ) && ! empty( $options['vars']['first_name'] ) ) {
				 $first_name = $options['vars']['first_name'];
		} else {
			 $first_name = '';
		}

		if ( isset( $options['vars']['last_name'] ) && ! empty( $options['vars']['last_name'] ) ) {
				 $last_name = $options['vars']['last_name'];
		} else {
			 $last_name = '';
		}

		$nickname = sanitize_text_field( $first_name . ' ' . $last_name );

		if ( empty( $nickname ) ) {
			$nickname = $email;
		}

		$params = array(
			'options'  => $options,
			'template' => $template,
		);

		if ( false === email_exists( $email ) ) {
			$random_password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
			$user            = wp_create_user( $email, $random_password, $email );
			wp_new_user_notification( $user, null, 'user', $params );

			$user_vars             = $options['vars'];
			$user_vars['ID']       = $user;
			$user_vars['nickname'] = $first_name . ' ' . $last_name;
			unset( $user_vars['wp_widget_lists'] );
			wp_update_user( $user_vars );
			write_log( 'Account created:' . $email );
		} else {
			write_log( 'Account for ' . $email . ' exists' );
		}

	}



	/**
	 * Adds the WordPress Ajax Library to the frontend.
	 */
	public function add_ajax_library() {
		echo '<script type="text/javascript">';
		echo 'var sailthru_vars = ' . wp_json_encode( [ 'ajaxurl' => admin_url( 'admin-ajax.php' ) ]);
		echo '</script>';
	} // end add_ajax_library

	/*--------------------------------------------------*/
	/* Public Functions
	/*--------------------------------------------------*/

	/**
	 * Loads the Widget's text domain for localization and translation.
	 */
	public function load_widget_text_domain() {

		load_plugin_textdomain( 'sailthru-for-wordpress', false, plugin_dir_path( __FILE__ ) . '/lang/' );

	} // end load_widget_text_domain


	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */
	public function register_admin_scripts() {

		wp_enqueue_style( 'sailthru-subscribe-admin-styles', SAILTHRU_PLUGIN_URL . 'css/widget.subscribe.admin.css' );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-sortable' );
	} // end register_admin_scripts

	/**
	 * Registers and enqueues widget-specific styles.
	 */
	public function register_widget_styles() {

		wp_enqueue_style( 'sailthru-subscribe-widget-styles', SAILTHRU_PLUGIN_URL . 'css/widget.subscribe.css' );

	} // end register_widget_styles

	/**
	 * Registers and enqueues widget-specific scripts.
	 */
	public function register_widget_scripts() {

		wp_enqueue_script( 'sailthru-subscribe-script', SAILTHRU_PLUGIN_URL . 'js/widget.subscribe.js', array( 'jquery' ) );

	} // end register_widget_scripts

	/*--------------------------------------------------*/
	/* Core Functions
	/*--------------------------------------------------*/

	function return_response( $response ) {

		header('Content-Type: application/json');
		echo  wp_json_encode( $response );
		exit();
	}

	function add_subscriber() {

		if ( ! wp_verify_nonce( $_POST['sailthru_nonce'], 'add_subscriber_nonce' ) ) {

			$result = array(
				'success' => false,
				'message' => 'This form could not be validated, please refresh the page and try again. ',
			);
			$this->return_response( $result );
		}

		$email = ! empty( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : false;

		// check if email is valid, if not bail.
		if ( ! is_email( $email ) ) {
			$result['success'] = false;
			$result['message'] = 'Please enter a valid email';
			$this->return_response( $result );
		}

		// check if email address exists in Sailthru.
		$sailthru   = get_option( 'sailthru_setup_options' );
		$api_key    = $sailthru['sailthru_api_key'];
		$api_secret = $sailthru['sailthru_api_secret'];

		$client = new WP_Sailthru_Client( $api_key, $api_secret );

		if ( $client ) {

			$options      = array();

			// set the source
			if ( isset( $_POST['source'] ) && ! empty( $_POST['source'] ) ) {
				$source = sanitize_text_field( $_POST['source'] );
			} else {
				$source = get_bloginfo( 'url' );
			}

			// initialize vars with source.
			$vars         = array( 'source' => $source );
			$customfields = get_option( 'sailthru_forms_options' );
			$key          = get_option( 'sailthru_forms_key' );

			for ( $i = 0; $i < $key; $i++ ) {
				$field_key = $i + 1;

				if ( ! empty( $customfields[ $field_key ]['sailthru_customfield_name'] ) ) {
					$name_stripped = preg_replace( '/[^\da-z]/i', '_', $customfields[ $field_key ]['sailthru_customfield_name'] );

					if ( ! empty( $_POST[ 'custom_' . $name_stripped ] ) ) {
						// check to see if this is an array or a string
						if ( is_array( $_POST[ 'custom_' . $name_stripped ] ) ) {

							foreach ( $_POST[ 'custom_' . $name_stripped ] as $val ) {
								$var_name             = str_replace( 'custom_', '', $name_stripped );
								$vars[ $var_name ] [] = sanitize_text_field( $val );
							}
						} else {
							$var_name          = str_replace( 'custom_', '', $name_stripped );
							$vars[ $var_name ] = sanitize_text_field( $_POST[ 'custom_' . $name_stripped ] );
						}
					}
				}
			} //end for loop


			$subscribe_to_lists  = array();
			$sailthru_email_list = sanitize_text_field( $_POST['sailthru_email_list'] );
			if ( ! empty( $sailthru_email_list ) ) {

				// check for double opt in setting
				if ( isset( $customfields['sailthru_double_opt_in'] ) && true === $customfields['sailthru_double_opt_in'] ) {
					$double_opt_in = true;
				} else {
					$double_opt_in = false;
				}

				$lists = explode( ',', $sailthru_email_list );

				foreach ( $lists as $key => $list ) {
					$subscribe_to_lists[ $list ] = 1;
				}

				$options['lists'] = $subscribe_to_lists;

			} else {

				$options['lists'] = array( 'Sailthru Subscribe Widget' => 1 ); // subscriber is an orphan

			}

			// clean up vars
			unset( $vars['email'] );
			unset( $vars['sailthru_nonce'] );
			unset( $vars['action'] );

			$options['vars'] = $vars;

			$data = array(
				'id'     => $email,
				'fields' => array(
					'lists' => 1,
					'keys'  => 1,
				),
			);

			$profile = false;

			try {
				$profile = $client->apiGet( 'user', $data );
			} catch ( Sailthru_Client_Exception $e ) {

				if ( ! strpos( $e->getMessage(), 'User not found with email' ) ) {
					write_log( $e->getMessage() );
				}
			}

			if ( ! empty( $profile ) ) {

				if ( isset( $profile['lists'] ) && count( $profile['lists'] ) > 0 ) {

					// now we need to check which lists they are being subscribed to which they are not a member of.
					foreach ( $options['lists'] as $list_name => $list_val ) {

						if ( ! array_key_exists( $list_name, $profile['lists'] ) ) {
							$new_lists[] = $list_name;
						}
					}
				} else {
					// all of the lists are new lists
					$new_lists = $options['lists'];
				}

				try {
					$profile_data = array(
						'id'    => $email,
						'key'   => 'email',
						'vars'  => $options['vars'],
						'lists' => $options['lists'],
					);
					$client->apiPost( 'user', $profile_data );
					$new_lists = $options['lists'];

				} catch ( Sailthru_Client_Exception $e ) {
					write_log( $e );
				}

			} else {

				try {
					$profile_data = array(
						'id'    => $email,
						'key'   => 'email',
						'vars'  => $options['vars'],
						'lists' => $options['lists'],
					);
					$client->apiPost( 'user', $profile_data );
					$new_lists = $options['lists'];

				} catch ( Sailthru_Client_Exception $e ) {
					write_log( $e );
				}
			}

			// check if the user is a new subscriber to any lists.
			$new_subscriber = count( $new_lists ) > 0 ? true : false;

			if ( isset( $customfields['sailthru_welcome_template'] ) && ! empty( $customfields['sailthru_welcome_template'] ) ) {

				if ( $new_subscriber ) {

					try {
						$client->send( $customfields['sailthru_welcome_template'], $email, $vars );
					} catch ( Sailthru_Client_Exception $e ) {
						write_log( $e );
					}
				}
			}

			// Handle the Event If it's been set to fire.
			if ( isset( $_POST['lo_event_name'] ) && ! empty( $_POST['lo_event_name'] ) ) {
				$event = sanitize_text_field( $_POST['lo_event_name'] );
			} else {
				$event = false;
			}

			if ( $event ) {

				$event_data = array(
					'id'    => $email,
					'event' => $event,
					'vars'  => $options['vars'],
				);

				try {
					$client->apiPost( 'event', $event_data );
				} catch ( Sailthru_Client_Exception $e ) {
					write_log( $e );
				}
			}

			if ( has_filter( 'sailthru_user_registration_enable' ) ) {
				$setup    = get_option( 'sailthru_setup_options' );
				$template = '';
				if ( isset( $setup['sailthru_setup_new_user_override_template'] ) && ! empty( $setup['sailthru_setup_new_user_override_template'] ) ) {
					$template = $setup['sailthru_setup_new_user_override_template'];
				}
				$this->create_wp_account( $email, $options, $template );
			}

			// format response.
			$result = array(
				'success' => true,
				'message' => 'User Subscribed',
			);

			$this->return_response( $result );

		} else {

			// format response.
			$result['success'] = false;
			$result['message'] = 'Sorry, something went wrong and we could not subscribe you.';
			$this->return_response( $result );
		}

	}




} // end class

// Register activation hook
register_activation_hook( __FILE__, array( 'Sailthru_Subscribe', 'activate' ) );

/**
 * Register Sailthru Subscribe Widget
 */
function sailthru_register_subscribe_widget() {
	register_widget( 'Sailthru_Subscribe_Widget' );
}
add_action( 'widgets_init', 'sailthru_register_subscribe_widget' );

function sailthru_widget_shortcode( $atts ) {

	// Configure defaults and extract the attributes into variables
	extract(
		shortcode_atts(
			array(
				'fields'          => 'name',
				'modal'           => 'false',
				'text'            => 'Subscribe',
				'sailthru_list'   => array(),
				'field_order'     => '',
				'using_shortcode' => true,
			), $atts
		)
	);

	if ( empty( $atts['using_shortcode'] ) ) {
		$atts['using_shortcode'] = true;
	}

	if ( empty( $atts['text'] ) ) {
		$atts['text'] = 'Subscribe to our newsletter';
	}

	// the widget doesn't render if there is no email list specified.
	if ( empty( $atts['sailthru_list'] ) ) {
		$atts['sailthru_list'] = array( 'Sailthru Wordpress Shortcode' );
	}

	if ( ! empty( $atts['modal'] ) ) {

		if ( 'true' === $atts['modal'] ) {
			$before_widget = '<div id="mask"></div><a class="show_shortcode" href="#">' . esc_html( $atts['text'] ) . '</a><div id="sailthru-modal"><div class="sailthru_shortcode_hidden">';
			$after_widget  = '</div></div>';
		} else {
			$before_widget = '<div class="sailthru_shortcode">';
			$after_widget  = '</div>';
		}
	} else {
		$before_widget = '<div class="sailthru_shortcode">';
		$after_widget  = '</div>';
	}

	$args = array(
		'before_widget' => $before_widget,
		'after_widget'  => '</div>',
		'before_title'  => '<div class="widget-title">',
		'after_title'   => $after_widget,
	);

	ob_start();
	the_widget( 'Sailthru_Subscribe_Widget', $atts, $args );
	$output = ob_get_clean();
	return $output;
}
add_shortcode( 'sailthru_widget', 'sailthru_widget_shortcode' );

<?php
function sailthru_attributes ( $attribute_list ) {
if ( ! empty( $attribute_list ) ) {
	$attributes = explode( ',', $attribute_list );
	$list = '';
		foreach ( $attributes as $attribute ) {
			$split = explode( ':', esc_attr($attribute) );
			$list .= $split[0]. '="' . $split[1] . '" ';

		}
	return $list;
}
return '';
}
function sailthru_field_class ( $class, $type = '' ) {
if ( ! empty( $class ) ) {
	if ($type =='radio' || $type =='checkbox') {
		return 'class="' . esc_attr($class).'"';
	} else {
		return 'class="form-control ' . esc_attr($class).'"';
	}
}
return '';
}
function sailthru_field_id ( $id ) {
if ( ! empty( $class ) ) {
	return 'id="' . esc_attr($id).'"';
}
return '';
}
if ( ! defined( 'SAILTHRU_PLUGIN_PATH' ) )
	define( 'SAILTHRU_PLUGIN_PATH', plugin_dir_path(__FILE__) );

if( ! defined( 'SAILTHRU_PLUGIN_URL' ) )
	define( 'SAILTHRU_PLUGIN_URL', plugin_dir_url(__FILE__) );


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
				'classname'		=>	'Sailthru_Subscribe',
				'description'	=>	__( 'A widget to allow your visitors to subscirbe to your Sailthru lists.', 'sailthru-for-wordpress' )
			)
		);

		// Register admin styles and scripts
		// According to documentation: admin_print_styles should not be used to enqueue styles or scripts on the admin pages. Use admin_enqueue_scripts instead.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

		// Register site styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_scripts' ) );

		// Include the Ajax library on the front end
		add_action( 'wp_head', array( &$this, 'add_ajax_library' ) );

		// Method to subscribe a user
		add_action( 'wp_ajax_nopriv_add_subscriber', array( &$this, 'add_subscriber') );
		add_action( 'wp_ajax_add_subscriber', array( &$this, 'add_subscriber') );

	} // end constructor

	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/

	/**
	 * Outputs the content of the widget.
	 *
	 * @param	array	args		The array of form elements
	 * @param	array	instance	The current instance of the widget
	 */
	public function widget( $args, $instance ) {


		if ( empty( $instance['sailthru_list'] ) ) {
			return false;
		}

		extract( $args, EXTR_SKIP );

		echo $before_widget;

		include( SAILTHRU_PLUGIN_PATH . 'views/widget.subscribe.display.php' );

		echo $after_widget;

	} // end widget

	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param	array	new_instance	The previous instance of values before the update.
	 * @param	array	old_instance	The new instance of values to be generated via the update.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		$instance['title'] = filter_var( $new_instance['title'], FILTER_SANITIZE_STRING );
		$customfields = get_option( 'sailthru_forms_options' );
		$key = get_option( 'sailthru_forms_key' );

			for ( $i = 0; $i < $key; $i++ ) {
				$field_key = $i + 1;
				$name_stripped = preg_replace( "/[^\da-z]/i", '_', $customfields[ $field_key ]['sailthru_customfield_name'] );
				//setup instance variables
				$instance['show_'.$name_stripped.'_name']     = (bool) $new_instance['show_'.$name_stripped.'_name'];
				$instance['show_'.$name_stripped.'_required'] = (bool) $new_instance['show_'.$name_stripped.'_required'];
				$instance['show_'.$name_stripped.'_type']     = $new_instance['show_'.$name_stripped.'_type'];
				$instance['field_order']     = $new_instance['field_order'];
				//$instance['sailthru_customfields_order_widget'] = sanitize_text_field($new_instance['field_order']);

			}
		$instance['sailthru_list'] = is_array( $new_instance['sailthru_list'] ) ? array_map( 'sanitize_text_field', $new_instance['sailthru_list'] ) : '';

		//if ( isset($new_instance['field_order']) && $new_instance['field_order'] != '' ){
		//	update_option( 'sailthru_customfields_order_widget', sanitize_text_field($new_instance['field_order']));
		//}
		return $instance;

	} // end widget

	/**
	 * Generates the administration form for the widget.
	 *
	 * @param	array	instance	The array of keys and values for the widget.
	 */
	public function form( $instance ) {


		// Default values for a widget instance
        $instance = wp_parse_args(
        	(array) $instance, array(
                'title' => '',
                'sailthru_list' => array( '' ),
                'field_order' => ''
            )
        );


        $title = $instance['title'];
        $sailthru_list = $instance['sailthru_list'];
        $order = $field_order = $instance['field_order'];
        $widget_id = $this->id;



		// Display the admin form
		include( SAILTHRU_PLUGIN_PATH . 'views/widget.subscribe.admin.php' );

	} // end form

	/*--------------------------------------------*
	 * Action Functions
	 *--------------------------------------------*/

	/**
	 * Adds the WordPress Ajax Library to the frontend.
	 */
	public function add_ajax_library() {


		$html = '<script type="text/javascript">';
			$html .= 'var ajaxurl = "'.home_url( 'wp-admin/admin-ajax.php' ).'"';
		$html .= '</script>';

		echo $html;

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


	public function activate( $network_wide ) {
		// nothing to see here
	} // end activate

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses
	 * 			"Network Activate" action, false if WPMU is disabled
	 * 			or plugin is activated on an individual blog
	 */
	public function deactivate( $network_wide ) {
		// nothing to see there
	} // end deactivate


	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */
	public function register_admin_scripts() {

		wp_enqueue_style( 'sailthru-subscribe-admin-styles', SAILTHRU_PLUGIN_URL . 'css/widget.subscribe.admin.css' );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'sailthru-subscribe-widget-admin-jquery-script', SAILTHRU_PLUGIN_URL . 'js/widget.subscribe.admin.js', array( 'jquery' ) );
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

		wp_enqueue_script( 'sailthru-subscribe-script', SAILTHRU_PLUGIN_URL . 'js/widget.subscribe.js' , array( 'jquery' ) );


	} // end register_widget_scripts

	/*--------------------------------------------------*/
	/* Core Functions
	/*--------------------------------------------------*/


	function add_subscriber() {

		if ( ! wp_verify_nonce( $_POST['sailthru_nonce'], "add_subscriber_nonce" ) ) {
			$result['error'] = true;
			$result['message'] = "This form does not appear to have been posted from your website and has not been submitted.";
		}

		// add the lists to the vars so it can be used for double opt in
		if ( isset( $_POST['sailthru_email_list']) ) {
			$vars['wp_widget_lists'] = explode( ',', filter_var( trim( $_POST['sailthru_email_list'] ), FILTER_SANITIZE_STRING ) );
		} else {
			$vars['wp_widget_lists'] = array();
		}

		$email = trim( $_POST['email'] );
		if ( ! filter_var( $email , FILTER_VALIDATE_EMAIL ) || empty ( $email ) ) {
			$result['error'] = true;
			$result['message'] = "Please enter a valid email address.";
		} else {
			$email = filter_var( $email, FILTER_VALIDATE_EMAIL );
		}

		if( isset($_POST['first_name'] ) && !empty($_POST['first_name'] ) ) {
			$first_name = filter_var(trim($_POST['first_name']), FILTER_SANITIZE_STRING);
		} else {
			$first_name = '';
		}

		if( isset($_POST['last_name']) && !empty($_POST['last_name'] ) ) {
			$last_name = filter_var(trim($_POST['last_name']), FILTER_SANITIZE_STRING);
		} else {
			$last_name = '';
		}

		if( $first_name || $last_name ) {

			$options = array(
				'vars' => array(
					'first_name'	=> $first_name,
					'last_name'		=> $last_name,
				)
			);

		}

		$subscribe_to_lists = array();
		if( !empty($_POST['sailthru_email_list'] ) ) {
		//add the custom fields info to the api call! This is where the magic happens
		$customfields = get_option( 'sailthru_forms_options' );

		// check for double opt in setting
		if ( isset ( $customfields['sailthru_double_opt_in'] ) &&  $customfields['sailthru_double_opt_in'] == true) {
			$double_opt_in = true;
		} else {
			$double_opt_in = false;
		}

		$key = get_option( 'sailthru_forms_key' );

			for ( $i = 0; $i < $key; $i++ ) {
				$field_key = $i + 1;

				if ( ! empty ( $customfields[ $field_key ]['sailthru_customfield_name'] ) ) {
					$name_stripped = preg_replace( "/[^\da-z]/i", '_', $customfields[ $field_key ]['sailthru_customfield_name'] );

					if ( ! empty ( $_POST['custom_'.$name_stripped] ) ) {
						// check to see if this is an array or a string
						if ( is_array($_POST['custom_'.$name_stripped] ) ) {

							foreach ( $_POST['custom_'.$name_stripped] as $val )  {
								$vars[ $name_stripped ] [] = filter_var( trim( $val ), FILTER_SANITIZE_STRING );
							}

						} else {
							$vars[ $name_stripped ] = filter_var( trim( $_POST['custom_'.$name_stripped] ), FILTER_SANITIZE_STRING );
						}
					}
				}
			} //end for loop

			if ( empty ( $vars ) ) {
				$vars = '';
			}
			$options = array(
				'vars' => $vars
			);

		$subscribe_to_lists = array();
			if ( !empty ( $_POST['sailthru_email_list'] ) ) {

				$lists = explode( ',', $_POST['sailthru_email_list'] );

				foreach( $lists as $key => $list ) {
					$subscribe_to_lists[ $list ] = 1;
				}

				$options['lists'] = $subscribe_to_lists;

			} else {

				$options['lists'] = array( 'Sailthru Subscribe Widget' => 1 );	// subscriber is an orphan

			}


		$options['vars']['source'] = get_bloginfo( 'url' );


		$result['data'] = array(
			'email'	=> $email,
			'options' => $options
		);
		if ( empty ( $result['error'] ) ) {

			$sailthru   = get_option( 'sailthru_setup_options' );
			$api_key    = $sailthru['sailthru_api_key'];
			$api_secret = $sailthru['sailthru_api_secret'];


			//$client = new Sailthru_Client( $api_key, $api_secret );
			$client = new WP_Sailthru_Client( $api_key, $api_secret);
				try {

					if ( $client ) {

						// first we check to see if this user exists for this list(varname)
						$user_exists = $client->apiGet('user', array('id' => $email , 'key' => 'email'));

						if ( !isset( $user_exists['error'] )  && $user_exists  ) {

							if ( isset ( $customfields['sailthru_welcome_template']) && !empty ($customfields['sailthru_welcome_template'] ) ) {

								$new_lists = array();
								$save_lists = false;

								// does the user have any lists?
								if ( isset( $user_exists['lists'] ) && count( $user_exists['lists'] ) > 0 ) 	{

									// now we need to check which lists they are being subscribed to which they are not a member of.
									foreach ( $options['lists'] as $list_name => $list_val ) {

										if ( !array_key_exists( $list_name, $user_exists['lists'] ) ) {
											$new_lists[] = $list_name;
										}

									}

								} else {
									// all of the lists are new lists
									$new_lists = $options['lists'];
								}

								// if there's any lists the user is not a member of send the welcome email
								if ( count( $new_lists ) > 0) {
									$send_mail = $client->send( $customfields['sailthru_welcome_template'], $email, $vars);
								}

							}

						} else {

							// check to see if we get an error that the user does not exist, otherwise throw a real error
							if ( isset ( $user_exists['errormsg'] ) && strpos($user_exists['errormsg'] , 'User not found with email') !== false ) {
								// if there's no error we can send the user the welcome email
								if ( isset ( $customfields['sailthru_welcome_template']) && !empty ($customfields['sailthru_welcome_template'] ) ) {
									$send_mail = $client->send( $customfields['sailthru_welcome_template'], $email, $vars);
								}
							} else {
								if ( $res['ok'] != true ) {
									$result['error'] = true;
									$result['message'] = "There was a problem with your email address.";
								}
							}

						}

						if ( $double_opt_in ) {
							unset( $options['lists'] );
						}
						unset( $options['vars']['lists'] );
						$res = $client->saveUser( $email, $options );

					}
				}
				catch (Sailthru_Client_Exception $e) {
					//silently fail
					return;
				}

			if ( $res['ok'] != true ) {
				$result['error'] = true;
				$result['message'] = "There was an error subscribing you. Please try again later.";
			}

			$result['result'] = $res;

		}

		// did this request come from an ajax call?
		if ( !empty ( $_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest') {
			$result = json_encode( $result );
			echo $result;
			exit();
		} else {
			echo $result['message'];
			exit();
		}

	}	// end add_subscriber()
}

} // end class

// Register activation hook
register_activation_hook( __FILE__, array( 'Sailthru_Subscribe', 'activate' ) );

// Register a new widget with Wordpress
add_action( 'widgets_init', create_function( '', 'register_widget("Sailthru_Subscribe_Widget");' ) );


function sailthru_widget_shortcode( $atts ) {

	// Configure defaults and extract the attributes into variables
	extract( shortcode_atts( array(
		'fields' => 'name',
		'modal'  => 'false',
		'text'   => 'Subscribe',
		'sailthru_list' => array(),
		'field_order' => '',
		'using_shortcode' => true
	), $atts ) );



	if( empty( $atts['using_shortcode'] ) ) {
		$atts['using_shortcode'] = true;
	}


	if ( empty($atts['text'] ) ) {
		$atts['text'] = 'Subscribe to our newsletter';
	}

	// the widget doesn't render if there is no email list specified.
	if( empty( $atts['sailthru_list']) ) {
		$atts['sailthru_list'] = array('Sailthru Wordpress Shortcode');
	}


	if ( ! empty($atts['modal'] ) ) {
		if ( $atts['modal'] == 'true' ) {
			$before_widget = '<div id="mask"></div><a id="show_shortcode" href="#">' . esc_html($atts['text']) . '</a><div id="sailthru-modal"><div class="sailthru_shortcode_hidden">';
			$after_widget = '</div></div>';
		}
		else{
			$before_widget = '<div class="sailthru_shortcode">';
			$after_widget = '</div>';
		}
	} else {
		$before_widget = '<div class="sailthru_shortcode">';
		$after_widget = '</div>';
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


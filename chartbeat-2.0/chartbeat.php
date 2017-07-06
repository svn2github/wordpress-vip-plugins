<?php
/*
Plugin Name: Chartbeat
Plugin URI: http://chartbeat.com/wordpress/
Description: Adds Chartbeat pinging to Wordpress.
Version: 2.0.7
Author: Chartbeat
Author URI: http://chartbeat.com/
*/

/*
Copyright 2009-2017 Chartbeat Inc.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

function chartbeat_menu() {
	add_options_page('chartbeat plugin options', 'Chartbeat', 'manage_options', 'chartbeat-options', 'chartbeat_options_page');
	add_menu_page( 'Chartbeat Console', 'Chartbeat', 'edit_posts', 'chartbeat_console', 'chartbeat_console', plugins_url('media/chartbeat.png', __FILE__) );
}
add_action('admin_menu', 'chartbeat_menu');

function check_chartbeat_accountid_error() {
	if (!get_option('chartbeat_userid') ) {
		add_action( 'admin_notices', 'display_chartbeat_accountid_error' );
}

function display_chartbeat_accountid_error() {
		$class = 'notice notice-error';
		$message = 'You need to set your Chartbeat <a href="'.esc_url(admin_url('options-general.php?page=chartbeat-options')).'">Account ID</a> in the Chartbeat options page';

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
	}
}

add_action( 'admin_init', 'check_chartbeat_accountid_error', 1 );


function chartbeat_console() {
	if (!current_user_can('edit_posts'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	$domain = apply_filters( 'chartbeat_config_domain', chartbeat_get_display_url (get_option('home')) );
	?>
	<style type="text/css">
		#wpbody-content { height:100%; }
		#chartbeat-iframe {
			padding: 10px 0;
			min-height: 640px;
		}
	</style>
	<?php
	if (!get_option('chartbeat_enable_newsbeat')) {
		$iframe_url = add_query_arg( array(
			'url' => chartbeat_get_display_url( $domain ),
			'k' => get_option( 'chartbeat_apikey' ),
			'slim' => 1,
		), '//chartbeat.com/dashboard/' );
		?>
		<iframe id="chartbeat-iframe" width="100%" height="100%" src="<?php echo esc_url( $iframe_url ); ?>"></iframe>
		<?php
	} else {
		$iframe_url = add_query_arg( array(
			'url' => chartbeat_get_display_url( $domain ),
			'k' => get_option( 'chartbeat_apikey' ),
			'slim' => 1,
		), '//chartbeat.com/publishing/dashboard/' );
		?>
		<iframe id="chartbeat-iframe"width="100%" height="100%" src="<?php echo esc_url( $iframe_url ); ?>"></iframe>
	<?php
	}
}

function chartbeat_options_page() {
	$domain = apply_filters( 'chartbeat_config_domain', chartbeat_get_display_url (get_option('home')) );
	?>
	<div class="wrap">
		<h2>Chartbeat</h2>
		<form method="post" action="options.php" onsubmit="buildOptions()">
			<?php
			// outputs all of the hidden fields that options.php will check, including the nonce
			wp_nonce_field('update-options');
			settings_fields('chartbeat-options'); ?>

			<script>
			function showSettings() {
				window.open('//chartbeat.com/wordpress/?site=' + encodeURIComponent(window.location.host));
			}
			</script>
			To enable tracking, you must enter your chartbeat account id. <a href="#" onclick="showSettings()">Find yours.</a> <br />
			<table class="form-table">
				<tr>
					<th scope="row">Account ID</th>
					<td><input size="30" type="text" name="chartbeat_userid"
						value="<?php echo esc_attr( get_option('chartbeat_userid') ); ?>" />
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Track visits by Site Admins?','chartbeat'); ?><br />
						<small>Administrators must be logged in to avoid tracking.</small>
					</th>
					<td>
						<input type="radio" name="chartbeat_trackadmins" value="1" <?php checked( get_option('chartbeat_trackadmins'), 1 ); ?> />
						Yes
						<input type="radio" name="chartbeat_trackadmins" value="0" <?php checked( get_option('chartbeat_trackadmins'), 0 ); ?> />
						No
					</td>
				</tr>
	
				<tr>
					<th scope="row"><?php _e('Enable Chartbeat Publishing?','chartbeat'); ?><br /> <small>Sign
							up for <a href="http://chartbeat.com/publishing/">Chartbeat Publishing</a>.
					</small></th>
					<td>
						<input type="radio" name="chartbeat_enable_newsbeat" value="1" <?php checked( get_option('chartbeat_enable_newsbeat'), 1 ); ?> />
						Yes
						<input type="radio" name="chartbeat_enable_newsbeat" value="0" <?php checked( get_option('chartbeat_enable_newsbeat'), 0 ); ?> />
						No
					</td>
				</tr>

				<tr>
					<th scope="row">
						<?php esc_html_e('Enable Headline Testing?','chartbeat'); ?><br />
						<small>Sign up for <a href="https://chartbeat.com/publishing/headline-optimization/">Headline Testing</a></small>
					</th>
					<td>
						<input type="radio" name="chartbeat_enable_headline_testing" value="1" <?php checked( get_option('chartbeat_enable_headline_testing'), 1 ); ?> />
						Yes
						<input type="radio" name="chartbeat_enable_headline_testing" value="0" <?php checked( get_option('chartbeat_enable_headline_testing'), 0 ); ?> />
						No
					</td>
				</tr>

				<tr>
					<th scope="row">API Key<br/>
					<?php if (get_option('chartbeat_enable_newsbeat')) { ?>
						<small>Get API key <a href="https://chartbeat.com/newsbeat/settings/api-keys/">here</a></small>
					<?php } else { ?>
						<small>Get API key <a href="https://chartbeat.com/apikeys/">here</a></small>
					<?php } ?>
					</th>
					<td><input size="30" type="text" name="chartbeat_apikey" value="<?php echo esc_attr( get_option('chartbeat_apikey') ); ?>" />
					</td>
				</tr>
	
			</table>
			<br /> <br />

			<script src="<?php echo plugins_url('media/topwidget.compiled.js', __FILE__); ?>" type="text/javascript"></script>
			<script type="text/javascript"> 
			var themes = { 'doe':   { 'bgcolor': '', 'border': '#dde7d4', 'text': '#555' },
				'gray':  { 'bgcolor': '#e3e3e3', 'border': '#333333', 'text': '#555', 'header_bgcolor': '#999999', 'header_color': '#fff' },
				'red':   { 'bgcolor': '#ffffff', 'border': '#cc3300', 'text': '#555', 'header_bgcolor': '#f5c5be', 'header_color': '#fff' },
				'blue':  { 'bgcolor': '#e0ecff', 'border': '#3a5db0' },
				'green': { 'bgcolor': '#c9edcc', 'border': '#69c17d', 'text': '#555' } };
			var theme = 'doe';
			var limit = 10;

			function changeTheme(select) {
				theme = select.options[select.selectedIndex].value;
				renderWidget();
			}
			
			function changeLimit(select) {
				limit = select.options[select.selectedIndex].value;
				renderWidget();
			}
			
			function renderWidget() {
				new CBTopPagesWidget( '<?php echo esc_js(get_option('chartbeat_apikey')) ?>',
								   { 'host': '<?php echo esc_js( chartbeat_get_display_url( $domain ) ); ?>',
									 'background': themes[theme]['bgcolor'],
									 'border': themes[theme]['border'],
									 'header_bgcolor': themes[theme]['header_bgcolor'],
									 'header_color': themes[theme]['header_color'],
									 'text': themes[theme]['text'],
									 'limit': limit });
			}

			function addOption(array, key, val) {
				array.push("'" + key + "': '" + val + "'");
			}
			function buildOptions() {
				var options = [];
				addOption(options, 'background', themes[theme]['bgcolor']);
				addOption(options, 'border', themes[theme]['border']);
				addOption(options, 'header_bgcolor', themes[theme]['header_bgcolor']);
				addOption(options, 'header_color', themes[theme]['header_color']);
				addOption(options, 'text', themes[theme]['text']);
				addOption(options, 'limit', limit);
				options = '{' + options.join(',') + '}';
				document.getElementById('chartbeat_widgetconfig').value = options;
				console.debug("options:" + options);
			}
			renderWidget();
			</script>

			If your theme supports it, you can also add a widget under
			<tt>Appearance > Widgets</tt>
			to show where users currently are on your site. <br> <br>
			<table cellspacing="10">
				<tr>
					<td valign="top">Number of pages to show
						<select name="metric" id="toplimit" onChange="changeLimit(this);">
							<option value="5">5</option>
							<option value="10" selected="selected">10</option>
							<option value="20">20</option>
							<option value="30">30</option>
						</select>
						<br /> <br />

						Color scheme
						<select name="theme" id="toptheme" onChange="changeTheme(this);">
							<option value="doe">John Doe</option>
							<option value="gray">Dorian Gray</option>
							<option value="red">Red Rum</option>
							<option value="blue">Blue Moon</option>
							<option value="green">Green Giant</option>
						</select>
					</td>
					<td>&nbsp;</td>
					<td>
						Sample:
						<br> <br>
						<div id="cb_top_pages"></div>
					</td>
				</tr>
			</table>
			<input type="hidden" id="chartbeat_widgetconfig" name="chartbeat_widgetconfig" value="{}" />

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</div>
<?php
}

// Function to register settings and sanitize output. To be called later in add_action
function chartbeat_register_settings() {
	register_setting('chartbeat-options','chartbeat_userid', 'intval');
	register_setting('chartbeat-options','chartbeat_apikey','chartbeat_is_validmd5');
	register_setting('chartbeat-options','chartbeat_widgetconfig','chartbeat_is_validjson');
	register_setting('chartbeat-options','chartbeat_trackadmins','intval'); // add trackadmin setting
	register_setting('chartbeat-options','chartbeat_enable_newsbeat','intval');
	register_setting('chartbeat-options','chartbeat_enable_headline_testing','intval');
}

function chartbeat_is_validmd5($md5) {
	if( !empty($md5) && preg_match('/^[a-f0-9]{32}$/', $md5) ) { 
		return $md5;
	} else {
		add_settings_error( 'chartbeat_apikey','invalid_apikey','API Key is not correct, please check again','error');
		return 777;
	}
}

function chartbeat_is_validjson($json_str) {
	if( is_null(json_decode($json_str)) ) { 
		return $json_str; 
	} else {
		add_settings_error( 'chartbeat_widgetconfig','invalid_widgetconfig','Widget config is malformed','error');
	}
}

function chartbeat_configs() {
	$domain = apply_filters( 'chartbeat_config_domain', chartbeat_get_display_url (get_option('home')) );
	$domain = preg_replace('#^www\.(.+\.)#i', '$1', $domain);
	$cb_configs['domain'] = $domain;

	$user_id = get_option('chartbeat_userid');
	$cb_configs['uid'] = $user_id;

	// Get Author and Sections
	// Only add these values on blog posts use the queried object in case there
	// are multiple Loops on the page.
	if (is_single()) {
		$post = get_queried_object();

		// Use the author's display name 
		$author = get_the_author_meta('display_name', $post->post_author);
		$cb_configs['author'] = apply_filters( 'chartbeat_config_author', $author );
	
		// Use the post's categories as sections
		$cats = get_the_terms($post->ID, 'category');
		if ($cats) {
			$cat_names = array();
			foreach ( $cats as $cat ) {
				$cat_names[] = $cat->name;
			}
		}

		$sections = (array)apply_filters( 'chartbeat_config_sections', $cat_names );
		$cb_configs['sections'] = implode( ", ", $sections);
	}


	if ( apply_filters( 'chartbeat_config_use_canonical', true ) )
		$cb_configs['use_canonical'] = 'true';
	else
		$cb_configs['use_canonical'] = 'false';

	return $cb_configs;

}

function add_chartbeat_head() {
	echo "\n<script type=\"text/javascript\">var _sf_startpt=(new Date()).getTime()</script>\n";
	if( 1 == get_option( 'chartbeat_enable_headline_testing' ) ) :
		$cb_configs = chartbeat_configs();
	?>
		<script type="text/javascript">
			var _sf_async_config = _sf_async_config || {};
			_sf_async_config.uid = <?php echo wp_json_encode( $cb_configs["uid"] ); ?>;
			_sf_async_config.domain = <?php echo wp_json_encode( $cb_configs["domain"] ); ?>;
			_sf_async_config.useCanonical = <?php echo wp_json_encode( $cb_configs["use_canonical"] ); ?>;
		</script>
		<script src="//static.chartbeat.com/js/chartbeat_mab.js"></script>
	<?php
	endif;
}

function add_chartbeat_config(){
		
		$cb_configs = chartbeat_configs();
		if( 1 != get_option( 'chartbeat_enable_headline_testing' ) ) :
		?>
			var _sf_async_config={};
			_sf_async_config.uid = <?php echo wp_json_encode( $cb_configs["uid"] ); ?>;
			_sf_async_config.domain = <?php echo wp_json_encode( $cb_configs["domain"] ); ?>;
			_sf_async_config.useCanonical = <?php echo wp_json_encode( $cb_configs["use_canonical"] ); ?>;
	<?php
		endif;
		$enable_newsbeat = get_option('chartbeat_enable_newsbeat');
		if ($enable_newsbeat) { ?>
		 _sf_async_config.authors = <?php echo wp_json_encode( $cb_configs["author"] ); ?>;
			_sf_async_config.sections = <?php echo wp_json_encode( $cb_configs["sections"] ); ?>;
		<?php }

}

function add_chartbeat_footer() {
	$user_id = get_option('chartbeat_userid');
	if ($user_id) {

		// if visitor is admin AND tracking is off, do not load chartbeat
		if ( current_user_can( 'manage_options') && get_option('chartbeat_trackadmins') == 0)
			return $analytics ;
		
		?>
		<script type="text/javascript">
			<?php
				echo add_chartbeat_config();
				$default_chartbeat_url = "//static.chartbeat.com/js/chartbeat.js";
				$chartbeat_url = apply_filters( 'chartbeat_url', $default_chartbeat_url );
			?>
			(function(){
			        function loadChartbeat() {
					window._sf_endpt=(new Date()).getTime();
					var e = document.createElement('script');
					e.setAttribute('language', 'javascript');
					e.setAttribute('type', 'text/javascript');
					e.setAttribute('src', <?php echo wp_json_encode( $chartbeat_url ); ?> );
					document.body.appendChild(e);
				  }
				  var oldonload = window.onload;
				  window.onload = (typeof window.onload != 'function') ?
					 loadChartbeat : function() { try { oldonload(); } catch (e) { loadChartbeat(); throw e} loadChartbeat(); };
				})();
		</script>
<?php
	}
}


function chartbeat_amp_add_analytics( $analytics ) {
  if ( ! is_array( $analytics ) ) {
      $analytics = array();
  }
	$user_id = get_option('chartbeat_userid');
	if ($user_id) {
		// if visitor is admin AND tracking is off, do not load chartbeat
		if ( current_user_can( 'manage_options') && get_option('chartbeat_trackadmins') == 0)
			return $analytics ;

		$cb_configs = chartbeat_configs(); 

		$analytics['chartbeat'] = array(
	    'type' => 'chartbeat',
	    'attributes' => array(),
	    'config_data' => array(
	        'vars' => array(
	            'uid' => wp_json_encode( $cb_configs["uid"] ),
	            'domain' => wp_json_encode( $cb_configs["domain"] ),
	        )
	    ),
	  );

	  $enable_newsbeat = get_option('chartbeat_enable_newsbeat');
		if ($enable_newsbeat) { 
			$analytics['chartbeat']['config_data']['vars']['authors'] = wp_json_encode( $cb_configs['author'] );
			$analytics['chartbeat']['config_data']['vars']['sections'] = wp_json_encode( $cb_configs['sections'] );
		}
	}
	return $analytics;
}

add_filter( 'amp_post_template_analytics', 'chartbeat_amp_add_analytics' );

function chartbeat_fbia_analytics( $analytics ) {
	$user_id = get_option('chartbeat_userid');
	if ($user_id) {
		// if visitor is admin AND tracking is off, do not load chartbeat
		if ( current_user_can( 'manage_options') && get_option('chartbeat_trackadmins') == 0)
			return $analytics ;
	?>
	<figure class="op-tracker">
    <iframe>
			<script type="text/javascript">
			<?php echo add_chartbeat_config(); ?>
			window._sf_endpt=(new Date()).getTime();
			</script>
			<script defer src="//static.chartbeat.com/js/chartbeat_fia.js"></script>
    </iframe>
</figure>
	<?php
	}
}

add_action( 'instant_articles_article_header', 'chartbeat_fbia_analytics' );

class Chartbeat_Widget extends WP_Widget {

	function __construct() {
        parent::__construct('chartbeat_widget', 'Chartbeat Widget',array( 'description' => __('Display your site\'s top pages')));
        
        if ( is_active_widget(false,false,$this->id_base,true) || is_admin() ) {
        	wp_enqueue_script( 'chartbeat_topwidget', plugins_url('media/topwidget.compiled.js', __FILE__) );
        	wp_localize_script( 'chartbeat_topwidget', 'cbproxy', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),
        		'cbnonce' => wp_create_nonce( 'cbproxy-nonce' ) ) );
        }
    }

	function widget( $args, $instance ) {
		extract( $args );
		echo $before_widget;
		
		$api_key = get_option( 'chartbeat_apikey' );
		$widget_config = get_option('chartbeat_widgetconfig');
		
		if ( $api_key ) : ?>
		<div id="cb_top_pages"></div>
		<script type="text/javascript">
		var options = { };
		new CBTopPagesWidget( <?php echo json_encode(get_option('chartbeat_widgetconfig')); ?> );
		</script>
		<?php
		endif;
		echo $after_widget;
	}
}

// Add proxy for Chartbeat API requests
add_action( 'wp_ajax_nopriv_cbproxy-submit', 'cbproxy_submit' );
add_action( 'wp_ajax_cbproxy-submit', 'cbproxy_submit' );

function cbproxy_submit() {
	// check nonce
	$nonce = $_GET['cbnonce'];
	if ( ! wp_verify_nonce( $nonce, 'cbproxy-nonce' ) ) die ( 'cbproxy-nonce failed!');
	$domain = apply_filters( 'chartbeat_config_domain', chartbeat_get_display_url (get_option('home')) );
	$url = 'https://api.chartbeat.com';
	$url .= $_GET['url'];
	$url .= '&host=' . rawurlencode( chartbeat_get_display_url(wp_json_encode( $domain )) ) .'&apikey=' . rawurlencode(get_option('chartbeat_apikey'));
	$transient = 'cbproxy_' . md5($url);
	header( 'Content-Type: application/jsonp' );
	$response = get_transient( $transient );
	if ( !$response ) {
		$get = wp_remote_get( $url , array( 'timeout' => 3 ) );
		if( is_wp_error( $response ) ) {
			$response = json_encode( array( 'error' => $get->get_error_message() ) );
		} else {
			$response = wp_remote_retrieve_body($get);
		}
		set_transient($transient,$response,5);
	}
	
	echo htmlspecialchars_decode( wp_json_encode( $response ) );
	exit;
}

// Dashboard Widget
function chartbeat_widget_init() {
	register_widget( 'Chartbeat_Widget' );
}

add_action('widgets_init', 'chartbeat_widget_init');

function chartbeat_get_display_url( $url ){
	return strtok(preg_replace("/(https?:\/\/)?(www\.)?/i","",$url),"/");
}

function chartbeat_dashboard_widget_function() {
	?>
	<div id="chartbeatGauge"></div>
	<div id="chartbeatRefsTable" class="chartbeatWidget">
		<table id="chartbeatLinks" class="chartbeatTable">
			<thead>
				<tr>
					<th colspan=2 class="chartbeatLabel">Top Referrers</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
		<table id="chartbeatSearch" class="chartbeatTable">
			<thead>
				<tr>
					<th colspan=2 class="chartbeatLabel">Top Search</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
		<div class="clear"></div>
	</div>
	<div id="chartbeatGraph" class="chartbeatWidget clear">
		<div class="chartbeatLabel">Visits - Last 3 Day</div>
		<div id="chartbeatHist">
			<div id="annotations"></div>
		</div>
	</div>
	<script type="text/javascript">
	<?php add_filter( 'posts_where', 'chartbeat_filter_where_last_three_days' ); ?>
	var events = [];
	// Get published post Events 
	<?php
	$args = array( 'post_type' => array( 'post' ),'post_status' => 'publish', 'orderby' => 'date', 'order' => 'ASC' );
	$the_query = new WP_Query( $args );
	$domain = apply_filters( 'chartbeat_config_domain', chartbeat_get_display_url (get_option('home')) );
	while ( $the_query->have_posts() ) : $the_query->the_post(); 
		$tstamp = get_the_time('Y,n-1,j,G,i');
		if ($tstamps[$tstamp])
			continue; 

		$tstamps[$tstamp] = true;
		$category = get_the_category();
		if($category[0])
			$category_link = wpcom_vip_get_term_link($category[0]->cat_ID, 'category' );

		?>var ev = {domain:'<?php echo esc_js( chartbeat_get_display_url( $domain ) );?>',title:'<?php echo esc_js( get_the_title() ); ?>',
	  	value:'<?php echo esc_js( chartbeat_get_display_url( $category_link ) ); ?>', group_name:'<?php echo esc_js( chartbeat_get_display_url( get_page_link() ) ); ?>',
	  	t: new Date(<?php echo the_time('Y,n-1,j,G,i'); ?>).getTime()/1000,group_type:'page',num_referrers:10,id:'<?php echo esc_js( get_the_ID() ); ?>',type:'wp',data:{action_type:"create"}};
		events.push(ev);
	<?php
	endwhile;
	wp_reset_postdata();?>

	// Get revisions
	<?php
	$args = array( 'post_type' => array( 'revision' ), 'post_status' => 'inherit', 'orderby' => 'date', 'order' => 'ASC' );
	$the_query = new WP_Query( $args );
	while( $the_query->have_posts() ) : $the_query->the_post();
		$tstamp = get_the_time('Y,n-1,j,G,i');
		if ($tstamps[$tstamp])
			continue;

		$tstamps[$tstamp] = true;
		$category = get_the_category();
		if($category[0])
			$category_link = wpcom_vip_get_term_link($category[0]->cat_ID, 'category' );

		?>var ev = {domain:'<?php echo esc_js( chartbeat_get_display_url( $domain ) ); ?>',title:'<?php echo esc_js( get_the_title() ); ?>',
	  	value:'<?php echo esc_js( chartbeat_get_display_url( $category_link ) ); ?>',group_name:'<?php echo esc_js( chartbeat_get_display_url( get_page_link() ) ); ?>',
	  	t: new Date(<?php echo the_time('Y,n-1,j,G,i'); ?>).getTime()/1000,group_type:'page',num_referrers:10,id:'<?php echo esc_js( get_the_ID() ); ?>',type:'wp',data:{action_type:"update"}};
		events.push(ev);
	<?php
	endwhile; 
	wp_reset_postdata();

	remove_filter( 'posts_where', 'chartbeat_filter_where_last_three_days' );
	?>

	function loadChartBeatWidgets(){
		new CBDashboard('chartbeatGauge','chartbeatRefsTable','chartbeatHist',200,"<?php echo esc_js( chartbeat_get_display_url( $domain ) ); ?>","<?php echo esc_js( get_option('chartbeat_apikey') ); ?>",events);
	};
	
	var currOnload = window.onload;
	window.onload = (typeof window.onload != 'function') ? loadChartBeatWidgets : function() { oldonload(); loadChartBeatWidgets(); };
	</script>
	<?php
}

// Create a new filtering function that will add our where clause to the query
function chartbeat_filter_where_last_three_days( $where = '' ) {
	global $wpdb;
	$where .= $wpdb->prepare( " AND $wpdb->posts.post_modified > %s", date( 'Y-m-d', strtotime( '-3 days' ) ) );
	return $where;
}

function chartbeat_add_dashboard_widgets() {
	// Don't add widgets if we haven't set up Chartbeat yet
	if ( ! get_option('chartbeat_userid') )
		return;

	wp_enqueue_style( 'cbplugin_css' );
	wp_enqueue_script( 'cbdashboard' );
	wp_add_dashboard_widget('chartbeat_dashboard_widget', 'Chartbeat', 'chartbeat_dashboard_widget_function');
}

function chartbeat_plugin_admin_init() {
	wp_register_style('cbplugin_css',plugins_url('media/cb_plugin.css', __FILE__));

	wp_register_script( 'cbdashboard',plugins_url('media/cbdashboard.compiled.js', __FILE__));
}

add_action('wp_dashboard_setup', 'chartbeat_add_dashboard_widgets' );
add_action( 'admin_init', 'chartbeat_plugin_admin_init' );

// Add Column to Posts Manager
add_filter('manage_posts_columns', 'chartbeat_columns');
function chartbeat_columns($defaults) {
	if ( ! get_option('chartbeat_userid') )
		return $defaults;

	$defaults['cb_visits'] = __('Active Visits');
	return $defaults;
}
add_action('manage_posts_custom_column', 'chartbeat_custom_columns', 10, 2);

function chartbeat_custom_columns($column_name, $id) {
	$domain = apply_filters( 'chartbeat_config_domain', chartbeat_get_display_url (get_option('home')) );
	if( $column_name == 'cb_visits' ) {
		$post_url = parse_url(get_permalink( $id ));
		$json_url = add_query_arg( array(
			'host' => chartbeat_get_display_url( $domain ),
			'apikey' => get_option('chartbeat_apikey'),
			'path' => urlencode( $post_url["path"] ),
		), '//api.chartbeat.com/live/quickstats/' );
		?>
	
		<script type="text/javascript">
		jQuery.getJSON('<?php echo esc_js( $json_url ); ?>',
			function(data) {
				if ( !data.visits ) data.visits = 0;
				jQuery('#post-<?php echo absint( $id ); ?> .cb_visits').append(data.visits);
			}
		);
		</script>
		<?php
	}
}

// Returns URL for asset on the static.chartbeat domain
function chartbeat_get_static_asset_url( $path = '' ) {
	$domain = (is_ssl() ? 'https' : 'http') . '://static.chartbeat.com/';
	return $domain . ltrim( $path, '/' );
}

// If admin register settings on page that have been saved
// if not, add content to wp_head and wp_footer.
if ( is_admin() ){
	add_action( 'admin_init', 'chartbeat_register_settings' );
}else {
	add_action('wp_head', 'add_chartbeat_head');
	add_action('wp_footer', 'add_chartbeat_footer');
}

?>

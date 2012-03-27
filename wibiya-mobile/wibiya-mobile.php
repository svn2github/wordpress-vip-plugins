<?php

if (is_file(WP_CONTENT_DIR . '/plugins/vip-do-not-include-on-wpcom/is-mobile.php')) {
    require_once(WP_CONTENT_DIR . '/plugins/vip-do-not-include-on-wpcom/is-mobile.php');
}
/*
  Plugin Name: Mobilize with Wibiya
  Plugin URI: http://wibiya.conduit.com
  Description: Add mobile presentation for VIP wordpress sites
  Version: 1.0
  Author: Wibiya
  Author URI: http://wibiya.conduit.com
 */

/*
  Copyright 2012 Wibiya <gnot [at] g-loaded.eu>, wibiya.conduit.com

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

define("__POLICY_PLUGIN_INSTALLATION__", 0);
define("__POLICY_PENDING_USER__", 1);
define("__POLICY_INACTIVE_USER__", 2);
define("__POLICY_DISABLE_USER__", 3);
define("__POLICY_ACTIVE_USER__", 4);
define("__POLICY_ACTIVE_REGISTER_USER__", 5);
define("__LIVEPREVIEW_URI__", "http://wibiya.conduit.com/mobile/wp_mobile.php?livepreview=1&domain=" . base64_encode(home_url()));
define("__WIBIYA_ADMIN_PANEL__", "http://wibiya.conduit.com/mobile/mobile_studio_general.php");
define("__WIBIYA_REGISTER_PAGE__", "http://wibiya.conduit.com/mobile/wp_mobile.php?register=1");
define("__WIBIYA_CURL_URI__", "http://wibiya.conduit.com/mobile/wp_mobile.php");
define("__WIBIYA_MOBILE_URL__", plugins_url("/", __FILE__));

/*
  Admin Panel
 */

function ap_wibiya_mobile()
{
    add_options_page(__('Wibiya Mobile Options', 'wibiya-mobile'), __('Wibiya Mobile', 'wibiya-mobile'), 'administrator', 'ap_wibiya_mobile_options', 'ap_wibiya_mobile_options_page');
}

function ap_wibiya_mobile_options_page()
{
    echo "<br />";
    echo "<br />"; 
    switch ($GLOBALS['ap_wibiya_mobile_policy']) {
        case __POLICY_ACTIVE_USER__ :
            if (isset($_POST['ap_wibiya_mobile_deactivate'])) {
                if (function_exists('current_user_can') && !current_user_can('manage_options')) {
                    //log_app('function', 'ap_wibiya_mobile_options_page() __POLICY_ACTIVE_USER__  invalide form referer');
                    die(_e('Hacker?', 'Wibiya'));
                }
                if (function_exists('check_admin_referer')) {
                    check_admin_referer('ap_wibiya_mobile_admin_form');
					ap_wibiya_mobile_theme_enable();
                    ap_wibiya_mobile_update_policy(__POLICY_DISABLE_USER__);
                    $params['action'] = 'disable_site';
                    $response = ap_wibiya_mobile_curl_request($params);
                }
            }
            if (!$GLOBALS['ap_wibiya_mobile_register']) {
                if (isset($_GET['registration']) && $_GET['registration'] == 1 && isset($_GET['code']) && ($_GET['code'] == ap_wibiya_mobile_make_hash(home_url()))) {
                    $GLOBALS['ap_wibiya_mobile_register'] = 1;
					update_option('ap_wibiya_mobile_register', 1);
					update_option('ap_wibiya_mobile_url', base64_decode($_GET['murl']));
					echo "<script>myRef = window.open('".__WIBIYA_ADMIN_PANEL__."','mywin', 'left=20,top=20,width=1200px,height=800px,toolbar=1,resizable=0'); myRef.focus();</script>";
                }
            }
            break;
        case __POLICY_INACTIVE_USER__ :
        case __POLICY_DISABLE_USER__ :
            if (isset($_POST['ap_wibiya_mobile_activate'])) {
                if (function_exists('current_user_can') && !current_user_can('manage_options')) {
                    //log_app('function', 'ap_wibiya_mobile_options_page() __POLICY_DISABLE_USER__  invalide form referer');
                    die(_e('Hacker?', 'Wibiya'));
                }
                if (function_exists('check_admin_referer')) {
                    check_admin_referer('ap_wibiya_mobile_admin_form');
					ap_wibiya_mobile_theme_disable();
                    ap_wibiya_mobile_update_policy(__POLICY_ACTIVE_USER__);
                    $params['action'] = 'active_site';
                    $response = ap_wibiya_mobile_curl_request($params);
                }
            }
            break;
        default:
			if(($GLOBALS['ap_wibiya_mobile_policy']==__POLICY_PENDING_USER__||$GLOBALS['ap_wibiya_mobile_policy']==__POLICY_PLUGIN_INSTALLATION__)&&isset($_GET['secondary_email'])&&(is_email($_GET['secondary_email'])))
			{
				update_option('ap_wibiya_mobile_secondary_email', is_email($_GET['secondary_email']));
			}
            ap_wibiya_mobile_check_site();
            break;
    }
	if(($GLOBALS['ap_wibiya_mobile_policy']>=__POLICY_DISABLE_USER__) && ($GLOBALS['ap_wibiya_mobile_register']!=1))
	{ ap_wibiya_check_recovery(); }
    ap_wibiya_mobile_show_livepreview();
}

function ap_wibiya_mobile_show_livepreview()
{
    switch ($GLOBALS['ap_wibiya_mobile_policy']) {
        case __POLICY_INACTIVE_USER__ :
            ap_wibiya_mobile_show_livepreview_inactive();
            break;
        case __POLICY_ACTIVE_USER__ :
            if (!$GLOBALS['ap_wibiya_mobile_register']) {
                ap_wibiya_mobile_show_livepreview_active();
            } else {
                ap_wibiya_mobile_show_livepreview_register();
            }
            break;
        case __POLICY_DISABLE_USER__ :
            ap_wibiya_mobile_show_livepreview_disable();
            break;
        default:
            ap_wibiya_mobile_show_livepreview_panding();
            break;
    }
}
function ap_wibiya_check_recovery()
{
	$params['action'] = 'check_recovery';
	$params['code'] = ap_wibiya_mobile_make_hash(home_url());
    $params['url'] = home_url();
    $response = wp_remote_post(__WIBIYA_CURL_URI__, array(
            'method' => 'POST',
            'timeout' => 5,
            'redirection' => 1,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array('msg' => json_encode($params)),
            'cookies' => array()
        )
    );
    if (is_wp_error($response) || !isset($response["body"])) {
        //log_app('function', 'ap_wibiya_mobile_curl_request() curl to wibiya bad response ');
        $response = false;
    }
	$responseRec = ap_wibiya_mobile_parce_responce($response["body"]);
	if($responseRec['register']==1&&(!empty($responseRec['url'])))
	{
		$GLOBALS['ap_wibiya_mobile_register'] = 1;
		update_option('ap_wibiya_mobile_register', 1);
		update_option('ap_wibiya_mobile_url', base64_decode($_GET['url']));
	}
}
function ap_wibiya_mobile_update_policy($policy)
{
    $GLOBALS['ap_wibiya_mobile_policy'] = $policy;
    update_option('ap_wibiya_mobile_policy', $policy);
}

function ap_wibiya_mobile_using_ie()
{
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $ub = False;
    if (preg_match('/MSIE/i', $u_agent)) {
        $ub = True;
    }
    return $ub;
}

function ap_wibiya_mobile_header()
{
    wp_enqueue_style('wibiya-mobile-css', __WIBIYA_MOBILE_URL__ . 'views/css/screen.css');
    if (ap_wibiya_mobile_using_ie()) {
        wp_enqueue_style('wibiya-mobile-css', __WIBIYA_MOBILE_URL__ . 'views/css/ie.css');
    }
    wp_enqueue_script('jquery');
}

function ap_wibiya_mobile_show_livepreview_panding()
{
    require('views/pending.php');
}

function ap_wibiya_mobile_show_livepreview_inactive()
{
	require('views/inactive.php');
}

function ap_wibiya_mobile_show_livepreview_active()
{
	require('views/active.php');
}

function ap_wibiya_mobile_show_livepreview_disable()
{
    require('views/disable.php');
}

function ap_wibiya_mobile_show_livepreview_register()
{
    require('views/register.php');
}

function ap_wibiya_mobile_set_plugin_var()
{
    $GLOBALS['ap_wibiya_mobile_install'] = get_option('ap_wibiya_mobile_install');
    $GLOBALS['ap_wibiya_mobile_policy'] = get_option('ap_wibiya_mobile_policy');
    $GLOBALS['ap_wibiya_mobile_register'] = get_option('ap_wibiya_mobile_register');
    $GLOBALS['blogname'] = get_option('blogname');
    $GLOBALS['admin_email'] = get_option('admin_email');
}

function ap_wibiya_mobile_make_hash($data)
{
    return hash('ripemd160', $data);
}

function ap_wibiya_mobile_curl_request($params)
{
    $params['code'] = ap_wibiya_mobile_make_hash(home_url());
    $params['url'] = home_url();
    $response = wp_remote_post(__WIBIYA_CURL_URI__, array(
            'method' => 'POST',
            'timeout' => 5,
            'redirection' => 1,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array('msg' => json_encode($params)),
            'cookies' => array()
        )
    );
    if (is_wp_error($response) || !isset($response["body"])) {
        //log_app('function', 'ap_wibiya_mobile_curl_request() curl to wibiya bad response ');
        $response = false;
    }
    return $response["body"];
}

function ap_wibiya_mobile_check_site()
{
    $params['action'] = 'check_site';
    $params['blogname'] = $GLOBALS['blogname'];
    $params['admin_email'] = $GLOBALS['admin_email'];
	$params['secondary_email'] = get_option('ap_wibiya_mobile_secondary_email');
	$params['admin_url']=admin_url();
    $response = ap_wibiya_mobile_curl_request($params);
    if ($response) {
        $response = ap_wibiya_mobile_parce_responce($response);
        if (($GLOBALS['ap_wibiya_mobile_policy'] == __POLICY_PLUGIN_INSTALLATION__ || $GLOBALS['ap_wibiya_mobile_policy'] == __POLICY_PENDING_USER__)
            && $response['url'] != "" && intval($response['policy']) == __POLICY_INACTIVE_USER__
        ) {
            ap_wibiya_mobile_update_policy(__POLICY_INACTIVE_USER__);
            update_option('ap_wibiya_mobile_url', base64_decode($response['url']));
            return true;
        }
        if (($GLOBALS['ap_wibiya_mobile_policy'] == __POLICY_PLUGIN_INSTALLATION__) && ($response['url'] != "" && intval($response['policy']) == __POLICY_PENDING_USER__)) {
            ap_wibiya_mobile_update_policy(__POLICY_PENDING_USER__);
        }
    }
    return false;
}

function ap_wibiya_mobile_parce_responce($str)
{
    $strAr = explode(";", $str);
    $retAr = array();
    foreach ($strAr as $val) {
	
        if ($val) {
            $tmpAr = explode(":", $val);
            if (isset($tmpAr[0]) && isset($tmpAr[1])) {
                $retAr[$tmpAr[0]] = $tmpAr[1];
            }
        }
    }
    return $retAr;
}

function ap_wibiya_mobile_install_plugin()
{
    add_option('ap_wibiya_mobile_install', '4', '', 'yes');
    update_option('ap_wibiya_mobile_install', 1);
    add_option('ap_wibiya_mobile_policy', '16', '', 'yes');
    ap_wibiya_mobile_update_policy(__POLICY_PLUGIN_INSTALLATION__);
    add_option('ap_wibiya_mobile_url', '4000', '', 'no');
    update_option('ap_wibiya_mobile_url', '');
    add_option('ap_wibiya_mobile_register', '4', '', 'yes');
    update_option('ap_wibiya_mobile_register', 0);
	add_option('ap_wibiya_mobile_secondary_email', '255', '', 'yes');
	$curr_user=wp_get_current_user();
	update_option('ap_wibiya_mobile_secondary_email', $curr_user->data->user_email);
    ap_wibiya_mobile_check_site();
	add_option('ap_wibiya_mobile_theme_state', '4', '2', 'yes');
	if(!function_exists('register_activation_hook'))
	{
		wp_redirect(admin_url()."options-general.php?page=ap_wibiya_mobile_options");
		exit;
	}
}

function ap_wibiya_mobile_uninstall_plugin()
{
    delete_option('ap_wibiya_mobile_install');
    delete_option('ap_wibiya_mobile_policy');
    delete_option('ap_wibiya_mobile_url');
    delete_option('ap_wibiya_mobile_register');
	delete_option('ap_wibiya_mobile_secondary_email');
	delete_option('ap_wibiya_mobile_theme_state');
	$params['action'] = 'uninstall_site';
    $response = ap_wibiya_mobile_curl_request($params);
	if(!function_exists('register_deactivation_hook'))
	{
		wp_redirect(admin_url());
		exit;
	}
}
function ap_wibiya_mobile_theme_disable()
{
	if($GLOBALS['ap_wibiya_mobile_policy']==__POLICY_INACTIVE_USER__ || $GLOBALS['ap_wibiya_mobile_policy']==__POLICY_DISABLE_USER__)
	{
		if (get_option( 'wp_mobile_disable' ) == 1 )
		{ 
			update_option('ap_wibiya_mobile_theme_state', 1); 	
		}
		elseif(get_option( 'wp_mobile_disable' ) == 0)
		{
			update_option('ap_wibiya_mobile_theme_state', 0);
			update_option('wp_mobile_disable',1);
		}		
		else
		{ 
			update_option('ap_wibiya_mobile_theme_state', '2'); 
			update_option('wp_mobile_disable',1);
		}
	}
}
function ap_wibiya_mobile_theme_enable()
{
	if ((get_option( 'ap_wibiya_mobile_theme_state' ) == 1)&&(get_option( 'wp_mobile_disable' ) != false))
	{ 
		update_option('wp_mobile_disable',1);	
	}
	elseif((get_option( 'ap_wibiya_mobile_theme_state' ) == 0)&&(get_option( 'wp_mobile_disable' ) != false))
	{
		update_option('wp_mobile_disable',0);
	}
	else
	{ 
		if (get_option( 'wp_mobile_disable' ) == 1 )
		{ 
			delete_option('wp_mobile_disable');
		}
	}
}
function ap_wibiya_mobile_is_mobile()
{
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/android|avantgo|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
        return true;
    }
    return false;
}

function wibiya_mobile_run()
{
    ap_wibiya_mobile_set_plugin_var();
    if (isset($_GET['remove']) && $_GET['remove'] == 1) {
        ap_wibiya_mobile_uninstall_plugin();
    }
    if (is_admin() && (!get_option('ap_wibiya_mobile_install')) && strstr($_SERVER['REQUEST_URI'], "page=ap_wibiya_mobile_options")) {
        ap_wibiya_mobile_install_plugin();
    }
    if (is_admin()) {
        add_action('admin_menu', 'ap_wibiya_mobile');
        add_action('admin_head', 'ap_wibiya_mobile_header');
    } else {

	// We shouldn't be redirecting XML-RPC requests
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
		return;

        $is_mobile = false;
        if ((function_exists('is_mobile') && is_mobile('any', false)) || (ap_wibiya_mobile_is_mobile() == true)) {
            $is_mobile = true;
        }
        if ($is_mobile && isset($GLOBALS['ap_wibiya_mobile_install']) && $GLOBALS['ap_wibiya_mobile_install'] == 1 && $GLOBALS['ap_wibiya_mobile_policy'] >= __POLICY_ACTIVE_USER__ && get_option('ap_wibiya_mobile_url')&&(!isset($_GET['noredirect']))&&(!isset($_GET['feed']))) {
			if(is_home())
			{  $mobile_url=get_option('ap_wibiya_mobile_url').'?url=&cms=wordpress&timestamp='.time();    }
			else{ $mobile_url=get_option('ap_wibiya_mobile_url').'?url='.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&cms=wordpress&timestamp='.time();  }	
			wp_redirect($mobile_url);
            exit;
        }
    }
}
if(function_exists('register_activation_hook'))
{ register_activation_hook( __FILE__, 'ap_wibiya_mobile_install_plugin'); }
if(function_exists('register_deactivation_hook'))
{ register_deactivation_hook( __FILE__, 'ap_wibiya_mobile_uninstall_plugin'); }
add_action('init', 'wibiya_mobile_run');

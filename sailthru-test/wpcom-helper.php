<?php

/***
This file is called by vip-init and can be used to run code that is specific to the VIP platform,
for more information about developing for the VIP platform check out

http://vip.wordpress.com/documentation/development-environment/#vip-init-php
*/

// Don't track logins on the VIP platform. Change made at the request of Automattic
remove_action( 'wp_login', 'sailthru_user_login', 10 );

global $Sailthru_Subscribe_Widget;
remove_filter( 'sailthru_user_registration_enable', array($Sailthru_Subscribe_Widget, 'create_wp_account') );


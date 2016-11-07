<?php

class NDN_Plugin_Admin_Test extends WP_UnitTestCase {


  protected $_ndn_admin;
  protected $_ndn;

  /**
   * @runInSeparateProcess
   */
  public function create_client_info_headers()
  {
    header('login-submission : 1');
    header('username : testuser');
    header('password : password1');
  }

  function setUp() {
    $this->_ndn_admin = new NDN_Plugin_Admin('ndn_plugin', '0.1.3');
  }

  /**
    * @covers create_plugin_menu
  */
  function testCreatePluginMenu() {
    // Set Base URL
    update_option( 'siteurl', 'http://example.com' );
    // Create Instance of NDN_Admin class
    $ndn_admin = $this->_ndn_admin;
    // Create Menus and Submenus
    $ndn_admin->create_plugin_menu();
    // $ndn_admin->register_custom_modal_page();

    // Expectation of site URL
    $expected['ndn-plugin-settings'] = 'http://example.com/wp-admin/admin.php?page=ndn-plugin-settings';
    // $expected['admin_page_ndn-video-search?'] = 'http://example.com/wp-admin/admin.php?page=ndn-plugin-settings';

    // Test each expectation of site urls
    foreach ($expected as $name => $value) {
      $this->assertEquals( $value, menu_page_url( $name, false ) );
    }
  }

  /**
   * @covers submit_client_information
   */
  // function test_submit_client_information() {
  //   $ndn_admin = $this->_ndn_admin;
  //
  //   $_POST['login-submission'] = '1';
  //   $_POST['username'] = 'testuser';
  //   $_POST['password'] = 'defaultpassword';
  //
  //   $ndn_admin->submit_client_information();
  //   // Error Cannot modify header information - headers already sent by (output started at /private/tmp/wordpress-tests-lib/includes/bootstrap.php:54)
  //
  // }

  /**
  * @covers check_user_token
  */
  function test_check_user_token_with_no_token () {
    $ndn_admin = $this->_ndn_admin;
    $ndn_admin::check_user_token();
    $this->assertFalse(
      $ndn_admin::$has_token
    );
  }

  /**
  * @covers check_user_token
  */
  function test_check_user_token_with_token () {
    // add token requirements
    add_option( 'ndn_client_id', '12345', NULL, 'no' );
    add_option( 'ndn_client_secret', 'fl324jnfnusv9seb@fdvllk', NULL, 'no' );
    add_option( 'ndn_refresh_token', 'if4u3bf43iu23h8f7', NULL, 'no' );

    $ndn_admin = $this->_ndn_admin;
    $ndn_admin::check_user_token();
    $this->assertTrue(
      $ndn_admin::$has_token
    );
  }

  /**
   * @covers ndn_plugin_hook_embed
   */
  // function test_ndn_plugin_hook_embed() {
  //   $ndn_admin = $this->_ndn_admin;
  //   $output = $ndn_admin->ndn_plugin_hook_embed();
  //   $this->assertRegexp('/\<script type\=\"text\/javascript\" src=\"\/\/launch\.newsinc\.com\/js\/embed\.js\" id\=\"\_nw2e\-js\"\>\<\/script\>/', $output);
  // }

  /**
   * @covers save_plugin_settings
   */
  function test_save_plugin_settings() {
    $ndn_admin = $this->_ndn_admin;

    $this->assertFalse(
      $ndn_admin::$configured
    );

    $_POST['ndn-save-settings'] = '1';
    $_POST['ndn-plugin-default-tracking-group'] = '10557';
    $_POST['ndn-plugin-default-div-class'] = 'default-class-div';
    $_POST['ndn-plugin-default-site-section'] = 'default_site_section';
    $_POST['ndn-plugin-default-width'] = '425';
    $_POST['ndn-default-responsive'] = '1';
    $_POST['ndn-default-video-position'] = 'center';
    $_POST['ndn-plugin-default-start-behavior'] = 'click_to_play';

    $ndn_admin::save_plugin_settings();
    $this->assertEquals( get_option( 'ndn_default_tracking_group' ), '10557' );
    $this->assertEquals( get_option( 'ndn_default_div_class' ), 'default-class-div' );
    $this->assertEquals( get_option( 'ndn_default_site_section' ), 'default_site_section' );
    $this->assertEquals( get_option( 'ndn_default_width' ), '425' );
    $this->assertEquals( get_option( 'ndn_default_responsive' ), '1' );
    $this->assertEquals( get_option( 'ndn_default_video_position' ), 'center' );
    $this->assertEquals( get_option( 'ndn_default_start_behavior' ), 'click_to_play' );

    $this->assertTrue(
      $ndn_admin::$configured
    );

  }

  /**
   * @covers submit_search_query
   */
  // function test_submit_search_query(){
  //   $_POST['search-action'] = '1';
  //   $_POST['query'] = 'test';
  //
  //   $ndn_admin = $this->_ndn_admin;
  //   $ndn_admin->submit_search_query();
  //
  //   ERROR Trouble with mocking API
  // }
}

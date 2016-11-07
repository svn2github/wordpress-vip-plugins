<?php

// class NDN_Plugin_Init_Test extends WP_UnitTestCase
// {
//   /**
//    * @covers run_ndn_plugin
//    */
//   function test_run() {
//
//     $ndn_plugin = $this->getMockBuilder( 'NDN_Plugin' )
//       ->setMethods( array( 'run' ) )
//       ->getMock();
//     $ndn_plugin->expects( $this->once() )
//       ->method( 'run' );
//
//     run_ndn_plugin();
//   }
// }

class NDN_Plugin_Test extends WP_UnitTestCase {

  protected $ndn_plugin;

  public function setUp() {
    $this->_ndn_plugin = new NDN_Plugin;
  }

  /**
    * @covers run_ndn_plugin
  */
  function test_run() {
    // $ndn_plugin = new NDN_Plugin;
    // TODO This Method doesn't work as intended. Test re-write necessary

    $mockedMethods = array('run');

    $mock = $this->getMock('NDN_Plugin_Loader', $mockedMethods);
    $mock->expects($this->any())
      ->method('run')
      ->will($this->returnValue(null));

    $this->_ndn_plugin->run();

    // $ndn_plugin->run();
  }

  /**
    * @covers get_plugin_name
  */
  function test_get_plugin_name() {
    // $ndn_plugin = new NDN_Plugin;
    $plugin_name = $this->_ndn_plugin->get_plugin_name();
    $this->assertEquals($plugin_name, 'ndn_plugin');
  }

  /**
    * @covers get_version
  */
  function test_get_version() {
    // $ndn_plugin = new NDN_Plugin;
    $version = $this->_ndn_plugin->get_version();
    $this->assertRegExp('/\d+/', $version);
  }

  /**
    * @covers get_loader
  */
  function test_get_loader() {
    // $ndn_plugin = new NDN_Plugin;
    $loader = $this->_ndn_plugin->get_loader();
    $this->assertInstanceOf('NDN_Plugin_loader', $loader);
    $this->assertTrue(
      method_exists($loader, 'add_action'),
      'Class does not have method add_action'
    );
  }
}

<?php

require_once __DIR__ . '/../../includes/apple-push-api/class-api.php';
require_once __DIR__ . '/../../includes/apple-push-api/class-credentials.php';

use Apple_Push_API\API as API;
use Apple_Push_API\Credentials as Credentials;
use Apple_Push_API\MIME_Builder as MIME_Builder;
use Apple_Push_API\Request\Request_Exception as Request_Exception;

class MIME_Builder_Test extends WP_UnitTestCase {

	public function setup() {
		$this->builder = new MIME_Builder();
	}

	public function testAddJSON() {
		$eol      = "\r\n";
		$name     = 'some-name';
		$filename = 'article.json';
		$json     = '{"hello": "world"}';
		$size     = strlen( $json );

		$expected = '--' . $this->builder->boundary() . $eol .
			'Content-Type: application/json' . $eol .
			"Content-Disposition: form-data; name=$name; filename=$filename; size=$size" . $eol .
		 	$eol . $json . $eol;

		$this->assertEquals(
			$expected,
			$this->builder->add_json_string( $name, $filename, $json )
		);
	}

	public function testInvalidJSON() {
		$name     = 'some-name';
		$filename = 'article.json';
		$json     = '';

		$this->setExpectedException( 'Apple_Push_API\\Request\\Request_Exception', 'The attachment article.json could not be included in the request because it was empty.' );
		$this->builder->add_json_string( $name, $filename, $json );
	}

}



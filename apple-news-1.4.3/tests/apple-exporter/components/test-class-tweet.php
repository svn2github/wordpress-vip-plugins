<?php

require_once __DIR__ . '/class-component-testcase.php';

use Apple_Exporter\Components\Tweet as Tweet;

class Tweet_Test extends Component_TestCase {

	public function testInvalidMarkup() {
		$component = new Tweet( '<blockquote class="twitter-tweet" lang="en">Invalid content. No URL.</blockquote>',
			null, $this->settings, $this->styles, $this->layouts );

		$this->assertEquals( null, $component->to_array() );
	}

	public function testMatchesASingleURL() {
		$node = $this->build_node( 'https://twitter.com/gosukiwi/status/608069908044390400' );
		$this->assertNotNull( Tweet::node_matches( $node ) );
	}

	public function testGetsURLFromNewFormat() {
		$component = new Tweet( '<blockquote class="twitter-tweet"
			lang="en"><p lang="en" dir="ltr">Swift will be open source later this
			year, available for iOS, OS X, and Linux. <a
			href="http://t.co/yQhyzxukTn">http://t.co/yQhyzxukTn</a></p>&mdash;
		Federico Ramirez (@gosukiwi) <a
			href="https://twitter.com/gosukiwi/status/608069908044390400">June 9,
			2015</a></blockquote>', null, $this->settings, $this->styles, $this->layouts );

		$result = $component->to_array();
		$this->assertEquals( 'tweet', $result['role'] );
		$this->assertEquals( 'https://twitter.com/gosukiwi/status/608069908044390400', $result['URL'] );
	}

	public function testGetsURLFromOldFormat() {
		$component = new Tweet( '<blockquote class="twitter-tweet"
			lang="en">WordPress.com (@wordpressdotcom) <a
			href="http://twitter.com/#!/wordpressdotcom/status/204557548249026561"
			data-datetime="2012-05-21T13:01:34+00:00">May 21, 2012</a></blockquote>',
			null, $this->settings, $this->styles, $this->layouts );

		$result = $component->to_array();
		$this->assertEquals( 'tweet', $result['role'] );
		$this->assertEquals( 'https://twitter.com/wordpressdotcom/status/204557548249026561', $result['URL'] );
	}

	public function testGetUsingWWW() {
		$component = new Tweet( '<blockquote class="twitter-tweet"
			lang="en">WordPress.com (@wordpressdotcom) <a
			href="https://www.twitter.com/#!/wordpressdotcom/status/204557548249026561"
			data-datetime="2012-05-21T13:01:34+00:00">May 21, 2012</a></blockquote>',
			null, $this->settings, $this->styles, $this->layouts );

		$result = $component->to_array();
		$this->assertEquals( 'tweet', $result['role'] );
		$this->assertEquals( 'https://twitter.com/wordpressdotcom/status/204557548249026561', $result['URL'] );
	}

	public function testGetUsingStatuses() {
		$component = new Tweet( '<blockquote class="twitter-tweet"
			lang="en">WordPress.com (@wordpressdotcom) <a
			href="https://twitter.com/#!/wordpressdotcom/statuses/204557548249026561"
			data-datetime="2012-05-21T13:01:34+00:00">May 21, 2012</a></blockquote>',
			null, $this->settings, $this->styles, $this->layouts );

		$result = $component->to_array();
		$this->assertEquals( 'tweet', $result['role'] );
		$this->assertEquals( 'https://twitter.com/wordpressdotcom/status/204557548249026561', $result['URL'] );
	}

	public function testGetLastLink() {
		$component = new Tweet( '<blockquote class="twitter-tweet"
			lang="en"><p><a
			href="https://twitter.com/foo/status/1111">twitter.com/foo/status/1111</a></p>&mdash;
		<br />WordPress.com (@wordpressdotcom) <a
			href="http://twitter.com/#!/wordpressdotcom/status/123"
			data-datetime="2012-05-21T13:01:34+00:00">May 21, 2012</a></blockquote>',
			null, $this->settings, $this->styles, $this->layouts );

		$result = $component->to_array();
		$this->assertEquals( 'tweet', $result['role'] );
		$this->assertEquals( 'https://twitter.com/wordpressdotcom/status/123', $result['URL'] );
	}

	public function testFilter() {
		$component = new Tweet( '<blockquote class="twitter-tweet"
			lang="en"><p><a
			href="https://twitter.com/foo/status/1111">twitter.com/foo/status/1111</a></p>&mdash;
		<br />WordPress.com (@wordpressdotcom) <a
			href="http://twitter.com/#!/wordpressdotcom/status/123"
			data-datetime="2012-05-21T13:01:34+00:00">May 21, 2012</a></blockquote>',
			null, $this->settings, $this->styles, $this->layouts );

		add_filter( 'apple_news_tweet_json', function( $json ) {
			$json['URL'] = 'https://twitter.com/alleydev/status/123';
			return $json;
		} );

		$result = $component->to_array();
		$this->assertEquals( 'https://twitter.com/alleydev/status/123', $result['URL'] );
	}
}


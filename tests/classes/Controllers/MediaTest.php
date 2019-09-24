<?php

use DataSync\Controllers\Media;
use PHPUnit_Framework_TestCase;

class MediaTest extends PHPUnit_Framework_TestCase {

	public function test_adding_new_media_into_receiver() {

		$media = new Media();

		$expected = 'success';

		$this->assertEquals( $expected, $media->send_to_receiver() );
	}

}
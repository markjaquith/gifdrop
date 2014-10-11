<?php

class GifDrop_Test_Default_Options extends GifDrop_TestCase {
	function test_incrementing_option() {
		$this->assertEquals( '1', $this->plugin()->increment_id() ); // base36 output is a string
		$this->assertEquals( 2, get_option( 'gifdrop_filename_count' ) ); // test that the option incremented to 2
	}
	function test_plugin_options() {
		$this->assertEquals( 'gifs', $this->plugin()->get_option( 'path' ) );
		$this->assertEquals( true, $this->plugin()->get_option( 'created_page' ) );
	}
}

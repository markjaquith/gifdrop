<?php

class GifDrop_Test_Sanitizing extends GifDrop_TestCase {
	function test_unsandwich_slashes() {
		$data = array(
			'foo/bar/',
			'/foo/bar',
			'/foo/bar/',
			'///foo/bar///',
		);
		foreach ( $data as $datum ) {
			$this->assertEquals( 'foo/bar', $this->plugin()->unsandwich_slashes( $datum ) );
		}
	}

	function test_sanitize_path() {
		$data = array(
			'/FOO/bar/',
			'/FOO&/bar/',
			'/FOO^///bar//',
		);
		foreach ( $data as $datum ) {
			$this->assertEquals( 'foo/bar', $this->plugin()->sanitize_path( $datum ) );
		}
	}

	function test_sanitize_slug() {
		$data = array(
			'/foo/bar/' => 'foo-bar',
			'///foo///BAR///' => 'foo-bar',
			'' => 'gifdrop-on-site-root',
		);
		foreach ( $data as $datum => $expected ) {
			$this->assertEquals( $expected, $this->plugin()->sanitize_slug( $datum ) );
		}
	}
}

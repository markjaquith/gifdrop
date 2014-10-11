<?php

class GifDrop_Test_Path_Setting extends GifDrop_TestCase {
	function test_path_setting() {
		global $wpdb;
		$p = $this->plugin();
		$p->update_path( '/foo&///bar///' );
		$this->assertEquals( 'foo/bar', $p->get_option( 'path' ) );
		// Broken. Why?
		// $this->assertEquals( 1, $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts", $p->sanitize_slug( 'foo/bar' ) ) ) );
	}
}

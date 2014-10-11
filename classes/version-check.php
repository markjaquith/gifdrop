<?php
defined( 'WPINC' ) or die;

class GifDrop_Version_Check {
	private static $instance;
	const MINVERSION = '3.9';

	protected function __construct() {
		self::$instance = $this;
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			new self;
		}
		return self::$instance;
	}

	public function passes() {
		return version_compare( get_bloginfo( 'version' ), self::MINVERSION, '>=' );
	}

	public function plugins_loaded() {
		if ( ! $this->passes() ) {
			remove_action( 'init', array( GifDrop_Plugin::get_instance(), 'init' ) );
  		if ( current_user_can( 'activate_plugins' ) ) {
				add_action( 'admin_init', array( $this, 'admin_init' ) );
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			}
		}
	}

	public function admin_init() {
		deactivate_plugins( plugin_basename( dirname( dirname( __FILE__ ) ) . '/gifdrop.php' ) );
	}

	public function admin_notices() {
		echo '<div class="updated error"><p>' . sprintf( __('<strong>GifDrop</strong> requires WordPress %s or higher, and has thus been <strong>deactivated</strong>. Please update WordPress and then try again!', 'gifdrop' ), self::MINVERSION ) . '</p></div>';
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}

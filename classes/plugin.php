<?php
defined( 'WPINC' ) or die;

class GifDrop_Plugin {
	private static $instance;
	private $base;
	const OPTION = 'gifdrop';
	const NONCE = 'gifdrop_save';

	protected function __construct() {
		self::$instance = $this;
		$this->base = dirname( dirname( __FILE__ ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			new self;
		}
		return self::$instance;
	}

	public function init() {
		// Initialize translations
		load_plugin_textdomain( 'gifdrop', false, basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );

		// Hooks
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	public function admin_init() {
		add_action( 'admin_post_gifdrop-save', array( $this, 'save' ) );
	}

	public function admin_menu() {
		$hook = add_options_page( __( 'GifDrop Settings', 'gifdrop' ), __( 'GifDrop', 'gifdrop' ), 'manage_options', 'gifdrop', array( $this, 'admin_page' ) );
		add_action( 'load-' . $hook, array( $this, 'load' ) );
	}

	public function load() {
		if ( isset( $_GET['updated'] ) ) {
			add_action( 'admin_notices', array( $this, 'updated' ) );
		}

		add_action ( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function updated() {
		include( $this->base . '/templates/updated.php' );
	}

	public function admin_enqueue_scripts() {
		// Stub
	}

	public function admin_page() {
		include( $this->base . '/templates/admin-page.php' );
	}

	public function get_option( $key, $default = null ) {
		$option = get_option( self::OPTION );
		if ( ! is_array( $option ) || ! isset( $option[$key] ) ) {
			return $default;
		} else {
			return $option[$key];
		}
	}

	public function set_option( $key, $value ) {
		$option = get_option( self::OPTION );
		is_array( $option ) || $option = array();
		$option[$key] = $value;
		update_option( self::OPTION, $option );
	}

	public function admin_url() {
		return admin_url( 'options-general.php?page=gifdrop' );
	}

	public function save() {
		current_user_can( 'manage_options' ) || die;
		check_admin_referer( self::NONCE );
		$_post = stripslashes_deep( $_POST );
		// $doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		// Update logic here

		wp_redirect( $this->admin_url() . '&updated=true' );
		exit;
	}
}

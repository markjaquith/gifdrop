<?php
defined( 'WPINC' ) or die;

class GifDrop_Plugin {
	private static $instance;
	private $base;
	const OPTION = 'gifdrop';
	const NONCE = 'gifdrop_save';

	protected function __construct( $__FILE__ ) {
		self::$instance = $this;
		$this->__FILE__ = $__FILE__;
		$this->base = dirname( dirname( __FILE__ ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_filter( 'template_include', array( $this, 'template_include' ) );
	}

	public static function get_instance( $__FILE__ ) {
		if ( ! isset( self::$instance ) ) {
			new self( $__FILE__ );
		}
		return self::$instance;
	}

	protected function get_url() {
		return plugin_dir_url( $this->__FILE__ );
	}

	protected function get_path() {
		return plugin_dir_path( $this->__FILE__ );
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
		wp_enqueue_script( 'gifdrop-settings', $this->get_url() . 'js/admin.js', array( 'jquery', 'wp-backbone' ), '0.1' );
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

		$pages = $this->get_page_ids();
		$new_pages = isset( $_post['gifdrop_enabled'] ) ? array_map( 'intval', $_post['gifdrop_enabled'] ) : array();

		$remove = array_values( array_diff( $pages, $new_pages ) );
		$add = array_values( array_diff( $new_pages, $pages ) );

		foreach ( $remove as $post_id ) {
			delete_post_meta( $post_id, '_gifdrop_enabled' );
		}

		foreach ( $add as $post_id ) {
			update_post_meta( $post_id, '_gifdrop_enabled', 'enabled' );
		}

		wp_redirect( $this->admin_url() . '&updated=true' );
		exit;
	}

	protected function register_frontend_scripts() {
		wp_register_script( 'gifdrop-isotope', $this->get_url() . 'js/isotope.min.js', array(), '2.0.0' );
		wp_register_script( 'gifdrop', $this->get_url() . 'js/gifdrop.js', array( 'jquery', 'backbone', 'wp-backbone', 'wp-util', 'wp-plupload', 'gifdrop-isotope' ), '0.1' );
	}

	protected function only_some_attachment_fields( &$attachment ) {
		$img = wp_get_attachment_image_src( $attachment->ID, 'full' );
		$attachment = (object) array(
			'id' => $attachment->ID,
			'src' => $img[0],
			'width' => $img[1],
			'height' => $img[2],
		);
	}

	public function template_include( $template ) {
		if ( is_page() ) {
			if ( get_post_meta( get_queried_object_id(), '_gifdrop_enabled', true ) ) {
				$this->register_frontend_scripts();
				$images = get_posts( array(
					'post_parent' => get_queried_object_id(),
					'post_type'   => 'attachment',
					'orderby' => 'date',
					'order' => 'DESC',
				));
				array_walk( $images, array( $this, 'only_some_attachment_fields' ) );
				wp_localize_script( 'gifdrop', 'gifdropSettings', array(
					'id' => get_queried_object_id(),
					'attachments' => $images,
				));
				wp_plupload_default_settings();
				return $this->get_path() . '/templates/page.php';
			}
		}
		return $template;
	}

	protected function get_page_ids() {
		global $wpdb;
		$pages = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_gifdrop_enabled'" );
		$pages = $pages ? array_map( 'intval', $pages ) : array();
		return $pages;
	}

	protected function get_all_pages() {
		$all_pages = get_pages();
		$pages_out = array();
		foreach ( $all_pages as $page ) {
			$pages_out[] = (object) array(
				'id' => intval( $page->ID ),
				'title' => apply_filters( 'the_title', $page->post_title, $page->ID )
			);
		}
		return $pages_out;
	}
}

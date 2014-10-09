<?php
defined( 'WPINC' ) or die;

class GifDrop_Plugin {
	private static $instance;
	private $base;
	const OPTION = 'gifdrop';
	const NONCE = 'gifdrop_save';

	protected function __construct( $__FILE__ ) {
		$this->__FILE__ = $__FILE__;
		$this->base = dirname( dirname( __FILE__ ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_init', array( $this, 'change_upload_dir' ), 999 );
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'metadata_filter', ), 10, 2 );
		add_filter( 'image_size_names_choose', array( $this, 'image_size_names_choose' ) );
		add_filter( 'sanitize_file_name', array( $this, 'filename' ), 10, 2 );
		add_action( 'wp_ajax_gifdrop', array( $this, 'ajax' ) );
		add_action( 'root_rewrite_rules', array( $this, 'inject_rewrite_rules' ), 99 );
	}

	public static function get_instance( $__FILE__ = null ) {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( $__FILE__ );
		}
		return self::$instance;
	}

	public function get_url() {
		return plugin_dir_url( $this->__FILE__ );
	}

	public function get_path() {
		return plugin_dir_path( $this->__FILE__ );
	}

	public function init() {
		// Initialize translations
		load_plugin_textdomain( 'gifdrop', false, basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );

		// Register our CPT
		$this->register_cpt();

		// Hooks
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	protected function register_cpt() {
		$args = array(
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'query_var'           => 'gifdrop',
			'rewrite'             => false,
			'capability_type'     => 'page',
		);
		register_post_type( 'gifdrop', $args );

		// Create our "container" page on the fly
		$this->maybe_create_gifdrop();
	}

	protected function maybe_create_gifdrop() {
		if ( ! $this->get_option( 'created_page' ) ) {
			$path = 'gifs';
			$this->set_option( 'path', $path );
			wp_insert_post( array(
				'post_type' => 'gifdrop',
				'post_title' => $path,
				'post_name' => $path,
				'post_status' => 'publish',
			));
			$this->set_option( 'created_page', true );
			add_action( 'shutdown', 'flush_rewrite_rules' );
		}
	}

	public function sanitize_path( $path ) {
		return sanitize_title_with_dashes( $path );
	}

	public function inject_rewrite_rules( $rules ) {
		$rules[trailingslashit( $this->get_option( 'path' ) ) . '?$'] = 'index.php?&gifdrop=' . $this->sanitize_path( $this->get_option( 'path' ) );
		return $rules;
	}

	public function admin_init() {
		add_action( 'admin_post_gifdrop-save', array( $this, 'save' ) );
	}

	public static function create_upload_dir() {
		// fetch the base uploads dir
		$uploads = wp_upload_dir();
		// set our new directory
		$basedir = $uploads['basedir'] . '/gifdrop/';
		// check if folder exists. if not, make it
		if ( ! is_dir( $basedir ) ) {
			mkdir( $basedir );
			// set the CHMOD in case
			@chmod( $basedir, 0755 );
		}
	}

 	public function set_upload_dir( $upload ) {
 		$upload['subdir'] = '/gifdrop';
 		$upload['path']   = $upload['basedir'] . $upload['subdir'];
 		$upload['url']    = $upload['baseurl'] . $upload['subdir'];
 		return $upload;
 	}

	public function change_upload_dir() {
		global $pagenow;
		if ( ! empty( $_REQUEST['post_id'] ) && ( 'async-upload.php' === $pagenow ) ) {
			if ( $this->is_gifdrop_page( absint( $_REQUEST['post_id'] ) ) ) {
				$this->create_upload_dir();
				add_filter( 'upload_dir', array( $this, 'set_upload_dir' ) );
			}
		}
	}

	public function filename( $filename, $filename_raw ) {
		global $pagenow;
		if ( ! empty( $_REQUEST['post_id'] ) && ( 'async-upload.php' == $pagenow ) ) {
			if ( $this->is_gifdrop_page( absint( $_REQUEST['post_id'] ) ) ) {
				$info = pathinfo( $filename );
				$ext  = empty( $info['extension'] ) ? '' : '.' . $info['extension'];
				return $this->increment_id() . $ext;
			}
		}
	}

	public function admin_menu() {
		$hook = add_options_page( __( 'GifDrop Settings', 'gifdrop' ), __( 'GifDrop', 'gifdrop' ), 'manage_options', 'gifdrop', array( $this, 'admin_page' ) );
		add_action( 'load-' . $hook, array( $this, 'load' ) );
	}

	public function load() {
		if ( isset( $_GET['updated'] ) ) {
			add_action( 'admin_notices', array( $this, 'updated' ) );
			flush_rewrite_rules();
		}

		add_action ( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function updated() {
		include( $this->base . '/templates/updated.php' );
	}

	public function ajax() {
		$request = stripslashes_deep( $_REQUEST );
		if ( ! wp_verify_nonce( $request['_ajax_nonce'], 'gifdrop_' . $request['post_id'] ) ) {
			$this->json_error( __( 'Invalid nonce', 'gifdrop' ) );
		}
		switch( $_REQUEST['subaction'] ) {
			case 'update' :
				$model = json_decode( $request['model'] );
				$post = array(
					'ID' => $model->id,
					'post_title' => $model->title
				);
				if ( current_user_can( 'edit_post', $model->id ) ) {
					$success = wp_update_post( $post );
				} else {
					$this->json_error( __( 'You are not allowed to update this item', 'gifdrop' ) );
				}

				if ( $success ) {
					wp_send_json_success( array() );
				} else {
					$this->json_error( __( 'Update failure. Try again?', 'gifdrop' ) );
				}
				break;
			default:
				$this->json_error( __( 'Invalid subaction', 'gifdrop' ) );
		}
	}

	public function json_error( $message ) {
		status_header( 412 );
		wp_send_json_error( array( 'message' => $message ) );
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'gifdrop-settings', $this->get_url() . 'js/admin.js', array( 'jquery' ), '0.2' );
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
		global $wpdb;
		current_user_can( 'manage_options' ) || die;
		check_admin_referer( self::NONCE );
		$_post = stripslashes_deep( $_POST );

		$old_path = $this->get_option( 'path' );
		if ( $_post['gifdrop_path'] !== $old_path ) {
			$new_path = $_post['gifdrop_path'];
			$page_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'gifdrop'" );
			wp_update_post( array(
				'ID' => absint( $page_id ),
				'post_title' => $new_path,
				'post_name' => $this->sanitize_path( $new_path ),
			));
			$this->set_option( 'path', $new_path );
		}

		wp_redirect( $this->admin_url() . '&updated=true' );
		exit;
	}

	protected function register_frontend_scripts() {
		wp_register_script( 'gifdrop-isotope', $this->get_url() . 'js/isotope.min.js', array('jquery'), '2.0.0' );
		wp_register_script( 'gifdrop-lazyload', $this->get_url() . 'js/jquery.lazyload.min.js', array('jquery'), '1.9.3' );
		wp_register_script( 'gifdrop', $this->get_url() . 'js/gifdrop.js', array( 'jquery', 'backbone', 'wp-backbone', 'wp-util', 'wp-plupload', 'gifdrop-isotope', 'gifdrop-lazyload' ), '0.1' );
	}

	protected function register_frontend_styles() {
		wp_register_style( 'gifdrop', $this->get_url() . 'css/gifdrop.css', array( 'dashicons' ), '0.1' );
	}

	protected function only_some_attachment_fields( &$attachment ) {
		$full = wp_get_attachment_image_src( $attachment->ID, 'full' );
		$static = wp_get_attachment_image_src( $attachment->ID, 'full-gif-static' );
		$attachment = (object) array(
			'id' => $attachment->ID,
			'src' => $full[0],
			'static' => $static ? $static[0] : $full[0],
			'width' => $full[1],
			'height' => $full[2],
			'title' => $attachment->post_title,
		);
	}

	public function template_include( $template ) {
		if ( is_singular() ) {
			if ( $this->is_gifdrop_page( get_queried_object_id() ) ) {
				$this->register_frontend_scripts();
				$this->register_frontend_styles();
				$images = get_posts( array(
					'post_parent' => get_queried_object_id(),
					'post_type'   => 'attachment',
					'orderby' => 'date',
					'order' => 'DESC',
					'posts_per_page' => -1
				));
				array_walk( $images, array( $this, 'only_some_attachment_fields' ) );
				wp_localize_script( 'gifdrop', 'gifdropSettings', array(
					'id' => (int) get_queried_object_id(),
					'nonce' => wp_create_nonce( 'gifdrop_' . get_queried_object_id() ),
					'attachments' => $images,
				));
				wp_plupload_default_settings();
				return $this->get_path() . '/templates/page.php';
			}
		}
		return $template;
	}

	protected function increment_id() {
		// set our option first with non autoloading
		add_option( 'gifdrop_filename_count', 1, '', 'no' );
		// now get our option
		$current = absint( get_option( 'gifdrop_filename_count' ) );
		// update the counter first
		update_option( 'gifdrop_filename_count', $current + 1 );
		// return the value base32 encoded
		return base_convert( $current, 10, 36 );
	}

	protected function is_gifdrop_page( $post_id = 0 ) {
		return 'gifdrop' === get_post_type( $post_id );
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

	public function metadata_filter( $metadata, $attachment_id ) {
		if ( 'image/gif' === get_post_mime_type( $attachment_id ) ) {
			$file = get_attached_file( $attachment_id );
			$editor = wp_get_image_editor( $file );
			if ( ! is_wp_error( $editor ) ) {
				// Flip the image twice
				// The first flip flips the image and boils it down to the first gif frame
				// The second flip restores it to its original orientation
				$editor->flip( true, false );
				$editor->flip( true, false );
				$gif_static_metadata = $editor->save();
				$metadata['sizes']['full-gif-static'] = $gif_static_metadata;
			}
		}
		return $metadata;
	}

	public function image_size_names_choose( $sizes ) {
		if ( isset( $_POST['provide_full_gif_static'] ) ) {
			$sizes['full-gif-static'] = __( 'Full Size (non-animated)', 'gifdrop' );
		}
		return $sizes;
	}
}

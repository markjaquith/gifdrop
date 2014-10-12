<?php
defined( 'WPINC' ) or die;

class GifDrop_Plugin {
	private static $instance;
	private $base;
	const OPTION = 'gifdrop';
	const NONCE = 'gifdrop_save';

	/**
	 * Constructs the object, hooks in to 'plugins_loaded'
	 *
	 * @param string $__FILE__ the main plugin file's __FILE__ value
	 */
	public function __construct( $__FILE__ ) {
		$this->__FILE__ = $__FILE__;
		$this->base = dirname( dirname( __FILE__ ) );
		add_action( 'plugins_loaded', array( $this, 'add_hooks' ) );
	}

	/**
	 * Initializes the plugin object and returns its instance
	 *
	 * @param string $__FILE__ the main plugin file's __FILE__ value
	 * @return object the plugin object instance
	 */
	public static function get_instance( $__FILE__ = null ) {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( $__FILE__ );
		}
		return self::$instance;
	}

	/**
	 * Adds all the plugin's hooks
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_post_gifdrop-save', array( $this, 'save' ) );
		add_action( 'admin_init', array( $this, 'change_upload_dir' ), 999 );
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'metadata_filter', ), 10, 2 );
		add_filter( 'image_size_names_choose', array( $this, 'image_size_names_choose' ) );
		add_filter( 'sanitize_file_name', array( $this, 'filename' ), 10, 2 );
		add_action( 'wp_ajax_gifdrop', array( $this, 'ajax' ) );
		add_action( 'root_rewrite_rules', array( $this, 'inject_rewrite_rules' ), 99 );
		add_action( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 20, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename($this->__FILE__), array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Returns the URL to the plugin directory
	 *
	 * @return string the URL to the plugin directory
	 */
	public function get_url() {
		return plugin_dir_url( $this->__FILE__ );
	}

	/**
	 * Returns the path to the plugin directory
	 *
	 * @return string the absolute path to the plugin directory
	 */
	public function get_path() {
		return plugin_dir_path( $this->__FILE__ );
	}

	/**
	 * Initializes translations, registers custom post type
	 */
	public function init() {
		// Initialize translations
		load_plugin_textdomain( 'gifdrop', false, basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );

		// Register our CPT
		$this->register_cpt();
	}

	/**
	 * Registers the 'gifdrop' custom post type
	 */
	public function register_cpt() {
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

	/**
	 * Creates a GifDrop landing page (if it hasn't yet)
	 */
	public function maybe_create_gifdrop() {
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

	/**
	 * Removes slashes from the beginning and end of a string
	 *
	 * @param string $string a string, maybe with leading/trailing slashes
	 * @return string a string without leading/trailing slashes
	 */
	public function unsandwich_slashes( $string ) {
		// Remove leading slashes
		$string = preg_replace( '#^/+#', '', $string );
		// And trailing slashes
		$string = untrailingslashit( $string );
		return $string;
	}

	/**
	 * Sanitizes a GifDrop URL path
	 *
	 * @param  string $path the path to sanitize
	 * @return string the sanitized URL path
	 */
	public function sanitize_path( $path ) {
		// Remove leading/trailing slashes
		$path = $this->unsandwich_slashes( $path );
		// Lowercase
		$path = strtolower( $path );
		// Only a-z, 0-9, slash and dash
		$path = preg_replace( '#[^a-z0-9/-]#', '', $path );
		// No multiple slashes in a row
		$path = preg_replace( '#/+#', '/', $path );
		return $path;
	}

	/**
	 * Sanitizes a GifDrop URL slug
	 *
	 * @param string $path the path to sanitize
	 * @return string the sanitized URL slug
	 */
	public function sanitize_slug( $path ) {
		// Do the path sanitization first (removes leading/trailing slashes and more)
		$path = $this->sanitize_path( $path );
		// Make other slashes into a dash so experts/exchange doesn't become expertsexchange
		$path = str_replace( '/', '-', $path );
		// Run it through the WP dashing function
		$path = sanitize_title_with_dashes( $path );
		if ( '' === $path ) {
			// Special string because can't match blank strings in the query engine
			$path = 'gifdrop-on-site-root';
		}
		return $path;
	}

	/**
	 * Returns a regular expression for matching the GifDrop path (for rewrites)
	 *
	 * @return string the regular expression
	 */
	public function path_regex() {
		$path = $this->get_option( 'path' );
		$path = trailingslashit( $this->sanitize_path( $path ) );
		if ( '/' === $path ) {
			$path = '$';
		} else {
			$path .= '?$';
		}
		return $path;
	}

	/**
	 * Returns the sanitized path slug for the GifDrop repository
	 *
	 * @return string the path slug
	 */
	public function path_slug() {
		return $this->sanitize_slug( $this->get_option( 'path' ) );
	}

	/**
	 * Inserts the GifDrop rewrite rules
	 * @param  array $rules WordPress rewrite rules
	 * @return array modified rewrite rules
	 */
	public function inject_rewrite_rules( $rules ) {
		$rules[$this->path_regex()] = 'index.php?&gifdrop=' . $this->path_slug();
		return $rules;
	}

	/**
	 * Create the GifDrop uploads directory
	 */
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

	/**
	 * Changes the uploads dir for GifDrop uploads
	 * @param array $upload information about the upload
	 * @return array information with the modified paths
	 */
 	public function set_upload_dir( $upload ) {
 		$upload['subdir'] = '/gifdrop';
 		$upload['path']   = $upload['basedir'] . $upload['subdir'];
 		$upload['url']    = $upload['baseurl'] . $upload['subdir'];
 		return $upload;
 	}

 	/**
 	 * Monitors for GifDrop uploads and sets a filter to change uploads dir
 	 */
	public function change_upload_dir() {
		global $pagenow;
		if ( ! empty( $_REQUEST['post_id'] ) && ( 'async-upload.php' === $pagenow ) ) {
			if ( $this->is_gifdrop_page( absint( $_REQUEST['post_id'] ) ) ) {
				$this->create_upload_dir();
				add_filter( 'upload_dir', array( $this, 'set_upload_dir' ) );
			}
		}
	}

	/**
	 * Sets the filename of GifDrop uploads
	 *
	 * Uses a short base36 id to keep things tiny for sharing
	 *
	 * @param  string $filename the filename WordPress wants to use
	 * @return string the modified filename
	 */
	public function filename( $filename ) {
		global $pagenow;
		if ( ! empty( $_REQUEST['post_id'] ) && ( 'async-upload.php' == $pagenow ) ) {
			if ( $this->is_gifdrop_page( absint( $_REQUEST['post_id'] ) ) ) {
				$info = pathinfo( $filename );
				$ext  = empty( $info['extension'] ) ? '' : '.' . $info['extension'];
				$filename = $this->increment_id() . $ext;
			}
		}
		return $filename;
	}

	/**
	 * Adds the settings page to the WordPress Settings menu
	 */
	public function admin_menu() {
		$hook = add_options_page( __( 'GifDrop Settings', 'gifdrop' ), __( 'GifDrop', 'gifdrop' ), 'manage_options', 'gifdrop', array( $this, 'admin_page' ) );
		add_action( 'load-' . $hook, array( $this, 'load' ) );
	}

	/**
	 * Handles settings page loading actions
	 *
	 * - Adds an updated message
	 * - Flushes rewrite rules
	 * - Enqueues admin scripts
	 */
	public function load() {
		if ( isset( $_GET['updated'] ) ) {
			add_action( 'admin_notices', array( $this, 'updated' ) );
			flush_rewrite_rules();
		}

		add_action ( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Loads an "updated" message template
	 */
	public function updated() {
		include( $this->base . '/templates/updated.php' );
	}

	/**
	 * Handles AJAX requests
	 */
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

	/**
	 * Throws a JSON error (and HTTP header)
	 *
	 * @param string $message the error message
	 */
	public function json_error( $message ) {
		status_header( 412 );
		wp_send_json_error( array( 'message' => $message ) );
	}

	/**
	 * Enqueues scripts on the settings page
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'gifdrop-settings', $this->get_url() . 'js/admin.js', array( 'jquery' ), '0.2' );
	}

	/**
	 * Loads the settings page
	 */
	public function admin_page() {
		include( $this->base . '/templates/admin-page.php' );
	}

	/**
	 * Gets a suboption
	 * @param string $key the suboption key
	 * @param mixed $default the default value if not exists
	 * @return mixed the value of the suboption
	 */
	public function get_option( $key, $default = null ) {
		$option = get_option( self::OPTION );
		if ( ! is_array( $option ) || ! isset( $option[$key] ) ) {
			return $default;
		} else {
			return $option[$key];
		}
	}

	/**
	 * Sets a suboption
	 *
	 * @param string $key the suboption key
	 * @param mixed $value the value of the suboption
	 * @return bool success of the set
	 */
	public function set_option( $key, $value ) {
		$option = get_option( self::OPTION );
		is_array( $option ) || $option = array();
		$option[$key] = $value;
		return update_option( self::OPTION, $option );
	}

	/**
	 * fetches the site name (used in numerous places)
	 * with a fallback to GifDrop
	 *
	 * @param bool $echo whether or not to echo the name
	 *
	 * @return string the name
	 */
	public function get_site_name( $echo = false ) {
		// fetch the name
		$name = self::get_option( 'name', __( 'GifDrop', 'gifdrop' ) );
		// echo if requested
		if ( ! empty( $echo ) ) {
			echo $name;
		}
		// just return it
		return $name;
	}

	/**
	 * Returns the URL for the GifDrop settings page
	 *
	 * @return string the settings page URL
	 */
	public function admin_url() {
		return admin_url( 'options-general.php?page=gifdrop' );
	}

	/**
	 * Save handler for GifDrop options
	 *
	 * Does security checks, saves, and then redirects
	 */
	public function save() {
		current_user_can( 'manage_options' ) || die;
		check_admin_referer( self::NONCE );
		$_post = stripslashes_deep( $_POST );

		// handle name setting
		$name = ! empty( $_post['gifdrop_name'] ) ? sanitize_text_field( $_post['gifdrop_name'] ) : '';
		$this->set_option( 'name', $name );

		// handle path setting
		$old_path = $this->get_option( 'path' );
		if ( $_post['gifdrop_path'] !== $old_path ) {
			$this->update_path( $_post['gifdrop_path'] );
		}

		wp_redirect( $this->admin_url() . '&updated=true' );
		exit;
	}

	/**
	 * Updates the path setting in GifDrop (where GifDrop should live on the frontend)
	 *
	 * @param string $new_path the new path
	 */
	public function update_path( $new_path ) {
		global $wpdb;
		$new_path = $this->sanitize_path( $new_path );
		$page_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'gifdrop'" );
		wp_update_post( array(
			'ID' => absint( $page_id ),
			'post_title' => 'GifDrop: ' . trailingslashit( $new_path ),
			'post_name' => $this->sanitize_slug( $new_path ),
		));
		$this->set_option( 'path', $new_path );
	}

	/**
	 * Registers scripts for the frontend (GifDrop repository page)
	 */
	public function register_frontend_scripts() {
		wp_register_script( 'gifdrop-isotope', $this->get_url() . 'bower_components/isotope/dist/isotope.pkgd.min.js', array('jquery'), '2.0.1' );
		wp_register_script( 'gifdrop-lazyload', $this->get_url() . 'bower_components/jquery.lazyload/jquery.lazyload.min.js', array('jquery'), '1.9.3' );
		wp_register_script( 'gifdrop-zeroclipboard', $this->get_url() . 'bower_components/zeroclipboard/dist/ZeroClipboard.min.js', array(), '2.1.6' );
		wp_register_script( 'gifdrop', $this->get_url() . 'js/gifdrop.js', array( 'jquery', 'backbone', 'wp-backbone', 'wp-util', 'wp-plupload', 'gifdrop-isotope', 'gifdrop-lazyload', 'gifdrop-zeroclipboard' ), '0.1b' );
	}

	/**
	 * Registers styles for the frontend (GifDrop repository page)
	 */
	public function register_frontend_styles() {
		wp_register_style( 'gifdrop', $this->get_url() . 'css/gifdrop.css', array( 'dashicons' ), '0.1b' );
	}

	/**
	 * Filter out attachment fields we don't care about
	 *
	 * @param object $attachment the attachment object
	 */
	public function only_some_attachment_fields( &$attachment ) {
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

	/**
	 * Take over the template loading process for a GifDrop repository page
	 *
	 * @param string $template the template WordPress wants to load
	 * @return string the modified template we tell WordPress to load
	 */
	public function template_include( $template ) {
		if ( is_singular() ) {
			if ( $this->is_gifdrop_page( get_queried_object_id() ) ) {
				$this->register_frontend_scripts();
				$this->register_frontend_styles();
				// To-Do: Caching of this call
				$images = get_posts( array(
					'post_parent' => get_queried_object_id(),
					'post_type'   => 'attachment',
					'orderby' => 'date',
					'order' => 'DESC',
					'posts_per_page' => -1
				));
				// Filter out some of the attachment fields so the object is as small as possible
				array_walk( $images, array( $this, 'only_some_attachment_fields' ) );
				wp_localize_script( 'gifdrop', 'gifdropSettings', array(
					'id' => (int) get_queried_object_id(),
					'nonce' => wp_create_nonce( 'gifdrop_' . get_queried_object_id() ),
					'attachments' => $images,
					// 'attachments' => array(), // to test no-gifs situation
					'canUpload' => current_user_can( 'edit_post', (int) get_queried_object_id() ) ? '1' : '0', // 1/0 strings for REASONS
				));
				wp_plupload_default_settings();
				return $this->get_path() . '/templates/page.php';
			}
		}
		return $template;
	}

	/**
	 * Increments the base10 auto increment ID and returns the previous base36 one
	 *
	 * We use this to make gif URLs smaller, like link shorteners do.
	 *
	 * @return string a new base36 number (string) to use for a new upload
	 */
	public function increment_id() {
		// set our option first with non autoloading
		add_option( 'gifdrop_filename_count', 1, '', 'no' );
		// now get our option
		$current = absint( get_option( 'gifdrop_filename_count' ) );
		// update the counter first
		update_option( 'gifdrop_filename_count', $current + 1 );
		// return the value base32 encoded
		return base_convert( $current, 10, 36 );
	}

	/**
	 * Indicates whether a given post id is a GifDrop repository page
	 *
	 * @param integer $post_id the post ID to check
	 * @return boolean whether the given ID is for a GifDrop repository page
	 */
	public function is_gifdrop_page( $post_id = 0 ) {
		return 'gifdrop' === get_post_type( $post_id );
	}

	/**
	 * Makes an animated gif static for the full-gif-static size
	 *
	 * @param array $metadata the image metadata
	 * @param int $attachment_id the attachment id
	 * @return array the modified metadata
	 */
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

	/**
	 * Adds the non-static full size image
	 *
	 * We do this because animated gifs are huge, so we want to create a
	 * static copy of the first frame
	 *
	 * @param array $sizes the array of sizes
	 * @return array the modified array of sizes
	 */
	public function image_size_names_choose( $sizes ) {
		if ( isset( $_POST['provide_full_gif_static'] ) ) {
			$sizes['full-gif-static'] = __( 'Full Size (non-animated)', 'gifdrop' );
		}
		return $sizes;
	}

	/**
	 * Adds a GitHub link to the plugin meta
	 *
	 * @param array $links the current array of links
	 * @param string $file the current plugin being processed
	 * @return array the modified array of links
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === plugin_basename( $this->__FILE__ ) ) {
			return array_merge(
				$links,
				array( sprintf( __('<a href="%s" target="_blank">Contribute code on GitHub</a>', 'gifdrop' ), 'https://github.com/markjaquith/gifdrop/' ) )
			);
		}
		return $links;
	}

	/**
	 * Adds a settings link to the plugin action links
	 * @param array $links an array of action links
	 * @return array the modified array of links
	 */
	public function plugin_action_links( $links ) {
		$links[] = '<a href="'. $this->admin_url() .'">' . __( 'Settings', 'gifdrop' ) . '</a>';
		return $links;
	}
}

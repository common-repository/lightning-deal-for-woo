<?php
/**
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @package    Woold
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class Woold {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * Total discount applied.
	 *
	 * @var float
	 */
	public static $total_discount_applied = 0;

	/**
	 * Deals matching current time.
	 *
	 * @var array
	 */
	public static $time_applicable_deals;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Settings.
	 *
	 * @var [type]
	 */
	public $settings;

	/**
	 * Posttype class.
	 *
	 * @var Woold_Lightning_Posttype
	 */
	public $posttype;

	/**
	 * DB class.
	 *
	 * @var Woold_Lightning_DB
	 */
	public $db;

	/**
	 * Product class.
	 *
	 * @var Woold_Lightning_Product
	 */
	public $product;

	/**
	 * Order class.
	 *
	 * @var Woold_Lightning_Order
	 */
	public $order;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( defined( 'WOOLD_VERSION' ) ) {
			$this->version = WOOLD_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woold';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->change_plugin_icon();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {

		/**
		 * Internationalization.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-woold-i18n.php';

		/**
		 * Admin and settings.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-woold-admin.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-woold-settings.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-woold-post-type.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-woold-db.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-woold-product.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-woold-order.php';

		require_once plugin_dir_path( __DIR__ ) . 'public/class-woold-public.php';

		$this->settings = new Woold_Settings();
		$this->posttype = new Woold_Lightning_Posttype();
		$this->db       = new Woold_Lightning_DB();
		$this->product  = new Woold_Lightning_Product();
		$this->order    = new Woold_Lightning_Order();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woold_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function set_locale() {

		$plugin_i18n = new Woold_I18n();

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Woold_Admin( $this->get_plugin_name(), $this->get_version() );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_public_hooks() {

		$plugin_public = new Woold_Public( $this->get_plugin_name(), $this->get_version() );

		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
	}


	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Change plugin icon.
	 */
	public function change_plugin_icon() {
		woold_fs()->add_filter(
			'plugin_icon',
			function () {
				return dirname( __DIR__ ) . '/public/images/plugin-icon.jpg';
			}
		);
	}
}

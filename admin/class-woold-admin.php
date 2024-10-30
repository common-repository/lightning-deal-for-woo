<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Woold
 * @subpackage Woold/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Woold_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Renders upgrade to pro button
	 *
	 * @since    1.0.0
	 * @return string Anchor link HTML of Upgrade to pro button.
	 */
	public static function render_upgrade_to_pro_button() {
		return '<a target="_blank" href = "' . admin_url( 'admin.php?page=woold-settings-pricing' ) . '"  class="woold-pro-btn button button-primary button-large" ><i class="woold-pro-btn__icon dashicons dashicons-lock"></i>' . esc_html__( 'Upgrade to Pro', 'lightning-deal-for-woo' ) . '</a>';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woold_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woold_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, WOOLD_URL . 'admin/css/woold-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'select2', WOOLD_URL . 'admin/css/select2.min.css', array(), $this->version );
		wp_enqueue_style( 'jquery-datetimepicker', WOOLD_URL . 'public/vendor/jquery-datetimepicker/jquery.datetimepicker.min.css', array(), $this->version );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, WOOLD_URL . 'admin/js/woold-admin.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'jquery-datetimepicker', WOOLD_URL . 'public/vendor/jquery-datetimepicker/jquery.datetimepicker.full.min.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'select2', WOOLD_URL . 'admin/js/select2.min.js', array( 'jquery' ), $this->version, true );

		$data = array(
			'product_nonce' => wp_create_nonce( 'search-products' ),
			'i18n'          => array(
				'invalid_start_date'         => esc_html__( 'Invalid Start date', 'lightning-deal-for-woo' ),
				'invalid_end_date'           => esc_html__( 'Invalid End date', 'lightning-deal-for-woo' ),
				'start_time_less_than_end'   => esc_html__( 'Start time cannot be later than end time.', 'lightning-deal-for-woo' ),
				'claim_less_than_max_orders' => esc_html__( 'Claimed deals starting index cannot be equal or more than max orders limit.', 'lightning-deal-for-woo' ),
			),
		);
		wp_localize_script( $this->plugin_name, 'venus_woold', $data );
	}
}

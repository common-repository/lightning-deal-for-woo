<?php
/**
 * Interface with the WP Settings Framework.
 *
 * @since      1.0.0
 *
 * @package    Woold
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Settings Interface class.
 */
class Woold_Settings {
	/**
	 * Plugin Path
	 *
	 * @var String
	 */
	private $plugin_path;

	/**
	 * WordPress Settings Framework
	 *
	 * @var WordPressSettingsFramework
	 */
	private $wpsf;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Include and create a new WordPressSettingsFramework Object.
		require_once WOOLD_PATH . 'includes/vendor/WordPress-Settings-Framework/wp-settings-framework.php';
		$this->wpsf = new WordPressSettingsFramework( WOOLD_PATH . 'admin/settings.php', 'woold' );

		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );

		// Settings validation.
		add_filter( $this->wpsf->get_option_group() . '_settings_validate', array( &$this, 'validate_settings' ) );
	}

	/**
	 * Add settings page.
	 */
	public function add_settings_page() {
		$this->wpsf->add_settings_page(
			array(
				'parent_slug' => 'woocommerce',
				'page_title'  => __( 'Lightning Deal for WooCommerce', 'lightning-deal-for-woo' ),
				'menu_title'  => __( 'Lightning Deal', 'lightning-deal-for-woo' ),
				'capability'  => 'manage_woocommerce',
			)
		);
	}

	/**
	 * Validate settings.
	 *
	 * @param array $input The input to be processed.
	 *
	 * @return array
	 */
	public function validate_settings( $input ) {
		// Do your settings validation here.
		// Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting.
		return $input;
	}

	/**
	 * Get a particular setting.
	 *
	 * @return array $settings Settings.
	 */
	public function get_settings() {
		if ( ! empty( $this->wpsf ) ) {
			return $this->wpsf->get_settings();
		} else {
			return get_option( 'woold_settings', array() );
		}
	}

	/**
	 * Get label for the "Lightning deal".
	 *
	 * @return string
	 */
	public static function get_label() {
		global $woold;
		$settings = $woold->settings->get_settings();
		return isset( $settings['general_general_label'] ) ? $settings['general_general_label'] : 'Lightning Deal';
	}
}

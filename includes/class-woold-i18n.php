<?php
/**
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package    Woold
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define the internationalization functionality.
 */
class Woold_I18n {
	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'lightning-deal-for-woo',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}

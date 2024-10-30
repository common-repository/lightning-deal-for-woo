<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Woold
 * @subpackage Woold/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woold
 * @subpackage Woold/public
 */
class Woold_Public {

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
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		global $woold;
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woold-public.css', array(), $this->version, 'all' );

		// Add inline CSS.
		$settings         = $woold->settings->get_settings();
		$bar_filled_color = esc_html( sanitize_text_field( $settings['display_colors_filed_bar'] ) );
		$bar_bg_color     = esc_html( sanitize_text_field( $settings['display_colors_empty_bar'] ) );

		$custom_css = '
			.woold__bar__bg {
				background-color: ' . esc_html( $bar_bg_color ) . ' ;
			}

			.woold__bar__filled {
				background-color: ' . esc_html( $bar_filled_color ) . ' ;
			}
		';

		wp_add_inline_style( $this->plugin_name, $custom_css );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woold-public.js', array( 'jquery' ), $this->version, false );
		global $wp;

		$data        = array(
			'i18n' => array(
				'sale_ended' => esc_html__( 'Sale had ended', 'lightning-deal-for-woo' ),
			),
		);
		$current_url = site_url( add_query_arg( array(), $wp->request ) );

		if ( '' !== $current_url ) {
			$data['current_url'] = $current_url;
		}

		wp_localize_script( $this->plugin_name, 'woold_data', $data );
	}
}

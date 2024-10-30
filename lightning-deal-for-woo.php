<?php

/**
 * Plugin Name:       Lightning Deal for Woo
 * Plugin URI:        https://ideawp.com/plugin/lightning-deal-for-woocommerce/
 * Description:       Create time-bound, auto-expiring deals to create urgency and grow your Conversions.
 * Version: 1.1.1
 * Author:            IdeaWP
 * Author URI:        https://ideawp.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lightning-deal-for-woo
 * Domain Path:       /languages
 * 
 * WC requires at least: 8.0
 * WC tested up to: 2.8.5
 *
 * @link              https://ideawp.com/
 * @since             1.0.0
 * @package           Woold
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}
/*
 * Check if WooCommerce is active.
 */
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
    return;
}
/**
 * Currently plugin version.
 */
define( 'WOOLD_VERSION', '1.1.1' );
define( 'WOOLD_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOLD_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOLD_TEMPLATE_PATH', plugin_dir_path( __FILE__ ) . 'templates/' );
define( 'WOOLD_INC_PATH', plugin_dir_path( __FILE__ ) . 'includes/' );
/**
 * The code that runs during plugin activation.
 */
function woold_activate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-woold-activator.php';
    Woold_Activator::activate();
}

register_activation_hook( __FILE__, 'woold_activate' );
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woold.php';
/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function woold_run() {
    $GLOBALS['woold'] = new Woold();
}

woold_run();
/**
 * Freemius SDK.
 */
function woold_fs() {
    global $woold_fs;
    if ( !isset( $woold_fs ) ) {
        require plugin_dir_path( __FILE__ ) . 'includes/vendor/freemius/wordpress-sdk/start.php';
        $woold_fs = fs_dynamic_init( array(
            'id'             => '15831',
            'slug'           => 'lightning-deal-for-woocommerce',
            'type'           => 'plugin',
            'public_key'     => 'pk_28a6687661e7596a25c74d6a9cfc6',
            'is_premium'     => false,
            'premium_suffix' => 'Pro',
            'has_addons'     => false,
            'has_paid_plans' => true,
            'trial'          => array(
                'days'               => 7,
                'is_require_payment' => true,
            ),
            'menu'           => array(
                'slug'    => 'woold-settings',
                'contact' => false,
                'support' => false,
                'parent'  => array(
                    'slug' => 'woocommerce',
                ),
            ),
            'is_live'        => true,
        ) );
    }
    return $woold_fs;
}

// Init Freemius.
woold_fs();
/**
 * Freemius SDK loaded.
 *
 * @since 1.2.0
 */
do_action( 'woold_fs_loaded' );
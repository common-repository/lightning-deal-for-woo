<?php
/**
 * Fired during plugin activation
 *
 * @package    Woold
 * @subpackage Woold/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Woold_Activator {

	/**
	 * Install tables when plugin is activated.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'woo_lightning_deal';

		$sql = "CREATE TABLE $table_name (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`deal_post_id` int(11) NOT NULL,
			`products` longtext NOT NULL,
			`product_categories` longtext NOT NULL,
			`object` varchar(50) NOT NULL,
			`start_time` datetime NOT NULL,
			`end_time` datetime NOT NULL,
			`discount_type` varchar(50) DEFAULT 'fixed' NOT NULL ,
			`discount` float NOT NULL,
			`max_orders` int(11) NOT NULL,
			`claim_start_index` int(11) DEFAULT 0 NOT NULL ,
			`max_order_calculation_method` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT 'per_product' NOT NULL ,
			`views` int(11) NOT NULL,
			`claimed_cart` int(11) NOT NULL,
			`claimed_ordered` longtext NOT NULL,
			PRIMARY KEY (`id`)
		  ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}
}

<?php
/**
 * Functions related to product.
 *
 * @package    Woold
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Functions related to deals.
 */
class Woold_Lightning_Product {

	/**
	 * Deals matching current time.
	 *
	 * @var array
	 */
	public static $time_applicable_deals;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	/**
	 * Init hook.
	 */
	public static function init() {
		global $woold;

		$settings = $woold->settings->get_settings();

		add_action( $settings['display_display_position_single_page'], array( __CLASS__, 'get_lightning_bar' ) );

		// Add lightning bar on Product collection block.
		$archive_block_position = self::get_archive_blocks_positions( $settings['display_display_position_archive'] );
		add_filter( $archive_block_position, array( __CLASS__, 'render_deal_on_product_archive_blocks' ), 10, 2 );

		// Don't double lightning bar twice in classic theme.
		if ( function_exists( 'wp_is_block_theme' ) && ! wp_is_block_theme() ) {
			add_action( $settings['display_display_position_archive'], array( __CLASS__, 'get_lightning_bar' ) );
		}

		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'show_discount' ), 10, 2 );
		add_filter( 'woocommerce_available_variation', array( __CLASS__, 'modify_variation_json' ), 10, 3 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'add_lightning_label_after_title' ), 6 );
	}

	/**
	 * Returns respective block position hook for classic template hook.
	 *
	 * @param string $position Classic WP postion hook.
	 * @return string   $block_position Block position hook.
	 */
	public static function get_archive_blocks_positions( $position ) {
		switch ( $position ) {
			case 'woocommerce_after_shop_loop_item_title':
				$block_position = 'render_block_core/post-title';
				break;
			case 'woocommerce_after_shop_loop_item':
				$block_position = 'render_block_woocommerce/product-button';
				break;
			case 'woocommerce_before_shop_loop_item_title':
				$block_position = 'render_block_woocommerce/product-image';
				break;
			case 'woocommerce_before_shop_loop_item':
				$block_position = 'render_block_woocommerce/product-image';
				break;

			default:
				$block_position = 'render_block_woocommerce/product-button';
				break;
		}

		return $block_position;
	}

	/**
	 * Inject deal bar as per position settings in product archive block.
	 *
	 * @param  string $block_content Block content.
	 * @param  array  $block Block array.
	 * @return string $block_content Updated block content with injected deal bar.
	 */
	public static function render_deal_on_product_archive_blocks( $block_content, $block ) {
		if ( is_admin() ) {
			return $block_content;
		}

		global $woold;

		$settings = $woold->settings->get_settings();

		if ( 'woocommerce/product-image' === $block['blockName'] && 'woocommerce_before_shop_loop_item' === $settings['display_display_position_archive'] ) {
			$block_content = self::get_lightning_bar( true ) . $block_content;
		} else {
			$block_content .= self::get_lightning_bar( true );
		}

		return $block_content;
	}


	/**
	 * Show discounted price.
	 *
	 * @param string     $price_html Price HTML.
	 * @param WC_Product $product    Product.
	 *
	 * @return string
	 */
	public static function show_discount( $price_html, $product ) {
		$deal = self::get_current_running_deal_for_product( $product->get_ID() );

		if ( empty( $deal ) ) {
			return $price_html;
		}

		$discount = self::calculate_discount( $deal['deal_post_id'], $product );

		if ( empty( $discount ) ) {
			return $price_html;
		}

		$actual_price     = $product->get_price();
		$discounted_price = $actual_price - $discount;

		if ( $product->is_on_sale() ) {
			$actual_price = $product->get_regular_price();
		}

		$price = sprintf(
			'<del>%s</del>
			<ins>%s</ins>',
			wc_price( $actual_price ),
			wc_price( $discounted_price )
		);

		return $price;
	}

	/**
	 * Show lightning bar.
	 *
	 * @param boolean $return_html Whether to return or print the HTML.
	 *
	 * @return string HTML from deal bar file.
	 */
	public static function get_lightning_bar( $return_html = false ) {
		if ( defined( 'REST_REQUEST' ) ) {
			return;
		}

		global $product, $post;

		if ( empty( $product ) ) {
			$product = wc_get_product();
		}

		$all_blocks = parse_blocks( $post->post_content );

		if ( ! empty( $all_blocks ) ) {
			foreach ( $all_blocks as $content_block ) {
				if ( 'woocommerce/single-product' === $content_block['blockName'] ) {
					$product_id = $content_block['attrs']['productId'];
					$product    = wc_get_product( $product_id );
				}
			}
		}

		$deal = self::get_current_running_deal_for_product( $product->get_ID() );

		if ( ! $deal || empty( $product ) ) {
			return;
		}

		$is_deal_in_cart = Woold_Lightning_Order::is_deal_in_cart( $deal, $product );
		$bar_enabled     = self::is_bar_enabled( $deal );

		if ( $return_html ) {
			ob_start();
		}

		if ( is_product() ) {
			global $woold;

			$settings          = $woold->settings->get_settings();
			$max_width_setting = $settings['display_display_max_width_single_product'];
			$single_max_width  = isset( $max_width_setting ) && '' !== $max_width_setting ? $max_width_setting . 'px' : '400px';
		}

		include WOOLD_TEMPLATE_PATH . 'frontend/single-product-lightning-bar.php';

		if ( $return_html ) {
			$bar_html = ob_get_clean();
			return $bar_html;
		}
	}

	/**
	 * Add discounted price.
	 *
	 * @param array                $variation_data Variation data.
	 * @param WC_Product_Variable  $product        Parent product.
	 * @param WC_Product_Variation $variation      Child Product/Variation.
	 *
	 * @return array
	 */
	public static function modify_variation_json( $variation_data, $product, $variation ) {
		$deal = self::get_current_running_deal_for_product( $product->get_id() );

		if ( empty( $deal ) ) {
			return $variation_data;
		}

		$discount = self::calculate_discount( $deal['deal_post_id'], $product );

		if ( empty( $discount ) ) {
			return $variation_data;
		}

		$variation_data['woold_deal_post_id'] = $deal['deal_post_id'];
		$variation_data['woold_price']        = $variation->get_price() - $discount;
		$variation_data['woold_price_html']   = wc_price( $variation_data['woold_price'] );

		return $variation_data;
	}


	/**
	 * Get current deals for the given product.
	 * when settings are saved or a deal is updated.
	 *
	 * @param int  $product_id      Product ID.
	 * @param bool $return_multiple Return multiple deals or only the first deal?.
	 * @param bool $purge_cache     Whether to purge transient cache or not.
	 *
	 * @return array
	 */
	public static function get_current_running_deal_for_product( $product_id, $return_multiple = false, $purge_cache = false ) {
		global $wpdb;

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return false;
		}

		$transient_key    = sprintf( 'woold_applicable_deal_for_product_%d', $product_id );
		$deal_data        = get_transient( $transient_key );
		$orders_in_deal   = 0;
		$applicable_deals = array();
		$parent_id        = self::get_parent_id( $product );

		if ( false !== $deal_data && false === $purge_cache ) {
			$applicable_deals = $deal_data;
		} else {

			$time = current_time( 'mysql' );

			if ( ! Woold::$time_applicable_deals ) {
				Woold::$time_applicable_deals = $wpdb->get_col(
					$wpdb->prepare( "select deal_post_id from {$wpdb->prefix}woo_lightning_deal ld, {$wpdb->prefix}posts p where p.ID = ld.deal_post_id and p.post_status = 'publish' and start_time <= %s and end_time >= %s", $time, $time )
				);
			}

			foreach ( Woold::$time_applicable_deals as $deal_id ) {
				$deal = Woold_Lightning_DB::get_deal_data( $deal_id );

				if ( isset( $deal['claimed_ordered'][ $parent_id ] ) && 'per_product' === $deal['max_order_calculation_method'] ) {
					$orders_in_deal = $deal['claimed_ordered'][ $parent_id ];

				} elseif ( isset( $deal['claimed_ordered']['all'] ) && 'per_deal' === $deal['max_order_calculation_method'] ) {
					$orders_in_deal = $deal['claimed_ordered']['all'];
				}

				$max_order_limit = Woold_Lightning_Order::max_order_limit_validation( $deal['max_orders'], $deal['claim_start_index'], $orders_in_deal, 1 );

				if ( 'product' === $deal['object'] && in_array( $product_id, $deal['products'], true ) && ! $max_order_limit ) {
					$applicable_deals[] = $deal;
				}

				$product_categories = $product->get_category_ids();

				if ( 'product_category' === $deal['object'] && array_intersect( $deal['product_categories'], $product_categories ) ) {
					$applicable_deals[] = $deal;
				}
			}

			set_transient( $transient_key, $applicable_deals, DAY_IN_SECONDS );
		}

		foreach ( $applicable_deals as $deal_key => &$deal ) {
			$deal = self::add_extra_deal_parameters( $deal, $product );

			if ( 0 >= $deal['time_left'] ) {
				unset( $applicable_deals[ $deal_key ] );
			}
		}

		// array shift will return the first deal only.
		return $return_multiple ? $applicable_deals : array_shift( $applicable_deals );
	}

	/**
	 * Add time_left and other parameter(in future).
	 *
	 * @param array      $deal    Single Deal data.
	 * @param WP_Product $product The product, we need it so we can store it in the database.
	 *
	 * @return array modified deal.
	 */
	public static function add_extra_deal_parameters( $deal, $product ) {
		if ( empty( $deal ) ) {
			return false;
		}

		$end_timestamp = strtotime( $deal['end_time'] );
		$end_timestamp = new DateTime( $deal['end_time'], wp_timezone() );

		$deal['time_left'] = $end_timestamp->format( 'U' ) - time();

		$parent_id               = self::get_parent_id( $product );
		$deal['claimed_percent'] = self::calculate_claimed_deals_discount_percentage( $deal, $parent_id );

		return $deal;
	}

	/**
	 * Calculate deal discount percentage with respect to maximum orders/claimed deals stating index.
	 *
	 * @param array  $deal The lightning deal.
	 * @param object $parent_id The Parent id of Product associated with the deal.
	 */
	public static function calculate_claimed_deals_discount_percentage( $deal, $parent_id ) {
		$discount_index       = 0;
		$deal_claimed_percent = 0;
		$deal_order_limit     = (int) isset( $deal['max_orders'] ) ? $deal['max_orders'] : 0;
		$claimed_start_index  = (int) isset( $deal['claim_start_index'] ) ? $deal['claim_start_index'] : 0;

		if ( 'per_product' === $deal['max_order_calculation_method'] && isset( $deal['claimed_ordered'][ $parent_id ] ) ) {
			$deal_claimed_percent = (int) ( $deal['claimed_ordered'][ $parent_id ] + $claimed_start_index ) / (int) $deal_order_limit * 100;
		} elseif ( 'per_deal' === $deal['max_order_calculation_method'] && isset( $deal['claimed_ordered']['all'] ) ) {
			$deal_claimed_percent = (int) ( $deal['claimed_ordered']['all'] + $claimed_start_index ) / (int) $deal_order_limit * 100;
		} else {
			$deal_claimed_percent = (int) $claimed_start_index / (int) $deal_order_limit * 100;
		}

		return $deal_claimed_percent;
	}

	/**
	 * Calculate discount.
	 *
	 * @param int  $deal_post_id  Deal post id.
	 * @param bool $product       Product for which discount is to be calculated.
	 *
	 * @return int|false
	 */
	public static function calculate_discount( $deal_post_id, $product ) {
		if ( ! is_numeric( $deal_post_id ) ) {
			return false;
		}

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return false;
		}

		$cache_key = sprintf( 'woold_discount_%d_%d', $deal_post_id, $product->get_id() );
		$discount  = wp_cache_get( $cache_key );

		if ( false !== $discount ) {
			return $discount;
		}

		$deal = Woold_Lightning_DB::get_deal_data( $deal_post_id );

		if ( empty( $deal ) || ! is_array( $deal ) || empty( $deal['discount'] ) ) {
			return false;
		}

		$discount      = 0;
		$product_price = (int) $product->get_price( 'edit' );
		$price         = 0;

		$discount = (float) ( 'fixed' === $deal['discount_type'] ? $deal['discount'] : ( $product_price * $deal['discount'] / 100 ) );

		$price = $product_price - $discount;

		// If price is negetive, then return 0.
		if ( $discount < 0 ) {
			$discount = 0;
		}

		// If discount is more then actual price :D then return actual product price.
		if ( $discount > $product_price ) {
			$discount = $product_price;
		}

		wp_cache_set( $cache_key, $discount );

		return $discount;
	}

	/**
	 * Add lightning deal label after title.
	 */
	public static function add_lightning_label_after_title() {
		global $product, $woold;

		$product_id   = $product->get_id();
		$deal_applied = self::get_current_running_deal_for_product( $product_id );
		$settings     = $woold->settings->get_settings();

		if ( empty( $deal_applied ) ) {
			return;
		}

		echo wp_kses_post(
			sprintf(
				'<div class="woold_label">%s</div>',
				$settings['general_general_label']
			)
		);
	}

	/**
	 * Helper function to get the parent product ID, but if the product itself is
	 * parent product then it returns self ID.
	 *
	 * @param WP_Product $product Product object.
	 *
	 * @return int
	 */
	public static function get_parent_id( $product ) {
		$parent_id = $product->get_parent_id();
		return $parent_id ? $parent_id : $product->get_id();
	}

	/**
	 * Should lightning bar be visible based on the settings?
	 *
	 * @param array $deal Deal.
	 */
	public static function is_bar_enabled( $deal ) {
		global $product, $woold;

		if ( ! is_a( $product, 'WC_Product' ) || empty( $deal ) ) {
			return false;
		}

		$settings = $woold->settings->get_settings();

		if ( 'more_than_percent' !== $settings['general_general_bar_condition'] ) {
			return true;
		}

		$parent_id     = self::get_parent_id( $product );
		$claimed_order = ( 'per_product' === $deal['max_order_calculation_method'] ) ?
		( isset( $deal['claimed_ordered'][ $parent_id ] ) ? $deal['claimed_ordered'][ $parent_id ] : 0 ) // If calculation method is 'per_product'.
		:
		( isset( $deal['claimed_ordered']['all'] ) ? $deal['claimed_ordered']['all'] : 0 ); // Else.

		$claimed_percent = self::calculate_claimed_deals_discount_percentage( $deal, $parent_id );

		return $claimed_percent > $settings['general_general_bar_condition_percentage'];
	}
}

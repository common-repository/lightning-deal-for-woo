<?php
/**
 * All the orders realated function.
 *
 * @package    Woold
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Functions related to deals.
 */
class Woold_Lightning_Order {
	/**
	 * Constructor.
	 */
	public function __construct() {
		/**
		 * Filters.
		 */
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'add_to_cart_validation' ), 10, 4 );
		add_filter( 'woocommerce_update_cart_validation', array( __CLASS__, 'update_cart_validation' ), 10, 4 );
		add_filter( 'woocommerce_before_checkout_process', array( __CLASS__, 'before_checkout_process' ), 10, 1 );
		// save_fields_data_to_order_item_data will only run if woocommerce_add_to_cart_validation is success.
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'save_fields_data_to_order_item_data' ), 10, 2 );
		// Woo REST APIs filters.
		add_filter( 'woocommerce_store_api_validate_add_to_cart', array( __CLASS__, 'rest_store_api_validate_add_to_cart' ), 10, 2 );
		add_filter( 'woocommerce_store_api_add_to_cart_data', array( __CLASS__, 'rest_add_deal_to_cart' ), 10, 2 );

		/**
		 * Action hooks.
		 */
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_values_to_order_item_meta' ), 10, 4 );
		// Apply discount.
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'calculate_totals' ), 10 );
		// Priority is 9, because we want to re calculate total, before it shows in cart, after a deal is trashed from admin.
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'reset_cart_after_deal_trashed' ), 9 );
		add_action( 'trashed_post', array( __CLASS__, 'delete_deal_after_deal_post_trashed' ), 10, 2 );
		// Display 'Lightning deal applied' message.
		add_action( 'woocommerce_cart_totals_after_order_total', array( __CLASS__, 'display_total_discount_on_cart' ), 10 );
		add_action( 'woocommerce_checkout_cart_item_quantity', array( __CLASS__, 'display_total_discount_on_checkout_item' ), 10, 3 );
		// Update stats.
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'update_order_placed_stats' ), 10, 3 );
		// Validation.
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'checkout_validation' ), 10, 2 );
		add_action( 'woocommerce_store_api_cart_errors', array( __CLASS__, 'rest_checkout_validation' ), 10, 2 );
		// Woo REST APIs actions.
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'rest_update_order_placed_stats' ) );
		add_action( 'woocommerce_store_api_validate_cart_item', array( __CLASS__, 'rest_store_api_validate_cart_item' ), 10, 2 );
	}

	/**
	 * Validate maximum quantity for product.
	 *
	 * @param object $product Woocommerce product object.
	 *
	 * @param object $request WP REST request.
	 *
	 * @throws \Exception If quantity is more than the defined quantity.
	 */
	public static function rest_store_api_validate_add_to_cart( $product, $request ) {
		$product_id   = $product->get_id();
		$deal         = Woold_Lightning_Product::get_current_running_deal_for_product( $product_id );
		$deal_post_id = isset( $deal['deal_post_id'] ) ? $deal['deal_post_id'] : false;

		if ( ! $deal_post_id ) {
			return true;
		}
		$passed       = true;
		$quantity     = isset( $request['quantity'] ) ? $request['quantity'] : 0;
		$can_add_item = self::cart_validation( $passed, $deal_post_id, $product_id, $quantity );

		if ( ! $can_add_item ) {
			global $woold;
			$settings = $woold->settings->get_settings();
			// translators: Setting label.
			throw new Exception( sprintf( esc_html__( '%s: Max order limit reached.', 'lightning-deal-for-woo' ), wp_kses_post( $settings['general_general_label'] ) ), 1 );
		}
	}

	/**
	 * Validate maximum quantity for product in cart.
	 *
	 * @param object $product Woocommerce product object.
	 *
	 * @param object $request WP REST request.
	 *
	 * @throws \Exception If quantity is more than the defined quantity.
	 */
	public static function rest_store_api_validate_cart_item( $product, $request ) {
		$product_id   = $product->get_id();
		$deal         = Woold_Lightning_Product::get_current_running_deal_for_product( $product_id );
		$deal_post_id = isset( $deal['deal_post_id'] ) ? $deal['deal_post_id'] : false;

		if ( ! $deal_post_id ) {
			return true;
		}

		$passed   = true;
		$quantity = isset( $request['quantity'] ) ? $request['quantity'] : 0;
		self::cart_validation( $passed, $deal_post_id, $product_id, $quantity );
	}


	/**
	 * Add deal id to the cart meta data.
	 *
	 * @param array            $add_to_cart_data Cart item data.
	 *
	 * @param \WP_REST_Request $request REST request.
	 *
	 * @return array $add_to_cart_data Cart item data with deal id in meta data.
	 */
	public static function rest_add_deal_to_cart( $add_to_cart_data, \WP_REST_Request $request ) {
		$product_id   = isset( $add_to_cart_data['id'] ) ? $add_to_cart_data['id'] : '';
		$deal         = Woold_Lightning_Product::get_current_running_deal_for_product( $product_id );
		$deal_post_id = isset( $deal['deal_post_id'] ) ? $deal['deal_post_id'] : false;

		if ( $deal_post_id ) {
			$add_to_cart_data['cart_item_data']['woold']['applied_deal_post_id'] = $deal_post_id;
		}

		return $add_to_cart_data;
	}

	/**
	 * Save fields data to order item data.
	 * Will only run if add_to_cart_validation() returns true.
	 *
	 * @param array $item_data Item data.
	 * @param array $cart_item Cart Data.
	 *
	 * @return array $item_data Updated item data.
	 */
	public static function save_fields_data_to_order_item_data( $item_data, $cart_item ) {

		if ( null !== filter_input( INPUT_POST, 'woold_deal_post_id', FILTER_VALIDATE_INT ) ) {
			$woold_deal_post_id = filter_input( INPUT_POST, 'woold_deal_post_id', FILTER_VALIDATE_INT );
		} elseif ( null !== filter_input( INPUT_GET, 'woold_deal_post_id', FILTER_VALIDATE_INT ) ) {
			$woold_deal_post_id = filter_input( INPUT_GET, 'woold_deal_post_id', FILTER_VALIDATE_INT );
		}
		$deal_post_id = isset( $woold_deal_post_id ) ? $woold_deal_post_id : false;

		if ( ! $deal_post_id ) {
			return $item_data;
		}

		$item_data['woold'] = array(
			'applied_deal_post_id' => $deal_post_id,
			'add_to_cart_time'     => time(),
		);

		return $item_data;
	}

	/**
	 * Checkout validation for max quantity.
	 */
	public static function before_checkout_process() {
		global $woold;
		$cart     = WC()->cart->get_cart();
		$settings = $woold->settings->get_settings();
		$max_qty  = $settings['general_general_max_quantity'];

		if ( empty( $max_qty ) ) {
			return true;
		}

		foreach ( $cart as $cart_item_key => $cart_item ) {
			$product        = $cart_item['data'];
			$quantity       = $cart_item['quantity'];
			$orders_in_deal = 0;

			if ( $quantity > $max_qty ) {
				// translators: Setting label.
				wc_add_notice( sprintf( esc_html__( '%s: Maximum order limit reached. Please try to decrease product quantity.', 'lightning-deal-for-woo' ), $settings['general_general_label'] ), 'error' );
				return;
			}
		}
	}

	/**
	 * Deal max order limit checkout validation.
	 *
	 * @param int $max_orders   Max order count for deal.
	 * @param int $claimed_deals_index  Claimed starting index for deal.
	 * @param int $orders_in_deal  Existing orders in deal.
	 * @param int $quantity        Product quatity to check.
	 *
	 * @return boolean  Validation result.
	 */
	public static function max_order_limit_validation( $max_orders, $claimed_deals_index, $orders_in_deal, $quantity = 0 ) {
		return (int) $max_orders < (int) ( $orders_in_deal + (int) $claimed_deals_index + $quantity );
	}

	/**
	 * Update cart validation.
	 *
	 * @param boolean $passed      Validation result.
	 * @param int     $product_id  Product ID.
	 * @param int     $cart_item   Cart item.
	 * @param int     $quantity    Quantity.
	 *
	 * @return boolean $passed Validation result.
	 */
	public static function update_cart_validation( $passed, $product_id, $cart_item, $quantity ) {
		if ( empty( $cart_item['woold'] ) || empty( $cart_item['woold']['applied_deal_post_id'] ) ) {
			return;
		}

		$deal_post_id = isset( $cart_item['woold']['applied_deal_post_id'] ) ? $cart_item['woold']['applied_deal_post_id'] : false;

		if ( ! $deal_post_id ) {
			return $passed;
		}

		return self::cart_validation( $passed, $deal_post_id, $cart_item['product_id'], $quantity );
	}


	/**
	 * Add to cart validation.
	 *
	 * @param boolean $passed      Validation result.
	 * @param int     $product_id  Product ID.
	 * @param int     $quantity    Quantity.
	 *
	 * @return boolean $passed Validation result.
	 */
	public static function add_to_cart_validation( $passed, $product_id, $quantity ) {

		if ( null !== filter_input( INPUT_POST, 'woold_deal_post_id', FILTER_VALIDATE_INT ) ) {
			$woold_deal_post_id = filter_input( INPUT_POST, 'woold_deal_post_id', FILTER_VALIDATE_INT );
		} elseif ( null !== filter_input( INPUT_GET, 'woold_deal_post_id', FILTER_VALIDATE_INT ) ) {
			$woold_deal_post_id = filter_input( INPUT_GET, 'woold_deal_post_id', FILTER_VALIDATE_INT );
		}

		$deal_post_id = isset( $woold_deal_post_id ) ? $woold_deal_post_id : false;

		if ( ! $deal_post_id ) {
			return $passed;
		}

		return self::cart_validation( $passed, $deal_post_id, $product_id, $quantity );
	}

	/**
	 * Cart validation on add and update quantity.
	 *
	 * @param boolean $passed      Validation result.
	 * @param int     $deal_post_id  Deal ID.
	 * @param int     $product_id  Product ID.
	 * @param int     $quantity    Quantity.
	 *
	 * @return boolean $passed Validation result.
	 */
	public static function cart_validation( $passed, $deal_post_id, $product_id, $quantity ) {
		if ( is_admin() ) {
			return $passed;
		}

		// Check if deal is still applicable.
		$deals = Woold_Lightning_Product::get_current_running_deal_for_product( $product_id, true );

		$deal_ids = wp_list_pluck( $deals, 'deal_post_id' );
		$deal_ids = array_map( 'intval', $deal_ids );

		foreach ( $deal_ids as $key => &$value ) {
			$deal_ids[ $key ] = (int) $value;
		}

		if ( ! in_array( (int) $deal_post_id, $deal_ids, true ) ) {
			wc_add_notice( __( 'Sorry! This deal is not applicable.', 'lightning-deal-for-woo' ), 'error' );
			return false;
		}

		// Check if quantity is allowed.
		global $woold;

		$max_order_limit = false;
		$settings        = $woold->settings->get_settings();
		$max_qty         = $settings['general_general_max_quantity'];

		if ( empty( $max_qty ) ) {
			return $passed;
		}

		$product               = wc_get_product( $product_id );
		$add_to_cart_parent_id = Woold_Lightning_Product::get_parent_id( $product );
		$products_cart_count   = array();
		$total_product_count   = 1;
		$product_qty           = 0;

		foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
			$cart_parent_id                         = Woold_Lightning_Product::get_parent_id( $cart_item['data'] );
			$products_cart_count[ $cart_parent_id ] = $cart_item['quantity'];

			if ( $add_to_cart_parent_id !== $cart_parent_id ) {
				continue;
			}

			$total_product_count += WC()->cart->get_cart_contents_count();

			// Product matches. Now check if quantity is allowed.
			if ( $products_cart_count[ $cart_parent_id ] + $quantity > $max_qty ) {
				// Translators: Lightning deal label.
				wc_add_notice( sprintf( __( '%s: Max quantity limit reached.', 'lightning-deal-for-woo' ), $settings['general_general_label'] ), 'error' );
				return false;
			}

			if ( ! empty( $deals ) ) {
				foreach ( $deals as $deal ) {
					$orders_in_deal = 0;

					if ( isset( $deal['claimed_ordered'][ $cart_parent_id ] ) && 'per_product' === $deal['max_order_calculation_method'] ) {
						$orders_in_deal = $deal['claimed_ordered'][ $cart_parent_id ];

					} elseif ( isset( $deal['claimed_ordered']['all'] ) && 'per_deal' === $deal['max_order_calculation_method'] ) {
						$orders_in_deal = $deal['claimed_ordered']['all'];
					}

					if ( 'per_deal' === $deal['max_order_calculation_method'] ) {
						$product_qty = (int) $total_product_count;

						if ( 'woocommerce_update_cart_validation' === current_action() || 'woocommerce_store_api_validate_cart_item' === current_action() ) {
							$product_qty = 0;
							WC()->cart->set_quantity( $cart_item_key, $quantity );
							$product_qty = (int) WC()->cart->get_cart_contents_count();
						}
					}

					if ( 'per_product' === $deal['max_order_calculation_method'] ) {
						$product_qty = (int) $products_cart_count[ $cart_parent_id ] + 1;

						if ( 'woocommerce_update_cart_validation' === current_action() || 'woocommerce_store_api_validate_cart_item' === current_action() ) {
							$product_qty = 0;
							$product_qty = (int) $quantity;
						}
					}

					$max_order_limit = self::max_order_limit_validation( $deal['max_orders'], $deal['claim_start_index'], $orders_in_deal, $product_qty );

				}
			}
		}

		if ( $max_order_limit ) {
			// translators: Lightning deal label.
			wc_add_notice( sprintf( __( '%s: Maximum order limit reached.', 'lightning-deal-for-woo' ), $settings['general_general_label'] ), 'error' );
			return false;
		}

		return $passed;
	}
	/**
	 * Set Product discount price.
	 *
	 * @param WC_Cart $cart WC Cart object.
	 *
	 * @return void
	 */
	public static function calculate_totals( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		// Loop through cart items.
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$fees = 0;

			if ( empty( $cart_item['woold'] ) || empty( $cart_item['woold']['applied_deal_post_id'] ) ) {
				continue;
			}

			$discount = Woold_Lightning_Product::calculate_discount( $cart_item['woold']['applied_deal_post_id'], $cart_item['data'] );

			// Only set price if there is a discount.
			if ( $discount ) {
				$price = $cart_item['data']->get_price( 'edit' );
				$cart_item['data']->set_price( $price - $discount );
				Woold::$total_discount_applied += $discount;
			}
		}
	}

	/**
	 * Display discount on cart.
	 *
	 * @param int    $quantity       Quantity.
	 * @param array  $cart_item      Cart Item.
	 * @param string $cart_item_key  Cart item key.
	 *
	 * @return string Updated quantity with Lightning Deal discount message.
	 */
	public static function display_total_discount_on_checkout_item( $quantity, $cart_item, $cart_item_key ) {
		global $woold;

		$settings = $woold->settings->get_settings();

		if ( empty( $cart_item['woold'] ) ) {
			return $quantity;
		}

		$regular_price = $cart_item['data']->get_regular_price();
		$deal_price    = $cart_item['data']->get_price();
		$discount      = $regular_price - $deal_price;

		$hover_body = sprintf(
			'<span class="woold_checkout_msg_body_row">%s: %s </span>
			<span class="woold_checkout_msg_body_row">%s : %s</span>
			<span class="woold_checkout_msg_body_row">%s: %s</span>',
			esc_html__( 'Regular price', 'lightning-deal-for-woo' ),
			wc_price( $regular_price ),
			// Translators: Lightning Deal discount.
			sprintf( esc_html__( '%s discount', 'lightning-deal-for-woo' ), $settings['general_general_label'] ),
			wc_price( $discount ),
			esc_html__( 'Discounted price', 'lightning-deal-for-woo' ),
			wc_price( $deal_price )
		);

		$quantity = sprintf(
			'%s <div class="woold_cart_checkout_msg"><span class="woold_cart_checkout_msg__title">%s</span><span class="woold_cart_checkout_msg__body">%s</span></div>',
			$quantity,
			// Translators: With 'Lightning deal' applied.
			sprintf( esc_html__( 'With %s applied', 'lightning-deal-for-woo' ), $settings['general_general_label'] ),
			$hover_body
		);

		return $quantity;
	}

	/**
	 * Delele a deal after its related deal post type is trashed
	 *
	 * @param int   $deleted_deal_id       The Deal ID, that is deleted.
	 * @param array $previous_status      Previous publish status of the deal post type.
	 *
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public static function delete_deal_after_deal_post_trashed( $deleted_deal_id, $previous_status ) {
		if ( 'woold_deal' !== get_post_type( $deleted_deal_id ) && ! is_numeric( $deleted_deal_id ) ) {
			return;
		}

		Woold_Lightning_DB::trash_deal( $deleted_deal_id );
	}
	/**
	 * The function is used to remove deals from cart items, when a deal is trashed from admin.
	 *
	 * @return void WC cart with updated cart items, after removing trashed deals.
	 */
	public static function reset_cart_after_deal_trashed() {

		if ( empty( WC()->cart->get_cart() ) ) {
			return;
		}

		foreach ( WC()->cart->get_cart() as $cart_item_id => &$cart_item ) {
			$applied_deal_post_id = (int) ( isset( $cart_item['woold']['applied_deal_post_id'] ) ? $cart_item['woold']['applied_deal_post_id'] : 0 );

			if (
				empty( $cart_item['woold'] ) ||
				'trash' !== get_post_status( $applied_deal_post_id ) ||
				0 === $applied_deal_post_id
			) {
				return;
			}
			// If deals exists in cart and its trashed from admin, delete it from cart as well.
			unset( $cart_item['woold'] );
			// Update the cart after removed items.
			WC()->cart->cart_contents[ $cart_item_id ] = $cart_item;
		}
	}
	/**
	 * Display lightning deal total discount on cart.
	 */
	public static function display_total_discount_on_cart() {
		if ( empty( Woold::$total_discount_applied ) ) {
			return;
		}

		global $woold;

		$settings = $woold->settings->get_settings();
		// Translators: 'Lightning deal' discount.
		$title = sprintf( esc_html__( '%s discount', 'lightning-deal-for-woo' ), $settings['general_general_label'] );
		?>
		<tr class="lightning-discount-total">
			<th><?php echo esc_html( $title ); ?></th>
			<td data-title="<?php echo esc_attr( $title ); ?>">
		<?php
			echo wp_kses_post( '-' . wc_price( Woold::$total_discount_applied ) ); // PHPCS:ignore
		?>
		</td>
		</tr>
		<?php
	}

	/**
	 * Add values to order item meta.
	 *
	 * @param [type] $item           The item.
	 * @param [type] $cart_item_key  The cart item key.
	 * @param [type] $values         The values.
	 * @param [type] $order          The Order.
	 *
	 * @return void
	 */
	public static function add_values_to_order_item_meta( $item, $cart_item_key, $values, $order ) {

		if ( ! empty( $values['woold'] ) ) {
			if ( isset( $values['woold']['applied_deal_post_id'] ) ) {
				$item->add_meta_data( '_woold_deal_post_id', $values['woold']['applied_deal_post_id'] );
			}

			if ( isset( $values['woold']['add_to_cart_time'] ) ) {
				$item->add_meta_data( '_woold_add_to_cart_time', $values['woold']['add_to_cart_time'] );
			}
		}
	}


	/**
	 * Update stats when order is placed.
	 *
	 * @param int    $order_id WC Order id.
	 * @param array  $posted_data WC Order data.
	 * @param object $order WC Order object.
	 *
	 * @return void|boolean false for invalid parameters, void otherwise.
	 */
	public static function update_order_placed_stats( $order_id, $posted_data, $order ) {
		self::update_orders_stats( $order );
	}

	/**
	 * Update stats when order is placed.
	 *
	 * @param object $order WC Order object.
	 *
	 * @return void|boolean false for invalid parameters, void otherwise.
	 */
	public static function update_orders_stats( $order ) {
		if ( null === $order ) {
			return;
		}

		$items = $order->get_items();
		$deals = array();

		foreach ( $items as $item ) {
			$deal_post_id = $item->get_meta( '_woold_deal_post_id' );
			$cart_time    = $item->get_meta( '_woold_add_to_cart_time' );
			$quantity     = $item->get_quantity();

			if ( ! $deal_post_id ) {
				return false;
			}

			$deals[] = $deal_post_id;
			$product = $item->get_product();
			Woold_Lightning_DB::update_order_stats( $deal_post_id, $product, $quantity );
			// Clear transient.
			$parent_id = Woold_Lightning_Product::get_parent_id( $product );
			$deal      = Woold_Lightning_DB::get_deal_data( $deal_post_id );

			if ( 'per_deal' === $deal['max_order_calculation_method'] && ! empty( $deal['products'] ) ) {

				foreach ( $deal['products'] as $product_id ) {
					$transient_key = sprintf( 'woold_applicable_deal_for_product_%d', $product_id );
					delete_transient( $transient_key );
				}
			} else {
				$transient_key = sprintf( 'woold_applicable_deal_for_product_%d', $parent_id );
				delete_transient( $transient_key );
			}
		}

		$deals = array_unique( $deals );

		foreach ( $deals as $deal_post_id ) {
			Woold_Lightning_DB::update_order_count_stats( $deal_post_id );
		}
	}

	/**
	 * Update stats when order is placed via Store API.
	 *
	 * @param object \WC_Order $order WC Order object.
	 *
	 * @return void|boolean false for invalid parameters, void otherwise.
	 */
	public static function rest_update_order_placed_stats( \WC_Order $order ) {
		self::update_orders_stats( $order );
	}

	/**
	 * Is deal in cart?
	 *
	 * @param array      $deal    Deal data.
	 * @param WC_Product $product Product.
	 *
	 * @return bool
	 */
	public static function is_deal_in_cart( $deal, $product ) {
		if ( empty( $deal ) ) {
			return false;
		}

		$deal_product_id = $product->get_id();

		// Use Parent ID if product is a variation.
		if ( $product->is_type( 'variation' ) ) {
			$deal_product_id = $product->get_parent_id();
		}

		foreach ( WC()->cart->cart_contents as $cart_item ) {

			if ( empty( $cart_item['woold'] ) ) {
				continue;
			}

			// Use Parent ID if product is a variation.
			$cart_product_id = $cart_item['product_id'];

			if ( $cart_item['data']->is_type( 'variaiont' ) ) {
				$cart_product_id = $cart_item['data']->get_parent_id();
			}

			if ( $cart_item['woold']['applied_deal_post_id'] !== $deal['deal_post_id'] ) {
				continue;
			}

			if ( $cart_product_id === $deal_product_id ) {
				return $cart_item['quantity'];
			}
		}

		return false;
	}

	/**
	 * Check if the deal is still applicable(for checkout block).
	 *
	 * @param array $errors Errors.
	 * @param array $cart  Cart data.
	 *
	 * @return void
	 */
	public static function rest_checkout_validation( $errors, $cart ) {
		self::validate_checkout( $errors );
	}


	/**
	 * Check if the deal is still applicable.
	 *
	 * @param array $data   Checkout data.
	 * @param array $errors Errors.
	 *
	 * @return void
	 */
	public static function checkout_validation( $data, $errors ) {
		self::validate_checkout( $errors );
	}

	/**
	 * Validate if the deal is still applicable.
	 *
	 * @param array $errors Errors.
	 *
	 * @return void
	 */
	public static function validate_checkout( $errors ) {
		global $woold;

		$settings   = $woold->settings->get_settings();
		$deal_label = $settings['general_general_label'];

		foreach ( WC()->cart->cart_contents as $cart_item ) {
			if ( empty( $cart_item['woold'] ) || empty( $cart_item['woold']['applied_deal_post_id'] ) ) {
				continue;
			}

			$appicable_deals     = (array) Woold_Lightning_Product::get_current_running_deal_for_product( $cart_item['product_id'], true, true );
			$applicable_deal_ids = wp_list_pluck( $appicable_deals, 'deal_post_id' );
			$applicable_deal_ids = array_map( 'intval', $applicable_deal_ids );

			foreach ( $applicable_deal_ids as $key => &$value ) {
				$applicable_deal_ids[ $key ] = (int) $value;
			}

			if ( ! in_array( (int) $cart_item['woold']['applied_deal_post_id'], $applicable_deal_ids, true ) ) {
				/*
				 * Translators:
				 * 1 - Deal name
				 * 2 - product name
				 * Example: 'Lightning deal' for 'tshirt' is not applicable anymore.
				 */
				$message = sprintf( __( '%1$s for "%2$s" is not applicable anymore.', 'lightning-deal-for-woo' ), wc_clean( $deal_label ), wc_clean( $cart_item['data']->get_title() ) );
				$errors->add( 'validation', $message );
			}
		}
	}
}

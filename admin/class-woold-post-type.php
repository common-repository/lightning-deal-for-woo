<?php

/**
 * The CPT-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Woold
 * @subpackage Woold/admin
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}
/**
 * The CPT-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woold
 * @subpackage Woold/admin
 */
class Woold_Lightning_Posttype {
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
     */
    public function __construct() {
        add_action( 'init', array(__CLASS__, 'regiser_lightning_post_type') );
        add_action( 'add_meta_boxes', array(__CLASS__, 'add_metabox') );
        add_action(
            'save_post_woold_deal',
            array(__CLASS__, 'save_metabox_data'),
            10,
            2
        );
    }

    /**
     * Register lightning deal post type.
     */
    public static function regiser_lightning_post_type() {
        $args = array(
            'label'               => __( 'Lightning Deal', 'lightning-deal-for-woo' ),
            'supports'            => array('title'),
            'taxonomies'          => array(),
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=product',
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-chart-pie',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'page',
        );
        register_post_type( 'woold_deal', $args );
    }

    /**
     * Add metabox.
     */
    public static function add_metabox() {
        add_meta_box(
            'woold_metabox',
            'Lightning Deals',
            array(__CLASS__, 'metabox_output'),
            'woold_deal'
        );
        add_meta_box(
            'woold_stats_metabox',
            'Stats',
            array(__CLASS__, 'metabox_stats_output'),
            'woold_deal'
        );
    }

    /**
     * Output of metabox.
     *
     * @param WP_Post $post Post object.
     */
    public static function metabox_output( $post ) {
        $deal = Woold_Lightning_DB::get_deal_data( $post->ID );
        // Prior to 4.5.0, taxonomy was passed as the first parameter of get_terms(). but in newer versions, its passed as a array item.
        $cat_args = array(
            'taxonomy'   => 'product_cat',
            'orderby'    => 'name',
            'order'      => 'asc',
            'hide_empty' => false,
        );
        $product_categories = get_terms( $cat_args );
        $selected_products = array();
        $datetime = new DateTime('+1 day', wp_timezone());
        $tomorrow_date = $datetime->format( 'Y/m/d H:i' );
        // Set default value of the deal.
        $deal_defaults = array(
            'deal_post_id'                 => 0,
            'products'                     => array(),
            'product_categories'           => array(),
            'object'                       => 'product',
            'start_time'                   => current_time( 'Y/m/d H:i' ),
            'end_time'                     => $tomorrow_date,
            'discount_type'                => 'fixed',
            'discount'                     => 10,
            'max_orders'                   => 10,
            'claim_start_index'            => 0,
            'views'                        => 0,
            'claimed_cart'                 => array(),
            'claimed_ordered'              => 0,
            'max_order_calculation_method' => 'per_product',
        );
        $deal = wp_parse_args( $deal, $deal_defaults );
        foreach ( $deal['products'] as $product_id ) {
            $selected_products[$product_id] = get_the_title( $product_id );
        }
        include WOOLD_TEMPLATE_PATH . 'admin/lightning-deal-metabox.php';
    }

    /**
     * State metabox output.
     *
     * @param Object $post Post.
     */
    public static function metabox_stats_output( $post ) {
        if ( empty( $post ) || 'auto-draft' === $post->post_status ) {
            printf( '<div class="woold-stats__no_data">%s</div>', esc_html__( 'No data available.', 'lightning-deal-for-woo' ) );
            return;
        }
        $deal = Woold_Lightning_DB::get_deal_data( $post->ID );
        if ( empty( $deal ) || empty( $deal['claimed_ordered'] ) ) {
            printf( '<div class="woold-stats__no_data">%s</div>', esc_html__( 'No data available.', 'lightning-deal-for-woo' ) );
            return;
        }
        include WOOLD_TEMPLATE_PATH . 'admin/lightning-deal-stats-metabox.php';
    }

    /**
     * Save metabox data.
     *
     * @param int     $post_id  Post ID.
     * @param WP_Post $post     Post object.
     *
     * @return void
     */
    public static function save_metabox_data( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( !isset( $_POST['woold_discount_fixed'] ) ) {
            return;
        }
        $nonce = filter_input( INPUT_POST, 'woold_post_nonce', FILTER_SANITIZE_SPECIAL_CHARS );
        if ( !isset( $nonce ) || !wp_verify_nonce( $nonce, 'woold_post_nonce' ) ) {
            return;
        }
        $max_orders = filter_input( INPUT_POST, 'woold_max_orders', FILTER_VALIDATE_INT );
        $discount_fixed = filter_input(
            INPUT_POST,
            'woold_discount_fixed',
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );
        $products = filter_input(
            INPUT_POST,
            'woold_products',
            FILTER_SANITIZE_NUMBER_INT,
            FILTER_REQUIRE_ARRAY
        );
        $product_categories = filter_input(
            INPUT_POST,
            'woold_product_categories',
            FILTER_SANITIZE_NUMBER_INT,
            FILTER_REQUIRE_ARRAY
        );
        $start_time = filter_input( INPUT_POST, 'woold_time_start', FILTER_SANITIZE_SPECIAL_CHARS );
        $end_time = filter_input( INPUT_POST, 'woold_time_end', FILTER_SANITIZE_SPECIAL_CHARS );
        $start_time_obj = new DateTime($start_time, wp_timezone());
        $end_time_obj = new DateTime($end_time, wp_timezone());
        $data = array(
            'deal_post_id'                 => $post_id,
            'products'                     => ( $products ? wp_json_encode( $products ) : '' ),
            'product_categories'           => ( $product_categories ? wp_json_encode( $product_categories ) : '' ),
            'object'                       => filter_input( INPUT_POST, 'woold_object', FILTER_SANITIZE_SPECIAL_CHARS ),
            'start_time'                   => $start_time_obj->format( 'Y-m-d H:i:s' ),
            'end_time'                     => $end_time_obj->format( 'Y-m-d H:i:s' ),
            'max_orders'                   => ( empty( $max_orders ) || !is_numeric( $max_orders ) ? $default_max_order : $max_orders ),
            'discount'                     => $discount_fixed,
            'discount_type'                => 'fixed',
            'max_order_calculation_method' => 'per_product',
            'claim_start_index'            => 0,
        );
        Woold_Lightning_DB::insert( $data );
        self::clear_all_transient();
    }

    /**
     * Clear all transient.
     */
    public static function clear_all_transient() {
        global $wpdb;
        $res = $wpdb->query( "delete from {$wpdb->prefix}options where option_name like '%woold_applicable_deal_for_product_%'" );
    }

}

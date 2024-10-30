<?php

/**
 * Database related functions.
 *
 * @package    Woold
 * @subpackage Woold/includes
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}
/**
 * Functions related to Database.
 */
class Woold_Lightning_DB {
    /**
     * Insert a record.
     *
     * @param array $row Row to be instered.
     *
     * @return int|false The number of rows updated, or false on error.
     */
    public static function insert( $row ) {
        global $wpdb;
        $wpdb->show_errors();
        $count = $wpdb->get_var( $wpdb->prepare( "select count(*) as count  from {$wpdb->prefix}woo_lightning_deal where deal_post_id = %d ", $row['deal_post_id'] ) );
        $insert = array(
            'deal_post_id'                 => $row['deal_post_id'],
            'products'                     => $row['products'],
            'product_categories'           => $row['product_categories'],
            'object'                       => $row['object'],
            'start_time'                   => $row['start_time'],
            'end_time'                     => $row['end_time'],
            'discount'                     => $row['discount'],
            'max_orders'                   => $row['max_orders'],
            'discount_type'                => $row['discount_type'],
            'max_order_calculation_method' => $row['max_order_calculation_method'],
            'claim_start_index'            => $row['claim_start_index'],
        );
        $insert_format = array(
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%f',
            '%d',
            '%s',
            '%s',
            '%d'
        );
        if ( !$count ) {
            return $wpdb->insert( $wpdb->prefix . 'woo_lightning_deal', $insert, $insert_format );
        } else {
            $where = array(
                'deal_post_id' => $row['deal_post_id'],
            );
            $where_format = array('%d');
            return $wpdb->update(
                $wpdb->prefix . 'woo_lightning_deal',
                $insert,
                $where,
                $insert_format,
                $where_format
            );
        }
    }

    /**
     * Update order count for a deal, when order is placed.
     *
     * @param int $deal_post_id Deal ID.
     * @return false|int
     */
    public static function update_order_count_stats( $deal_post_id ) {
        global $wpdb;
        if ( !is_numeric( $deal_post_id ) ) {
            return false;
        }
        $deal = self::get_deal_data( $deal_post_id );
        $claimed_orders = $deal['claimed_ordered'];
        // update order count.
        if ( isset( $claimed_orders['order_count'] ) ) {
            ++$claimed_orders['order_count'];
        } else {
            $claimed_orders['order_count'] = 1;
        }
        $update = array(
            'claimed_ordered' => wp_json_encode( $claimed_orders ),
        );
        $where = array(
            'deal_post_id' => $deal_post_id,
        );
        $update_format = array('%s');
        $where_format = array('%d');
        return $wpdb->update(
            $wpdb->prefix . 'woo_lightning_deal',
            $update,
            $where,
            $update_format,
            $where_format
        );
    }

    /**
     * Update order stats.
     *
     * @param int        $deal_post_id   Deal post ID.
     * @param WC_product $product        Product.
     * @param int        $quantity       Quantity to increment stats by.
     *
     * @return false|int
     */
    public static function update_order_stats( $deal_post_id, $product, $quantity = 1 ) {
        global $wpdb;
        if ( !is_numeric( $deal_post_id ) || empty( $product ) ) {
            return false;
        }
        $deal = self::get_deal_data( $deal_post_id );
        $product_id = $product->get_id();
        if ( isset( $deal['claimed_ordered'][$product_id] ) ) {
            $deal['claimed_ordered'][$product_id] += $quantity;
        } else {
            $deal['claimed_ordered'][$product_id] = $quantity;
        }
        // Save total sales too.
        if ( isset( $deal['claimed_ordered']['all'] ) ) {
            $deal['claimed_ordered']['all'] += $quantity;
        } else {
            $deal['claimed_ordered']['all'] = $quantity;
        }
        $insert = array(
            'claimed_ordered' => wp_json_encode( $deal['claimed_ordered'] ),
        );
        $where = array(
            'deal_post_id' => $deal_post_id,
        );
        $insert_format = array('%s');
        $where_format = array('%d');
        return $wpdb->update(
            $wpdb->prefix . 'woo_lightning_deal',
            $insert,
            $where,
            $insert_format,
            $where_format
        );
    }

    /**
     * Get the deal data.
     *
     * @param int $deal_post_id Deal post id.
     *
     * @return array $row
     */
    public static function get_deal_data( $deal_post_id ) {
        global $wpdb;
        $no_row = array();
        $row = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}woo_lightning_deal where deal_post_id = %d", $deal_post_id ), ARRAY_A );
        if ( null === $row ) {
            return $no_row;
        }
        $row['max_order_calculation_method'] = 'per_product';
        $row['discount_type'] = 'fixed';
        $row['claim_start_index'] = 0;
        return self::json_decode( $row );
    }

    /**
     * Json decode the encoded attributes i.e. product_categories, products.
     *
     * @param array $row Row.
     *
     * @return array
     */
    public static function json_decode( $row ) {
        if ( !is_array( $row ) ) {
            return false;
        }
        if ( isset( $row['products'] ) && $row['products'] ) {
            $row['products'] = json_decode( $row['products'], true );
            $row['products'] = array_map( 'absint', $row['products'] );
        } else {
            $row['products'] = array();
        }
        if ( isset( $row['product_categories'] ) && $row['product_categories'] ) {
            $row['product_categories'] = json_decode( $row['product_categories'], true );
            $row['product_categories'] = array_map( 'absint', $row['product_categories'] );
        } else {
            $row['product_categories'] = array();
        }
        $row['claimed_ordered'] = json_decode( $row['claimed_ordered'], true );
        if ( !is_array( $row['claimed_ordered'] ) ) {
            $row['claimed_ordered'] = array();
        }
        return $row;
    }

    /**
     * Trash a deal, when a deal custom post type is trashed.
     *
     * @param int $deal_id Deal id to delete.
     * @return int|false The number of rows deleted, or false on error.
     */
    public static function trash_deal( $deal_id ) {
        global $wpdb;
        $where = array(
            'deal_post_id' => $deal_id,
        );
        return $wpdb->delete( $wpdb->prefix . 'woo_lightning_deal', $where, array('%d') );
    }

}

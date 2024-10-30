<?php
/**
 * WordPress Settings Framework
 *
 * @package Woold
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_filter( 'wpsf_register_settings_woold', 'woold_settings' );

/**
 * Settings for Woold.
 *
 * @param array $wpsf_settings An array of settings.
 *
 * @return array $wpsf_settings Settings array.
 */
function woold_settings( $wpsf_settings ) {
	// Tabs.
	$wpsf_settings['tabs'] = array(
		array(
			'id'    => 'general',
			'title' => __( 'General', 'lightning-deal-for-woo' ),
		),
		array(
			'id'    => 'display',
			'title' => __( 'Display', 'lightning-deal-for-woo' ),
		),
	);

	// Settings.
	$wpsf_settings['sections'] = array(
		array(
			'tab_id'        => 'general',
			'section_id'    => 'general',
			'section_title' => __( 'General', 'lightning-deal-for-woo' ),
			'section_order' => 10,
			'fields'        => array(
				array(
					'id'      => 'label',
					'title'   => __( 'Label', 'lightning-deal-for-woo' ),
					'desc'    => __( 'Label to use for "Lightning Deal"', 'lightning-deal-for-woo' ),
					'type'    => 'text',
					'default' => 'Lightning Deal',
				),
				array(
					'id'      => 'bar_condition',
					'title'   => __( 'Show Claimed bar if', 'lightning-deal-for-woo' ),
					'type'    => 'select',
					'choices' => array(
						'always'            => __( 'Always Show', 'lightning-deal-for-woo' ),
						'more_than_percent' => __( 'More than x% is claimed', 'lightning-deal-for-woo' ),
						'dont'              => __( 'Never show', 'lightning-deal-for-woo' ),
					),
					'default' => 'always',
				),
				array(
					'id'      => 'bar_condition_percentage',
					'title'   => __( 'Bar Threshold Percentage', 'lightning-deal-for-woo' ),
					'desc'    => __( 'Set the percentage at which the claimed bar will display to customers.', 'lightning-deal-for-woo' ),
					'type'    => 'number',
					'default' => '10',
				),
				array(
					'id'      => 'max_quantity',
					'title'   => __( 'Maximum Cart Items limit', 'lightning-deal-for-woo' ),
					'desc'    => __( 'Global maximum quantity of any deal product that a customer can purchase. Leave empty for unlimited.', 'lightning-deal-for-woo' ),
					'type'    => 'number',
					'default' => '',
				),
			),
		),
		array(
			'tab_id'        => 'display',
			'section_id'    => 'display',
			'section_title' => __( 'Display', 'lightning-deal-for-woo' ),
			'section_order' => 10,
			'fields'        => array(
				array(
					'id'      => 'position_single_page',
					'title'   => __( 'Position on Single Product Page', 'lightning-deal-for-woo' ),
					'desc'    => __( 'Position of the lightning deal section on the Single Product page.', 'lightning-deal-for-woo' ),
					'type'    => 'select',
					'choices' => array(
						'woocommerce_before_add_to_cart_form'     => __( 'Before Add to cart form', 'lightning-deal-for-woo' ),
						'woocommerce_before_add_to_cart_button'   => __( 'Before Add to cart button', 'lightning-deal-for-woo' ),
						'woocommerce_before_single_variation'     => __( 'Before single variation', 'lightning-deal-for-woo' ),
						'woocommerce_before_add_to_cart_quantity' => __( 'Before Add to cart quantity', 'lightning-deal-for-woo' ),
						'woocommerce_after_add_to_cart_quantity'  => __( 'After Add to cart quantity', 'lightning-deal-for-woo' ),
						'woocommerce_after_single_variation'      => __( 'After single variation', 'lightning-deal-for-woo' ),
						'woocommerce_after_add_to_cart_button'    => __( 'After Add to cart button', 'lightning-deal-for-woo' ),
					),
					'default' => 'woocommerce_before_add_to_cart_button',
				),
				array(
					'id'      => 'position_archive',
					'title'   => __( 'Position on Product Archive Pages.', 'lightning-deal-for-woo' ),
					'desc'    => __( 'Position of the lightning deal section on the Product Archive pages.', 'lightning-deal-for-woo' ),
					'type'    => 'select',
					'choices' => array(
						'woocommerce_before_shop_loop_item' => __( 'Before shop loop item', 'lightning-deal-for-woo' ),
						'woocommerce_before_shop_loop_item_title' => __( 'Before title', 'lightning-deal-for-woo' ),
						'woocommerce_after_shop_loop_item_title' => __( 'After title', 'lightning-deal-for-woo' ),
						'woocommerce_after_shop_loop_item' => __( 'After shop loop item', 'lightning-deal-for-woo' ),
					),
					'default' => 'woocommerce_after_shop_loop_item',
				),
				array(
					'id'      => 'max_width_single_product',
					'title'   => __( 'Maximum width', 'lightning-deal-for-woo' ),
					'class'   => 'woold_deal_max_width',
					'desc'    => __( 'Maximum width for the Lightning Deal section on the Single product page.', 'lightning-deal-for-woo' ),
					'type'    => 'number',
					'default' => '400',
				),
			),
		),
		array(
			'tab_id'        => 'display',
			'section_id'    => 'colors',
			'section_title' => 'Colors',
			'section_order' => 10,
			'fields'        => array(
				array(
					'id'      => 'filed_bar',
					'title'   => __( 'Filled Bar color', 'lightning-deal-for-woo' ),
					'type'    => 'color',
					'default' => '#000',
				),
				array(
					'id'      => 'empty_bar',
					'title'   => __( 'Empty Bar color', 'lightning-deal-for-woo' ),
					'type'    => 'color',
					'default' => '#e6e6e6',
				),
			),
		),
	);

	return $wpsf_settings;
}

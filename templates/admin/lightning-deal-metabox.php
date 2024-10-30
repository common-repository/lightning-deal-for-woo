<?php

/**
 * Lightning deal metabox HTML output.
 *
 * @package woold
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}
?>
<div class="woold-mb">
	<div class="woold-mb__row">
		<div class="wooold-mb__col_left">
			<label><?php 
esc_html_e( 'Enable this deal for', 'lightning-deal-for-woo' );
?></label>
		</div>
		<div class="wooold-mb__col_right">
			<select name="woold_object" id="woold_mb_object">
				<option value="product" <?php 
selected( 'product', $deal['object'] );
?> ><?php 
esc_html_e( 'Specific Products', 'lightning-deal-for-woo' );
?></option>
				<option value="product_category" <?php 
selected( 'product_category', $deal['object'] );
?>><?php 
esc_html_e( 'Product Category', 'lightning-deal-for-woo' );
?></option>
			</select>
		</div>
	</div>

	<div class="woold-mb__row">
		<div class="wooold-mb__col_left">
			<label><?php 
esc_html_e( 'Products or Category', 'lightning-deal-for-woo' );
?></label>
		</div>
		<div class="wooold-mb__col_right woold-mb-wrap-objects" >
			<div class="woold-mb-object--category">
				<label><?php 
esc_html_e( 'Search Product categories:', 'lightning-deal-for-woo' );
?></label>
				<select placeholder="Search categories.."  name="woold_product_categories[]" id="woold_product_categories" multiple>
					<?php 
foreach ( $product_categories as $category ) {
    $selected = in_array( $category->term_id, $deal['product_categories'], true );
    ?>
						<option <?php 
    selected( true, $selected );
    ?> value="<?php 
    echo esc_attr( $category->term_id );
    ?>"><?php 
    echo esc_html( $category->name );
    ?></option>
						<?php 
}
?>
				</select>
			</div>
			<div class="woold-mb-object--product">
				<label><?php 
esc_html_e( 'Search Products:', 'lightning-deal-for-woo' );
?></label>
				<select placeholder="Search products.." name="woold_products[]" id="woold_products" multiple>
					<?php 
foreach ( $selected_products as $product_id => $selected_product ) {
    printf( '<option selected value="%s">%s</option>', esc_attr( $product_id ), esc_attr( $selected_product ) );
}
?>
				</select>
			</div>
		</div>
	</div>

	<div class="woold-mb__row">
		<div class="wooold-mb__col_left">
			<label><?php 
esc_html_e( 'Time:', 'lightning-deal-for-woo' );
?></label>
		</div>
		<div class="wooold-mb__col_right">
			<div class="woold-mb-time">
				<div class="woold-mb-time__col">
					<span><?php 
esc_html_e( 'Start:', 'lightning-deal-for-woo' );
?></span> 
					<input type="datetime" name='woold_time_start' id='woold_time_start' value="<?php 
echo esc_attr( $deal['start_time'] );
?>">
				</div>
				<div class="woold-mb-time__col">
					<span><?php 
esc_html_e( 'End:', 'lightning-deal-for-woo' );
?></span> 
					<input type="datetime" name='woold_time_end' id="woold_time_end" value="<?php 
echo esc_attr( $deal['end_time'] );
?>">
				</div>
			</div>
		</div>
	</div>
	<div class="woold-mb__row">
		<div class="wooold-mb__col_left">
			<label><?php 
esc_html_e( 'Discount', 'lightning-deal-for-woo' );
?></label>
		</div>
		<?php 
?>
		<div class="wooold-mb__col_right">
			<div class="woold-mb-discount-fixed">
				<div class="woold_discount_type_wrap">
				<input type="hidden" name="woold_discount_type" id="woold_discount_type" value="<?php 
esc_html_e( 'Fixed', 'lightning-deal-for-woo' );
?>">
					<input type="number" name="woold_discount_fixed" id="woold_discount_fixed" value="<?php 
echo esc_attr( $deal['discount'] );
?>"><?php 
echo esc_html( get_woocommerce_currency_symbol() );
?>
				</div>
			</div>
		</div>
		<?php 
?>
	</div>
	<div class="woold-mb__row">
		<div class="wooold-mb__col_left">
			<label><?php 
esc_html_e( 'Max orders limit', 'lightning-deal-for-woo' );
?></label>
		</div>
		<div class="wooold-mb__col_right">
			<input type="number" id="woold_max_orders" name="woold_max_orders" value="<?php 
echo esc_attr( $deal['max_orders'] );
?>">
		</div>
	</div>
	
	<div class="woold-mb__row">
		<div class="wooold-mb__col_left">
			<label><?php 
esc_html_e( 'Claimed deals starting index', 'lightning-deal-for-woo' );
?></label>
		</div>
		
		<div class="wooold-mb__col_right">

		<?php 
echo wp_kses_post( Woold_Admin::render_upgrade_to_pro_button() );
?>
		</div>
	</div>
	
	<div class="woold-mb__row">
		<div class="wooold-mb__col_left">
			<label><?php 
esc_html_e( 'Max orders calculation method', 'lightning-deal-for-woo' );
?></label>
		</div>
		
		<div class="wooold-mb__col_right">

		<?php 
echo wp_kses_post( Woold_Admin::render_upgrade_to_pro_button() );
?>
		</div>
	</div>
	
	<?php 
wp_nonce_field( 'woold_post_nonce', 'woold_post_nonce' );
?>
</div>

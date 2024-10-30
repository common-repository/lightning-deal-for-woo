<?php
/**
 * Lightning deal on single product page.
 *
 * @package Woold
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<?php
do_action( 'woold_lightning_bar_before_deal' );

if ( ! isset( $single_max_width ) ) {
	$single_max_width = 'none';
}
?>
<div class="woold" style="max-width: <?php echo esc_attr( $single_max_width ); ?>">

	<?php if ( $bar_enabled ) { ?>
		<?php do_action( 'woold_lightning_bar_before_bar' ); ?>
		<div class="woold__bar">
			<div class="woold__bar__bg">
				<div class="woold__bar__filled" style="width: <?php echo esc_attr( $deal['claimed_percent'] ); ?>%"></div>
			</div>
		</div>

		<?php do_action( 'woold_lightning_bar_after_bar' ); ?>

		<div class='woold__meta'>
			<div class="woold__claimed" >
				<?php
					// Translators: Deal claimed percentage.
					printf( esc_html__( '%1$d%% claimed', 'lightning-deal-for-woo' ), esc_attr( $deal['claimed_percent'] ) );
				?>
			</div>

			<div class="woold__ends_in" >
				Ends in <span class="woold_ends_in_time" data-time-left="<?php echo esc_attr( $deal['time_left'] ); ?>"></span>
			</div>
			<?php if ( $is_deal_in_cart ) { ?>
				<div class="woold__deal_already_in_cart">
					<?php esc_html_e( 'This deal is in your cart', 'lightning-deal-for-woo' ); ?>
				</div>
			<?php } ?>
		</div>

		<?php do_action( 'woold_lightning_bar_after_meta' ); ?>

	<?php } ?>
	<?php echo '<span class="onsale">' . esc_html( Woold_Settings::get_label() ); ?>


	<input type="hidden" name='woold_deal_post_id' value='<?php echo esc_attr( $deal['deal_post_id'] ); ?>' />
</div>

<?php do_action( 'woold_lightning_bar_after_deal' ); ?>

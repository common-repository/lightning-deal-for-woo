<?php

/**
 * Admin: stats meta box template.
 *
 * @package    Woold
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}
$overlay_class = ( woold_fs()->can_use_premium_code__premium_only() ? '' : 'woold-stats-mb--overlay' );
if ( '' !== $overlay_class ) {
    ?>
	<div class="woold-available-in-pro">
	<?php 
    echo esc_html__( 'Available in Pro version only.', 'lightning-deal-for-woo' );
    echo wp_kses_post( Woold_Admin::render_upgrade_to_pro_button() );
    ?>
	</div>
	<?php 
}
?>
<div class="woold-stats-mb <?php 
echo wp_kses_post( $overlay_class );
?>">
	
	<?php 
?>
</div>

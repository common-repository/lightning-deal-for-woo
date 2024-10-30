(function ($) {
	'use strict';

	var woold_metabox = {
		on_ready: function () {
			$( '#woold_metabox input, #woold_metabox select, #woold_metabox textrea' ).change( woold_metabox.input_change );
			woold_metabox.input_change();
			woold_metabox.setup_select2();
			woold_metabox.setup_timepicker();
			woold_metabox.validate_form();
		},

		/**
		 * Show/hide the UI elements based on choices.
		 */
		input_change: function () {
			var object = $( "#woold_mb_object" ).val();
			if ('product' == object) {
				$( ".woold-mb-object--category" ).hide();
				$( ".woold-mb-object--product" ).show();
			} else {
				$( ".woold-mb-object--category" ).show();
				$( ".woold-mb-object--product" ).hide();
			}

			var discount_type = $( "#woold_discount_type" ).val();
			if ('fixed' === discount_type) {
				$( ".woold-mb-discount--fixed" ).show();
				$( ".woold-mb-discount--percent" ).hide();
			} else {
				$( ".woold-mb-discount--percent" ).show();
				$( ".woold-mb-discount--fixed" ).hide();
			}
		},

		setup_select2: function () {
			$( '#woold_product_categories' ).select2(
				{
					placeholder: $( '#woold_product_categories' ).attr( 'placeholder' ),
				}
			);

			$( '#woold_products' ).select2(
				{
					ajax: {
						url: ajaxurl,
						dataType: 'json',
						delay: 250,
						placeholder: $( '#woold_products' ).attr( 'placeholder' ),
						data: function (params) {
							return {
								term: params.term,
								action: 'woocommerce_json_search_products',
								security: venus_woold.product_nonce,
							};
						},
						processResults: function (data) {
							var options = [];
							if (data) {
								for (var key in data) {
									options.push( { "id": key, "text": data[key] } );
								}
							}
							return {
								results: options
							};
						},
						cache: true
					},
					minimumInputLength: 3 // the minimum of symbols to input before perform a search
				}
			);
		},

		setup_timepicker: function () {
			$( "#woold_time_start" ).datetimepicker();
			$( "#woold_time_end" ).datetimepicker();
		},

		validate_form: function () {
			$( "form#post" ).submit(
				function () {
					var max_orders        = parseInt( $( "#woold_max_orders" ).val() );
					var claim_start_index = parseInt( $( "#woold_csi" ).val() );
					var start             = $( "#woold_time_start" ).val();
					var end               = $( "#woold_time_end" ).val();
					var start_obj, end_obj;

					try {
						start_obj = new Date( start );
					} catch (ex) {
						alert( venus_woold.i18n.invalid_start_date );
					}

					try {
						end_obj = new Date( end );
					} catch (ex) {
						alert( venus_woold.i18n.invalid_end_date );
					}

					if (start_obj && end_obj && start_obj.getTime() > end_obj.getTime()) {
						alert( venus_woold.i18n.start_time_less_than_end );
						return false;
					}

					if (max_orders <= claim_start_index) {
						alert( venus_woold.i18n.claim_less_than_max_orders );
						return false;
					}

				}
			);
		}
	};

	var woold_settings = {
		on_ready: function () {
			woold_settings.toggle_bar_threshold_field();
			$( "#general_general_bar_condition" ).change( woold_settings.toggle_bar_threshold_field );
		},
		toggle_bar_threshold_field: function () {
			var bar_condition = $( "#general_general_bar_condition" ).val();
			var $threshold_tr = $( "#general_general_bar_condition_percentage" ).parent().parent();

			if ('more_than_percent' === bar_condition) {
				$threshold_tr.show();
			} else {
				$threshold_tr.hide();
			}
		}
	};

	$(
		function () {
			if ('woold_deal' == pagenow) {
				woold_metabox.on_ready();
			} else if ('woocommerce_page_woold-settings' == pagenow) {
				woold_settings.on_ready();
			}
		}
	);
})( jQuery );

(function ($) {
	'use strict';

	var woold = {
		on_ready: function () {
			// For each form.
			$( '.variations_form' ).each(
				function () {
					$( this ).on( 'found_variation', { variationForm: this }, woold.on_variation_found );
				}
			);

			woold.product_archive_assign_deal_id_to_add_to_cart();
		},

		on_variation_found: function (event, variation) {
			if ( ! variation.woold_price_html) {
				return;
			}

			// Update price.
			// Delay execution.
			window.setTimeout(
				() => {
                var form   = event.data.variationForm;
                var $price = form.$form.find( '.woocommerce-variation-price' );
                $price.html( variation.woold_price_html );
				},
				0
			);
		},

		product_archive_assign_deal_id_to_add_to_cart: function () {

			$( "a.add_to_cart_button" ).each(
				function () {
					var $input = $( this ).closest( '.product' ).find( "[name=woold_deal_post_id]" );

					if ( ! $input.length || ! $( this ).length) {
						return;
					}

					$( this ).attr( 'data-woold_deal_post_id', $input.val() )
					var href = $( this ).attr( 'href' );

					if (href.includes( 'woold_deal_post_id' )) {
						return;
					}

					var woold_page_url = new URL( woold_data.current_url + href );
					woold_page_url.searchParams.append( 'woold_deal_post_id', $input.val() );
					$( this ).attr( 'href', woold_page_url );

				}
			);
		}

	}

	var woold_timer = {
		on_ready: function () {
			$( '.woold_ends_in_time' ).each(
				function () {
					woold_timer.start_timer_for_element( $( this ) );
				}
			);
		},

		start_timer_for_element: function ($element) {

			var time_left = $element.data( 'time-left' );
			if (typeof time_left === 'undefined') {
				return;
			}

			var intervalHandler = window.setInterval(
				function ($element) {

					var time_left = $element.data( 'time-left' );
					if (typeof time_left === 'undefined') {
						return;
					}

					time_left--;
					$element.html( woold_timer.format_date( time_left ) );
					$element.data( 'time-left', time_left );

					if (0 >= time_left) {

						if ($( 'body' ).hasClass( 'single' )) {
							window.location.reload();
						}

						clearTimeout( intervalHandler );
					}
				},
				1000,
				$element
			);
		},

		format_date: function (seconds) {
			var days    = Math.floor( seconds / (60 * 60 * 24) );
			var hours   = Math.floor( (seconds / (60 * 60)) % 24 );
			var minutes = Math.floor( (seconds / 60) % 60 );
			var seconds = Math.floor( seconds % 60 );

			var _return = "";

			if (days) {
				_return = days + "d";
			}

			_return += " " + hours + "h " + minutes + "m " + seconds + "s";

			return _return;
		}

	}

	$( document ).ready( woold.on_ready );
	$( document ).ready( woold_timer.on_ready );

})( jQuery );

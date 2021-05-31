jQuery( function( $ ) {
	'use strict';

	/**
	 * Object to handle Xendit admin functions.
	 */
	var wc_xendit_admin = {
		isTestMode: function() {
			return $( '#woocommerce_xendit_gateway_developmentmode' ).is( ':checked' );
		},

		getSecretKey: function() {
			if ( wc_xendit_admin.isTestMode() ) {
				return $( '#woocommerce_xendit_gateway_secret_key_dev' ).val();
			} else {
				return $( '#woocommerce_xendit_gateway_secret_key' ).val();
			}
		},

		/**
		 * Initialize.
		 */
		init: function() {
			// Toggle Xendit Checkout settings.
			$( '#woocommerce_xendit_xendit_checkout' ).change( function() {
				if ( $( this ).is( ':checked' ) ) {
					$( '#woocommerce_xendit_xendit_checkout_locale, #woocommerce_xendit_xendit_bitcoin, #woocommerce_xendit_xendit_checkout_image' ).closest( 'tr' ).show();
					$( '#woocommerce_xendit_request_payment_api' ).closest( 'tr' ).hide();
				} else {
					$( '#woocommerce_xendit_xendit_checkout_locale, #woocommerce_xendit_xendit_bitcoin, #woocommerce_xendit_xendit_checkout_image' ).closest( 'tr' ).hide();
					$( '#woocommerce_xendit_request_payment_api' ).closest( 'tr' ).show();
				}
			}).change();

			// Validate the keys to make sure it is matching test with test field.
			$( '#woocommerce_xendit_gateway_secret_key, #woocommerce_xendit_gateway_api_key' ).on( 'input', function() {
				var value = $( this ).val();

				if ( value.indexOf( '_test_' ) >= 0 ) {
					$( this ).css( 'border-color', 'red' ).after( '<span class="description xendit-error-description" style="color:red; display:block;">' + wc_xendit_admin_params.localized_messages.not_valid_live_key_msg + '</span>' );
				} else {
					$( this ).css( 'border-color', '' );
					$( '.xendit-error-description', $( this ).parent() ).remove();
				}
			}).trigger( 'input' );

			// Validate the keys to make sure it is matching live with live field.
			$( '#woocommerce_xendit_gateway_secret_key_dev, #woocommerce_xendit_gateway_api_key_dev' ).on( 'input', function() {
				var value = $( this ).val();

				if ( value.indexOf( '_live_' ) >= 0 ) {
					$( this ).css( 'border-color', 'red' ).after( '<span class="description xendit-error-description" style="color:red; display:block;">' + wc_xendit_admin_params.localized_messages.not_valid_test_key_msg + '</span>' );
				} else {
					$( this ).css( 'border-color', '' );
					$( '.xendit-error-description', $( this ).parent() ).remove();
				}
			}).trigger( 'input' );

			// Hide this fields for now
			/*$( '#woocommerce_xendit_statement_descriptor' ).parents( 'tr' ).eq( 0 ).hide();

			$( '#woocommerce_xendit_statement_descriptor' ).parent().after('<p id="xendit_descriptor_info"></p>');

			$( '#woocommerce_xendit_statement_descriptor' ).on( 'input', function () {
				var value = $( this ).val();
				var defaultInfo = '"XENDIT*${YOUR_BUSINESS_NAME}';
				var infoEnding = 'will be shown in your customer billing statement';

				$( '#xendit_descriptor_info' ).text(defaultInfo + ' - ' + value + '" ' + infoEnding);

				if (value === '') {
					$( '#xendit_descriptor_info' ).text(defaultInfo + '" ' + infoEnding);
				}
			}).trigger('input');*/
		}
	};

	wc_xendit_admin.init();
});

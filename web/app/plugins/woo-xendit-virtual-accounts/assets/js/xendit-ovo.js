jQuery( function( $ ) {
    'use strict';

    /**
     * Object to handle Xendit payment forms.
     */
    var wc_xendit_form = {

        /**
         * Initialize event handlers and UI state.
         */
        init: function() {
            // checkout page
            if ( $( 'form.woocommerce-checkout' ).length ) {
                this.form = $( 'form.woocommerce-checkout' );
            }

            $( 'form.woocommerce-checkout' )
                .on(
                    'checkout_place_order_xendit_ovo',
                    this.onSubmit
                );

            // pay order page
            if ( $( 'form#order_review' ).length ) {
                this.form = $( 'form#order_review' );
            }

            $( 'form#order_review' )
                .on(
                    'submit',
                    this.onSubmit
                );

            // add payment method page
            if ( $( 'form#add_payment_method' ).length ) {
                this.form = $( 'form#add_payment_method' );
            }

            $( 'form#add_payment_method' )
                .on(
                    'submit',
                    this.onSubmit
                );

            $( document )
                .on(
                    'change',
                    '#wc-xendit_ovo-cc-form :input',
                    this.onOVOPhoneNumberChange
                )
                .on(
                    'checkout_error',
                    this.clearXenditForm
                )
        },

        block: function() {
            if ($("[class='xendit-overlay-box']").length === 0) {
                var overlayDiv = $( "<div class='xendit-overlay-box'>" +
                    "<div id='xendit-overlay-content'>\n" +
                    "  <span class='xendit-overlay-text' style='margin-top: 80px;'>Periksa kembali telepon selular Anda, buka aplikasi Ovo anda dan</span>\n" +
                    "  <span class='xendit-overlay-text'>konfirmasikan transaksi anda dengan memasukkan PIN</span>" +
                    "</div>" +
                    "</div>" );
                $( 'body' ).append(overlayDiv);
            }

            $( '.xendit-overlay-box' ).show();
            return;
        },

        isXenditChosen: function() {
            return $( '#payment_method_xendit_ovo' ).is( ':checked' );
        },

        hasOvoPhoneNumber: function() {
            return 0 < $( 'input.xendit_ovo_phone' ).length;
        },

        onSubmit: function( e ) {
            if ( wc_xendit_form.isXenditChosen() && ! wc_xendit_form.hasOvoPhoneNumber()) {
                // #xendit- prefix comes from wc-gateway-xendit->id field
                var ovoPhoneNumber = $( '#xendit_ovo_phone' ).val().replace(/\s/g, '');

                wc_xendit_form.form.append( "<input type='hidden' class='xendit_ovo_phone' name='xendit_ovo_phone' value='" + ovoPhoneNumber + "'/>" );

                wc_xendit_form.block();
                return;
            }
        },

        onOVOPhoneNumberChange: function() {
            $( '.xendit_ovo_phone' ).remove();
        },

        clearField: function() {
            $( '.xendit_ovo_phone' ).remove();
        },

        clearXenditForm: function() {
            wc_xendit_form.clearField();

            if ($("[class='xendit-overlay-box']").length > 0) {
                $('.xendit-overlay-box').hide();
            }
        }
    };

    wc_xendit_form.init();
} );
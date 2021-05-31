/* global wc_xendit_params */
Xendit.setPublishableKey( wc_xendit_params.key );
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
					'checkout_place_order_xendit_cc',
					this.onSubmit
				);

			// pay order page
			if ( $( 'form#order_review' ).length ) {
				this.form = $( 'form#order_review' );
			}

			$( 'form#order_review, form#add_payment_method' )
				.on(
					'submit',
					this.onSubmit
				);

			// add payment method page
			if ( $( 'form#add_payment_method' ).length ) {
				this.form = $( 'form#add_payment_method' );
			}

			$( document )
				.on(
					'change',
					'#wc-xendit_cc-cc-form :input',
					this.onCCFormChange
				)
				.on(
					'checkout_error',
					this.clearToken
				)
				.ready(function () {
					$('body').append('<div class="overlay" style="display: none;"></div>' +
		            	'<div id="three-ds-container" style="display: none;">' +
		                	'<iframe height="450" width="550" id="sample-inline-frame" name="sample-inline-frame"> </iframe>' +
		            	'</div>');

					$('.entry-content .woocommerce').prepend('<div id="woocommerce-error-custom-my" class="woocommerce-error" style="display:none"></div>');

					$('.overlay').css({'position': 'absolute','top': '0','left': '0','height': '100%','width': '100%','background-color': 'rgba(0,0,0,0.5)','z-index': '10'});
					$('#three-ds-container').css({'width': '550px','height': '450px','line-height': '200px','position': 'fixed','top': '25%','left': '40%','margin-top': '-100px','margin-left': '-150px','background-color': '#ffffff','border-radius': '5px','text-align': 'center','z-index': '9999'});					
				});
		},

		isXenditChosen: function() {
			return $('#payment_method_xendit_cc').is(':checked') || ($('#payment_method_xendit_cc').is(':checked') && 'new' === $('input[name="wc-xendit_cc-payment-token"]:checked').val());
		},

		hasToken: function() {
			return 0 < $('input[name="xendit_token"]').length;
		},

		hasError: function() {
			return 0 < $('input[name="xendit_failure_reason"]').length;
		},

		block: function() {
			wc_xendit_form.form.block({
				message: null,
				overlayCSS: {
					background: '#000',
					opacity: 0.5
				}
			});
		},

		handleError: function( err ) {
			var failure_reason;
			if(typeof err != 'undefined') {
				failure_reason = err.message || err.error_code; 
			} else {
				failure_reason = 'We encountered an issue while processing the checkout. Please contact us. Code: 200035';
			}

			wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_failure_reason' value='" + failure_reason + "'/>" );
			return true;
		},

		onSubmit: function( e ) {
			if (!wc_xendit_form.isXenditChosen() || wc_xendit_form.hasToken() || wc_xendit_form.hasError()) {
				return true;
			}
			else {
				e.preventDefault();
				wc_xendit_form.block();

				if (wc_xendit_params.has_saved_cards) {
					// if using a saved card on checkout, submit form immediately
					var ccToken = $('input[name="wc-xendit_cc-payment-token"]:checked').val();
					
					if (ccToken && ccToken != 'new') {
						// we append xendit_token here to avoid recursive onSubmit
						// in backend, we will get the actual token from `wc-xendit_cc-payment-token`
						wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_token' value='" + ccToken + "'/>" );
						wc_xendit_form.form.submit();

						return false;
					}
				}

				// #xendit_cc- prefix comes from wc-gateway-xendit->id field
				var card       = $('#xendit_cc-card-number').val().replace(/\s/g, '');
				var cvn        = $('#xendit_cc-card-cvc').val();
				var expires    = $('#xendit_cc-card-expiry').val().split(" / ");
				var fullYear = new Date().getFullYear();

				expires = {
					month: expires[0],
					year: String(fullYear).substr(0, 2) + expires[1]
				};

				// check if all card details are not empty
				if (!card || !cvn || !$( '#xendit_cc-card-expiry' ).val()) {
					var err = {
						message: wc_xendit_params.missing_card_information
					}
					return wc_xendit_form.handleError(err);
				}

				// allow 15 digits for AMEX & 16 digits for others
				if (card.length != 16 && card.length != 15) {
					var err = {
						message: wc_xendit_params.incorrect_number
					}
					return wc_xendit_form.handleError(err);
				}

				// validate card number
				if (!Xendit.card.validateCardNumber(card)) {
					var err = {
						message: wc_xendit_params.invalid_number
					}
					return wc_xendit_form.handleError(err);
				}

				// validate expiry format MM / YY
				if ($( '#xendit_cc-card-expiry' ).val().length != 7) {
					var err = {
						message: wc_xendit_params.invalid_expiry
					}
					return wc_xendit_form.handleError(err);
				}

				// validate cvc
				if (cvn.length < 3) {
					var err = {
						message: wc_xendit_params.invalid_cvc
					}
					return wc_xendit_form.handleError(err);
				}
				
				var data = {
					"card_number"   	: card,
					"card_exp_month"	: String(expires.month).length === 1 ? '0' + String(expires.month) : String(expires.month),
					"card_exp_year" 	: String(expires.year),
					"card_cvn"      	: cvn,
					"is_multiple_use"	: true,
					"on_behalf_of"		: wc_xendit_params.on_behalf_of,
					"currency"			: wc_xendit_params.currency
				};
				var card_type = wc_xendit_form.getCardType();

				wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_card_number' value='" + data.card_number + "'/>" );
				wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_card_exp_month' value='" + data.card_exp_month + "'/>" );
				wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_card_exp_year' value='" + data.card_exp_year + "'/>" );
				wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_card_cvn' value='" + data.card_cvn + "'/>" );
				wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_card_type' value='" + card_type + "'/>" );

				if ($("#xendit-installment-option").length) {
					var value = document.querySelector('input[name="xendit_installments"]:checked').value;
					wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_installment' value='" + value + "'/>" );
				}

				Xendit.card.createToken( data, wc_xendit_form.onTokenizationResponse );

				// Prevent form submitting
				return false;
			}
		},

		onCCFormChange: function(e) {
			$('.xendit_cc_hidden_input').remove();

			//format expiry to MM / YY
			$('#xendit_cc-card-expiry').prop('maxlength', 7);

			//change cvc into password field
			$('#xendit_cc-card-cvc').prop('type', 'password');

			if (e.target.id === 'xendit_cc-card-number') {
				// Remove installment option if CC number is changed
				if ($("#xendit-installment-option").length) {
					$("#xendit-installment-option").remove();
				}
				if ($("#xendit-promotion-option").length) {
					$("#xendit-promotion-option").remove();
				}

				var cardNumber = $('#xendit_cc-card-number').val().replace(/\s/g, '');
	
				if (Xendit.card.validateCardNumber(cardNumber)) {
					var data = {
						bin: cardNumber.substr(0, 6),
						amount: wc_xendit_params.amount,
						currency: wc_xendit_params.currency
					};

					Xendit.card.getChargeOption(data, wc_xendit_form.onGetChargeOptionResponse);
				}
			}
		},

		onGetChargeOptionResponse: function (err, res) {
			if (err) {
				// If error, don't disturb checkout flow
				console.log('Unable to retrieve charge option', err);
				return;
			}

			// Quit process if no installment or promotion available
			if (!res.installments && !res.promotions) {
				return;
			}

			//  Reflect final amount
			var selectedPayment = $('input[name="payment_method"]:checked').val();
			if (res.promotions.length && selectedPayment == 'xendit_cc') {
				var promotion = res.promotions[0];

				$("#wc-xendit_cc-cc-form").append("<div id='xendit-promotion-option' style='color:red'>Discount applied! Final amount: <b>" + wc_xendit_params.currency + " " + promotion.final_amount.toLocaleString() + "</b></div>");
				wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_promotion_final_amount' value='" + promotion.final_amount + "'/>" );
				wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_promotion_description' value='" + promotion.description + "'/>" );
			}

			// Sort installment option from smallest count
			if (res.installments.length) {
				var installments = res.installments.sort(function (x, y) {
					return x.count - y.count
				});			

				let radioHTML = '<table>' +
									'<tr><td style="padding:5px 0 0 0;">Available installment options</td></tr>' +
									'<tr><td style="padding:0;"><input type="radio" name="xendit_installments" value="" checked>&nbsp; Full payment</label></td></tr>';

				// Generate radio button for each installment option
				$.each(installments, function(index, value) {
					var dividedAmount = Math.round(wc_xendit_params.amount / value.count).toLocaleString();
					var label = `${value.count} ${value.interval} (~${value.currency} ${dividedAmount}/${value.interval})`;

					radioHTML += '<tr><td style="padding:0;"><input type="radio" name="xendit_installments" value=' + JSON.stringify({
						count: value.count,
						interval: value.interval
					}) + '>&nbsp; ' + label + '</td></tr>';
				});

				radioHTML += '</table>'

				$("#wc-xendit_cc-cc-form").append("<div id='xendit-installment-option'>" + radioHTML + "</div>");
			}
		},

		onTokenizationResponse: function(err, response) {
			if (err) {
				var failure_reason = err.message;
				if (err.error_code == 'INVALID_USER_ID') {
					failure_reason = 'Invalid sub-account value. Please check your "On Behalf Of" configuration on XenPlatform option. Code: 100004';
				} else if (err.error_code == 'VALIDATION_ERROR') {
					failure_reason = 'Please verify that the credit card information is correct. Code: 200003';
				}
				wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_failure_reason' value='" + failure_reason + "'/>" );
				
				wc_xendit_form.form.submit();
				return false;
			}
			var token_id = response.id;

			wc_xendit_form.form.append("<input type='hidden' class='xendit_cc_hidden_input' name='xendit_token' value='" + token_id + "'/>");
			
			var data = {
				"token_id"   : token_id
			};

			if(wc_xendit_params.can_use_dynamic_3ds === "1"){
				Xendit.card.threeDSRecommendation(data, wc_xendit_form.on3DSRecommendationResponse);
			} else {
				wc_xendit_form.form.submit();
			}
			
			// Prevent form submitting
			return false;
		},

		clearToken: function() {
			$('.xendit_cc_hidden_input').remove();
		},

		on3DSRecommendationResponse: function(err, response) {
			if (err) {
				wc_xendit_form.form.submit();
				return false;
			}

			wc_xendit_form.form.append( "<input type='hidden' class='xendit_cc_hidden_input' name='xendit_should_3ds' value='" + response.should_3ds + "'/>" );
			wc_xendit_form.form.submit();
			
			return;
		},

		/* getting card type from WC card input class name */
		getCardType: function() {
			var class_names = $('#xendit_cc-card-number').attr('class').split(' ');
			var index = class_names.indexOf('identified');

			if (index > -1) {
				return class_names[index - 1];
			}
			
			return 'unknown';
		}
	};

	wc_xendit_form.init();
} );
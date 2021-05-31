<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Xendit_Cardless extends WC_Payment_Gateway {
    const DEFAULT_CARDLESS_TYPE = 'KREDIVO';
    const DEFAULT_MAX_AMOUNT_30DAYS = 3000000;
    const DEFAULT_MAX_AMOUNT_OTHERS = 30000000;
    const DEFAULT_EXTERNAL_ID_VALUE = 'woocommerce-xendit';

    public function __construct()
    {
        $this->id = 'xendit_kredivo';
        $this->has_fields = true;

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->supported_currencies = array(
            'IDR'
        );

        $this->method_type = 'Cardless';
        $this->method_code = 'Kredivo';
        $this->title = !empty($this->get_option('channel_name')) ? $this->get_option('channel_name') : $this->method_code;
        $this->default_description = 'Bayar pesanan dengan Kredivo anda melalui <strong>Xendit</strong>';
        $this->description = !empty($this->get_option('payment_description')) ? nl2br($this->get_option('payment_description')) : $this->default_description;
        $this->verification_token = $this->get_option('verification_token');

        $this->method_title = __('Xendit Kredivo', 'woocommerce-gateway-xendit');
        $this->method_description = sprintf(__('Collect payment from Kredivo on checkout page and get the report realtime on your Xendit Dashboard. <a href="%1$s" target="_blank">Sign In</a> or <a href="%2$s" target="_blank">sign up</a> on Xendit and integrate with <a href="%3$s" target="_blank">your Xendit keys</a>.', 'woocommerce-gateway-xendit'), 'https://dashboard.xendit.co/auth/login', 'https://dashboard.xendit.co/register', 'https://dashboard.xendit.co/settings/developers#api-keys');

        $main_settings = get_option('woocommerce_xendit_gateway_settings');
        $this->developmentmode = $main_settings['developmentmode'];
        $this->secret_key = $this->developmentmode == 'yes' ? $main_settings['secret_key_dev'] : $main_settings['secret_key'];
        $this->publishable_key = $this->developmentmode == 'yes' ? $main_settings['api_key_dev'] : $main_settings['api_key'];
        $this->external_id_format = !empty($main_settings['external_id_format']) ? $main_settings['external_id_format'] : self::DEFAULT_EXTERNAL_ID_VALUE;

        $this->xendit_status = $this->developmentmode == 'yes' ? "[Development]" : "[Production]";
        $this->xendit_callback_url = home_url() . '/?wc-api=wc_xendit_callback&xendit_mode=xendit_cardless_callback';
        $this->success_payment_xendit = $main_settings['success_payment_xendit'];
        $this->for_user_id = $main_settings['on_behalf_of'];
        $this->generic_error_message = 'We encountered an issue while processing the checkout. Please contact us. ';

        $this->xenditClass = new WC_Xendit_PG_API();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_available_payment_gateways', array(&$this, 'check_gateway_status'));
    }

    public function init_form_fields()
    {
        $this->form_fields = require(WC_XENDIT_PG_PLUGIN_PATH . '/libs/forms/wc-xendit-cardless-kredivo-settings.php');
    }

    public function admin_options()
    {
        ?>
        <script>
            jQuery(document).ready(function($) {
                $('.channel-name-format').text('<?=$this->title;?>');
                $('#woocommerce_<?=$this->id;?>_channel_name').change(
                    function() {
                        $('.channel-name-format').text($(this).val());
                    }
                );

                var isSubmitCheckDone = false;

                $("button[name='save']").on('click', function(e) {
                    if (isSubmitCheckDone) {
                        isSubmitCheckDone = false;
                        return;
                    }

                    e.preventDefault();

                    var paymentDescription = $('#woocommerce_<?=$this->id;?>_payment_description').val();
                    if (paymentDescription.length > 250) {
                        return swal({
                            text: 'Text is too long, please reduce the message and ensure that the length of the character is less than 250.',
                            buttons: {
                                cancel: 'Cancel'
                            }
                        });
                    } else {
                        isSubmitCheckDone = true;
                    }

                    $("button[name='save']").trigger('click');
                });
            });
        </script>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    public function payment_fields()
    {
        if ($this->description) {
            $test_description = '';
            if ($this->developmentmode == 'yes') {
                $test_description = '<strong>TEST MODE</strong> - Real payment will not be detected';
            }

            echo '<p>' . $this->description . '</p>
                <p style="color: red; font-size:80%; margin-top:10px;">' . $test_description . '</p>';
        }

        echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

        do_action('woocommerce_credit_card_form_start', $this->id);

        // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
        echo '<div class="form-row form-row-wide">
                <label>Installment <span class="required">*</span></label>
                <select id="xendit_payment_type_kredivo" name="xendit_payment_type_kredivo" autocomplete="off">
                    <option value="30_days">30 days</option>
                    <option value="3_months">3 months</option>
                    <option value="6_months">6 months</option>
                    <option value="12_months">12 months</option>
                </select>
            </div>
            <div class="clear"></div>';

        do_action('woocommerce_credit_card_form_end', $this->id);

        echo '<div class="clear"></div></fieldset>';
    }

    public function validate_fields() {
        $listPaymentType = array("30_days", "3_months", "6_months", "12_months");

        if (empty($_POST['xendit_payment_type_kredivo'])) {
            wc_add_notice('Please select the installment period. Code: 500004', 'error');
            return false;
        } else if (!in_array($_POST['xendit_payment_type_kredivo'], $listPaymentType)) {
            wc_add_notice('Please set the installment period to be 30 days, 3 months, 6 months, or 12 months. Code: 500005', 'error');
            return false;
        }

        return true;
    }

    public function validate_payment($response)
    {
        global $wpdb, $woocommerce;

        $errorMetrics = $this->xenditClass->constructMetricPayload('woocommerce_callback', 'error', $response->cardless_credit_type);
        $successMetrics = $this->xenditClass->constructMetricPayload('woocommerce_callback', 'success', $response->cardless_credit_type);

        try {
            $external_id = $response->external_id;
            
            if(($response->cardless_credit_type === WC_Xendit_Cardless::DEFAULT_CARDLESS_TYPE && empty($this->verification_token)) || ($this->verification_token != $response->callback_authentication_token)) {
                header('HTTP/1.1 401 Verification token not match');
                echo "Verification token not match"; 
                exit;
            }
            
            if ($external_id) {
                $exploded_ext_id = explode("-", $external_id);
                $order_id = end($exploded_ext_id);
    
                if (!is_numeric($order_id)) {
                    $exploded_ext_id = explode("_", $external_id);
                    $order_id = end($exploded_ext_id);
                }
    
                $order = new WC_Order($order_id);
    
                if ($this->developmentmode != 'yes') {
                    $payment_gateway = wc_get_payment_gateway_by_order($order_id);
                    if (false === get_post_status($order_id) || strpos($payment_gateway->id, 'xendit')) {
                        header('HTTP/1.1 400 Invalid Data Received');
                        echo 'Xendit is live and require a valid order id';
                        exit;
                    }
                }
    
                if ($response->transaction_status === 'settlement') {
                    $notes = WC_Xendit_PG_Helper::build_order_notes(
                        $response->transaction_id, 
                        $response->transaction_status, 
                        $response->payment_type, 
                        $order->get_currency(), 
                        $response->amount
                    );
    
                    WC_Xendit_PG_Helper::complete_payment($order, $notes, $this->success_payment_xendit);
    
                    // Empty cart in action
                    $woocommerce->cart->empty_cart();
                }
                else if ($response->transaction_status === "deny" || $response->transaction_status === "cancel" || $response->transaction_status === "expire") {
                    $order->update_status('failed');
    
                    $notes = WC_Xendit_PG_Helper::build_order_notes(
                        $response->transaction_id, 
                        $response->transaction_status, 
                        $response->payment_type, 
                        $order->get_currency(), 
                        $response->amount
                    );  
                    $order->add_order_note("<b>Xendit payment failed.</b><br>" . $notes);

                    $this->xenditClass->trackMetricCount($successMetrics);
                    die($response->cardless_credit_type . ' status is ' . $response->transaction_status);
                }

                $this->xenditClass->trackMetricCount($successMetrics);
                die('Success');
            } else {
                header('HTTP/1.1 400 Invalid Data Received');
                echo 'Xendit external id check not passed';
                exit;
            }
        } catch (Exception $e) {
            $this->xenditClass->trackMetricCount($errorMetrics);

            header('HTTP/1.1 500 Server Error');
            echo $e->getMessage();
            exit;
        }
    }

    public function get_icon()
    {
        $style = version_compare(WC()->version, '2.6', '>=') ? 'style="margin-left: 0.3em; max-height: 32px;"' : '';
        $file_name = strtolower($this->method_code) . '.png';
        $icon = '<img src="' . plugins_url('assets/images/' . $file_name, WC_XENDIT_PG_MAIN_FILE) . '" alt="Xendit" ' . $style . ' />';

        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }
    
    /* 
    * Execute when click place order
    */
    public function process_payment($order_id) {
        global $woocommerce;

        try {
            $order = new WC_Order($order_id);

            $external_id = $this->external_id_format . '-' . $order_id;
            $amount = $order->get_total();
            $payment_type = wc_clean($_POST['xendit_payment_type_kredivo']);
            
            /* Handle credit limit */
            if ($payment_type == "30_days" && $amount > WC_Xendit_Cardless::DEFAULT_MAX_AMOUNT_30DAYS) {
                WC_Xendit_PG_Helper::cancel_order($order, 'Cancelled because amount exceeds credit limit.');
                
                $err_msg = sprintf(__(
                    'The maximum amount for 30 days installment is %1$s %2$s. Please select a longer installment scheme. Code: 500006', 
                    'woocommerce-gateway-xendit'
                ), $order->get_currency(), wc_price(WC_Xendit_Cardless::DEFAULT_MAX_AMOUNT_30DAYS));

                wc_add_notice($this->get_localized_error_message('INVALID_AMOUNT_ERROR', $err_msg), 'error');
                return;
            }
            else if ($amount > WC_Xendit_Cardless::DEFAULT_MAX_AMOUNT_OTHERS) {
                WC_Xendit_PG_Helper::cancel_order($order, 'Cancelled because amount exceeds credit limit.');

                $err_msg = sprintf(__(
                    'The maximum amount for using this payment is %1$s %2$s. Please remove one or more item(s) from your cart. Code: 100002', 
                    'woocommerce-gateway-xendit'
                ), $order->get_currency(), wc_price(WC_Xendit_Cardless::DEFAULT_MAX_AMOUNT_OTHERS));

                wc_add_notice($this->get_localized_error_message('INVALID_AMOUNT_ERROR', $err_msg), 'error');
                return;
            }

            $additional_data = WC_Xendit_PG_Helper::generate_items_and_customer($order, $amount, WC_Xendit_Cardless::DEFAULT_CARDLESS_TYPE);
        
            $billing_address_format = trim($order->get_billing_address_1()."\n".$order->get_billing_address_2());
            $shipping_address_format = trim($order->get_shipping_address_1()."\n".$order->get_shipping_address_2());
            $shipping_city = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
            $shipping_postcode = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode();
            $shipping_country_code = $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country();

            $shipping_address = array();
            $shipping_address['first_name']     = $order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name();
            $shipping_address['last_name']      = $order->get_shipping_last_name() ? $order->get_shipping_last_name() : $order->get_billing_last_name();
            $shipping_address['address']        = $shipping_address_format ? $shipping_address_format : ($billing_address_format ? $billing_address_format : 'N/A');
            $shipping_address['city']           = $shipping_city ? $shipping_city : 'N/A';
            $shipping_address['postal_code']    = $shipping_postcode ? $shipping_postcode : 'N/A';
            $shipping_address['phone']          = $order->get_billing_phone() ? $order->get_billing_phone() : 'N/A';
            $shipping_address['country_code']   = $shipping_country_code ? $shipping_country_code : 'ID';

            /*
             * Array with parameters for API interaction
             */
            $args = array(
                'cardless_credit_type' => WC_Xendit_Cardless::DEFAULT_CARDLESS_TYPE,
                'external_id' => $external_id,
                'amount' => floatval($amount),
                'payment_type' => $payment_type,
                'items' => $additional_data['items'],
                'customer_details' => $additional_data['customer'],
                'shipping_address' => json_encode($shipping_address),
                'redirect_url' => $this->get_return_url($order), //thank you page
                'callback_url' => $this->xendit_callback_url
            );
            
            $header = array(
                'x-plugin-method' => strtoupper( $this->method_code ),
                'x-plugin-store-name' => get_option('blogname')
            );

            $response = $this->xenditClass->createCardlessPayment($args, $header);

            if (!empty($response['error_code'])) {
                update_post_meta($order_id, 'Xendit_error', esc_attr($response['error_code']));
                
                $response['message'] = !empty($response['code']) ? $response['message'] . ' Code: ' . $response['code'] : $response['message'];
                wc_add_notice($this->get_localized_error_message($response['error_code'], $response['message']), 'error');
                
                // log error metrics
                $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code, $response['error_code']);
                $this->xenditClass->trackMetricCount($metrics);
                return;
            }
            
            if (isset($response['redirect_url'])) {
                // Set payment pending
                $order->update_status('pending', __('Awaiting Xendit payment', 'xendit'));
                update_post_meta($order_id, 'Xendit_order_id', esc_attr($response['order_id']));
                update_post_meta($order_id, 'Xendit_cardless_url', esc_attr($response['redirect_url']));
                
                // send customer object data
                $reference_id = (!empty($order->get_billing_email())) ? $order->get_billing_email() : $order->get_billing_phone();
                $customer = WC_Xendit_PG_Helper::generate_customer($order);
                WC_Xendit_PG_Helper::process_customer_object($reference_id, $customer);

                // log success metrics
                $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'success', $this->method_code);
                $this->xenditClass->trackMetricCount($metrics);

                // Redirect to Kredivo page
                return array(
                    'result' => 'success',
                    'redirect' => $response['redirect_url']
                );
            }
            else { //we're still in checkout page
                // log error metrics
                $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code, 'GENERATE_CHECKOUT_URL_ERROR');
                $this->xenditClass->trackMetricCount($metrics);
                
                wc_add_notice($this->get_localized_error_message('GENERATE_CHECKOUT_URL_ERROR'), 'error');
                return;
            }
        } catch (Exception $e) {
            wc_add_notice($this->generic_error_message . 'Code: 100007', 'error');

            // log error metrics
            $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code);
            $this->xenditClass->trackMetricCount($metrics);
            return;
        }
    }

    public function get_localized_error_message($error_code, $message = "") {
        switch ($error_code) {
            case 'GENERATE_CHECKOUT_URL_ERROR':
                return $this->generic_error_message . 'Code: 500001';
            default:
                return $message ? $message : $error_code;
        }
    }

    public function check_gateway_status( $gateways ) {
        global $wpdb, $woocommerce;

        if (is_null($woocommerce->cart)) {
            return $gateways;
        }

        if ( empty($this->secret_key) ) {
            unset($gateways[$this->id]);
            return $gateways;
        }

        if ( empty($this->verification_token) ) {
            unset($gateways[$this->id]);
            return $gateways;
        }

        $amount = $woocommerce->cart->get_cart_contents_total();
        if ($amount > WC_Xendit_Cardless::DEFAULT_MAX_AMOUNT_OTHERS) {
            unset($gateways[$this->id]);
            return $gateways;
        }

        return $gateways;
    }
}
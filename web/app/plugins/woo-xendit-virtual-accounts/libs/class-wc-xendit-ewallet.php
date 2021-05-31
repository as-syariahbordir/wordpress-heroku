<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Xendit_EWallet extends WC_Payment_Gateway {
    const DEFAULT_EXTERNAL_ID_VALUE = 'woocommerce-xendit';
    const DEFAULT_MINIMUM_AMOUNT = 10000;
    const DEFAULT_MAXIMUM_AMOUNT = 10000000;

    public function __construct() {
        $this->supported_currencies = array(
            'IDR'
        );
        $this->enabled = $this->get_option( 'enabled' );

        $main_settings = get_option('woocommerce_xendit_gateway_settings');
        $this->developmentmode = $main_settings['developmentmode'];
        $this->secret_key = $this->developmentmode == 'yes' ? $main_settings['secret_key_dev'] : $main_settings['secret_key'];
        $this->publishable_key = $this->developmentmode == 'yes' ? $main_settings['api_key_dev'] : $main_settings['api_key'];
        $this->external_id_format = !empty($main_settings['external_id_format']) ? $main_settings['external_id_format'] : self::DEFAULT_EXTERNAL_ID_VALUE;

        $this->xendit_status = $this->developmentmode == 'yes' ? "[Development]" : "[Production]";
        $this->xendit_callback_url = home_url() . '/?wc-api=wc_xendit_callback&xendit_mode=xendit_ewallet_callback';
        $this->success_payment_xendit = $main_settings['success_payment_xendit'];
        $this->for_user_id = $main_settings['on_behalf_of'];
        $this->generic_error_message = 'We encountered an issue while processing the checkout. Please contact us. ';

        $this->xenditClass = new WC_Xendit_PG_API();

        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_filter('woocommerce_available_payment_gateways', array(&$this, 'check_gateway_status'));
    }

    public function payment_scripts() {
        if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) && ! is_add_payment_method_page() ) {
            return;
        }

        if ( 'no' === $this->enabled ) {
            return;
        }

        if ( empty( $this->secret_key ) ) {
            return;
        }

        wp_enqueue_script( 'woocommerce_xendit_ewallet', plugins_url( 'assets/js/xendit-ovo.js', WC_XENDIT_PG_MAIN_FILE ), array( 'jquery' ), WC_XENDIT_PG_VERSION, true );
    }

    public function payment_fields() {
        if ( $this->description ) {
            $test_description = '';
            if ( $this->developmentmode == 'yes' ) {
                $test_description = '<strong>TEST MODE</strong> - Real payment will not be detected';
            }

            echo '<p>' . $this->description . '</p>
                <p style="color: red; font-size:80%; margin-top:10px;">' . $test_description . '</p>';
        }
    }

    public function process_payment($order_id) {
        global $woocommerce;
        
        try {
            $order = wc_get_order($order_id);
            $total_amount = (int) $order->get_total();

            if ($total_amount < WC_Xendit_EWallet::DEFAULT_MINIMUM_AMOUNT && $this->developmentmode != 'yes') {
                WC_Xendit_PG_Helper::cancel_order($order, 'Cancelled because amount is below minimum amount');

                $err_msg = sprintf(__(
                    'The minimum amount for using this payment is %1$s %2$s. Please put more item(s) to reach the minimum amount. Code: 100001', 
                    'woocommerce-gateway-xendit'
                ), $order->get_currency(), wc_price(WC_Xendit_EWallet::DEFAULT_MINIMUM_AMOUNT));

                wc_add_notice($this->get_localized_error_message('INVALID_AMOUNT_ERROR', $err_msg), 'error');
                return;
            }

            if ($total_amount > WC_Xendit_EWallet::DEFAULT_MAXIMUM_AMOUNT) {
                WC_Xendit_PG_Helper::cancel_order($order, 'Cancelled because amount is above maximum amount');

                $err_msg = sprintf(__(
                    'The maximum amount for using this payment is %1$s %2$s. Please remove one or more item(s) from your cart. Code: 100002', 
                    'woocommerce-gateway-xendit'
                ), $order->get_currency(), wc_price(WC_Xendit_EWallet::DEFAULT_MAXIMUM_AMOUNT));
                
                wc_add_notice($this->get_localized_error_message('INVALID_AMOUNT_ERROR', $err_msg), 'error');
                return;
            }

            $additional_data = WC_Xendit_PG_Helper::generate_items_and_customer($order, $total_amount);

            /*
             * Array with parameters for API interaction
             */
            $external_id = $this->external_id_format . '-' . $order_id;
            $args = array(
                'external_id' => $external_id,
                'amount' => $total_amount,
                'ewallet_type' => $this->method_code,
                'items' => !empty($additional_data['items']) ? $additional_data['items'] : '',
                'customer' => !empty($additional_data['customer']) ? $additional_data['customer'] : '',
                'platform_callback_url' => $this->xendit_callback_url
            );

            switch ($this->method_code) {
                case 'OVO':
                    $args['phone'] = wc_clean( $_POST[$this->id . '_phone'] );
                break;
                case 'DANA':
                    $args['redirect_url'] = get_site_url().'?xendit_ewallet_redirect=true&order_id='.$order_id.'&ewallet_type=DANA';
                break;
                case 'LINKAJA':
                    $args['phone'] = wc_clean( $_POST[$this->id . '_phone'] );
                    $args['redirect_url'] = get_site_url().'?xendit_ewallet_redirect=true&order_id='.$order_id.'&ewallet_type=LINKAJA';
                break;
            }

            $header = array(
                'x-plugin-method' => strtoupper($this->method_code),
                'x-plugin-store-name' => get_option('blogname'),
                'x-api-version' => '2020-02-01'
            );

            $response = $this->xenditClass->createEwalletPayment($args, $header);

            if (!empty($response['error_code'])) {
                update_post_meta($order_id, 'Xendit_error', esc_attr($response['error_code']));

                $response['message'] = !empty($response['code']) ? $response['message'] . ' Code: ' . $response['code'] : $response['message'];
                wc_add_notice($this->get_localized_error_message($response['error_code'], $response['message']), 'error');
                
                // log error metrics
                $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code, $response['error_code']);
                $this->xenditClass->trackMetricCount($metrics);
                return;
            }
            
            update_post_meta($order_id, '_xendit_external_id', $response['external_id']);

            $reference_id = (!empty($order->get_billing_email())) ? $order->get_billing_email() : $order->get_billing_phone();
            $customer = WC_Xendit_PG_Helper::generate_customer($order);

            if (isset($response['checkout_url'])) { // DANA / LINKAJA redirection
                // log success metrics
                $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'success', $this->method_code);
                $this->xenditClass->trackMetricCount($metrics);

                WC_Xendit_PG_Helper::process_customer_object($reference_id, $customer);

                return array(
                    'result' => 'success',
                    'redirect' => $response['checkout_url']
                );
            }

            $isSuccessful = false;
            $loopCondition = true;
            $startTime = time();
            $errorCode = '';

            while ($loopCondition && (time() - $startTime < 70)) {
                $getEwallet = $this->xenditClass->getEwallet($this->method_code, $external_id);

                if (!empty($getEwallet['error_code'])) {
                    $errorCode = $getEwallet['error_code'];
                    $getEwallet['message'] = !empty($getEwallet['code']) ? $getEwallet['message'] . ' Code: ' . $getEwallet['code'] : $getEwallet['message'];
                    wc_add_notice($this->get_localized_error_message($errorCode, $getEwallet['message']), 'error');
                    break;
                }

                if ($getEwallet['status'] == 'COMPLETED') {
                    $loopCondition = false;
                    $isSuccessful = true;
                }
                
                // for ovo
                if ($getEwallet['status'] == 'FAILED') {
                    sleep(10);
                    
                    $errorCode = get_transient('xendit_ewallet_failure_code_'.$order_id);
                    if (!$errorCode) {
                        // Make this as default failure reason if no failure code is found
                        $errorCode = 'USER_DID_NOT_AUTHORIZE_THE_PAYMENT';
                    }
                    
                    wc_add_notice($this->get_localized_error_message($errorCode), 'error');
                    break;
                }

                // for linkaja
                if ($getEwallet['status'] == 'EXPIRED') {
                    $errorCode = 'PAYMENT_EXPIRED';
                    wc_add_notice($this->get_localized_error_message($errorCode, $this->generic_error_message . ' Code: 400018'), 'error');
                    break;
                }

                sleep(1);
            }

            if (!$isSuccessful) {
                // log error metrics
                $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code, $errorCode);
                $this->xenditClass->trackMetricCount($metrics);
                return;
            }

            // log success metrics
            $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'success', $this->method_code);
            $this->xenditClass->trackMetricCount($metrics);

            WC_Xendit_PG_Helper::process_customer_object($reference_id, $customer);

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } catch (Exception $e) {
            wc_add_notice($this->generic_error_message . 'Code: 100007', 'error');

            // log error metrics
            $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code);
            $this->xenditClass->trackMetricCount($metrics);
            return;
        }
    }

    public function validate_payment($response) {
        global $wpdb, $woocommerce;

        $errorMetrics = $this->xenditClass->constructMetricPayload('woocommerce_callback', 'error', $response->ewallet_type);
        $successMetrics = $this->xenditClass->constructMetricPayload('woocommerce_callback', 'success', $response->ewallet_type);
        
        try {
            $external_id = $response->external_id;
            $xendit_status = $this->xendit_status;

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
                        echo 'Xendit is live and required valid order id';
                        exit;
                    }
                }
    
                if ($response->failure_code) {                    
                    set_transient('xendit_ewallet_failure_code_'.$order_id, $response->failure_code, 60);
                }
    
                //check if order in WC is still pending after payment
                $ewallet = $this->xenditClass->getEwallet($response->ewallet_type, $external_id);

                if (!empty($ewallet['error_code'])) {
                    $ewallet['message'] = !empty($ewallet['code']) ? $ewallet['message'] . ' Code: ' . $ewallet['code'] : $ewallet['message'];
                    die($this->get_localized_error_message($ewallet['error_code'], $ewallet['message']));
                }

                if ($ewallet['status'] === 'COMPLETED') {
                    $notes = WC_Xendit_PG_Helper::build_order_notes(
                        $ewallet['external_id'], 
                        $ewallet['status'], 
                        $response->ewallet_type, 
                        $order->get_currency(), 
                        $ewallet['amount']
                    );
    
                    WC_Xendit_PG_Helper::complete_payment($order, $notes, $this->success_payment_xendit);
    
                    // Empty cart in action
                    $woocommerce->cart->empty_cart();
    
                    $this->xenditClass->trackMetricCount($successMetrics);

                    die('Success');
                } else {
                    $current_status = $order->get_status();
                    if ($current_status == 'pending') {
                        $order->update_status('failed');
    
                        $notes = WC_Xendit_PG_Helper::build_order_notes(
                            $ewallet['external_id'], 
                            $ewallet['status'], 
                            $response->ewallet_type, 
                            $order->get_currency(), 
                            $ewallet['amount']
                        );
                        $order->add_order_note("<b>Xendit payment failed.</b><br>" . $notes);

                        $this->xenditClass->trackMetricCount($successMetrics);

                        die($response->ewallet_type . ' status is ' . $ewallet['status']);
                    }

                    $this->xenditClass->trackMetricCount($successMetrics);

                    die($response->ewallet_type . ' status is ' . $ewallet['status'] . '. Current order status is ' . $current_status);
                }
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

    public function redirect_ewallet($order_id='', $ewallet_type='DANA') {
        global $wpdb, $woocommerce;

        try {
            $order = new WC_Order($order_id);
            $orderData = $order->get_data();
            $external_id = get_post_meta($order_id, '_xendit_external_id', true);
            $ewallet = $this->xenditClass->getEwallet($ewallet_type, $external_id);
            
            $url = wc_get_checkout_url();
            if (!empty($ewallet['status']) && $ewallet['status'] === 'COMPLETED') {
                $url = WC_Payment_Gateway::get_return_url($order);
            } else {
                wc_add_notice($this->get_localized_error_message('PAYMENT_FAILED', $this->generic_error_message . 'Code: 100007'), 'error');
            }
    
            wp_safe_redirect($url);
            exit;
        } catch (Exception $e) {
            // log error metrics
            $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $ewallet_type);
            $this->xenditClass->trackMetricCount($metrics);

            wc_add_notice($e->getMessage(), 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }
    }
    
    public function is_valid_for_use() {
        return in_array(get_woocommerce_currency(), apply_filters(
            'woocommerce_' . $this->id . '_supported_currencies',
            $this->supported_currencies
        ));
    }

    public function check_gateway_status( $gateways ) {
        global $wpdb, $woocommerce;

        if (is_null($woocommerce->cart)) {
            return $gateways;
        }

        if (!$this->secret_key) {
            unset($gateways[$this->id]);
            return $gateways;
        }

        $amount = $woocommerce->cart->get_cart_contents_total();
        if ($amount > WC_Xendit_EWallet::DEFAULT_MAXIMUM_AMOUNT) {
            unset($gateways[$this->id]);
            return $gateways;
        }

        if (!$this->is_valid_for_use()) {
            unset($gateways[$this->id]);

            return $gateways;
        }

        return $gateways;
    }

    public function get_localized_error_message($error_code, $message='') {
        switch ($error_code) {
            case 'INVALID_AMOUNT_ERROR':
                return $message;
            case 'PHONE_NUMBER_NOT_REGISTERED':
                return 'Your number is not registered in '.$this->method_code.', please register first or contact '.$this->method_code.' Customer Service. Code: 400004';
            case 'EWALLET_APP_UNREACHABLE':
                return 'Please check your '.$this->method_code.' app on your phone and try again. Code: 400006';
            default:
                return $message ? $message : $error_code;
        }
    }

    public function get_icon() {
        $style = version_compare( WC()->version, '2.6', '>=' ) ? 'style="margin-left: 0.3em; max-height: 23px;"' : '';
        $file_name = strtolower( $this->method_code ) . '.png';
        $icon = '<img src="' . plugins_url('assets/images/' . $file_name , WC_XENDIT_PG_MAIN_FILE) . '" alt="Xendit" ' . $style . ' />';

        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
    }
}
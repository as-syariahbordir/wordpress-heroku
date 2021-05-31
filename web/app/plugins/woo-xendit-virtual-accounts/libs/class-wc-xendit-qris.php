<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Xendit_QRIS extends WC_Payment_Gateway {
    const DEFAULT_MAX_AMOUNT = 5000000;
    const DEFAULT_MIN_AMOUNT = 1500;
    const DEFAULT_EXTERNAL_ID_VALUE = 'woocommerce-xendit';

    public function __construct()
    {
        $this->id = 'xendit_qris';

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->supported_currencies = array(
            'IDR'
        );

        $this->method_code = 'QRIS';
        $this->title = !empty($this->get_option('channel_name')) ? $this->get_option('channel_name') : $this->method_code;
        $this->default_description = 'Bayar pesanan dengan QRIS anda melalui <strong>Xendit</strong>';
        $this->description = !empty($this->get_option('payment_description')) ? nl2br($this->get_option('payment_description')) : $this->default_description;

        $this->method_title = __('Xendit QRIS', 'woocommerce-gateway-xendit');
        $this->method_description = sprintf(__('Collect payment from QRIS on checkout page and get the report realtime on your Xendit Dashboard. <a href="%1$s" target="_blank">Sign In</a> or <a href="%2$s" target="_blank">sign up</a> on Xendit and integrate with <a href="%3$s" target="_blank">your Xendit keys</a>.', 'woocommerce-gateway-xendit'), 'https://dashboard.xendit.co/auth/login', 'https://dashboard.xendit.co/register', 'https://dashboard.xendit.co/settings/developers#api-keys');

        $main_settings = get_option('woocommerce_xendit_gateway_settings');
        $this->developmentmode = $main_settings['developmentmode'];
        $this->secret_key = $this->developmentmode == 'yes' ? $main_settings['secret_key_dev'] : $main_settings['secret_key'];
        $this->publishable_key = $this->developmentmode == 'yes' ? $main_settings['api_key_dev'] : $main_settings['api_key'];
        $this->external_id_format = !empty($main_settings['external_id_format']) ? $main_settings['external_id_format'] : self::DEFAULT_EXTERNAL_ID_VALUE;

        $this->xendit_status = $this->developmentmode == 'yes' ? "[Development]" : "[Production]";
        $this->xendit_callback_url = home_url() . '/?wc-api=wc_xendit_callback&xendit_mode=xendit_qris_callback';
        $this->success_payment_xendit = $main_settings['success_payment_xendit'];
        $this->for_user_id = $main_settings['on_behalf_of'];

        $this->xenditClass = new WC_Xendit_PG_API();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_available_payment_gateways', array(&$this, 'check_gateway_status'));
    }

    public function init_form_fields()
    {
        $this->form_fields = require(WC_XENDIT_PG_PLUGIN_PATH . '/libs/forms/wc-xendit-qris-settings.php');
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
        global $woocommerce;
        if (!empty($this->description)) {
            if ($this->id !== 'xendit_gateway') {
                $test_description = '';
                if ($this->developmentmode == 'yes') {
                    $test_description = sprintf(__('<strong>TEST MODE</strong> - Real payment will not be detected', 'woocommerce-gateway-xendit'));
                }
                echo '<p style="margin-bottom:0;">' . $this->description . '</p>
                <p style="color: red; font-size:80%; margin-top:0;">' . $test_description . '</p>';

                return;
            }

            if ($this->developmentmode == 'yes') {
                $test_description = sprintf(__('<strong>TEST MODE.</strong> The QRIS shown on next page are for testing only.', 'woocommerce-gateway-xendit'));
                $this->description = trim($test_description . '<br />' . $this->description);
            }
            echo wpautop(wptexturize($this->description));
        }
    }

    public function validate_payment( $response ) {
        global $wpdb, $woocommerce;

        $errorMetrics = $this->xenditClass->constructMetricPayload('woocommerce_callback', 'error', $this->method_code);
        $successMetrics = $this->xenditClass->constructMetricPayload('woocommerce_callback', 'success', $this->method_code);

        try {
            $get_qris = $this->xenditClass->getQRIS($response->qr_code->external_id);

            if (!empty($get_qris['error_code'])) {
                $get_qris['message'] = !empty($get_qris['code']) ? $get_qris['message'] . ' Code: ' . $get_qris['code'] : $get_qris['message'];
                throw new Exception($this->get_localized_error_message($get_qris['error_code'], $get_qris['message']));
            }

            $external_id = $get_qris['external_id'];
            $xendit_status = $this->xendit_status;
            
            $exploded_ext_id = explode("-", $external_id);
            $order_id = end($exploded_ext_id);

            $order = new WC_Order($order_id);

            if ($this->developmentmode != 'yes') {
                $payment_gateway = wc_get_payment_gateway_by_order($order_id);
                if (false === get_post_status($order_id) || strpos($payment_gateway->id, 'xendit')) {
                    header('HTTP/1.1 400 Invalid Data Received');
                    echo 'Xendit is live and required valid order id';
                    exit;
                }
            }

            if ('INACTIVE' == $get_qris['status']) {
                $notes = WC_Xendit_PG_Helper::build_order_notes(
                    $get_qris['id'], 
                    $get_qris['status'], 
                    'QRIS', 
                    $order->get_currency(), 
                    $get_qris['amount']
                );

                WC_Xendit_PG_Helper::complete_payment($order, $notes, $this->success_payment_xendit);

                $this->xenditClass->trackMetricCount($successMetrics);

                die('Success');
            }

            die('Nothing changing');
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
        global $wpdb, $woocommerce;

        $errorMetrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code);
        $successMetrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'success', $this->method_code);

        try {
            $order = new WC_Order($order_id);
            $amount = $order->get_total();

            if ($amount < WC_Xendit_QRIS::DEFAULT_MIN_AMOUNT) {
                WC_Xendit_PG_Helper::cancel_order($order, 'Cancelled because amount is below minimum amount');

                $err_msg = sprintf(__(
                    'The minimum amount for using this payment is %1$s %2$s. Please put more item(s) to reach the minimum amount. Code: 100001', 
                    'woocommerce-gateway-xendit'
                ), $order->get_currency(), wc_price(WC_Xendit_QRIS::DEFAULT_MIN_AMOUNT));

                wc_add_notice($message, 'error');
                return;
            }

            if ($amount > WC_Xendit_QRIS::DEFAULT_MAX_AMOUNT) {
                WC_Xendit_PG_Helper::cancel_order($order, 'Cancelled because amount is above maximum amount');
                
                $err_msg = sprintf(__(
                    'The minimum amount for using this payment is %1$s %2$s. Please put more item(s) to reach the minimum amount. Code: 100001', 
                    'woocommerce-gateway-xendit'
                ), $order->get_currency(), wc_price(WC_Xendit_QRIS::DEFAULT_MAX_AMOUNT));

                wc_add_notice($message, 'error');
                return;
            }

            $qris_id = get_post_meta($order_id, 'qris_id', true);

            if ($qris_id) {
                return array(
                    'result' => 'success',
                    'redirect' => 'https://tpi-ui.xendit.co/qris/'.$qris_id,
                );
            }

            $external_id = $this->external_id_format . '-' . $order_id;
            $args = array(
                'external_id' => $external_id,
                'type' => 'DYNAMIC',
                'platform_callback_url' => $this->xendit_callback_url,
                'amount' => $amount,
                'success_redirect_url' => $this->get_return_url($order),
                'failure_redirect_url' => wc_get_checkout_url()
            );

            $header = array(
                'x-plugin-store-name' => get_option('blogname')
            );

            $response = $this->xenditClass->createQRIS($args, $header);

            if (!empty($response['error_code'])) {
                $response['message'] = !empty($response['code']) ? $response['message'] . ' Code: ' . $response['code'] : $response['message'];
                throw new Exception($this->get_localized_error_message($response['error_code'], $response['message']));
            }

            $qris_id = $response['id'];
            update_post_meta($order_id, 'qris_id', $qris_id);

            $this->xenditClass->trackMetricCount($successMetrics);

            //customer object generation
            $reference_id = $order->get_billing_email() ? $order->get_billing_email() : $order->get_billing_phone();
            $customer_data = WC_Xendit_PG_Helper::generate_customer($order);
            WC_Xendit_PG_Helper::process_customer_object($reference_id ,$customer_data);

            return array(
                'result' => 'success',
                'redirect' => 'https://tpi-ui.xendit.co/qris/'.$qris_id,
            );
        } catch (Exception $e) {
            $message = $e->getMessage();

            WC_Xendit_PG_Helper::cancel_order($order, 'Cancelled because: ' . $message);
            $this->xenditClass->trackMetricCount($errorMetrics);

            wc_add_notice($message, 'error');
            return;
        }
    }

    public function check_gateway_status($gateways) {
        global $wpdb, $woocommerce;

        if (is_null($woocommerce->cart)) {
            return $gateways;
        }

        if (empty($this->secret_key)) {
            unset($gateways[$this->id]);
            return $gateways;
        }

        $amount = $woocommerce->cart->get_cart_contents_total();
        if ($amount > WC_Xendit_QRIS::DEFAULT_MAX_AMOUNT) {
            unset($gateways[$this->id]);
            return $gateways;
        }

        if ($amount < WC_Xendit_QRIS::DEFAULT_MIN_AMOUNT) {
            unset($gateways[$this->id]);
            return $gateways;
        }

        return $gateways;
    }

    public function get_localized_error_message($error_code, $message) {
        return $message ? $message : $error_code;
    }
}
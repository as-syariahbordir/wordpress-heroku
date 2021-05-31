<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * WC_Xendit_CC class.
 *
 * @extends WC_Payment_Gateway_CC
 */
class WC_Xendit_CC extends WC_Payment_Gateway_CC
{    
    /**
     * External ID
     * @var string
	 */
    const DEFAULT_EXTERNAL_ID_VALUE = 'woocommerce-xendit';

    /**
     * Minimum amount
     * @var number
     */
    private $DEFAULT_MINIMUM_AMOUNT = 0;

    /**
     * Maximum amount
     * @var number
     */
    private $DEFAULT_MAXIMUM_AMOUNT = 0;

    /**
     * Should we capture Credit cards
     *
     * @var bool
     */
    public $capture;

    /**
     * Alternate credit card statement name
     *
     * @var bool
     */
    public $statement_descriptor;

    /**
     * Checkout enabled
     *
     * @var bool
     */
    public $xendit_checkout;

    /**
     * Checkout Locale
     *
     * @var string
     */
    public $xendit_checkout_locale;

    /**
     * Credit card image
     *
     * @var string
     */
    public $xendit_checkout_image;

    /**
     * Should we store the users credit cards?
     *
     * @var bool
     */
    public $saved_cards;

    /**
     * API access secret key
     *
     * @var string
     */
    public $secret_key;

    /**
     * Api access publishable key
     *
     * @var string
     */
    public $publishable_key;

    /**
     * Is test mode active?
     *
     * @var bool
     */
    public $testmode;
    
    /* 
    * environment
    */
    public $environment='';
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id                   = 'xendit_cc';
        $this->method_code          = 'CREDIT_CARD';
        $this->method_title         = __('Xendit', 'woocommerce-gateway-xendit');
        $this->method_description   = sprintf(__('Collect payment from Credit Cards on checkout page and get the report realtime on your Xendit Dashboard. <a href="%1$s" target="_blank">Sign In</a> or <a href="%2$s" target="_blank">sign up</a> on Xendit and integrate with <a href="%3$s" target="_blank">your Xendit keys</a>.', 'woocommerce-gateway-xendit'), 'https://dashboard.xendit.co/auth/login', 'https://dashboard.xendit.co/register', 'https://dashboard.xendit.co/settings/developers#api-keys');
        $this->has_fields           = true;
        $this->view_transaction_url = 'https://dashboard.xendit.co/dashboard/credit_cards';
        $this->supports             = array(
            'subscriptions',
            'products',
            'refunds',
            'subscription_cancellation',
            'subscription_reactivation',
            'subscription_suspension',
            'subscription_amount_changes',
            'subscription_payment_method_change', // Subs 1.n compatibility.
            'subscription_payment_method_change_admin',
            'subscription_date_changes',
            'multiple_subscriptions',
            'tokenization',
			'add_payment_method',
        );
        $this->supported_currencies = array(
            'IDR', 'PHP', 'USD'
        );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Get setting values.
        $this->default_title           = 'Credit Card (Xendit)';
        $this->title                   = !empty($this->get_option('channel_name')) ? $this->get_option('channel_name') : $this->default_title;
        $this->default_description     = 'Pay with your credit card via Xendit.';
        $this->description             = !empty($this->get_option('payment_description')) ? nl2br($this->get_option('payment_description')) : $this->default_description;
        
        $main_settings = get_option('woocommerce_xendit_gateway_settings');

        $this->developmentmode         = $main_settings['developmentmode'];
        $this->testmode                = 'yes' === $this->developmentmode;
        $this->environment             = $this->testmode ? 'development' : 'production';
        $this->capture                 = true;
        $this->statement_descriptor    = $this->get_option('statement_descriptor');
        $this->xendit_checkout         = 'yes' === $this->get_option('xendit_checkout');
        $this->xendit_checkout_locale  = $this->get_option('xendit_checkout_locale');
        $this->xendit_checkout_image   = '';
        $this->saved_cards             = true;
        $this->secret_key              = $this->testmode ? $main_settings['secret_key_dev'] : $main_settings['secret_key'];
        $this->publishable_key         = $this->testmode ? $main_settings['api_key_dev'] : $main_settings['api_key'];
        $this->external_id_format      = !empty($main_settings['external_id_format']) ? $main_settings['external_id_format'] : self::DEFAULT_EXTERNAL_ID_VALUE;
        $this->xendit_status           = $this->developmentmode == 'yes' ? "[Development]" : "[Production]";
        $this->xendit_callback_url     = home_url() . '/?wc-api=wc_xendit_callback&xendit_mode=xendit_cc_callback';
        $this->success_payment_xendit  = $main_settings['success_payment_xendit'];
        $this->for_user_id             = $main_settings['on_behalf_of'];

        if ($this->xendit_checkout) {
            $this->order_button_text = __('Continue to payment', 'woocommerce-gateway-xendit');
        }

        if ($this->testmode) {
            $this->description .= '<br/>' . sprintf(__('TEST MODE. Try card "4000000000000002" with any CVN and future expiration date, or see <a href="%s">Xendit Docs</a> for more test cases.', 'woocommerce-gateway-xendit'), 'https://dashboard.xendit.co/docs/');
            $this->description  = trim($this->description);
        }

        $this->xenditClass = new WC_Xendit_PG_API();

        $this->set_current_currency(get_woocommerce_currency());
        $this->generic_error_message = 'We encountered an issue while processing the checkout. Please contact us. ';

        // Hooks.
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_checkout_billing', array($this, 'show_checkout_error'), 10, 0);
        add_filter('woocommerce_available_payment_gateways', array(&$this, 'check_gateway_status'));
        add_filter('woocommerce_thankyou', array(&$this, 'update_order_status'));
    }

    public function set_current_currency($currency = 'IDR')
    {
        switch ($currency) {
            case 'IDR':
                $this->DEFAULT_MINIMUM_AMOUNT = 10000;
                $this->DEFAULT_MAXIMUM_AMOUNT = 200000000;
                break;

            case 'PHP':
                $this->DEFAULT_MINIMUM_AMOUNT = 1;
                $this->DEFAULT_MAXIMUM_AMOUNT = 200000000;
                break;

            case 'USD':
                $this->DEFAULT_MINIMUM_AMOUNT = 1;
                $this->DEFAULT_MAXIMUM_AMOUNT = 200000000;
                break;

            default:
                $this->DEFAULT_MINIMUM_AMOUNT = 0;
                $this->DEFAULT_MAXIMUM_AMOUNT = 0;
                break;
        }
    }

    /**
     * Get_icon function. This is called by WC_Payment_Gateway_CC when displaying payment option
     * on checkout page.
     *
     * @access public
     * @return string
     */
    public function get_icon()
    {
        $style = version_compare(WC()->version, '2.6', '>=') ? 'style="margin-left: 0.3em; max-width: 80px;"' : '';

        if (get_woocommerce_currency() == 'PHP') {
            $img = 'assets/images/cc-php.png';
        } else {
            $img = 'assets/images/cc.png';
        }
        $icon = '<img src="' . plugins_url($img, WC_XENDIT_PG_MAIN_FILE) . '" alt="Xendit" ' . $style . ' />';
        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

    /**
     * Render admin settings HTML
     * 
     * Host some PHP reliant JS to make the form dynamic
     */
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

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = require( WC_XENDIT_PG_PLUGIN_PATH . '/libs/forms/wc-xendit-cc-settings.php' );
    }

    /**
     * Payment form on checkout page. This is called by WC_Payment_Gateway_CC when displaying
     * payment form on checkout page.
     */
    public function payment_fields()
    {
        global $wp;
        $user                 = wp_get_current_user();
        $display_tokenization = $this->supports('tokenization') && is_checkout() && $this->saved_cards;
        $total                = WC()->cart->total;

        // If paying from order, we need to get total from order not cart.
		if (isset($_GET['pay_for_order']) && !empty($_GET['key'])) {
			$order      = wc_get_order(wc_clean($wp->query_vars['order-pay']));
			$total      = $order->get_total();
			$user_email = $order->get_billing_email();
		} else {
			if ($user->ID) {
				$user_email = get_user_meta($user->ID, 'billing_email', true);
				$user_email = $user_email ? $user_email : $user->user_email;
			} else {
                $user_email = '';
            }
		}

        echo '<div
			id="xendit-payment-cc-data"
			data-description=""
			data-email="' . esc_attr($user_email) . '"
			data-amount="' . esc_attr($total) . '"
			data-name="' . esc_attr($this->statement_descriptor) . '"
			data-currency="' . esc_attr(strtolower(get_woocommerce_currency())) . '"
			data-locale="' . esc_attr('en') . '"
			data-image="' . esc_attr($this->xendit_checkout_image) . '"
			data-allow-remember-me="' . esc_attr($this->saved_cards ? 'true' : 'false') . '">';

        if ($this->description && !is_add_payment_method_page()) {
            echo apply_filters('wc_xendit_description', wpautop(wp_kses_post($this->description)));
        }

        if ($display_tokenization) {
            $this->tokenization_script();
            $this->saved_payment_methods();
        }

        // Load the fields. Source: https://woocommerce.wp-a2z.org/oik_api/wc_payment_gateway_ccform/
        $this->form();

        if (apply_filters('wc_'.$this->id.'_display_save_payment_method_checkbox', $display_tokenization) && !is_add_payment_method_page() && !isset($_GET['change_payment_method'])) {
            $this->save_payment_method_checkbox();
        }
        do_action( 'wc_'.$this->id.'_cards_payment_fields', $this->id );

        echo '</div>';
    }

    /**
     * Localize Xendit messages based on code
     *
     * @since 3.0.6
     * @version 3.0.6
     * @return array
     */
    public function get_frontend_error_message()
    {
        return apply_filters('wc_xendit_localized_messages', array(
            'invalid_number'            => __('Invalid Card Number. Please make sure the card is Visa / Mastercard / JCB. Code: 200030', 'woocommerce-gateway-xendit'),
            'invalid_expiry'            => __('The card expiry that you entered does not meet the expected format. Please try again by entering the 2 digits of the month (MM) and the last 2 digits of the year (YY). Code: 200031', 'woocommerce-gateway-xendit'),
            'invalid_cvc'               => __('The CVC that you entered is less than 3 digits. Please enter the correct value and try again. Code: 200032', 'woocommerce-gateway-xendit'),
            'incorrect_number'          => __('The card number that you entered must be 16 digits long. Please re-enter the correct card number and try again. Code: 200033', 'woocommerce-gateway-xendit'),
            'missing_card_information'  => __('Card information is incomplete. Please complete it and try again. Code: 200034', 'woocommerce-gateway-xendit'),
        ));
    }

    /**
     * payment_scripts function.
     *
     * Outputs scripts used for xendit payment
     *
     * @access public
     */
    public function payment_scripts()
    {
        global $wp;

        if (!is_cart() && 
            !is_checkout() && 
            !isset($_GET['pay_for_order']) && 
            !is_add_payment_method_page() && 
            !isset($_GET['change_payment_method'])
        ) {
            return;
        }

        wp_enqueue_script('xendit', 'https://js.xendit.co/v1/xendit.min.js', '', WC_XENDIT_PG_VERSION, true);
        wp_enqueue_script('woocommerce_'.$this->id, plugins_url('assets/js/xendit.js', WC_XENDIT_PG_MAIN_FILE), array('jquery', 'xendit'), WC_XENDIT_PG_VERSION, true);

        $xendit_params = array(
            'key' => $this->publishable_key,
            'on_behalf_of' => $this->for_user_id,
            'amount' => WC()->cart->cart_contents_total + WC()->cart->tax_total + WC()->cart->shipping_total,
            'currency' => get_woocommerce_currency()
        );

        // If we're on the pay page we need to pass xendit.js the address of the order.
        if (isset($_GET['pay_for_order']) && 'true' === $_GET['pay_for_order']) {
            $order_id = wc_clean($wp->query_vars['order-pay']);
			$order    = wc_get_order($order_id);

            $xendit_params['billing_first_name'] = $order->get_billing_first_name();
            $xendit_params['billing_last_name']  = $order->get_billing_last_name();
            $xendit_params['billing_address_1']  = $order->get_billing_address_1();
            $xendit_params['billing_address_2']  = $order->get_billing_address_2();
            $xendit_params['billing_state']      = $order->get_billing_state();
            $xendit_params['billing_city']       = $order->get_billing_city();
            $xendit_params['billing_postcode']   = $order->get_billing_postcode();
            $xendit_params['billing_country']    = $order->get_billing_country();
        }

        $cc_settings = $this->get_cc_settings();
        $xendit_params['can_use_dynamic_3ds'] = isset($cc_settings['can_use_dynamic_3ds']) ? $cc_settings['can_use_dynamic_3ds'] : false;
        $xendit_params['has_saved_cards'] = $this->saved_cards;

        // merge localized messages to be use in JS
        $xendit_params = array_merge($xendit_params, $this->get_frontend_error_message());

        wp_localize_script('woocommerce_'.$this->id, 'wc_xendit_params', apply_filters('wc_xendit_params', $xendit_params));
    }

    /**
	 * Add payment method via account screen.
	 * We store the token locally.
	 */
    public function add_payment_method()
    {
		$error_msg = __('There was a problem adding the payment method.', 'woocommerce-gateway-xendit');

		if (empty($_POST['xendit_token']) || !is_user_logged_in()) {
            wc_add_notice($error_msg, 'error');
            return;
		}

        $token = wc_clean($_POST['xendit_token']);

        $source = array(
            "card_last_four"     => substr(wc_clean($_POST['xendit_card_number']), -4),
            "card_expiry_year"   => wc_clean($_POST['xendit_card_exp_year']),
            "card_expiry_month"  => wc_clean($_POST['xendit_card_exp_month']),
            "card_type"          => wc_clean($_POST['xendit_card_type'])
        );
        
        $this->save_payment_token($token, $source);

		return array(
			'result'   => 'success',
			'redirect' => wc_get_endpoint_url('payment-methods'),
		);
    }
    
    /**
	 * Store the payment token when saving card.
	 */
    public function save_payment_token($tokenId, $source)
    {
        $user_id = get_current_user_id();

        $token = new WC_Payment_Token_CC();
        $token->set_token($tokenId);
        $token->set_gateway_id($this->id);
        $token->set_last4($source['card_last_four']);
        $token->set_expiry_year($source['card_expiry_year']);
        $token->set_expiry_month($source['card_expiry_month']);
        $token->set_card_type($source['card_type']);
        $token->set_user_id($user_id);
        $token->save();

        // Set this token as the users new default token
        WC_Payment_Tokens::set_users_default($user_id, $token->get_id());
    }

    /**
     * Generate the request for the payment.
     * @param  WC_Order $order
     * @param  object $source
     * @return array()
     */
    protected function generate_payment_request($order, $xendit_token, $auth_id = '', $duplicated = false, $is_recurring = false, $check_ccpromo = true)
    {
        global $woocommerce;

        $amount = $order->get_total();

        //TODO: Find out how can we pass CVN on redirected flow
        $cvn = isset($_POST['xendit_card_cvn']) ? wc_clean($_POST['xendit_card_cvn']) : '';

        $main_settings = get_option('woocommerce_xendit_gateway_settings');
        $default_external_id = $this->external_id_format . '-' . $order->get_id();
        $external_id = $duplicated ? $default_external_id . '-' . uniqid() : $default_external_id;
        $additional_data = WC_Xendit_PG_Helper::generate_items_and_customer($order, $amount);

        $post_data                              = array();
        $post_data['amount']                    = $amount;
        $post_data['currency']                  = $order->get_currency();
        $post_data['token_id']                  = $xendit_token;
        $post_data['external_id']               = $external_id;
        $post_data['store_name']                = get_option('blogname');
        $post_data['items']                     = isset($additional_data['items']) ? $additional_data['items'] : '';
        $post_data['customer']                  = $this->get_customer_details($order);

        if ($cvn) {
            $post_data['card_cvn']              = $cvn;
        }
        if ($auth_id) {
            $post_data['authentication_id']     = $auth_id;
        }
        if ($is_recurring) {
            $post_data['is_recurring']          = $is_recurring;
        }
        if (!empty($_POST['xendit_installment'])) { //JSON string
            $installment_data                   = stripslashes(wc_clean($_POST['xendit_installment']));
            $post_data['installment']           = json_decode($installment_data);
        }

        // get charge option by token ID
        if ($check_ccpromo) {
            $ccOption = $this->xenditClass->getChargeOption($xendit_token, $amount, $post_data['currency']);
            if (!empty($ccOption['promotions'][0])) { //charge with discounted amount
                $post_data['amount'] = $ccOption['promotions'][0]['final_amount'];
                $discount = $amount - $post_data['amount'];
                $order->add_order_note('Card promotion applied. Total discounted amount: ' . $post_data['currency'] . ' ' . number_format($discount));
    
                // add discount item to order total
                $item = new WC_Order_Item_Fee();
                $item->set_name($ccOption['promotions'][0]['reference_id']);
                $item->set_amount(-$discount);
                $item->set_total(-$discount);
                $item->set_tax_class('');
                $item->set_taxes(false);
                $item->save();

                $order->add_item($item);
                $order->calculate_totals(false); //no taxes
                $order->save();
            }
        }

        return $post_data;
    }

    /**
     * Get payment source. This can be a new token or existing token.
     *
     * @throws Exception When card was not added or for and invalid card.
     * @return object
     */
    protected function get_source()
    {
        $xendit_source   = false;
        $token_id        = false;

        // New CC info was entered and we have a new token to process
        if (isset($_POST['xendit_token'])) {
            $xendit_token = wc_clean($_POST['xendit_token']);
            // Not saving token, so don't define customer either.
            $xendit_source = $xendit_token;
        }
        else if (isset($_POST['wc-'.$this->id.'-payment-token']) && 'new' !== $_POST['wc-'.$this->id.'-payment-token']) {
            // Use an EXISTING multiple use token, and then process the payment
            $token_id = wc_clean($_POST['wc-'.$this->id.'-payment-token']);
            $token    = WC_Payment_Tokens::get($token_id);

            // associates payment token with WP user_id
            if (!$token || $token->get_user_id() !== get_current_user_id()) {
                WC()->session->set('refresh_totals', true);
                throw new Exception(__('Invalid payment method. Please input a new card number. Code: 200036', 'woocommerce-gateway-xendit'));
            }

            $xendit_source = $token->get_token();
        }

        return (object) array(
            'token_id' => $token_id,
            'source'   => $xendit_source,
        );
    }

    /**
     * Get payment source from an order. This could be used in the future for
     * a subscription as an example, therefore using the current user ID would
     * not work - the customer won't be logged in :)
     *
     * Not using 2.6 tokens for this part since we need a customer AND a card
     * token, and not just one.
     *
     * @param object $order
     * @return object
     */
    protected function get_order_source($order = null)
    {
        $xendit_source   = false;
        $token_id        = false;

        if ($order) {
            $order_id = version_compare(WC_VERSION, '3.0.0', '<') ? $order->id : $order->get_id();

            if ($meta_value = get_post_meta($order_id, '_xendit_card_id', true)) {
                $xendit_source = $meta_value;
            }
        }

        return (object) array(
            'token_id' => $token_id,
            'source'   => $xendit_source,
        );
    }

    /**
     * Process the payment.
     *
     * NOTE 2019/03/22: The key to have 3DS after order creation is calling it after this is called.
     * Currently still can't do it somehow. Need to dig deeper on this!
     *
     * @param int  $order_id Reference.
     * @param bool $retry Should we retry on fail.
     *
     * @throws Exception If payment will not be accepted.
     *
     * @return array|void
     */
    public function process_payment($order_id, $retry = true)
    {
        $cc_settings = $this->get_cc_settings();
        
        try {
            $order = new WC_Order($order_id);
            
            if ($order->get_total() < $this->DEFAULT_MINIMUM_AMOUNT) {
                WC_Xendit_PG_Helper::cancel_order($order, 'Cancelled because amount is below minimum amount');
                
                $err_msg = sprintf(__(
                    'The minimum amount for using this payment is %1$s %2$s. Please put more item(s) to reach the minimum amount. Code: 100001', 
                    'woocommerce-gateway-xendit'
                ), $order->get_currency(), wc_price($this->DEFAULT_MINIMUM_AMOUNT));

                wc_add_notice($this->get_localized_error_message('INVALID_AMOUNT_ERROR', $err_msg), 'error');
                return;
            }

            if ($order->get_total() > $this->DEFAULT_MAXIMUM_AMOUNT) {
                WC_Xendit_PG_Helper::cancel_order($order, 'Cancelled because amount is above maximum amount');
                
                $err_msg = sprintf(__(
                    'The maximum amount for using this payment is %1$s %2$s. Please remove one or more item(s) from your cart. Code: 100002', 
                    'woocommerce-gateway-xendit'
                ), $order->get_currency(), wc_price($this->DEFAULT_MAXIMUM_AMOUNT));
                
                wc_add_notice($this->get_localized_error_message('INVALID_AMOUNT_ERROR', $err_msg), 'error');
                return;
            }

            // Handle error from tokenization phase here
            if (isset($_POST['xendit_failure_reason'])) {
                $xendit_failure_reason = wc_clean($_POST['xendit_failure_reason']);
                $order->add_order_note('Checkout with credit card unsuccessful. Reason: ' . $xendit_failure_reason);

                throw new Exception(__($xendit_failure_reason, 'woocommerce-gateway-xendit'));
            }

            // Get token.
            $source = $this->get_source();

            if (empty($source->source)) {
                $error_msg = __('The card you are trying to use has been declined. Please try again with a different card. Code: 200037', 'woocommerce-gateway-xendit');
                throw new Exception($error_msg);
            }

            // Store source to order meta.
            $this->save_source($order, $source);
            
            $maybe_saved_card = false;

            // If using saved card
            if (isset($_POST['wc-'.$this->id.'-payment-token']) && 'new' !== $_POST['wc-'.$this->id.'-payment-token']) {
                $token_id = wc_clean($_POST['wc-'.$this->id.'-payment-token']);
        
                $token = WC_Payment_Tokens::get($token_id);
                $xendit_token = $token->get_token();
            }
            else if (isset($_POST['xendit_token'])) {
                $xendit_token = wc_clean($_POST['xendit_token']);

                // This checks to see if customer opted to save the payment method.
                $maybe_saved_card = isset($_POST['wc-' . $this->id . '-new-payment-method']) && !empty($_POST['wc-' . $this->id . '-new-payment-method']);
            }

            if(empty($cc_settings["should_authenticate"])) {
                // if should_authenticate equal to false
                if(!empty($cc_settings["can_use_dynamic_3ds"])) {
                    // if can_use_dynamic_3ds equal to true, the payment using 3ds recomendation
                    return $this->process_payment_3ds_recommendation($order, $xendit_token, $maybe_saved_card);
                } else {
                    return $this->process_payment_without_authenticate($order, $xendit_token, $maybe_saved_card);
                }
            } else {
                // if should_authenticate equal to true, the payment must using 3ds
                return $this->process_payment_must_3ds($order, $xendit_token, $maybe_saved_card);
            }
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');

            if ($order->has_status(array('pending', 'failed'))) {
                $this->send_failed_order_email($order_id);
            }

            // log error metrics
            $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code);
            $this->xenditClass->trackMetricCount($metrics);
            return;
        }
    }

    /**
     * Payment flow using 3DS recommendation feature.
     * 
     * @param WC_Order $order
     * @param string $xendit_token
     */
    private function process_payment_3ds_recommendation($order, $xendit_token, $maybe_saved_card)
    {
        $xendit_should_3ds = $_POST['xendit_should_3ds'];
        if ($xendit_should_3ds === 'true') {
            return $this->process_payment_must_3ds($order, $xendit_token, $maybe_saved_card);
        } else {
            return $this->process_payment_without_authenticate($order, $xendit_token, $maybe_saved_card);
        }
    }

    /**
     * Payment using must use 3ds flow.
     * 
     * @param WC_Order $order
     * @param string $xendit_token
     */
    private function process_payment_must_3ds($order, $xendit_token, $maybe_saved_card)
    {
        $hosted_3ds_response = $this->create_hosted_3ds($order, $xendit_token);

        if ('IN_REVIEW' === $hosted_3ds_response['status']) {
            // save payment method when save to account checkbox is checked
            if (is_user_logged_in() && $this->saved_cards && $maybe_saved_card) {
                $this->add_payment_method();
            }

            return array(
                'result'   => 'success',
                'redirect' => esc_url_raw($hosted_3ds_response['redirect']['url']),
            );
        } else if ('VERIFIED' === $hosted_3ds_response['status']) {
            $request_payload = $this->generate_payment_request($order, $xendit_token, $hosted_3ds_response['authentication_id'], false, false, false);
            $response = $this->xenditClass->createCharge($request_payload);

            if (!empty($response['error_code'])) {
                if ($response['error_code'] == 'EXTERNAL_ID_ALREADY_USED_ERROR') {
                    $request_payload = $this->generate_payment_request($order, $xendit_token, $hosted_3ds_response['authentication_id'], true, false, false);
                    $response = $this->xenditClass->createCharge($request_payload);
                }
            }

            if (!empty($_POST['xendit_installment'])) {
                $installment_data = stripslashes(wc_clean($_POST['xendit_installment']));
                $installment = json_decode($installment_data, true);
                $order->update_meta_data('_xendit_cards_installment', $installment['count'] . ' ' . $installment['interval']);
                $order->save();
            }

            $this->process_response($response, $order);

            WC()->cart->empty_cart();

            do_action('wc_'.$this->id.'_process_payment', $response, $order);

            // save payment method when save to account checkbox is checked
            if (is_user_logged_in() && $this->saved_cards && $maybe_saved_card) {
                $this->add_payment_method();
            }

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
        } else {
            $error_msg = 'Authentication process failed. Please try again. Code: 200039';

            // log error metrics
            $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code);
            $this->xenditClass->trackMetricCount($metrics);

            throw new Exception($error_msg);
        }

        return $response;
    }

    /**
     * Payment without authenticate flow.
     * 
     * @param WC_Order $order
     * @param string $xendit_token
     */
    private function process_payment_without_authenticate($order, $xendit_token, $maybe_saved_card)
    {
        try {
            $request_payload = $this->generate_payment_request($order, $xendit_token);
            $response = $this->xenditClass->createCharge($request_payload);

            if (!empty($response['error_code'])) {
                if ($response['error_code'] == 'EXTERNAL_ID_ALREADY_USED_ERROR') {
                    $request_payload = $this->generate_payment_request($order, $xendit_token, '', true);
                    $response = $this->xenditClass->createCharge($request_payload);
                }
            }

            if (!empty($_POST['xendit_installment'])) {
                $installment_data = stripslashes(wc_clean($_POST['xendit_installment']));
                $installment = json_decode($installment_data, true);
                $order->update_meta_data('_xendit_cards_installment', $installment['count'] . ' ' . $installment['interval']);
                $order->save();
            }

            $this->process_response($response, $order);

            WC()->cart->empty_cart();

            do_action('wc_'.$this->id.'_process_payment', $response, $order);

            // save payment method when save to account checkbox is checked
            if (is_user_logged_in() && $this->saved_cards && $maybe_saved_card) {
                $this->add_payment_method();
            }

            // Return thank you page redirect.
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } catch (Exception $e) {
            // log error metrics
            $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code);
            $this->xenditClass->trackMetricCount($metrics);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Store extra meta data for an order from a Xendit Response.
     */
    public function process_response($response, $order)
    {
        if (is_wp_error($response)) {
            if ('source' === $response->get_error_code() && $source->token_id) {
                $token = WC_Payment_Tokens::get($source->token_id);
                $token->delete();
                $message = __('The card you are trying to use has been declined. Please try again with a different card. Code: 200038', 'woocommerce-gateway-xendit');
                $order->add_order_note('Card charge error. Reason: ' . $message);

                throw new Exception($message);
            }

            $localized_messages = $this->get_frontend_error_message();
            $message = isset($localized_messages[$response->get_error_code()]) ? $localized_messages[$response->get_error_code()] : $response->get_error_message();
            $order->add_order_note('Card charge error. Reason: ' . $message);

            throw new Exception($message);
        }

        if (!empty($response['error_code'])) {
            $response['message'] = !empty($response['code']) ? $response['message'] . ' Code: ' . $response['code'] : $response['message'];
            $message = $this->get_localized_error_message($response['error_code'], $response['message']);
            $order->add_order_note('Card charge error. Reason: ' . $message);

            throw new Exception($message);
        }

        if (empty($response['id'])) { //for merchant who uses old API version
            throw new Exception($this->generic_error_message . 'Code: 200040');
        }

        if ($response['status'] !== 'CAPTURED') {
            $order->update_status('failed', sprintf(__('Xendit charges (Charge ID:'.$response['id'].').', 'woocommerce-gateway-xendit'), $response['id']));
            $message = $this->get_localized_error_message($response['failure_reason']);
            $order->add_order_note('Card charge error. Reason: ' . $message);

            throw new Exception($message);
        }

        $order_id = version_compare(WC_VERSION, '3.0.0', '<') ? $order->id : $order->get_id();

        // Store other data such as fees
        if (isset($response['balance_transaction']) && isset($response['balance_transaction']['fee'])) {
            // Fees and Net need to both come from Xendit to be accurate as the returned
            // values are in the local currency of the Xendit account, not from WC.
            $fee = !empty($response['balance_transaction']['fee']) ? number_format($response['balance_transaction']['fee']) : 0;
            $net = !empty($response['balance_transaction']['net']) ? number_format($response['balance_transaction']['net']) : 0;
            update_post_meta($order_id, 'Xendit Fee', $fee);
            update_post_meta($order_id, 'Net Revenue From Xendit', $net);
        }

        $this->complete_cc_payment($order, $response['id'], $response['status'], $response['capture_amount']);

        do_action('wc_gateway_xendit_process_response', $response, $order);

        return $response;
    }

    /* 
     * Get CC Setting
     */
    private function get_cc_settings() {
        global $wpdb;

        $cc_settings = get_transient('cc_settings_xendit_pg');

        if (empty($cc_settings)) {
            $cc_settings = $this->xenditClass->getCCSettings();
            set_transient('cc_settings_xendit_pg', $cc_settings, 60);
        }

        return $cc_settings;
    }
    
    /**
     * Save source to order.
     *
     * @param WC_Order $order For to which the source applies.
     * @param stdClass $source Source information.
     */
    protected function save_source($order, $source)
    {
        $order_id = version_compare(WC_VERSION, '3.0.0', '<') ? $order->id : $order->get_id();

        // Store source in the order.
        if ($source->source) {
            version_compare(WC_VERSION, '3.0.0', '<') ? update_post_meta($order_id, '_xendit_card_id', $source->source) : $order->update_meta_data('_xendit_card_id', $source->source);
        }

        if (is_callable(array( $order, 'save' ))) {
            $order->save();
        }
    }

    /**
     * Refund a charge
     * @param  int $order_id
     * @param  float $amount
     * @return bool
     */
    public function process_refund($order_id, $amount = null, $reason = '', $duplicated = false)
    {
        $order = wc_get_order($order_id);

        if (!$order || !$order->get_transaction_id()) {
            return false;
        }

        $default_external_id = $this->external_id_format . '-' . $order->get_transaction_id();
        $body = array(
            'store_name'    => get_option('blogname'),
            'external_id'   => $duplicated ? $default_external_id . '-' . uniqid() : $default_external_id
        );

        if (is_null($amount) || (float)$amount < 1) {
            return false;
        }

        if ($amount) {
            $body['amount']	= $amount;
        }

        if ($reason) {
            $body['metadata'] = array(
                'reason'	=> $reason,
            );
        }

        $response = $this->xenditClass->createRefund($body, $order->get_transaction_id());

        if (is_wp_error($response)) {
            return false;
        } else if (!empty($response['error_code'])) {
            if ($response['error_code'] === 'DUPLICATE_REFUND_ERROR') {
                return $this->process_refund($order_id, $amount, $reason, true);
            }

            return false;
        } else if (!empty($response['id'])) {
            $refund_message = sprintf(__('Refunded %1$s - Refund ID: %2$s - Reason: %3$s', 'woocommerce-gateway-xendit'), wc_price($response['amount']), $response['id'], $reason);
            $order->add_order_note($refund_message);
            
            return true;
        }
    }

    /**
     * Sends the failed order email to admin
     *
     * @version 3.1.0
     * @since 3.1.0
     * @param int $order_id
     * @return null
     */
    public function send_failed_order_email($order_id)
    {
        $emails = WC()->mailer()->get_emails();
        if (! empty($emails) && ! empty($order_id)) {
            $emails['WC_Email_Failed_Order']->trigger($order_id);
        }
    }

    public function create_hosted_3ds($order, $xendit_token)
    {
        global $woocommerce;

        $amount = $order->get_total();
        $additional_data = WC_Xendit_PG_Helper::generate_items_and_customer($order, $amount);
        
        $args = array(
            'utm_nooverride' => '1',
            'order_id'       => $order->get_id(),
        );
        $return_url = esc_url_raw(add_query_arg($args, $this->get_return_url($order)));
        
        $hosted_3ds_data = array(
            'token_id'		        => $xendit_token,
            'amount'		        => $amount,
            'currency'		        => $order->get_currency(),
            'external_id'	        => $this->external_id_format .'-'. $order->get_id(),
            'platform_callback_url' => $this->xendit_callback_url,
            'return_url'	        => $return_url, //thank you page
            'failed_return_url'     => wc_get_checkout_url(),
            'items'                 => isset($additional_data['items']) ? $additional_data['items'] : '',
            'customer'              => $this->get_customer_details($order),
        );

        // get charge option by token ID
        $ccOption = $this->xenditClass->getChargeOption($xendit_token, $amount, $hosted_3ds_data['currency']);
        if (!empty($ccOption['promotions'][0])) { //charge with discounted amount
            $hosted_3ds_data['amount'] = $ccOption['promotions'][0]['final_amount'];
            $discount = $amount - $hosted_3ds_data['amount'];
            $order->add_order_note('Card promotion applied. Total discounted amount: ' . $hosted_3ds_data['currency'] . ' ' . number_format($discount));

            // add discount item to order total
            $item = new WC_Order_Item_Fee();
            $item->set_name($ccOption['promotions'][0]['reference_id']);
            $item->set_amount(-$discount);
            $item->set_total(-$discount);
            $item->set_tax_class('');
            $item->set_taxes(false);
            $item->save();

            $order->add_item($item);
            $order->calculate_totals(false); //no taxes
            $order->save();
        }

        $installment = array();
        if (!empty($_POST['xendit_installment'])) { //JSON string
            $installment_data = stripslashes(wc_clean($_POST['xendit_installment']));
            $hosted_3ds_data['installment'] = json_decode($installment_data);
            $installment = json_decode($installment_data, true);
        }
 
        $hosted_3ds_response = $this->xenditClass->createHostedThreeDS($hosted_3ds_data);
        
        if (!empty($hosted_3ds_response['error'])) {
            $localized_message = $hosted_3ds_response['error']['message'];

            $order->add_order_note($localized_message);

            throw new WP_Error(print_r($hosted_3ds_response), $localized_message);
        }

        if (WC_Xendit_PG_Helper::is_wc_lt('3.0')) {
            update_post_meta($order_id, '_xendit_hosted_3ds_id', $hosted_3ds_response['id']);
            if ($installment) {
                update_post_meta($order_id, '_xendit_cards_installment', $installment['count'] . ' ' . $installment['interval']);
            }
        } else {
            $order->update_meta_data('_xendit_hosted_3ds_id', $hosted_3ds_response['id']);
            if ($installment) {
                $order->update_meta_data('_xendit_cards_installment', $installment['count'] . ' ' . $installment['interval']);
            }
            $order->save();
        }
        
        return $hosted_3ds_response;
    }

    public function is_valid_for_use()
    {
        return in_array(get_woocommerce_currency(), apply_filters(
            'woocommerce_' . $this->id . '_supported_currencies',
            $this->supported_currencies
        ));
    }

    public function check_gateway_status($gateways)
    {
        global $wpdb, $woocommerce;
        
        if (is_null($woocommerce->cart)) {
            return $gateways;
        }

        if ($this->enabled == 'no') {
            unset($gateways[$this->id]);
            return $gateways;
        }

        if ($this->secret_key == "") {
            unset($gateways[$this->id]);
            return $gateways;
        }

        if (!$this->is_valid_for_use()) {
            unset($gateways[$this->id]);
            return $gateways;
        }

        return $gateways;
    }

    /**
     * Map card's failure reason to more detailed explanation based on current insight.
     *
     * @param $failure_reason
     * @return string
     */
    private function get_localized_error_message($failure_reason, $message = "")
    {
        switch ($failure_reason) {
            // mapping failure_reason, while error_code has been mapped via TPI Service
            case 'AUTHENTICATION_FAILED':
                return 'Authentication process failed. Please try again. Code: 200001';
            case 'PROCESSOR_ERROR': 
                return $this->generic_error_message . 'Code: 200009';
            case 'EXPIRED_CARD': 
                return 'Card declined due to expiration. Please try again with another card. Code: 200010';
            case 'CARD_DECLINED':
                return 'Card declined by the issuer. Please try with another card or contact the bank directly. Code: 200011';
            case 'INSUFFICIENT_BALANCE': 
                return 'Card declined due to insuficient balance. Ensure the sufficient balance is available, or try another card. Code: 200012';
            case 'STOLEN_CARD': 
                return 'Card declined by the issuer. Please try with another card or contact the bank directly. Code: 200013';
            case 'INACTIVE_CARD': 
                return 'Card declined due to eCommerce payments enablement. Please try with another card or contact the bank directly. Code: 200014';
            case 'INVALID_CVN': 
                return 'Card declined due to incorrect card details entered. Please try again. Code: 200015';
            default: 
                return $message ? $message : $failure_reason;
        }
    }

    /**
     * Retrieve customer details. Currently will intro this new structure
     * on cards endpoint only.
     * 
     * Source: https://docs.woocommerce.com/wc-apidocs/class-WC_Order.html
     * 
     * @param $order
     */
    private function get_customer_details($order)
    {
        $customer_details = array();

        $billing_details = array();
        $billing_details['first_name'] = $order->get_billing_first_name();
        $billing_details['last_name'] = $order->get_billing_last_name();
        $billing_details['email'] = $order->get_billing_email();
        $billing_details['phone_number'] = $order->get_billing_phone();
        $billing_details['address_city'] = $order->get_billing_city();
        $billing_details['address_postal_code'] = $order->get_billing_postcode();
        $billing_details['address_line_1'] = $order->get_billing_address_1();
        $billing_details['address_line_2'] = $order->get_billing_address_2();
        $billing_details['address_state'] = $order->get_billing_state();
        $billing_details['address_country'] = $order->get_billing_country();


        $shipping_details = array();
        $shipping_details['first_name'] = $order->get_shipping_first_name();
        $shipping_details['last_name'] = $order->get_shipping_last_name();
        $shipping_details['address_city'] = $order->get_shipping_city();
        $shipping_details['address_postal_code'] = $order->get_shipping_postcode();
        $shipping_details['address_line_1'] = $order->get_shipping_address_1();
        $shipping_details['address_line_2'] = $order->get_shipping_address_2();
        $shipping_details['address_state'] = $order->get_shipping_state();
        $shipping_details['address_country'] = $order->get_shipping_country();

        $customer_details['billing_details'] = $billing_details;
        $customer_details['shipping_details'] = $shipping_details;

        return json_encode($customer_details);
    }

    public function complete_cc_payment($order, $charge_id, $status, $amount)
    {
        global $woocommerce;

        $order_id = version_compare(WC_VERSION, '3.0.0', '<') ? $order->id : $order->get_id();

        if (!$order->is_paid()) {
            $notes = WC_Xendit_PG_Helper::build_order_notes(
                $charge_id, 
                $status, 
                'CREDIT CARD', 
                $order->get_currency(), 
                $amount,
                get_post_meta($order_id, '_xendit_cards_installment', true)
            );

            WC_Xendit_PG_Helper::complete_payment($order, $notes, $this->success_payment_xendit, $charge_id);
    
            update_post_meta($order_id, '_xendit_charge_id', $charge_id);
            update_post_meta($order_id, '_xendit_charge_captured', 'yes');
            $message = sprintf(__('Xendit charge complete (Charge ID: %s)', 'woocommerce-gateway-xendit'), $charge_id);
            $order->add_order_note($message);
    
            // Reduce stock levels
            version_compare(WC_VERSION, '3.0.0', '<') ? $order->reduce_order_stock() : wc_reduce_stock_levels($order_id);

            // send customer object data
            $reference_id = (!empty($order->get_billing_email())) ? $order->get_billing_email() : $order->get_billing_phone();
            $customer = WC_Xendit_PG_Helper::generate_customer($order);
            WC_Xendit_PG_Helper::process_customer_object($reference_id, $customer);
            
            // log success metrics
            $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'success', $this->method_code);
            $this->xenditClass->trackMetricCount($metrics);
        }
    }

    public function validate_payment($response)
    {
        global $wpdb, $woocommerce;

        $errorMetrics = $this->xenditClass->constructMetricPayload('woocommerce_callback', 'error', $this->method_code);
        $successMetrics = $this->xenditClass->constructMetricPayload('woocommerce_callback', 'success', $this->method_code);

        try {
            $external_id = $response->external_id;
            $xendit_status = $this->xendit_status;

            if ($external_id) {
                $exploded_ext_id = explode("-", $external_id);
                $order_num = end($exploded_ext_id);
    
                if (!is_numeric($order_num)) {
                    $exploded_ext_id = explode("_", $external_id);
                    $order_num = end($exploded_ext_id);
                }
    
                sleep(3);
                $is_changing_status = $this->get_is_changing_order_status($order_num);
    
                if ($is_changing_status) {
                    echo 'Already changed with redirect';
                    exit;
                }
    
                $order = new WC_Order($order_num);
                $order_id = version_compare(WC_VERSION, '3.0.0', '<') ? $order->id : $order->get_id();
    
                if ($this->developmentmode != 'yes') {
                    $payment_gateway = wc_get_payment_gateway_by_order($order_id);
                    if (false === get_post_status($order_id) || strpos($payment_gateway->id, 'xendit')) {
                        header('HTTP/1.1 400 Invalid Data Received');
                        echo 'Xendit is live and require a valid order id';
                        exit;
                    }
                }
    
                $charge = $this->xenditClass->getCharge($response->id);
    
                if (isset($charge['error_code'])) {
                    header('HTTP/1.1 400 Invalid Credit Card Charge Data Received');
                    echo 'Error in getting credit card charge. Error code: ' . $charge['error_code'];
                    exit;
                }
    
                if ('CAPTURED' == $charge['status']) {
                    $this->complete_cc_payment($order, $charge['id'], $charge['status'], $charge['capture_amount']);

                    $this->xenditClass->trackEvent(array(
                        'reference' => 'charge_id',
                        'reference_id' => $charge['id'],
                        'event' => 'ORDER_UPDATED_AT.CALLBACK'
                    ));

                    $this->xenditClass->trackMetricCount($successMetrics);
                    die('Success');
                } else {
                    $order->update_status('failed', sprintf(__('Xendit charges (Charge ID: %s).', 'woocommerce-gateway-xendit'), $charge['id']));

                    $message = $this->get_localized_error_message($charge['failure_reason']);
                    $order->add_order_note($message);
    
                    $notes = WC_Xendit_PG_Helper::build_order_notes(
                        $charge['id'], 
                        $charge['status'], 
                        'CREDIT CARD', 
                        $order->get_currency(), 
                        $charge['capture_amount']
                    );
                    $order->add_order_note("<b>Xendit payment failed.</b><br>" . $notes);

                    $this->xenditClass->trackMetricCount($successMetrics);
                    die('Credit card charge status is ' . $charge['status']);
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
    
    /** 
     * Show error base on query
     */
    function show_checkout_error() { 
        if(isset($_REQUEST['error'])) {
            $notices = wc_add_notice($this->get_localized_error_message($_REQUEST['error']), 'error');
            unset($_REQUEST['error']);
            wp_safe_redirect(wc_get_checkout_url());
        }
    }

    function update_order_status($order_id) {
        if (!$order_id){
            return;
        }

        $order = new WC_Order($order_id);
        if ($order->get_status() == 'processing' && $order->get_status() != $this->success_payment_xendit) {
            $order->update_status($this->success_payment_xendit);
        }
        return;
    }

    public function get_is_changing_order_status($order_id, $state = true)
    {
        $transient_key = 'xendit_is_changing_order_status_' . $order_id;

        $is_changing_order_status = get_transient($transient_key);

        if (empty($is_changing_order_status)) {
            set_transient($transient_key, $state, 60);

            return false;
        }
        
        return $is_changing_order_status;
    }
}

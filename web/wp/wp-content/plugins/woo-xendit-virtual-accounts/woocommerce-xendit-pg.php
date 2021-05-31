<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
Plugin Name: Woocommerce - Xendit
Plugin URI: https://wordpress.org/plugins/woo-xendit-virtual-accounts
Description: Accept payments in Indonesia with Xendit. Seamlessly integrated into WooCommerce.
Version: 2.24.1
Author: Xendit
Author URI: https://www.xendit.co/
*/

define('WC_XENDIT_PG_VERSION', '2.24.1');
define('WC_XENDIT_PG_MAIN_FILE', __FILE__);
define('WC_XENDIT_PG_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));

add_action('plugins_loaded', 'woocommerce_xendit_pg_init');

function woocommerce_xendit_pg_init()
{
    if (! class_exists('WC_Payment_Gateway')) {
        return;
    }

    if (! class_exists('WC_Xendit_PG')) {
        class WC_Xendit_PG
        {
            private static $instance;

            public static function get_instance()
            {
                if (self::$instance === null) {
                    self::$instance = new self();
                }

                return self::$instance;
            }

            private function __clone()
            {
            }

            private function __wakeup()
            {
            }

            private function __construct()
            {
                $this->init();                
            }

            public function init()
            {
                require_once dirname(__FILE__) . '/libs/class-wc-xendit-api.php';

                require_once dirname(__FILE__) . '/libs/helpers/class-wc-xendit-email.php';
                require_once dirname(__FILE__) . '/libs/helpers/class-wc-xendit-logger.php';
                require_once dirname(__FILE__) . '/libs/helpers/class-wc-xendit-site-data.php';
                
                require_once dirname(__FILE__) . '/libs/class-wc-xendit-helper.php';
                require_once dirname(__FILE__) . '/libs/class-wc-xendit-invoice.php';
                require_once dirname(__FILE__) . '/libs/class-wc-xendit-ewallet.php';
                require_once dirname(__FILE__) . '/libs/class-wc-xendit-cardless.php';
                require_once dirname(__FILE__) . '/libs/class-wc-xendit-cc.php';
                require_once dirname(__FILE__) . '/libs/class-wc-xendit-cc-redirect-handler.php';
                require_once dirname(__FILE__) . '/libs/class-wc-xendit-qris.php';
                
                require_once dirname(__FILE__) . '/libs/cronjob/wc-cron-xendit-site-data.php';

                if ($this->should_load_addons()) {
                    require_once dirname(__FILE__) . '/libs/class-wc-xendit-cc-addons.php';
                }

                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-bcava.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-bni-syariah-va.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-bniva.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-briva.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-mandiriva.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-permatava.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-alfamart.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-indomaret.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-shopeepay.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-paymaya.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-gcash.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-grabpay.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-invoice-7eleven.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-ewallet-ovo.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-ewallet-dana.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-ewallet-linkaja.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-dd-bri.php';
                require_once dirname(__FILE__) . '/libs/methods/class-wc-xendit-dd-bpi.php';
                
                add_filter('plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_action_links' ));
                add_filter('woocommerce_payment_gateways', array( $this, 'woocommerce_add_xendit_gateway' ));
                add_filter('woocommerce_payment_complete_order_status', array( $this, 'update_status_complete' ));
            }

            /**
             * Adds plugin action links
             *
             * @since 1.0.0
             */
            public function plugin_action_links($links)
            {
                $setting_link = $this->get_setting_link();

                $plugin_links = array(
                    '<a href="' . $setting_link . '">' . __('Settings', 'woocommerce-gateway-xendit') . '</a>',
                    '<a href="https://docs.xendit.co/integrations/woocommerce/">' . __('Docs', 'woocommerce-gateway-xendit') . '</a>',
                    '<a href="https://help.xendit.co/hc/en-us">' . __('Support', 'woocommerce-gateway-xendit') . '</a>',
                );
                return array_merge($plugin_links, $links);
            }

            /**
             * Get setting link.
             *
             * @since 1.0.0
             *
             * @return string Setting link
             */
            public function get_setting_link()
            {
                $use_id_as_section = function_exists('WC') ? version_compare(WC()->version, '2.6', '>=') : false;

                $section_slug = $use_id_as_section ? 'xendit_gateway' : strtolower('WC_Xendit_CC');
                return admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $section_slug);
            }

            /**
             * Add xendit payment methods
             *
             * @param array $methods
             * @return array $methods
             */
            function woocommerce_add_xendit_gateway($methods)
            {
                global $wpdb, $woocommerce;

                $main_settings = get_option( 'woocommerce_xendit_gateway_settings' );

                $currency = get_woocommerce_currency();
                $methods[] = 'WC_Xendit_Invoice';

                $cc_methods;
                if ($this->should_load_addons()) {
                    $cc_methods = 'WC_Xendit_CC_Addons';
                } else {
                    $cc_methods = 'WC_Xendit_CC';
                }

                switch ($currency) {
                    case 'IDR':
                        $methods[] = 'WC_Xendit_BCAVA';
                        $methods[] = 'WC_Xendit_BNI_SYARIAH_VA';
                        $methods[] = 'WC_Xendit_BNIVA';
                        $methods[] = 'WC_Xendit_BRIVA';
                        $methods[] = 'WC_Xendit_MandiriVA';
                        $methods[] = 'WC_Xendit_PermataVA';
                        $methods[] = 'WC_Xendit_Alfamart';
                        $methods[] = 'WC_Xendit_Indomaret';
                        $methods[] = 'WC_Xendit_Shopeepay';
                        $methods[] = 'WC_Xendit_OVO';
                        $methods[] = 'WC_Xendit_DANA';
                        $methods[] = 'WC_Xendit_LINKAJA';
                        $methods[] = 'WC_Xendit_DD_BRI';
                        
                        if (empty($main_settings['on_behalf_of'])) {
                            $methods[] = 'WC_Xendit_QRIS';
                            $methods[] = 'WC_Xendit_Cardless';
                        }

                        $methods[] = $cc_methods;
                        break;
                    case 'PHP':
                        $methods[] = $cc_methods;
                        $methods[] = 'WC_Xendit_DD_BPI';
                        $methods[] = 'WC_Xendit_7Eleven';
                        $methods[] = 'WC_Xendit_Paymaya';
                        $methods[] = 'WC_Xendit_Gcash';
                        $methods[] = 'WC_Xendit_Grabpay';
                        break;
                    case 'USD':
                        $methods[] = $cc_methods;
                        break;
                    default:
                        break;
                }

                return $methods;
            }

            function should_load_addons()
            {
                if (class_exists('WC_Subscriptions_Order') && function_exists('wcs_create_renewal_order')) {
                    return true;
                }

                if (class_exists('WC_Pre_Orders_Order')) {
                    return true;
                }

                return false;
            }

            public function update_status_complete($order_id)
            {
                global $wpdb, $woocommerce;
                $main_settings = get_option( 'woocommerce_xendit_gateway_settings' );

                return $main_settings['success_payment_xendit'];
            }
        }

        $GLOBALS['wc_xendit_pg'] = WC_Xendit_PG::get_instance();
    }

    add_action('woocommerce_api_wc_xendit_callback', 'check_xendit_response');
    function check_xendit_response()
    {
        global $wpdb, $woocommerce;

        if (isset($_REQUEST['xendit_mode'])) {
            try {
                if ($_REQUEST['xendit_mode'] == 'xendit_invoice_callback') {
                    $xendit = new WC_Xendit_Invoice();
                } elseif ($_REQUEST['xendit_mode'] == 'xendit_ewallet_callback') {
                    $xendit = new WC_Xendit_EWallet();
                } elseif ($_REQUEST['xendit_mode'] == 'xendit_qris_callback') {
                    $xendit = new WC_Xendit_QRIS();
                } elseif ($_REQUEST['xendit_mode'] == 'xendit_cardless_callback') {
                    $xendit = new WC_Xendit_Cardless();
                } elseif ($_REQUEST['xendit_mode'] == 'xendit_cc_callback') {
                    $xendit = new WC_Xendit_CC();
                }

                $xendit_status = $xendit->developmentmode == 'yes' ? "[Development]" : "[Production]";

                $script_base = str_replace(array("https://", "http://"), "", home_url());
                $script_base = str_replace($_SERVER['SERVER_NAME'], "", $script_base);
                $script_base = rtrim($script_base, '/');

                $data = file_get_contents("php://input");
                $response = json_decode($data);

                $identifier = ($_REQUEST['xendit_mode'] == 'xendit_qris_callback') ? $response->qr_code->external_id : $response->external_id;

                if (($_SERVER["REQUEST_METHOD"] === "POST")) {
                    $current_callback_token = $_SERVER['HTTP_X_CALLBACK_TOKEN'];

                    if (!isset($current_callback_token)) {
                        if ($identifier) {
                            $exploded_ext_id = explode("-", $identifier);
                            $order_id = end($exploded_ext_id);
                            $order = new WC_Order($order_id);

                            if (!$order->is_paid()) {
                                $xendit->validate_payment($response);
                            } else {
                                WC_Xendit_PG_Logger::log("{$xendit_status} [" . $identifier . "] Order ID $order_id is already updated.");
                                echo 'Order status is already updated';
                                exit;
                            }
                        }
                    } else {
                        WC_Xendit_PG_Logger::log("{$xendit_status} [" . $identifier . "] Callback Request: Callback token no longer supported!");
                        header('HTTP/1.1 501 Invalid Token');
                        echo 'Invalid Token';
                        exit;
                    }
                } else {
                    WC_Xendit_PG_Logger::log("{$xendit_status} [" . $identifier . "] Callback Request: Invalid callback! . $script_base " . $_SERVER["SCRIPT_NAME"]);
                    header('HTTP/1.1 501 Invalid Callback');
                    echo 'Invalid Callback';
                    exit;
                }
            } catch (Exception $e) {
                WC_Xendit_PG_Logger::log("{$xendit_status} [" . $identifier . "] Error in processing callback. " . $e->getMessage());
                header('HTTP/1.1 401');
                echo 'Error in processing callback. ' . $e->getMessage();
                exit;
            }
        }
    }

    /**
     * Keep the old callback during transitioning period
     */
    $callback_modes = array(
        'xendit_invoice_callback', 
        'xendit_ewallet_callback',
        'xendit_qris_callback',
        'xendit_cardless_callback', 
        'xendit_cc_callback'
    );
    if (!isset($_REQUEST['wc-api']) && isset($_REQUEST['xendit_mode']) && in_array($_REQUEST['xendit_mode'], $callback_modes)) {
        add_action('init', 'check_xendit_response');
    }

    function redirect_ewallet() {
        global $wpdb, $woocommerce;

        $order_id = $_REQUEST['order_id'];
        $ewallet_type = $_REQUEST['ewallet_type'];

        $xendit = new WC_Xendit_EWallet();

        return $xendit->redirect_ewallet($order_id, $ewallet_type);
    }

    if (
        (
            isset($_REQUEST['xendit_dana']) ||
            (
                isset($_REQUEST['xendit_ewallet_redirect']) &&
                isset($_REQUEST['ewallet_type'])
            )
        ) &&
        isset($_REQUEST['order_id'])
    ) {
        add_action('init', 'redirect_ewallet');
    }
    
    // register jquery and style on initialization
    add_action('init', 'register_script');
    function register_script()
    {
        wp_register_style('xendit_pg_style', plugins_url('/assets/css/xendit-pg-style.css', __FILE__), false, '1.0.1', 'all');
    }

    // use the registered jquery and style above
    add_action('wp_enqueue_scripts', 'enqueue_style');
    function enqueue_style()
    {
        wp_enqueue_style('xendit_pg_style');
    }

    add_action('admin_enqueue_scripts', 'admin_scripts');
    function admin_scripts($hook)
    {
        if ('post.php' !== $hook) {
            return;
        }
        $xendit = new WC_Xendit_Invoice();
        $public_api_key = $xendit->publishable_key; ?>
        <script>
            var xendit_pub_api_key = '<?=$public_api_key; ?>';
        </script> 
        <?php
        wp_enqueue_script('woo_xendit_pg_admin', plugins_url('assets/js/xendit-admin.js', WC_XENDIT_PG_MAIN_FILE), array(), WC_XENDIT_PG_VERSION, true);
        wp_register_script('sweetalert', 'https://unpkg.com/sweetalert@2.1.2/dist/sweetalert.min.js', null, null, true);
        wp_enqueue_script('sweetalert');
    }

    add_filter('woocommerce_available_payment_gateways', 'show_hide_cc_old_method');
    function show_hide_cc_old_method($gateways) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
       
        //latest PG version contain merged codes
        if (defined('WC_XENDIT_VERSION') && version_compare(WC_XENDIT_VERSION, '1.5.1', '>=') && is_plugin_active(plugin_basename(WC_XENDIT_MAIN_FILE))) {
            
            //check if both CC payment methods are enabled
            if (isset($gateways['xendit']) && isset($gateways['xendit_cc'])) {
                unset($gateways['xendit']);
            }
        }

        return $gateways;
    }

    /**
     * Migrate subscriptions with old payment method "xendit" to the new "xendit_cc" if:
     * - Subscription status is still active
     * - API key is not empty
     * 
     * @return void
     */
    add_action('init', 'migrate_xendit_subscription');
    function migrate_xendit_subscription()
    {
        if (!is_admin()) {
            return;
        }

        if (!function_exists('get_option')) {
            return;
        }

        $should_not_migrate = get_transient('xendit_should_not_migrate_subscription');

        if ($should_not_migrate == true) {
            return;
        }

        $main_settings = get_option( 'woocommerce_xendit_gateway_settings' );
        $testmode = $main_settings['developmentmode'];
        $secret_key = $testmode ? $main_settings['secret_key_dev'] : $main_settings['secret_key'];

        if (! $secret_key) {
            return;
        }

        $query_args = array(
            'post_type'      => 'shop_subscription',
            'posts_per_page' => 100,
            'paged'          => 1,
            'offset'         => 0,
            'order'          => 'DESC',
            'fields'         => 'ids',
            'post_status'    => 'wc-active',
            'meta_query' => array(
                array(
                    'key' => '_payment_method',
                    'value' => 'xendit',
                    'compare' => '=',
                )
            )
        );

        $subscription_post_ids = get_posts($query_args);

        if (empty($subscription_post_ids)) {
            set_transient('xendit_should_not_migrate_subscription', true, 86400); //expire in 24 hours
        }

        foreach ($subscription_post_ids as $post_id) {
            update_post_meta($post_id, '_payment_method', 'xendit_cc');
        }
    }

    add_action('woocommerce_review_order_before_submit', 'add_disclaimer_text', 9);
    function add_disclaimer_text() {
        $chosen_payment_method = WC()->session->get('chosen_payment_method');

        if (strpos($chosen_payment_method, 'xendit') !== false) {
            if (get_woocommerce_currency() === 'IDR') {
                echo '<p>Dengan menggunakan metode pembayaran ini, anda setuju bahwa semua data yang berhubungan dengan order anda akan diproses oleh saluran pembayaran.</p>';
            } else {
                echo '<p>By using this payment method, you agree that all submitted data for your order will be processed by payment processor.</p>';
            }
        }
    }

    add_filter( 'woocommerce_cart_needs_payment', 'filter_cart_needs_payment_callback', 100, 2 );
    function filter_cart_needs_payment_callback( $needs_payment, $cart ) {
        return $cart->total > 0 ? $needs_payment : false;
    }

    add_filter('woocommerce_thankyou_order_received_text', 'woo_redirect_invoice', 10, 2);
    function woo_redirect_invoice( $str, $order ) {
        $order_data = $order->get_data();

        if (empty($_GET['order_id']) || !is_object($order)) {
            return $str;
        }

        if ('processing' === $order->get_status() || 'completed' === $order->get_status() || 'on-hold' === $order->get_status()) {
            return $str;
        }

        $order_id = wc_clean($_GET['order_id']);
        $invoice_url = get_post_meta($order_id, 'Xendit_invoice_url', true);
        $delay = 3;

        if(!empty($invoice_url)) {
        ?>
            <p id="xendit-invoice-countdown"></p>
            <script>
                var timeLeft = <?php echo $delay; ?>;
                var elem = document.getElementById('xendit-invoice-countdown');

                // Load after everything is rendered
                window.addEventListener("load", function() {
                    // Update the count down every 1 second
                    var x = setInterval(function() {
                        if (timeLeft == 0) {
                            clearTimeout(x);
                            var invoiceUrl = "<?php echo $invoice_url; ?>";
                            window.location.replace(invoiceUrl);
                            elem.innerHTML = 'Not redirected automatically? <button id="xendit-invoice-onclick">Pay Now</button>';

                            var button = document.getElementById('xendit-invoice-onclick');

                            button.onclick = function () {
                                location.href = invoiceUrl;
                            }
                        } else {
                            elem.innerHTML = 'Thank you for placing the order, you will be redirected in ' + timeLeft;
                            timeLeft--;
                        }
                    }, 1000);
                });
            </script>

            <style>
            #xendit-invoice-countdown {
                font-size: 24px;
                text-align: center;
            }

            #xendit-invoice-onclick {
                background: #4481F1;
                border-radius: 10px;
                color: #FFFFFF;
                line-height: 28px;
                margin-left: 16px;
            }
            </style>
        <?php
        }

        return $str;
    }
}

<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles orders for redirect flow.
 *
 * @since 1.3.0
 */
class WC_Xendit_CC_Redirect_Handler extends WC_Xendit_CC
{
    private static $_this;

    /**
     * Constructor.
     *
     * @since 1.3.0
     * @version 1.3.0
     */
    public function __construct()
    {
        parent::__construct();

        add_action('wp', array($this, 'maybe_process_redirect_order'));
    }

    /**
     * Processses the orders that are redirected.
     *
     * @since 1.3.0
     * @version 1.3.0
     */
    public function maybe_process_redirect_order()
    {
        if (!is_order_received_page() || empty($_GET['hosted_3ds_id'])) {
            return;
        }

        $order_id = wc_clean($_GET['order_id']);

        $this->process_redirect_payment($order_id);
    }

    /**
     * Processes payments.
     * Note at this time the original source has already been
     * saved to a customer card (if applicable) from process_payment.
     *
     * @since 1.3.0
     * @param int $order_id
     */
    public function process_redirect_payment($order_id)
    {
        global $woocommerce;

        try {
            $hosted_3ds_id  = wc_clean($_GET['hosted_3ds_id']);
            if (empty($hosted_3ds_id)) {
                return;
            }

            if (empty($order_id)) {
                return;
            }

            $order = wc_get_order($order_id);

            if (!is_object($order)) {
                return;
            }

            if ('processing' === $order->get_status() || 'completed' === $order->get_status() || 'on-hold' === $order->get_status()) {
                return;
            }
            $this->get_is_changing_order_status($order_id);

            $hosted_3ds = $this->xenditClass->getHostedThreeDS($hosted_3ds_id);
            $hosted_3ds_status   = $hosted_3ds['status'];

            if ('VERIFIED' !== $hosted_3ds_status) {
                throw new Exception(__('Authentication process failed. Please try again. Code: 200039', 'woocommerce-gateway-xendit'));
            }

            $this->complete_cc_payment($order, $hosted_3ds['charge_id'], 'CAPTURED', $hosted_3ds['amount']);

            $this->xenditClass->trackEvent(array(
                'reference' => 'charge_id',
                'reference_id' => $hosted_3ds['charge_id'],
                'event' => 'ORDER_UPDATED_AT.REDIRECT'
            ));
        } catch (Exception $e) {
            $this->get_is_changing_order_status($order_id, false);
            $order->update_status('failed', sprintf(__('Payment failed: %s', 'woocommerce-gateway-xendit'), $e->getMessage()));

            // log error metrics
            $metrics = $this->xenditClass->constructMetricPayload('woocommerce_checkout', 'error', $this->method_code);
            $this->xenditClass->trackMetricCount($metrics);

            wc_add_notice($e->getMessage(), 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }
    }
}

new WC_Xendit_CC_Redirect_Handler();

<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Xendit Email
 *
 * @since 2.11.3
 */

class WC_Xendit_Email {
    public static function send_confirmation_email($order_id, $response)
    {
        global $wpdb, $woocommerce;

        $order = new WC_Order($order_id);
        $mailer = $woocommerce->mailer();

        $mail_body = '';

        $banks = array();
        foreach ($response['available_banks'] as $available_banks) {
            $banks[] = $available_banks;
        }

        $retails = array();
        foreach ($response['available_retail_outlets'] as $available_retail) {
            $retails[] = $available_retail;
        }

        $open_payment = count($banks) + count($retails);

        $mail_body .= '<table class="shop_table order_details">
            <tbody>
                <tr>
                    <td colspan="2">
                        <div style="text-align:left;">
                        ' . sprintf(__('Your order #%s has been created and waiting for payment', 'xendit'), $order->get_order_number()) . '<br />
                        <strong>' . sprintf(__('Please pay your invoice to %s this Bank Account / Retail Outlets:', 'xendit'), $open_payment > 1 ? 'one of' : '') . '</strong>
                        </div>
                    </td>
                </tr>';

        if (count($banks) > 0) {
            $mail_body .= '
            <tr>
                <td width="50%">
                    <strong>BANK ACCOUNT</strong>
                </td>
            </tr>';
        }

        foreach ($banks as $bank) {
            $mail_body .= '
                <tr>
                    <td width="50%">
                        <div style="text-align:left;">
                        <img src="' . plugins_url('assets/images/' . strtolower($bank['bank_code']) . '.png', WC_XENDIT_PG_MAIN_FILE) . '" style="max-width:180px;width:100%;" class="img-responsive">
                        </div>
                        <div style="text-align:left;">
                        ' . sprintf(__('Bank Name: <strong>%s</strong>', 'xendit'), $bank['bank_code']) . '<br />
                        ' . sprintf(__('Account Number: <strong>%s</strong>', 'xendit'), $bank['bank_account_number']) . '<br />
                        ' . sprintf(__('Account Holder: <strong>%s</strong>', 'xendit'), $bank['account_holder_name']) . '<br />
                        ' . sprintf(__('Bank Branch: <strong>%s</strong>', 'xendit'), $bank['bank_branch']) . '<br />
                        ' . sprintf(__('Unique Amount: <strong>%s</strong>', 'xendit'), wc_price($bank['transfer_amount'])) . '<br />
                        </div>
                    </td>
                </tr>';
        }

        if (count($retails) > 0) {
            $mail_body .= '
            <tr>
                <td width="50%">
                    <strong>RETAIL OUTLETS</strong>
                </td>
            </tr>';
        }
        foreach ($retails as $retail) {
            $mail_body .= '
                <tr>
                    <td width="50%">
                        <div style="text-align:left;">
                        <img src="' . plugins_url('assets/images/' . strtolower($retail['retail_outlet_name']) . '.png', WC_XENDIT_PG_MAIN_FILE) . '" style="max-width:180px;width:100%;" class="img-responsive">
                        </div>
                        <div style="text-align:left;">
                        ' . sprintf(__('Retail Outlet Name: <strong>%s</strong>', 'xendit'), $retail['retail_outlet_name']) . '<br />
                        ' . sprintf(__('Payment Code: <strong>%s</strong>', 'xendit'), $retail['payment_code']) . '<br />
                        ' . sprintf(__('Unique Amount: <strong>%s</strong>', 'xendit'), wc_price($retail['transfer_amount'])) . '<br />
                        </div>
                    </td>
                </tr>';
        }

        $mail_body .= '
                <tr>
                    <td colspan="2">
                        <div style="text-align:left;">
                            <strong>' . sprintf(__('NOTE: Please pay this before %s', 'xendit'), date("Y-m-d H:i:s", strtotime($response['expiry_date']))) . '</strong>
                        </div>
                        <div style="text-align:left;">
                            <strong><a class="button cancel" href="' . $order->get_view_order_url() . '">' . __('View my order', 'xendit') . '</a></strong>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>';

        $message = $mailer->wrap_message(__('Order confirmation', 'xendit'), $mail_body);
        $sended = $mailer->send($order->get_billing_email(), sprintf(__('Order #%s has been created', 'xendit'), $order->get_order_number()), $message);

        return $sended;
    }
}
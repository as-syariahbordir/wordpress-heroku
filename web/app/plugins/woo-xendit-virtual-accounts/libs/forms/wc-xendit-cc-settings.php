<?php
if (! defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'wc_xendit_cc_settings',
    array(
        'channel_name' => array(
            'title'       => __('Payment Channel Name', 'woocommerce-gateway-xendit'),
            'type'        => 'text',
            'description' => __('Your payment channel name will be changed into <strong><span class="channel-name-format"></span></strong>', 'woocommerce-gateway-xendit'),
            'placeholder' => 'Credit Card (Xendit)',
        ),
        'payment_description' => array(
            'title'       => __('Payment Description', 'woocommerce-gateway-xendit'),
            'type'        => 'textarea',
            'css'         => 'width: 400px;',
            'description' => __('Change your payment description for <strong><span class="channel-name-format"></span></strong>', 'woocommerce-gateway-xendit'),
            'placeholder' => 'Pay with your credit card via Xendit',
        ),
        'statement_descriptor' => array(
            'title'       => __('Statement Descriptor', 'woocommerce-gateway-xendit'),
            'type'        => 'text',
            'description' => __('Extra information about a charge. This will appear on your customer’s credit card statement.', 'woocommerce-gateway-xendit'),
            'default'     => '',
            'desc_tip'    => true,
        ),
    )
);

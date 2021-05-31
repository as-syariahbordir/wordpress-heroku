<?php
if (! defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'wc_xendit_kredivo_settings',
    array(
        'kredivo_title' => array(
            'title' => __('Kredivo', 'woocommerce-gateway-xendit'),
            'type' => 'title',
            'description' => __('Allow customers to buy now, pay later using their Kredivo account.<br>Important: Please email merchantops@xendit.co to activate Kredivo payment channel for your Xendit account before going live to avoid errors during checkout.', 'woocommerce-gateway-xendit'),
        ),
        'channel_name' => array(
            'title' => __('Payment Channel Name', 'woocommerce-gateway-xendit'),
            'type' => 'text',
            'description' => __('Your payment channel name will be changed into <strong><span class="channel-name-format"></span></strong>', 'woocommerce-gateway-xendit'),
            'placeholder' => 'Kredivo',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'woocommerce-gateway-xendit'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for <strong><span class="channel-name-format"></span></strong>', 'woocommerce-gateway-xendit'),
            'placeholder' => 'Bayar pesanan dengan Kredivo anda melalui Xendit',
        ),
        'verification_token' => array(
            'title' => __('Verification Token', 'woocommerce-gateway-xendit'),
            'type' => 'password',
            'description' => __('Your payment Verification Token. Get your own <a href="https://dashboard.xendit.co/settings/developers#callbacks" target="_blank">Verification Token</a>', 'woocommerce-gateway-xendit'),
            'placeholder' => 'Verification Token',
        )
    )
);

<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Xendit_DANA extends WC_Xendit_EWallet {
    public function __construct() {
        parent::__construct();

        $this->id = 'xendit_dana';

        // Load the form fields.
        $this->init_form_fields();
        
        // Load the settings.
        $this->init_settings();

        $this->method_code          = 'DANA';
        $this->title                = !empty($this->get_option('channel_name')) ? $this->get_option('channel_name') : $this->method_code;
        $this->default_description  = 'Bayar pesanan dengan akun DANA anda melalui <strong>Xendit</strong>';
        $this->description          = !empty($this->get_option('payment_description')) ? nl2br($this->get_option('payment_description')) : $this->default_description;
        $this->method_title         = __( 'Xendit DANA', 'woocommerce-gateway-xendit' );
        $this->method_description   = sprintf( __( 'Collect payment from DANA account on checkout page and get the report realtime on your Xendit Dashboard. <a href="%1$s" target="_blank">Sign In</a> or <a href="%2$s" target="_blank">sign up</a> on Xendit and integrate with <a href="%3$s" target="_blank">your Xendit keys</a>.', 'woocommerce-gateway-xendit' ), 'https://dashboard.xendit.co/auth/login', 'https://dashboard.xendit.co/register', 'https://dashboard.xendit.co/settings/developers#api-keys' );

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
		$this->form_fields = require( WC_XENDIT_PG_PLUGIN_PATH . '/libs/forms/wc-xendit-ewallet-dana-settings.php' );
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
}
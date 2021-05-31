<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Xendit_LINKAJA extends WC_Xendit_EWallet {
    public function __construct() {
        parent::__construct();

        $this->id = 'xendit_linkaja';

        // Load the form fields.
        $this->init_form_fields();
        
		// Load the settings.
        $this->init_settings();
        
        $this->method_code          = 'LINKAJA';
        $this->title                = !empty($this->get_option('channel_name')) ? $this->get_option('channel_name') : $this->method_code;
        $this->default_description  = 'Bayar pesanan dengan akun LINKAJA anda melalui <strong>Xendit</strong>';
        $this->description          = !empty($this->get_option('payment_description')) ? nl2br($this->get_option('payment_description')) : $this->default_description;
        $this->method_title         = __( 'Xendit LINKAJA', 'woocommerce-gateway-xendit' );
        $this->method_description   = sprintf( __( 'Collect payment from LINKAJA account on checkout page and get the report realtime on your Xendit Dashboard. <a href="%1$s" target="_blank">Sign In</a> or <a href="%2$s" target="_blank">sign up</a> on Xendit and integrate with <a href="%3$s" target="_blank">your Xendit keys</a>.', 'woocommerce-gateway-xendit' ), 'https://dashboard.xendit.co/auth/login', 'https://dashboard.xendit.co/register', 'https://dashboard.xendit.co/settings/developers#api-keys' );

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
		$this->form_fields = require( WC_XENDIT_PG_PLUGIN_PATH . '/libs/forms/wc-xendit-ewallet-linkaja-settings.php' );
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

    public function payment_fields() {
        if ( $this->description ) {
            $test_description = '';
            if ( $this->developmentmode == 'yes' ) {
                $test_description = ' <strong>TEST MODE</strong> - Real payment will not be detected';
            }

            echo '<p>' . $this->description . '</p>
                <p style="color: red; font-size:80%; margin-top:10px;">' . $test_description . '</p>';
        }

        echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

        do_action( 'woocommerce_credit_card_form_start', $this->id );

        // I recommend to use unique IDs, because other gateways could already use #ccNo, #expdate, #cvc
        echo '<div class="form-row form-row-wide">
                <input id="'. $this->id .'_phone" name="'. $this->id .'_phone" type="text" autocomplete="off" placeholder="Phone Number">
            </div>
            <div class="clear"></div>';

        do_action( 'woocommerce_credit_card_form_end', $this->id );

        echo '<div class="clear"></div></fieldset>';
    }

    public function validate_fields(){
        if(empty($_POST[$this->id . '_phone'])) {
            wc_add_notice('<strong>' . $this->method_code . ' phone number</strong> is required! Code: 400016', 'error');
            return false;
        }
        return true;
    }

    public function get_icon() {
        $style = version_compare( WC()->version, '2.6', '>=' ) ? 'style="margin-left: 0.3em; margin-top: 0.3em; max-width: 48px;"' : '';
        $file_name = strtolower( $this->method_code ) . '.png';
        $icon = '<img src="' . plugins_url('assets/images/' . $file_name , WC_XENDIT_PG_MAIN_FILE) . '" alt="Xendit" ' . $style . ' />';

        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
    }
}
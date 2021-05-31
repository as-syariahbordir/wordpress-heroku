<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       http://jogja.camp
 * @since      1.0.0
 *
 * @package    Ongkoskirim_Id
 * @subpackage Ongkoskirim_Id/public/partials
 */


class Ongkoskirim_Id_Shipping_Method extends WC_Shipping_Method {
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->id                 = 'ongkoskirim-id';
        $this->method_title       = __( 'OngkosKirim.id', 'ongkoskirim-id' );
        $this->method_description = __( 'Ongkos Kirim untuk ekspedisi Indonesia (JNE, Tiki, Wahana, dll)', 'ongkoskirim-id' );

        // Availability & Countries
        $this->availability = 'including';
        $this->countries = array('ID');

        $this->init();

        $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
        $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'OngkosKirim.id Shipping', 'ongkoskirim-id' );

        $this->wc_assets_path	= str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';

        $this->lib	= new Ongkoskirim_Id_Library();

        $this->shipping_company_pro		= array(3,5,34);
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init() {

        if(!isset($this->wc_assets_path))
            $this->wc_assets_path	= str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';

        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        // Save settings in admin if you have any defined
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );



        $select2_css_path = $this->wc_assets_path . 'css/select2.css';
        if( is_cart() && !wp_style_is( 'select2' )){
            wp_enqueue_style( 'select2', $select2_css_path);
        }

        $select2_js_path = $this->wc_assets_path . 'js/select2/select2.js';
        if( is_cart() && !wp_script_is( 'select2' ) ){
            wp_enqueue_script( 'select2', $select2_js_path, array( 'jquery' ));
        }
    }

    /**
     * Define settings field for this shipping
     * @return void
     */
    function init_form_fields() {

        $this->form_fields = array(

            'enabled' => array(
                'title' => __( 'Enable', 'ongkoskirim-id' ),
                'type' => 'checkbox',
                'description' => __( 'Aktifkan plugin pengiriman ini', 'ongkoskirim-id' ),
                'default' => 'yes'
            )
        );

    }

    function admin_options() {
        $this->save_settings_form();

        //$this->lib->clear_cache();

        $company	    = $this->lib->remote_get_shipping_company();
        //$cities         = $this->lib->remote_get_cities();
        //$districts      = $this->lib->remote_get_districts(3);
        $cities_from    = $this->lib->remote_get_cities_from();
        //echo "<pre>";
        //print_r( $cities_from );
        //echo "</pre>";


        /*
        $license = 'asdf';
        $this->license	= new Ongkoskirim_Id_License();
        $response	= $this->license->activate($license);
        echo "<pre>";
        print_r( $response );
        echo "</pre>";
        die();
        */

        /*
        echo "<pre>";
        echo "<h1>endpoint: POST /shipping_company</h1>";
        //print_r( $company );
        echo "<h1>endpoint: POST /cities</h1>";
        //print_r( $cities );
        //echo "<h1>endpoint: POST /districts</h1>";
        //print_r( $districts );
        echo "<h1>endpoint: POST /cities_from</h1>";
        //print_r( $cities_from );
        echo "</pre>";
        //die();
        */
        $options    = $this->lib->get_options();
        ?>
            <?php require dirname(__DIR__).'/admin/partials/ongkoskirim-id-admin-settings-page.php'; ?>
        <?php
    }

    /**
     * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package = array() ) {
        $district_id    = $this->get_checkout_district_id();
        $city_id    = $this->get_checkout_city_id();

        if( empty($district_id) || empty($city_id) )
            return false;

        $districts	        = $this->lib->remote_get_districts($city_id);
        $shipping_company   = $this->lib->remote_get_shipping_company();


        $weight = $this->lib->get_cart_weight($package['contents']);
        $prices = $districts[$district_id]['price'];

        $added_cost = ( $this->lib->get_option("is_added_cost_enable") == "1") ? $this->lib->get_option("added_cost") : 0;

        foreach($prices as $shipping_id=>$price){
            $cost   = ($price*$weight)+$added_cost;
            $cost   = $this->lib->convert_currency($cost);
            $rate = array(
                'id' => "ongkoskirim_id_".$shipping_id,
                'label' => $shipping_company[$shipping_id],
                'cost' => $cost
            );
            $this->add_rate( $rate );
        }

    }

    private function get_checkout_district_id() {
        if(isset($_POST['post_data'])) { // checkout
            if ($this->get_checkout_data('ship_to_different_address') === '1') {
                $district_id = $this->get_checkout_data('shipping_district');
            } else {
                $district_id = $this->get_checkout_data('billing_district');
            }
        } else { // after checkout
            if (@$_POST['shipping_district']) {
                $district_id = htmlspecialchars(@$_POST['shipping_district']);
            } else {
                $district_id = htmlspecialchars(@$_POST['billing_district']);
            }
        }
        return $district_id;
    }

    private function get_checkout_city_id() {
        if(isset($_POST['post_data'])) { // checkout
            if ($this->get_checkout_data('ship_to_different_address') === '1') {
                $city_id = $this->get_checkout_data('shipping_city');
            } else {
                $city_id = $this->get_checkout_data('billing_city');
            }
        } else { // after checkout
            if (@$_POST['shipping_district']) {
                $city_id = htmlspecialchars(@$_POST['shipping_city']);
            } else {
                $city_id = htmlspecialchars(@$_POST['billing_city']);
            }
        }


        return $city_id;
    }

    private function get_checkout_data($items) {
        $post = explode('&',@$_POST['post_data']);
        $return = '';

        foreach ($post as $value) {
            if (strpos($value,$items) !== FALSE) {
                $return = $value;
                $ar = explode('=',$return);
                $return = $ar[1];
                break;
            }
        }

        $return = str_replace('+',' ',$return);
        return $return;
    }

    public function save_settings_form(){
        if (wp_verify_nonce( @$_REQUEST['ongkoskirim-id-nonce'], 'ongkoskirim-id-settings' )) {
            $_POST['shipping_company_enabled']  = array();
            if( isset($_POST['shipping_company_enabledbox'])){
                foreach($_POST['shipping_company_enabledbox'] as $company_id=>$v){
                    $_POST['shipping_company_enabled'][]    = $company_id;
                }
            }
            // check shipping company & store_location berubah tidak
            $clear_cache = false;
            if( $_POST['store_city_id'] != $this->lib->options['store_city_id'])
                $clear_cache = true;

            $company_changed = array_diff($_POST['shipping_company_enabled'],$this->lib->options['shipping_company_enabled']);
            if( !empty( $company_changed ) )
                $clear_cache = true;

            $this->lib->save_options($_POST);

            if( $clear_cache ){
                $this->lib->clear_cache();
            }

        }
        else if (wp_verify_nonce( @$_REQUEST['ongkoskirim-id-nonce'], 'ongkoskirim-id-remove-cache' )) {
            $this->lib->clear_cache();
        }
        else if (wp_verify_nonce( @$_REQUEST['ongkoskirim-id-nonce'], 'ongkoskirim-id-reset-options' )) {
            $this->lib->clear_cache();
            $this->lib->remove_options();
            $this->lib->add_options(true);
        }

    } // function save_settings_form

} //class Ongkoskirim_Id_Shipping_Method


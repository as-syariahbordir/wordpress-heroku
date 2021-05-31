<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://jogja.camp
 * @since      1.0.0
 *
 * @package    Ongkoskirim_Id
 * @subpackage Ongkoskirim_Id/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ongkoskirim_Id
 * @subpackage Ongkoskirim_Id/public
 * @author     jogja.camp <ongkoskirimid@jogjacamp.co.id>
 */
class Ongkoskirim_Id_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->cache_age	= 60*60*24*7;

		$this->debug=1;
		$this->lib	= new Ongkoskirim_Id_Library();

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ongkoskirim_Id_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ongkoskirim_Id_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if( is_checkout() || is_account_page() )
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ongkoskirim-id-public.css', array(), $this->version, 'all' );



	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ongkoskirim_Id_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ongkoskirim_Id_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if( is_checkout() || is_account_page() ){
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ongkoskirim-id-public.js', array( 'jquery' ), $this->version, true );
			$this->localize();
		}
	}


	public function add_ongkoskirim_id_shipping_method( $methods ) {
		$methods[] = 'Ongkoskirim_Id_Shipping_Method';
		return $methods;
	}

	public function ongkoskirim_id_shipping_method()
	{
		if (!class_exists('Ongkoskirim_Id_Shipping_Method')) {
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ongkoskirim-id-shipping-method.php';
		}
	}

	public function ongkoskirim_id_custom_override_checkout_fields( $fields ) {
		$fields['billing'] 	= $this->ongkoskirim_id_custom_override_billing_fields( $fields['billing'] );
		$fields['shipping'] = $this->ongkoskirim_id_custom_override_shipping_fields( $fields['shipping'] );
		return $fields;
	}

	public function ongkoskirim_id_custom_override_fields_type( $fields, $type="billing" ) {

		$array_order = array($type."_first_name",$type."_last_name",$type."_company",
			$type."_country",$type."_state",$type."_city",$type."_district",$type."_address_1",$type."_address_2",
			$type."_postcode");

		if( $type == "billing"){
			$array_order[]	= $type."_phone";
			$array_order[]	= $type."_email";
		}

		if(($key = array_search("update_totals_on_change", $fields[$type.'_country']['class'])) !== false) {
			unset($fields[$type.'_country']['class'][$key]);
		}
		$fields[$type.'_state']['placeholder']	= __( 'Pilih Provinsi', 'ongkoskirim-id' );
		$fields[$type.'_state']['label']	= __( 'Provinsi', 'ongkoskirim-id' );

		$fields[$type.'_city']		= $this->get_field_city();
		$fields[$type.'_district']		= $this->get_field_district();

		$fields	= $this->lib->reorder_field_priority($fields, $array_order);

		return $fields;
	}

	public function ongkoskirim_id_custom_override_billing_fields( $fields ) {
		return $this->ongkoskirim_id_custom_override_fields_type( $fields, "billing" );
	}

	public function ongkoskirim_id_custom_override_shipping_fields( $fields ) {
		return $this->ongkoskirim_id_custom_override_fields_type( $fields, "shipping" );
	}

	public function ongkoskirim_id_custom_shipping_calculator_postcode(){
		return false;
	}

	function flush_shipping_cache(){
		$packages = WC()->cart->get_shipping_packages();
		foreach ($packages as $key => $value) {
			$shipping_session = "shipping_for_package_$key";
			unset(WC()->session->{$shipping_session});
		}
	}

	private function get_field_city(){
		$field_kota = array(
			'type' => 'select',
			//'class' => array( 'form-row-wide',"update_totals_on_change" ),
			'options' => array('' => ''),
			'required' => TRUE,
			'placeholder' => __( 'Pilih Kota / Kabupaten', 'ongkoskirim-id' ),
			'label' => __( 'Kota / Kabupaten', 'ongkoskirim-id' )
		);

		return $field_kota;
	}

	private function get_field_district(){
		$field_kota = array(
			'type' => 'select',
			'class' => array( 'form-row-wide', "address-field", "update_totals_on_change" ),
			'options' => array('' => ''),
			'required' => TRUE,
			'placeholder' => __( 'Pilih Kecamatan', 'ongkoskirim-id' ),
			'label' => __( 'Kecamatan', 'ongkoskirim-id' )
		);

		return $field_kota;
	}


	private function localize(){
		$cities	= $this->lib->remote_get_cities();

		// existing customer
		$customer	= array();
		$customer['logged_in']	= 0;
		if (is_user_logged_in()) {
			$customer['logged_in']	= 1;
			$customer['billing_country']	= get_user_meta(get_current_user_id(), 'billing_country', true);
			$customer['billing_state']		= get_user_meta(get_current_user_id(), 'billing_state', true);
			$customer['billing_city']		= get_user_meta(get_current_user_id(), 'billing_city', true);
			$customer['billing_district']	= get_user_meta(get_current_user_id(), 'billing_district', true);
			$customer['shipping_country']	= get_user_meta(get_current_user_id(), 'shipping_country', true);
			$customer['shipping_state']		= get_user_meta(get_current_user_id(), 'shipping_state', true);
			$customer['shipping_city']		= get_user_meta(get_current_user_id(), 'shipping_city', true);
			$customer['shipping_district']	= get_user_meta(get_current_user_id(), 'shipping_district', true);
		}

		$lang	= array();
		$lang['kecamatan']	= __("Kecamatan");
		$lang['pilih_kecamatan']	= __("Pilih Kecamatan");
		$lang['pilih_kota']	= __("Pilih Kota");
		$lang['loading']	= __("Loading");
		
		wp_localize_script( $this->plugin_name, 'ongkoskirim_id',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'cities' => $cities,
				'customer' => $customer)
		);
	}

	// ajax
	public function get_cities(){

		$cities	= $this->lib->remote_get_cities();
		echo json_encode($cities); 
		wp_die();
	}

	// ajax
	public function get_districts(){
		$city_id	= intval($_POST['city_id']);
		$districts	= $this->lib->remote_get_districts($city_id);
		if($districts){
			$districts_title	= array();
			foreach($districts as $district_id=>$d)
			{
				$districts_title[$district_id] = $d['title'];
			}

			echo json_encode($districts_title);
		}

		wp_die();
	}

	function ongkoskirim_id_checkout_update_order_meta($order_id){
		global $woocommerce,$custom_meta;

		$order = new WC_Order( $order_id );
		$user_id = version_compare( $woocommerce->version, '3.0', '>=' ) ? $order->get_user_id() : $order->user_id;

		$custom_meta	= array();

		$this->update_order_meta("billing", $user_id, $order_id);

		if( isset($_POST['ship_to_different_address']) && $_POST['ship_to_different_address']=='1')
			$this->update_order_meta("shipping", $user_id, $order_id);
		else{
			if( $custom_meta['city'] )
				update_post_meta($order_id, '_shipping_city', $custom_meta['city']);
			if( isset($custom_meta['district']) &&  !empty($custom_meta['district'])){
				$address2	= (empty($_POST['shipping_address_2'])) ? $custom_meta['district'] : $_POST['shipping_address_2'] . ", ". $custom_meta['district'];
				update_post_meta($order_id, '_shipping_address_2', sanitize_text_field($address2));
			}
		}

		update_user_meta($user_id, 'last_shipping', $custom_meta);
	}


	/*
	 * $type = billing/shipping
	 */
	public function update_order_meta($type, $user_id, $order_id){
		global $custom_meta;
		if($_POST[$type.'_country'] == 'ID'){
			if ( ! empty( $_POST[$type.'_state'] ) ) {
				update_user_meta($user_id, $type.'_state', sanitize_text_field( $_POST[$type.'_state']));
			}
			if ( ! empty( $_POST[$type.'_city'] ) ) {
				$custom_meta['city_id']	= intval($_POST[$type.'_city']);
				$custom_meta['city']		= sanitize_text_field( $this->lib->getCityById($custom_meta['city_id']) );

				update_user_meta($user_id, $type.'_city', sanitize_text_field( $_POST[$type.'_city']));
				update_post_meta($order_id, '_'.$type.'_city', $custom_meta['city']);
			}

			if ( ! empty( $_POST[$type.'_district'] ) ) {
				$custom_meta['district_id']	= intval($_POST[$type.'_district']);
				$district	= $this->lib->getDistrictById($custom_meta['city_id'], $custom_meta['district_id']);
				$custom_meta['district']		= sanitize_text_field( $district['title'] );

				update_user_meta($user_id, $type.'_district', sanitize_text_field( $_POST[$type.'_district']));


				$address2	= (empty($_POST[$type.'_address_2'])) ? $custom_meta['district'] : $_POST[$type.'_address_2'] . ", ". $custom_meta['district'];
				update_post_meta($order_id, '_'.$type.'_address_2', sanitize_text_field($address2));
			}
		}else{
			$custom_meta['city']	= $_POST[$type.'_city_text'];
			update_post_meta($order_id, '_'.$type.'_city', sanitize_text_field($_POST[$type.'_city_text']));
			update_user_meta($user_id, $type.'_city', sanitize_text_field( $_POST[$type.'_city_text']));
		}


		return $custom_meta;
	}

	public function ongkoskirim_id_custom_my_account_my_address_formatted_address( $fields, $customer_id, $name ){

		if( !$fields['city'])
			return false;

		if( $fields['country']!="ID"){
			return $fields;
		}

		$city_id		= $fields['city'];
		$fields['city']	= $this->lib->getCityById($fields['city']);

		$district_id	= get_user_meta( $customer_id, $name.'_district', true );

		if( !$district_id){
			return $fields;
		}

		$district		= $this->lib->getDistrictById($city_id, $district_id);
		$district_title	= $district['title'];

		$fields['address_2']	= (empty($fields['address_2'])) ? $district_title :  $fields['address_2'] . ", ".$district_title;

		return $fields;
	}


	public function ongkoskirim_id_custom_woocommerce_process_myaccount_field_billing_city($value){

		if( $_POST['billing_country'] != 'ID')
			$value	= $_POST['billing_city_text'];


		return $value;
	}

	public function ongkoskirim_id_custom_woocommerce_process_myaccount_field_shipping_city($value){
		if( $_POST['shipping_country'] != 'ID')
			$value	= $_POST['shipping_city_text'];
		return $value;
	}


	// kode unik
	public function ongkoskirim_id_custom_woocommerce_cart_calculate_fees(){
		global $woocommerce;
		
		if( !$this->lib->get_option("is_unique_code") )
			return false;

		$digit	= $this->lib->get_option("unique_code_length");
		$woocommerce->cart->add_fee( __('Kode Unik', "ongkoskirim-id"), $this->lib->get_random($digit) );
	}

	public function show_weight_checkout() {
		if ($this->lib->get_option("is_show_weight") != "1")
			return false;

		global $woocommerce;

		$packages	= $woocommerce->cart->get_cart();
		if ( sizeof( $packages ) > 0 ) {
			$weight = $this->lib->get_cart_weight($packages);
		}
		?>

		<tr>
			<td class="product-name">
				<?php echo __('Berat Pengiriman', "ongkoskirim-id")?>
			</td>
			<td class="product-total">
				<?php echo $weight ?> Kg
			</td>
		</tr>
		<?php
	}

	public function ongkoskirim_id_load_language(){
		load_plugin_textdomain("ongkoskirim-id", false, dirname(plugin_basename(__FILE__)) . '/languages');
	}
}
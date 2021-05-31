<?php


class Ongkoskirim_Id_Library {
	function __construct(){
		$this->domain			= "ongkoskirim.id";
		$this->remote_base_url	= "http://api.".$this->domain."/v1/shippings/";
		$this->options_key		= array("version_type","shipping_company_enabled","store_city_id","is_show_weight","is_unique_code","unique_code_length","is_added_cost_enable","added_cost", "default_weight", "weight_tolerance", "license_key", "license_status");
		$this->option_prefix	= "ongkoskirim_id_";
		$this->options			= $this->get_options();
		$this->debug=0;
		$this->timeout=30;

		$this->cache_age	= 60*60*24*7;

	}

	/********************
	 *
	 *  REMOTE API
	 *
	 ********************/

	function getCityById($city_id){
		$cities	= $this->remote_get_cities();
		return $cities['city_id'][$city_id];
	}

	function getDistrictById($city_id, $district_id){
		$districts	= $this->remote_get_districts($city_id);

		return $districts[$district_id];
	}

	function remote_get_cities(){
		$endpoint	= "cities";

		return $this->remote_get($endpoint);
	}

	function remote_get_cities_from(){
		$endpoint	= "cities_from";

		return $this->remote_get($endpoint);
	}

	function remote_get_shipping_company(){
		$endpoint	= "shipping_company";

		return $this->remote_get($endpoint);
	}

	function remote_get_districts($city_id, $from_city_id=""){
		$endpoint	= "districts";
		if( empty($from_city_id))
			$from_city_id	= $this->options['store_city_id'];
		$post = array("city_id"=>$city_id, "from_city_id"=>$from_city_id, "comp_id"=>json_encode($this->options['shipping_company_enabled']));

		return $this->remote_get($endpoint, $post, $city_id);
	}

	function remote_get_currency(){
		$endpoint	= "currency";

		$data = $this->remote_get_plain($endpoint);
		if( !isset($data['rates']) )
			return false;

		return $data['rates'];
	}

	function remote_get($endpoint, $post=array(), $transient_addon=""){

		$transient_name	= $this->option_prefix.$endpoint;
		$transient_name	= (empty($transient_addon)) ?  $transient_name : $transient_name."-".$transient_addon;

		//echo 'transient_name:'.$transient_name;
		//echo "<hr>";

		if ( false === ( $data = get_transient( $transient_name ) ) ) {

			$post['license_key']	= $this->options['license_key'];
			$post['license_status']	= $this->options['license_status'];

			$url      = $this->remote_base_url. $endpoint ."";
			//echo "<hr>remote:".$url;
			$response	= $this->do_remote_get($url, $post);
			if( $response ){
				if(isset($response['license_status'])){
					if( $response['license_status'] != 'active' && !empty($response['license_status']))
						$this->update_license($response['license_status']);
				}
				$data	= $response['body'];
				set_transient( $transient_name, $data, $this->cache_age );
			}
			else
				return false;
		}
		return $data;
	}

	function remote_get_plain($endpoint, $transient_addon=""){

		$transient_name	= $this->option_prefix.$endpoint;
		$transient_name	= (empty($transient_addon)) ?  $transient_name : $transient_name."-".$transient_addon;

		if( $endpoint == 'currency'){
			$url	= 'http://api.fixer.io/latest?base=IDR';
		}

		if ( false === ( $data = get_transient( $transient_name ) ) ) {
			$response = wp_remote_get( esc_url_raw( $url ) );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$data	= json_decode( wp_remote_retrieve_body( $response ), true );
			set_transient( $transient_name, $data, 60*60*12 );
		}

		return $data;
	}

	function do_remote_get($url, $post=array()){

		if( $this->debug ){
			echo "REMOTE GET : ".$url;
			echo "<pre>";
			print_r( $post );
			echo "</pre>";
		}

		$post = array(
			'method' => 'POST',
			'body' => $post
		);
		$response = wp_remote_post( esc_url_raw( $url ), $post );

		if( $this->debug ){
			echo "<pre>";
			print_r( $response );
			echo "</pre>";
		}

		if ( is_wp_error( $response ) ) {
			// coba lagi menggunakan timeout yg lebih tinggi
			$post['timeout'] = $this->timeout;
			// if( $this->debug ){
			// 	echo "REMOTE GET : ".$url;
			// 	echo "<pre>";
			// 	print_r( $post );
			// 	echo "</pre>";
			// }

			$response = wp_remote_post( esc_url_raw( $url ), $post );

			if( $this->debug ){
				echo "try again with timeout value : ".$this->timeout;
				echo "<pre>";
				print_r( $response );
				echo "</pre>";
			}
			if ( is_wp_error( $response ) ) {
				return false;
			} else {
				$response['body']	= json_decode( wp_remote_retrieve_body( $response ), true );

				if( $this->debug ){
					echo "RESPONSE:";
					echo "<pre>";
					print_r( $response );
					echo "</pre>";
				}

				return $response;
			}
		} else {
			$response['body']	= json_decode( wp_remote_retrieve_body( $response ), true );

			if( $this->debug ){
				echo "RESPONSE:";
				echo "<pre>";
				print_r( $response );
				echo "</pre>";
			}


			return $response;
		}
	}




	/********************
	 *
	 *  OPTIONS
	 *
	 ********************/
	public function get_options(){
		$options	= array();
		foreach($this->options_key as $key)
			$options[$key]	= get_option( $this->option_prefix . $key);

		return $options;
	}

	public function get_option($key){
		return get_option( $this->option_prefix . $key);
	}

	public function save_options($new_options){
		$old_options	= $this->get_options();

		//echo "<pre>";
		//print_r($_POST);
		//echo "</pre>";
		foreach( $old_options as $key=>$old_value ){

			if( !isset($new_options[$key]) )
				continue;

			if( !isset($new_options[$key]))
				$new_options[$key] = '';

			$new_value	= $new_options[$key];

			if( !is_array($new_value))
				$new_value	= sanitize_text_field($new_value);

			$clear_cache	= false;
			if ($new_value !== $old_value) {
				update_option($this->option_prefix . $key, $new_value);

				if( $key == "store_city_id" || $key == "shipping_company_enabled" )
					$clear_cache	= true;

				//echo "update option $key:$new_value<hr>";
				//if( is_array($new_value))
				//	print_r( $new_value );

			}

			if( $clear_cache )
				$this->clear_cache();
		}
		//die();
	} // function save_options


	// remove all options from wp db
	public function remove_options() {
		foreach( $this->options_key as $key){
			if($key!='version_type' && $key!='license_key' && $key!='license_status' ){
				$name	= $this->option_prefix . $key;
				delete_option( $name );
			}
		}
		return true;
	} // function removeOptions

	public function add_options($reset=false){
		if( !$reset ){
			add_option( $this->option_prefix . 'version_type', '0');
			add_option( $this->option_prefix . 'license_key', '');
			add_option( $this->option_prefix . 'license_status', 'free');
		}
		add_option( $this->option_prefix . 'shipping_company_enabled', array(1,2));
		add_option( $this->option_prefix . 'store_city_id', '0');
		add_option( $this->option_prefix . 'is_show_weight', 1);
		add_option( $this->option_prefix . 'is_unique_code', 0);
		add_option( $this->option_prefix . 'unique_code_length', 2);
		add_option( $this->option_prefix . 'is_added_cost_enable', 0);
		add_option( $this->option_prefix . 'added_cost', 0);
		add_option( $this->option_prefix . 'default_weight', 1);
		add_option( $this->option_prefix . 'weight_tolerance', 300);
	}


	/********************
	 *
	 *  WEIGHT TOOLS
	 *
	 ********************/

	public function weight_convert($weight, $to_unit, $from_unit='') {
		$from_unit	= (empty($from_unit)) ? get_option('woocommerce_weight_unit') : $from_unit;

		return wc_get_weight($weight,$to_unit,$from_unit);
	}

	public function weight_round($weight) {
		$weight_tolerance 	= $this->get_option('weight_tolerance');
		$weight_tolerance	= $weight_tolerance/1000;
		$fraction = fmod($weight, 1);
		if ($fraction <= $weight_tolerance) {
			$weight = floor($weight);
		} else {
			$weight = ceil($weight);
		}
		if($weight<1)
			$weight = 1;

		return $weight;
	}

	public function get_cart_weight($packages){
		$default_weight	= $this->get_option("default_weight");
		$weight	= 0;

		if( $packages )
			foreach ( $packages as $cart_item_key => $values ) {
				$product = $values['data'];
				$current_weight	= $product->has_weight() ? $product->get_weight() : $default_weight;
				$weight += $current_weight * $values['quantity'];
			}
		$weight	= $this->weight_convert($weight, "kg");
		$weight	= $this->weight_round($weight);

		return $weight;
	}

	/********************
	 *
	 *  GENERAL TOOLS
	 *
	 ********************/

	public function get_random($digit=1){
		$max	= '';
		$min	= '1';
		for($i=1;$i<=$digit; $i++){
			$max	.= 9;
			if( $i != 1 )
				$min	.= 0;
		}

		return rand($min, $max);
	}

	public function convert_currency($cost = 0, $symbol = '') {
		$rates = $this->remote_get_currency();
		if (empty($symbol))
			$symbol = get_option('woocommerce_currency');

		if($symbol != 'IDR' && $rates )
			return $cost * (float) $rates[$symbol];

		return $cost;
	}

	function clear_cache(){
		$cities	= $this->remote_get_cities();
		$transient_names	= array();
		$transient_names[]	= "shipping_company";
		$transient_names[]	= "cities";
		$transient_names[]	= "cities_from";
		$transient_names[]	= "currency";
		if($cities['city_id'])
		foreach( $cities['city_id'] as $city_id=>$city){
			$transient_names[]	= "districts-".$city_id;
		}

		foreach($transient_names as $transient_name){
			delete_transient($this->option_prefix . $transient_name);
			//echo "delete_transient($transient_name);<hr>";
		}

		return true;
	}

	public function reorder_field_priority($fields, $array_order){
		$tmp = array();
		$priority = 10;
		foreach($array_order as $key){
			$tmp[$key]  = $fields[$key];
			$tmp[$key]['priority'] = $priority;
			$priority	+= 10;
		}

		return $tmp;
	}


	public function is_license_active() {
		$license = get_option($this->option_prefix . 'license_status');
		if ($license == 'active')
			return true;

		return false;
	}


	public function update_license($new_status) {
		if ($new_status == 'active')
			return true;

		return update_option($this->option_prefix . 'license_status', $new_status);
	}

	public function is_woocommerce_active(){
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ));
	}
}
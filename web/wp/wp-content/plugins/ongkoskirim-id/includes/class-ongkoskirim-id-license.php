<?php


class Ongkoskirim_Id_License {
	function __construct($lib=""){
		// supaya tidak dobel2 panggil lib
		if( empty($lib) )
			$this->lib	= new Ongkoskirim_Id_Library();
		else
			$this->lib	= $lib;


		$this->license_url	= "http://store.".$this->lib->domain."/";
		$this->product_id	= "oid";
		$this->option_prefix	= "ongkoskirim_id_";
	}

	function activate($license_key){

		$return = array();

		// API query parameters
		$api_params = array(
			'slm_action' => 'slm_activate',
			'license_key' => $license_key,
			'registered_domain' => $this->get_website_url(),
			'item_reference' => $this->product_id,
		);

		$response	= $this->remote_get($api_params);

		// Check for error in the response
		if (is_wp_error($response)){
			$return['status']	= false;
			$return['msg']		= "Cannot connect to api server.";

			return $return;
		}

		// License data.
		$license_data = json_decode(wp_remote_retrieve_body($response));


		if($license_data->result == 'success'){//Success was returned for the license activation

			// Uncomment the followng line to see the message that returned from the license server
			//echo '<br />The following message was returned from the server: '.$license_data->message;

			//Save the license key in the options table
			update_option($this->option_prefix . "license_key", $license_key);
			update_option($this->option_prefix . "license_status", "active");
			update_option($this->option_prefix . "version_type", "1");


			$return['status']	= true;
			$return['msg']		= $license_data->message;

			return $return;
		}
		else{

			$return['status']	= false;
			$return['msg']		= $license_data->message;

			return $return;
		}
	}

	function deactivate($license_key){

		$return = array();


		// API query parameters
		$api_params = array(
			'slm_action' => 'slm_deactivate',
			'license_key' => $license_key,
			'registered_domain' => $this->get_website_url(),
			'item_reference' => $this->product_id,
		);

		$response	= $this->remote_get($api_params);

		// Check for error in the response
		if (is_wp_error($response)){
			$return['status']	= false;
			$return['msg']		= "Cannot connect to api server.";

			return $return;
		}

		// License data.
		$license_data = json_decode(wp_remote_retrieve_body($response));

		if($license_data->result == 'success' || $license_data->message ='The license key on this domain is already inactive'){//Success was returned for the license activation

			// Uncomment the followng line to see the message that returned from the license server
			//echo '<br />The following message was returned from the server: '.$license_data->message;

			//Save the license key in the options table
			update_option($this->option_prefix . "license_key", "");
			update_option($this->option_prefix . "license_status", "deactivate");
			update_option($this->option_prefix . "version_type", "0");

			$return['status']	= true;
			$return['msg']		= $license_data->message;

			return $return;
		}
		else{
			$return['status']	= false;
			$return['msg']		= $license_data->message;

			return $return;
		}
	}

	private function get_website_url(){
		$matches 		= array();
		preg_match_all("#^.+?[^\/:](?=[?\/]|$)#", get_site_url(), $matches);
		$website_url	= $matches[0][0];

		return $website_url;
	}

	private function remote_get($api_params){
		// Send query to the license manager server
		$query = esc_url_raw(add_query_arg($api_params, $this->license_url));
		return $response = wp_remote_get($query, array('timeout' => 30, 'sslverify' => false));
	}
}
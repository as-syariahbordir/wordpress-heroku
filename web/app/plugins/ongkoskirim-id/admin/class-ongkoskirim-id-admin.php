<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://jogja.camp
 * @since      1.0.0
 *
 * @package    Ongkoskirim_Id
 * @subpackage Ongkoskirim_Id/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ongkoskirim_Id
 * @subpackage Ongkoskirim_Id/admin
 * @author     jogja.camp <ongkoskirimid@jogjacamp.co.id>
 */
class Ongkoskirim_Id_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->lib	= new Ongkoskirim_Id_Library();
		$this->license	= new Ongkoskirim_Id_License($this->lib);

		$this->homepage_url	= 'http://plugin.'.$this->lib->domain.'/';
		$this->docs_url		= $this->homepage_url.'dokumentasi.php';
		$this->download_url	= $this->homepage_url.'ongkoskirim.id.1.0.0.zip';
		$this->contact_url	= 'http://forum.ongkoskirim.id/';

		$this->upgrade_pro_url			= 'http://store.'.$this->lib->domain.'/checkout/?add-to-cart=37&variation_id=87&attribute_site=1%20Site&clear-cart=1';
		$this->upgrade_pro_multi_url	= 'http://store.'.$this->lib->domain.'/checkout/?add-to-cart=37&variation_id=86&attribute_site=5%20Sites&clear-cart=1';

		$this->settings_url = admin_url('admin.php?page=wc-settings&tab=shipping&section=ongkoskirim-id');
	}

	/**
	 * Register the stylesheets for the admin area.
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

		// welcome page
		if( isset($_GET['page']) && $_GET['page'] == 'ongkoskirim-id'){
			wp_enqueue_style( $this->plugin_name."-boostrap", 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name."-gfont", 'https://fonts.googleapis.com/icon?family=Material+Icons', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name."-welcome-style", plugin_dir_url( __FILE__ ) . 'css/ongkoskirim-id-welcome-style.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name."-responsive", plugin_dir_url( __FILE__ ) . 'css/ongkoskirim-id-welcome-responsive.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name."-fontawesome", 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', array(), $this->version, 'all' );
		}
		else{
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ongkoskirim-id-admin.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
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

		if( isset($_GET['page']) && $_GET['page'] == 'ongkoskirim-id'){

		}else{
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ongkoskirim-id-admin.js', array( 'jquery' ), $this->version, true );
		}

	}

	/**
	 * Add a welcome page
	 *
	 * @since  1.0.0
	 */
	public function add_welcome_page() {

		add_menu_page( 'OngkosKirim.id', 'OngkosKirim.id', 'manage_options', 'ongkoskirim-id', array($this,'display_welcome_page'), 'dashicons-products', 5  );

	}

	/**
	 * Display welcome page
	 *
	 * @since 		1.0.0
	 * @return 		void
	 */
	public function display_welcome_page() {

		include( plugin_dir_path( __FILE__ ) . 'partials/ongkoskirim-id-admin-welcome-page.php' );

	} // display_welcome_page()


	/**
	 * Add a settings page
	 *
	 * @since  1.0.0
	 */
	public function add_settings_menu($sections) {
		/**
		 * Create the section beneath the products tab
		 **/
		$sections['ongkoskirim-id'] = 'OngkosKirim.id';
		return $sections;
	}



	/**
	 * Add setting link to plugin list table
	 *
	 * @access public
	 * @param  array $links Existing links
	 * @return array		Modified links
	 * @since 1.0.0
	 */
	public function add_settings_link( $links ){
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=ongkoskirim-id' ) . '">' . __( 'Settings', 'ongkoskirim-id' ) . '</a>',
			'<a href="' . $this->docs_url . '" target="new">' . __( 'Docs', 'ongkoskirim-id' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	public function activate_license(){

		$license = $_POST['license_key'];
		$response	= $this->license->activate($license);
		//print_r($response);
		if( isset($response['status']) && $response['status']){
			echo "success";
		}
		else{
			if( isset($response['msg']) )
				echo $response['msg'];
			else
				__("Gagal tersambung ke server", "ongkoskirim-id");
		}
		wp_die();
	}

	public function deactivate_license(){
		$license = $_POST['license_key'];
		$response	= $this->license->deactivate($license);
		if( isset($response['status']) && $response['status'])
			echo "success";
		else{
			if( isset($response['msg']) )
				echo $response['msg'];
			else
				__("Gagal tersambung ke server", "ongkoskirim-id");
		}
		wp_die();
	}

	/* sampaikan pesan error */
	public function admin_notices() {

		$errmsg = array();
		$status = get_option('woocommerce_ongkoskirim-id_settings', array('enabled'=>'no'));

		if (!$this->lib->is_woocommerce_active()) {
			$errmsg[] = __('Plugin Woocommerce tidak aktif', "ongkoskirim-id");
		}

		if (!function_exists('curl_version')) {
			$errmsg[] = __('CURL tidak aktif di server Anda', "ongkoskirim-id");
		}

		if ($status['enabled'] == 'yes') {

			if ($this->lib->options['license_status']=='expired') {
				$errmsg[] = __('Lisensi plugin OngkosKirim.id sudah kadaluarsa. <a href="http://plugin.ongkoskirim.id">Perpanjang sekarang!</a>', "ongkoskirim-id");
			}
			if (empty($this->lib->options['store_city_id'])) {
				$errmsg[] = __('Lokasi Toko Kosong', "ongkoskirim-id");
			}
			if (empty($this->lib->options['store_city_id'])) {
				$errmsg[] = __('Lokasi Toko Kosong', "ongkoskirim-id");
			}
		}
		if (!empty($errmsg)) {
			?>
			<div class="notice notice-error">
				<p><?php _e('Plugin OngkosKirim.id tidak aktif karena sbb:', "ongkoskirim-id") ?></p>
				<?php foreach ($errmsg as $e) : ?>
					<p style="margin:0;"><?php echo $e; ?></p>
				<?php endforeach; ?>
				<p></p>
			</div>
			<?php
		}
	}
}
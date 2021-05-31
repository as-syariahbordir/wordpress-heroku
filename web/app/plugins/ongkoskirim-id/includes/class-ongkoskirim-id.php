<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://jogja.camp
 * @since      1.0.0
 *
 * @package    Ongkoskirim_Id
 * @subpackage Ongkoskirim_Id/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Ongkoskirim_Id
 * @subpackage Ongkoskirim_Id/includes
 * @author     jogja.camp <ongkoskirimid@jogjacamp.co.id>
 */


class Ongkoskirim_Id {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Ongkoskirim_Id_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'ongkoskirim-id';
		$this->version = '1.0.4';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Ongkoskirim_Id_Loader. Orchestrates the hooks of the plugin.
	 * - Ongkoskirim_Id_i18n. Defines internationalization functionality.
	 * - Ongkoskirim_Id_Admin. Defines all hooks for the admin area.
	 * - Ongkoskirim_Id_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ongkoskirim-id-library.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ongkoskirim-id-license.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ongkoskirim-id-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ongkoskirim-id-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ongkoskirim-id-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ongkoskirim-id-public.php';



		$this->loader = new Ongkoskirim_Id_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Ongkoskirim_Id_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Ongkoskirim_Id_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Ongkoskirim_Id_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_welcome_page' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_notices' );
		$this->loader->add_action( 'wp_ajax_ongkoskirim_id_activate_license', $plugin_admin, 'activate_license' );
		$this->loader->add_action( 'wp_ajax_ongkoskirim_id_deactivate_license', $plugin_admin, 'deactivate_license' );


		$plugin_file	= $this->plugin_name."/".$this->plugin_name.".php";
		$this->loader->add_filter( 'plugin_action_links_' . $plugin_file, $plugin_admin, 'add_settings_link' );
	}


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Ongkoskirim_Id_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'woocommerce_shipping_init', $plugin_public, 'ongkoskirim_id_shipping_method' );
		$this->loader->add_filter( 'woocommerce_shipping_methods', $plugin_public, 'add_ongkoskirim_id_shipping_method' );

		$this->loader->add_filter( 'woocommerce_billing_fields' , $plugin_public, 'ongkoskirim_id_custom_override_billing_fields' );
		$this->loader->add_filter( 'woocommerce_shipping_fields' , $plugin_public, 'ongkoskirim_id_custom_override_shipping_fields' );
		$this->loader->add_filter( 'woocommerce_checkout_fields' , $plugin_public, 'ongkoskirim_id_custom_override_checkout_fields' );

		$this->loader->add_action( 'wp_ajax_get_cities', $plugin_public, 'get_cities' );
		$this->loader->add_action( 'wp_ajax_nopriv_get_cities', $plugin_public, 'get_cities' );

		$this->loader->add_action( 'wp_ajax_get_districts', $plugin_public, 'get_districts' );
		$this->loader->add_action( 'wp_ajax_nopriv_get_districts', $plugin_public, 'get_districts' );

		$this->loader->add_filter( 'woocommerce_checkout_update_order_review', $plugin_public, 'flush_shipping_cache');

		$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $plugin_public, 'ongkoskirim_id_checkout_update_order_meta' );

		$this->loader->add_filter( 'woocommerce_shipping_calculator_enable_postcode', $plugin_public, 'ongkoskirim_id_custom_shipping_calculator_postcode');
		$this->loader->add_filter( 'woocommerce_my_account_my_address_formatted_address', $plugin_public, 'ongkoskirim_id_custom_my_account_my_address_formatted_address',10,3);


		$this->loader->add_filter( 'woocommerce_process_myaccount_field_billing_city', $plugin_public, 'ongkoskirim_id_custom_woocommerce_process_myaccount_field_billing_city',10,1);
		$this->loader->add_filter( 'woocommerce_process_myaccount_field_shipping_city', $plugin_public, 'ongkoskirim_id_custom_woocommerce_process_myaccount_field_shipping_city',10,1);

		// kode unik
		$this->loader->add_action( 'woocommerce_cart_calculate_fees', $plugin_public, 'ongkoskirim_id_custom_woocommerce_cart_calculate_fees',10,1 );

		// tampilkan berat pengiriman
		$this->loader->add_action( 'woocommerce_review_order_after_cart_contents', $plugin_public,'show_weight_checkout');

		$this->loader->add_action('plugins_loaded', $plugin_public, 'ongkoskirim_id_load_language');

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Ongkoskirim_Id_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}

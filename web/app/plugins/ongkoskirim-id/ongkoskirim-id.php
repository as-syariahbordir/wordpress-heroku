<?php

/**
 * Plugin OngkosKirim.id
 *
 *
 * @link              http://plugin.ongkoskirim.id/
 * @since             1.0.0
 * @package           Ongkoskirim_Id
 *
 * @wordpress-plugin
 * Plugin Name:       Ongkoskirim.id
 * Plugin URI:        http://plugin.ongkoskirim.id/
 * Description:       Plugin woocommerce untuk menambahkan ongkos kirim JNE, TIKI, Sicepat, Wahana dan lain-lain.
 * Version:           1.0.6
 * Author:            jogja.camp
 * Author URI:        http://jogjacamp.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ongkoskirim-id
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ongkoskirim-id-activator.php
 */
function activate_ongkoskirim_id() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ongkoskirim-id-activator.php';
	Ongkoskirim_Id_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ongkoskirim-id-deactivator.php
 */
function deactivate_ongkoskirim_id() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ongkoskirim-id-deactivator.php';
	Ongkoskirim_Id_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ongkoskirim_id' );
register_deactivation_hook( __FILE__, 'deactivate_ongkoskirim_id' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ongkoskirim-id.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ongkoskirim_id() {

	$plugin = new Ongkoskirim_Id();
	$plugin->run();

}
run_ongkoskirim_id();
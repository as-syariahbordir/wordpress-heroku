<?php

/**
 * Fired during plugin activation
 *
 * @link       http://jogja.camp
 * @since      1.0.0
 *
 * @package    Ongkoskirim_Id
 * @subpackage Ongkoskirim_Id/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ongkoskirim_Id
 * @subpackage Ongkoskirim_Id/includes
 * @author     jogja.camp <ongkoskirimid@jogjacamp.co.id>
 */
class Ongkoskirim_Id_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		$lib	= new Ongkoskirim_Id_Library();
		$lib->add_options();
	}
}

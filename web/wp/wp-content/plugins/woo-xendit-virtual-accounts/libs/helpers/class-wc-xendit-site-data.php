<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Xendit Site Data
 *
 * @since 2.13.0
 */
class WC_Xendit_Site_Data
{
    /**
     * Utilize WC Site Data
     *
     * @since 2.13.0
     * @version 0.0.1
     */
    public static function retrieve()
    {
        global $wpdb, $woocommerce;

        $mysql_version = 'N/A';
        if((new WC_Xendit_Site_Data)->isEnabled('shell_exec')) {
            $mysql_version =  shell_exec('mysql --version') ? shell_exec('mysql --version') : 'N/A';
        }

        return array(
            'language'          => 'PHP/'.phpversion(),
            'wp_version'        => get_bloginfo('version'),
            'wc_version'        => $woocommerce->version,
            'database'          => $mysql_version,
            'webserver'         => $_SERVER['SERVER_SOFTWARE'],
            'plugins'           => WC_Xendit_Site_Data::get_plugin_list()
        );
    }

    private static function get_plugin_list()
    {
        $plugins = [];

        foreach (get_plugins() as $key => $plugin) {
            $plugins[] = (object) array(
                'name'          => $plugin['Name'],
                'plugin_uri'    => $plugin['PluginURI'],
                'version'       => $plugin['Version'],
                'network'       => $plugin['Network'],
                'is_active'     => is_plugin_active("$key")
            );
        }

        return $plugins;
    }

    private function isEnabled($func) {
        return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
    }
}

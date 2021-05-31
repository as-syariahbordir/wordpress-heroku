<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Cron Xendit Site Data
 *
 * @since 2.13.0
 */

WC_Cron_Xendit_Site_data::instance();

class WC_Cron_Xendit_Site_data {
    const CRON_INTERVAL = 2635200; // Once a month
    const CRON_JOB = 'xendit_site_data_exec';
    protected static $_instance = null;

    public function __construct()
    {
        $this->xenditClass = new WC_Xendit_PG_API(); 

        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('init', array($this, 'schedule'));
        add_action('data_site_activated', array($this, 'data_site_activated'));
        add_filter('cron_schedules', array($this, 'add_interval'));
        register_deactivation_hook(WC_XENDIT_PG_MAIN_FILE, array($this,'data_site_deactivated'));
    
        add_action(self::CRON_JOB, array($this, 'xendit_site_data_wp_exec'));

    }

    /**
     *  Constructor.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function data_site_deactivated()
    {
        $timestamp = wp_next_scheduled(self::CRON_JOB);
        wp_unschedule_event($timestamp, self::CRON_JOB);
    }

    public function data_site_activated()
    {
        $this->schedule();
    }

    public function schedule()
    {
        if (!wp_next_scheduled(self::CRON_JOB)) {
            wp_schedule_event(time(), 'every_month', self::CRON_JOB);
        }
    }

    public function add_interval($schedules)
    {
        $schedules['every_month'] = array(
            'interval' => self::CRON_INTERVAL,
            'display' => 'Every ' . self::CRON_INTERVAL . ' cron job for Xendit Site Data Collector',
        );

        return $schedules;
    }

    public function xendit_site_data_wp_exec()
    {
        $site_data = WC_Xendit_Site_Data::retrieve();

        $this->xenditClass->createPluginInfo($site_data);
    }
}
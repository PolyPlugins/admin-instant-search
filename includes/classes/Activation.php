<?php

namespace PolyPlugins\Admin_Instant_Search;

if (!defined('ABSPATH')) exit;

class Activation {
  
  /**
   * init
   *
   * @return void
   */
  public static function init() {
    self::schedule_cron();
    self::set_default_options();
  }
  
  /**
   * Schedule cron
   *
   * @return void
   */
  private static function schedule_cron() {
    if (!wp_next_scheduled('admin_instant_search_background_worker')) {
      wp_schedule_event(time(), 'every_minute', 'admin_instant_search_background_worker');
    }
  }
  
  /**
   * Set default options
   *
   * @return void
   */
  private static function set_default_options() {
    $default_options = array(
      'enabled' => false,
    );

    add_option('admin_instant_search_settings_polyplugins', $default_options);
  }

}
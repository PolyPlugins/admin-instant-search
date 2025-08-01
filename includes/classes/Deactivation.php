<?php

namespace PolyPlugins\Admin_Instant_Search;

if (!defined('ABSPATH')) exit;

class Deactivation {
  
  /**
   * init
   *
   * @return void
   */
  public static function init() {
    self::clear_cron();
  }
  
  /**
   * Clear cron
   *
   * @return void
   */
  private static function clear_cron() {
    wp_clear_scheduled_hook('admin_instant_search_background_worker');
  }

}
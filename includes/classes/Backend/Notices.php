<?php

namespace PolyPlugins\Admin_Instant_Search\Backend;

use PolyPlugins\Admin_Instant_Search\Utils;

class Notices {

  private $plugin;
  private $version;
  private $plugin_dir_url;
  private $notice_key = 'admin_instant_search_dismiss_notice';

  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->plugin         = $plugin;
    $this->version        = $version;
    $this->plugin_dir_url = $plugin_dir_url;
  }

  public function init() {
    // add_action('admin_notices', array($this, 'maybe_show_notice'));
    // add_action('wp_ajax_admin_instant_search_dismiss_notice_nonce', array($this, 'dismiss_notice'));
  }

  public function maybe_show_notice() {
    $is_dismissed = get_option('admin_instant_search_notice_dismissed_polyplugins');

    if ($is_dismissed) {
      return;
    }
    
    $screen = get_current_screen();

    if ($screen->id != 'toplevel_page_admin-instant-search') {

    }
  }

  public function dismiss_notice() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'admin_instant_search_dismiss_notice_nonce')) {
      Utils::send_error('Invalid session', 403);
    }

    if (!current_user_can('manage_options')) {
      Utils::send_error('Unauthorized', 401);
    }

    update_option('admin_instant_search_notice_dismissed_polyplugins', true);
  }

}

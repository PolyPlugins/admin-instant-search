<?php

namespace PolyPlugins\Admin_Instant_Search\Backend;

use PolyPlugins\Admin_Instant_Search\Utils;

class Enqueue {

  /**
	 * Full path and filename of plugin.
	 *
	 * @var string $version Full path and filename of plugin.
	 */
  private $plugin;

	/**
	 * The version of this plugin.
	 *
	 * @var string $version The current version of this plugin.
	 */
	private $version;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct($plugin, $version) {
    $this->plugin  = $plugin;
    $this->version = $version;
  }
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    add_action('admin_enqueue_scripts', array($this, 'enqueue'));
  }
  
  /**
   * Enqueue scripts and styles
   *
   * @return void
   */
  public function enqueue($hook_suffix) {
    $this->enqueue_dismiss_notices();

    if ($hook_suffix === 'toplevel_page_admin-instant-search') {
      $this->enqueue_styles();
      $this->enqueue_scripts();
      $this->enqueue_wordpress();
    }

    if ($hook_suffix === 'woocommerce_page_wc-orders') {
      $this->enqueue_order_styles();
      $this->enqueue_order_scripts();
    }
  }
  
  /**
   * Enqueue scripts
   *
   * @return void
   */
  private function enqueue_dismiss_notices() {
    wp_enqueue_script('admin-instant-search-dismiss-notices', plugins_url('/js/backend/dismiss-notices.js', $this->plugin), array('jquery'), $this->version, true);
    wp_localize_script(
      'admin-instant-search-dismiss-notices',
      'admin_instant_search_object',
      array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('admin_instant_search_dismiss_notice_nonce')
      )
    );
  }
  
  /**
   * Enqueue styles
   *
   * @return void
   */
  private function enqueue_styles() {
    wp_enqueue_style('admin-instant-search-settings', plugins_url('/css/backend/settings.css', $this->plugin), array(), $this->version);
    wp_enqueue_style('bootstrap', plugins_url('/css/backend/bootstrap-wrapper.min.css', $this->plugin), array(), $this->version);
    wp_enqueue_style('bootstrap-icons', plugins_url('/css/bootstrap-icons.min.css', $this->plugin), array(), $this->version);
    wp_enqueue_style('select2', plugins_url('/css/backend/select2.min.css', $this->plugin), array(), $this->version);
    wp_enqueue_style('sweetalert2', plugins_url('/css/backend/sweetalert2.min.css', $this->plugin), array(), $this->version);
  }
  
  /**
   * Enqueue scripts
   *
   * @return void
   */
  private function enqueue_scripts() {
    wp_enqueue_script('admin-instant-search-settings', plugins_url('/js/backend/settings.js', $this->plugin), array('jquery', 'wp-color-picker', 'wp-i18n'), $this->version, true);
    wp_localize_script(
      'admin-instant-search-settings',
      'admin_instant_search_object',
      array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('admin_instant_search_reindex_nonce')
      )
    );
    wp_set_script_translations('admin-instant-search-settings', 'admin-instant-search', plugin_dir_path($this->plugin) . '/languages/');
    wp_enqueue_script('bootstrap', plugins_url('/js/bootstrap.min.js', $this->plugin), array('jquery', 'wp-color-picker'), $this->version, true);
    wp_enqueue_script('select2', plugins_url('/js/backend/select2.min.js', $this->plugin), array('jquery'), $this->version, true);
    wp_enqueue_script('sweetalert2', plugins_url('/js/backend/sweetalert2.all.min.js', $this->plugin), array('jquery'), $this->version, true);
  }
  
  /**
   * Enqueue order styles
   *
   * @return void
   */
  private function enqueue_order_styles() {
    wp_enqueue_style('admin-instant-search-orders', plugins_url('/css/backend/orders.css', $this->plugin), array(), $this->version);
    wp_enqueue_style('sweetalert2', plugins_url('/css/backend/sweetalert2.min.css', $this->plugin), array(), $this->version);
  }
  
  /**
   * Enqueue order scripts
   *
   * @return void
   */
  private function enqueue_order_scripts() {
    $options     = get_option('admin_instant_search_settings_polyplugins');
    $is_indexing = Utils::is_indexing();
    
    // Fallback to default search when indexing
    if (!$is_indexing) {
      wp_enqueue_script('admin-instant-search-orders', plugins_url('/js/backend/orders.js', $this->plugin), array('jquery', 'wp-i18n'), $this->version, true);
      wp_localize_script(
        'admin-instant-search-orders',
        'admin_instant_search_object',
        array(
          'options'  => $options,
          'currency' => class_exists('WooCommerce') ? get_woocommerce_currency_symbol() : '',
          'nonce'    => wp_create_nonce('wp_rest'),
        )
      );
      wp_set_script_translations('admin-instant-search-orders', 'admin-instant-search', plugin_dir_path($this->plugin) . '/languages/');
      wp_enqueue_script('sweetalert2', plugins_url('/js/backend/sweetalert2.all.min.js', $this->plugin), array('jquery'), $this->version, true);
    }
  }
  
  /**
   * Enqueue WordPress related styles and scripts
   *
   * @return void
   */
  private function enqueue_wordpress() {
    wp_enqueue_media();
    wp_enqueue_editor();
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('wp-components');
  }

}
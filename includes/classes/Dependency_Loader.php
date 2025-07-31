<?php

namespace PolyPlugins\Admin_Instant_Search;

class Dependency_Loader {

  /**
	 * Full path and filename of plugin.
	 *
	 * @var string $version Full path and filename of plugin.
	 */
  private $plugin;

	/**
	 * The version of this plugin.
	 *
	 * @var   string $version The current version of this plugin.
	 */
	private $version;

  /**
   * The URL to the plugin directory.
   *
   * @var string $plugin_dir_url URL to the plugin directory.
   */
	private $plugin_dir_url;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->plugin         = $plugin;
    $this->version        = $version;
    $this->plugin_dir_url = $plugin_dir_url;
  }
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if (!is_plugin_active('speedy-search/speedy-search.php')) {
      $this->load_frontend();
      $this->load_backend();
      $this->load_updater();
    } else {
      add_action('admin_notices', array($this, 'display_incompatible_notice'));
    }
  }
  
  /**
   * Load Frontend
   *
   * @return void
   */
  public function load_frontend() {
    $frontend_loader = new Frontend_Loader($this->plugin, $this->version, $this->plugin_dir_url);
    $frontend_loader->init();
  }
  
  /**
   * Load Backend
   *
   * @return void
   */
  public function load_backend() {
    $backend_loader = new Backend_Loader($this->plugin, $this->version, $this->plugin_dir_url);
    $backend_loader->init();
  }
  
  /**
   * Load Updater
   *
   * @return void
   */
  public function load_updater() {
    $backend_loader = new Updater($this->plugin, $this->version, $this->plugin_dir_url);
    $backend_loader->init();
  }
  
  /**
   * Display incompatible notice
   *
   * @return void
   */
  public function display_incompatible_notice() {
    ?>
    <div class="notice notice-error">
      <p>Admin Instant Search is not compatible with Snappy Search, please disable the Admin Instant Search plugin and enable Admin Instant Search from within Snappy Search settings.</p>
    </div>
    <?php
  }

}
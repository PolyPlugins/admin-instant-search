<?php

namespace PolyPlugins\Admin_Instant_Search;

use PolyPlugins\Admin_Instant_Search\Backend\Admin;
use PolyPlugins\Admin_Instant_Search\Backend\API;
use PolyPlugins\Admin_Instant_Search\Backend\Background_Worker;
use PolyPlugins\Admin_Instant_Search\Backend\Enqueue;
use PolyPlugins\Admin_Instant_Search\Backend\Index_Updater;
use PolyPlugins\Admin_Instant_Search\Backend\Notices;
use PolyPlugins\Admin_Instant_Search\Backend\Reindexer;

class Backend_Loader {

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
    $this->load_enqueue();
    $this->load_admin();
    $this->load_api();
    $this->load_background_worker();
    $this->load_index_updater();
    $this->load_reindexer();
    $this->load_notices();
  }
  
  /**
   * Load UI
   *
   * @return void
   */
  public function load_enqueue() {
    $gui = new Enqueue($this->plugin, $this->version);
    $gui->init();
  }
  
  /**
   * Load Admin
   *
   * @return void
   */
  public function load_admin() {
    $admin = new Admin($this->plugin, $this->version, $this->plugin_dir_url);
    $admin->init();
  }
  
  /**
   * Load Admin
   *
   * @return void
   */
  public function load_api() {
    $admin = new API($this->plugin, $this->version, $this->plugin_dir_url);
    $admin->init();
  }
  
  /**
   * Load Background Worker
   *
   * @return void
   */
  public function load_background_worker() {
    $admin = new Background_Worker($this->plugin, $this->version, $this->plugin_dir_url);
    $admin->init();
  }
  
  /**
   * Load Index Updater
   *
   * @return void
   */
  public function load_index_updater() {
    $admin = new Index_Updater($this->plugin, $this->version, $this->plugin_dir_url);
    $admin->init();
  }
  
  /**
   * Load Reindexer
   *
   * @return void
   */
  public function load_reindexer() {
    $admin = new Reindexer($this->plugin, $this->version, $this->plugin_dir_url);
    $admin->init();
  }
  
  /**
   * Load Notices
   *
   * @return void
   */
  public function load_notices() {
    $admin = new Notices($this->plugin, $this->version, $this->plugin_dir_url);
    $admin->init();
  }

}
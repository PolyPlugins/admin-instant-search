<?php

namespace PolyPlugins\Admin_Instant_Search;

class Updater {

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

  public function init() {
    add_action('wp', array($this, 'maybe_update'));
  }

  public function maybe_update() {
    $stored_version = get_option('admin_instant_search_version_polyplugins');

    if (!$stored_version) {
      $stored_version = $this->version;

      update_option('admin_instant_search_version_polyplugins', $this->version);
    }
  }

}
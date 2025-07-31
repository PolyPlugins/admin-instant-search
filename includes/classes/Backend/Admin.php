<?php

namespace PolyPlugins\Admin_Instant_Search\Backend;

use PolyPlugins\Admin_Instant_Search\Utils;

class Admin {

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
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'settings_init'));
    add_filter('plugin_action_links_' . plugin_basename($this->plugin), array($this, 'add_setting_link'));
    
    $repo_enabled = Utils::get_option('repo_enabled');

    if ($repo_enabled) {
		  add_action('admin_menu', array($this, 'advanced_repo_search'));
    }
  }

	/**
	 * Add admin menu to backend
	 *
	 * @return void
	 */
	public function add_admin_menu() {
    add_action('admin_notices', array($this, 'maybe_show_indexing_notice'));
    add_action('admin_notices', array($this, 'maybe_show_missing_extensions_notice'));
		add_menu_page(__('Admin Instant Search', 'admin-instant-search'), __('Admin Instant Search', 'admin-instant-search'), 'manage_options', 'admin-instant-search', array($this, 'options_page'), 'dashicons-search');
	}
  
  /**
   * Maybe show indexing notice
   *
   * @return void
   */
  public function maybe_show_indexing_notice() {
    $enabled = Utils::get_option('enabled');

    if (!$enabled) {
      return;
    }

    $is_indexing = Utils::is_indexing();
    ?>
    <?php if ($is_indexing) : ?>
      <div class="notice notice-warning">
        <p><?php esc_html_e('Admin Instant Search is currently indexing.', 'admin-instant-search'); ?></p>
      </div>
    <?php endif; ?>
    <?php
  }
  
  /**
   * Maybe show missing extensions notice
   *
   * @return void
   */
  public function maybe_show_missing_extensions_notice() {
    $is_missing_extensions = Utils::is_missing_extensions();
    ?>
    <?php if ($is_missing_extensions) : ?>
      <div class="notice notice-error">
        <p><?php esc_html_e('Admin Instant Search requires the following extensions:', 'admin-instant-search'); ?></p>
        <?php foreach($is_missing_extensions as $missing_extension) : ?>
          <li><?php echo esc_html($missing_extension); ?></li>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php
  }
  
	/**
	 * Initialize Settings
	 *
	 * @return void
	 */
	public function settings_init() {
    // Register the setting page
    register_setting(
      'admin_instant_search_polyplugins',          // Option group
      'admin_instant_search_settings_polyplugins', // Option name
      array($this, 'sanitize')
    );

    add_settings_section(
      'admin_instant_search_general_section_polyplugins',
      '',
      null,
      'admin_instant_search_general_polyplugins'
    );

    add_settings_section(
      'admin_instant_search_orders_section_polyplugins',
      '',
      null,
      'admin_instant_search_orders_polyplugins'
    );
    
    // Add a setting under general section
		add_settings_field(
			'enabled',                                  // Setting Id
			__('Enabled?', 'admin-instant-search'),            // Setting Label
			array($this, 'enabled_render'),             // Setting callback
			'admin_instant_search_general_polyplugins',        // Setting page
			'admin_instant_search_general_section_polyplugins' // Setting section
		);

		add_settings_field(
			'characters',
		  __('Characters', 'admin-instant-search'),
			array($this, 'characters_render'),
			'admin_instant_search_general_polyplugins',
			'admin_instant_search_general_section_polyplugins'
		);

		add_settings_field(
			'max_characters',
		  __('Max Characters', 'admin-instant-search'),
			array($this, 'max_characters_render'),
			'admin_instant_search_general_polyplugins',
			'admin_instant_search_general_section_polyplugins'
		);

		add_settings_field(
			'typing_delay',
		  __('Typing Delay', 'admin-instant-search'),
			array($this, 'typing_delay_render'),
			'admin_instant_search_general_polyplugins',
			'admin_instant_search_general_section_polyplugins'
		);
    
		add_settings_field(
			'orders_enabled',
			__('Enabled?', 'admin-instant-search'),
			array($this, 'orders_enabled_render'),
			'admin_instant_search_orders_polyplugins',
			'admin_instant_search_orders_section_polyplugins'
		);

		add_settings_field(
			'orders_batch',
		  __('Batch', 'admin-instant-search'),
			array($this, 'orders_batch_render'),
			'admin_instant_search_orders_polyplugins',
			'admin_instant_search_orders_section_polyplugins'
		);
    
		add_settings_field(
			'orders_result_limit',
			__('Result Limit', 'admin-instant-search'),
			array($this, 'orders_result_limit_render'),
			'admin_instant_search_orders_polyplugins',
			'admin_instant_search_orders_section_polyplugins'
		);
	}

  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function enabled_render() {
		$option = Utils::get_option('enabled'); // Get enabled option value
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="admin_instant_search_settings_polyplugins[enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'admin-instant-search'); ?>
    </div>
		<?php
	}

  /**
	 * Render Characters Field
	 *
	 * @return void
	 */
	public function characters_render() {
		$option = Utils::get_option('characters') ?: 4;
    ?>
    <input type="number" name="admin_instant_search_settings_polyplugins[characters]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many characters to trigger Admin Instant Search?', 'admin-instant-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Characters Field
	 *
	 * @return void
	 */
	public function max_characters_render() {
		$option = Utils::get_option('max_characters') ?: 100;
    ?>
    <input type="number" name="admin_instant_search_settings_polyplugins[max_characters]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('Maximum number of characters allowed to be searched?', 'admin-instant-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Typing Delay Field
	 *
	 * @return void
	 */
	public function typing_delay_render() {
		$option = Utils::get_option('typing_delay') ?: 300;
    ?>
    <input type="number" name="admin_instant_search_settings_polyplugins[typing_delay]" id="typing_delay" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many milliseconds between inputs until a search is fired?', 'admin-instant-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function orders_enabled_render() {
		$options = Utils::get_option('orders');
    $option  = isset($options['enabled']) ? $options['enabled'] : 0;
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="admin_instant_search_settings_polyplugins[orders][enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'admin-instant-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Index and show orders in the search?', 'admin-instant-search'); ?></strong></p>
		<?php
	}

  /**
	 * Render Orders Batch Field
	 *
	 * @return void
	 */
	public function orders_batch_render() {
		$options = Utils::get_option('orders');
    $option  = isset($options['batch']) ? $options['batch'] : 100;
    ?>
    <input type="number" name="admin_instant_search_settings_polyplugins[orders][batch]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many orders should be indexed per minute?', 'admin-instant-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Orders Batch Field
	 *
	 * @return void
	 */
	public function orders_result_limit_render() {
		$options = Utils::get_option('orders');
    $option  = isset($options['result_limit']) ? $options['result_limit'] : 100;
    ?>
    <input type="number" name="admin_instant_search_settings_polyplugins[orders][result_limit]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many orders would you like to show?', 'admin-instant-search'); ?></strong></p>
	  <?php
	}
	
	/**
	 * Render options page
	 *
	 * @return void
	 */
	public function options_page() {
  ?>
    <form action='options.php' method='post'>
      <div class="bootstrap-wrapper">
        <div class="container">
          <div class="row">
            <div class="col-3"></div>
            <div class="col-6">
              <h1><?php esc_html_e('Admin Instant Search Settings', 'admin-instant-search'); ?></h1>
            </div>
            <div class="col-3"></div>
          </div>
          <div class="row">
            <div class="nav-links col-12 col-md-6 col-xl-3">
              <ul>
                <li>
                  <a href="javascript:void(0);" class="active" data-section="general">
                    <i class="bi bi-gear-fill"></i>
                    <?php esc_html_e('General', 'admin-instant-search'); ?>
                  </a>
                </li>
                <?php if (class_exists('WooCommerce')) : ?>
                  <li>
                    <a href="javascript:void(0);" data-section="orders">
                      <i class="bi bi-bag-fill"></i>
                      <?php esc_html_e('Orders', 'admin-instant-search'); ?>
                    </a>
                  </li>
                <?php endif; ?>
                <li>
                  <a href="javascript:void(0);" data-section="reindex">
                    <i class="bi bi-database-fill"></i>
                    <?php esc_html_e('Reindex', 'admin-instant-search'); ?>
                  </a>
                </li>
              </ul>
            </div>
            <div class="tabs col-12 col-md-6 col-xl-6">
              <div class="tab general">
                <?php
                do_settings_sections('admin_instant_search_general_polyplugins');
                ?>
              </div>

              <?php if (class_exists('WooCommerce')) : ?>
                <div class="tab orders" style="display: none;">
                  <?php
                  do_settings_sections('admin_instant_search_orders_polyplugins');
                  ?>
                </div>
              <?php endif; ?>
            
              <?php
              settings_fields('admin_instant_search_polyplugins');
              submit_button();
              ?>
              
            </div>

            <div class="ctas col-12 col-md-12 col-xl-3">
              <div class="cta">
                <h2 style="color: #fff;">Something Not Working?</h2>
                <p>We pride ourselves on quality, so if something isn't working or you have a suggestion, feel free to call or email us. We're based out of Tennessee in the USA.
                <p><a href="tel:+14232818591" class="button button-primary" style="text-decoration: none; color: #fff; font-weight: 700; text-transform: uppercase; background-color: #333; border-color: #333;" target="_blank">Call Us</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://www.polyplugins.com/contact/" class="button button-primary" style="text-decoration: none; color: #fff; font-weight: 700; text-transform: uppercase; background-color: #333; border-color: #333;" target="_blank">Email Us</a></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  <?php
  }

  /**
   * Sanitize Options
   *
   * @param  array $input Array of option inputs
   * @return array $sanitary_values Array of sanitized options
   */
  public function sanitize($input) {
		$sanitary_values = array();

    if (isset($input['enabled']) && $input['enabled']) {
      $sanitary_values['enabled'] = $input['enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['enabled'] = false;
    }

    if (isset($input['characters']) && is_numeric($input['characters'])) {
			$sanitary_values['characters'] = sanitize_text_field($input['characters']);
		}

    if (isset($input['max_characters']) && is_numeric($input['max_characters'])) {
			$sanitary_values['max_characters'] = sanitize_text_field($input['max_characters']);
		}

    if (isset($input['typing_delay']) && is_numeric($input['typing_delay'])) {
			$sanitary_values['typing_delay'] = sanitize_text_field($input['typing_delay']);
		}

    if (isset($input['orders']['enabled']) && $input['orders']['enabled']) {
      $sanitary_values['orders']['enabled'] = $input['orders']['enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['orders']['enabled'] = false;
    }

    if (isset($input['orders']['batch']) && is_numeric($input['orders']['batch'])) {
			$sanitary_values['orders']['batch'] = sanitize_text_field($input['orders']['batch']);
		}

    if (isset($input['orders']['result_limit']) && is_numeric($input['orders']['result_limit'])) {
			$sanitary_values['orders']['result_limit'] = sanitize_text_field($input['orders']['result_limit']);
		}

    return $sanitary_values;
  }
  
  /**
   * Add setting link
   *
   * @return void
   */
  public function add_setting_link($links) {
    $settings_link = '<a href="options-general.php?page=admin-instant-search">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  public function advanced_repo_search() {
    add_submenu_page(
      'plugins.php',
      'Advanced Search',
      'Advanced Search',
      'manage_options',
      'repo-advanced-search',
      array($this, 'repo_advanced_search_page')
    );
  }

  public function repo_advanced_search_page() {
    include plugin_dir_path($this->plugin) . 'templates/repo-advanced-search.php';
  }


}
<?php

/**
 * Plugin Name: Instant Order Search for WooCommerce
 * Description: Search WooCommerce orders fast without having to wait for the page to load between searches.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Poly Plugins
 * Author URI: https://www.polyplugins.com
 * Requires Plugins: woocommerce
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace PolyPlugins;

use WC_Order_Refund;

/**
 * To-Do
 * 
 * Potentially add caching
 * Add product count to cron
 * Show all orders when initializing instant order search.
 * Fix weird scrollbar bug, probably related to viewport height
 * 
 */

register_activation_hook(__FILE__, array(__NAMESPACE__ . '\Instant_Order_Search', 'activation'));
register_deactivation_hook(__FILE__, array(__NAMESPACE__ . '\Instant_Order_Search', 'deactivation'));

class Instant_Order_Search {

  const OPTIONS_NAME                = 'instant_order_search_options_poly_plugins';
  const OPTIONS_NAME_INDEX          = 'instant_order_search_index_poly_plugins';
  const OPTIONS_NAME_INDEX_PROGRESS = 'instant_order_search_index_progress_poly_plugins';

  private $options;

  public function init() {
    add_action('admin_menu', array($this, 'register_settings_page'), 999);
    add_action('admin_init', array($this, 'settings_page_init'));
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
    add_action('wp_ajax_instant_order_search', array($this, 'search_orders'));
    add_action('wp_ajax_nopriv_instant_order_search', array($this, 'search_orders'));
    add_action('build_order_index', array($this, 'build_order_index'));
    add_action('woocommerce_thankyou', array($this, 'add_new_order_to_index'));
    add_action('woocommerce_order_status_changed', array($this, 'update_order_status_to_index'), 10, 4);
    add_action('before_woocommerce_init', array($this, 'add_hpos_compatibility'));
    add_action('admin_notices', array($this, 'indexing_notice'));
  }

  /**
   * Register settings page 
   *
   * @return void
   */
  public function register_settings_page() {
    add_submenu_page(
			'woocommerce',                        // Parent slug (same as main menu)
			'Instant Order Search',               // Page title
			'Instant Order Search',               // Menu title
			'manage_options',                     // Capability
			'instant-order-search-settings',      // Menu slug
			array($this, 'create_settings_page'), // Function to display the content
		);
	}
  
  /**
   * Adds HPOS compatibility flag
   *
   * @return void
   */
  public function add_hpos_compatibility() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
      \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
  }
  
  /**
   * Displays a notice on order page when indexing orders
   *
   * @return void
   */
  public function indexing_notice() {
    $screen = get_current_screen();

    // Check if we're on the orders screen
    if ( $screen->post_type === 'shop_order' && ( $screen->base === 'edit' || $screen->id === 'woocommerce_page_wc-orders' ) ) {
      $is_indexing = get_option(self::OPTIONS_NAME_INDEX_PROGRESS);

      if ($is_indexing) {
        $class   = 'notice notice-info';
        $message = __('Instant order search is unavailable as it is currently indexing orders.', 'instant-order-search');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
      }
    }
  }
  
  /**
   * Create settings page
   *
   * @return void
   */
  public function create_settings_page() {
		$this->options = get_option(self::OPTIONS_NAME);
		?>

		<div class="wrap">
			<h2>Instant Order Search Settings</h2>
			<p></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields('instant_order_search_option_group');
					do_settings_sections('instant-order-search-settings');
					submit_button();
				?>
			</form>
		</div>
	<?php
	}
  
  /**
   * Init settings page
   *
   * @return void
   */
  public function settings_page_init() {
		$this->options = get_option(self::OPTIONS_NAME);
		
		register_setting(
			'instant_order_search_option_group', // option_group
			self::OPTIONS_NAME,                  // option_name
			array($this, 'sanitize')             // sanitize_callback
		);

		// Batch Size
		add_settings_section(
			'setting_section',              // id
			'',                             // title
			array(),                        // callback
			'instant-order-search-settings' // page
		);

		add_settings_field(
			'batch_size',                        // id
			'Batch Size',                        // title
			array($this, 'batch_size_callback'), // callback
			'instant-order-search-settings',     // page
			'setting_section'                    // section
		);

		// Reindex
		add_settings_section(
			'setting_section',              // id
			'',                             // title
			array(),                        // callback
			'instant-order-search-settings' // page
		);

		add_settings_field(
			'reindex',                        // id
			'Reindex',                        // title
			array($this, 'reindex_callback'), // callback
			'instant-order-search-settings',  // page
			'setting_section'                 // section
		);
	}
  
  /**
   * Batch size callback
   *
   * @return void
   */
  public function batch_size_callback() {
    $batch_size = $this->get_batch_size();
    ?>
			<input class="regular-text" type="number" name="<?php echo esc_html(self::OPTIONS_NAME); ?>[batch_size]" id="batch_size" value="<?php echo esc_html($batch_size); ?>">
      <p>When indexing orders, how many orders do you want to process per minute? Lower is less strain on your server, but will take longer if you have a lot of orders.
    <?php
	}
  
  /**
   * Reindex callback
   *
   * @return void
   */
  public function reindex_callback() {
    $this->options = get_option(self::OPTIONS_NAME);
    $reindex        = isset($this->options['reindex']) ? $this->options['reindex'] : '';
    ?>
			<input type="checkbox" name="<?php echo esc_html(self::OPTIONS_NAME); ?>[reindex]" id="reindex" <?php checked(1, $reindex, true); ?> /> Yes
      <p>This will clear the current order search index and reindex all orders. Generally you should not check this, unless instructed to by support.</p>
    <?php
	}
  
  /**
   * Sanitize options
   *
   * @param  mixed $input
   * @return void
   */
  public function sanitize($input) {
    $options = $this->get_options();
    
		if (isset($input['batch_size']) && $input['batch_size']) {
			$options['batch_size'] = is_numeric($input['batch_size']) ? (int) $input['batch_size'] : 100;
		}
    
		if (isset($input['reindex']) && $input['reindex']) {
			if ($input['reindex'] == 'on') {
        $this->reindex_orders();
      }
		}

    return $options;
  }
  
  /**
   * Admin enqueue
   *
   * @return void
   */
  public function admin_enqueue() {
    $screen = get_current_screen();

    // Check if we're on the orders screen
    if ( $screen->post_type === 'shop_order' && ( $screen->base === 'edit' || $screen->id === 'woocommerce_page_wc-orders' ) ) {
      $is_indexing = get_option(self::OPTIONS_NAME_INDEX_PROGRESS);

      if (!$is_indexing) {
        wp_enqueue_style('instant-order-search', plugins_url('/css/admin.css', __FILE__), array(), filemtime(plugin_dir_path(dirname(__FILE__)) . dirname(plugin_basename(__FILE__))  . '/css/admin.css'));
        wp_enqueue_script('instant-order-search', plugins_url('/js/admin.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(dirname(__FILE__)) . dirname(plugin_basename(__FILE__))  . '/js/admin.js'), true);
        wp_localize_script('instant-order-search', 'instantOrderSearch', array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('instant_order_search_nonce'),
        ));
      }
    }
  }
  
  /**
   * Build order index
   *
   * @return void
   */
  public function build_order_index() {
    $batch_size = $this->get_batch_size();

    // Get the current offset from the progress option or start from 0.
    $offset = get_option(self::OPTIONS_NAME_INDEX_PROGRESS, 0);

    // Get the next batch of orders.
    $orders = $this->get_orders_batch($offset);

    if (empty($orders)) {
      // If no orders are returned, we are done with the batching.
      $this->clear_cron_event();

      return;
    }

    // Add the batch to the index.
    $this->add_orders_to_index($orders);

    // Update the offset for the next batch.
    update_option(self::OPTIONS_NAME_INDEX_PROGRESS, $offset + $batch_size);
  }
  
  /**
   * Add new order to index
   *
   * @param  int  $order_id The order id
   * @return void
   */
  public function add_new_order_to_index($order_id) {
    $order = wc_get_order($order_id);

    $this->add_order_to_index($order);
  }
  
  /**
   * Update the order status within the index
   *
   * @param  int    $order_id   The order id
   * @param  string $old_status The old order status
   * @param  string $new_status The new order status
   * @param  object $order      The order object
   * @return void
   */
  public function update_order_status_to_index($order_id, $old_status, $new_status, $order) {
    // Get the existing order index from options.
    $order_index = $this->get_index();

    if (isset($order_index[$order_id])) {
      $order_index[$order_id]['order_status'] = $new_status;

      update_option(self::OPTIONS_NAME_INDEX, $order_index);
    }
  }
  
  /**
   * Search orders
   *
   * @return void
   */
  public function search_orders() {
    check_ajax_referer('instant_order_search_nonce', 'nonce');

    $order_index = $this->get_index();

    if (!empty($order_index)) {
      wp_send_json_success($order_index);
    } else {
      wp_send_json_error('No orders found');
    }
  }
  
  /**
   * Get orders batch
   *
   * @param  int   $offset     The offset
   * @return array $orders_ids The order ids
   */
  private function get_orders_batch($offset) {
    $batch_size = $this->get_batch_size();

    $args = array(
      'orderby' => 'date',
      'order'   => 'ASC',
      'limit'   => $batch_size,
      'offset'  => $offset,
    );

    $orders = wc_get_orders($args);

    return $orders;
  }
    
  /**
   * Add the orders to the index
   *
   * @param  array $orders Orders to add to the index
   * @return void
   */
  private function add_orders_to_index($orders) {
    // Get the existing order index from options.
    $order_index = $this->get_index();
    foreach ($orders as $order) {
      $order_number = $order->get_id();

      if ($order instanceof WC_Order_Refund) {
        $order = wc_get_order($order->get_parent_id());
      }

      // Only add orders that are not already in the index.
      if (!key_exists($order_number, $order_index)) {
        $billing_name    = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $billing_address = $order->get_billing_address_1() . ', ' . $order->get_billing_city() . ', ' . $order->get_billing_state() . ' ' . $order->get_billing_postcode();
        $billing_email   = $order->get_billing_email();
        $billing_phone   = $order->get_billing_phone();
        $order_total     = $order->get_total();
        $order_status    = $order->get_status();

        $order_index[$order_number] = array(
          'order_number'    => is_numeric($order_number) ? (int) $order_number : '',
          'name'            => $billing_name ? sanitize_text_field($billing_name) : '',
          'billing_address' => $billing_address ? sanitize_text_field($billing_address) : '',
          'billing_email'   => $billing_email ? sanitize_email($billing_email) : '',
          'billing_phone'   => $billing_phone ? sanitize_text_field($billing_phone) : '',
          'order_total'     => is_numeric($order_total) ? sanitize_text_field(wc_price($order_total)) : '',
          'order_status'    => $order_status ? sanitize_text_field($order_status) : '',
        );
      }
    }

    // Store the updated index in options.
    update_option(self::OPTIONS_NAME_INDEX, $order_index);
  }
    
  /**
   * Add the orders to the index
   *
   * @param  object $order The order to add to the index
   * @return void
   */
  private function add_order_to_index($order) {
    // Get the existing order index from options.
    $order_index = $this->get_index();

    $order_number = $order->get_id();

    if ($order instanceof WC_Order_Refund) {
      $order = wc_get_order($order->get_parent_id());
    }

    // Only add orders that are not already in the index.
    if (!key_exists($order_number, $order_index)) {
      $billing_name    = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
      $billing_address = $order->get_billing_address_1() . ', ' . $order->get_billing_city() . ', ' . $order->get_billing_state() . ' ' . $order->get_billing_postcode();
      $billing_email   = $order->get_billing_email();
      $billing_phone   = $order->get_billing_phone();
      $order_total     = $order->get_total();
      $order_status    = $order->get_status();

      $order_index[$order_number] = array(
        'order_number'    => is_numeric($order_number) ? (int) $order_number : '',
        'name'            => $billing_name ? sanitize_text_field($billing_name) : '',
        'billing_address' => $billing_address ? sanitize_text_field($billing_address) : '',
        'billing_email'   => $billing_email ? sanitize_email($billing_email) : '',
        'billing_phone'   => $billing_phone ? sanitize_text_field($billing_phone) : '',
        'order_total'     => is_numeric($order_total) ? sanitize_text_field(wc_price($order_total)) : '',
        'order_status'    => $order_status ? sanitize_text_field($order_status) : '',
      );
    }

    // Store the updated index in options.
    update_option(self::OPTIONS_NAME_INDEX, $order_index);
  }
  
  /**
   * Reindex orders
   *
   * @return void
   */
  private function reindex_orders() {
    $this->clear_index();

    if (!wp_next_scheduled('build_order_index')) {
      wp_schedule_event(time(), 'every_minute', 'build_order_index');
    }
  }

  /**
   * Clear the cron event and the progress offset once processing is complete.
   *
   * @return void
   */
  private function clear_cron_event() {
    delete_option(self::OPTIONS_NAME_INDEX_PROGRESS);

    wp_clear_scheduled_hook('build_order_index');
  }
  
  /**
   * Get the index
   *
   * @return array $index The order index
   */
  private function get_index() {
    $index = get_option(self::OPTIONS_NAME_INDEX, array());

    return $index;
  }
  
  /**
   * Clear the index
   *
   * @return void
   */
  private function clear_index() {
    update_option(self::OPTIONS_NAME_INDEX, array());
  }

  /**
   * Get options
   *
   * @return mixed $options The options
   */
  private function get_options() {
    $options = get_option(self::OPTIONS_NAME);

    return $options;
  }
  
  /**
   * Get option
   *
   * @param  mixed $option The option to fetch
   * @return mixed $option The option
   */
  private function get_option($option) {
    $options = $this->get_options();
    $option  = isset($options[$option]) ? $options[$option] : false;

    return $option;
  }
  
  /**
   * Get batch size
   *
   * @return int $batch_size The batch size
   */
  private function get_batch_size() {
    $options    = $this->get_option('batch_size');
    $batch_size = $options ? $options : 100;

    return $batch_size;
  }
  
  /**
   * Activation
   *
   * @return void
   */
  public static function activation() {
    $options = get_option(self::OPTIONS_NAME);

    // Don't reindex if reactivated
    if ($options) {
      if (!wp_next_scheduled('build_order_index')) {
        wp_schedule_event(time(), 'every_minute', 'build_order_index');
      }
    }
  }
    
  /**
   * Deactivation
   *
   * @return void
   */
  public static function deactivation() {
    wp_clear_scheduled_hook('build_order_index');
  }

}

$instant_order_search = new Instant_Order_Search();
$instant_order_search->init();

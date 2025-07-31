<?php

namespace PolyPlugins\Admin_Instant_Search\Backend;

use PolyPlugins\Admin_Instant_Search\TNTSearch;
use PolyPlugins\Admin_Instant_Search\Utils;
use WP_REST_Request;
use WP_REST_Response;

class API {
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    add_action('rest_api_init', array($this, 'add_endpoints'));
  }

  /**
	 * Add endpoint for webhooks to connect to
	 *
	 * @return void
	 */
	public function add_endpoints() {
    $orders         = Utils::get_option('orders');
    $orders_enabled = isset($orders['enabled']) ? $orders['enabled'] : 0;

    if ($orders_enabled) {
      register_rest_route(
        'admin-instant-search/v1',
        '/orders/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_orders'),
          'permission_callback' => array($this, 'check_permissions')
        )
      );
    }
	}
  
  /**
   * Get orders
   *
   * @param  mixed $request
   * @return void
   */
  public function get_orders(WP_REST_Request $request) {
		$options          = Utils::get_option('orders');
		$result_limit     = isset($options['result_limit']) ? $options['result_limit'] : 100;
		$max_characters   = isset($options['max_characters']) ? $options['max_characters'] : 100;
    $get_search_query = $request->get_param('search');
    $search_query     = $get_search_query ? sanitize_text_field($get_search_query) : '';

    if (empty($search_query)) {
      return new WP_REST_Response(array(
        'error' => 'Search query is required.'
      ), 400);
    }

    if (strlen($search_query) > $max_characters) {
      return new WP_REST_Response(array(
        'error' => 'Too many characters in search query.'
      ), 400);
    }

    // Generate a unique cache key for this search
    $cache_key = 'admin_instant_search_orders_' . md5($search_query);

    // Check if results exist in WordPress Object Cache
    $cached_results = wp_cache_get($cache_key, 'admin_instant_search_api');

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    // Get TNTSearch instance
    $tnt = TNTSearch::get_instance()->tnt();
    
    $index_name = Utils::get_index_name('shop_order');
    
    $tnt->selectIndex($index_name);
    $tnt->fuzziness = true;

    // Perform the search
    $results = $tnt->search($search_query, $result_limit);
    $result_ids = isset($results['ids']) ? array_map('absint', $results['ids']) : array();

    if (empty($result_ids)) {
      return new WP_REST_Response([], 200); // No results found
    }

    $orders_data = array();

    foreach ($result_ids as $order_id) {
      $order = wc_get_order($order_id);

      $orders_data[] = array(
        'id'                  => $order->get_id(),
        'order_number'        => sanitize_text_field($order->get_order_number()),
        'order_date'          => sanitize_text_field($order->get_date_created()->date('Y-m-d H:i:s')),
        'billing_first_name'  => sanitize_text_field($order->get_billing_first_name()),
        'billing_last_name'   => sanitize_text_field($order->get_billing_last_name()),
        'billing_address_1'   => sanitize_text_field($order->get_billing_address_1()),
        'billing_address_2'   => sanitize_text_field($order->get_billing_address_2()),
        'billing_city'        => sanitize_text_field($order->get_billing_city()),
        'billing_email'       => sanitize_email($order->get_billing_email()),
        'billing_phone'       => sanitize_text_field($order->get_billing_phone()),
        'shipping_first_name' => sanitize_text_field($order->get_shipping_first_name()),
        'shipping_last_name'  => sanitize_text_field($order->get_shipping_last_name()),
        'shipping_address_1'  => sanitize_text_field($order->get_shipping_address_1()),
        'shipping_address_2'  => sanitize_text_field($order->get_shipping_address_2()),
        'shipping_city'       => sanitize_text_field($order->get_shipping_city()),
        'order_status'        => sanitize_text_field($order->get_status()),
        'total'               => sanitize_text_field($order->get_total()),
        'origin'              => sanitize_text_field($order->get_meta('_wc_order_attribution_utm_source')),
      );
    }

    wp_cache_set($cache_key, $orders_data, 'admin_instant_search_api', 600);

    return new WP_REST_Response($orders_data, 200);
  }

  public function check_permissions() {
    return current_user_can('manage_woocommerce');
  }

}
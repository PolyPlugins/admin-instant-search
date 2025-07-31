<?php

namespace PolyPlugins\Admin_Instant_Search\Backend;

use PolyPlugins\Admin_Instant_Search\TNTSearch;
use PolyPlugins\Admin_Instant_Search\Utils;
use WP_Query;

class Background_Worker {

  private $tnt;
  private $options;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct() {
    $this->tnt     = TNTSearch::get_instance()->tnt();
    $this->options = Utils::get_options();
  }
  
  /**
   * init
   *
   * @return void
   */
  public function init() {
    add_action('admin_instant_search_background_worker', array($this, 'background_worker'));
    add_action('cron_schedules', array($this, 'add_cron_schedules'));
  }

  /**
   * Background worker
   *
   * @return void
   */
  public function background_worker() {
    $enabled               = Utils::get_option('enabled');
    $is_missing_extensions = Utils::is_missing_extensions();

    // Don't continue if missing extensions
    if ($is_missing_extensions) {
      return;
    }

    if (!$enabled) {
      return;
    }

    $this->indexer();
  }
  
  /**
   * Indexer
   *
   * @return void
   */
  public function indexer() {
    if (class_exists('WooCommerce')) {
      $this->maybe_index_orders('shop_order');
    }
  }

  /**
   * Add cron schedules
   * @param  array $schedules The schedules array
   * @return array $schedules The schedules array
   */
  public function add_cron_schedules($schedules) {
    if (!isset($schedules['every_minute'])) {
      $schedules['every_minute'] = array(
        'interval' => 60,
        'display'  => __('Every Minute', 'admin-instant-search')
     );
    }
    
    return $schedules;
  }

  /**
   * Maybe index
   *
   * @return void
   */
  public function maybe_index_orders($post_type) {
    $type    = $post_type . 's';
    $options = Utils::get_option('orders');
    $enabled = isset($options['enabled']) ? $options['enabled'] : 1;

    $index                = Utils::get_index($type);
    $is_indexing_complete = isset($index['complete']) ? true : false;

    if ($is_indexing_complete) {
      return;
    }

    if (!$enabled) {
      Utils::update_index($type, 'complete', true);
      return;
    }

    $index_name = Utils::get_index_name($post_type);

    if (!$index) {
      $this->tnt->createIndex($index_name);
    }

    $this->tnt->selectIndex($index_name);

    $progress = isset($index['progress']) ? $index['progress'] : 1;
    $batch    = isset($this->options[$type]['batch']) ? $this->options[$type]['batch'] : 100;

    $args = array(
      'limit'     => $batch,
      'offset'    => $progress - 1,
      'orderby'   => 'date',
      'order'     => 'ASC',
      'return'    => 'objects',
    );

    $orders = wc_get_orders($args);

    if (!empty($orders)) {
      $index = $this->tnt->getIndex();

      foreach ($orders as $order) {
        $order_id = $order->get_id();

        $args = array(
          'id'                  => intval($order_id),
          'order_number'        => sanitize_text_field($order->get_order_number()),
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
        );

        $index->insert($args);
        $progress++;

        Utils::update_index($type, 'progress', $progress);
      }
    } else {
      Utils::update_index($type, 'complete', true);
    }
  }

}
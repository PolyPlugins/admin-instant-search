<?php

namespace PolyPlugins\Admin_Instant_Search\Backend;

use PolyPlugins\Admin_Instant_Search\TNTSearch;
use PolyPlugins\Admin_Instant_Search\Utils;

class Index_Updater {

  private $plugin;
  private $version;
  private $plugin_dir_url;
  private $tnt;

  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->plugin         = $plugin;
    $this->version        = $version;
    $this->plugin_dir_url = $plugin_dir_url;
    $this->tnt            = TNTSearch::get_instance()->tnt();
  }

  public function init() {
    $options = Utils::get_option('orders');
    $enabled = isset($options['enabled']) ? $options['enabled'] : 1;

    if ($enabled) {
      add_action('woocommerce_thankyou', array($this, 'add_new_order_to_index'));
    }
  }

  /**
   * Update or add post/page/product/download to the index
   *
   * @param int     $post_id
   * @param WP_Post $post
   * @param bool    $update
   * @return void
   */
  public function update_index($order_id) {
    $order = wc_get_order($order_id);

    // Select or create the appropriate index
    $index_name = Utils::get_index_name('shop_order');

    try {
      $this->tnt->selectIndex($index_name);
    } catch (\TeamTNT\TNTSearch\Exceptions\IndexNotFoundException $e) {
      $this->tnt->createIndex($index_name);
      $this->tnt->selectIndex($index_name);
    }

    $index = $this->tnt->getIndex();
    $index->disableOutput = true;

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
  }
}

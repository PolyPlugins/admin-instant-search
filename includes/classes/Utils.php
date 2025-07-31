<?php

namespace PolyPlugins\Admin_Instant_Search;

use PolyPlugins\Admin_Instant_Search\TNTSearch;

class Utils {

  /**
   * Get admin instant search options
   *
   * @return array $options The admin instant search options
   */
  public static function get_options() {
    $options = get_option('admin_instant_search_settings_polyplugins');

    return $options;
  }
  
  /**
   * Get admin instant search option from options array
   *
   * @param  string $option The option to retrieve from options
   * @return mixed  $option The retrieved option value
   */
  public static function get_option($option) {
    $options = self::get_options();
    $option  = isset($options[$option]) ? $options[$option] : false;

    return $option;
  }
  
  /**
   * Update an option
   *
   * @param  string $option The option name
   * @param  mixed  $value  The option value
   * @return void
   */
  public static function update_option($option, $value) {
    $options          = self::get_options();
    $options[$option] = $value;

    update_option('admin_instant_search_settings_polyplugins', $options);
  }

  /**
   * Get admin instant search indexes
   *
   * @return array $options The admin instant search indexes options
   */
  public static function get_indexes() {
    $options = get_option('admin_instant_search_indexes_polyplugins');

    return $options ? $options : array();
  }
  
  /**
   * Get admin instant search index from options array
   *
   * @param  string $option The option to retrieve from options
   * @return mixed  $option The retrieved option value
   */
  public static function get_index($option) {
    $options = self::get_indexes();
    $option  = isset($options[$option]) ? $options[$option] : false;

    return $option;
  }
  
  /**
   * Update an index
   *
   * @param  string $index  The index name
   * @param  string $option The option name
   * @param  mixed  $value  The option value
   * @return void
   */
  public static function update_index($index, $option, $value) {
    $options                  = self::get_indexes();
    $options[$index][$option] = $value;

    update_option('admin_instant_search_indexes_polyplugins', $options);
  }
  
  /**
   * Check if currently indexing
   *
   * @return bool $is_indexing The indexing status
   */
  public static function is_indexing() {
    $allowed_post_types = self::get_allowed_post_types();
    $is_indexing        = false;

    foreach($allowed_post_types as $allowed_post_type) {
      $type        = $allowed_post_type . 's';
      $index       = self::get_index($type);
      $is_indexing = isset($index['complete']) ? false : true;

      if ($is_indexing) {
        return $is_indexing;
      }
    }

    return $is_indexing;
  }
  
  /**
   * Flush DB index
   *
   * @param  string $post_type The post type
   * @return void
   */
  public static function delete_db_index($post_type) {
    $tnt        = TNTSearch::get_instance()->tnt();
    $index_name = self::get_index_name($post_type);

    $tnt->selectIndex($index_name);
    $tnt->engine->flushIndex($index_name);
  }
  
  /**
   * Reindex
   *
   * @return void
   */
  public static function reindex() {
    $allowed_post_types = self::get_allowed_post_types();

    // Reset indexing progress
    foreach ($allowed_post_types as $allowed_post_type) {
      self::delete_db_index($allowed_post_type);
    }

    delete_option('admin_instant_search_indexes_polyplugins');
  }
  
  /**
   * Send success json
   *
   * @param  string $message The message to send
   * @param  int    $code    The status code
   * @return void
   */
  public static function send_success($message, $code = 200) {
    $message = $message ? sanitize_text_field($message) : __('Success', 'admin-instant-search');
    $code    = is_numeric($code) ? (int) $code : 200;

    wp_send_json_success(array(
      'message' => sanitize_text_field($message),
      'status' => $code
    ), $code);
  }
  
  /**
   * Send error json
   *
   * @param  mixed $message
   * @param  mixed $code
   * @return void
   */
  public static function send_error($message, $code = 400) {
    $message = $message ? sanitize_text_field($message) : __('Error', 'admin-instant-search');
    $code    = is_numeric($code) ? (int) $code : 400;

    wp_send_json_error(array(
      'message' => sanitize_text_field($message),
      'status' => $code
    ), $code);
  }
  
  /**
   * Get allowed post types
   *
   * @return array $allowed_post_types The allowed post types
   */
  public static function get_allowed_post_types() {
    $allowed_post_types = array();

    if (class_exists('WooCommerce')) {
      $allowed_post_types[] = 'shop_order';
    }

    return $allowed_post_types;
  }
  
  /**
   * Get index name
   *
   * @param  string $post_type  The type of index
   * @return string $index      The index name
   */
  public static function get_index_name($post_type) {
    $type          = $post_type . 's';
    $index_name    = '';

    $index_name = 'wp_ss_' . $type;

    return $index_name;
  }
  
  /**
   * Checks for any missing extensions
   *
   * @return mixed $is_missing_extension Array of missing extensions or false
   */
  public static function is_missing_extensions() {
    $missing_extensions = array();

    $extensions = array(
      'PDO',
      'mbstring'
    );

    foreach ($extensions as $extension) {
      if (!extension_loaded($extension)) {
        $missing_extensions[] = $extension;
      }
    }

    $is_missing_extensions = $missing_extensions ? $missing_extensions : false;

    return $is_missing_extensions;
  }

  /**
   * Convert Hex color to RGBA
   *
   * @param  mixed $hex
   * @param  mixed $alpha
   * @return void
   */
  public static function hex_to_rgba($hex, $alpha = null) {
    // Remove the '#' if present
    $hex = ltrim($hex, '#');
    
    // Get the red, green, and blue values
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // If alpha is provided, return rgba format, otherwise return rgb
    if ($alpha !== null) {
      if ($alpha > 1) {
        $alpha = $alpha / 100; // Convert percentage to decimal
      }

      return "rgba($r, $g, $b, $alpha)";
    } else {
      return "rgb($r, $g, $b)";
    }
  }

}
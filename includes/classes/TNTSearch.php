<?php

namespace PolyPlugins\Admin_Instant_Search;

use TeamTNT\TNTSearch\TNTSearch as TNTSearchEngine;

class TNTSearch {
  
  private $index_path;
  private $tnt;
  private static $instance = null;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct(){
    $this->init();
  }
    
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    $is_missing_extensions = Utils::is_missing_extensions();

    // Don't continue if missing extensions
    if ($is_missing_extensions) {
      return;
    }

    $this->init_tnt();
  }
  
  /**
   * Init TNT
   *
   * @return void
   */
  public function init_tnt() {
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASSWORD')) {
      return;
    }

    $this->tnt = new TNTSearchEngine;
    
    $this->tnt->loadConfig(array(
      'driver'    => 'mysql',
      'engine'    => \TeamTNT\TNTSearch\Engines\MysqlEngine::class,
      'host'      => DB_HOST,
      'database'  => DB_NAME,
      'username'  => DB_USER,
      'password'  => DB_PASSWORD,
      'storage'   => $this->index_path,
      'stemmer'   => \TeamTNT\TNTSearch\Stemmer\PorterStemmer::class,
      'tokenizer' => \TeamTNT\TNTSearch\Support\Tokenizer::class
    ));
  }
  
  /**
   * Get TNTSearch
   *
   * @return object
   */
  public function tnt() {
    return $this->tnt;
  }
  
  /**
   * Get instance
   *
   * @return object
   */
  public static function get_instance() {
    if (self::$instance === null) {
      self::$instance = new self();
    }

    return self::$instance;
  }

}

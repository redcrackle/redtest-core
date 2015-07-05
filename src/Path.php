<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 4/16/15
 * Time: 5:22 PM
 */

namespace RedTest\core;


class Path {

  /**
   * @var string
   */
  private $path;

  /**
   * Default constructor.
   *
   * @param string $path
   *   Path.
   */
  public function __construct($path) {
    $this->path = $path;
  }

  /**
   * Returns the page callback function name.
   *
   * @return null|string
   *   Page callback function if one exists, NULL otherwise.
   */
  public function getPageCallback() {
    if ($router_item = self::getItem($this->path)) {
      return $router_item['page_callback'];
    }

    return NULL;
  }

  /**
   * Returns input argument names that are passed to the page callback function.
   *
   * @return bool|array
   *   An array of arguments passed to the callback function.
   */
  public function getPageArguments() {
    if ($router_item = self::getItem($this->path)) {
      if (is_array($router_item['page_arguments'])) {
        return $router_item['page_arguments'];
      }
      return unserialize($router_item['page_arguments']);
    }

    return array();
  }

  /**
   * Returns access callback function for a particular path.
   *
   * @return null|string
   *   Access callback function if one exists and NULL otherwise.
   */
  public function getAccessCallback() {
    if ($router_item = self::getItem($this->path)) {
      return $router_item['access_callback'];
    }

    return NULL;
  }

  /**
   * Returns an array of access argument names that are being passed to the
   * access callback function.
   *
   * @return array
   *   Array of access argument names.
   */
  public function getAccessArguments() {
    if ($router_item = self::getItem($this->path)) {
      return unserialize($router_item['access_arguments']);
    }

    return array();
  }

  /**
   * Returns whether the currently logged in user has access to the specified
   * path.
   *
   * @return bool
   *   TRUE if the user has access and FALSE otherwise.
   */
  public function hasAccess() {
    if ($router_item = self::getItem($this->path)) {
      if ($router_item['access']) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns the page title for the specified path.
   *
   * @return null|string
   *   Title if one exists and NULL otherwise.
   */
  public function getTitle() {
    if ($router_item = self::getItem($this->path)) {
      if (!empty($router_item['title'])) {
        return $router_item['title'];
      }
    }

    return NULL;
  }

  public function getItem() {
    //drupal_static_reset('menu_get_item');
    $router_item = menu_get_item($this->path);

    //drupal_static_reset('menu_get_item');

    return $router_item;
  }

  /**
   * Returns blocks present on a webpage.
   *
   * @param null|string $region
   *   Region for which the blocks need to be filtered. If this argument is
   *   NULL, then blocks for all regions on the page are returned.
   *
   * @return array
   *   If $region is NULL, then the output format will be:
   *   array(
   *     'region 1' => array(
   *       'block 1' => array(...),
   *       'block 2' => array(...),
   *     ),
   *     'region 2' => array(
   *       'block 3' => array(...),
   *     ),
   *   )
   *   If $region is not NULL, then the output format will be:
   *   array(
   *     'block 1' => array(...),
   *     'block 2' => array(...),
   *   )
   */
  public function getBlocks($region = NULL) {
    if (!module_exists('block')) {
      return array();
    }

    $path = $this->path;
    if ($this->path == '<front>') {
      $path = variable_get('site_frontpage', 'node');
    }

    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast = &drupal_static('Menu::' . __FUNCTION__);
    }
    $pages = &$drupal_static_fast;

    if (!isset($pages[$path])) {
      $pages[$path] = array();

      // This is a hack that needed to be done otherwise function
      // menu_tree_page_data() in menu.inc file calls menu_get_item() with NULL
      // argument. This means that menu_get_item() takes path as $_GET['q'].
      // There doesn't seem to be any other way to solve this.
      $original_query_param = $_GET['q'];
      $_GET['q'] = $path;

      block_page_build($pages[$path]);

      $_GET['q'] = $original_query_param;

      // Function menu_tree() in menu.inc caches the blocks by page. So its
      // static cache needs to be reset before going to any other page.
      drupal_static_reset('menu_tree');
    }

    if (!is_null($region)) {
      return array_key_exists(
        $region,
        $pages[$path]
      ) ? $pages[$path]['region'] : array();
    }

    return $pages[$path];
  }
}
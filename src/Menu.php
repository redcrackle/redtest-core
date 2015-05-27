<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 4/16/15
 * Time: 5:22 PM
 */

namespace RedTest\core;


class Menu {

  /**
   * Returns the page callback function name.
   *
   * @param string $path
   *   Path where the page resides.
   *
   * @return null|string
   *   Page callback function if one exists, NULL otherwise.
   */
  public static function getPageCallback($path) {
    if ($router_item = self::getItem($path)) {
      return $router_item['page_callback'];
    }

    return NULL;
  }

  /**
   * Returns input argument names that are passed to the page callback function.
   *
   * @param string $path
   *   Path where the page resides.
   *
   * @return bool|array
   *   An array of arguments passed to the callback function.
   */
  public static function getPageArguments($path) {
    if ($router_item = self::getItem($path)) {
      return $router_item['page_arguments'];
    }

    return array();
  }

  /**
   * Returns access callback function for a particular path.
   *
   * @param string $path
   *   Path where the page resides.
   *
   * @return null|string
   *   Access callback function if one exists and NULL otherwise.
   */
  public static function getAccessCallback($path) {
    if ($router_item = self::getItem($path)) {
      return $router_item['access_callback'];
    }

    return NULL;
  }

  /**
   * Returns an array of access argument names that are being passed to the
   * access callback function.
   *
   * @param string $path
   *   Path where the page resides.
   *
   * @return array
   *   Array of access argument names.
   */
  public static function getAccessArguments($path) {
    if ($router_item = self::getItem($path)) {
      return unserialize($router_item['access_arguments']);
    }

    return array();
  }

  /**
   * Returns whether the currently logged in user has access to the specified
   * path.
   *
   * @param string $path
   *   Path where the page resides.
   *
   * @return bool
   *   TRUE if the user has access and FALSE otherwise.
   */
  public static function hasAccess($path) {
    if ($router_item = self::getItem($path)) {
      if ($router_item['access']) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns the page title for the specified path.
   *
   * @param string $path
   *   Path where the page resides.
   *
   * @return null|string
   *   Title if one exists and NULL otherwise.
   */
  public static function getTitle($path) {
    if ($router_item = self::getItem($path)) {
      if (!empty($router_item['title'])) {
        return $router_item['title'];
      }
    }

    return NULL;
  }

  public static function getItem($path) {
    //drupal_static_reset('menu_get_item');
    $router_item = menu_get_item($path);

    //drupal_static_reset('menu_get_item');

    return $router_item;
  }

  /**
   * Returns blocks present on a webpage.
   *
   * @param $path
   *   URL for which the blocks need to be returned.
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
  public static function getBlocks($path, $region = NULL) {
    if (!module_exists('block')) {
      return array();
    }

    if ($path == '<front>') {
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
      // argument. This means that menu_get_item() takes path as $_GET['q']. There
      // doesn't seem to be any other way to solve this.
      $original_query_param = $_GET['q'];
      $_GET['q'] = $path;

      block_page_build($pages[$path]);

      $_GET['q'] = $original_query_param;
    }

    if (!is_null($region)) {
      return array_key_exists($region, $pages[$path]) ? $pages[$path]['region'] : array();
    }

    return $pages[$path];
  }
}
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
    if ($router_item = menu_get_item($path)) {
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
    if ($router_item = menu_get_item($path)) {
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
    if ($router_item = menu_get_item($path)) {
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
    if ($router_item = menu_get_item($path)) {
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
    if ($router_item = menu_get_item($path)) {
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
    if ($router_item = menu_get_item($path)) {
      if (!empty($router_item['title'])) {
        return $router_item['title'];
      }
    }

    return NULL;
  }
}
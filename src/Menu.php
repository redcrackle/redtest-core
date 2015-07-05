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
   * @var string
   */
  private $menu_name;

  /**
   * Default constructor.
   *
   * @param string $menu_name
   *   Menu name.
   */
  public function __construct($menu_name) {
    $this->menu_name = $menu_name;
  }

  public function getLinks() {
    return menu_load_links($this->menu_name);
  }

  public function getLinksByTitle($title, $parent_title = NULL) {
    $links = $this->getLinks();

    $output = array();
    foreach ($links as $link) {
      if ($link['link_title'] == $title) {
        if (!is_null($parent_title)) {
          $plid = $link['plid'];
          $parent_links = $this->getLinks();
          foreach ($parent_links as $parent_link) {
            if ($parent_link['mlid'] == $plid && $parent_link['link_title'] == $parent_title) {
              $output[] = $link;
            }
          }
        }
        else {
          $output[] = $link;
        }
      }
    }

    return $output;
  }

  public function getLinksByPath($path, $parent_path = NULL) {
    $links = $this->getLinks();

    $output = array();
    foreach ($links as $link) {
      if ($link['link_path'] == $path) {
        if (!is_null($parent_path)) {
          $plid = $link['plid'];
          $parent_links = $this->getLinks();
          foreach ($parent_links as $parent_link) {
            if ($parent_link['mlid'] == $plid && $parent_link['link_path'] == $parent_path) {
              $output[] = $link;
            }
          }
        }
        else {
          $output[] = $link;
        }
      }
    }

    return $output;
  }

  public function getLinksByTitlePath($title, $path, $parent_title = NULL, $parent_path = NULL) {
    $links = $this->getLinks();

    $output = array();
    foreach ($links as $link) {
      if ($link['link_path'] == $path && $link['link_title'] == $title) {
        if (!is_null($parent_title) || !is_null($parent_path)) {
          $plid = $link['plid'];
          $parent_links = $this->getLinks();
          foreach ($parent_links as $parent_link) {
            if ($parent_link['mlid'] == $plid) {
              if (!is_null($parent_title) && $parent_link['link_title'] != $parent_title) {
                continue;
              }
              if (!is_null($parent_path) && $parent_link['link_path'] != $parent_path) {
                continue;
              }

              $output[] = $link;
            }
          }
        }
        else {
          $output[] = $link;
        }
      }
    }

    return $output;
  }
}
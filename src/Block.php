<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 5/24/15
 * Time: 4:13 PM
 */

namespace RedTest\core;


/**
 * Class Block
 *
 * @package RedTest\core
 */
class Block {

  /**
   * @var string
   */
  private $name;

  /**
   * Constructor.
   *
   * @param string $name
   *   Machine name of the block. You can get this by concatenating block's
   *   module name and delta separated by "_". As an example "search_form",
   *   where module name is "search" and delta is "form". For custom blocks
   *   created via UI, this will be "block_<bid>" where <bid> is the block id.
   */
  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * Returns whether block is present on the provided URL or not.
   *
   * @param null|string $path
   *   URL of the page where block needs to be searched. If this is NULL, then
   *   homepage is assumed.
   * @param null|string $region
   *   Region of the page where the block needs to be searched. If this is
   *   NULL, then all the regions are searched.
   *
   * @return bool
   *   TRUE if block is present and FALSE otherwise.
   */
  public function isPresent($path = NULL, $region = NULL) {
    return !is_null($this->getInfo($path, $region));
  }

  /**
   * Returns the block array.
   *
   * @param null|string $path
   *   URL of the page where block needs to be searched. If this is NULL, then
   *   homepage is assumed.
   * @param null|string $region
   *   Region of the page where the block needs to be searched. If this is
   *   NULL, then all the regions are searched.
   *
   * @return null|array
   *   Block info array if block is found and NULL otherwise.
   */
  public function getInfo($path = NULL, $region = NULL) {
    if (is_null($path)) {
      $path = '<front>';
    }

    $output = NULL;

    if (is_null($region)) {
      foreach (Menu::getBlocks($path) as $region => $block_array) {
        foreach ($block_array as $block => $block_info) {
          if ($block == $this->name) {
            $output = $block_info;
            break;
          }
        }
      }
    }
    else {
      foreach (Menu::getBlocks($path, $region) as $block => $block_info) {
        if ($block == $this->name) {
          $output = $block_info;
          break;
        }
      }
    }

    return $output;
  }

  /**
   * Returns subject of the block.
   *
   * @param null|string $path
   *   URL of the page where block needs to be searched. If this is NULL, then
   *   homepage is assumed.
   *
   * @return string
   *   Subject of the block.
   */
  public function getSubject($path) {
    $block_info = $this->getInfo($path);
    if (is_null($block_info)) {
      return NULL;
    }

    return $block_info['#block']->subject;
  }

  /**
   * Returns markup of the block, if any. This is more useful for custom blocks created via the UI.
   *
   * @param null|string $path
   *   URL of the page where block needs to be searched. If this is NULL, then
   *   homepage is assumed.
   *
   * @return null|string
   *   Markup if it exists and NULL otherwise.
   */
  public function getMarkup($path) {
    $block_info = $this->getInfo($path);
    if (is_null($block_info)) {
      return NULL;
    }

    return isset($block_info['#markup']) ? $block_info['#markup'] : NULL;
  }
}
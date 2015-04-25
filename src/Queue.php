<?php
/**
 * Created by PhpStorm.
 * User: neemehta
 * Date: 4/21/15
 * Time: 12:44 PM
 */

namespace RedTest\core;

use \DrupalQueue;

class Queue {

  protected $name;

  public function __construct($name) {
    $this->name = $name;
  }

  public function clear() {
    $queue = DrupalQueue::get($this->name);
    $queue->createQueue();

    while ($item = $queue->claimItem(0)) {
      $queue->deleteItem($item);
    }
  }

  public function getItems() {
    $data = db_select('queue', 'q')
      ->fields('q', array('item_id', 'data'))
      ->condition('q.name', $this->name)
      ->execute()
      ->fetchAllKeyed();

    $output = array();
    foreach ($data as $item_id => $item) {
      $output[$item_id] = unserialize($item);
    }

    return $output;
  }
}
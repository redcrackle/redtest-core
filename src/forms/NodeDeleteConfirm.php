<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 1/13/15
 * Time: 7:54 PM
 */

namespace RedTest\core\forms;


use tests\phpunit_tests\core\Utilities;

class NodeDeleteConfirm extends Form {

  private $node;

  public function __construct($nid) {
    $this->node = node_load($nid);
    if ($this->node) {
      module_load_include('inc', 'node', 'node.pages');
      parent::__construct('node_delete_confirm', $this->node);
    }
  }

  public function delete() {
    $this->fillValues(array('confirm' => TRUE));
    return parent::submit($this->node);
  }
}
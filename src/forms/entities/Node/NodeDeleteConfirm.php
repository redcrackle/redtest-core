<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 1/13/15
 * Time: 7:54 PM
 */

namespace RedTest\core\forms\entities\Node;

use RedTest\core\forms\Form;

/**
 * Class NodeDeleteConfirm
 *
 * @package RedTest\core\forms\entities\Node
 */
class NodeDeleteConfirm extends Form {

  /**
   * @var bool|mixed
   */
  private $node;

  /**
   * Default constructor.
   *
   * @param int $nid
   *   Node id of the node to be deleted.
   */
  public function __construct($nid) {
    $this->node = node_load($nid);
    if ($this->node) {
      $this->includeFile('inc', 'node', 'node.pages');
      parent::__construct('node_delete_confirm', $this->node);
    }

    $this->setInitialized(TRUE);
  }

  /**
   * Submit the node delete form.
   *
   * @return \RedTest\core\Response
   *   Response object.
   */
  public function submit() {
    $this->fillConfirmValues(TRUE);
    return $this->pressButton(NULL, array(), $this->node);
  }
}
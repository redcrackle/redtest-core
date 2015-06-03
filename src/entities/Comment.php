<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities;

use RedTest\core\Utils;

class Comment extends Entity {

  /**
   * Default constructor for the comment object.
   *
   * @param int|null $cid
   *   Cid if an existing comment is to be loaded and null if a new comment is
   *   to be loaded.
   */
  public function __construct($cid = NULL) {
    $args = func_get_args();
    array_shift($args);
    $nid = array_shift($args);

    if (is_null($cid)) {
      if (!isset($nid) || !is_numeric($nid)) {
        throw new \Exception('Provide nid for a comment add form.');
      }
      $node = node_load($nid);
      $comment = (object) array('nid' => $nid);
    }
    else {
      $comment = comment_load($cid);
      $node = $comment['#node'];
      if (!is_null($nid) && $node->nid != $nid) {
        throw new \Exception(
          'Id of the node associated with the comment and provided node id do not match.'
        );
      }
    }

    if (!$node) {
      throw new \Exception("Node $nid doesn't exist.");
    }

    $classname = get_called_class();
    $class = new \ReflectionClass($classname);
    $class_shortname = $class->getShortName();

    $type = Utils::makeSnakeCase(
      substr($class_shortname, 0, -7)
    );
    if ($node->type != $type) {
      throw new \Exception(
        "Classes of comment and the node do not match. Class of comment is $type while that of node is " . $node->type . "."
      );
    }

    parent::__construct($comment);
  }

  public function getFieldInstances() {
    $nid = $this->getNidValues();
    $node = node_load($nid);
    $bundle = 'comment_node_' . $node->type;

    return field_info_instances('comment', $bundle);
  }

}

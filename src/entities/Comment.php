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
    $pid = array_shift($args);

    if (is_null($cid)) {
      if (!isset($nid) || !is_numeric($nid)) {
        $this->setErrors('Provide nid for a comment add form.');
        $this->setInitialized(FALSE);
        return;
      }
      $node = node_load($nid);
      $comment = (object) array('nid' => $nid);
    }
    else {
      $comment = comment_load($cid);
      $comment_nid = $comment->nid;
      if (!is_null($nid) && $comment_nid != $nid) {
        $this->setErrors(
          'Id of the node associated with the comment and provided node id do not match.'
        );
        $this->setInitialized(FALSE);
        return;
      }

      $node = node_load($comment_nid);
    }

    if (!$node) {
      $this->setErrors("Node $nid doesn't exist.");
      $this->setInitialized(FALSE);
      return;
    }

    if (!is_null($pid)) {
      $parent_comment = comment_load($pid);
      if (!$parent_comment) {
        $this->setInitialized(FALSE);
        $this->setErrors("Comment $pid does not exist.");
        return;
      }
      if ($parent_comment->nid != $node->nid) {
        $this->setInitialized(FALSE);
        $this->setErrors(
          "Node id associated with the parent comment and the one that is provided do not match."
        );
        return;
      }

      $comment->pid = $pid;
    }

    $classname = get_called_class();
    $class = new \ReflectionClass($classname);
    $class_shortname = $class->getShortName();

    $type = Utils::makeSnakeCase(substr($class_shortname, 0, -7));
    if ($node->type != $type) {
      $this->setErrors(
        "Classes of comment and the node do not match. Class of comment is $type while that of node is " . $node->type . "."
      );
      $this->setInitialized(TRUE);
      return;
    }

    parent::__construct($comment);
  }

  /**
   * Returns field instances attached to the comment.
   *
   * @return array
   *   An array of field instances.
   */
  public function getFieldInstances() {
    $nid = $this->getNidValues();
    $node = node_load($nid);
    $bundle = 'comment_node_' . $node->type;

    return field_info_instances('comment', $bundle);
  }

  /**
   * Delete the comment programmatically.
   *
   * @return bool
   *   Returns TRUE.
   */
  public function deleteProgrammatically() {
    comment_delete($this->getId());
    return TRUE;
  }
}

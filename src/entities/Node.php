<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities;

use RedTest\core\forms\entities\Node\NodeDeleteConfirm;
use RedTest\core\Utils;

class Node extends Entity {

  /**
   * Default constructor for the node object. Do not call this class directly.
   * Create a separate class for each content type and use its constructor.
   *
   * @param int $nid
   *   Nid if an existing node is to be loaded.
   */
  public function __construct($nid = NULL) {
    $class = new \ReflectionClass(get_called_class());

    $type = Utils::makeSnakeCase($class->getShortName());
    if (!is_null($nid) && is_numeric($nid)) {
      $node = node_load($nid);
      if ($node->type == $type) {
        parent::__construct($node);
      }
    }
    else {
      global $user;
      $node = (object) array(
        'title' => NULL,
        'type' => $type,
        'language' => LANGUAGE_NONE,
        'is_new' => TRUE,
        'name' => $user->name,
      );
      node_object_prepare($node);
      parent::__construct($node);
    }

    $this->setInitialized(TRUE);
  }

  /**
   * Delete the node programmatically.
   *
   * @return bool
   *   Returns TRUE.
   */
  public function deleteProgrammatically() {
    node_delete($this->getId());
    return TRUE;
  }

  /**
   * Delete a node using the form.
   *
   * @return array
   *   An array with two values:
   *   (1) bool $success: If form submission succeeded.
   *   (2) string $msg: An error message if submission failed and empty
   *   otherwise.
   */
  public function delete() {
    $nodeDeleteConfirm = new NodeDeleteConfirm($this->getId());
    return $nodeDeleteConfirm->submit();
  }

  /**
   * View the node in provided view mode.
   *
   * @param string $view_mode
   *   View mode.
   *
   * @return array
   *   Renderable array of the node.
   */
  public function view($view_mode = 'full') {
    return node_view($this->getEntity(), $view_mode);
  }
}

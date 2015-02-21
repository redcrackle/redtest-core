<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities;

use RedTest\core\Utilities as Utilities;

class Node extends Entity {

  /**
   * Default constructor for the node object. We want this to be protected so
   * that no class other than child classes can call it directly. We expect the
   * users to create a separate class for each content type and use its
   * constructor.
   *
   * @param int $nid
   *   Nid if an existing node is to be loaded.
   */
  protected function __construct($nid = NULL) {
    $class = new \ReflectionClass(get_called_class());

    $type = Utilities::convertTitleCaseToUnderscore($class->getShortName());
    if (!is_null($nid) && is_numeric($nid)) {
      $node = node_load($nid);
      if ($node->type == $type) {
        parent::__construct($node);
      }
    }
    else {
      $node = (object) array(
        'title' => NULL,
        'type' => $type,
        'language' => LANGUAGE_NONE,
        'is_new' => TRUE,
      );
      node_object_prepare($node);
      parent::__construct($node);
    }
  }
}

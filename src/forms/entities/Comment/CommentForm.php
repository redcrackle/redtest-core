<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 6/2/15
 * Time: 10:41 AM
 */

namespace RedTest\core\forms\entities\Comment;

use RedTest\core\fields\Field;
use RedTest\core\forms\entities\EntityForm;
use RedTest\core\Utils;


class CommentForm extends EntityForm {

  /**
   * Default constructor.
   *
   * @param null|int $id
   *   Comment id if a comment edit form is to be loaded and null if comment
   *   add form is to be loaded.
   *
   * @throws \Exception
   */
  public function __construct($id) {
    if (!user_access('post comments')) {
      throw new \Exception("User is not allowed to post comments.");
    }

    $args = func_get_args();
    array_shift($args);
    $nid = array_shift($args);

    /*if (is_null($id)) {
      if (!isset($nid) || !is_numeric($nid)) {
        throw new \Exception('Provide nid for a comment add form.');
      }
      $node = node_load($nid);
    }
    else {
      $comment = comment_load($id);
      $node = $comment['#node'];
    }

    if (!$node) {
      throw new \Exception("Node $nid doesn't exist.");
    }*/

    $classname = get_called_class();
    $class = new \ReflectionClass($classname);
    $class_shortname = $class->getShortName();
    $class_fullname = "RedTest\\entities\\Comment\\" . substr(
        $class_shortname,
        0,
        -4
      );
    $commentObject = new $class_fullname($id, $nid);
    $this->setEntityObject($commentObject);

    $nid = $commentObject->getNidValues();
    $node = node_load($nid);
    if ($node->comment != COMMENT_NODE_OPEN && is_null($id)) {
      throw new \Exception("Node $nid does not allow posting of comments.");
    }

    /*$classname = get_called_class();
    $class = new \ReflectionClass($classname);
    $class_shortname = $class->getShortName();

    $type = Utils::makeSnakeCase(
      substr($class_shortname, 0, -11)
    );
    if ($node->type != $type) {
      throw new \Exception(
        "Classes of comment and the node do not match. Class of comment is $type while that of node is " . $node->type . "."
      );
    }

    $class_fullname = "RedTest\\entities\\Comment\\" . substr($class_shortname, 0, -4);
    $commentObject = new $class_fullname($id, $nid);
    $this->setEntityObject($commentObject);*/

    $type = Utils::makeSnakeCase(
      substr($class_shortname, 0, -11)
    );
    parent::__construct('comment_node_' . $type . '_form', $node);
  }

  public function fillDefaultValues($options = array()) {
    $options += array(
      'skip' => array(),
      'required_fields_only' => TRUE,
    );

    list($success, $fields, $msg) = parent::fillDefaultValues(
      $options
    );
    if (!$success) {
      return array(FALSE, $fields, $msg);
    }

    if (($this->isSubjectRequired(
        ) || !$options['required_fields_only']) && $this->hasSubjectAccess()
    ) {
      list($success, $value, $msg) = $this->fillDefaultSubjectValues();
      if (!$success) {
        return array(FALSE, $fields, $msg);
      }
      $fields['subject'] = $value;
    }

    if ($this->isAuthorSubfieldToBeFilled(
      'name',
      $options['required_fields_only']
    )
    ) {
      list($success, $value, $msg) = Field::fillDefaultValues($this, array('author', 'mail'));
      if (!$success) {
        return array(FALSE, $fields, $msg);
      }
      $fields['name'] = $value;
    }

    if ($this->isAuthorSubfieldToBeFilled(
      'mail',
      $options['required_fields_only']
    )
    ) {
      list($success, $value, $msg) = $this->fillMailValues(
        Utils::getRandomEmail()
      );
      if (!$success) {
        return array(FALSE, $fields, $msg);
      }
      $fields['mail'] = $value;
    }

    if ($this->isAuthorSubfieldToBeFilled('homepage', $options['required_fields_only'])) {
      list($success, $value, $msg) = $this->fillHomepageValues(Utils::getRandomUrl());
      if (!$success) {
        return array(FALSE, $fields, $msg);
      }
      $fields['homepage'] = $value;
    }

    return array(TRUE, $fields, '');
  }

  /**
   * Returns whether the author subfield is to be filled.
   *
   * @param string $field_name
   *   Subfield name.
   * @param bool $required_fields_only
   *   TRUE if only required fields need to be filled and FALSE otherwise.
   *
   * @return bool
   *   TRUE if the subfield is to be filled and FALSE otherwise.
   */
  private function isAuthorSubfieldToBeFilled(
    $field_name,
    $required_fields_only
  ) {
    $form = $this->getForm();
    return (($this->isRequired(
          array('author', $field_name)
        ) || !$required_fields_only) && $this->hasAccess(
        array('author', $field_name)
      ) && ($form['author'][$field_name]['#type'] == 'textfield'));
  }

  /**
   * Returns field instance information.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   Field instance array.
   */
  protected function getFieldInstance($field_name) {
    $nid = $this->getEntityObject()->getNidValues();
    $node = node_load($nid);
    $bundle = 'comment_node_' . $node->type;

    return field_info_instance('comment', $field_name, $bundle);
  }
}
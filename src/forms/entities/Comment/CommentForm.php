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
use RedTest\core\entities\Comment;


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
      $this->setErrors("User is not allowed to post comments.");
      $this->setInitialized(FALSE);
      return;
    }

    $args = func_get_args();
    array_shift($args);
    $nid = array_shift($args);
    $pid = array_shift($args);

    $classname = get_called_class();
    $class = new \ReflectionClass($classname);
    $class_shortname = $class->getShortName();
    $class_fullname = "RedTest\\entities\\Comment\\" . substr(
        $class_shortname,
        0,
        -4
      );

    $commentObject = new $class_fullname($id, $nid, $pid);
    if (!$commentObject->getInitialized()) {
      $this->setErrors($commentObject->getErrors());
      $this->setInitialized(FALSE);
      return;
    }
    $this->setEntityObject($commentObject);

    $nid = $commentObject->getNidValues();
    $node = node_load($nid);
    if ($node->comment != COMMENT_NODE_OPEN && is_null($id)) {
      $this->setErrors("Node $nid does not allow posting of comments.");
      $this->setInitialized(FALSE);
      return;
    }

    $type = Utils::makeSnakeCase(
      substr($class_shortname, 0, -11)
    );
    parent::__construct('comment_node_' . $type . '_form', (object) array('nid' => $nid, 'pid' => $pid));
  }

  public function fillRandomValues($options = array()) {
    $options += array(
      'skip' => array(),
      'required_fields_only' => TRUE,
    );

    list($success, $fields, $msg) = parent::fillRandomValues(
      $options
    );
    if (!$success) {
      return array(FALSE, $fields, $msg);
    }

    if (($this->isSubjectRequired(
        ) || !$options['required_fields_only']) && $this->hasSubjectAccess()
    ) {
      list($success, $value, $msg) = $this->fillSubjectRandomValues();
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
      list($success, $value, $msg) = $this->fillFieldValues(
        array('author', 'name'),
        Utils::getRandomText(10)
      );
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
      list($success, $value, $msg) = $this->fillFieldValues(
        array('author', 'mail'),
        Utils::getRandomEmail()
      );
      if (!$success) {
        return array(FALSE, $fields, $msg);
      }
      $fields['mail'] = $value;
    }

    if ($this->isAuthorSubfieldToBeFilled(
      'homepage',
      $options['required_fields_only']
    )
    ) {
      list($success, $value, $msg) = $this->fillFieldValues(
        array('account', 'homepage'),
        Utils::getRandomUrl()
      );
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
        ) || !$required_fields_only) && $this->hasFieldAccess(
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

  public function submit() {
    $this->includeFile('inc', 'comment', 'comment.pages');

    $this->processBeforeSubmit();

    list($success, $msg) = $this->pressButton(t('Save'), array(), $this->getEntityObject()->getEntity());

    $commentObject = NULL;
    if ($success) {
      // Get the comment from form_state.
      $form_state = $this->getFormState();
      $comment = $form_state['comment'];
      $node_type = str_replace('comment_node_', '', $comment->node_type);
      $classname = Utils::makeTitleCase($node_type) . 'Comment';
      $class_fullname = "RedTest\\entities\\Comment\\" . $classname;
      $commentObject = new $class_fullname($comment->cid);
      if (!$commentObject->getInitialized()) {
        return array(FALSE, NULL, $commentObject->getErrors());
      }
      $this->setEntityObject($commentObject);

      // Store the created node in $entities so that it can later be deleted.
      global $entities;
      $entities['comment'][$comment->cid] = $commentObject;
    }

    $this->processAfterSubmit();

    return array($success, $commentObject, $msg);
  }
}
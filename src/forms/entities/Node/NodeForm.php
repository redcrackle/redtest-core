<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:58 PM
 */

namespace RedTest\core\forms\entities\Node;

use RedTest\core\forms\entities\EntityForm;
use RedTest\core\Utils;

class NodeForm extends EntityForm {

  /**
   * Default constructor of the node form. We want this to be protected so that
   * no class other than child classes can call it directly. We expect the
   * users to create a separate class for each content type and use its
   * constructor.
   *
   * @param null|int $nid
   *   Node id if an existing node form is to be loaded.
   */
  protected function __construct($nid = NULL) {
    $classname = get_called_class();
    $class = new \ReflectionClass($classname);
    $class_shortname = $class->getShortName();
    $class_fullname = "RedTest\\entities\\Node\\" . Utils::makeTitleCase(
        substr($class_shortname, 0, -4)
      );

    $type = Utils::makeSnakeCase(
      substr($class_shortname, 0, -4)
    );
    $nodeObject = new $class_fullname($nid);
    $this->setEntityObject($nodeObject);

    if (!is_null($this->getEntityObject()->getEntity())) {
      module_load_include('inc', 'node', 'node.pages');
      parent::__construct(
        $type . '_node_form',
        $this->getEntityObject()->getEntity()
      );
    }
  }

  /**
   * Set author name.
   *
   * @param string $username
   *   Username of the author.
   */
  private function setAuthorname($username) {
    $this->fillValues(
      array(
        'name' => $username,
      )
    );
  }

  /**
   * This function is used for node form submit.
   */
  public function submit() {
    $this->fillValues(array('op' => t('Save')));
    $this->removeKey('triggering_element');
    $this->removeKey('validate_handlers');
    $this->removeKey('submit_handlers');
    $this->removeKey('clicked_button');
    module_load_include('inc', 'node', 'node.pages');
    list($success, $errors) = parent::submit($this->getEntityObject()->getEntity());

    if ($success) {
      // Get the node from form_state.
      $form_state = $this->getFormState();
      $node = $form_state['node'];
      $type = $node->type;
      $classname = Utils::makeTitleCase($type);
      $class_fullname = "RedTest\\entities\\Node\\" . $classname;
      $nodeObject = new $class_fullname($node->nid);
      $this->setEntityObject($nodeObject);

      return array(TRUE, $nodeObject, array());
    }

    return array(FALSE, NULL, $errors);
  }

  /**
   * Fill form with default values. These default values are what you define in
   * this function and are different from Drupal's default values for the
   * fields.
   *
   * @param array $skip
   *   An array of field or property names that should not be filled with
   *   default values.
   *
   * @return array|void
   */
  public function fillDefaultValuesExcept($skip = array()) {
    list($success, $fields, $msg) = parent::fillDefaultValuesExcept($skip);
    if (!$success) {
      return array(FALSE, $fields, $msg);
    }

    if (!in_array('title', $skip)) {
      $fields['title'] = Utils::getRandomText(25);
      $this->fillTitle($fields['title']);
    }

    return array(TRUE, $fields, "");
  }
}

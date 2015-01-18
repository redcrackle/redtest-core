<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:58 PM
 */

namespace tests\phpunit_tests\core\forms;

use tests\phpunit_tests\core\Utilities as Utilities;

class NodeForm extends EntityForm {

  /**
   * Default constructor of the node form.
   *
   * @param int $nid
   *   Node id if an existing node form is to be loaded.
   */
  protected function __construct($nid = NULL) {
    $classname = get_called_class();
    $class = new \ReflectionClass($classname);
    $class_shortname = $class->getShortName();
    $class_fullname = "tests\\phpunit_tests\\custom\\entities\\node\\" . substr(
        $class_shortname,
        0,
        -4
      );

    $type = Utilities::convertTitleCaseToUnderscore(
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
  function setAuthorname($username) {
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
    $output = parent::submit($this->getEntityObject()->getEntity());

    if ($output) {
      // Get the node from form_state.
      $form_state = $this->getFormState();
      $node = $form_state['node'];
      $type = $node->type;
      $classname = Utilities::convertUnderscoreToTitleCase($type);
      $class_fullname = "tests\\phpunit_tests\\custom\\entities\\node\\" . $classname;
      $nodeObject = new $class_fullname($node->nid);
      $this->setEntityObject($nodeObject);
    }

    return $output;
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
  public function fillDefaultValues($skip = array()) {
    list($success, $fields, $msg) = parent::fillDefaultValues($skip);
    if (!$success) {
      return array(FALSE, $fields, $msg);
    }

    if (!in_array('title', $skip)) {
      $fields['title'] = Utilities::getRandomString();
      $this->fillTitle($fields['title']);
    }

    return array(TRUE, $fields, "");
  }
}
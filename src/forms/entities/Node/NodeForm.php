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
use RedTest\core\entities\User;

class NodeForm extends EntityForm {

  /**
   * Default constructor of the node form.
   *
   * @param null|int $nid
   *   Node id if an existing node form is to be loaded.
   */
  public function __construct($nid = NULL) {
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
      $this->includeFile('inc', 'node', 'node.pages');
      //module_load_include('inc', 'node', 'node.pages');
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
    $this->includeFile('inc', 'node', 'node.pages');
    list($success, $errors) = $this->pressButton(
      NULL,
      $this->getEntityObject()->getEntity()
    );

    if ($success) {
      // Get the node from form_state.
      $form_state = $this->getFormState();
      $node = $form_state['node'];
      $type = $node->type;
      $classname = Utils::makeTitleCase($type);
      $class_fullname = "RedTest\\entities\\Node\\" . $classname;
      $nodeObject = new $class_fullname($node->nid);
      $this->setEntityObject($nodeObject);

      // Store the created node in $entities so that it can later be deleted.
      global $entities;
      $entities['node'][$node->nid] = $nodeObject;

      return array(TRUE, $nodeObject, array());
    }

    return array(FALSE, NULL, $errors);
  }

  /**
   * Fill form with default values. These default values are what you define in
   * this function and are different from Drupal's default values for the
   * fields.
   *
   * @param boolean $required_fields_only
   *   Whether only required fields are to be filled.
   * @param array $skip
   *   An array of field or property names that should not be filled with
   *   default values.
   * @param array $data
   *   This parameter is just to make all the fillDefaultValues() functions
   *   uniform and is not used here.
   *
   * @return array
   *   An array with the following values:
   *   (1) $success: TRUE if all the fields except the ones to be skipped could
   *   be filled and FALSE otherwise.
   *   (2) $fields: An associative array of field values that were filled keyed
   *   by the field name.
   *   (3) $msg: An error message if there was an error filling fields with
   *   default values and an empty string otherwise.
   */
  public function fillDefaultValues(
    $required_fields_only = TRUE,
    $skip = array(),
    $data = array()
  ) {
    list($success, $fields, $msg) = parent::fillDefaultValues(
      $required_fields_only,
      $skip,
      $data
    );
    if (!$success) {
      return array(FALSE, $fields, $msg);
    }

    if ((!$required_fields_only || $this->isTitleRequired()) && !in_array(
        'title',
        $skip
      )
    ) {
      // Check if the field is required. We use '#required' key in form array
      // since it can be set or unset using custom code.
      // Field is not required. There is no need to fill this field.
      $fields['title'] = Utils::getRandomText(25);
      $this->fillTitleValues($fields['title']);
    }

    if ($this->hasAccess(
        array('options', 'status')
      ) && isset($data['status'])
    ) {
      $status = NULL;
      switch ($data['status']) {
        case 'random':
          $status = Utils::getRandomBool();
          break;
        case 'published':
          $status = 1;
          break;
        case 'unpublished':
          $status = 0;
          break;
      }
      if (!is_null($status)) {
        $this->fillStatusValues($status);
        $fields['status'] = $status;
      }
    }

    if ($this->hasAccess(
        array('revision_information', 'revision')
      ) && isset($data['revision'])
    ) {
      $revision = NULL;
      $revision_log = NULL;
      switch ($data['revision']) {
        case 'random':
          $revision = Utils::getRandomBool();
          break;
        case TRUE:
          $revision = 1;
          break;
        case FALSE:
          $revision = 0;
          break;
      }
      if (!is_null($revision)) {
        $this->fillRevisionValues($revision);
        $fields['revision'] = $revision;
        if ($revision && $this->hasAccess(
            array('revision_information', 'log')
          ) && isset($data['revision_log'])
        ) {
          switch ($data['revision_log']) {
            case 'random':
              $revision_log = Utils::getRandomBool() ? Utils::getRandomText(
                25
              ) : NULL;
              break;
            case TRUE:
              $revision_log = Utils::getRandomText(25);
              break;
            case FALSE:
              $revision_log = '';
              break;
          }
          if (!is_null($revision_log)) {
            $this->fillLogValues($revision_log);
            $fields['log'] = $revision_log;
          }
        }
      }
    }

    if ($this->hasAccess(array('author', 'name')) && isset($data['change_author']) && $data['change_author']) {
      // We'll need to create new author first.
      // Masquerade as user 1.
      list($superAdmin, $originalUser, $originalState) = User::masquerade(1);
      list($success, $userObject, $msg) = User::createDefault();
      // Return to original user.
      User::unmasquerade($originalUser, $originalState);

      if (!$success) {
        return array(FALSE, $fields, $msg);
      }

      $name = $userObject->getNameValues();
      $this->fillNameValues($name);
      $fields['name'] = $name;
    }

    if ($this->hasAccess(
        array('author', 'date')
      ) && isset($data['change_published_date']) && $data['change_published_date']
    ) {
      $now = time();
      $start = isset($data['start_published_date']) ? Utils::formatDate(
        $data['start_published_date'],
        'integer'
      ) : $now - (3 * 365 * 24 * 60 * 60);
      $end = isset($data['end_published_date']) ? Utils::formatDate(
        $data['end_published_date'],
        'integer'
      ) : $now + (3 * 365 * 24 * 60 * 60);

      $date = Utils::getRandomDate('Y-m-d H:i:s', $start, $end);
      $this->fillDateValues($date);
      $fields['date'] = $date;
    }

    return array(TRUE, $fields, "");
  }
}

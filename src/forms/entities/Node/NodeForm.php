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
   * Default constructor of the node form. You should not be invoking NodeForm
   * directly. Create a form for your content type that extends NodeForm and
   * invoke that. The access level has to be kept public here because access
   * level of parent class has to be match that of child class.
   *
   * @param null|int $nid
   *   Node id if an existing node form is to be loaded. If a new node form is
   *   to be created, then keep it empty.
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
      parent::__construct(
        $type . '_node_form',
        $this->getEntityObject()->getEntity()
      );
    }
  }

  /**
   * This function is used for node form submit.
   */
  public function submit() {
    //$this->fillValues(array('op' => t('Save')));
    $this->includeFile('inc', 'node', 'node.pages');

    $this->processBeforeSubmit();

    list($success, $msg) = $this->pressButton(
      t('Save'),
      array(),
      $this->getEntityObject()->getEntity()
    );

    $nodeObject = NULL;
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
    }

    $this->processAfterSubmit();

    return array($success, $nodeObject, $msg);
  }

  /**
   * Fill form with default values. These default values are what you define in
   * this function and are different from Drupal's default values for the
   * fields.
   *
   * @param array $options
   *   An associative options array. It can have the following keys:
   *   (a) skip: An array of field names which are not to be filled.
   *   (b) required_fields_only: TRUE if only required fields are to be filled
   *   and FALSE if all fields are to be filled.
   *
   * @return array
   *   An array with the following values:
   *   (1) $success: TRUE if fields were filled successfully and FALSE
   *   otherwise.
   *   (2) $fields: An associative array of field values that are to be filled
   *   keyed by field name.
   *   (3) $msg: Error message if $success is FALSE, and an empty string
   *   otherwise.
   */
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

    if ((!$options['required_fields_only'] || $this->isTitleRequired(
        )) && !in_array(
        'title',
        $options['skip']
      )
    ) {
      // Check if the field is required. We use '#required' key in form array
      // since it can be set or unset using custom code.
      // Field is not required. There is no need to fill this field.
      $fields['title'] = Utils::getRandomText(25);
      $this->fillTitleValues($fields['title']);
    }

    if ($this->hasFieldAccess(
        array('options', 'status')
      ) && isset($options['status'])
    ) {
      $status = NULL;
      switch ($options['status']) {
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

    if ($this->hasFieldAccess(
        array('revision_information', 'revision')
      ) && isset($options['revision'])
    ) {
      $revision = NULL;
      $revision_log = NULL;
      switch ($options['revision']) {
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
        if ($revision && $this->hasFieldAccess(
            array('revision_information', 'log')
          ) && isset($options['revision_log'])
        ) {
          switch ($options['revision_log']) {
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

    if ($this->hasFieldAccess(
        array('author', 'name')
      ) && isset($options['change_author']) && $options['change_author']
    ) {
      // We'll need to create new author first.
      // Masquerade as user 1.
      list($superAdmin, $originalUser, $originalState) = User::masquerade(1);
      list($success, $userObject, $msg) = User::createRandom();
      // Return to original user.
      User::unmasquerade($originalUser, $originalState);

      if (!$success) {
        return array(FALSE, $fields, $msg);
      }

      $name = $userObject->getNameValues();
      $this->fillNameValues($name);
      $fields['name'] = $name;
    }

    if ($this->hasFieldAccess(
        array('author', 'date')
      ) && isset($options['change_published_date']) && $options['change_published_date']
    ) {
      $now = time();
      $start = isset($options['start_published_date']) ? Utils::formatDate(
        $options['start_published_date'],
        'integer'
      ) : $now - (3 * 365 * 24 * 60 * 60);
      $end = isset($options['end_published_date']) ? Utils::formatDate(
        $options['end_published_date'],
        'integer'
      ) : $now + (3 * 365 * 24 * 60 * 60);

      $date = Utils::getRandomDate('Y-m-d H:i:s', $start, $end);
      $this->fillDateValues($date);
      $fields['date'] = $date;
    }

    return array(TRUE, $fields, "");
  }
}

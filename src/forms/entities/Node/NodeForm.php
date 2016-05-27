<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:58 PM
 */

namespace RedTest\core\forms\entities\Node;

use RedTest\core\forms\entities\EntityForm;
use RedTest\core\Response;
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

    $this->setInitialized(TRUE);
  }

  /**
   * Set the URL before pressing any button so that Purl doesn't redirect. We
   * may need to move this to EntityForm.php if even user is a valid Spaces_OG
   * space.
   *
   * @param string $triggering_element_name
   *   Name of the Add More button or value of Op key.
   * @param array $options
   *   Options array. If key "ajax" is set to TRUE, then
   *   $triggering_element_name is assumed to be name of the Add More button
   *   otherwise it is taken to be the value of Op key.
   *
   * @return Response
   *   Response object.
   */
  public function pressButton($triggering_element_name = NULL, $options = array()) {
    Utils::setSpacesOGPurlURL($this->getEntityObject()->getId());
    $args = func_get_args();
    return call_user_func_array(array('parent', 'pressButton'), $args);
  }

  /**
   * This function is used for node form submit.
   */
  public function submit() {
    //$this->fillValues(array('op' => t('Save')));
    $this->includeFile('inc', 'node', 'node.pages');

    $this->processBeforeSubmit();

    $response = $this->pressButton(
      t('Save'),
      array(),
      $this->getEntityObject()->getEntity()
    );

    $nodeObject = NULL;
    if ($response->getSuccess()) {
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

    $response->setVar($nodeObject);
    return $response;
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
   * @return Response
   *   Response object.
   */
  public function fillRandomValues($options = array()) {
    $options += array(
      'skip' => array(),
      'required_fields_only' => TRUE,
    );

    $response = parent::fillRandomValues($options);
    if (!$response->getSuccess()) {
      return $response;
    }

    $fields = $response->getVar();

    // @todo Check about field access.
    if ((!$options['required_fields_only'] || $this->isTitleRequired(
        )) && !in_array(
        'title',
        $options['skip']
      )
    ) {
      // Check if the field is required. We use '#required' key in form array
      // since it can be set or unset using custom code.
      // Field is not required. There is no need to fill this field.
      // @todo Provide the ability to pass max_length in $options array.
      $response = $this->fillTitleRandomValues();
      if (!$response->getSuccess()) {
        $response->setVar($fields);
        return $response;
      }
      $fields['title'] = $response->getVar();
    }

    // @todo Check in $skip array.
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
        $response = $this->fillStatusValues($status);
        if (!$response->getSuccess()) {
          $response->setVar($fields);
          return $response;
        }
        $fields['status'] = $status;
      }
    }

    // @todo Check in $skip array.
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
        $response = $this->fillRevisionValues($revision);
        if (!$response->getSuccess()) {
          $response->setVar($fields);
          return $response;
        }
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
            $response = $this->fillLogValues($revision_log);
            if (!$response->getSuccess()) {
              $response->setVar($fields);
              return $response;
            }
            $fields['log'] = $revision_log;
          }
        }
      }
    }

    // @todo Check $skip array.
    if ($this->hasFieldAccess(
        array('author', 'name')
      ) && isset($options['change_author']) && $options['change_author']
    ) {
      // We'll need to create new author first.
      // Masquerade as user 1.
      list($superAdmin, $originalUser, $originalState) = User::masquerade(1);

      $response = User::createRandom();

      // Return to original user.
      User::unmasquerade($originalUser, $originalState);

      if (!$response->getSuccess()) {
        $response->setVar($fields);
        return $response;
      }

      $userObject = $response->getVar();
      $name = $userObject->getNameValues();
      $this->fillNameValues($name);
      $fields['name'] = $name;
    }

    // @todo Check $skip array.
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
      $response = $this->fillDateValues($date);
      if (!$response->getSuccess()) {
        $response->setVar($fields);
        return $response;
      }
      $fields['date'] = $date;
    }

    return new Response(TRUE, $fields, "");
  }
}

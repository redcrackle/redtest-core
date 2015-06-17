<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/15/14
 * Time: 6:00 PM
 */

namespace RedTest\core\forms\entities\TaxonomyTerm;

use RedTest\core\entities\TaxonomyTerm;
use RedTest\core\forms\entities\EntityForm;
use RedTest\core\Response;
use RedTest\core\Utils;

/**
 * Class TaxonomyFormTerm
 *
 * @package RedTest\core\forms\entities\TaxonomyTerm
 */
class TaxonomyFormTerm extends EntityForm {

  private $vocabulary;

  /**
   * Default constructor of the taxonomy term form. You should not be invoking
   * TaxonomyFormTerm directly. Create a form for your vocabulary that extends
   * TaxonomyFormTerm and invoke that. The access level has to be kept public
   * here because access level of parent class has to be match that of child
   * class.
   *
   * @param null|int $tid
   *   Taxonomy term id if a taxonomy term edit form is to be loaded. If a
   *   taxonomy term add form is to be created, then keep it empty.
   */
  public function __construct($tid = NULL) {
    $classname = get_called_class();
    $class = new \ReflectionClass($classname);
    $class_shortname = $class->getShortName();
    $vocabulary_name = Utils::makeSnakeCase(
      substr($class_shortname, 0, -4)
    );

    if (!is_null($tid) && is_numeric($tid)) {
      // Tid is not null and is numeric.
      $term = taxonomy_term_load($tid);
      if ($term->vocabulary_machine_name == $vocabulary_name) {
        $this->vocabulary = taxonomy_vocabulary_machine_name_load(
          $vocabulary_name
        );
        $base_path = "RedTest\\entities\\TaxonomyTerm\\";
        $class_fullname = $base_path . substr($class_shortname, 0, -4);
        $termObject = new $class_fullname($tid);
        $this->setEntityObject($termObject);
        $this->includeFile('inc', 'taxonomy', 'taxonomy.admin');
        parent::__construct('taxonomy_form_term', $term, $this->vocabulary);

        $this->setInitialized(TRUE);
        return;
      }
      else {
        // Vocabulary name of the provided term does not match the class it was called from. Return with a FAIL response.
        $this->setErrors("Vocabulary of the provided term does not match the class it was called from.");
        $this->setInitialized(FALSE);
        return;
      }
    }
    else {
      // Proper tid is not provided. Create a dummy term object.
      $base_path = "RedTest\\entities\\TaxonomyTerm\\";
      $class_fullname = $base_path . substr($class_shortname, 0, -4);
      $termObject = new $class_fullname();
      $this->setEntityObject($termObject);
    }

    // tid is not provided or is not numeric.
    $this->vocabulary = taxonomy_vocabulary_machine_name_load($vocabulary_name);
    $this->includeFile('inc', 'taxonomy', 'taxonomy.admin');
    parent::__construct('taxonomy_form_term', array(), $this->vocabulary);

    $this->setInitialized(TRUE);
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

    $response = parent::fillRandomValues($options);
    if (!$response->getSuccess()) {
      return $response;
    }

    $fields = $response->getVar();

    if (!$options['required_fields_only'] || $this->isDescriptionRequired()) {
      // Check if the field is required. We use '#required' key in form array
      // since it can be set or unset using custom code.
      // Field is required or we need to fill all fields.
      if (!in_array('description', $options['skip'])) {
        $description = array(
          'value' => Utils::getRandomText(100),
          'format' => 'plain_text',
        );
        $response = $this->fillDescriptionValues($description);
        if (!$response->getSuccess()) {
          return new Response(FALSE, $fields, $response->getMsg());
        }
        $fields['description'] = $description['value'];
        $fields['format'] = $description['format'];
      }
    }

    // Fill name at the end so that there is less chance of getting non-unique
    // value in the database.
    if (!in_array('name', $options['skip'])) {
      // Make sure that taxonomy term name is not repeated so that deleting
      // entities at the end is easier.
      $name = TaxonomyTerm::getUniqueName($this->vocabulary->machine_name);
      $response = $this->fillNameValues($name);
      if (!$response->getSuccess()) {
        return new Response(FALSE, $fields, $response->getMsg());
      }
      $fields['name'] = $name;
    }

    return new Response(TRUE, $fields, "");
  }

  /**
   * This function is used to submit Taxonomy form.
   *
   * @return array
   */
  public function submit() {
    //$this->fillValues(array('op' => t('Save')));
    $weight = $this->getValues('weight');
    if (empty($weight)) {
      $response = $this->fillWeightValues(0);
      if (!$response->getSuccess()) {
        return $response;
      }
    }
    $parent = $this->getValues('parent');
    if (empty($parent)) {
      $response = $this->fillParentValues(array(0 => "0"));
      if (!$response->getSuccess()) {
        return $response;
      }
    }

    if (is_null($this->getEntityObject()->getId())) {
      $response = $this->pressButton(
        t('Save'),
        array(),
        array(),
        $this->vocabulary
      );
    }
    else {
      $response = $this->pressButton(
        t('Save'),
        array(),
        $this->getEntityObject()->getEntity(),
        NULL
      );
    }

    if (!$response->getSuccess()) {
      return new Response(FALSE, NULL, $response->getMsg());
    }

    $classname = get_called_class();
    $class = new \ReflectionClass($classname);
    $class_shortname = $class->getShortName();
    $base_path = "RedTest\\entities\\TaxonomyTerm\\";
    $class_fullname = $base_path . substr($class_shortname, 0, -4);

    $form_state = $this->getFormState();
    $termObject = new $class_fullname($form_state['term']->tid);
    $this->setEntityObject($termObject);
    $this->getEntityObject()->reload();

    // Store the created user in $entities so that it can later be deleted.
    global $entities;
    $entities['taxonomy_term'][$termObject->getId()] = $termObject;

    return new Response(TRUE, $termObject, "");
  }

  /**
   * This function is used for vocabulary machine name
   *
   * @param  $value
   *   This is vocabulary machine name
   */
  public function fillTermVocabField($value) {
    $this->fillTermVocabWidgetField($value);
  }

  /**
   * This function is used for vocabulary id
   *
   * @param  $value
   *   This is vocabulary id
   */
  public function fillTermVocabVidField($value) {
    $this->fillTermVocabVidWidgetField($value);
  }

  /**
   * Delete the taxonomy term. This is a multi-step form. This action involves
   * first pressing the delete button and then confirming the action.
   *
   * @return array
   *   An array with two values:
   *   (1) bool $success: TRUE if deletion was successful and FALSE otherwise.
   *   (2) string $msg: An error message if deletion failed.
   */
  public function delete() {
    $response = $this->pressButton(
      t('Delete'),
      array(),
      $this->getEntityObject()->getEntity(),
      NULL
    );
    if (!$response->getSuccess()) {
      return $response;
    }

    $response = $this->fillConfirmValues("1");
    if (!$response->getSuccess()) {
      return $response;
    }

    $response = $this->pressButton(
      t('Delete'),
      array(),
      $this->getEntityObject()->getEntity(),
      NULL
    );
    if (!$response->getSuccess()) {
      return $response;
    }

    global $entities;
    unset($entities['taxonomy_term'][$this->getEntityObject()->getId()]);

    return new Response(TRUE, NULL, "");
  }
}

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
use RedTest\core\Utils;

class TaxonomyFormTerm extends EntityForm {

  private $vocabulary;

  function __construct($tid = NULL) {
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

        return;
      }
      else {
        // Vocabulary name of the provided term does not match the class it was called from. Return without doing anything.
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
  }

  /**
   * Fill form with default values. These default values are what you define in
   * this function and are different from Drupal's default values for the
   * fields.
   *
   * @param array $skip
   *   An array of field or property names that should not be filled with
   *   default values.
   * @param array $data
   *   An array of other key value pairs.
   *
   * @return array
   *   An array consisting of three values: TRUE (which means that the function
   *   executed without any error), an array of fields which were modified and
   *   an empty message.
   */
  public function fillDefaultValues($skip = array(), $data = array()) {
    $data += array('required_fields_only' => TRUE);

    list($success, $fields, $msg) = parent::fillDefaultValues($skip, $data);
    if (!$success) {
      return array(FALSE, $fields, $msg);
    }

    if (!$data['required_fields_only'] || $this->isDescriptionRequired()) {
      // Check if the field is required. We use '#required' key in form array
      // since it can be set or unset using custom code.
      // Field is required or we need to fill all fields.
      if (!in_array('description', $skip)) {
        $description = array(
          'value' => Utils::getRandomText(100),
          'format' => 'plain_text',
        );
        $this->fillDescriptionValues($description);
        $fields['description'] = $description['value'];
        $fields['format'] = $description['format'];
      }
    }

    // Fill name at the end so that there is less chance of getting non-unique
    // value in the database.
    if (!in_array('name', $skip)) {
      // Make sure that taxonomy term name is not repeated so that deleting
      // entities at the end is easier.
      $name = TaxonomyTerm::getUniqueName($this->vocabulary->machine_name);
      $this->fillNameValues($name);
      $fields['name'] = $name;
    }

    return array(TRUE, $fields, "");
  }

  /**
   * This function is used for submit Taxonomy form.
   *
   * @return array
   */
  public function submit() {
    $this->fillValues(array('op' => t('Save')));
    $weight = $this->getValues('weight');
    if (empty($weight)) {
      $this->fillValues(array('weight' => 0));
    }
    $parent = $this->getValues('parent');
    if (empty($parent)) {
      $this->fillParentValues(array(0 => "0"));
    }

    if (is_null($this->getEntityObject()->getId())) {
      list($success, $msg) = $this->pressButton(NULL, array(), $this->vocabulary);
    }
    else {
      list($success, $msg) = $this->pressButton(NULL, $this->getEntityObject()->getEntity(), NULL);
    }

    //$output = parent::submit(array(), $this->vocabulary);
    if (!$success) {
      return array(FALSE, NULL, $msg);
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

    return array(TRUE, $termObject, "");
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

  public function delete() {
    $this->fillOpValues(t('Delete'));
    list($success, $msg) = $this->pressButton(NULL, $this->getEntityObject()->getEntity(), NULL);

    $this->fillOpValues(t('Delete'));
    $this->fillConfirmValues("1");
    list($success, $msg) = $this->pressButton(NULL, $this->getEntityObject()->getEntity(), NULL);
    if (!$success) {
      return array(FALSE, $msg);
    }

    global $entities;
    unset($entities['taxonomy_term'][$this->getEntityObject()->getId()]);
    return array(TRUE, $msg);
  }
}

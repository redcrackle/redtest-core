<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 11/15/14
 * Time: 11:44 PM
 */

namespace RedTest\core\forms\entities;

use RedTest\core\forms\Form;
use RedTest\core\Utils;
use RedTest\core\fields\Field;

abstract class EntityForm extends Form {

  /**
   * @var Entity
   */
  private $entityObject;

  public function getEntityObject() {
    return $this->entityObject;
  }

  public function setEntityObject($entityObject) {
    $this->entityObject = $entityObject;
  }

  public function fillDefaultTaxonomyShsValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $vocabularyClassName = $this->getVocabularyClassNameFromVocabularyName(
      $field['settings']['allowed_values'][0]['vocabulary']
    );

    list($success, $termObjects, $msg) = $vocabularyClassName::createDefault(
      $num
    );
    if (!$success) {
      return array(FALSE, $termObjects, $msg);
    }

    $this->fillTaxonomyShs($field_name, Utils::getId($termObjects));

    return array(TRUE, $termObjects, "");
  }


  public function fillDefaultOptionsSelectValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    if ($field['type'] == 'taxonomy_term_reference') {
      $vocabularyClassName = $this->getVocabularyClassNameFromVocabularyName(
        $field['settings']['allowed_values'][0]['vocabulary']
      );

      list($success, $termObjects, $msg) = $vocabularyClassName::createDefault(
        $num
      );
      if (!$success) {
        return array(FALSE, $termObjects, $msg);
      }

      $this->fillOptionsSelect($field_name, Utils::getId($termObjects));

      return array(TRUE, $termObjects, "");
    }
  }

  public function fillDefaultDatePopupValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $date_format = $instance['widget']['settings']['input_format'];
    $date_format_custom = $instance['widget']['settings']['input_format_custom'];
    $year_range = $instance['widget']['settings']['year_range'];
    $increment = $instance['widget']['settings']['increment'];
    $granularity = $field['settings']['granularity'];
    $timezone = $field['settings']['tz_handling'];

    // @todo Generate default date based on parameters.
    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[] = Utils::getRandomDate(
        $date_format,
        "1/1/2000"
      );
    }

    //$this->fillDatePopup($field_name, $values);

    if (sizeof($values) == 1) {
      $values = $values[0];
    }

    return array(TRUE, $values, "");
  }

  public function fillDefaultTaxonomyAutocompleteValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $vocabularyClassName = $this->getVocabularyClassNameFromVocabularyName(
      $field['settings']['allowed_values'][0]['vocabulary']
    );

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      if (Utils::getRandomInt(0, 1)) {
        $values[] = Utils::getRandomString();
      }
      else {
        list($success, $termObject, $msg) = $vocabularyClassName::createDefault(
        );
        if (!$success) {
          return array(FALSE, $values, $msg);
        }

        $values[] = $termObject->getLabel();
      }
    }

    $this->fillTaxonomyAutocomplete($field_name, $values);

    if (sizeof($values) == 1) {
      $values = $values[0];
    }

    return array(TRUE, $values, "");
  }

  public function fillDefaultEntityreferenceViewWidgetValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $view = $instance['widget']['settings']['view'];
    list($view_name, $display_name) = explode("|", $view);
    $result = views_get_view_result($view_name, $display_name);
    $rows = array();
    foreach ($result as $row) {
      $data = $row->_field_data;
      foreach ($data as $key => $val) {
        $rows[] = $val['entity']->$key;
      }
    }
    shuffle($rows);
    $values = array_slice($rows, 0, $num);

    $this->fillEntityreferenceViewWidget($field_name, $values);

    if (sizeof($values) == 1) {
      $values = $values[0];
    }

    return array(TRUE, $values, "");
  }

  public function fillDefaultTermReferenceTreeValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    return array(TRUE, array(), "");
  }

  public function fillDefaultAutocompleteDeluxeTaxonomyValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $vocabularyClassName = $this->getVocabularyClassNameFromVocabularyName(
      $field['settings']['allowed_values'][0]['vocabulary']
    );

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      if (Utils::getRandomInt(0, 1)) {
        $values[] = Utils::getRandomString();
      }
      else {
        list($success, $termObject, $msg) = $vocabularyClassName::createDefault(
        );
        if (!$success) {
          return array(FALSE, $values, $msg);
        }

        $values[] = $termObject->getLabel();
      }
    }

    $this->fillAutocompleteDeluxeTaxonomy($field_name, $values);

    return array(TRUE, $values, "");
  }

  /**
   * __call Magic method. If function name matches the pattern
   * fillDefault*Values, then fillDefaultFieldValues() function is called with
   * the appropriate field name. If function name matches "fill*", then
   * fillFieldValues() function is called with appropriate field name and
   * arguments.
   *
   * @param string $name
   *   Function name.
   * @param $arguments
   *   Arguments passed to the function.
   *
   * @return array|mixed
   *   Values returned by the matching function.
   */
  public function __call($name, $arguments) {
    if ($this->isFillDefaultFieldValuesFunction($name)) {
      $field_name = Utils::makeSnakeCase(substr($name, 11, -6));

      return $this->fillDefaultFieldValues($field_name);
    }
    elseif ($this->isFillFieldValuesFunction($name)) {
      $field_name = Utils::makeSnakeCase(substr($name, 4));

      return $this->fillFieldValues($field_name, $arguments);
    }
  }

  /**
   * Fill specified field with the provided values.
   *
   * @param string $field_name
   *   Field name.
   * @param string|int|array $values
   *   Value that needs to be filled.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were actually filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public function fillFieldValues($field_name, $values) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    if (!is_null($field) && !is_null($instance)) {
      return Field::fillValues($this, $field_name, $values);
    }

    // $field_name is a property.
    if (is_array($values)) {
      $values = $values[0];
    }
    $this->fillValues(array($field_name => $values));

    return array(TRUE, $values, "");
  }

  public function fillDefaultFieldValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    if (!is_null($field) && !is_null($instance)) {
      return Field::fillDefaultValues($this, $field_name);
    }

    $function = "fillDefault" . Utils::makeTitleCase($field_name) . "Values";

    return $this->$function();
  }

  /**
   * Fills default values in all the fields except that are asked to be
   * skipped.
   *
   * @param array $skip
   *   An array of field names which are not supposed to be filled by default
   *   values.
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
  public function fillDefaultValuesExcept($skip = array()) {
    // First get all field instances.
    $field_instances = $this->entityObject->getFieldInstances();

    // Iterate over all the field instances and unless they are in $skip array,
    // fill default values for them.
    $fields = array();
    foreach ($field_instances as $field_name => $field_instance) {
      if (!in_array($field_name, $skip)) {
        $function = "fillDefault" . Utils::makeTitleCase(
            $field_name
          ) . "Values";
        list($success, $values, $msg) = $this->$function();
        $fields[$field_name] = $values;
        if (!$success) {
          return array(FALSE, $fields, $msg);
        }
      }
    }

    return array(TRUE, $fields, "");
  }

  /**
   * Returns the number of values to be filled in the field based on the
   * field's cardinality. If cardinality is unlimited, then a random integer
   * between 2 and 5 (inclusive) is returned. If cardinality is 1, then 1 is
   * returned. If cardinality is any other number, then a random integer
   * between 2 and that integer is returned.
   *
   * @param int $cardinality
   *   Field's cardinality.
   *
   * @return int
   *   Number of values to be filled in the field.
   */
  private function getNumberOfItemsFromCardinality($cardinality) {
    if ($cardinality == -1) {
      $num = Utils::getRandomInt(2, 5);

      return $num;
    }
    elseif ($cardinality == 1) {
      $num = 1;

      return $num;
    }
    else {
      $num = Utils::getRandomInt(2, $cardinality);

      return $num;
    }
  }

  /**
   * @param $vocabulary
   *
   * @return mixed|string
   */
  private function getVocabularyClassNameFromVocabularyName($vocabulary) {
    $vocabularyClassName = Utils::makeTitleCase(
      str_replace(" ", "_", $vocabulary)
    );
    $base_path = "tests\\phpunit_tests\\custom\\entities\\taxonomy_term\\";
    $vocabularyClassName = $base_path . $vocabularyClassName;

    return $vocabularyClassName;
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
  private function getFieldInstance($field_name) {
    list(, , $bundle) = entity_extract_ids(
      $this->entityObject->getEntityType(),
      $this->entityObject->getEntity()
    );
    $instance = field_info_instance(
      $this->entityObject->getEntityType(),
      $field_name,
      $bundle
    );

    return $instance;
  }

  /**
   * Returns field information.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   Field information array.
   */
  private function getFieldInfo($field_name) {
    return field_info_field($field_name);
  }

  /**
   * Returns field information, field instance information and number of values
   * to be filled in the field.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array of field information, field instance information and the number
   *   of values to be filled in the field.
   */
  public function getFieldDetails($field_name) {
    $instance = NULL;
    $num = 0;
    $field = $this->getFieldInfo($field_name);
    if (!is_null($field)) {
      $instance = $this->getFieldInstance($field_name);
      $num = $this->getNumberOfItemsFromCardinality($field['cardinality']);
    }

    return array($field, $instance, $num);
  }

  /**
   * Returns whether the function name matches the pattern to fill a field with
   * default values.
   *
   * @param string $name
   *   Function name.
   *
   * @return bool
   *   TRUE if it matches and FALSE if not.
   */
  private function isFillDefaultFieldValuesFunction($name) {
    // Check if function name starts with "fillDefault" and ends with "Values".
    return (strpos($name, 'fillDefault') === 0 && strrpos(
        $name,
        'Values'
      ) == strlen($name) - 6);
  }

  /**
   * Returns whether the function name matches the pattern to fill a field with
   * provided values.
   *
   * @param string $name
   *   Function name.
   *
   * @return bool
   *   TRUE if it matches and FALSE if not.
   */
  private function isFillFieldValuesFunction($name) {
    // Check if function name starts with "fill".
    return strpos($name, 'fill') === 0;
  }
}

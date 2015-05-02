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

abstract class EntityForm extends Form {

  /**
   * @var Entity
   */
  private $entityObject;

  /**
   * Returns the entity object.
   *
   * @return Entity
   *   Entity object.
   */
  public function getEntityObject() {
    return $this->entityObject;
  }

  /**
   * Sets the entity object.
   *
   * @param $entityObject
   *   Entity object.
   */
  public function setEntityObject($entityObject) {
    $this->entityObject = $entityObject;
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
      $field_name = Utils::makeSnakeCase(substr($name, 4, -6));
      $arguments = array_shift($arguments);

      return $this->fillFieldValues($field_name, $arguments);
    }
    else {
      return parent::__call($name, $arguments);
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
   *   (2) $values: Values that were actually filled in $form_state.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public function fillFieldValues($field_name, $values) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    if (!is_null($field) && !is_null($instance)) {
      $short_field_class = Utils::makeTitleCase($field['type']);
      $field_class = "RedTest\\core\\fields\\" . $short_field_class;

      return $field_class::fillValues($this, $field_name, $values);
    }

    // $field_name is a property.
    $values = Utils::normalize($values);
    $this->fillValues(array($field_name => $values));

    return array(TRUE, $values, "");
  }

  /**
   * Fill specified field with randomly generated values.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were actually filled in $form_state.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public function fillDefaultFieldValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    if (!is_null($field) && !is_null($instance)) {
      $short_field_class = Utils::makeTitleCase($field['type']);
      $field_class = "RedTest\\core\\Fields\\" . $short_field_class;

      return $field_class::fillDefaultValues($this, $field_name);
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
  public function fillDefaultValues($skip = array()) {
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
    return (strpos($name, 'fill') === 0 && strrpos($name, 'Values') == strlen(
        $name
      ) - 6);
  }

  public function fillMultiValued($field_name, $values) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    $short_field_class = Utils::makeTitleCase($field['type']);
    $field_class = "RedTest\\core\\Fields\\" . $short_field_class;

    $original_values = $this->getValues($field_name);
    $original_values = !empty($original_values[LANGUAGE_NONE]) ? $original_values[LANGUAGE_NONE] : array();
    unset($original_values['add_more']);
    $input_replace = array_slice($values, 0, sizeof($original_values), TRUE);
    $input = $input_replace;

    $return = array();
    if (sizeof($values) > sizeof($original_values)) {
      $this->setValues($field_name, array(LANGUAGE_NONE => $input));
      $input_add = array_slice($values, sizeof($original_values), NULL, TRUE);
      foreach ($input_add as $key => $value) {
        $triggering_element_name = $field_class::getTriggeringElementName($field_name, $key);
        $this->addMore($field_name, $input, $triggering_element_name);
        $input[] = $value;
        $this->setValues($field_name, array(LANGUAGE_NONE => $input));
      }
      $return = $input;
    }
    elseif (sizeof($input) < sizeof($original_values) - 1) {
      $return = $input;
      for ($i = sizeof($input); $i < sizeof($original_values) - 1; $i++) {
        $input[] = $field_class::getEmptyValue($this, $field_name);
      }
      $this->setValues($field_name, array(LANGUAGE_NONE => $input));
    }
    else {
      $return = $input;
      $this->setValues($field_name, array(LANGUAGE_NONE => $input));
    }

    return $return;
  }
}

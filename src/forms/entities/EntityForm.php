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
      array_unshift($arguments, $field_name);

      return call_user_func_array(
        array($this, 'fillDefaultFieldValues'),
        $arguments
      );
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
   * @param string|array $field_name
   *   Field name if it is present at top-level form element. If it is not at
   *   the top-level form element, then provide an array.
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

    // $field_name is a property or is a field but is not a CCK field.
    $this->fillValues($field_name, $values);
    //$this->fillValues(, array($field_name => $values));

    return array(TRUE, $values, "");
  }

  /**
   * Fill specified field with randomly generated values.
   *
   * @param string $field_name
   *   Field name.
   * @param array $options
   *   Options array.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were actually filled in $form_state.
   *   (3) $msg: Error message if $success is FALSE and empty string otherwise.
   */
  public function fillDefaultFieldValues($field_name, $options = array()) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    if (!is_null($field) && !is_null($instance)) {
      $short_field_class = Utils::makeTitleCase($field['type']);
      $field_class = "RedTest\\core\\fields\\" . $short_field_class;

      return $field_class::fillDefaultValues($this, $field_name, $options);
    }

    $function = "fillDefault" . Utils::makeTitleCase($field_name) . "Values";

    return parent::__call($function, array($options));
  }

  /**
   * Fills default values in fields.
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
  public function fillDefaultValues($options = array()) {
    $options += array(
      'skip' => array(),
      'required_fields_only' => TRUE,
    );

    // First get all field instances.
    $field_instances = $this->entityObject->getFieldInstances();

    // Iterate over all the field instances and unless they are in
    // $options['skip'] array, fill default values for them.
    $fields = array();
    foreach ($field_instances as $field_name => $field_instance) {
      $required_function_name = 'is' . Utils::makeTitleCase(
          $field_name
        ) . 'Required';
      if ($options['required_fields_only'] && !$this->$required_function_name()
      ) {
        // Check if the field is required. We use '#required' key in form array
        // since it can be set or unset using custom code.
        // Field is not required. There is no need to fill this field.
        continue;
      }

      if (in_array($field_name, $options['skip'])) {
        // Field needs to be skipped.
        continue;
      }

      $function = "fillDefault" . Utils::makeTitleCase(
          $field_name
        ) . "Values";
      list($success, $values, $msg) = $this->$function($options);
      $fields[$field_name] = $values;
      if (!$success) {
        return array(FALSE, $fields, $msg);
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
  protected function getFieldInstance($field_name) {
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

  /**
   * Fill values in a multi-valued field.
   *
   * @param string $field_name
   *   Field name.
   * @param array $values
   *   Field values array.
   * @param int $offset
   *   Offset for replacement. In some fields, an empty valued field has NULL
   *   value in form_state. Use 1 for such a field. In other cases, an empty
   *   multi-values field has one value which is empty. Use 0 in such a case.
   *
   * @return array
   *   An array with the following values:
   *   (a) $success: Whether multi-valued field could be filled.
   *   (b) $return: The actual values filled.
   *   (c) $msg: An error message if the values could not be filled, an empty
   *   string otherwise.
   */
  public function fillMultiValued($field_name, $values, $offset = 0) {
    if (is_null($values)) {
      $values = array();
    }

    if (is_string($values) || is_numeric($values)) {
      $values = array($values);
    }

    $field = $this->getFieldInfo($field_name);
    $short_field_class = Utils::makeTitleCase($field['type']);
    $field_class = "RedTest\\core\\fields\\" . $short_field_class;

    $original_values = $this->getValues($field_name);
    $original_values = !empty($original_values[LANGUAGE_NONE]) ? $original_values[LANGUAGE_NONE] : array();
    if (isset($original_values['add_more'])) {
      // If the form has an "add_more" key, that means that there is one less
      // field that is available to us for filling without pressing "Add More" button.
      $offset -= 1;
    }
    $threshold = sizeof($original_values) + $offset;
    // $input_replace is an array of input values that can be replaced into
    // existing fields without pressing "Add More" button.
    $input_replace = array_slice($values, 0, $threshold, TRUE);
    $input = $input_replace;

    $return = array();
    if (sizeof($values) > $threshold) {
      // Number of input values is more than the number of fields available
      // without pressing Add More button. We fill the available fields with the
      // input values and for each remaining input value, we need to press "Add
      // More" button.
      $this->setValues($field_name, array(LANGUAGE_NONE => $input));

      // $input_add is the remaining input values for which we need to press
      // "Add More" button.
      $input_add = array_slice($values, $threshold, NULL, TRUE);
      foreach ($input_add as $key => $value) {
        $triggering_element_name = $field_class::getTriggeringElementName(
          $field_name,
          $key
        );
        list($success, $msg) = $this->pressButton(
          $triggering_element_name,
          array('ajax' => TRUE)
        );
        if (!$success) {
          return array(FALSE, $input, $msg);
        }
        $input[] = $value;
        $this->setValues($field_name, array(LANGUAGE_NONE => $input));
      }
      $return = $input;
    }
    elseif (sizeof($input) < $threshold - 1) {
      // Number of input values is less than the number of fields available
      // without pressing Add More button. We clear out all the available fields
      // and fill them with the new values.
      $return = $input;
      for ($i = sizeof($input); $i < $threshold - 1; $i++) {
        $input[] = $field_class::getEmptyValue($this, $field_name);
      }
      $this->setValues($field_name, array(LANGUAGE_NONE => $input));
    }
    else {
      $return = $input;
      if (is_array($input) && !sizeof($input)) {
        // $input is an empty array, which means we need to make it empty.
        $input[] = $field_class::getEmptyValue($this, $field_name);
      }
      $this->setValues($field_name, array(LANGUAGE_NONE => $input));
    }

    return array(TRUE, $return, "");
  }

  public function processBeforeSubmit() {
    // First get all field instances.
    $field_instances = $this->getEntityObject()->getFieldInstances();

    // Iterate over all the field instances and if the field is to be filled,
    // then process it.
    foreach ($field_instances as $field_name => $field_instance) {
      list($field_class, $widget_type) = Field::getFieldClass(
        $this,
        $field_name
      );

      $field_class::processBeforeSubmit($this, $field_name);
    }
  }

  public function processAfterSubmit() {
    // First get all field instances.
    $field_instances = $this->getEntityObject()->getFieldInstances();

    // Iterate over all the field instances and if the field is to be filled,
    // then process it.
    foreach ($field_instances as $field_name => $field_instance) {
      list($field_class, $widget_type) = Field::getFieldClass(
        $this,
        $field_name
      );

      $field_class::processAfterSubmit($this, $field_name);
    }
  }
}

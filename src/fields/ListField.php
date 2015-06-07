<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 3/28/15
 * Time: 7:24 PM
 */

namespace RedTest\core\fields;

use RedTest\core\forms\Form;
use RedTest\core\Utils;

class ListField extends Field {

  /**
   * Fill checkboxes field with random values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param array $options
   *   Options array.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether default values could be filled in the field.
   *   (2) $values: Values that were filled for the field.
   *   (3) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillDefaultOptionsButtonsValues(
    Form $formObject,
    $field_name,
    $options = array()
  ) {
    $num = 1;
    $allowed_values = array();
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $allowed_values = array_keys($field['settings']['allowed_values']);
    }

    $called_class = get_called_class();
    $values = $called_class::generateListValues($allowed_values, $num);

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  public static function fillOptionsButtonsValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $formObject->emptyField($field_name);

    if (is_string($values) || is_numeric($values)) {
      $values = array($values);
    }

    $input = array();
    $output = array();
    $success = FALSE;
    $msg = '';
    if (sizeof($values)) {
      foreach ($values as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
          // If value is 0, then make it a string otherwise it will be
          // interpreted as not selected.
          $output[] = $value;
          $input[$value] = ($value === 0 ? strval($value) : $value);
        }
      }

      list($success, , $msg) = $formObject->fillValues(
        $field_name,
        array(LANGUAGE_NONE => $input)
      );
    }
    else {
      // Input is empty.
      list($success, , $msg) = $formObject->fillValues(
        $field_name,
        array(LANGUAGE_NONE => NULL)
      );
    }

    return array($success, Utils::normalize($output), $msg);
  }

  public static function fillDefaultOptionsSelectValues(
    Form $formObject,
    $field_name
  ) {
    $num = 1;
    $allowed_values = array();
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $allowed_values = array_keys($field['settings']['allowed_values']);
    }

    $called_class = get_called_class();
    $values = $called_class::generateListValues($allowed_values, $num);

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  public static function fillOptionsSelectValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $formObject->emptyField($field_name);

    if (is_string($values) || is_numeric($values)) {
      $values = array($values);
    }

    $input = array();
    $success = TRUE;
    $msg = '';
    if (sizeof($values)) {
      foreach ($values as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
          // If value is 0, then make it a string otherwise it will be
          // interpreted as not selected.
          $input[$value] = ($value === 0 ? strval($value) : $value);
        }
      }

      list($success, $output, $msg) = $formObject->fillValues($field_name, array(LANGUAGE_NONE => $input));
      $input = $output[LANGUAGE_NONE];
    }

    return array($success, $input, $msg);
  }

  /**
   * Generate a list of values.
   *
   * @param array $allowed_values
   *   Allowed values.
   * @param int $num
   *   Number of values to select.
   *
   * @return mixed|array
   *   A float, int or text if only one value was to be returned, an array of
   *   floats, int or text otherwise.
   */
  protected static function generateListValues($allowed_values, $num = 1) {
    $selected_keys = array_rand(
      $allowed_values,
      min($num, sizeof($allowed_values))
    );
    if (is_numeric($selected_keys)) {
      $selected_keys = array($selected_keys);
    }
    $values = array();
    foreach ($selected_keys as $selected_key) {
      $values[] = $allowed_values[$selected_key];
    }

    return Utils::normalize($values);
  }

  public static function compareValues($actual_values, $values) {
    $field_class = get_called_class();

    $actual_values = $field_class::formatValuesForCompare($actual_values);
    $values = $field_class::formatValuesForCompare($values);

    if (sizeof($actual_values) != sizeof($values)) {
      return array(FALSE, "Number of values do not match.");
    }

    foreach ($values as $index => $value) {
      if ($actual_values[$index] != $value) {
        return array(FALSE, "Index $index does not match.");
      }
    }

    return array(TRUE, "");
  }

  /**
   * Formats the values so that they can be compared.
   *
   * @param string|int|array $values
   *   Following formats are acceptable:
   *   (a) 12
   *   (b) "red"
   *   (a) array(12, "red")
   *   (b) array(12 => 12, "red" => "red")
   *   (c) array(
   *         array(
   *           'value' => 12,
   *         ),
   *         array(
   *           'value' => 'red',
   *         ),
   *       )
   *
   * @return array
   *   An array of list values: array(12, "red")
   */
  public static function formatValuesForCompare($values) {
    if (empty($values)) {
      return array();
    }

    $output = array();
    if (is_string($values) || is_numeric($values)) {
      $output[] = $values;
    }
    elseif (is_array($values)) {
      foreach ($values as $index => $value) {
        if (is_string($value) || is_numeric($value)) {
          $output[] = $value;
        }
        elseif (is_array($value) && array_key_exists('value', $value)) {
          $output[] = $value['value'];
        }
      }
    }

    return $output;
  }
}
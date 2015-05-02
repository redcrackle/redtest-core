<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 3/25/15
 * Time: 12:16 PM
 */

namespace RedTest\core\fields;

use RedTest\core\forms\Form;
use RedTest\core\Utils;

class Email extends Field {

  /**
   * Fills email field with default values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillDefaultValues(Form $formObject, $field_name) {
    $num = 1;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
    }

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[] = Utils::getRandomEmail();
    }

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  /**
   * Fill Email field with specified values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param array|string $values
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillValues(Form $formObject, $field_name, $values) {
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $formObject->emptyField($field_name);

    $values = self::normalizeInputForCompare($values);

    $input = array();
    $index = 0;
    foreach ($values as $key => $value) {
      if ($index >= 1) {
        $triggering_element_name = $field_name . '_add_more';
        $formObject->addMore($field_name, $input, $triggering_element_name);
      }
      $input[$index] = array('email' => $value);
      $formObject->setValues($field_name, array(LANGUAGE_NONE => $input));
      $index++;
    }

    return array(TRUE, Utils::normalize($input), "");
  }

  /**
   * Compares two arrays filled with email ids.
   *
   * @param string|array $actual_values
   *   Actual values that are in the entity.
   * @param string|array $values
   *   Values to compare field values against.
   *
   * @return array
   */
  public static function compareValues($actual_values, $values) {
    $field_class = get_called_class();

    $actual_values = $field_class::normalizeInputForCompare($actual_values);
    $values = $field_class::normalizeInputForCompare($values);

    if (sizeof($actual_values) != sizeof($values)) {
      return array(FALSE, "Number of values do not match.");
    }

    foreach ($values as $key => $value) {
      if ($value != $actual_values[$key]) {
        return array(FALSE, "Key " . $key . " does not match.");
      }
    }

    return array(TRUE, "");
  }

  /**
   * Standardizes the values array so that they can be compared.
   *
   * @param string|array $values
   *   Acceptable formats are:
   *   (a) "john@doe.com"
   *   (b) array("john@doe.com", "matt@doe.com")
   *   (c) array(
   *         array(
   *           'email' => 'john@doe.com',
   *         ),
   *         array(
   *           'email' => 'matt@doe.com',
   *         ),
   *       )
   *
   * @return array
   *   An array of emails. Example is array("john@doe.com", "matt@doe.com")
   */
  public static function normalizeInputForCompare($values) {
    $output = array();
    if (!empty($values) && (is_string($values))) {
      $output[] = $values;
    }
    elseif (is_array($values)) {
      foreach ($values as $key => $value) {
        if (!empty($values) && (is_string($value))) {
          $output[] = $value;
        }
        elseif (is_array($value) && array_key_exists('email', $value)) {
          $output[] = $value['email'];
        }
      }
    }

    return $output;
  }
}
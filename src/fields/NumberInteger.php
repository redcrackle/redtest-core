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

class NumberInteger extends Field {

  /**
   * Fill random integer values in the provided field.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether values could be filled in the field.
   *   (2) $values: Values that were filled for the field.
   *   (3) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillDefaultValues(Form $formObject, $field_name) {
    $num = 1;
    $min = -255;
    $max = 255;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      if (!empty($instance['settings']['min'])) {
        $min = $instance['settings']['min'];
      }
      if (!empty($instance['settings']['max'])) {
        $max = $instance['settings']['max'];
      }
    }

    $values = Utils::getRandomInt($min, $max, $num);

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  /**
   * Fills provided float values in the field.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param mixed $values
   *   Following formats are acceptable:
   *   (a) 23
   *   (b) array(23, -89)
   *   (c) array(array('value' => 23), array('value' => -89))
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether values could be filled in the field.
   *   (2) $values: Values that were filled for the field.
   *   (3) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillValues(Form $formObject, $field_name, $values) {
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $formObject->emptyField($field_name);

    $values = self::normalizeInput($values);

    $input = array();
    $index = 0;
    foreach ($values as $key => $value) {
      if ($index >= 1) {
        $triggering_element_name = $field_name . '_add_more';
        $formObject->addMore($field_name, $input, $triggering_element_name);
      }
      $input[$index] = array('value' => $value);
      $formObject->setValues($field_name, array(LANGUAGE_NONE => $input));
      $index++;
    }

    return array(TRUE, Utils::normalize($values), "");
  }

  /**
   * Compare the decimal values with the actual values.
   *
   * @param mixed $actual_values
   *   Following formats are acceptable:
   *   (a) 23.67
   *   (b) array(23.34, -89.12)
   *   (c) array(array('value' => 23.34), array('value' => -89.12))
   * @param mixed $values
   *   Following formats are acceptable:
   *   (a) 23.67
   *   (b) array(23.34, -89.12)
   *   (c) array(array('value' => 23.34), array('value' => -89.12))
   *
   * @return array
   *   An array with 2 values:
   *   (a) $success: Whether the values are the equal.
   *   (b) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function compareValues($actual_values, $values) {
    $actual_values = self::normalizeInput($actual_values);
    $values = self::normalizeInput($values);

    if (sizeof($values) != sizeof($actual_values)) {
      return array(FALSE, "Number of values do not match.");
    }

    foreach ($values as $key => $value) {
      if ($actual_values[$key] != $value) {
        return array(FALSE, "Key $key does not match.");
      }
    }

    return array(TRUE, "");
  }

  /**
   * Format the values into a standardized format for easy comparison.
   *
   * @param string|float|array $values
   *   Following formats are acceptable:
   *   (a) 23.67
   *   (b) array(23.34, -89.12)
   *   (c) array(array('value' => 23.34), array('value' => -89.12))
   *
   * @return array
   *   An array in standardized format for comparison. An example is:
   *   array(23.34, -89.12)
   */
  public static function normalizeInput($values) {
    $output = array();

    if (self::isValidValue($values)) {
      $output[] = $values;
    }
    elseif (is_array($values)) {
      foreach ($values as $key => $value) {
        if (self::isValidValue($value)) {
          $output[] = $value;
        }
        elseif (is_array($value) && array_key_exists(
            'value',
            $value
          ) && self::isValidValue($value['value'])
        ) {
          $output[] = $value['value'];
        }
      }
    }

    return $output;
  }

  /**
   * Checks whether the input is a valid decimal number.
   *
   * @param mixed $value
   *   Provided value.
   *
   * @return bool
   *   Whether the provided input is a valid integer or not.
   */
  private static function isValidValue($value) {
    if (!empty($value) && is_numeric($value)) {
      return TRUE;
    }

    return FALSE;
  }
}
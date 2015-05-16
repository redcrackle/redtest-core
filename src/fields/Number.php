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

class Number extends Field {

  /**
   * Fills provided decimal values in the field.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param mixed $values
   *   Following formats are acceptable:
   *   (a) 23.67
   *   (b) array(23.34, -89.12)
   *   (c) array(array('value' => 23.34), array('value' => -89.12))
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether values could be filled in the field.
   *   (2) $values: Values that were filled for the field.
   *   (3) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $field_class = get_called_class();

    list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
    $decimal_separator = $field['settings']['decimal_separator'];

    $values = $field_class::normalizeInput($values, $decimal_separator);
    $input = $field_class::addCorrectDecimalSeparator(
      $values,
      $decimal_separator
    );
    $input = $field_class::formatValuesForInput($input);

    list($success, $return, $msg) = $formObject->fillMultiValued($field_name, $input);
    if (!$success) {
      return array(FALSE, Utils::normalize($return), $msg);
    }

    return array(TRUE, Utils::normalize($values), "");
  }

  /**
   * Returns an empty field value.
   *
   * @param Form $formObject
   *   Form object.
   * @param $field_name
   *   Field name.
   *
   * @return array
   *   An empty field value array.
   */
  public static function getEmptyValue(Form $formObject, $field_name) {
    return array('value' => '');
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
   * @param array $field
   *   Field information array.
   *
   * @return array
   *   An array with 2 values:
   *   (a) $success: Whether the values are the equal.
   *   (b) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function compareValues($actual_values, $values, $field) {
    $field_class = get_called_class();

    $decimal_separator = NULL;
    if (!empty($field['settings']['decimal_separator'])) {
      $decimal_separator = $field['settings']['decimal_separator'];
    }

    $actual_values = $field_class::normalizeInput(
      $actual_values,
      $decimal_separator
    );
    $values = $field_class::normalizeInput($values, $decimal_separator);

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
   * @param null|string $decimal_separator
   *   Decimal separator
   *
   * @return array
   *   An array in standardized format for comparison. An example is:
   *   array(23.34, -89.12)
   */
  public static function normalizeInput($values, $decimal_separator = NULL) {
    $field_class = get_called_class();

    $output = array();

    if ($field_class::isValidValue($values, $decimal_separator)) {
      $output[] = is_null($decimal_separator) ? $values : str_replace(
        $decimal_separator,
        '.',
        $values
      );
    }
    elseif (is_array($values)) {
      foreach ($values as $key => $value) {
        if ($field_class::isValidValue($value, $decimal_separator)) {
          $output[] = is_null($decimal_separator) ? $value : str_replace(
            $decimal_separator,
            '.',
            $value
          );
        }
        elseif (is_array($value) && array_key_exists(
            'value',
            $value
          ) && $field_class::isValidValue($value['value'], $decimal_separator)
        ) {
          $output[] = is_null(
            $decimal_separator
          ) ? $value['value'] : str_replace(
            $decimal_separator,
            '.',
            $value['value']
          );
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
   * @param null|string $decimal_separator
   *   Decimal separator.
   *
   * @return bool
   *   Whether the provided input is a valid decimal number or not.
   */
  protected static function isValidValue($value, $decimal_separator = NULL) {
    if (isset($value) && (is_string($value) || is_numeric($value))) {
      $value = is_null($decimal_separator) ? $value : str_replace(
        $decimal_separator,
        '.',
        $value
      );
      if (is_numeric($value)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Replaces "." by the provided decimal separator.
   *
   * @param array $values
   *   An array of decimal values.
   * @param null|string $decimal_separator
   *   Decimal separator.
   *
   * @return array
   *   An array of values with "." replaced by provided decimal separator.
   */
  protected static function addCorrectDecimalSeparator(
    $values,
    $decimal_separator = NULL
  ) {
    $output = array();
    foreach ($values as $value) {
      $output[] = is_null($decimal_separator) ? $value : str_replace(
        '.',
        $decimal_separator,
        $value
      );
    }

    return $output;
  }

  protected static function formatValuesForInput($values) {
    $output = array();
    foreach ($values as $key => $value) {
      $output[$key] = array('value' => $value);
    }

    return $output;
  }
}
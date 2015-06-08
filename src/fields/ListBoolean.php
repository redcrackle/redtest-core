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
use RedTest\core\fields\Field;

/**
 * Class ListBoolean
 *
 * @package RedTest\core\fields
 */
class ListBoolean extends ListField {

  /**
   * Fill checkbox field with random values.
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
  public static function fillOptionsButtonsRandomValues(
    Form $formObject,
    $field_name,
    $options = array()
  ) {
    $num = 1;
    $required = FALSE;
    $allowed_values = array();
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $allowed_values = array_keys($field['settings']['allowed_values']);
      $required = $instance['required'];
    }

    $values = array();
    foreach ($allowed_values as $allowed_value) {
      if (Utils::getRandomInt(0, 1)) {
        $values[] = $allowed_value;
      }
    }

    if ($required && !sizeof($values)) {
      // This field is required and no checkbox has been selected. Select one
      // randomly.
      $key = array_rand($allowed_values);
      $values[] = $allowed_values[$key];
    }

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  /**
   * Fill single checkbox field with default values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether default values could be filled in the field.
   *   (2) $values: Values that were filled for the field.
   *   (3) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillOptionsOnOffRandomValues(
    Form $formObject,
    $field_name
  ) {
    $required = FALSE;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $required = $instance['required'];
    }

    $values = ($required || Utils::getRandomInt(0, 1)) ? 1 : 0;

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  /**
   * Fills the field of the form with provided values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param string|array $values
   *   Following are the acceptable formats:
   *   (a) "0", "1"
   *   (b) array(0, 1)
   *
   * @return string|array
   *   Values that are filled in the field. Following are the returned formats:
   *   (a) 0, 1
   *   (b) array(0, 1)
   *   If multiple values are filled, then the return variable will be in
   *   format (b), otherwise it will be in format (a).
   */
  public static function fillOptionsOnOffValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    if (!Field::hasFieldAccess($formObject, $field_name)) {
      return array(
        FALSE,
        "",
        "Field " . Utils::getLeaf($field_name) . " is not accessible."
      );
    }

    $formObject->emptyField($field_name);

    if (is_string($values) || is_numeric($values)) {
      // $values is in acceptable format (a).
      $values = array($values);
    }

    $input = array();
    $output = array();
    if (sizeof($values)) {
      if (sizeof($values) == 1 && ($values[0] === 0 || $values[0] === "0")) {
        if (method_exists($formObject, 'getEntityObject')) {
          $entityObject = $formObject->getEntityObject();
          list($field, $instance, $num) = Field::getFieldDetails(
            $entityObject,
            $field_name
          );
          //if ($num == 1) {
          // This is a single checkbox and value input is 0 so set it to NULL.
          $input = NULL;
          $output[0] = 0;
          /*}
          else {
            // This is a multi-valued field so 0 is a key. Set it to be a string.
            $input[0] = "0";
            $output[0] = 0;
          }*/
        }
      }
      else {
        foreach ($values as $key => $value) {
          if (is_string($value) || is_numeric($value)) {
            // $values is in acceptable format (b).
            $output[] = $value;
            $input[$value] = ($value === 0 ? strval($value) : $value);
          }
        }
      }

      list($success, , $msg) = $formObject->fillValues(
        $field_name,
        array(LANGUAGE_NONE => $input)
      );
      if (!$success) {
        return array(FALSE, Utils::normalize($output), $msg);
      }
    }

    return array(TRUE, Utils::normalize($output), "");
  }

  /**
   * Compares the values that are in the field with the ones provided. This
   * function differs from compareValues() function by the fact that before
   * comparing, a value of 0 is removed from the input array since 0 for single
   * checkbox signifies empty or unchecked.
   *
   * @param array|int|string $actual_values
   *   Following are the acceptable formats:
   *   (a) 0
   *   (b) "0", "2" (any numeric string)
   *   (d) 12 (any integer)
   *   (e) array(0, 2)
   *   (g) array(
   *         array(
   *           'value' => 0,
   *         ),
   *         array(
   *           'value' => 2,
   *         )
   *       )
   * @param array|int|string $values
   *   Following are the acceptable formats:
   *   (a) 0
   *   (b) "0", "2" (any numeric string)
   *   (d) 12 (any integer)
   *   (e) array(0, 2)
   *   (g) array(
   *         array(
   *           'value' => 0,
   *         ),
   *         array(
   *           'value' => 2,
   *         )
   *       )
   *
   * @return array
   *   An array with two values:
   *   (a) TRUE or FALSE: whether the actual values and values are the same.
   *   (b) $msg: An error message if the actual values and values are not the
   *   same, an empty string otherwise.
   */
  public static function compareOptionsOnOffValues($actual_values, $values) {
    $field_class = get_called_class();
    $actual_values = $field_class::formatOptionsOnOffValuesForCompare(
      $actual_values
    );
    $values = $field_class::formatOptionsOnOffValuesForCompare($values);

    if (sizeof($actual_values) != sizeof($values)) {
      return array(
        FALSE,
        "Number of values do not match. Actual values are: " . print_r(
          $actual_values,
          TRUE
        ) . " and expected values are " . print_r($values, TRUE) . "."
      );
    }

    foreach ($values as $key => $value) {
      if ($actual_values[$key] != $value) {
        return array(
          FALSE,
          "Key $key does not match. Actual values are: " . print_r(
            $actual_values,
            TRUE
          ) . " and expected values are " . print_r($values, TRUE) . "."
        );
      }
    }

    return array(TRUE, "");
  }

  /**
   * Compares the values that are in the field with the ones provided.
   *
   * @param array|int|string $actual_values
   *   Following are the acceptable formats:
   *   (a) 0
   *   (b) "0", "2" (any numeric string)
   *   (d) 12 (any integer)
   *   (e) array(0, 2)
   *   (g) array(
   *         array(
   *           'value' => 0,
   *         ),
   *         array(
   *           'value' => 2,
   *         )
   *       )
   * @param array|int|string $values
   *   Following are the acceptable formats:
   *   (a) 0
   *   (b) "0", "2" (any numeric string)
   *   (d) 12 (any integer)
   *   (e) array(0, 2)
   *   (g) array(
   *         array(
   *           'value' => 0,
   *         ),
   *         array(
   *           'value' => 2,
   *         )
   *       )
   *
   * @return array
   *   An array with two values:
   *   (a) TRUE or FALSE: whether the actual values and values are the same.
   *   (b) $msg: An error message if the actual values and values are not the
   *   same, an empty string otherwise.
   */
  public static function compareValues($actual_values, $values) {
    $field_class = get_called_class();
    $actual_values = $field_class::formatValuesForCompare($actual_values);
    $values = $field_class::formatValuesForCompare($values);

    if (sizeof($actual_values) != sizeof($values)) {
      return array(
        FALSE,
        "Number of values do not match. Actual values are: " . print_r(
          $actual_values,
          TRUE
        ) . " and expected values are " . print_r($values, TRUE) . "."
      );
    }

    foreach ($values as $key => $value) {
      if ($actual_values[$key] != $value) {
        return array(
          FALSE,
          "Key $key does not match. Actual values are: " . print_r(
            $actual_values,
            TRUE
          ) . " and expected values are " . print_r($values, TRUE) . "."
        );
      }
    }

    return array(TRUE, "");
  }

  /**
   * Format the On/Off checkbox field values so that they can be compared. The
   * main difference from formatValuesForCompare() function is that this
   * removes 0 value since it signifies unchecked checkbox.
   *
   * @param array|int|string $values
   *   Following are the acceptable formats:
   *   (a) 0
   *   (b) "0", "2" (any numeric string)
   *   (d) 12 (any integer)
   *   (e) array(0, 2)
   *   (g) array(
   *         array(
   *           'value' => 0,
   *         ),
   *         array(
   *           'value' => 2,
   *         )
   *       )
   *
   * @return array
   *   An array with the following format: array(2, 5). Output array will not
   *   have a value 0.
   */
  public static function formatOptionsOnOffValuesForCompare($values) {
    $output = self::formatValuesForCompare($values);

    // For OptionsOnOff widget, a value of 0 means empty so remove it.
    return array_diff($output, array(0));
  }

  /**
   * Formats the boolean list field values for comparison.
   *
   * @param array|int|string $values
   *   Following are the acceptable formats:
   *   (a) 0
   *   (b) "0", "2" (any numeric string)
   *   (d) 12 (any integer)
   *   (e) array(0, 2)
   *   (g) array(
   *         array(
   *           'value' => 0,
   *         ),
   *         array(
   *           'value' => 2,
   *         )
   *       )
   *
   * @return array
   *   An array with the following format: array(0, 2)
   */
  public static function formatValuesForCompare($values) {
    $output = array();

    $field_class = get_called_class();
    if ($field_class::isValueValid($values)) {
      $output[] = intval($values);
    }
    elseif (is_array($values)) {
      foreach ($values as $key => $value) {
        if ($field_class::isValueValid($value)) {
          $output[] = intval($value);
        }
        elseif (is_array($value) && array_key_exists(
            'value',
            $value
          ) && $field_class::isValueValid($value['value'])
        ) {
          $output[] = intval($value['value']);
        }
      }
    }

    return $output;
  }

  /**
   * Checks whether the specified value is a valid boolean list value. TRUE is
   * returned for the following values:
   *   (a) 0
   *   (b) "0"
   *   (c) "abc" (any string)
   *   (d) 12 (any integer)
   *
   * @param mixed $value
   *   Value that needs to be checked.
   *
   * @return bool
   *   TRUE if the value is a valid value for boolean list field and FALSE
   *   otherwise.
   */
  private static function isValueValid($value) {
    if (!isset($value) || $value === "") {
      return FALSE;
    }

    if (is_string($value) || is_numeric($value)) {
      return TRUE;
    }

    if ($value === "0" || $value === 0) {
      return TRUE;
    }

    return FALSE;
  }
}
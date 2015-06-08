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
   * Fill email field with random values.
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
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillDefaultValues(
    Form $formObject,
    $field_name,
    $options = array()
  ) {
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
    if (!Field::hasFieldAccess($formObject, $field_name)) {
      return array(
        FALSE,
        "",
        "Field " . Utils::getLeaf($field_name) . " is not accessible."
      );
    }

    $field_class = get_called_class();

    $values = $field_class::normalizeInputForCompare($values);
    $values = $field_class::formatValuesForInput($values);

    list($success, $return, $msg) = $formObject->fillMultiValued(
      $field_name,
      $values
    );
    if (!$success) {
      return array(FALSE, Utils::normalize($return), $msg);
    }

    return array(TRUE, Utils::normalize($return), "");
  }

  /**
   * Returns triggering element name.
   *
   * @param string $field_name
   *   Field name.
   * @param int $index
   *   Index of the element in multi-valued field.
   *
   * @return string
   *   Triggering element name.
   */
  public static function getTriggeringElementName($field_name, $index) {
    return $field_name . '_add_more';
  }

  /**
   * Returns an empty field.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An empty field.
   */
  public static function getEmptyValue($field_name) {
    return array('email' => '');
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

  /**
   * @param $values
   *
   * @return array
   */
  private static function formatValuesForInput($values) {
    $output = array();

    foreach ($values as $index => $value) {
      $output[] = array('email' => $value);
    }

    return $output;
  }
}
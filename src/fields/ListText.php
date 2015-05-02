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

class ListText extends ListField {

  public static function fillDefaultOptionsButtonsValues(
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

    $values = self::generateListValues($allowed_values, $num);

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
    if (sizeof($values)) {
      foreach ($values as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
          $input[$value] = $value;
        }
      }

      $formObject->setValues($field_name, array(LANGUAGE_NONE => $input));
    }

    return array(TRUE, $input, "");
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

    $values = self::generateListValues($allowed_values, $num);

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

    if (is_string($values)) {
      $values = array($values);
    }

    $input = array();
    if (sizeof($values)) {
      foreach ($values as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
          $input[$value] = $value;
        }
      }

      $formObject->setValues($field_name, array(LANGUAGE_NONE => $input));
    }

    return array(TRUE, $input, "");
  }

  /**
   * Generate a list of text values.
   *
   * @param array $allowed_values
   *   Allowed text values.
   * @param int $num
   *   Number of values to select.
   *
   * @return string|array
   *   A string if only one value was to be returned, an array of strings
   *   otherwise.
   */
  /*private static function generateListTextValues($allowed_values, $num = 1) {
    $selected_keys = array_rand($allowed_values, min($num, sizeof($allowed_values)));
    if (is_numeric($selected_keys)) {
      $selected_keys = array($selected_keys);
    }
    $values = array();
    foreach ($selected_keys as $selected_key) {
      $values[] = $allowed_values[$selected_key];
    }

    return Utils::normalize($values);
  }*/
}
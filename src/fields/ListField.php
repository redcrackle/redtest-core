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
} 
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

  public static function fillDefaultValues(
    Form $formObject,
    $field_name
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

  public static function fillValues(
    Form $formObject,
    $field_name,
    $values
  ) {
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

  public static function compareValues($actual_values, $values) {
    $field_class = get_called_class();

    $actual_values = $field_class::normalizeInputForCompare($actual_values);
    $values = $field_class::normalizeInputForCompare($values);

    xdebug_break();
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

  public static function normalizeInputForCompare($values) {
    $output = array();
    if (!empty($values) && (is_string($values) || is_numeric($values))) {
      $output[] = $values;
    }
    elseif (is_array($values)) {
      foreach ($values as $key => $value) {
        if (!empty($values) && (is_string($value) || is_numeric($value))) {
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
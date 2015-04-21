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

class NumberDecimal extends Field {

  public static function fillDefaultNumberValues(
    Form $formObject,
    $field_name
  ) {
    $num = 1;
    $min = -255;
    $max = 255;
    $scale = 2;
    $decimal_separator = '.';
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      if (!empty($instance['settings']['min'])) {
        $min = $instance['settings']['min'];
      }
      if (!empty($instance['settings']['max'])) {
        $max = $instance['settings']['max'];
      }
      $scale = $field['settings']['scale'];
      $decimal_separator = $field['settings']['decimal_separator'];
    }

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      // We are assuming that precision is set correctly to accommodate min and
      // max values.
      $min_int = $min * pow(10, $scale);
      $max_int = $max * pow(10, $scale);
      $number = Utils::getRandomInt($min_int, $max_int) / pow(10, $scale);
      $number = str_replace(".", $decimal_separator, $number);
      $values[] = $number;
    }

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  public static function fillNumberValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $formObject->emptyField($field_name);

    if (is_string($values) || is_numeric($values)) {
      $values = array($values);
    }

    $input = array();
    $index = 0;
    foreach ($values as $key => $value) {
      $input[$index] = array('value' => $value);
      $triggering_element_name = $field_name . '_add_more';
      //$triggering_element_value = 'Add another item';
      $formObject->addMore($field_name, $input, $triggering_element_name);
      $index++;
    }

    return array(TRUE, Utils::normalize($values), "");
  }
}
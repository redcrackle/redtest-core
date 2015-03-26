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

  public static function fillDefaultValues(Form $formObject, $field_name) {
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $function = 'fillDefault' . Utils::makeTitleCase(
          $instance['widget']['type']
        ) . 'Values';

      return self::$function($formObject, $field_name);
    }
  }

  public static function fillDefaultNumberValues(
    Form $formObject,
    $field_name
  ) {
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

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      // We are assuming that precision is set correctly to accommodate min and
      // max values.
      $values[] = Utils::getRandomInt($min, $max);
    }

    return self::fillNumberValues($formObject, $field_name, $values);
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
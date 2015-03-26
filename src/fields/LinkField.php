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

class LinkField extends Field {

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

  public static function fillDefaultLinkFieldValues(
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
      $values[] = array(
        'title' => Utils::getRandomText(25),
        'url' => Utils::getRandomUrl(),
      );
    }

    return self::fillLinkFieldValues($formObject, $field_name, $values);
  }

  public static function fillLinkFieldValues(
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
      if (is_string($value)) {
        $input[$index] = array(
          'url' => $value
        );
      }
      else {
        $input[$index] = $value;
      }

      $triggering_element_name = $field_name . '_add_more';
      //$triggering_element_value = 'Add another item';
      $formObject->addMore($field_name, $input, $triggering_element_name);
      $index++;
    }

    //$formObject->setValues($field_name, array(LANGUAGE_NONE => $input));

    return array(TRUE, Utils::normalize($input), "");
  }
}
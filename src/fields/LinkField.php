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
    $show_url = 0;
    $show_title = 'required';
    $link_target = 'default';
    $show_link_class = 0;
    $show_link_title = 0;
    $title_maxlength = 128;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $show_title = $instance['settings']['title'];
      $show_url = $instance['settings']['url'];
      $title_maxlength = $instance['settings']['title_maxlength'];
      $link_target = $instance['settings']['attributes']['target'];
      $show_link_class = $instance['settings']['attributes']['configurable_class'];
      $show_link_title = $instance['settings']['attributes']['configurable_title'];
    }

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $value = array();
      if ($show_url != 'optional' || Utils::getRandomBool()) {
        $value['url'] = Utils::getRandomUrl();
      }
      if ($show_title == 'required' || empty($value['url']) || ($show_title == 'optional' && Utils::getRandomBool())) {
        $value['title'] = Utils::getRandomText($title_maxlength);
      }
      if ($link_target == 'user' && Utils::getRandomBool()) {
        $value['attributes']['target'] = '_blank';
      }
      if ($show_link_class) {
        $value['attributes']['class'] = Utils::getRandomString(10);
      }
      if ($show_link_title) {
        $value['attributes']['title'] = Utils::getRandomText(15);
      }

      $values[] = $value;
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
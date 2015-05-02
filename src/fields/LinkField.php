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
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(TRUE, "", "");
    }

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
      if ($show_url !== 'optional' || Utils::getRandomBool()) {
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

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  public static function fillValues(Form $formObject, $field_name, $values) {
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $formObject->emptyField($field_name);

    $values = self::denormalizeInput($values);

    $input = array();
    $index = 0;
    foreach ($values as $key => $value) {
      if ($index >= 1) {
        $triggering_element_name = self::getTriggeringElementName($field_name);
        $formObject->addMore($field_name, $input, $triggering_element_name);
      }
      $input[$index] = self::createInput($value);
      $formObject->setValues($field_name, array(LANGUAGE_NONE => $input));
      $index++;
    }

    return array(TRUE, Utils::normalize($input), "");
  }

  /**
   * Returns name of the triggering element based on field name.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return string
   *   Triggering element name.
   */
  private static function getTriggeringElementName($field_name) {
    return $field_name . '_add_more';
  }

  /**
   * Creates an input array based on the provided values.
   *
   * @param string|array $value
   *   Provided values.
   *
   * @return mixed
   *   Input array that can be sent in form POST.
   */
  private static function createInput($value) {
    if (is_string($value)) {
      $input = array(
        'url' => $value
      );
    }
    else {
      $input = $value;
    }

    return $input;
  }

  /**
   * Normalizes the input values so that they are in the acceptable input
   * format.
   *
   * @param string|array $values
   *   A string URL or an array of URLs.
   *
   * @return array
   *   Standardized format: array of URLs.
   */
  private static function denormalizeInput($values) {
    if (is_string($values)) {
      $values = array($values);
    }

    return $values;
  }

  public static function compareValues($actual_values, $values) {
    $actual_values = self::formatValuesForCompare($actual_values);
    $values = self::formatValuesForCompare($values);

    if (sizeof($values) != sizeof($actual_values)) {
      return array(FALSE, "Number of values do not match.");
    }

    foreach ($values as $index => $value_array) {
      foreach ($value_array as $key => $value) {
        if ($actual_values[$index][$key] != $value) {
          return array(FALSE, "Key $key does not match for index $index.");
        }
      }
    }

    return array(TRUE, "");
  }

  /**
   * Format the value so that it can be compared.
   *
   * @param string|array $values
   *   Acceptable formats are:
   *   (a) 'http://redcrackle.com'
   *   (b) array('http://redcrackle.com', 'http://www.google.com')
   *   (c) array(
   *         'url' => 'http://redcrackle.com',
   *         'title' => 'Red Crackle',
   *       )
   *   (d) array(
   *         array(
   *           'url' => 'http://redcrackle.com',
   *           'title' => 'Red Crackle',
   *         ),
   *         array(
   *           'url' => 'http://www.google.com',
   *           'title' => 'Google',
   *         ),
   *       )
   *
   * @return array
   *   array(
   *     array(
   *       'url' => 'http://redcrackle.com',
   *       'title' => 'Red Crackle',
   *     ),
   *     array(
   *       'url' => 'http://www.google.com',
   *       'title' => 'Google',
   *     ),
   *   )
   */
  private static function formatValuesForCompare($values) {
    if (empty($values)) {
      return array();
    }

    $output = array();
    if (is_string($values)) {
      $output[] = array('url' => $values);
    }
    elseif (is_array($values)) {
      if (array_key_exists('url', $values) || array_key_exists('title', $values)) {
        $output[] = $values;
      }
      else {
        $output = $values;
      }
    }

    return $output;
  }
}
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

/**
 * Class NumberDecimal
 *
 * @package RedTest\core\fields
 */
class NumberDecimal extends Number {

  /**
   * Fill random decimal values in the provided field.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether values could be filled in the field.
   *   (2) $values: Values that were filled for the field.
   *   (3) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillDefaultValues(Form $formObject, $field_name) {
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
}
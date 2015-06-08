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

class NumberFloat extends Number {

  /**
   * Fill random float values in the float field.
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
   *   (1) $success: Whether values could be filled in the field.
   *   (2) $values: Values that were filled for the field.
   *   (3) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillRandomValues(Form $formObject, $field_name, $options = array()) {
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

    $values = Utils::getRandomFloat($min, $max, $num);

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }
}
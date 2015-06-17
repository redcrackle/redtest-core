<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 3/25/15
 * Time: 12:16 PM
 */

namespace RedTest\core\fields;

use RedTest\core\Response;
use RedTest\core\forms\Form;
use RedTest\core\Utils;

class NumberInteger extends Number {

  /**
   * Fill random integer values in the integer field.
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
  public static function fillRandomValues(
    Form $formObject,
    $field_name,
    $options = array()
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

    $values = Utils::getRandomInt($min, $max, $num);

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  /**
   * Fills provided float values in the field.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param mixed $values
   *   Following formats are acceptable:
   *   (a) 23
   *   (b) array(23, -89)
   *   (c) array(array('value' => 23), array('value' => -89))
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether values could be filled in the field.
   *   (2) $values: Values that were filled for the field.
   *   (3) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillValues(Form $formObject, $field_name, $values) {
    if (!Field::hasFieldAccess($formObject, $field_name)) {
      return new Response(
        FALSE,
        NULL,
        "Field " . Utils::getLeaf($field_name) . " is not accessible."
      );
    }

    $field_class = get_called_class();
    $values = $field_class::normalizeInput($values);
    $input = $field_class::formatValuesForInput($values);

    $response = $formObject->fillMultiValued($field_name, $input);
    if (!$response->getSuccess()) {
      $response->normalizeVar();
      return $response;
    }

    $response->setVar(Utils::normalize($values));
    return $response;
  }

  /**
   * Checks whether the input is a valid decimal number.
   *
   * @param mixed $value
   *   Provided value.
   * @param null $decimal_separator
   *   This is added to make the declaration of this function compatible with
   *   that of Number::isValidValue(). This argument is not used.
   *
   * @return bool
   *   Whether the provided input is a valid integer or not.
   */
  protected static function isValidValue($value, $decimal_separator = NULL) {
    if (!empty($value) && is_numeric($value)) {
      return TRUE;
    }

    return FALSE;
  }
}
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

class Phone extends Field {

  /**
   * Fill random phone number values in the phone field.
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
  public static function fillDefaultPhoneTextfieldValues(
    Form $formObject,
    $field_name,
    $options = array()
  ) {
    $num = 1;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $country = $field['settings']['country'];
      $phone_country_code = $instance['settings']['phone_country_code'];

    }

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[] = Utils::getRandomEmail();
    }

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  public static function fillPhoneTextfieldValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $formObject->emptyField($field_name);

    if (is_string($values) || is_numeric($values)) {
      $values = array($values);
    }

    $input = array();
    $index = 0;
    foreach ($values as $key => $value) {
      $input[$index] = array('email' => $value);
      $triggering_element_name = $field_name . '_add_more';
      //$triggering_element_value = 'Add another item';
      $formObject->pressButton($triggering_element_name, array('ajax' => TRUE));
      $index++;
    }

    //$formObject->setValues($field_name, array(LANGUAGE_NONE => $input));

    return array(TRUE, Utils::normalize($input), "");
  }
}
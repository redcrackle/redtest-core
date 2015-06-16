<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 3/25/15
 * Time: 12:16 PM
 */

namespace RedTest\core\fields;

use RedTest\core\forms\Form;
use RedTest\core\Response;
use RedTest\core\Utils;

class ListText extends ListField {

  /**
   * Fill checkboxes field with random values.
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
   *   (1) $success: Whether default values could be filled in the field.
   *   (2) $values: Values that were filled for the field.
   *   (3) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillOptionsButtonsRandomValues(
    Form $formObject,
    $field_name,
    $options = array()
  ) {
    $num = 1;
    $allowed_values = array();
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $allowed_values = array_keys($field['settings']['allowed_values']);
    }

    $field_class = get_called_class();
    $values = $field_class::generateListValues($allowed_values, $num);

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  public static function fillOptionsButtonsValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    if (!Field::hasFieldAccess($formObject, $field_name)) {
      return new Response(
        FALSE,
        NULL,
        "Field " . Utils::getLeaf($field_name) . " is not accessible."
      );
    }

    $formObject->emptyField($field_name);

    if (is_string($values) || is_numeric($values)) {
      $values = array($values);
    }

    $input = array();
    if (sizeof($values)) {
      foreach ($values as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
          $input[$value] = $value;
        }
      }

      $response = $formObject->fillValues(
        $field_name,
        array(LANGUAGE_NONE => $input)
      );
      $input = $response->getVar();
      $input = $input[LANGUAGE_NONE];
    }

    return (isset($response) ? new Response(
      $response->getSuccess(),
      $input,
      $response->getMsg()
    ) : new Response(TRUE, $input, ''));
  }

  public static function fillOptionsSelectRandomValues(
    Form $formObject,
    $field_name
  ) {
    $num = 1;
    $allowed_values = array();
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $allowed_values = array_keys($field['settings']['allowed_values']);
    }

    $field_class = get_called_class();
    $values = $field_class::generateListValues($allowed_values, $num);

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  public static function fillOptionsSelectValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    if (!Field::hasFieldAccess($formObject, $field_name)) {
      return new Response(
        FALSE,
        "",
        "Field " . Utils::getLeaf($field_name) . " is not accessible."
      );
    }

    $formObject->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $input = array();
    if (sizeof($values)) {
      foreach ($values as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
          $input[$value] = $value;
        }
      }

      $response = $formObject->fillValues(
        $field_name,
        array(LANGUAGE_NONE => $input)
      );
    }

    return (isset($response) ? new Response(
      $response->getSuccess(),
      $input,
      $response->getMsg()
    ) : new Response(TRUE, $input, ''));
  }
}
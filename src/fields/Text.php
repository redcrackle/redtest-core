<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 2/19/15
 * Time: 4:15 PM
 */

namespace RedTest\core\fields;

use RedTest\core\forms\Form;
use RedTest\core\Utilities;

class Text extends Field {

  /**
   * Fill text area with summary widget.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param string|array $values
   *   Either a string or an array. If it's a string, then it is assumed that
   *   the field has only one value. If it is an array of strings, then it is
   *   assumed that the field is multi-valued and the strings in the array
   *   correspond to multiple text values of this field. If it is an array of
   *   arrays, then it is assumed that the field is multi-valued and the inside
   *   array can have the keys 'value', 'summary' or 'format' which will be set
   *   in form_state. Here are a few examples this parameter can take:
   *   "<p>This is text string.</p>", or
   *   array("<p>This is text string 1.</p>", "This is text string 2."), or
   *   array(
   *     array(
   *       'value' => "This is text string 1.",
   *       'summary' => "<p>Text string 1</p>",
   *       'format' => 'filtered_html',
   *     ),
   *     array(
   *       'value' => "This is text string 2.",
   *       'summary' => "Text string 2",
   *       'format' => 'plain_text',
   *     ),
   *   );
   * @param string $summary
   *   Summary text. If $values parameter doesn't specify summary explicitly,
   *   then this parameter is used as a default.
   * @param string $format
   *   Text format. If $values parameter doesn't specify text format
   *   explicitly, then this parameter is used as a default.
   */
  public static function fillTextTextAreaWithSummaryValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $formObject->emptyField($field_name);

    $defaults = array();
    if (!empty($summary)) {
      $defaults['summary'] = $summary;
    }
    if (!empty($format)) {
      $defaults['format'] = $format;
    }

    $input = array();

    if (is_string($values)) {
      // Values is a string, which means that it's single-valued.
      $input[0] = array('value' => $values) + $defaults;
    }
    elseif (is_array($values)) {
      // $values is an array. It can be an array of strings or array of arrays.
      foreach ($values as $key => $val) {
        if (is_string($val)) {
          $input[$key] = array('value' => $val) + $defaults;
        }
        elseif (is_array($val)) {
          $input[$key] = $val + $defaults;
        }
      }
    }

    $formObject->setValues($field_name, array(LANGUAGE_NONE => $input));

    if (sizeof($input) == 1 && is_string($input[0])) {
      $input = $input[0];
    }

    return array(TRUE, $input, "");
  }

  public static function fillDefaultValues(Form $formObject, $field_name) {
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $function = 'fillDefault' . Utilities::convertUnderscoreToTitleCase(
          $instance['widget']['type']
        ) . 'Values';

      return self::$function($formObject, $field_name);
    }
  }

  public static function fillFieldValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $function = 'fill' . Utilities::convertUnderscoreToTitleCase(
          $instance['widget']['type']
        ) . 'Values';

      return self::$function($formObject, $field_name, $values);
    }
  }

  public static function fillTextTextareaValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $formObject->emptyField($field_name);

    $defaults = array();
    if (!empty($format)) {
      $defaults['format'] = $format;
    }

    $input = array();

    if (is_string($values)) {
      // Values is a string, which means that it's single-valued.
      $input[0] = array('value' => $values) + $defaults;
    }
    elseif (is_array($values)) {
      // $values is an array. It can be an array of strings or an array of arrays.
      foreach ($values as $key => $val) {
        if (is_string($val)) {
          $input[$key] = array('value' => $val) + $defaults;
        }
        elseif (is_array($val)) {
          $input[$key] = $val + $defaults;
        }
      }
    }

    $formObject->setValues($field_name, array(LANGUAGE_NONE => $input));

    if (sizeof($input) == 1 && is_string($input[0])) {
      $input = $input[0];
    }

    return array(TRUE, $input, "");
  }

  public static function fillDefaultTextTextAreaWithSummaryValues(
    $formObject,
    $field_name
  ) {

    $num = 1;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
    }

    $values = self::generateTextAreaValues($num, TRUE);

    return self::fillValues($formObject, $field_name, $values);
  }

  public static function fillDefaultTextTextAreaValues(
    $formObject,
    $field_name
  ) {
    $num = 1;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
    }

    $values = self::generateTextAreaValues($num, FALSE);

    return self::fillValues($formObject, $field_name, $values);
  }

  private static function generateTextAreaValues(
    $num = 1,
    $generate_summary = FALSE
  ) {
    global $user;
    $filter_formats = array_keys(filter_formats($user));

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[$i]['value'] = Utilities::getRandomText(100);
      $values[$i]['format'] = $filter_formats[array_rand($filter_formats)];
      if ($generate_summary) {
        $values[$i]['summary'] = Utilities::getRandomText(25);
      }
    }

    return $values;
  }
} 

<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 2/19/15
 * Time: 4:15 PM
 */

namespace RedTest\core\fields;

use RedTest\core\forms\Form;

class TextField extends Field {

  public static function fillValues(
    $formObject,
    $field_name,
    $widget,
    $values
  ) {
    if ($widget == 'text_text_area_with_summary') {
      self::fillTextAreaWithSummaryValues($formObject, $field_name, $values);
    }
    else {
      self::fillTextTextarea($formObject, $field_name, $values);
    }
  }

  public static function fillDefaultValues($formObject, $field_name, $widget, $num = 1) {
    if ($widget == 'text_text_area_with_summary') {
      return self::fillDefaultTextAreaWithSummaryValues($formObject, $field_name, $widget, $num);
    }
    else {
      return self::fillDefaultTextTextAreaValues($formObject, $field_name, $widget, $num = 1);
    }
  }

  /**
   * Fill text area with summary widget.
   *
   * @param object $formObject
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
   *   Summary text. If $values doesn't specify summary explicitly, then this
   *   parameter is used as a default.
   * @param string $format
   *   Text format. If $values doesn't specify text format explicitly, then
   *   this parameter is used as a default.
   */
  public static function fillTextAreaWithSummaryValues(
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
  }

  public static function fillTextTextarea(Form $formObject, $field_name, $values) {
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
  }

  function fillDefaultTextAreaWithSummaryValues($formObject, $field_name, $widget, $num = 1) {
    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[] = Utilities::getRandomString(100);
    }

    self::fillValues($formObject, $field_name, $widget, $values);

    if (sizeof($values) == 1) {
      $values = $values[0];
    }

    return array(TRUE, $values, "");
  }

  function fillDefaultTextTextAreaValues($formObject, $field_name, $widget, $num = 1) {

  }
} 
<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 2/19/15
 * Time: 4:15 PM
 */

namespace RedTest\core\fields;

use RedTest\core\forms\Form;
use RedTest\core\Utils;

class TextWithSummary extends Field {

  /**
   * Fills default values in the provided field.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillDefaultValues(Form $formObject, $field_name) {
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      /*$function = 'fillDefault' . Utils::makeTitleCase(
          $instance['widget']['type']
        ) . 'Values';*/
      $function = 'fillDefaultTextTextAreaWithSummaryValues';

      return self::$function($formObject, $field_name);
    }
  }

  /**
   * Fills specified values in the provided field.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param array|string $values
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
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $function = 'fill' . Utils::makeTitleCase(
          $instance['widget']['type']
        ) . 'Values';

      return self::$function($formObject, $field_name, $values);
    }
  }

  /**
   * Fills text area with summary field with default values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillDefaultTextTextAreaWithSummaryValues(
    Form $formObject,
    $field_name
  ) {
    $num = 1;
    $display_summary = TRUE;
    $text_processing = TRUE;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $display_summary = $instance['settings']['display_summary'];
      $text_processing = $instance['settings']['text_processing'];
    }

    $values = self::generateTextAreaValues($num, $display_summary, $text_processing);

    return self::fillValues($formObject, $field_name, $values);
  }

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
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillTextTextAreaWithSummaryValues(
    Form $formObject,
    $field_name,
    $values,
    $summary = '',
    $format = ''
  ) {
    $formObject->emptyField($field_name);

    $defaults = array();
    if (!empty($summary)) {
      $defaults['summary'] = $summary;
    }
    if (!empty($format)) {
      $defaults['format'] = $format;
    }

    return self::fillTextValues($formObject, $field_name, $values, $defaults);
  }

  /**
   * Fills text area field with default values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillDefaultTextTextAreaValues(
    Form $formObject,
    $field_name
  ) {
    $num = 1;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $text_processing = $instance['settings']['text_processing'];
    }

    $values = self::generateTextAreaValues($num, FALSE, $text_processing);

    return self::fillValues($formObject, $field_name, $values);
  }

  /**
   * Fills text area field with provided values.
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
   *   array can have the keys 'value' or 'format' which will be set
   *   in form_state. Here are a few examples this parameter can take:
   *   "<p>This is text string.</p>", or
   *   array("<p>This is text string 1.</p>", "This is text string 2."), or
   *   array(
   *     array(
   *       'value' => "This is text string 1.",
   *       'format' => 'filtered_html',
   *     ),
   *     array(
   *       'value' => "This is text string 2.",
   *       'format' => 'plain_text',
   *     ),
   *   );
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
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

    return self::fillTextValues($formObject, $field_name, $values, $defaults);
  }

  /**
   * Fills a textfield in the form with default values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillDefaultTextTextfieldValues(
    Form $formObject,
    $field_name
  ) {
    $num = 1;
    $text_processing = FALSE;
    $max_length = 100;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $text_processing = $instance['settings']['text_processing'];
      $max_length = $field['settings']['max_length'];
    }

    $values = self::generateTextFieldValues(
      $num,
      $text_processing,
      $max_length
    );

    return self::fillValues($formObject, $field_name, $values);
  }

  /**
   * Fills text field values in the form state.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param array|string $values
   *   Either a string or an array. If it's a string, then it is assumed that
   *   the field has only one value. If it is an array of strings, then it is
   *   assumed that the field is multi-valued and the strings in the array
   *   correspond to multiple text values of this field. If it is an array of
   *   arrays, then it is assumed that the field is multi-valued and the inside
   *   array can have the keys 'value' or 'format' which will be set
   *   in form_state. Here are a few examples this parameter can take:
   *   "<p>This is text string.</p>", or
   *   array("<p>This is text string 1.</p>", "This is text string 2."), or
   *   array(
   *     array(
   *       'value' => "This is text string 1.",
   *       'format' => 'filtered_html',
   *     ),
   *     array(
   *       'value' => "This is text string 2.",
   *       'format' => 'plain_text',
   *     ),
   *   );
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillTextTextfieldValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $formObject->emptyField($field_name);

    return self::fillTextValues($formObject, $field_name, $values, array());
  }

  /**
   * Returns an array of randomly generated text area values.
   *
   * @param int $num
   *   Number of values to generate.
   * @param bool $generate_summary
   *   Whether summary needs to be generated for each value.
   * @param bool $generate_format
   *   Whether format needs to be generated for each value.
   *
   * @return array
   *   An array of text area values that can be set in the form state array.
   */
  private static function generateTextAreaValues(
    $num = 1,
    $generate_summary = FALSE,
    $generate_format = TRUE
  ) {
    $filter_formats = array();
    if ($generate_format) {
      global $user;
      $filter_formats = array_keys(filter_formats($user));
    }

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[$i]['value'] = Utils::getRandomText(100);
      if ($generate_format) {
        $values[$i]['format'] = $filter_formats[array_rand($filter_formats)];
      }
      if ($generate_summary) {
        $values[$i]['summary'] = Utils::getRandomText(25);
      }
    }

    return $values;
  }

  /**
   * Returns a string or an array of randomly generated textfield values.
   *
   * @param int $num
   *   Number of values to return.
   * @param bool $generate_format
   *   Whether to generate the format of the textfield.
   * @param int $max_length
   *   Maximum length of the text field.
   *
   * @return string|array
   *   A string if only one value was to be returned, an array of strings
   *   otherwise.
   */
  private static function generateTextFieldValues(
    $num,
    $generate_format = FALSE,
    $max_length = 100
  ) {
    $filter_formats = array();
    if ($generate_format) {
      global $user;
      $filter_formats = array_keys(filter_formats($user));
    }

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[$i]['value'] = Utils::getRandomText($max_length);
      if ($generate_format) {
        $values[$i]['format'] = $filter_formats[array_rand($filter_formats)];
      }
    }

    return Utils::normalize($values);
  }

  /**
   * Convert values to input array.
   *
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
   * @param array $defaults
   *   Defaults array for the field.
   *
   * @return array
   *   An input array suitable to be set in the form state array.
   */
  private static function convertValuesToInput($values, $defaults) {
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

    return $input;
  }

  /**
   * Merges the provides values with defaults, formats the values into a proper
   * array that can be set in form_state, sets the values in form_state and
   * returns.
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
   * @param array $defaults
   *   Array of defaults.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  private static function fillTextValues(
    Form $formObject,
    $field_name,
    $values,
    $defaults
  ) {
    $input = self::convertValuesToInput($values, $defaults);
    $formObject->setValues($field_name, array(LANGUAGE_NONE => $input));
    $input = Utils::normalize($input);

    return array(TRUE, $input, "");
  }
}

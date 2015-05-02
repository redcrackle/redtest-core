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
use RedTest\core\entities\Entity;

class Text extends Field {

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
  public static function fillDefaultValues(
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

    $field_class = get_called_class();

    $values = $field_class::generateValues(
      $num,
      $text_processing,
      FALSE,
      $max_length,
      FALSE
    );

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
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
  public static function fillValues(
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

    $field_class = get_called_class();

    return $field_class::fillTextValues(
      $formObject,
      $field_name,
      $values,
      array()
    );
  }

  public static function checkValues(
    Entity $entityObject,
    $field_name,
    $values
  ) {
    $function = "get" . Utils::makeTitleCase($field_name) . "Values";
    $actual_values = $entityObject->$function();

    $field_class = get_called_class();

    return $field_class::compareValues($actual_values, $values);
  }

  public static function getValues(
    Entity $entityObject,
    $field_name,
    $post_process = FALSE
  ) {
    $field = $entityObject->getFieldItems($field_name);

    return $field;
  }

  public static function compareValues($actual_values, $values) {
    $field_class = get_called_class();

    xdebug_break();
    $actual_values = $field_class::convertValuesToInput(
      $actual_values,
      array()
    );
    $values = $field_class::convertValuesToInput($values, array());

    if (sizeof($values) != sizeof($actual_values)) {
      return array(FALSE, "Number of values do not match.");
    }

    foreach ($values as $index => $values_array) {
      foreach ($values_array as $key => $value) {
        if ($value != $actual_values[$index][$key]) {
          return array(FALSE, "Key " . $key . " does not match.");
        }
      }
    }

    return array(TRUE, "");
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
  protected static function generateValues(
    $num,
    $generate_format = FALSE,
    $generate_summary = FALSE,
    $max_length = 100,
    $newline = TRUE
  ) {
    $filter_formats = array();
    if ($generate_format) {
      global $user;
      $filter_formats = array_keys(filter_formats($user));
    }

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[$i]['value'] = Utils::getRandomText($max_length);
      if (!$newline) {
        $values[$i]['value'] = str_replace(PHP_EOL, " ", $values[$i]['value']);
      }
      if ($generate_format) {
        $values[$i]['format'] = $filter_formats[array_rand($filter_formats)];
      }
      if ($generate_summary) {
        $values[$i]['summary'] = Utils::getRandomText($max_length);
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
   *   (a) "<p>This is text string.</p>", or
   *   (b) array("<p>This is text string 1.</p>", "This is text string 2."), or
   *   (c) array(
   *         'value' => "This is text string 1.",
   *         'summary' => "<p>Text string 1</p>",
   *         'format' => 'filtered_html',
   *       ),
   *   (d) array(
   *         array(
   *           'value' => "This is text string 1.",
   *           'summary' => "<p>Text string 1</p>",
   *           'format' => 'filtered_html',
   *         ),
   *         array(
   *           'value' => "This is text string 2.",
   *           'summary' => "Text string 2",
   *           'format' => 'plain_text',
   *         ),
   *       );
   * @param array $defaults
   *   Defaults array for the field.
   *
   * @return array
   *   An input array suitable to be set in the form state array.
   */
  protected static function convertValuesToInput($values, $defaults) {
    if (empty($values)) {
      return array();
    }

    $input = array();

    if (is_string($values)) {
      // Values is a string, which means that it's single-valued.
      // $values is in acceptable format (a).
      $input[] = array('value' => $values) + $defaults;
    }
    elseif (is_array($values)) {
      if (array_key_exists('value', $values)) {
        // $values is in acceptable format (c).
        // Make sure that at least one value is filled.
        $value_exists = FALSE;
        foreach ($values as $key => $value) {
          if (!empty($value)) {
            $value_exists = TRUE;
            break;
          }
        }
        if ($value_exists) {
          $input[] = $values + $defaults;
        }
      }
      else {
        // $values is an array. It can be an array of strings or array of
        // arrays.
        foreach ($values as $key => $val) {
          if (is_string($val)) {
            // $values is in acceptable format (b).
            $input[$key] = array('value' => $val) + $defaults;
          }
          elseif (is_array($val)) {
            // $values is in acceptable format (d).
            // Make sure that at least one value is filled.
            $value_exists = FALSE;
            foreach ($val as $index => $value) {
              if (!empty($value)) {
                $value_exists = TRUE;
                break;
              }
            }
            if ($value_exists) {
              $input[$key] = $val + $defaults;
            }
          }
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
  protected static function fillTextValues(
    Form $formObject,
    $field_name,
    $values,
    $defaults
  ) {
    $field_class = get_called_class();

    $values = $field_class::convertValuesToInput($values, $defaults);

    $return = $formObject->fillMultiValued($field_name, $values);

    return array(TRUE, Utils::normalize($return), "");
  }
}

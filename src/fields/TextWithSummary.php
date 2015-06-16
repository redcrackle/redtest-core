<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 2/19/15
 * Time: 4:15 PM
 */

namespace RedTest\core\fields;

use RedTest\core\Response;
use RedTest\core\forms\Form;
use RedTest\core\Utils;

class TextWithSummary extends Text {

  /**
   * Fill text area with summary field with random long text with summary
   * values.
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
   *   (1) $success: Whether the field could be filled with provided values.
   *   (2) $values: Values that were filled.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function fillRandomValues(
    Form $formObject,
    $field_name,
    $options = array()
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

    $field_class = get_called_class();
    $values = $field_class::generateValues(
      $num,
      $text_processing,
      $display_summary
    );

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
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
   *       'summary' => "<p>Text string 1</p>", // this is an optional parameter
   *       'format' => 'filtered_html', // this is an optional parameter
   *     ),
   *     array(
   *       'value' => "This is text string 2.",
   *       'summary' => "Text string 2", // this is an optional parameter
   *       'format' => 'plain_text', // this is an optional parameter
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
  public static function fillValues(
    Form $formObject,
    $field_name,
    $values,
    $summary = '',
    $format = ''
  ) {
    if (!Field::hasFieldAccess($formObject, $field_name)) {
      return new Response(
        FALSE,
        "",
        "Field " . Utils::getLeaf($field_name) . " is not accessible."
      );
    }

    $defaults = array();
    if (!empty($summary)) {
      $defaults['summary'] = $summary;
    }
    if (!empty($format)) {
      $defaults['format'] = $format;
    }

    $field_class = get_called_class();
    return $field_class::fillTextValues(
      $formObject,
      $field_name,
      $values,
      $defaults
    );
  }

  /**
   * Returns an empty field value.
   *
   * @param Form $formObject
   *   Form object.
   * @param $field_name
   *   Field name.
   *
   * @return array
   *   An empty field value array.
   */
  public static function getEmptyValue(Form $formObject, $field_name) {
    list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
    $display_summary = $instance['settings']['display_summary'];

    $output = array('value' => '');
    if ($display_summary) {
      $output['summary'] = '';
    }

    return $output;
  }
}
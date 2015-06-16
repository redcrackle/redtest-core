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

class LinkField extends Field {

  /**
   * Fill link field with random values.
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
  public static function fillRandomValues(
    Form $formObject,
    $field_name,
    $options = array()
  ) {
    $num = 1;
    $show_url = 0;
    $show_title = 'required';
    $link_target = 'default';
    $show_link_class = 0;
    $show_link_title = 0;
    $title_maxlength = 128;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $show_title = $instance['settings']['title'];
      $show_url = $instance['settings']['url'];
      $title_maxlength = $instance['settings']['title_maxlength'];
      $link_target = $instance['settings']['attributes']['target'];
      $show_link_class = $instance['settings']['attributes']['configurable_class'];
      $show_link_title = $instance['settings']['attributes']['configurable_title'];
    }

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $value = array();
      if ($show_url !== 'optional' || Utils::getRandomBool()) {
        $value['url'] = Utils::getRandomUrl();
      }
      if ($show_title == 'required' || empty($value['url']) || ($show_title == 'optional' && Utils::getRandomBool(
          ))
      ) {
        $value['title'] = Utils::getRandomText($title_maxlength);
      }
      if ($link_target == 'user' && Utils::getRandomBool()) {
        $value['attributes']['target'] = '_blank';
      }
      if ($show_link_class) {
        $value['attributes']['class'] = Utils::getRandomString(10);
      }
      if ($show_link_title) {
        $value['attributes']['title'] = Utils::getRandomText(15);
      }

      $values[] = $value;
    }

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($values);
  }

  public static function fillValues(Form $formObject, $field_name, $values) {
    if (!Field::hasFieldAccess($formObject, $field_name)) {
      return new Response(
        FALSE,
        NULL,
        "Field " . Utils::getLeaf($field_name) . " is not accessible."
      );
    }

    $field_class = get_called_class();
    //$values = $field_class::createInput($values);
    $values = $field_class::formatValuesForCompare($values);

    $response = $formObject->fillMultiValued($field_name, $values);
    $return = $field_class::normalize($response->getVar());

    return new Response($response->getSuccess(), $return, $response->getMsg());
  }

  public static function normalize($values) {
    if (is_string($values) || is_numeric($values)) {
      return strval($values);
    }

    $output = array();
    if (is_array($values)) {
      if ((bool) count(array_filter(array_keys($values), 'is_string'))) {
        if (sizeof($values) == 1 && array_key_exists('url', $values)) {
          // $values is of the form array('url' => 'URL 1').
          $output = $values['url'];
        }
        else {
          // $values can be of the form array('url' => 'URL 1', 'title' => 'Title 1')
          $output = $values;
        }

        return $output;
      }
      else {
        // This is not an associative array.
        foreach ($values as $val) {
          if (is_string($val) || is_numeric($val)) {
            // $values is of the form array('URL 1', 'URL 2').
            $output[] = strval($val);
          }
          elseif (is_array($val)) {
            if ((bool) count(array_filter(array_keys($val), 'is_string'))) {
              if (sizeof($val) == 1 && array_key_exists('url', $val)) {
                // $values is of the form array(array('url' => 'URL 1'), array('url' => 'URL 2')).
                $output[] = $val['url'];
              }
              else {
                // $values is of the form array(array('url' => 'URL 1', 'title' => 'Title 1'), array('url' => 'URL 2', 'title' => 'Title 2')).
                $output[] = $val;
              }
            }
          }
        }

        return Utils::normalize($output);
      }
    }

    $output = Utils::normalize($values);

    /*if (is_array($values)) {
      if (sizeof($values) == 1) {
        if ((bool) count(array_filter(array_keys($values), 'is_string'))) {
          // Array is associative.
          if (array_key_exists('url', $values)) {
            // $values is of the form array('url' => 'URL 1').
            return strval($values['url']);
          }
        }
        else {
          // Array is not associative.
          // Check whether it's an array of array.
          foreach ($values as $val) {
            if (is_string($val) || is_numeric($val)) {
              // $values is of the form array('URL 1')
              return strval($val);
            }

            if (is_array($val)) {
              if (sizeof($val) == 1) {
                if ((bool) count(array_filter(array_keys($val), 'is_string'))) {
                  // Array is associative.
                  if (array_key_exists('url', $val)) {
                    return strval($val['url']);
                  }
                }
              }
            }
          }
        }
      }
    }*/

    return $output;
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
    $show_title = $instance['settings']['title'];

    $output = array(
      'url' => '',
      'attributes' => array(),
    );

    if ($show_title != 'none') {
      $output['title'] = '';
    }

    return $output;
  }

  /**
   * Returns name of the triggering element based on field name.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return string
   *   Triggering element name.
   */
  public static function getTriggeringElementName($field_name, $index) {
    return $field_name . '_add_more';
  }

  /**
   * Creates an input array based on the provided values.
   *
   * @param string|array $value
   *   Provided values.
   *
   * @return mixed
   *   Input array that can be sent in form POST.
   */
  private static function createInput($value) {
    $input = array();

    if (is_string($value) || is_numeric($value)) {
      $input[] = array(
        'url' => strval($value),
      );
    }
    elseif (is_array($value)) {
      foreach ($value as $val) {
        if (is_string($val) || is_numeric($val)) {
          $input[] = array('url' => $val);
        }
        else {
          $input[] = $val;
        }
      }
    }

    return $input;
  }

  /**
   * Normalizes the input values so that they are in the acceptable input
   * format.
   *
   * @param string|array $values
   *   A string URL or an array of URLs.
   *
   * @return array
   *   Standardized format: array of URLs.
   */
  private static function denormalizeInput($values) {
    if (is_string($values) || is_numeric($values)) {
      $values = array($values);
    }

    return $values;
  }

  public static function compareValues($actual_values, $values) {
    $field_class = get_called_class();

    $actual_values = $field_class::formatValuesForCompare($actual_values);
    $values = $field_class::formatValuesForCompare($values);

    if (sizeof($values) != sizeof($actual_values)) {
      return new Response(
        FALSE,
        NULL,
        "Number of values do not match. Actual values are " . print_r(
          $actual_values,
          TRUE
        ) . " and expected values are " . print_r($values, TRUE)
      );
    }

    foreach ($values as $index => $value_array) {
      foreach ($value_array as $key => $value) {
        if ($actual_values[$index][$key] != $value) {
          return new Response(
            FALSE,
            NULL,
            "Key $key does not match for index $index. Actual values are " . print_r(
              $actual_values,
              TRUE
            ) . " and expected values are " . print_r($values, TRUE)
          );
        }
      }
    }

    return new Response(TRUE, NULL, "");
  }

  /**
   * Format the value so that it can be compared.
   *
   * @param string|array $values
   *   Acceptable formats are:
   *   (a) 'http://redcrackle.com'
   *   (b) array('http://redcrackle.com', 'http://www.google.com')
   *   (c) array(
   *         'url' => 'http://redcrackle.com',
   *         'title' => 'Red Crackle',
   *       )
   *   (d) array(
   *         array(
   *           'url' => 'http://redcrackle.com',
   *           'title' => 'Red Crackle',
   *         ),
   *         array(
   *           'url' => 'http://www.google.com',
   *           'title' => 'Google',
   *         ),
   *       )
   *
   * @return array
   *   array(
   *     array(
   *       'url' => 'http://redcrackle.com',
   *       'title' => 'Red Crackle',
   *     ),
   *     array(
   *       'url' => 'http://www.google.com',
   *       'title' => 'Google',
   *     ),
   *   )
   */
  private static function formatValuesForCompare($values) {
    if (empty($values)) {
      return array();
    }

    $output = array();
    if (is_string($values) || is_numeric($values)) {
      $output[] = array('url' => strval($values));
    }
    elseif (is_array($values)) {
      if (array_key_exists('url', $values) || array_key_exists(
          'title',
          $values
        )
      ) {
        $output[] = $values;
      }
      else {
        foreach ($values as $val) {
          if (is_string($val) || is_numeric($val)) {
            $output[] = array('url' => strval($val));
          }
          elseif (is_array($val)) {
            if (array_key_exists('url', $val) || array_key_exists(
                'title',
                $val
              )
            ) {
              $output[] = $val;
            }
          }
        }
      }
    }

    return $output;
  }
}
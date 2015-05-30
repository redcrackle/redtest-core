<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 2/19/15
 * Time: 4:13 PM
 */

namespace RedTest\core\fields;

use RedTest\core\entities\Entity;
use RedTest\core\forms\Form;
use RedTest\core\Utils;

class Field {

  /**
   * Returns information about a field.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return array|null
   *   An array of field information if the field exists. NULL if the field
   *   does not exist.
   */
  public static function getFieldInfo($field_name) {
    return field_info_field($field_name);
  }

  /**
   * Returns information about field instance.
   *
   * @param Entity $entityObject
   *   Entity for which the field instance is to be returned.
   * @param string $field_name
   *   Field name.
   *
   * @return array|null
   *   An array of field instance information if the instance exists. NULL if
   *   the field instance does not exist.
   */
  public static function getFieldInstance(Entity $entityObject, $field_name) {
    list(, , $bundle) = entity_extract_ids(
      $entityObject->getEntityType(),
      $entityObject->getEntity()
    );
    $instance = field_info_instance(
      $entityObject->getEntityType(),
      $field_name,
      $bundle
    );

    return $instance;
  }

  /**
   * Returns information about the field, field instance and the number of
   * values.
   *
   * @param Entity $entityObject
   *   Entity for which the information is to be returned.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array of 3 values:
   *   (1) $field: An array of field information if the field exists. NULL if
   *   the field does not exist.
   *   (2) $instance: An array of field instance information if the instance
   *   exists. NULL if the field instance does not exist.
   *   (3) $num: Number of values in the field.
   */
  public static function getFieldDetails(Entity $entityObject, $field_name) {
    $instance = NULL;
    $num = 0;
    $field = self::getFieldInfo($field_name);

    if (!is_null($field)) {
      $instance = self::getFieldInstance($entityObject, $field_name);
      $num = self::getNumberOfItemsFromCardinality($field['cardinality']);
    }

    return array($field, $instance, $num);
  }


  /**
   * Returns the number of values to create based on field's cardinality. If
   * cardinality is unlimited, then return 2 or 3 randomly. If cardinality is 1,
   * then return 1. If cardinality is any other value, then return a random
   * number between 2 and the maximum number allowed.
   *
   * @param int $cardinality
   *   Field's cardinality.
   *
   * @return int
   *   Number of field values to create.
   *
   */
  private static function getNumberOfItemsFromCardinality($cardinality) {
    if ($cardinality == -1) {
      // -1 denotes that cardinality is unlimited.
      $num = Utils::getRandomInt(2, 3);

      return $num;
    }
    elseif ($cardinality == 1) {
      $num = 1;

      return $num;
    }
    else {
      $num = Utils::getRandomInt(2, $cardinality);

      return $num;
    }
  }

  public static function processBeforeCreateRandom(
    Form $formObject,
    $field_name,
    &$options
  ) {
    list($field_class, $widget_type) = self::getFieldClass(
      $formObject,
      $field_name
    );
    $function = 'process' . $widget_type . 'BeforeCreateRandom';
    if (method_exists($field_class, $function)) {
      list($success, $msg) = $field_class::$function(
        $formObject,
        $field_name,
        $options
      );
      if (!$success) {
        return array(FALSE, $msg);
      }
    }
    return array(TRUE, "");
  }

  public static function processBeforeSubmit(Form $formObject, $field_name) {
    list($field_class, $widget_type) = self::getFieldClass(
      $formObject,
      $field_name
    );
    $function = 'process' . $widget_type . 'BeforeSubmit';
    if (method_exists($field_class, $function)) {
      list($success, $msg) = $field_class::$function($formObject, $field_name);
      if (!$success) {
        return array(FALSE, $msg);
      }
    }
    return array(TRUE, "");
  }

  /**
   * @param \RedTest\core\forms\Form $formObject
   * @param $field_name
   *
   * @return array
   */
  public static function processAfterSubmit(Form $formObject, $field_name) {
    list($field_class, $widget_type) = self::getFieldClass(
      $formObject,
      $field_name
    );
    $function = 'process' . $widget_type . 'AfterSubmit';
    if (method_exists($field_class, $function)) {
      list($success, $msg) = $field_class::$function($formObject, $field_name);
      if (!$success) {
        return array(FALSE, $msg);
      }
    }
    return array(TRUE, "");
  }

  /**
   * Returns whether the provided field needs to be filled in the form. If the
   * field is in skip array, then this function returns FALSE. If the field is
   * not required and required_fields_only is set to TRUE, then this function
   * returns FALSE. Otherwise the function returns TRUE.
   *
   * @param \RedTest\core\forms\Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param array $options
   *   Options array. The only 2 keys that are used here are
   *   "required_fields_only" and "skip".
   *
   * @return bool
   *   TRUE if the field needs to be filled and FALSE otherwise.
   */
  public static function isToBeFilled(Form $formObject, $field_name, $options) {
    $required_function_name = 'is' . Utils::makeTitleCase(
        $field_name
      ) . 'Required';
    if ($options['required_fields_only'] && !$formObject->$required_function_name(
      )
    ) {
      // Check if the field is required. We use '#required' key in form array
      // since it can be set or unset using custom code.
      // Field is not required. There is no need to fill this field.
      return FALSE;
    }

    $options += array('skip' => array());
    if (in_array($field_name, $options['skip'])) {
      // Field needs to be skipped.
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Fills in default values in the specified field. Internally it calls the
   * class of the individual field type to fill the default values.
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
  public static function fillDefaultValues(
    Form $formObject,
    $field_name,
    $options = array()
  ) {
    list($field_class, $widget_type) = Field::getFieldClass(
      $formObject,
      $field_name
    );

    if (!empty($field_class)) {
      $function = 'fillDefault' . $widget_type . 'Values';

      return $field_class::$function($formObject, $field_name, $options);
    }

    return array(FALSE, "", "Field or property $field_name not found.");
  }

  /**
   * Fills values in the specified field. Internally it calls the class of the
   * individual field type to fill the values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param string|array $values
   *   Values to be filled.
   *
   * @return mixed
   *   An array with 2 values:
   *   (1) $success: Whether default values could be filled in the field.
   *   (2) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillValues(Form $formObject, $field_name, $values) {
    list($field_class, $widget_type) = Field::getFieldClass(
      $formObject,
      $field_name
    );

    if (!empty($field_class)) {
      $function = 'fill' . $widget_type . 'Values';

      return $field_class::$function($formObject, $field_name, $values);
    }

    return array(FALSE, "", "Field or property $field_name not found.");
  }

  /**
   * Returns class and widget type of a field.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array with two values:
   *   (a) Field class
   *   (b) Widget type in Title Case.
   */
  public static function getFieldClass(Form $formObject, $field_name) {
    $field_class = '';
    $widget_type = '';

    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);

      if (!is_null($field) && !is_null($instance)) {
        $short_field_class = Utils::makeTitleCase($field['type']);
        $field_class = "RedTest\\core\\fields\\" . $short_field_class;

        $widget_type = Utils::makeTitleCase($instance['widget']['type']);

        return array($field_class, $widget_type);
      }
    }

    // Code execution came here that means that either the form is not an
    // EntityForm or the field name is a property and is not really a field.
    $array = array($field_name);
    $key_exists = NULL;
    $form = $formObject->getForm();
    $value = drupal_array_get_nested_value($form, $array, $key_exists);
    if ($key_exists) {
      $type = $value['#type'];
      switch ($type) {
        case 'textfield':
          $field_class = 'Text';
          break;
      }

      if (!empty($field_class)) {
        $field_class = "RedTest\\core\\fields\\" . $field_class;
      }

      return array($field_class, $widget_type);
    }
  }

  /**
   * Returns whether a field is a CCK field attached to an entity form.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return bool
   *   TRUE if the field is a CCK field attached to an entity form, FALSE
   *   otherwise.
   */
  public static function isCckField(Form $formObject, $field_name) {
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);

      if (!is_null($field) && !is_null($instance)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  public static function checkValues(
    Entity $entityObject,
    $field_name,
    $values
  ) {
    list($field, $instance, $num) = Field::getFieldDetails(
      $entityObject,
      $field_name
    );

    $short_field_class = Utils::makeTitleCase($field['type']);
    $field_class = "RedTest\\core\\Fields\\" . $short_field_class;

    $widget_type = Utils::makeTitleCase($instance['widget']['type']);
    $function = "check" . $widget_type . "Values";

    if (method_exists($field_class, $function)) {
      return $field_class::$function($entityObject, $field_name, $values);
    }
    else {
      $function = "get" . Utils::makeTitleCase($field_name) . "Values";
      $actual_values = $entityObject->$function();

      $function = "compare" . $widget_type . "Values";
      if (method_exists($field_class, $function)) {
        return $field_class::$function(
          $actual_values,
          $values,
          $field,
          $instance
        );
      }

      return $field_class::compareValues(
        $actual_values,
        $values,
        $field,
        $instance
      );
    }
  }

  public static function getValues(
    Entity $entityObject,
    $field_name,
    $post_process = TRUE
  ) {
    list($field, $instance, $num) = Field::getFieldDetails(
      $entityObject,
      $field_name
    );

    $short_field_class = Utils::makeTitleCase($field['type']);
    $field_class = "RedTest\\core\\Fields\\" . $short_field_class;

    $widget_type = Utils::makeTitleCase($instance['widget']['type']);
    $function = "get" . $widget_type . "Values";

    if (method_exists($field_class, $function)) {
      return $field_class::$function($entityObject, $field_name);
    }
    else {
      return $entityObject->getFieldItems($field_name);
    }
  }

  public static function getTriggeringElementName($field_name, $index) {
    return $field_name . '_add_more';
  }
}
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
use RedTest\core\Utilities;

class Field {

  public static function getFieldInfo($field_name) {
    return field_info_field($field_name);
  }

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

  public static function getFieldDetails($entityObject, $field_name) {
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
   * @param $cardinality
   *
   * @return int
   */
  private function getNumberOfItemsFromCardinality($cardinality) {
    if ($cardinality == -1) {
      $num = Utilities::getRandomInt(2, 3);

      return $num;
    }
    elseif ($cardinality == 1) {
      $num = 1;

      return $num;
    }
    else {
      $num = Utilities::getRandomInt(2, $cardinality);

      return $num;
    }
  }

  public static function fillDefaultValues(Form $formObject, $field_name) {
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      $field = self::getFieldInfo($field_name);
      $field_class = Utilities::convertUnderscoreToTitleCase($field['module']);

      return call_user_func_array(
        array($field_class, 'fillDefaultValues'),
        array($formObject, $field_name)
      );
    }
  }


  public static function fillValues(Form $formObject, $field_name, $values) {
    if (method_exists($formObject, 'getEntityObject')) {
      $field = self::getFieldInfo($field_name);
      $field_class = Utilities::convertUnderscoreToTitleCase($field['module']);

      return call_user_func_array(
        array($field_class, 'fillValues'),
        array($formObject, $field_name, $values)
      );
    }
  }
}
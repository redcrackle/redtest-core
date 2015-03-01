<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 11/25/14
 * Time: 3:39 PM
 */

namespace RedTest\core;


class Utilities {

  public static function convertUnderscoreToTitleCase($input) {
    $output = str_replace("_", " ", strtolower($input));
    $output = ucwords($output);
    $output = str_replace(" ", "", $output);

    return $output;
  }

  public static function convertTitleCaseToUnderscore($input) {
    $input = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', "_$1", $input));

    // Insert _ before and after a numeric string unless it's at the start or end.
    $output = '';
    $is_numeric = FALSE;
    for ($pos = 0; $pos < strlen($input); $pos++) {
      if (is_numeric($input[$pos]) && !$is_numeric) {
        $output .= '_';
      }
      elseif (!is_numeric($input[$pos]) && $is_numeric) {
        $output .= '_';
      }
      $output .= $input[$pos];
    }

    return $output;
  }

  public static function getRandomString($length = 20) {
    return substr(
      str_shuffle(
        "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
      ),
      0,
      $length
    );
  }

  public static function getRandomText($length = 100) {
    if (class_exists('\Faker\Factory')) {
      $faker = \Faker\Factory::create();
      return $faker->text($length);
    }

    return substr(
      str_shuffle(
        " 0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
      ),
      0,
      $length
    );
  }

  public static function getRandomDate(
    $date_format,
    $start_date = 0,
    $end_date = NULL
  ) {
    $start_int = strtotime($start_date);
    if (is_null($end_date)) {
      $end_int = time();
    }
    else {
      $end_int = strtotime($end_date);
    }

    $val = self::getRandomInt($start_int, $end_int);

    return date($date_format, $val);
  }

  public static function getRandomInt($start_int, $end_int) {
    return mt_rand($start_int, $end_int);
  }

  public static function getId($input) {
    if (is_array($input)) {
      return array_map(function ($obj) { return $obj->getId(); }, $input);
    }

    return $input->getId();
  }

  public static function getLabel($input) {
    if (is_array($input)) {
      return array_map(function ($obj) { return $obj->getLabel(); }, $input);
    }

    return $input->getLabel();
  }

  /**
   * Delete entities. This is a copy of entity_delete_multiple() function in
   * entity.module since entity module may not be present.
   *
   * @param string $entity_type
   *   Entity type.
   * @param int $min_entity_id
   *  Minimum entity id over which all entities will be deleted.
   *
   * @return bool
   *   TRUE if entities got deleted and FALSE otherwise.
   */
  public static function deleteEntities($entity_type, $min_entity_id) {
    $query = new \EntityFieldQuery();
    $results = $query->entityCondition('entity_type', $entity_type)
      ->entityCondition('entity_id', $min_entity_id, '>')
      ->execute();
    if (isset($results[$entity_type])) {
      $entity_ids = array_keys($results[$entity_type]);

      $info = entity_get_info($entity_type);
      if (isset($info['deletion callback'])) {
        foreach ($entity_ids as $id) {
          $info['deletion callback']($id);
        }
      }
      elseif (in_array(
        'EntityAPIControllerInterface',
        class_implements($info['controller class'])
      )) {
        entity_get_controller($entity_type)->delete($entity_ids);
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * @throws \Exception
   */
  public static function deleteCreatedEntities() {
    global $entities;

    if (!empty($entities)) {
      foreach ($entities as $key => $val) {
        foreach ($val as $entity_id => $object) {
          $object->delete();
        }
      }
    }

    self::deleteEntities('node', 103931);
    self::deleteEntities('taxonomy_term', 19066);
    self::deleteEntities('user', 10);
  }
}
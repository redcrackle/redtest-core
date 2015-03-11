<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 11/25/14
 * Time: 3:39 PM
 */

namespace RedTest\core;


/**
 * Class Utils
 *
 * @package RedTest\core
 */
class Utils {

  /**
   * Converts snake case to title case. As an example, a_big_1_boy will be
   * converted to ABig1Boy.
   *
   * @param string $input
   *   String that is to be converted from snake case to title case.
   *
   * @return string
   *   String in title case format.
   */
  public static function makeTitleCase($input) {
    $output = str_replace("_", " ", strtolower($input));
    $output = ucwords($output);
    $output = str_replace(" ", "", $output);

    return $output;
  }

  /**
   * Converts title case to snake case. As an example, ABig1Boy is converted to
   * a_big_1_boy.
   *
   * @param string $input
   *   String that is to be converted from title case to snake case.
   *
   * @return string
   *   String in snake case format.
   */
  public static function makeSnakeCase($input) {
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

  /**
   * Returns a random string.
   *
   * @param int $length
   *   Length of the returned string. Defaults to 20.
   *
   * @return string
   *   Random string of specified length.
   */
  public static function getRandomString($length = 20) {
    return substr(
      str_shuffle(
        "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
      ),
      0,
      $length
    );
  }

  /**
   * Returns random text. If Faker library is present, then it uses its
   * create() function. If not, it generates text using str_shuffle() function.
   *
   * @param int $length
   *   Length of the returned text. Defaults to 100.
   *
   * @return string
   *   Random text of specified length.
   */
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

  /**
   * Returns a random date in the specified date format between start date and
   * end date.
   *
   * @param string $date_format
   *   Date format string.
   * @param int $start_date
   *   Unix timestamp of the start date. Default value is 0.
   * @param null|int $end_date
   *   Unix timestamp of the end date. Default value is the current time.
   *
   * @return bool|string
   *   FALSE if input is not valid and formatted date string, otherwise.
   */
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

  /**
   * Returns a random integer between start_int and end_int, including both of
   * them.
   *
   * @param int $start_int
   *   Start integer.
   * @param int $end_int
   *   End integer.
   *
   * @return int
   *   Random integer.
   */
  public static function getRandomInt($start_int, $end_int) {
    return mt_rand($start_int, $end_int);
  }

  /**
   * If an array is provided, this function returns id of each object in the
   * array by calling getId() function on each object. If an object is
   * provided, then its getId() function is called and the value is returned.
   *
   * @param object|array $input
   *   An object or an array of objects.
   *
   * @return array|int
   *   Id of the object or an array of ids.
   */
  public static function getId($input) {
    if (is_array($input)) {
      return array_map(function ($obj) { return $obj->getId(); }, $input);
    }

    return $input->getId();
  }

  /**
   * If an array is provided, this function returns label of each object in the
   * array by calling getLabel() function on each object. If an object is
   * provided, then its getLabel() function is called and the value is returned.
   *
   * @param object|array $input
   *   An object or an array of objects.
   *
   * @return array|string
   *   Label of the object or an array of labels.
   */
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
   * Deletes entities that were created while testing.
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
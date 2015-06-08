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
    static $map;

    if (isset($map[$input])) {
      return $map[$input];
    }

    $output = str_replace("_", " ", strtolower($input));
    $output = ucwords($output);
    $output = str_replace(" ", "", $output);

    $map[$input] = $output;

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
    static $map;

    if (isset($map[$input])) {
      return $map[$input];
    }

    $input_mod = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', "_$1", $input));

    // Insert _ before and after a numeric string unless it's at the start or end.
    $output = '';
    $is_numeric = FALSE;
    for ($pos = 0; $pos < strlen($input_mod); $pos++) {
      if (is_numeric($input_mod[$pos]) && !$is_numeric) {
        $output .= '_';
      }
      elseif (!is_numeric($input_mod[$pos]) && $is_numeric) {
        $output .= '_';
      }
      $output .= $input_mod[$pos];
    }

    $map[$input] = $output;

    return $output;
  }

  /**
   * Returns a random string.
   *
   * @param int $length
   *   Length of the returned string. Defaults to 20.
   * @param int $num
   *   Number of string values to return.
   *
   * @return null|string|array
   *   NULL if $num < 1, a random string if $num = 1 and an array of strings if
   *   $num > 1.
   */
  public static function getRandomString($length = 20, $num = 1) {
    if ($num < 1) {
      return NULL;
    }

    // Do not use Faker for generating a random string since it doesn't give
    // enough unique values and keys are generally used as unique indexes, for
    // e.g. in username.
    /*if (class_exists('\Faker\Factory')) {
      $faker = \Faker\Factory::create();

      if ($num == 1) {
        return $faker->word();
      }
      else {
        return $faker->words($num);
      }
    }*/

    $string_array = array();
    foreach (range(0, $num - 1) as $index) {
      $string_array[] = substr(
        str_shuffle(
          "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
        ),
        0,
        $length
      );
    }

    return self::normalize($string_array);
  }

  /**
   * Returns random text. If Faker library is present, then it uses its
   * create() function. If not, it generates text using str_shuffle() function.
   *
   * @param int $length
   *   Length of the returned text. Defaults to 100.
   * @param int $num
   *   Number of text values to return.
   *
   * @return null|string|array
   *   NULL if $num < 1, a random text if $num = 1 and an array of text if $num
   *   > 1.
   */
  public static function getRandomText($length = 100, $num = 1) {
    if ($num < 1) {
      return NULL;
    }

    $faker = NULL;
    if (class_exists('\Faker\Factory')) {
      $faker = \Faker\Factory::create();
    }

    $text_array = array();
    foreach (range(0, $num - 1) as $index) {
      if (!is_null($faker)) {
        $text_array[] = $faker->text($length);
      }
      elseif ($length <= 2) {
        $text_array[] = static::getRandomString($length);
      }
      else {
        $text_array[] = static::getRandomString(1) . substr(
            str_shuffle(
              "          0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
            ),
            0,
            $length - 2
          ) . static::getRandomString(1);
      }
    }

    return self::normalize($text_array);
  }

  /**
   * Returns a randomly generated email address. If Faker library is present,
   * then it uses its create() function. If not, it generates one using
   * strings.
   *
   * @param int $num
   *   Number of email addresses to return.
   *
   * @return null|string|array
   *   NULL if $num < 1, a random email address string if $num = 1 and an array
   *   of email address strings if $num > 1.
   */
  public static function getRandomEmail($num = 1) {
    if ($num < 1) {
      return NULL;
    }

    $faker = NULL;
    if (class_exists('\Faker\Factory')) {
      $faker = \Faker\Factory::create();
    }

    $email_addresses = array();
    foreach (range(0, $num - 1) as $index) {
      if (!is_null($faker)) {
        $email_addresses[] = $faker->safeEmail();
      }
      else {
        $email_addresses[] = self::getRandomString(
            8
          ) . '@' . self::getRandomString(20) . '.com';
      }
    }

    return self::normalize($email_addresses);
  }

  /**
   * Returns random URL.
   *
   * @param string $type
   *   "relative" for relative URL, "absolute" for absolute URL and "any" for
   *   relative URL 50% of time and absolute URL 50% of time.
   * @param int $num
   *   Number of URLs to return.
   *
   * @return null|string|array
   *   NULL if $num < 1, a random URL string if $num = 1 and an array of URL
   *   strings if $num > 1.
   */
  public static function getRandomUrl($type = 'any', $num = 1) {
    if ($num < 1) {
      return NULL;
    }

    $faker = NULL;
    if (class_exists('\Faker\Factory')) {
      $faker = \Faker\Factory::create();
    }

    $urls = array();
    if ($type == 'relative' || ($type == 'any' && self::getRandomBool())
    ) {
      $parts = self::getRandomInt(1, 5);
      $paths = array();
      if (!is_null($faker)) {
        $paths = $faker->words($parts);
      }
      else {
        $paths = Utils::getRandomString(8, $parts);
      }

      if (is_string($paths)) {
        $urls[] = $paths;
      }
      elseif (is_array($paths)) {
        $urls[] = implode("/", $paths);
      }
    }
    else {
      foreach (range(0, $num - 1) as $index) {
        if (!is_null($faker)) {
          $urls[] = $faker->url();
        }
        else {
          $urls[] = 'www.' . self::getRandomString(10) . '.com';
        }
      }
    }

    return self::normalize($urls);
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
   * @param int $num
   *   Number of integers to return.
   *
   * @return int|array|null
   *   NULL if $num is less than 1, random integer if $num = 1 and an array of
   *   integers if $num > 1.
   */
  public static function getRandomInt($start_int, $end_int, $num = 1) {
    if ($num < 1) {
      return NULL;
    }

    $faker = NULL;
    if (class_exists('\Faker\Factory')) {
      $faker = \Faker\Factory::create();
    }

    $numbers = array();
    foreach (range(0, $num - 1) as $index) {
      if (!is_null($faker)) {
        $numbers[] = $faker->numberBetween($start_int, $end_int);
      }
      else {
        $numbers[] = mt_rand($start_int, $end_int);
      }
    }

    return self::normalize($numbers);
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
        if ($entity_type == 'node') {
          node_delete_multiple($entity_ids);
        }
        elseif ($entity_type == 'user') {
          user_delete_multiple($entity_ids);
        }
        elseif ($entity_type == 'taxonomy_term') {
          foreach ($entity_ids as $entity_id) {
            taxonomy_term_delete($entity_id);
          }
        }
        elseif ($entity_type == 'comment') {
          foreach ($entity_ids as $entity_id) {
            comment_delete($entity_id);
          }
        }

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
          if ($object->deleteProgrammatically()) {
            unset($entities[$key][$entity_id]);
          }
        }
      }
    }

    /*self::deleteEntities('node', 1);
    self::deleteEntities('taxonomy_term', 0);
    self::deleteEntities('user', 30);
    self::deleteEntities('comment', 0);*/
  }

  /**
   * Returns the first value of the input array if there is only item in the
   * array. If there are more items in the array, then return the full array.
   *
   * @param array $input
   *   An array of values.
   *
   * @return mixed
   *   First value of the input array or the full input array.
   */
  public static function normalize($input) {
    if (is_array($input)) {
      if (sizeof($input) === 0) {
        return '';
      }
      elseif (sizeof($input) == 1) {
        return array_shift($input);
      }
    }

    return $input;
  }

  /**
   * Converts an associative array of errors to string.
   *
   * @param array $errors
   *   An array of errors.
   *
   * @return string
   *   Error in string format.
   */
  public static function convertErrorArrayToString($errors) {
    $output = '';
    $index = 1;
    foreach ($errors as $key => $value) {
      $output .= " (" . $index . ") " . $key . " - ";
      if (is_string($value)) {
        $output .= $value;
      }
      elseif (is_array($value)) {
        $output .= self::convertErrorArrayToString($value);
      }
    }

    return $output;
  }

  /**
   * Returns TRUE or FALSE randomly.
   *
   * @param int $num
   *   Number of booleans to return.
   *
   * @return null|bool|array
   *   NULL if $num is less than 1, random boolean if $num = 1 and an array of
   *   booleans if $num > 1.
   */
  public static function getRandomBool($num = 1) {
    if ($num < 1) {
      return NULL;
    }

    $bools = array();
    $ints = Utils::getRandomInt(0, 1, $num);
    if (is_array($ints)) {
      foreach ($ints as $integer) {
        $bools = $integer ? TRUE : FALSE;
      }
    }
    else {
      $bools = array($ints ? TRUE : FALSE);
    }

    return self::normalize($bools);
  }

  /**
   * Get random floating point number.
   *
   * @param float $min
   *   Minimum floating point value to return.
   * @param float $max
   *   Maximum floating point value to return.
   * @param int $num
   *   Number of values to return.
   * @param int $precision
   *   Number of decimal digits to round to.
   *
   * @return array|null|float
   *   NULL if the number of values requested is NULL. A floating point number
   *   if the number of values requested is 1 and an array of floating point
   *   numbers if the number of values requested is more than 1.
   */
  public static function getRandomFloat($min, $max, $num = 1, $precision = 3) {
    if ($num < 1) {
      return NULL;
    }

    $faker = NULL;
    if (class_exists('\Faker\Factory')) {
      $faker = \Faker\Factory::create();
    }

    $numbers = array();
    foreach (range(0, $num - 1) as $index) {
      if (!is_null($faker)) {
        $number = $faker->randomFloat(NULL, $min, $max);
      }
      else {
        $number = $min + mt_rand() / mt_getrandmax() * ($max - $min);
      }
      $numbers[] = substr(round($number, 3), 0, 10);
    }

    return $numbers;
  }

  /**
   * Sorts an array of objects.
   *
   * @param $array
   *   An array of objects.
   * @param $orderBy
   */
  public static function sort(&$array, $orderBy) {
    $new_array = array();
    foreach ($array as $key => $obj) {
      $new_array[$key] = get_object_vars($obj->getEntity());
      $obj->hash = $new_array[$key]['hash'] = spl_object_hash($obj);
    }

    $new_array = self::sort_array_multidim($new_array, $orderBy);

    $output = array();
    foreach ($new_array as $key => $value) {

    }
    print_r($new_array);
  }

  public static function filter(&$array, $filterBy) {
    if (is_object($array)) {
      $array = array($array);
    }

    $output = $array;
    foreach ($filterBy as $filter) {
      $name = $filter['name'];
      $value = $filter['value'];
      $new_output = array();
      foreach ($output as $key => $obj) {
        $output_val = $output[$key]->$name;
        if (is_null($value) && is_null($output_val)) {
          $new_output[] = $output[$key];
        }
        elseif (empty($value) && empty($output_val)) {
          $new_output[] = $output[$key];
        }
        elseif ((is_string($value) || is_numeric($value)) && (is_string(
              $output_val
            ) || is_numeric($output_val)) && $output_val == $value
        ) {
          $new_output[] = $output[$key];
        }
        elseif (is_array($output_val)) {
          if (is_string($value) || is_numeric($value)) {

          }
        }
      }
    }
    $output = array();
    foreach ($array as $key => $obj) {
      $entity = $obj->getEntity();
      foreach ($filterBy as $filter) {
        $name = $filter['name'];
        $value = $filter['value'];
        if (is_null($value) && is_null($array[$key]->$name)) {
          $output[] = $array[$key];
        }
        elseif (empty($value) && empty($array[$key]->$name)) {
          $output[] = $array[$key];
        }
      }
    }
  }

  /**
   * @name Mutlidimensional Array Sorter.
   * @author Tufan Barış YILDIRIM
   * @link http://www.tufanbarisyildirim.com
   * @github http://github.com/tufanbarisyildirim
   *
   * This function can be used for sorting a multidimensional array by sql like
   *   order by clause
   *
   * @param mixed $array
   * @param mixed $order_by
   *
   * @return array
   */
  public static function sort_array_multidim(array $array, $order_by) {
    //TODO -c flexibility -o tufanbarisyildirim : this error can be deleted if you want to sort as sql like "NULL LAST/FIRST" behavior.
    if (!is_array($array[0])) {
      throw new Exception(
        '$array must be a multidimensional array!',
        E_USER_ERROR
      );
    }

    $columns = explode(',', $order_by);
    foreach ($columns as $col_dir) {
      if (preg_match('/(.*)([\s]+)(ASC|DESC)/is', $col_dir, $matches)) {
        if (!array_key_exists(trim($matches[1]), $array[0])) {
          trigger_error(
            'Unknown Column <b>' . trim($matches[1]) . '</b>',
            E_USER_NOTICE
          );
        }
        else {
          if (isset($sorts[trim($matches[1])])) {
            trigger_error(
              'Redundand specified column name : <b>' . trim(
                $matches[1] . '</b>'
              )
            );
          }

          $sorts[trim($matches[1])] = 'SORT_' . strtoupper(trim($matches[3]));
        }
      }
      else {
        throw new Exception(
          "Incorrect syntax near : '{$col_dir}'", E_USER_ERROR
        );
      }
    }

    //TODO -c optimization -o tufanbarisyildirim : use array_* functions.
    $colarr = array();
    foreach ($sorts as $col => $order) {
      $colarr[$col] = array();
      foreach ($array as $k => $row) {
        $colarr[$col]['_' . $k] = strtolower($row[$col]);
      }
    }

    $multi_params = array();
    foreach ($sorts as $col => $order) {
      $multi_params[] = '$colarr[\'' . $col . '\']';
      $multi_params[] = $order;
    }

    $rum_params = implode(',', $multi_params);
    eval("array_multisort({$rum_params});");


    $sorted_array = array();
    foreach ($colarr as $col => $arr) {
      foreach ($arr as $k => $v) {
        $k = substr($k, 1);
        if (!isset($sorted_array[$k])) {
          $sorted_array[$k] = $array[$k];
        }
        $sorted_array[$k][$col] = $array[$k][$col];
      }
    }

    return array_values($sorted_array);
  }

  public static function formatDate($date, $format) {
    if (is_null($date)) {
      return NULL;
    }

    if (is_string($date)) {
      $date = strtotime($date);
    }
    if ($format != 'integer' && is_numeric($date)) {
      $date = format_date($date, 'custom', $format);
    }

    return $date;
  }

  /**
   * Whether string starts with a pattern.
   *
   * @param string $haystack
   *   String to be matched.
   * @param string $needle
   *   Pattern.
   *
   * @return bool
   *   TRUE if string starts with the pattern and FALSE otherwise.
   */
  public static function startsWith($haystack, $needle) {
    return (strpos($haystack, $needle) === 0);
  }

  /**
   * Whether string ends with a pattern.
   *
   * @param string $haystack
   *   String to be matched.
   * @param string $needle
   *   Pattern.
   *
   * @return bool
   *   TRUE if string ends with the pattern and FALSE otherwise.
   */
  public static function endsWith($haystack, $needle) {
    return (strrpos($haystack, $needle) == (strlen($haystack) - strlen(
          $needle
        )));
  }

  /**
   * Returns the last element in an array. if input is a string, then the same
   * string is returned.
   *
   * @param string|array $input
   *   Input string or array.
   *
   * @return mixed
   *   If input is string, then the same string is returned. If input is an
   *   array, then last value of the array is returned.
   */
  public static function getLeaf($input) {
    if (is_string($input)) {
      return $input;
    }

    return array_pop($input);
  }
}

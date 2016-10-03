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

    $text_array = array();
    foreach (range(0, $num - 1) as $index) {
      if ($length <= 2) {
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

    $email_addresses = array();
    foreach (range(0, $num - 1) as $index) {
      $email_addresses[] = self::getRandomString(
          8
        ) . '@' . self::getRandomString(20) . '.com';
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

    $urls = array();
    if ($type == 'relative' || ($type == 'any' && self::getRandomBool())
    ) {
      $parts = self::getRandomInt(1, 5);
      $paths = Utils::getRandomString(8, $parts);

      if (is_string($paths)) {
        $urls[] = $paths;
      }
      elseif (is_array($paths)) {
        $urls[] = implode("/", $paths);
      }
    }
    else {
      foreach (range(0, $num - 1) as $index) {
        $urls[] = 'www.' . self::getRandomString(10) . '.com';
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
    $start_int = is_numeric($start_date) ? $start_date : strtotime($start_date);
    if (is_null($end_date)) {
      $end_int = time();
    }
    else {
      $end_int = is_numeric($end_date) ? $end_date : strtotime($end_date);
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

    $numbers = array();
    foreach (range(0, $num - 1) as $index) {
      $numbers[] = mt_rand($start_int, $end_int);
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
      return array_map(function ($obj) {
        return $obj->getId();
      }, $input);
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
      return array_map(function ($obj) {
        return $obj->getLabel();
      }, $input);
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

    $numbers = array();
    foreach (range(0, $num - 1) as $index) {
      $number = $min + mt_rand() / mt_getrandmax() * ($max - $min);
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

  /**
   * Returns the path that drupal_goto() will redirect the user to.
   *
   * @return string
   *   Path where the user will be redirected to.
   */
  public static function getDrupalGotoPath() {
    global $drupal_goto;
    return !empty($drupal_goto['path']) ? $drupal_goto['path'] : '';
  }

  /**
   * Returns the options for the redirect that drupal_goto() has set.
   *
   * @return array
   *   Options array.
   */
  public static function getDrupalGotoOptions() {
    global $drupal_goto;
    return !empty($drupal_goto['options']) ? $drupal_goto['options'] : array();
  }

  /**
   * Returns the HTTP response code that drupal_goto() has set.
   *
   * @return string|int
   *   HTTP response code if available, otherwise an empty string.
   */
  public static function getDrupalGotoCode() {
    global $drupal_goto;
    return !empty($drupal_goto['http_response_code']) ?
      $drupal_goto['http_response_code'] : '';
  }

  /**
   * Reset the drupal_goto global variable.
   */
  public static function resetDrupalGoto() {
    // Inside a function, global variable can only be unset by unsetting it from
    // $GLOBALS array.
    unset($GLOBALS['drupal_goto']);
  }

  /**
   * Clears all the messages from the session.
   *
   * @param null|string $type
   *   Type of messages to clear. If this parameter is not specified, then all
   *   messages will be cleared.
   */
  public static function clearMessages($type = NULL) {
    return drupal_get_messages($type);
  }

  /**
   * Set the URL to the one specified by Purl so that we can avoid a
   * redirection.
   *
   * @param int|NULL $nid
   *   Node id if present, NULL otherwise.
   */
  public static function setSpacesOGPurlURL($nid = NULL) {
    // If URL is not correct, purl forwards the user to the correct page. In
    // order to avoid this, we set the correct URL here.
    if (!empty($nid) && module_exists('spaces_og') && $space = spaces_load('og',
        $nid)
    ) {
      // Most of the following code is copied from activate() function in space_type_purl.inc
      $space->purge_request_destination();
      // @TODO: This will drop other PURL providers. Probably not the desired behavior!
      $purl_params = array(
        'query' => drupal_get_query_parameters($_GET, array('q')),
        'purl' => array(
          'provider' => "spaces_{$space->type}",
          'id' => $space->id,
        ),
        'absolute' => TRUE,
      );
      $_GET['q'] = url($_GET['q'], $purl_params);
    }
  }

  /**
   * This function will retrieve a new
   * single use token from stripe. Helpful
   * for if you are doing a lot of repetitive testing.
   *
   * Note, you will need Guzzle for this. Hopefully you
   * are already using composer. If not, what are you doing?
   */
  public static function getStripeToken($param = array()) {
    //$client = new \GuzzleHttp\Client();
    $pubKey = variable_get('mp_stripe_public', 'pk_test_jLHDb7FuCHiWnVVr03QnyVBV');
    $cardNumber = isset($param['credit_card']['number']) ? $param['credit_card']['number'] : "4242424242424242";
    $cvc = isset($param['credit_card']['code']) ? $param['credit_card']['code'] : "123";
    $expMonth = isset($param['credit_card']['exp_month']) ? $param['credit_card']['exp_month'] : "11";
    $expYear = isset($param['credit_card']['exp_year']) ? $param['credit_card']['exp_year'] : "2018";
    $headers = [
      'Pragma' => 'no-cache',
      'Origin' => 'https://js.stripe.com',
      'Accept-Encoding' => 'gzip, deflate',
      'Accept-Language' => 'en-US,en;q=0.8',
      'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.104 Safari/537.36',
      'Content-Type' => 'application/x-www-form-urlencoded',
      'Accept' => 'application/json',
      'Cache-Control' => 'no-cache',
      'Referer' => 'https://js.stripe.com/v2/channel.html?stripe_xdm_e=http%3A%2F%2Fwww.beanstalk.dev&stripe_xdm_c=default176056&stripe_xdm_p=1',
      'Connection' => 'keep-alive'
    ];
    $postBody = [
      'key' => $pubKey,
      'payment_user_agent' => 'stripe.js/Fbebcbe6',
      'card[number]' => $cardNumber,
      'card[cvc]' => $cvc,
      'card[exp_month]' => $expMonth,
      'card[exp_year]' => $expYear,
    ];

    // if drupal_http_request not response data then we need to make this call again and again three time.
    for ($i = 0; $i < 3; $i++) {
      $response = drupal_http_request('https://api.stripe.com/v1/tokens', array(
        'headers' => $headers,
        'method' => 'POST',
        'data' => drupal_http_build_query($postBody),
        'timeout' => 120
      ));
      if ($response->code == 200) {
        $response = drupal_json_decode($response->data);
        return new Response(TRUE, $response['id'], "");
      }
    }

    $response_date = json_decode($response->data);
    return new Response(FALSE, $response_date->error->message, $response_date->error->message);
  }

  /**
   * This function will return sku list of all subscription products
   * @return Response
   */
  public static function getSubscriptionProductsList() {
    $sku = array();
    $query = db_select('commerce_product', 'cp');
    $query->fields('cp', array('sku'));
    $query->leftJoin('field_data_field_product', 'pr', 'cp.product_id = pr.entity_id');
    $query->condition('pr.bundle', 'subscription', '=');
    $result = $query->execute();
    while ($product = $result->fetchAssoc()) {
      $sku[] = $product['sku'];
    }
    return new Response(TRUE, $sku, NULL);
  }

  /**
   * Loads a block object from the database.
   *
   * This function returns the first block matching the module and delta and theme
   * parameters, so it can be used for theme-specific functionality.
   *
   * @param $module
   *   Name of the module that implements the block to load.
   * @param $delta
   *   Unique ID of the block within the context of $module. Pass NULL to return
   *   an empty block object for $module.
   * @param
   *  Name of theme optional, if not provided it will take from global theme
   *
   * @return
   *   A block object.
   */
  public static function block_load_by_theme($module, $delta, $theme = NULL) {
    if($theme == NULL) {
      global $theme;
    }
    $block = db_query('SELECT * FROM {block} WHERE module = :module AND delta = :delta AND theme = :theme', array(':module' => $module, ':delta' => $delta, ':theme' => $theme))->fetchObject();

    // If the block does not exist in the database yet return a stub block
    // object.
    if (empty($block)) {
      $block = new \stdClass();
      $block->module = $module;
      $block->delta = $delta;
    }

    return $block;
  }
}

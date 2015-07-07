<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 7/7/15
 * Time: 8:32 AM
 */

namespace RedTest\core;


/**
 * Class Mail
 *
 * @package RedTest\core
 */
class Mail {

  /**
   * Delete all emails.
   */
  public static function delete() {
    $test_token = getenv('TEST_TOKEN');
    $test_token_exists = isset($test_token);

    $query = db_delete('mail_logger');
    if ($test_token_exists) {
      $query->condition('test_token', $test_token);
    }
    $query->execute();
  }

  /**
   * Returns emails.
   *
   * @param array|string $fields
   *   A table column or an array of columns to return.
   * @param null|string $mailkey
   *   Filter the results by mail key.
   * @param null|string $subject
   *   Filter the results by subject.
   *
   * @return array
   *   An array of emails.
   */
  public static function get($fields = array(), $mailkey = NULL, $subject = NULL) {
    $test_token = getenv('TEST_TOKEN');
    $test_token_exists = isset($test_token);

    $original_fields = $fields;
    if (is_string($fields)) {
      $fields = array($fields);
    }
    elseif (is_array($fields) && sizeof($fields) == 1) {
      $original_fields = $fields[0];
    }

    $query = db_select('mail_logger', 'm');

    if (sizeof($fields)) {
      $query->fields('m', $fields);
    }
    else {
      $query->fields('m');
    }

    if ($test_token_exists) {
      $query->condition('m.test_token', $test_token);
    }
    if (!is_null($mailkey)) {
      $query->condition('m.mailkey', $mailkey);
    }
    if (!is_null($subject)) {
      $query->condition('m.subject', $subject);
    }

    $result = $query->execute();

    if (is_string($original_fields)) {
      return $result->fetchCol();
    }

    return $result->fetchAssoc();
  }
}
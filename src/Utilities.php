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
    return strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', "_$1", $input));
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

  public static function getRandomDate($date_format, $start_date = 0, $end_date = NULL) {
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
}
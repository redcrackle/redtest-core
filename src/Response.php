<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 6/14/15
 * Time: 10:08 AM
 */

namespace RedTest\core;


/**
 * Class Response
 *
 * @package RedTest\core
 */
class Response {

  /**
   * @var bool
   */
  private $success;

  /**
   * @var array|object|string|null
   */
  private $var;

  /**
   * @var string
   */
  private $msg;

  /**
   * Default constructor.
   *
   * @param bool $success
   *   Whether the function succeeded or not.
   * @param array|object|string|null $var
   *   Object or array of objects to be returned.
   * @param string $msg
   *   Error message.
   */
  public function __construct($success, $var, $msg) {
    $this->success = $success;
    $this->var = $var;
    $this->msg = $msg;
  }

  /**
   * Verify that the function succeeded. Return $var.
   *
   * @param \PHPUnit_Framework_TestCase|string $testCase
   *   PHPUnit test case.
   *
   * @return array|null|object|string
   *   Object or array of objects to be returned.
   */
  public function verify($testCase) {
    if (is_string($testCase)) {
      $testCase = new $testCase;
    }
    $testCase->assertTrue($this->success, $this->msg);
    return $this->var;
  }

  /**
   * Returns the Success variable.
   *
   * @return bool
   *   Success variable.
   */
  public function getSuccess() {
    return $this->success;
  }

  /**
   * Returns the returned array or object.
   *
   * @return array|null|object|string
   *   Object or array of objects that function returned.
   */
  public function getVar() {
    return $this->var;
  }

  /**
   * Returns error message.
   *
   * @return string
   *   Error message.
   */
  public function getMsg() {
    return $this->msg;
  }
}
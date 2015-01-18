<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 3/15/14
 * Time: 2:41 PM
 */

namespace tests\phpunit_tests\core\forms;


final class UserLoginForm extends Form {

  /**
   * Default constructor.
   */
  function __construct() {
    parent::__construct('user_login');
  }

  /**
   * Submit the form.
   *
   * @return mixed $output
   *   User id if the user was able to log in, and an array of errors if not.
   */
  public function submit() {
    $this->fillValues(array('op' => t('Log In')));
    $output = parent::submit();
    if (is_array($output)) {
      // There was an error.
      return $output;
    }
    else {
      $form_state = $this->getFormState();
      return $form_state['uid'];
    }
  }
}
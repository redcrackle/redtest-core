<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 3/15/14
 * Time: 6:00 PM
 */

namespace tests\phpunit_tests\core\forms;


class UserRegisterForm extends Form {

  /**
   * Default UserRegisterForm constructor. If you need to disable modules such as captcha, recaptcha, honeypot, mollom, etc., do it here.
   */
  function __construct() {
    // Captcha and Honeypot must be disabled for form submission
    //module_disable(array('captcha', 'recaptcha', 'honeypot', 'mollom'), TRUE);
    //cache_clear_all();
    parent::__construct('user_register_form');
  }

  /**
   * Default UserRegisterForm deconstructor. If you disabled any module in the constructor, you can enable them here.
   */
  function __destruct() {
    //module_enable(array('captcha', 'recaptcha', 'honeypot', 'mollom'), TRUE);
    //cache_clear_all();
  }

  /**
   * Submit the user registration form.
   *
   * @return mixed $output
   *   ID of the new user if registration was successful, an array of errors if not.
   */
  public function submit() {
    $this->fillValues(array('op' => t('Create new account')));
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
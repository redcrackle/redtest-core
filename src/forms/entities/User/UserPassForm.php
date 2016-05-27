<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 3/15/14
 * Time: 6:00 PM
 */

namespace RedTest\core\forms\entities\User;

use RedTest\core\entities\User;
use RedTest\core\forms\Form;
use RedTest\core\Response;

class UserPassForm extends Form {

  private $userObject;
  /**
   * Default UserRegisterForm constructor. If you need to disable modules such as captcha, recaptcha, honeypot, mollom, etc., do it here.
   */
  function __construct($user) {
    // Captcha and Honeypot must be disabled for form submission
    //module_disable(array('captcha', 'recaptcha', 'honeypot', 'mollom'), TRUE);
    //cache_clear_all();
    parent::__construct('user_pass');

   // $this->userObject = $user;
   // $this->setEntityObject($userObject);
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

    $response = $this->pressButton(t('E-mail new password'));

    if (!$response->getSuccess()) {
      return $response;
    }
    return $this->getResponse($response);
  }
} 
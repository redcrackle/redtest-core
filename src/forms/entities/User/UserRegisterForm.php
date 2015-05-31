<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 3/15/14
 * Time: 6:00 PM
 */

namespace RedTest\core\forms\entities\User;

use RedTest\core\entities\User;
use RedTest\core\forms\entities\EntityForm;

class UserRegisterForm extends EntityForm {

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
    list($success, $msg) = $this->pressButton(t('Create new account'));
    //list($success, $msg) = parent::submit();
    if (!$success) {
      return array(FALSE, NULL, $msg);
    }

    // Get the user from form_state.
    $form_state = $this->getFormState();
    $uid = $form_state['user']->uid;
    $userObject = new User($uid);
    $this->setEntityObject($userObject);

    // Store the created user in $entities so that it can later be deleted.
    global $entities;
    $entities['user'][$uid] = $userObject;

    return array(TRUE, $userObject, "");
  }
} 
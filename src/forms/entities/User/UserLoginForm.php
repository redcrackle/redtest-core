<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 3/15/14
 * Time: 2:41 PM
 */

namespace RedTest\core\forms\entities\User;

use RedTest\core\forms\Form;
use RedTest\core\entities\User;

class UserLoginForm extends Form {

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
    list($success, $msg) = $this->pressButton(t('Log In'));

    // Reset the static variables that can get affected when a user logs in.
    drupal_static_reset('menu_get_item');
    drupal_static_reset('menu_tree');
    drupal_static_reset('menu_tree_page_data');
    drupal_static_reset('menu_tree_set_path');
    drupal_static_reset('Menu::getBlocks');

    if (!$success) {
      return array(FALSE, NULL, $msg);
    }

    // Get the user from form_state.
    $form_state = $this->getFormState();
    $uid = $form_state['uid'];
    $userObject = new User($uid);
    $this->setEntityObject($userObject);

    return array(TRUE, $userObject, "");
  }
}
<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/15/14
 * Time: 5:27 PM
 */

namespace RedTest\core\entities;

use RedTest\core\forms\entities\User as UserForms;
use RedTest\core\Utils;


class User extends Entity {

  /**
   * Default constructor for the user object.
   *
   * @param int $uid
   *   User id.
   */
  public function __construct($uid = NULL) {
    if (!is_null($uid) && is_numeric($uid) && $account = user_load($uid)) {
      parent::__construct($account);
    }
  }

  /**
   * Log a user in.
   *
   * @param string $username
   *   Username.
   * @param string $password
   *   Password.
   *
   * @return mixed $user
   *   User object if the user logged in successfully and an array of errors,
   *   otherwise.
   */
  public static function login($username, $password) {
    $userLoginForm = new UserForms\UserLoginForm();
    $userLoginForm->fillValues(
      array(
        'name' => $username,
        'pass' => $password,
      )
    );
    $uid = $userLoginForm->submit();
    if (is_array($uid)) {
      return $uid;
    }
    else {
      return new User($uid);
    }
  }

  /**
   * Register a new user.
   *
   * @param string $username
   *   Username.
   * @param string $email
   *   Email address.
   * @param string $password
   *   Password.
   * @param array $roles
   *   An array of roles that are to be added to the user in addition to the default role(s) that the user gets on registering. You can either pass in role id or role string.
   *
   * @return mixed $user
   *   User object if the user logged in successfully and an array of errors,
   *   otherwise.
   */
  public static function registerUser($username, $email, $password, $roles = array()) {
    $userRegisterForm = new UserForms\UserRegisterForm();
    $userRegisterForm->fillValues(
      array(
        'name' => $username,
        'mail' => $email,
        'pass' => array(
          'pass1' => $password,
          'pass2' => $password,
        ),
      )
    );

    list($success, $userObject, $msg) = $userRegisterForm->submit();
    if (!$success) {
      return array(FALSE, NULL, $msg);
    }

    /**
     * @todo Find a better way to make the user active and add roles than using user_save().
     */
    $roles = self::formatRoles($roles);

    if (!$userObject->getStatusValues() || sizeof($roles)) {
      $account = $userObject->getEntity();
      $edit['status'] = TRUE;
      $edit['roles'] = $account->roles + $roles;
      $account = user_save($account, $edit);
      if (!$account) {
        return array(
          FALSE,
          NULL,
          "Could not make the user active or could not add roles"
        );
      }

      $userObject = new User(Utils::getId($userObject));
    }

    return array(TRUE, $userObject, "");
  }

  /**
   * Converts an array of role ids or role names to an array of role_id => role_name key/paid values.
   *
   * @param array $roles
   *   An array of role ids or role names.
   * @return array
   *   An associative array with role id as key and role name as value.
   *
   * @throws \Exception
   *   if provided role id or role name does not exist.
   */
  private static function formatRoles($roles) {
    if (is_string($roles) || is_numeric($roles)) {
      $roles = array($roles);
    }

    $output_roles = array();
    foreach ($roles as $rid) {
      if (is_numeric($rid) && $role = user_role_load($rid)) {
        $output_roles[$role->rid] = $role->name;
      }
      elseif (is_string($rid) && $role = user_role_load_by_name($rid)) {
        $output_roles[$role->rid] = $role->name;
      }
      else {
        throw new \Exception("Role $rid does not exist.");
      }
    }

    return $output_roles;
  }

  /**
   * Delete the user.
   */
  public function delete() {
    $userCancelConfirmForm = new UserForms\UserCancelConfirmForm(
      $this->getEntity()
    );
    $userCancelConfirmForm->submit();
  }

  /**
   * Log the currently logged in user out and load the anonymous user.
   */
  public function logout() {
    global $user;
    module_invoke_all('user_logout', $user);
    // Destroy the current session, and reset $user to the anonymous user.
    session_destroy();
    // Load Anonymous user object
    $user = user_load(0);
  }

  /**
   * Log a user in programmatically. The function first checks if the provided
   * input is a valid user id. If not, it checks whether it is a valid
   * username.
   *
   * @param string|int $uid_or_username
   *   Uid or Username.
   *
   * @return object $user
   *   User object.
   */
  public static function loginProgrammatically($uid_or_username) {
    global $user;
    if (is_numeric($uid_or_username) && $user = user_load($uid_or_username)) {
      $login_array = array('name' => $user->name);
    }
    elseif ($user = user_load_by_name($uid_or_username)) {
      $login_array = array('name' => $uid_or_username);
    }
    user_login_finalize($login_array);

    return new User($user->uid);
  }

  public static function createDefault($num = 1, $skip = array(), $roles = array()) {
    global $entities;

    $output = array();
    for ($i = 0; $i < $num; $i++) {
      $username = '';
      do {
        $username = Utils::getRandomString(20);
        $a = user_validate_name($username);
        $b = user_load_by_name($username);
        $c = !$a || $b;
      } while (!is_null(user_validate_name($username)) || user_load_by_name(
          $username
        ));

      $email = '';
      do {
        $email = $username . '@' . Utils::getRandomString(20) . '.com';
      } while (!is_null(user_validate_mail($email)) || user_load_by_mail(
          $email
        ));

      $password = Utils::getRandomString();
      list($success, $object, $msg) = User::registerUser($username, $email, $password, $roles);
      if (!$success) {
        return array(FALSE, $output, $msg);
      }

      $output[] = $object;
      $entities['user'][$object->getId()] = $object;
    }

    return array(TRUE, Utils::normalize($output), "");
  }
}

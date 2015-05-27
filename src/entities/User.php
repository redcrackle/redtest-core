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
   *   An array of roles that are to be added to the user in addition to the
   *   default role(s) that the user gets on registering. You can either pass
   *   in role id or role string.
   *
   * @return mixed $user
   *   User object if the user logged in successfully and an array of errors,
   *   otherwise.
   */
  public static function registerUser(
    $username,
    $email,
    $password,
    $roles = array()
  ) {
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
   * Converts an array of role ids or role names to an array of role_id =>
   * role_name key/paid values.
   *
   * @param array $roles
   *   An array of role ids or role names.
   *
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
   * Delete the user programmatically.
   */
  public function deleteProgrammatically() {
    user_delete($this->getId());
    return TRUE;
  }

  /**
   * Log the currently logged in user out and load the anonymous user.
   */
  public static function logoutFunction() {
    global $user;
    if ($user->uid === 0) {
      // User is already anonymous.
      return;
    }

    module_invoke_all('user_logout', $user);
    // Destroy the current session, and reset $user to the anonymous user.
    session_destroy();
    // Load Anonymous user object
    $user = user_load(0);

    // Reset the static variables that can get affected when a user logs in.
    drupal_static_reset('menu_get_item');
    drupal_static_reset('menu_tree');
    drupal_static_reset('menu_tree_page_data');
    drupal_static_reset('menu_tree_set_path');
  }

  public static function __callStatic($name, $arguments) {
    if ($name == 'logout') {
      static::logoutFunction();
    }
  }

  public function __call($name, $arguments) {
    if ($name == 'logout') {
      static::logoutFunction();
    }
    else {
      return parent::__call($name, $arguments);
    }
  }

  /**
   * Log a user in programmatically. The function first checks if the provided
   * input is a valid user id. If not, it checks whether it is a valid
   * username.
   *
   * @param string|int $uid_or_username
   *   Uid or Username.
   *
   * @return array
   *   An array with three values:
   *   (1) $success: Whether user could log in successfully.
   *   (2) $userObject: User object if the user could log in.
   *   (3) $msg: An error message if user could not log in. If the login was
   *   successful, then this will be empty.
   */
  public static function loginProgrammatically($uid_or_username) {
    global $user;
    if (is_numeric($uid_or_username) && $user = user_load($uid_or_username)) {
      $login_array = array('name' => $user->name);
    }
    elseif ($user = user_load_by_name($uid_or_username)) {
      $login_array = array('name' => $uid_or_username);
    }
    else {
      return array(
        FALSE,
        NULL,
        "User with uid or username $uid_or_username not found."
      );
    }
    user_login_finalize($login_array);

    $userObject = new User($user->uid);

    // Reset the static variables that can get affected when a user logs in.
    drupal_static_reset('menu_get_item');
    drupal_static_reset('menu_tree');
    drupal_static_reset('menu_tree_page_data');
    drupal_static_reset('menu_tree_set_path');
    drupal_static_reset('Menu::getBlocks');

    return array(TRUE, $userObject, "");
  }

  /**
   * Create new users with default field values.
   *
   * @param int $num
   *   Number of entities to create.
   * @param array $skip
   *   An array of fields that need to be skipped while creating the entities.
   * @param array $data
   *   Roles that the user needs to be assigned.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether entity creation succeeded.
   *   (2) $entities: An array of created entities. If there is only one entity
   *   to be created, then it returns the entity itself and not the array.
   *   (3) $msg: Error message if $success is FALSE and empty otherwise.
   */
  public static function createDefault(
    $num = 1,
    $skip = array(),
    $data = array()
  ) {
    $data += array(
      'roles' => array(),
      'required_fields_only' => TRUE,
    );

    $output = array();
    for ($i = 0; $i < $num; $i++) {
      do {
        $username = Utils::getRandomString(20);
        $a = user_validate_name($username);
        $b = user_load_by_name($username);
        $c = !$a || $b;
      } while (!is_null(user_validate_name($username)) || user_load_by_name(
          $username
        ));

      do {
        $email = $username . '@' . Utils::getRandomString(20) . '.com';
      } while (!is_null(user_validate_mail($email)) || user_load_by_mail(
          $email
        ));

      $password = Utils::getRandomString();
      list($success, $object, $msg) = User::registerUser(
        $username,
        $email,
        $password,
        $data['roles']
      );
      if (!$success) {
        return array(FALSE, $output, $msg);
      }

      $output[] = $object;
    }

    return array(TRUE, Utils::normalize($output), "");
  }

  public static function masquerade($uid) {
    global $user;
    $original_user_object = new User($user->uid);
    $old_state = drupal_save_session();
    drupal_save_session(FALSE);
    $user = user_load($uid);
    $user_object = new User($uid);

    return array($user_object, $original_user_object, $old_state);
  }

  public static function unmasquerade(User $original_user_object, $old_state) {
    global $user;
    $user = $original_user_object->getEntity();
    drupal_save_session($old_state);
  }
}

<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 3/15/14
 * Time: 6:22 PM
 */

namespace RedTest\core\forms\entities\User;

use RedTest\core\forms\Form;

class UserCancelConfirmForm extends Form {

  private $account;

  /**
   * Default constructor.
   *
   * @param mixed $uid_or_account
   *   User id or the account object.
   */
  function __construct($uid_or_account) {
    $this->account = NULL;
    if (!empty($uid_or_account) && is_numeric($uid_or_account)) {
      $this->account = user_load($uid_or_account);
    }
    elseif (is_object($uid_or_account)) {
      $this->account = $uid_or_account;
    }

    module_load_include('inc', 'user', 'user.pages');
    parent::__construct('user_cancel_confirm_form', $this->account);
  }

  function submit() {
    $this->fillValues(
      array(
        'user_cancel_method' => 'user_cancel_delete',
        '_account' => $this->account,
      )
    );

    parent::submit($this->account);
  }
}
<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities\Commerce;

use RedTest\core\Response;
use RedTest\core\entities\Entity;
use RedTest\core\Utils;
use RedTest\tests\MPUtils;

/**
 * Class CommerceCustomerProfile
 *
 * @package RedTest\core\entities\Commerce
 */
class CommerceCustomerProfile extends Entity {

  /**
   * Default constructor for the Commerce Customer Profile.
   *
   * @param int $order_id
   *   Order id if an existing order is to be loaded.
   */
  public function __construct($profile_id = NULL) {
    if (!is_null($profile_id) && is_numeric($profile_id)) {
      $profile = commerce_customer_profile_load($profile_id);
      if (!$profile) {
        $this->setErrors("Profile with id $profile does not exist.");
        $this->setInitialized(FALSE);
        return;
      }
    }
    else {
      global $user;
      $class = new \ReflectionClass(get_called_class());
      $type = Utils::makeSnakeCase($class->getShortName());
      $profile = commerce_customer_profile_new($type, $user->uid);
    }

    parent::__construct($profile);
  }

}

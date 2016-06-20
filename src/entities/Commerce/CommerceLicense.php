<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities\Commerce;

use RedTest\core\Utils;
use RedTest\core\entities\Entity;
use RedTest\entities\CommerceProduct\Recurring;
use RedTest\core\Response;

class CommerceLicense extends Entity {

  /**
   * Default constructor for the Commerce License object.
   *
   * @param int $license_id
   *   License id if an existing license entity is to be loaded.
   */
  public function __construct($license_id = NULL) {
    $args = func_get_args();
    array_shift($args);
    $order_id = array_shift($args);
    if (is_null($order_id)) {
      $order_id = 0;
    }

    $order = commerce_order_load($order_id);
    if (!is_null($order_id)) {
      $license = commerce_license_get_order_licenses($order_id);
      if (!$license) {
        $this->setErrors("License for order id $order_id does not exist.");
        $this->setInitialized(FALSE);
        return;
      }

    }

    parent::__construct($license);
  }

}

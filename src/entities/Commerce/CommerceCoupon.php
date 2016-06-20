<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities\Commerce;

use RedTest\core\Response;
use RedTest\core\Utils;
use RedTest\core\entities\Entity;

class CommerceCoupon extends Entity {

  /**
   * Default constructor for the Commerce Coupon object. Do not call this
   * class directly.
   *
   * @param int $coupon_id
   *   Coupon id if an existing coupon is to be loaded.
   * @param int $code
   *   Coupon code if an existing coupon is to be loaded.
   */
  public function __construct($coupon_id = NULL, $code = NULL) {
    if (!is_null($coupon_id)) {
      $coupon = commerce_coupon_load($coupon_id);
      if (!$coupon) {
        $this->setErrors("Coupon with id $coupon does not exist.");
        $this->setInitialized(FALSE);
        return;
      }
    }
    else if (!is_null($code)) {
      $coupon = commerce_coupon_load_by_code($code);
      if (!$coupon) {
        $this->setErrors("Coupon with code $code does not exist.");
        $this->setInitialized(FALSE);
        return;
      }
    }
    else {
      $coupon = commerce_coupon_create('discount_coupon');
    }

    parent::__construct($coupon);
  }

}

<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 4/17/16
 * Time: 6:47 PM
 */

namespace RedTest\core\forms\Commerce;

use RedTest\core\forms\Form;


class CommerceProductForm extends Form {


  public function __construct($product) {
    $this->includeFile('inc', 'commerce_product',
      'includes/commerce_product.forms');
    parent::__construct('commerce_product_product_form', $product);

  }
}
<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities\Commerce\CommerceProduct\Product;

use RedTest\core\entities\Commerce\CommerceProduct;
use RedTest\core\Response;
use RedTest\core\entities\Entity;
use RedTest\core\Utils;
use RedTest\tests\MPUtils;

/**
 * Class CommerceOrder
 *
 * @package RedTest\core\entities\Commerce
 */
class Product extends CommerceProduct {


  /**
   * Default constructor for the Commerce Product object.
   *
   *
   * @param int $product_id
   *   Product id if an existing product is to be loaded.
   */
  public function __construct($product_id = NULL) {
    parent::__construct($product_id);
  }

}

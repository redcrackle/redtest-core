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

class CommerceProduct extends Entity {

  /**
   * Default constructor for the Commerce Product object. Do not call this
   * class directly. Create a separate class for each product type and use its
   * constructor.
   *
   * @param int $product_id
   *   Product id if an existing product is to be loaded.
   */
  public function __construct($product_id = NULL) {
    $class = new \ReflectionClass(get_called_class());

    $type = Utils::makeSnakeCase($class->getShortName());
    if (!is_null($product_id)) {
      $product = NULL;
      if (is_numeric($product_id)) {
        $product = commerce_product_load($product_id);
      }

      if ($product && $product->type == $type) {
        parent::__construct($product);
        return;
      }

      // SKU might have been passed instead.
      $product = commerce_product_load_by_sku($product_id);

      if ($product && $product->type == $type) {
        parent::__construct($product);
        return;
      }

      if (!$product) {
        $this->setErrors("Product with id or sku $product_id and type $type does not exist.");
        $this->setInitialized(FALSE);
        return;
      }
    }
    else {
      $product = commerce_product_new($type);
      parent::__construct($product);
    }
  }

  /**
   * Delete the product programmatically.
   *
   * @return bool
   *   Returns TRUE.
   */
  public function deleteProgrammatically() {
    commerce_product_delete($this->getId());
    return TRUE;
  }

  /**
   * Delete a node using the form.
   *
   * @return Response
   *   Response object.
   */
  /*public function delete() {
    $nodeDeleteConfirm = new NodeDeleteConfirm($this->getId());
    return $nodeDeleteConfirm->submit();
  }*/

  /**
   * View the node in provided view mode.
   *
   * @param string $view_mode
   *   View mode.
   *
   * @return array
   *   Renderable array of the node.
   */
  /*public function view($view_mode = 'full') {
    return node_view($this->getEntity(), $view_mode);
  }*/
}

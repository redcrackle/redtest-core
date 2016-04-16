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

class CommerceProduct extends Entity {

  /**
   * Default constructor for the Commerce Product object. Do not call this
   * class directly. Create a separate class for each product type and use its
   * constructor.
   *
   * @param int $line_item_id
   *   Product id if an existing product is to be loaded.
   */
  public function __construct($line_item_id = NULL) {
    $class = new \ReflectionClass(get_called_class());

    $type = Utils::makeSnakeCase($class->getShortName());
    if (!is_null($line_item_id) && is_numeric($line_item_id)) {
      $product = commerce_product_load($line_item_id);
      if (!$product) {
        $this->setErrors("Product with id $line_item_id does not exist.");
        $this->setInitialized(FALSE);
        return;
      }

      if ($product->type != $type) {
        $this->setErrors("Product's type does not match the class.");
        $this->setInitialized(FALSE);
        return;
      }
    }
    else {
      $product = commerce_product_new($type);
    }

    parent::__construct($product);
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

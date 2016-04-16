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

class CommerceOrder extends Entity {

  /**
   * Default constructor for the Commerce Order object.
   *
   * @param int $order_id
   *   Order id if an existing order is to be loaded.
   */
  public function __construct($order_id = NULL) {
    if (!is_null($order_id) && is_numeric($order_id)) {
      $order = commerce_order_load($order_id);
      if (!$order) {
        $this->setErrors("Order with id $order_id does not exist.");
        $this->setInitialized(FALSE);
        return;
      }
    }
    else {
      global $user;
      $order = commerce_order_new($user->uid);
    }

    parent::__construct($order);
  }

  /**
   * Returns quantity of items of a particular product present in the order.
   *
   * @param int $product_id
   *   Product id.
   *
   * @return int
   *   Number of items of the product present in the order.
   */
  public function getProductQuantity($product_id) {
    $line_items = $this->getFieldItems('commerce_line_items');
    $quantity = 0;
    foreach ($line_items as $line_item) {
      $lineItemEntity = new CommerceLineItem($line_item['line_item_id']);
      $quantity += $lineItemEntity->getProductQuantity($product_id);
    }

    return $quantity;
  }

  public function getProductSKUQuantity($sku) {
    $commerce_product = commerce_product_load_by_sku($sku);
    return $this->getProductQuantity($commerce_product->product_id);
  }

  public function isProductPresent($excluded_product_ids = array()) {
    $line_items = $this->getFieldItems('commerce_line_items');
    foreach ($line_items as $line_item) {
      $lineItemEntity = new CommerceLineItem($line_item['line_item_id']);
      if ($lineItemEntity->isProductPresent($excluded_product_ids)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  public function isProductSKUPresent($excluded_product_skus = array()) {
    $excluded_product_ids = array();
    foreach ($excluded_product_skus as $sku) {
      $commerce_product = commerce_product_load_by_sku($sku);
      if ($commerce_product && !in_array($commerce_product->product_id, $excluded_product_ids)) {
        $excluded_product_ids[] = $commerce_product->product_id;
      }
    }

    return $this->isProductPresent($excluded_product_ids);
  }

  /**
   * Delete the order programmatically.
   *
   * @return bool
   *   Returns TRUE.
   */
  public function deleteProgrammatically() {
    commerce_order_delete($this->getId());
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

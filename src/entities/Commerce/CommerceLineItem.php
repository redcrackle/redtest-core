<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities\Commerce;

use RedTest\core\entities\Entity;
use RedTest\core\Response;
use RedTest\core\Utils;

/**
 * Class CommerceLineItem
 *
 * @package RedTest\core\entities\Commerce
 */
class CommerceLineItem extends Entity {

  /**
   * Default constructor for the Commerce Line Item object. Do not call this
   * class directly. Create a separate class for each line item type and use
   * its constructor.
   *
   * You can also pass a second argument, and it will be interpreted as
   * order_id. It is used for creating a new line item.
   *
   * @param int $line_item_id
   *   Product id if an existing product is to be loaded.
   */
  public function __construct($line_item_id = NULL) {
    $args = func_get_args();
    array_shift($args);
    $order_id = array_shift($args);
    if (is_null($order_id)) {
      $order_id = 0;
    }


    if (!is_null($line_item_id) && is_numeric($line_item_id)) {
      $line_item = commerce_line_item_load($line_item_id);
      if (!$line_item) {
        $this->setErrors("Line item with id $line_item_id does not exist.");
        $this->setInitialized(FALSE);
        return;
      }
    }
    else {
      $class = new \ReflectionClass(get_called_class());
      $type = Utils::makeSnakeCase($class->getShortName());
      $line_item = commerce_line_item_new($type, $order_id);
    }

    parent::__construct($line_item);
  }

  /**
   * Returns the number of items of a provided product id present in the line
   * item.
   *
   * @param int $product_id
   *   Product id.
   *
   * @return int
   *   Number of items present.
   */
  public function getProductQuantity($product_id) {
    $products = $this->getFieldItems('commerce_product');
    $quantity = 0;
    foreach ($products as $product) {
      if ($product['product_id'] == $product_id) {
        $quantity += $this->getEntity()->quantity;
      }
    }

    return $quantity;
  }

  /**
   * Returns the number of items of a provided product SKU present in the line
   * item.
   *
   * @param string $sku
   *   Product SKU.
   *
   * @return int
   *   Number of items present.
   */
  public function getProductSKUQuantity($sku) {
    $commerce_product = commerce_product_load_by_sku($sku);
    return $this->getProductQuantity($commerce_product->product_id);
  }

  /**
   * Returns whether the line item contains any product other than the excluded
   * product ids.
   *
   * @param array $excluded_product_ids
   *   An array of product ids that should not be considered.
   *
   * @return bool
   *   TRUE if at least one product is present and FALSE otherwise.
   */
  public function isProductPresent($excluded_product_ids = array()) {
    $products = $this->getFieldItems('commerce_product');
    foreach ($products as $product) {
      if (!in_array($product['product_id'], $excluded_product_ids)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns whether the line item contains any product other than the excluded
   * product SKUs.
   *
   * @param array $excluded_product_skus
   *   An array of product SKUs that should not be considered.
   *
   * @return bool
   *   TRUE if at least one product is present and FALSE otherwise.
   */
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
   * Returns an associative array of number of items present in the line item
   * keyed by the product id.
   *
   * @return array
   *   Associative array of number of items present in the line item keyed by
   *   the product id.
   */
  public function getProductQuantityMap() {
    $output = array();
    $products = $this->getFieldItems('commerce_product');
    $quantity = $this->getEntity()->quantity;
    foreach ($products as $product) {
      $product_id = $product['product_id'];
      if (array_key_exists($product_id, $output)) {
        $output[$product_id] += $quantity;
      }
      else {
        $output[$product_id] = $quantity;
      }
    }

    return $output;
  }

  /**
   * Returns an associative array of number of items present in the line item
   * keyed by the SKU.
   *
   * @return array
   *   Associative array of number of items present in the line item keyed by
   *   the SKU
   */
  public function getProductSKUQuantityMap() {
    $output = array();
    $products = $this->getFieldItems('commerce_product');
    $quantity = $this->getEntity()->quantity;

    $product_ids = array();
    foreach ($products as $product) {
      $product_ids[] = $product['product_id'];
    }

    $commerce_products = commerce_product_load_multiple($product_ids);

    foreach ($commerce_products as $product_id => $product) {
      $sku = $product->sku;
      if (array_key_exists($sku, $output)) {
        $output[$sku] += $quantity;
      }
      else {
        $output[$sku] = $quantity;
      }
    }

    return $output;
  }

  /**
   * Delete the line item programmatically.
   *
   * @return bool
   *   Returns TRUE.
   */
  public function deleteProgrammatically() {
    commerce_line_item_delete($this->getId());
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

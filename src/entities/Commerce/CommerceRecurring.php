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

use RedTest\core\forms\Form;

class CommerceRecurring extends Entity {

  /**
   * Default constructor for the Commerce Recurring object.
   *
   * @param int $recurring_id
   *   Recurring id if an existing recurring entity is to be loaded.
   */
  public function __construct($recurring_id = NULL) {
    $class = new \ReflectionClass(get_called_class());

    $type = Utils::makeSnakeCase($class->getShortName());
    if (!is_null($recurring_id)) {
      $rec_entity = NULL;
      if (is_numeric($recurring_id)) {
        $rec_entity = commerce_recurring_load($recurring_id);
      }

      if (!empty($rec_entity)) {
        parent::__construct($rec_entity);
        return;
      }
    }
    else {
      $rec_entity = commerce_recurring_new($type);
      parent::__construct($rec_entity);
    }
  }

  /**
   * This function is for get reference product from recurring entity
   * @return bool|Recurring
   */
  public function getCommerceRecurringRefProductValues() {
    $recurring_entity = $this->getEntity();
    $ref_product = field_get_items('commerce_recurring', $recurring_entity, 'commerce_recurring_ref_product');
    if(!empty($ref_product) && isset($ref_product[0]['target_id'])) {
      $product_id = $ref_product[0]['target_id'];
      $product_obj = new Recurring($product_id);
      return $product_obj;
    } else {
      return false;
    }
  }

  /**
   * This function will update due date of recurring entity
   * @param bool $time_stamp
   *  This is option parameter which is due date value which we need to set if this is empty it set current time value
   */
  public function updateDueDate($time_stamp = FALSE) {
    if ($time_stamp == FALSE) {
      $time_stamp = time();
    }
    $this->setValues(array('due_date' => $time_stamp));
  }

  /**
   * This function will create new recurring order and attached with recurring entity
   * @return array|bool
   */
  public function run_recurring_cron() {
    module_load_include('inc', 'commerce_recurring', 'commerce_recurring.rules');
    module_load_include('inc', 'mp_order', 'mp_order.rules');
    $recurring_entity = $this->getEntity();
    // Passing recurring entity and create order
    $return = commerce_recurring_rules_generate_order_from_recurring($recurring_entity);

    if (!empty($return) && isset($return['commerce_order'])) {
      global $entities;
      $order = new CommerceOrder($return['commerce_order']->order_id);
      $order->reload();
      $entities['commerce_order'][$order->order_id] = $order;
      mp_order_update_order_with_store_credit($return['commerce_order']);
      // Attaching order with recurring entity
      commerce_recurring_rules_iterate_recurring_from_order($return['commerce_order']);
      return $return;
    }
    else {
      return FALSE;
    }
  }

  /**
   * This function will update recurring entity status
   * @param $recurring_id
   *  This is recurring entity id status
   * @param $status
   *  This is recurring entity status option by default it will update status 1
   */
  public function updateStatus($recurring_id, $status = 1) {
    db_update('commerce_recurring')
      ->fields(array('status' => $status))
      ->condition('id', $recurring_id, '=')
      ->execute();
  }
}

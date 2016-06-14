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
      $rec_entity = commerce_recurring_new(array('type' => $type));
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

      $product = commerce_product_load($product_id);
      $product_class = Utils::makeTitleCase($product->type);
      $field_class = "RedTest\\entities\\CommerceProduct\\" . $product_class;
      $product_obj = new $field_class($product_id);
      return new Response(TRUE, $product_obj, "");
    } else {
      return new Response(FALSE, NULL, 'Reference product not available.');
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
   * This function will run all applicable cron on recurring entity
   * @return array|bool
   */
  public function runCron($cron = 'All') {
    $recurring_order = array();
    $return_value = array();
    module_load_include('inc', 'commerce_recurring', 'commerce_recurring.rules');
    module_load_include('inc', 'mp_order', 'mp_order.rules');
    module_load_include('inc', 'mp_upgrade', 'mp_upgrade.rules');

    // Create Order Based on recurring entity due date
    $recurring_entity = $this->getEntity();
    if (in_array($cron, array('All', 'create_order'))) {
      $due_entities = commerce_recurring_rules_get_due_items();
      $entity_array = array();

      foreach ($due_entities['commerce_recurring_entities'] as $entity_key => $entity) {
        $entity_array[$entity->id] = $entity;
      }

      if (array_key_exists($this->getId(), $entity_array)) {
        // Passing recurring entity and create order
        $recurring_order = commerce_recurring_rules_generate_order_from_recurring($recurring_entity);
        if (!empty($recurring_order) && isset($recurring_order['commerce_order'])) {
          $return_value['commerce_order'] = $recurring_order['commerce_order'];
          global $entities;
          $order = new CommerceOrder($recurring_order['commerce_order']->order_id);
          $order->reload();
          $entities['commerce_order'][$order->getId()] = $order;
          mp_order_update_order_with_store_credit($recurring_order['commerce_order']);

          mp_subscription_rules_action_update_recurring_billing_due_date($recurring_order['commerce_order']);
          // Attaching order with recurring entity
        }
      }
    }

    //Payment and Order Status of Above Created Order
    if (in_array($cron, array('All', 'pending_payment'))) {
      $orders = $this->getCommerceRecurringOrderValues();
      foreach ($orders as $associated_oder) {
        $order = new CommerceOrder($associated_oder['target_id']);
        if ($order->getStatusValues() == 'recurring_pending') {
          $card_response = commerce_cardonfile_rules_action_order_select_default_card($order->getEntity());
          $total = $order->getFieldItems('commerce_order_total');
          $charge_response = commerce_cardonfile_rules_action_order_charge_card($order->getEntity(), $total[0], $card_response['select_card_response']);
          $return_value['charge_response'] = $charge_response;
        }
      }
    }

    //Upgrade Yearly Plan
    if (in_array($cron, array('All', 'upgrade'))) {
      $recurring_entities = mp_upgrade_rules_action_get_upgraded_items();
      if (array_key_exists($this->getId(), $recurring_entities['commerce_recurring_entities'])) {
        mp_upgrade_rules_action_upgrade_recurring_license($this->getEntity());
      }
    }

    return new Response(TRUE, $return_value, NULL);
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

  /**
   * This function will update due date of recurring entity
   * @param bool $time_stamp
   *  This is option parameter which is due date value which we need to set if this is empty it set current time value
   */
  public function updateStartDate($time_stamp = FALSE) {
    if ($time_stamp == FALSE) {
      $time_stamp = time();
    }
    $this->setValues(array('start_date' => $time_stamp));
  }
}

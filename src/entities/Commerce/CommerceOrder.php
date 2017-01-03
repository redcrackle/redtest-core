<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities\Commerce;

use RedTest\core\entities\Commerce\LineItems\Shipping;
use RedTest\core\Response;
use RedTest\core\entities\Entity;
use RedTest\core\Utils;
use RedTest\tests\MPUtils;
use Stripe;

/**
 * Class CommerceOrder
 *
 * @package RedTest\core\entities\Commerce
 */
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

  /**
   * Returns the number of items of a provided SKU present in the order.
   *
   * @param string $sku
   *   Product SKU.
   *
   * @return int
   *   Number of items present in the order.
   */
  public function getProductSKUQuantity($sku) {
    $commerce_product = commerce_product_load_by_sku($sku);
    return $this->getProductQuantity($commerce_product->product_id);
  }

  /**
   * Returns whether a product other than the excluded product ids is present
   * in the order.
   *
   * @param array $excluded_product_ids
   *   Excluded product ids.
   *
   * @return bool
   *   TRUE if a product is present and FALSE otherwise.
   */
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

  /**
   * Returns whether a product other than the excluded product SKUs is present
   * in the order.
   *
   * @param array $excluded_product_skus
   *   Excluded product SKUs.
   *
   * @return bool
   *   TRUE if a product is present and FALSE otherwise.
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
   * Returns an associative array of number of items present in the order keyed
   * by the product id.
   *
   * @return array
   *   Associative array of number of items present in the order keyed by the
   *   product id.
   */
  public function getProductQuantityMap() {
    $output = array();
    $line_items = $this->getFieldItems('commerce_line_items');
    foreach ($line_items as $line_item) {
      $lineItemEntity = new CommerceLineItem($line_item['line_item_id']);
      $map = $lineItemEntity->getProductQuantityMap();
      foreach ($map as $product_id => $quantity) {
        if (array_key_exists($product_id, $output)) {
          $output[$product_id] += $quantity;
        }
        else {
          $output[$product_id] = $quantity;
        }
      }
    }

    return $output;
  }

  /**
   * Returns an associative array of number of items present in the order keyed
   * by the product SKU.
   *
   * @return array
   *   Associative array of number of items present in the order keyed by the
   *   product SKU.
   */
  public function getProductSKUQuantityMap() {
    $output = array();
    $line_items = $this->getFieldItems('commerce_line_items');
    foreach ($line_items as $line_item) {
      $lineItemEntity = new CommerceLineItem($line_item['line_item_id']);
      $map = $lineItemEntity->getProductSKUQuantityMap();
      foreach ($map as $sku => $quantity) {
        if (array_key_exists($sku, $output)) {
          $output[$sku] += $quantity;
        }
        else {
          $output[$sku] = $quantity;
        }
      }
    }

    return $output;
  }

  /**
   * Verifies whether the number of products in the order matches with the
   * input.
   *
   * @param $product_quantity_map
   *   An associative array of product quantity keyed by product id.
   * @param bool $exact
   *   TRUE if order should not have any product not provided in the
   *   $product_quantity_map array. If this argument is set to FALSE, then
   *   order may have more products than provided in the $product_quantity_map
   *   array and those products are ignored.
   *
   * @return bool
   *   TRUE if products and quantities in the order match with those provided
   *   as input and FALSE otherwise.
   */
  public function hasProducts($product_quantity_map, $exact = TRUE) {
    // Get the actual map of product id to quantity present in the order.
    $map = $this->getProductQuantityMap();

    // If size of $product_quantity_map is more than than of the actual map,
    // then return FALSE since there is at least one product in
    // $product_quantity_map that is not present in the actual map.
    if (sizeof($product_quantity_map) > sizeof($map)) {
      return FALSE;
    }

    // If $exact is set to TRUE then make sure that the number of products in
    // the $product_quantity_map is the same as that in the actual map.
    if ($exact && sizeof($map) != sizeof($product_quantity_map)) {
      return FALSE;
    }

    // Go through each product and quantity in the $product_quantity_map and
    // make sure that it matches with the actual map.
    foreach ($product_quantity_map as $product_id => $quantity) {
      if ($map[$product_id] != $quantity) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Verifies whether the number of products in the order matches with the
   * input.
   *
   * @param $product_quantity_map
   *   An associative array of product quantity keyed by product SKU.
   * @param bool $exact
   *   TRUE if order should not have any product not provided in the
   *   $product_quantity_map array. If this argument is set to FALSE, then
   *   order may have more products than provided in the $product_quantity_map
   *   array and those products are ignored.
   *
   * @return bool
   *   TRUE if products and quantities in the order match with those provided
   *   as input and FALSE otherwise.
   */
  public function hasProductSKUs($product_quantity_map, $exact = TRUE) {
    // Get the actual map of product id to quantity present in the order.
    $map = $this->getProductSKUQuantityMap();

    // If size of $product_quantity_map is more than than of the actual map,
    // then return FALSE since there is at least one product in
    // $product_quantity_map that is not present in the actual map.
    if (sizeof($product_quantity_map) > sizeof($map)) {
      return FALSE;
    }

    // If $exact is set to TRUE then make sure that the number of products in
    // the $product_quantity_map is the same as that in the actual map.
    if ($exact && sizeof($map) != sizeof($product_quantity_map)) {
      return FALSE;
    }

    // Go through each product and quantity in the $product_quantity_map and
    // make sure that it matches with the actual map.
    foreach ($product_quantity_map as $sku => $quantity) {
      if ($map[$sku] != $quantity) {
        return FALSE;
      }
    }

    return TRUE;
  }

  public function getTotalAmount() {
    $total = $this->getFieldItems('commerce_order_total');
    return $total[0]['amount'];
  }

  public function getTotalCurrency() {
    $total = $this->getFieldItems('commerce_order_total');
    return $total[0]['currency_code'];
  }

  /**
   * Reload the order from the database. This is necessary so that
   * commerce_cart_commerce_order_load() function can get called, which in turn
   * invokes the rule "Override price for recurring" provided by the recurring
   * framework.
   */
  public function reload() {
    // Unsetting $refreshed in commerce_cart_commerce_order_load() is needed so
    // that commerce_cart_order_refresh() can be called.
    drupal_static_reset('commerce_cart_commerce_order_load');

    // Temporarily set the value of commerce_cart_refresh_frequency to 0 so that
    // the function commerce_cart_order_can_refresh() which is called in
    // commerce_cart_commerce_order_load() returns TRUE and
    // commerce_cart_order_refresh() can actually get called.
    $commerce_cart_refresh_frequency = variable_get('commerce_cart_refresh_frequency', 0);
    global $conf;
    $conf['commerce_cart_refresh_frequency'] = 0;
    // Load the order from the database which should call the "Override price
    // for recurring" rule provided by recurring framework.
    parent::reload();
    // Set value of commerce_cart_refresh_frequency variable back to what it was
    // earlier.
    $conf['commerce_cart_refresh_frequency'] = $commerce_cart_refresh_frequency;
  }

  /**
   * Returns whether the logged in user can checkout the order.
   *
   * @return bool
   *   TRUE if checkout is allowed and FALSE otherwise.
   */
  public function canCheckout() {
    return commerce_checkout_access($this->getEntity());
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

  /**
   * This function will create recurring order and entity programmatically.
   * @param $product_ids
   * @return \entity
   */
  public static function createProgrammatically($product_ids) {
    global $user;
    global $entities;
    $product = array();
    $quantity = 1;
    $uid = $user->uid;
    $add_shipping = FALSE;
    //
    $subscription_products = Utils::getSubscriptionProductsList()
      ->verify(get_class());
    foreach ($product_ids as $product_id) {
      if ($product = commerce_product_load($product_id)) {

        if($product->type == 'product') {
          $add_shipping = TRUE;
        }
        if (in_array($product->sku, $subscription_products)) {
          $product->commerce_price[LANGUAGE_NONE][0]['amount'] = 0;
          $line_item = commerce_product_line_item_new($product, $quantity);
        }
        else {
          $line_item = commerce_product_line_item_new($product, $quantity);
        }
        commerce_cart_product_add($uid, $line_item);
      }
    }

    // if no product loaded then need to return null
    if (empty($product)) {
      return NULL;
    }

    $order = commerce_cart_order_load($uid);
    $order = commerce_order_status_update($order, "pending", TRUE);
    if($add_shipping) {
      // Save and add the line item to the order.
      $line_item = new Shipping(NULL, $order->order_id);
      $line_item = $line_item->createShippingLineItemProgrammatically($order);
      $new_line_item = commerce_shipping_add_shipping_line_item($line_item, $order, TRUE);
    }  
    commerce_avatax_calculate_sales_tax($order);
    commerce_order_save($order);
    drupal_static_reset('commerce_recurring_order_load_recurring_line_items');
    commerce_checkout_complete($order);

    $order_object = new CommerceOrder($order->order_id);
    $entities['commerce_order'][$order_object->getId()] = $order_object;
    $order_object->reload();
    return new Response(TRUE, $order_object, "");
  }

  /**
   * This function will make payment or order and update status completed and update recurring entity status.
   */
  public function capturePayment($recurring = TRUE) {
    global $user;
    global $entities;

    self::updateOrganisationInLicense($this->getEntity(), $user);
    self::paymentTransaction($this->getEntity());
  //  commerce_order_status_update($this->getEntity(), 'completed');
    if($recurring) {
      $recurring_entity = commerce_recurring_load_by_order($this->getEntity());

      if (!empty($recurring_entity)) {
        $recurring_entity = array_shift($recurring_entity);
        $recurring = new CommerceRecurring($recurring_entity->id);
        $recurring->updateStatus($recurring_entity->id);
        $recurring->reload();
        $entities['commerce_recurring'][$recurring->getId()] = $recurring;
        $recurring_entity_updated = $recurring->getFieldValues('due_date');
        $expiration_timestamp = mp_subscription_get_first_billing_cycle();
        if($expiration_timestamp != $recurring_entity_updated) {
          return new Response(FALSE, NULL, 'Recurring entity Due date is not correct');
        }
      }
      else {
        return new Response(FALSE, NULL, 'Recurring entity not created');
      }
    } 

    return new Response(TRUE, $this, "");
  }

  /**
   * This is helper function called inside createProgrammatically function will update organisation name in license
   * @param $order
   *  This is order object
   * @param $user
   *  This is user object
   */
  private function updateOrganisationInLicense($order, $user) {
    $licenses = commerce_license_get_order_licenses($order);
    foreach ($licenses as $license) {
      $license = entity_load_single('commerce_license', $license->license_id);
      $random_company_name = MPUtils::getCompanyName();
      $license->cle_name[LANGUAGE_NONE][0]['value'] = isset($user->field_organization_name[LANGUAGE_NONE][0]['value']) ? $user->field_organization_name[LANGUAGE_NONE][0]['value'] : $random_company_name;
      $license->synchronize();
    }
  }

  /**
   * This is helper function called inside createProgrammatically used for order payment transaction
   * @param $order
   *  This is order object
   */
  private function paymentTransaction($order) {

    $transaction = commerce_payment_transaction_new('commerce_stripe', $order->order_id);
    $payment_method = commerce_payment_method_instance_load('commerce_stripe|commerce_payment_commerce_stripe');
    $strip_token = Utils::getStripeToken()->verify(get_class());

    $charge = $order->commerce_order_total['und'][0];
    if (!commerce_stripe_load_library()) {
      drupal_set_message(t('Error capturing payment. Please contact shop admin to proceed.'), 'error');
    }

    $c = array(
      'amount' => $charge['amount'],
      'currency' => $charge['currency_code'],
      'card' => $strip_token,
      'capture' => TRUE,
      'description' => t('Order Number: @order_number', array('@order_number' => $order->order_number)),
    );

    Stripe\Stripe::setApiKey($payment_method['settings']['secret_key']);

    try {
      if ($charge['amount'] > 0) {
        $response = Stripe\Charge::create($c);
        $transaction->remote_id = $response->id;
        $transaction->payload[REQUEST_TIME] = $response->__toJSON();
        $transaction->remote_status = 'AUTH_CAPTURE';
        $transaction->message = t('Payment authorized only successfully.');
        $transaction->message .= '<br />' . t('Captured: @date', array('@date' => format_date(REQUEST_TIME, 'short')));
        $transaction->message .= '<br />' . t('Captured Amount: @amount', array('@amount' => $charge['amount']/100));
        $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
        $transaction->amount = $charge['amount'];
        $transaction->currency_code = $charge['currency_code'];
        sleep(5);
        commerce_payment_transaction_save($transaction);
        commerce_payment_commerce_payment_transaction_insert($transaction);
      }
    }
    catch(Exception $e) {
      drupal_set_message(t('We received the following error when trying to capture the transaction.'), 'error');
      drupal_set_message(check_plain($e->getMessage()), 'error');
      $transaction->payload[REQUEST_TIME] = $e->json_body;
      $transaction->message = t('Capture processing error: @stripe_error', array('@stripe_error' => $e->getMessage()));
      $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
      $transaction->remote_status='FAILED';
      commerce_payment_transaction_save($transaction);
    }

    if($charge['amount'] == 0) {
      $user = user_load($order->uid);
      $transaction->instance_id = $payment_method['instance_id'];
      $transaction->amount = $charge['amount'];
      $transaction->currency_code = $charge['currency_code'];
      $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
      $transaction->message = '@name';
      $transaction->message_variables = array('@name' => 'Payment authorized only successfully');
      commerce_payment_transaction_save($transaction);
      commerce_payment_commerce_payment_transaction_insert($transaction);
    }

    if(module_exists('commerce_cardonfile')) {
      $strip_token = Utils::getStripeToken()->verify(get_class());
      $card = _commerce_stripe_create_card($strip_token, $order->uid, $payment_method);
      $remote_id = (string) $card->customer . '|' . (string) $card->id;

      $card_data = commerce_cardonfile_new();
      $card_data->uid = $order->uid;
      $card_data->order_id = $order->order_id;
      $card_data->payment_method = $payment_method['method_id'];
      $card_data->instance_id = $payment_method['instance_id'];
      $card_data->remote_id = $remote_id;
      $card_data->card_type = 'Visa';
      $card_data->card_name = $user->name;
      $card_data->card_number = '1111';
      $card_data->card_exp_month = 5;
      $card_data->card_exp_year = 2018;
      $card_data->status = 1;
      $card_data->instance_default = 1;
      commerce_cardonfile_save($card_data);
    }
  }
  
  /**
   * This function will process payment for pending orders
   * @return array|bool
   */
  public static function runCron($order, $check_pass = FALSE) {
    module_load_include('inc', 'commerce_recurring', 'commerce_recurring.rules');
    module_load_include('inc', 'mp_order', 'mp_order.rules');


    if($check_pass == FALSE) {
      $payment_method = commerce_payment_method_instance_load('commerce_stripe|commerce_payment_commerce_stripe');
      $card_details = commerce_cardonfile_load_multiple_by_uid($order->uid, $payment_method['instance_id'], TRUE);

      foreach($card_details as $key) {
        $card_data = commerce_cardonfile_load($key->card_id);
        $card_data->remote_id = '';
        commerce_cardonfile_save($card_data);
      }

    }

    $card_response = commerce_cardonfile_rules_action_order_select_default_card($order);

    $order_total = field_get_items('commerce_order', $order, 'commerce_order_total');

    $response = commerce_cardonfile_rules_action_order_charge_card($order, $order_total[0], $card_response['select_card_response']);
    return new Response(TRUE, $response, "");
  }

}

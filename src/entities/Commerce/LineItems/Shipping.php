<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities\Commerce\LineItems;

use RedTest\core\entities\Commerce\CommerceCustomerProfile;
use RedTest\core\entities\Commerce\CommerceLineItem;
use RedTest\core\entities\Commerce\ProfileType\Billing;
use RedTest\core\Response;
use RedTest\core\entities\Entity;
use RedTest\core\Utils;
use RedTest\tests\MPUtils;

/**
 * Class CommerceOrder
 *
 * @package RedTest\core\entities\Commerce
 */
class Shipping extends CommerceLineItem {


  /**
   * Default constructor for the Commerce Shipping Line Item object.
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

    parent::__construct($line_item_id, $order_id);
  }

  /**
   * This function will create shipping lineitem
   *
   * @param object $order
   *   order object
   * @param string $service_name
   *   machine name of shipping service name
   *
   * @return object $line_item
   *   Shipping line item object
   */
  public function createShippingLineItemProgrammatically($order, $service_name = 'fedex_ground') {
    $billing = new Billing();
    $billing_id = $billing->createBillingProfileProgrammatically($order)->verify($this);

    $shipping = new \RedTest\core\entities\Commerce\ProfileType\Shipping();
    $shipping_id = $shipping->createShippingProfileProgrammatically($order)->verify($this);

    $order->commerce_customer_billing[LANGUAGE_NONE][0]['profile_id'] = $billing_id->profile_id;
    $order->commerce_customer_shipping[LANGUAGE_NONE][0]['profile_id'] = $shipping_id->profile_id;
    commerce_order_save($order);

    if (isset($service_name) && !isset($order->shipping_rates[$service_name])) {
      // Make the chosen service available to the order.
      commerce_shipping_service_rate_order($service_name, $order);
    }
    elseif (!isset($service_name)) {
      if (empty($order->shipping_rates)) {
        // No available rate.
        return;
      }
      $service_name = key($order->shipping_rates);
    }

    // Extract the unit price from the calculated rate.
    $rate_line_item = $order->shipping_rates[$service_name];
    $rate_line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $rate_line_item);
    $unit_price = $rate_line_item_wrapper->commerce_unit_price->value();

    // Create a new shipping line item with the calculated rate from the form.
    $line_item = commerce_shipping_line_item_new($service_name, $unit_price, $order->order_id, $rate_line_item->data, $rate_line_item->type);

    return $line_item;
  }

}

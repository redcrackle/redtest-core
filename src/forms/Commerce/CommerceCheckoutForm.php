<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 4/23/16
 * Time: 10:41 PM
 */

namespace RedTest\core\forms\Commerce;

use RedTest\core\forms\Form;


class CommerceCheckoutForm extends Form {

  private $page_id;

  private $order_id;

  public function __construct($order_id) {
    $args = func_get_args();
    array_shift($args);
    $page_id = array_shift($args);

    $order = commerce_order_load($order_id);
    if (!$order) {
      $this->setErrors("Order $order_id does not exist.");
      $this->setInitialized(FALSE);
      return;
    }

    $checkout_page = NULL;
    $checkout_pages = commerce_checkout_pages();
    if (is_null($page_id)) {
      $checkout_page = reset($checkout_pages);
    }
    elseif (!empty($checkout_pages[$page_id])) {
      $checkout_page = $checkout_pages[$page_id];
    }

    if (is_null($checkout_page)) {
      $this->setErrors("Checkout page not defined correctly.");
      $this->setInitialized(FALSE);
    }

    $this->includeFile('inc', 'commerce_checkout', 'includes/commerce_checkout.pages');
    parent::__construct('commerce_checkout_form_' . $checkout_page['page_id'], $order, $checkout_page);

    $this->page_id = $page_id;
    $this->order_id = $order_id;
  }

  public function submit() {
    $checkout_pages = commerce_checkout_pages();
    $checkout_page = $checkout_pages[$this->page_id];
    $order = commerce_order_load($this->order_id);

    $response = $this->pressButton(t('Checkout'), array(), 'commerce_checkout_form_' . $checkout_page['page_id'], $order, $checkout_page);

    return $response;
  }
}
<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 4/17/16
 * Time: 6:47 PM
 */

namespace RedTest\core\forms\Commerce;

use RedTest\core\forms\Form;
use RedTest\core\Response;
use RedTest\core\entities\Commerce\CommerceOrder;


class CommerceCartForm extends Form {

  private $order_id;

  private function getCartView() {
    // Load the specified View.
    $view = views_get_view('commerce_cart_form');
    $view->set_display('default');

    // Set the specific arguments passed in.
    $view->set_arguments(array($this->order_id));

    // Override the view url, if an override was provided.
    $view->override_url = 'cart';

    // Prepare and execute the View query.
    $view->pre_execute();
    $view->execute();

    return $view;
  }

  private function getResponse($response) {
    if (!$response->getSuccess()) {
      return $response;
    }

    $form_state = $this->getFormState();
    $order = new CommerceOrder($form_state['order']->order_id);

    return new Response(TRUE, $order, "");
  }

  public function __construct($order_id = NULL) {
    if (is_null($order_id)) {
      global $user;
      $order = commerce_cart_order_load($user->uid);
    }
    else {
      $order = commerce_order_load($order_id);
    }

    $this->order_id = $order->order_id;

    $view = $this->getCartView();

    $output = '';

    parent::__construct(views_form_id($view), $view, $output);
  }

  public function updateQuantity($line_item_row_number, $quantity) {
    $view = $this->getCartView();
    $output = '';

    $this->fillFieldValues(array('edit_quantity', $line_item_row_number), $quantity);
//    sleep(.5);
    $response = $this->pressButton(t('Update cart'), array(), $view, $output);

    return $this->getResponse($response);
  }

  public function removeLineItem($line_item_row_number) {
    $view = $this->getCartView();
    $output = '';

    $response = $this->pressButton(t('Remove'), array('triggering_element_key' => 'delete-line-item-' . $line_item_row_number), $view, $output);

    return $this->getResponse($response);
  }

  public function checkout() {
    $view = $this->getCartView();
    $output = '';

    $response = $this->pressButton(t('Checkout'), array(), $view, $output);

    return $this->getResponse($response);
  }
}
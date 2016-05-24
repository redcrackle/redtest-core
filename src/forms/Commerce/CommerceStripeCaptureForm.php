<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 4/23/16
 * Time: 10:41 PM
 */

namespace RedTest\core\forms\Commerce;

use RedTest\core\entities\Commerce\CommerceOrder;
use RedTest\core\forms\Form;
use RedTest\core\Response;
use RedTest\core\Utils;


class CommerceStripeCaptureForm extends Form {
  private $order_id;

  public function __construct($order_id) {
    $this->order = commerce_order_load($order_id);
    $transaction_array = commerce_payment_transaction_load_multiple(array(), array('order_id' => $order_id));
    $transaction_id = key($transaction_array);
    $this->transaction = $transaction_array[$transaction_id];

    $this->includeFile('inc', 'commerce_stripe', 'includes/commerce_stripe.admin');
    parent::__construct('commerce_stripe_capture_form',
      $this->order, $this->transaction);
  }

  private function getResponse($response) {
    if (!$response->getSuccess()) {
      return $response;
    }

    $form_state = $this->getFormState();
    $order = new CommerceOrder($form_state['order']->order_id);

    return new Response(TRUE, $order, "");
  }

  public function submit() {
    $response = $this->pressButton(t('Capture'), array(), $this->order, $this->transaction);
    if (!$response->getSuccess()) {
      return new Response(TRUE, $response, "");
    }

    // Get the user from form_state.
    $form_state = $this->getFormState();
    return new Response(TRUE, $form_state, "");
  }
}
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
    $args = func_get_args();
    array_shift($args);


    $transaction = commerce_payment_transaction_load(34954);
    $order = commerce_order_load(418699);
    $this->includeFile('inc', 'commerce_stripe', 'includes/commerce_stripe.admin');
    parent::__construct('commerce_stripe_capture_form',
      $order, $transaction);

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
    $transaction = commerce_payment_transaction_load(34954);
    $order = commerce_order_load(418699);
    $response = $this->pressButton(t('Capture'), array(), $order, $transaction);
    if (!$response->getSuccess()) {
      return $response;
    }

    // Get the user from form_state.
    $form_state = $this->getFormState();
    return $form_state;
  }


  public function bkpd_pressButton($name, $order, $transaction) {
    //s$order = commerce_order_load($this->order_id);

    $options['order'] =$order;
    $options['transaction'] =$transaction;

    $response = parent::pressButton($name, $options);

    return $this->getResponse($response);
  }
/*  public function pressButton($name) {
    $transaction = commerce_payment_transaction_load(59399);
    $order = commerce_order_load(443607);

    $response = parent::pressButton($name, array(),
      'commerce_stripe_capture_form');*/
/*    $response = parent::pressButton($name, array(),
      'commerce_stripe_capture_form', $order,
      $transaction);*/

/*    return $this->getResponse($response);
  }*/



/*  public function pressButton() {
    $transaction = commerce_payment_transaction_load(59399);
    $order = commerce_order_load(443607);
    $form_state['values']['op'] = 'Capture';
    $form = drupal_build_form('commerce_stripe_capture_form', $form_state, $order, $transaction);
  }*/


}
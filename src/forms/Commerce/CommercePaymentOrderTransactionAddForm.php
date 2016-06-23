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


class CommercePaymentOrderTransactionAddForm extends Form {

  public static $order;

  public function __construct($order) {
    $args = func_get_args();
    array_shift($args);

    $this->order = $order;
    $this->includeFile('inc', 'commerce_payment',
      'includes/commerce_payment.forms');
    parent::__construct('commerce_payment_order_transaction_add_form', $order);

  }

  private function getResponse($response) {
    if (!$response->getSuccess()) {
      return $response;
    }

    $form_state = $this->getFormState();
    $order = new CommerceOrder($form_state['order']->order_id);

    return new Response(TRUE, $order, "");
  }

  public function pressButton($name) {
    $form_state = $this->getFormState();
    $order = $form_state['order'];
    $response = parent::pressButton($name, array(), $form_state['order']);

    return $this->getResponse($response);
  }

  /**
   * Submit the line item order form.
   * @return mixed $output
   */
  public function addPaymentSubmit($type) {
    $form_state = $this->getFormState();
    $form_state['values']['payment_method'] = $type;
    $form_state['values']['op'] = 'Add payment';
    $form_state['add_payment']['op'] = 'Add payment';
    $this->setFormState($form_state);


    $response = $this->pressButton(t('Add payment'), array('ajax' => TRUE));
    if (!$response->getSuccess()) {
      return $response;
    }

    return new Response(TRUE, $response, "");
  }

  /**
   * Submit the Order form.
   * @return mixed $output
   */
  public function submit() {

    $form_state = $this->getFormState();
    $this->setFormState($form_state);

    $response = $this->pressButton(t('Save'), array());
    if (!$response->getSuccess()) {
      return $response;
    }

    return new Response(TRUE, $response, "");
  }

}

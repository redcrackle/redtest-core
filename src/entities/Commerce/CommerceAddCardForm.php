<?php
/**
 * Created by PhpStorm.
 * User: neeravbm
 * Date: 4/23/16
 * Time: 10:41 PM
 */

namespace RedTest\core\forms\Commerce;

use RedTest\core\forms\Form;
use RedTest\core\Response;
use RedTest\core\Utils;

class CommerceAddCardForm extends Form {

  public function __construct($card_data) {
    parent::__construct('commerce_stripe_cardonfile_create_form', array(), $card_data);
  }

  public function submit() {
    $response = $this->pressButton(t('Add card'));
    if (!$response->getSuccess()) {
      return new Response(FALSE, '', $response->getMsg());
    }

    // Get the user from form_state.
    $form_state = $this->getFormState();
    return new Response(TRUE, $form_state, "");
  }

  public function getStripToken() {
    $form_state = $this->getFormState();
    $strip_token = Utils::getFormStripeToken($form_state['values'])->verify($this);
    return $strip_token;
  }
}
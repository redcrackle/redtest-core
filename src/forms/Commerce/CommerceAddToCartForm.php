<?php

namespace RedTest\core\forms\Commerce;

use RedTest\core\forms\Form;
use RedTest\core\entities\Commerce\CommerceOrder;
use RedTest\core\Response;

/**
 * Class CommerceAddToCartForm
 *
 * @package RedTest\core\forms\Commerce
 */
class CommerceAddToCartForm extends Form {

  /**
   * @var array
   */
  private $cart_context;

  /**
   * @var array
   */
  private $arguments;

  /**
   * Constructor for the Add to cart form. Provide $nid as the id of the
   * product display node. Other optional arguments are product reference field
   * name, view mode of the product display node and language of the product
   * display node. If optional arguments are not provided, their default values
   * are assumed, which are "field_product", "default" and "en" respectively.
   *
   * @param int $nid
   *   Product display node id.
   */
  public function __construct($nid) {
    $args = func_get_args();
    array_shift($args);
    $field_name = array_shift($args);
    $view_mode = array_shift($args);
    $language = array_shift($args);

    if (is_null($field_name)) {
      $field_name = 'field_product';
    }

    if (is_null($view_mode)) {
      $view_mode = 'default';
    }

    if (is_null($language)) {
      $language = 'en';
    }

    $node = node_load($nid);

    $instance = field_info_instance('node', $field_name, $node->type);
    $display = field_get_display($instance, $view_mode, $node);
    $settings = array_merge(field_info_formatter_settings($display['type']), $display['settings']);
    $field_product = field_get_items('node', $node, $field_name);
    $product_id = $field_product[0]['product_id'];
    $products = commerce_product_load_multiple(array($product_id));

    $type = !empty($settings['line_item_type']) ? $settings['line_item_type'] : 'product';
    $line_item = commerce_product_line_item_new(commerce_product_reference_default_product($products), $settings['default_quantity'], 0, array(), $type);
    $line_item->data['context']['product_ids'] = array_keys($products);
    $line_item->data['context']['add_to_cart_combine'] = !empty($settings['combine']);
    $line_item->data['context']['show_single_product_attributes'] = !empty($settings['show_single_product_attributes']);

    $cart_context = array(
      'entity_type' => 'node',
      'entity_id' => $nid,
      'display' => 'default',
      'language' => $language,
    );
    $cart_context['class_prefix'] = $cart_context['entity_type'] . '-' . $nid;
    $cart_context['view_mode'] = $cart_context['entity_type'] . '_' . $view_mode;

    $entity_uri = entity_uri($cart_context['entity_type'], $node);

    $arguments = array(
      'form_id' => commerce_cart_add_to_cart_form_id(array($product_id)),
      'line_item' => $line_item,
      'show_quantity' => $settings['show_quantity'],
    );

    // Add the display path and referencing entity data to the line item.
    if (!empty($entity_uri['path'])) {
      $arguments['line_item']->data['context']['display_path'] = $entity_uri['path'];
    }

    $arguments['line_item']->data['context']['entity'] = array(
      'entity_type' => $cart_context['entity_type'],
      'entity_id' => $cart_context['entity_id'],
      'product_reference_field_name' => $field_name,
    );

    // Update the product_ids variable to point to the entity data if we're
    // referencing multiple products.
    if (count($arguments['line_item']->data['context']['product_ids']) > 1) {
      $arguments['line_item']->data['context']['product_ids'] = 'entity';
    }

    parent::__construct($arguments['form_id'], $arguments['line_item'], $arguments['show_quantity'], $cart_context);

    $this->cart_context = $cart_context;
    $this->arguments = $arguments;
  }

  /**
   * Press the Add to cart button.
   *
   * @return Response
   *   Response object.
   */
  public function submit($show_quantity = FALSE) {
    $show_quantity = ($show_quantity)?$show_quantity:$this->arguments['show_quantity'];
    $response = $this->pressButton(t('Add to cart'), array(), $this->arguments['line_item'], $show_quantity, $this->cart_context);
    if (!$response->getSuccess()) {
      return $response;
    }
    $form_state = $this->getFormState();
    $order_id = $form_state['line_item']->order_id;
    $order = new CommerceOrder($order_id);
    $order->reload();
    // To trigger the rule "Override price for recurring", we need to load the
    // order entity from the database after clearing the cache.
    /*$commerce_order = $order->getEntity();
    unset($commerce_order->data['last_cart_refresh']);
    $order->setEntity($commerce_order);
    drupal_static_reset('commerce_cart_commerce_order_load');
    $order->reload();*/

    global $entities;
    $entities['commerce_order'][$order->order_id] = $order;

    return new Response(TRUE, $order, "");
  }
}

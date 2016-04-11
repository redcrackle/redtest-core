<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities;

use RedTest\core\Response;
use RedTest\core\Utils;

class CommerceOrder extends Entity {

  /**
   * Delete the node programmatically.
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
}

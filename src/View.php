<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 4/17/15
 * Time: 7:44 PM
 */

namespace RedTest\core;


class View {

  private $view;

  public function __construct($view_name, $display_id = NULL) {
    $this->view = views_get_view($view_name);
    if (is_string($display_id)) {
      $this->view->set_display($display_id);
    }
    else {
      $this->view->init_display();
    }
  }

  public function execute(
    $contextual_filters = array(),
    $exposed_filters = array(),
    $page = 0
  ) {
    $this->view->set_current_page($page);
    $this->view->set_arguments($contextual_filters);
    $this->view->set_exposed_input($exposed_filters);
    $this->view->pre_execute();
    $this->view->execute();

    return $this->view->result;
  }

  public function getUrl() {
    return $this->view->get_url();
  }

  public function hasAccess() {
    return Menu::hasAccess($this->getUrl());
  }

  public function getResultCount() {
    return $this->view->total_rows;
  }

  public function hasValues($values, $sorted = TRUE, $exclusive = FALSE) {
    if (!$this->hasAccess()) {
      return FALSE;
    }

    if (array_values($values) !== $values) {
      // Array is associative and not sequential.
      $values = array($values);
    }

    $result = $this->view->result;

    if ($exclusive && (sizeof($values) != sizeof($result))) {
      return FALSE;
    }

    $last_pos_found = -1;
    foreach ($values as $value_array) {
      if ($sorted && ($last_pos_found >= (sizeof($result) - 1))) {
        // We are already at the last position and at least one value is still
        // left to be compared.
        return FALSE;
      }

      $found = FALSE;
      for ($i = $last_pos_found + 1; $i < sizeof($result); $i++) {
        $found = TRUE;
        foreach ($value_array as $key => $value) {
          if ($result[$i]->$key != $value) {
            reset($value_array);
            $found = FALSE;
            break;
          }
        }

        // There is a match.
        if ($found && $sorted) {
          $last_pos_found = $i;
        }

        if ($found) {
          break;
        }
      }

      if (!$found) {
        return FALSE;
      }
    }

    return TRUE;
  }
}
<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 9:33 PM
 */

namespace RedTest\core\forms;

use RedTest\core\Utils;
use RedTest\core\fields\Field;

class Form {

  private $form_id;
  private $form;
  private $form_state;
  private $errors;

  /**
   * Default constructor.
   *
   * @param string $form_id
   *   Form id.
   */
  public function __construct($form_id) {
    $args = func_get_args();
    $this->form_id = $form_id;
    array_shift($args);
    $this->form_state['build_info']['args'] = $args;

    $this->form = drupal_build_form($form_id, $this->form_state);
  }

  /**
   * Include the file. Internally this uses file_load_include() function.
   *
   * @param string $type
   *   Type of file. This is usually the file extension.
   * @param string $module
   *   Module name where the file is present.
   * @param null|string $name
   *   Base file name without the extension. If this is omitted, then $module
   *   is used instead.
   */
  public function includeFile($type, $module, $name = NULL) {
    form_load_include($this->form_state, $type, $module, $name);
  }

  /**
   * Return the form.
   *
   * @return array $form
   *   Form array.
   */
  public function getForm() {
    return $this->form;
  }

  /**
   * Sets the value of form array to the one provided.
   *
   * @param array $form
   *   Form array.
   */
  public function setForm($form) {
    $this->form = $form;
  }

  /**
   * Return the form state.
   *
   * @return array $form_state
   *   Form state array.
   */
  public function getFormState() {
    return $this->form_state;
  }

  /**
   * Sets the value of form state to the one provided.
   *
   * @param array $form_state
   *   Form state array.
   */
  public function setFormState($form_state) {
    $this->form_state = $form_state;
  }

  /**
   * Returns an array of errors.
   *
   * @return array
   *   Array of errors.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Clear errors from a form.
   *
   * @param null|string $name
   *   Element name whose error needs to be cleared. If no element name is
   *   provided, then all errors are cleared.
   */
  public function clearErrors($name = NULL) {
    if (is_null($name)) {
      form_clear_error();
      unset($this->errors);
    }
    else {
      $this->errors = &drupal_static('form_set_error', array());
      if (isset($this->errors[$name])) {
        unset($this->errors[$name]);
      }
    }
  }

  private function removeFileFieldWeights($element = NULL) {
    if (is_null($element)) {
      $element = $this->form;
    }

    if (!empty($element['#type']) && $element['#type'] == 'managed_file') {
      if (array_key_exists('_weight', $element)) {
        form_set_value($element['_weight'], NULL, $this->form_state);
      }
    }
    else {
      foreach (element_children($element) as $key) {
        $this->removeFileFieldWeights($element[$key]);
      }
    }
  }

  /**
   * Sets all checkboxes that have marked to have value as 0 in form_state to
   * be NULL. If we keep them to be 0, then drupal_form_submit() will interpret
   * it as selected.
   *
   * @param null|array $element
   *   Form array or a subset of it from where to begin recursion.
   */
  private function makeUncheckedCheckboxesNull($element = NULL) {
    if (is_null($element)) {
      $element = $this->form;
    }

    if (!empty($element['#type']) && $element['#type'] == 'checkbox') {
      $key_exists = FALSE;
      $value = drupal_array_get_nested_value(
        $this->form_state['values'],
        $element['#parents'],
        $key_exists
      );
      if ($key_exists && !is_null($value) && !$value && !is_string($value)) {
        form_set_value($element, NULL, $this->form_state);
      }
      elseif ($key_exists && !is_null($value) && is_array($value) && sizeof(
          $value
        ) == 1 && isset($value[0]) && $value[0] === 0
      ) {
        // Single checkbox returns array(0 => 0) instead of 0.
        form_set_value($element, NULL, $this->form_state);
      }
    }
    else {
      foreach (element_children($element) as $key) {
        $this->makeUncheckedCheckboxesNull($element[$key]);
      }
    }
  }

  /**
   * Fill value in any field of the form.
   *
   * @param array $values
   *   An associative array with field name and its values.
   */
  public function fillValues($values) {
    foreach ($values as $key => $value) {
      $this->form_state['values'][$key] = $value;
    }
  }

  /**
   * Remove a key from form_state.
   *
   * @param string $key
   *   Key string.
   */
  protected function removeKey($key) {
    unset($this->form_state[$key]);
  }

  /**
   * Returns whether a key is set in form_state.
   *
   * @param string $key
   *   Key string.
   *
   * @return bool
   *   TRUE if key is set and FALSE otherwise.
   */
  protected function hasKey($key) {
    return isset($this->form_state[$key]);
  }

  /**
   * Unset a field.
   *
   * @param string $field_name
   *   Machine name of the field.
   */
  public function emptyField($field_name) {
    unset($this->form_state['values'][$field_name]);
  }

  /**
   * Returns value of a field set is form_state array.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return null|mixed
   *   Value of the field set in form state array.
   */
  public function getValues($field_name) {
    return isset($this->form_state['values'][$field_name]) ? $this->form_state['values'][$field_name] : NULL;
  }

  /**
   * Sets value of a field in form state array.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $values
   *   Value to be set in form state array for a field.
   */
  public function setValues($field_name, $values) {
    $this->form_state['values'][$field_name] = $values;
  }

  /**
   * Simulate action of pressing of an Add More button. This function processes
   * the form based on the specified inputs and updates the form with the new
   * values in the cache so that the form's submit button can work correctly.
   *
   * @param string $triggering_element_name
   *   Name of the Add More button or value of Op key.
   * @param array $options
   *   Options array. If key "ajax" is set to TRUE, then
   *   $triggering_element_name is assumed to be name of the Add More button
   *   otherwise it is taken to be the value of Op key.
   *
   * @return array
   *   An array with two values:
   *   (1) $success: Whether the action of pressing button worked.
   *   (2) $msg: Error message if the action was unsuccessful.
   */
  public function pressButton(
    $triggering_element_name = NULL,
    $options = array()
  ) {
    $options += array('ajax' => FALSE);
    $ajax = $options['ajax'];

    // Make sure that a button with provided name exists.
    if ($ajax && !is_null($triggering_element_name) && !$this->buttonExists(
        $triggering_element_name
      )
    ) {
      return array(FALSE, "Button $triggering_element_name does not exist.");
    }

    if (!$ajax) {
      // If this is not an AJAX request, then the supplied name is the value of
      // Op parameter.
      list($success, $values, $msg) = $this->fillOpValues($triggering_element_name);
      if (!$success) {
        return array(FALSE, $msg);
      }
    }

    $this->clearErrors();
    $this->makeUncheckedCheckboxesNull();
    $this->removeFileFieldWeights();

    $old_form_state_values = !empty($this->form_state['values']) ? $this->form_state['values'] : array();
    $this->form_state = form_state_defaults();

    $args = func_get_args();
    // Remove $triggering_element_name from the arguments.
    array_shift($args);
    // Remove $options from the arguments.
    array_shift($args);
    $this->form_state['build_info']['args'] = $args;
    $this->form_state['programmed_bypass_access_check'] = FALSE;
    //$this->form_state['values']['form_build_id'] = $this->form['#build_id'];
    // Add more field button sets $form_state['rebuild'] to TRUE because of
    // which submit handlers are not called. Hence we set it back to FALSE.
    $this->removeKey('input');
    $this->removeKey('triggering_element');
    $this->removeKey('validate_handlers');
    $this->removeKey('submit_handlers');
    $this->removeKey('clicked_button');

    $this->form_state['input'] = $old_form_state_values;
    $this->form_state['input']['form_build_id'] = $this->form['#build_id'];
    if (!is_null($triggering_element_name)) {
      $this->form_state['input']['_triggering_element_name'] = $triggering_element_name;
    }
    $this->form_state['no_redirect'] = TRUE;
    $this->form_state['method'] = 'post';
    //$this->form_state['programmed'] = TRUE;

    $this->form = drupal_build_form($this->form_id, $this->form_state);

    if ($ajax && !is_null($triggering_element_name)) {
      unset($this->form_state['values'][$triggering_element_name]);
    }

    // Reset the static cache for validated forms otherwise form won't go
    // through validation function again.
    drupal_static_reset('drupal_validate_form');

    if ($errors = form_get_errors()) {
      $this->errors = $errors;

      return array(FALSE, implode(", ", $this->errors));
    }

    return array(TRUE, "");
  }

  /**
   * Returns whether a button with provided name exists in the form. This name
   * is searched recursively in the provided $element array.
   *
   * @param string $name
   *   Button name.
   * @param null|array $element
   *   Sub-array of the form to be searched for. If this is NULL, then search
   *   is started from the top-level form element.
   *
   * @return bool
   *   TRUE if button with given name exists and FALSE otherwise.
   */
  private function buttonExists($name, $element = NULL) {
    if (is_null($element)) {
      $element = $this->form;
    }

    if (!empty($element['#type']) && ($element['#type'] == 'submit' || $element['#type'] == 'button') && !empty($element['#name']) && $element['#name'] == $name) {
      return TRUE;
    }

    foreach (element_children($element) as $key) {
      $button_exists = $this->buttonExists($name, $element[$key]);
      if ($button_exists) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * This function is used to check the field access
   *
   * @param $field_name
   *  This is field name
   *
   * @return bool
   */
  public function isFieldAccessible($field_name) {
    if (isset($this->form[$field_name]['#access'])) {
      return $this->form[$field_name]['#access'];
    }
    else {
      return FALSE;
    }
  }


  public function fillMultiValued($field_name, $values, $offset = 0) {
    // In custom form, fields are single-valued by default so we won't worry
    // about multivalued submissions.
    $this->setValues($field_name, $values);

    return array(TRUE, Utils::normalize($values), "");
  }

  /*public function fillDefaultValues($skip = array()) {
    $fields = array();
    foreach (element_children($this->form) as $field_name) {
      $field = $this->form[$field_name];
      list($field_class, $widget_type) = Field::getFieldClass($this, $field_name);
      if (!empty($field_class)) {
        $function = 'fillDefault' . Utils::makeTitleCase($field_name) . 'Values';
        list($success, $values, $msg) = $this->$function();
        $fields[$field_name] = $values;
        if (!$success) {
          return array(FALSE, $fields, $msg);
        }
      }
      else {
        foreach (element_children($field) as $key) {

        }
      }
    }

    return array(TRUE, $fields, "");
  }*/

  /*private function fillNestedArray($element = NULL, $skip = array()) {
    if (is_null($element)) {
      $element = $this->form;
    }

    foreach (element_children($element) as $field_name) {
      $field = $this->form[$field_name];
      list($field_class, $widget_type) = Field::getFieldClass($this, $field_name);
      if (!empty($field_class)) {
        $function = 'fillDefault' . Utils::makeTitleCase($field_name) . 'Values';
        list($success, $values, $msg) = $this->$function();
        $fields[$field_name] = $values;
        if (!$success) {
          return array(FALSE, $fields, $msg);
        }
      }
      else {
        list($success, $this->fillNestedArray($field, $skip);
      }
    }
  }*/

  /**
   * @param $name
   * @param $arguments
   */
  public function __call($name, $arguments) {
    if (strpos($name, 'fillDefault') === 0 && strrpos(
        $name,
        'Values'
      ) == strlen($name) - 6
    ) {
      // Function name starts with "fillDefault" and ends with "Values".
      $field_name = Utils::makeSnakeCase(substr($name, 11, -6));

      return Field::fillDefaultValues($this, $field_name);
    }
    elseif (strpos($name, 'fill') === 0 && strrpos($name, 'Values') == strlen(
        $name
      ) - 6
    ) {
      // Function name starts with "fill" and ends with "Values".
      $field_name = Utils::makeSnakeCase(substr($name, 4, -6));
      $arguments = array_shift($arguments);

      return Field::fillValues($this, $field_name, $arguments);
    }
    elseif (strpos($name, 'has') === 0 && strrpos($name, 'Access') == strlen(
        $name
      ) - 6
    ) {
      // Function name starts with "has" and ends with "Access". Function name
      // is not one of "hasCreateAccess", "hasUpdateAccess", "hasViewAccess" or
      // "hasDeleteAccess" otherwise code execution would not have reached this
      // function. This means that we are checking if a field is accessible.
      $field_name = Utils::makeSnakeCase(substr($name, 3, -6));

      return $this->hasAccess($field_name);
    }
    elseif (strpos($name, 'is') === 0 && strrpos($name, 'Required') == strlen(
        $name
      ) - 8
    ) {
      // Function name starts with "is" and ends with "Required". We are
      // checking if a field is required or not.
      $field_name = Utils::makeSnakeCase(substr($name, 2, -8));

      return $this->isRequired($field_name);
    }
  }

  /**
   * Returns whether a field is required.
   *
   * @param string|array $parents
   *   Field name or an array of parents along with the field name.
   *
   * @return boolean
   *   TRUE if the field is required and FALSE otherwise.
   */
  public function isRequired($parents) {
    if (is_string($parents) || is_numeric($parents)) {
      $parents = array($parents);
    }

    $key_exists = NULL;
    $value = drupal_array_get_nested_value($this->form, $parents, $key_exists);
    if ($key_exists) {
      return (isset($value['#required']) && $value['#required']);
    }

    return FALSE;
  }

  /**
   * Returns whether user has access to the field.
   *
   * @param string|array $parents
   *   Field name or an array of parents along with the field name.
   *
   * @return bool
   *   TRUE if user has access to the field and FALSE otherwise.
   *
   * @throws \Exception
   */
  public function hasAccess($parents) {
    if (is_string($parents) || is_numeric($parents)) {
      $parents = array($parents);
    }

    $element = $this->getForm();
    if (array_key_exists('#access', $element) && !$element['#access']) {
      return FALSE;
    }

    foreach ($parents as $parent) {
      if (!empty($element[$parent])) {
        $element = $element[$parent];
        if (array_key_exists('#access', $element) && !$element['#access']) {
          return FALSE;
        }
      }
      else {
        throw new \Exception("Key $parent not present.");
      }
    }

    if (array_key_exists('#access', $element) && !$element['#access']) {
      return FALSE;
    }

    return TRUE;
  }
}


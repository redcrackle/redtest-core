<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 9:33 PM
 */

namespace RedTest\core\forms;

use tests\phpunit_tests\core\Utilities;

class Form {

  private $form_id;
  private $form;
  private $form_state;
  private $errors;
  private $ajax_post;
  private $upload_image;


  protected function __construct($form_id) {
    $args = func_get_args();
    //print_r($args);
    $this->form_id = $form_id;
    if (!empty($args)) {
      $this->form = call_user_func_array('drupal_get_form', $args);
    }
    else {
      $this->form = drupal_get_form($this->form_id);
    }

    return $this->form;
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
   * Return the form state.
   *
   * @return array $form_state
   *   Form state array.
   */
  public function getFormState() {
    return $this->form_state;
  }

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

  /**
   * Submit the form.
   *
   * @return mixed $output
   *   True, if successful and array of errors, if not.
   */
  public function submit() {
    $args = func_get_args();
    $this->form_state['build_info']['args'] = $args;
    $this->form_state['programmed_bypass_access_check'] = FALSE;
    $this->form_state['values']['form_build_id'] = $this->form['#build_id'];
    // Add more field button sets $form_state['rebuild'] to TRUE because of
    // which submit handlers are not called. Hence we set it back to FALSE.
    $this->form_state['rebuild'] = FALSE;
    $this->removeKey('input');
    $this->clearErrors();
    drupal_form_submit($this->form_id, $this->form_state);
    if ($errors = form_get_errors()) {
      $this->errors = $errors;

      return FALSE;
    }

    return TRUE;
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
   * Unset a field.
   *
   * @param string $field_name
   *   Machine name of the field.
   */
  public function emptyField($field_name) {
    unset($this->form_state['values'][$field_name]);
  }

  protected function getValues($field_name) {
    return !empty($this->form_state['values'][$field_name]) ? $this->form_state['values'][$field_name] : NULL;
  }

  /**
   * Fill values in a text field.
   *
   * @param string $field_name
   *   Field name.
   * @param array $values
   *   Array of field values if the field is multi-valued or a single value if
   *   the field is single-valued.
   */
  public function fillTextField($field_name, $values) {
    $this->emptyField($field_name);

    if (is_array($values)) {
      $index = 0;
      foreach ($values as $value) {
        $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['value'] = $value;
        $index++;
      }
    }
    else {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][0]['value'] = $values;
    }
  }

  /**
   * Set the path value.
   *
   * @param string $field_name
   *   Field name.
   * @param array $values
   *   Array of field values.
   */
  public function fillPathField($field_name, $values) {
    $this->emptyField($field_name);

    $this->fillValues(
      array(
        $field_name => array(
          'value' => $values,
          'provider' => 'spaces_og',
        )
      )
    );
  }

  public function fillNumber($field_name, $values) {
    $this->emptyField($field_name);

    // is_string doesn't work here because a numeric value shows FALSE in
    // is_string().
    if (!is_array($values)) {
      $values = array($values);
    }

    $index = 0;
    foreach ($values as $value) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['value'] = $value;
      $index++;
    }
  }

  /**
   * Fill single-valued text area field.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $value
   *   A single value or an array.
   */
  public function fillTextAreaSingle($field_name, $value) {
    $this->emptyField($field_name);

    if (is_string($value)) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][0]['value'] = $value;
    }
    else {
      foreach ($value as $key => $value_single) {
        $this->form_state['values'][$field_name][LANGUAGE_NONE][0][$key] = $value_single;
      }
    }
  }

  /**
   * Fill mult-valued text area field.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $values
   *   A single string or an array.
   */
  public function fillTextAreaMultiple($field_name, $values) {
    $this->emptyField($field_name);

    $index = 0;
    if (is_string($values)) {
      $values = array($values);
    }
    foreach ($values as $value) {
      if (is_string($value)) {
        $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['value'] = $value;
      }
      else {
        foreach ($value as $key => $value_single) {
          $this->form_state['values'][$field_name][LANGUAGE_NONE][$index][$key] = $value_single;
        }
      }
      $index++;
    }
  }

  /**
   * Fill text area with summary widget.
   *
   * @param string $field_name
   *   Field name.
   * @param string|array $values
   *   Either a string or an array. If it's a string, then it is assumed that
   *   the field has only one value. If it is an array of strings, then it is
   *   assumed that the field is multi-valued and the strings in the array
   *   correspond to multiple text values of this field. If it is an array of
   *   arrays, then it is assumed that the field is multi-valued and the inside
   *   array can have the keys 'value', 'summary' or 'format' which will be set
   *   in form_state. Here are a few examples this parameter can take:
   *   "<p>This is text string.</p>", or
   *   array("<p>This is text string 1.</p>", "This is text string 2."), or
   *   array(
   *     array(
   *       'value' => "This is text string 1.",
   *       'summary' => "<p>Text string 1</p>",
   *       'format' => 'filtered_html',
   *     ),
   *     array(
   *       'value' => "This is text string 2.",
   *       'summary' => "Text string 2",
   *       'format' => 'plain_text',
   *     ),
   *   );
   * @param string $summary
   *   Summary text. If $values doesn't specify summary explicitly, then this
   *   parameter is used as a default.
   * @param string $format
   *   Text format. If $values doesn't specify text format explicitly, then
   *   this parameter is used as a default.
   */
  public function fillTextTextareaWithSummary(
    $field_name,
    $values,
    $summary = '',
    $format = ''
  ) {
    $this->emptyField($field_name);

    $defaults = array();
    if (!empty($summary)) {
      $defaults['summary'] = $summary;
    }
    if (!empty($format)) {
      $defaults['format'] = $format;
    }

    unset($this->form_state['values'][$field_name]);

    $input = array();

    if (is_string($values)) {
      // Values is a string, which means that it's single-valued.
      $input[0] = array('value' => $values) + $defaults;
    }
    elseif (is_array($values)) {
      // $values is an array. It can be an array of strings or array of arrays.
      foreach ($values as $key => $val) {
        if (is_string($val)) {
          $input[$key] = array('value' => $val) + $defaults;
        }
        elseif (is_array($val)) {
          $input[$key] = $val + $defaults;
        }
      }
    }

    $this->form_state['values'][$field_name][LANGUAGE_NONE] = $input;
  }

  public function fillTextTextarea($field_name, $values, $format = '') {
    $this->emptyField($field_name);

    $defaults = array();
    if (!empty($format)) {
      $defaults['format'] = $format;
    }

    unset($this->form_state['values'][$field_name]);

    $input = array();

    if (is_string($values)) {
      // Values is a string, which means that it's single-valued.
      $input[0] = array('value' => $values) + $defaults;
    }
    elseif (is_array($values)) {
      // $values is an array. It can be an array of strings or an array of arrays.
      foreach ($values as $key => $val) {
        if (is_string($val)) {
          $input[$key] = array('value' => $val) + $defaults;
        }
        elseif (is_array($val)) {
          $input[$key] = $val + $defaults;
        }
      }
    }

    $this->form_state['values'][$field_name][LANGUAGE_NONE] = $input;
  }

  /**
   * Fill Simple Hierarchical Select Taxonomy TaxonomyTerm Reference field.
   *
   * @param string $field_name
   *   Field name.
   *
   * @param int|array $values
   *   Either a integer or an array of integers. Integer corresponds to the
   *   term id. If it's an integer, then it is assumed that the field has only
   *   one value. If it is an array of integers, then it is assumed that the
   *   field has multiple values. Here are a few examples this parameter can
   *   take:
   *   "25", or
   *   array(
   *     25,
   *     32
   *   );
   */
  public function fillTaxonomyShs($field_name, $values) {
    $this->emptyField($field_name);

    $input = array();
    if (is_string($values)) {
      $input[0] = array(
        'tid' => $values,
        '_weight' => 0,
      );
    }
    elseif (is_array($values)) {
      foreach ($values as $key => $val) {
        $input[$key] = array(
          'tid' => $val,
          '_weight' => $key,
        );
      }
    }
    //$input['add_more'] = t("Add another item");

    /*$this->form_state['input'] = $this->form_state['values'];
    $this->form_state['input'][$field_name][LANGUAGE_NONE] = $input;
    $this->form_state['input']['form_build_id'] = $this->form['#build_id'];
    $this->form_state['input']['form_id'] = $this->form['#form_id'];
    $this->form_state['input']['form_token'] = $this->form['form_token']['#default_value'];
    $this->form_state['input']['_triggering_element_name'] = 'field_contracting_family_add_more';
    $this->form_state['input']['_triggering_element_value'] = 'Add another item';
    $this->form_state['no_redirect'] = TRUE;
    $this->form_state['method'] = 'post';
    $this->form_state['programmed'] = TRUE;*/

    $this->form_state = form_state_defaults();
    // Get the form from the cache.
    $this->form = form_get_cache($this->form['#build_id'], $this->form_state);
    $unprocessed_form = $this->form;
    $this->form_state['input'] = $this->form_state['values'];
    $this->form_state['input'][$field_name][LANGUAGE_NONE] = $input;
    $this->form_state['input']['form_build_id'] = $this->form['#build_id'];
    $this->form_state['input']['form_id'] = $this->form['#form_id'];
    $this->form_state['input']['form_token'] = $this->form['form_token']['#default_value'];
    $this->form_state['input']['_triggering_element_name'] = $field_name . '_add_more';
    $this->form_state['input']['_triggering_element_value'] = 'Add another item';
    $this->form_state['no_redirect'] = TRUE;
    $this->form_state['method'] = 'post';
    $this->form_state['programmed'] = TRUE;

    drupal_process_form(
      $this->form['#form_id'],
      $this->form,
      $this->form_state
    );

    // Rebuild the form and set it in cache. This is the code at the end of
    // drupal_process_form() after above code boils out at
    // $form_state['programmed'] = TRUE.
    $this->form = drupal_rebuild_form(
      $this->form['#form_id'],
      $this->form_state,
      $this->form
    );
    if (!$this->form_state['rebuild'] && $this->form_state['cache'] && empty($this->form_state['no_cache'])) {
      form_set_cache(
        $this->form['#build_id'],
        $unprocessed_form,
        $this->form_state
      );
    }
    /*$_POST['field_contracting_family'][LANGUAGE_NONE] = $input;
    $_POST['form_build_id'] = $this->form['#build_id'];
    $_POST['form_token'] = $this->form['form_token']['#default_value'];
    $_POST['form_id'] = $this->form['#form_id'];
    $_POST['_triggering_element_name'] = 'field_contracting_family_add_more';
    $_POST['_triggering_element_value'] = 'Add another item';
    $sub_form = ajax_form_callback();
    unset($_POST);*/

  }

  public function InlineEntityFormCancel(
    $field_name,
    $ginfo,
    $triggering_element_name = FALSE,
    $field_parents = FALSE
  ) {
    $form = $this->getForm();
    if ($triggering_element_name == FALSE) {
      $triggering_element_name = $form[$field_name][LANGUAGE_NONE]['actions']['ief_add']['#name'];
    }
    $form_build_id = $form['#build_id'];
    $token = $form['form_token']['#default_value'];

    $_POST['og_group_ref'][LANGUAGE_NONE][0]['default'] = $ginfo;
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
    $_POST['form_build_id'] = $form_build_id;
    $_POST['form_token'] = $token;
    $_POST['form_id'] = $form['form_id']['#value'];
    $_POST['_triggering_element_name'] = $triggering_element_name;
    $_POST['_triggering_element_value'] = 'Cancel';
    $_POST['ajax_iframe_upload'] = 1;

    // Click on Add New Node button
    $sub_form = ajax_form_callback();
    unset($_POST);
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);

    // If More then 1 level
    if ($field_parents) {
      $element = $sub_form;
      drupal_array_set_nested_value($form, $field_parents, $element);
      // Update the Form with Latest form
      $this->form = $form;
    }
    else {
      $this->form[$field_name][LANGUAGE_NONE] = $sub_form;
    }
  }

  /**
   * Fill generic file. Upload images.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $image_paths
   *   A path or an array of paths of images which are to be uploaded.
   */
  public function fillFileGeneric(
    $field_name,
    $image_paths,
    $scheme = 'public'
  ) {
    $this->emptyField($field_name);

    if (is_string($image_paths)) {
      $image_paths = array($image_paths);
    }

    $index = 0;
    $input = array();
    $files = array();
    foreach ($image_paths as $image_path) {
      $filename = drupal_basename($image_path);
      $full_image_path = 'tests/assets/' . $image_path;
      $file_temp = file_get_contents($full_image_path);
      $file_temp = file_save_data(
        $file_temp,
        $scheme . '://' . $filename,
        FILE_EXISTS_RENAME
      );
      // Set file status to temporary otherwise there is validation error.
      $file_temp->status = 0;
      file_save($file_temp);

      $files[] = $file_temp;

      $input[$index] = array(
        'fid' => $file_temp->fid,
        'display' => 1,
      );

      $old_form_state_values = !empty($this->form_state['values']) ? $this->form_state['values'] : array();
      $this->form_state = form_state_defaults();
      // Get the form from the cache.
      $this->form = form_get_cache($this->form['#build_id'], $this->form_state);
      $unprocessed_form = $this->form;
      $this->form_state['input'] = $old_form_state_values;
      $this->form_state['input'][$field_name][LANGUAGE_NONE] = $input;
      $this->form_state['input']['form_build_id'] = $this->form['#build_id'];
      $this->form_state['input']['form_id'] = $this->form['#form_id'];
      $this->form_state['input']['form_token'] = $this->form['form_token']['#default_value'];
      $button_name = $field_name . '_' . LANGUAGE_NONE . '_' . (sizeof(
            $input
          ) - 1) . '_upload_button';
      //$button_name = $field_name . '_' . LANGUAGE_NONE . '_0_upload_button';
      $this->form_state['input']['_triggering_element_name'] = $button_name;
      $this->form_state['input']['_triggering_element_value'] = 'Upload';
      $this->form_state['no_redirect'] = TRUE;
      $this->form_state['method'] = 'post';
      $this->form_state['programmed'] = TRUE;

      drupal_process_form(
        $this->form['#form_id'],
        $this->form,
        $this->form_state
      );

      // Rebuild the form and set it in cache. This is the code at the end of
      // drupal_process_form() after above code boils out at
      // $form_state['programmed'] = TRUE.
      // Set $form_state['programmed'] = FALSE so that Line 504 on file.field.inc can add a default value at the end. Otherwise multi-valued submit fails.
      $this->form_state['programmed'] = FALSE;
      $this->form = drupal_rebuild_form(
        $this->form['#form_id'],
        $this->form_state,
        $this->form
      );
      if (!$this->form_state['rebuild'] && $this->form_state['cache'] && empty($this->form_state['no_cache'])) {
        form_set_cache(
          $this->form['#build_id'],
          $unprocessed_form,
          $this->form_state
        );
      }

      unset($this->form_state['values'][$button_name]);

      //$_GET['q'] = 'file/ajax/field_contract_files/und/' . $this->form['#build_id'];
      /*$_POST = $this->form_state['values'];
      $_POST[$field_name][$index] = $input[$index];
      $_POST['form_build_id'] = $this->form['#build_id'];
      $_POST['form_token'] = $this->form['form_token']['#default_value'];
      $_POST['form_id'] = $this->form['#form_id'];*/

      /*list($form, $form_state, $form_id, $form_build_id, $commands) = ajax_get_form();
      // Get the current element and count the number of files.
      $current_element = $form;
      foreach ($form_parents as $parent) {
        $current_element = $current_element[$parent];
      }
      $current_file_count = isset($current_element['#file_upload_delta']) ? $current_element['#file_upload_delta'] : 0;

      // Process user input. $form and $form_state are modified in the process.
      drupal_process_form($form['#form_id'], $form, $form_state);*/
      /*$commands = file_ajax_upload(
        'file',
        'ajax',
        $field_name,
        LANGUAGE_NONE,
        $this->form['#build_id']
      );
      unset($_POST);*/

      $index++;
    }


    /*$_FILES['files'] = array(
      'name' => array(
        'field_contract_files_und_0' => 'Continuous Delivery.pdf',
      ),
      'type' => array(
        'field_contract_files_und_0' => 'application / pdf',
      ),
      'tmp_name' => array(
        //'field_contract_files_und_0' => '/private/var/tmp / php7uhiOH',
        'field_contract_files_und_0' => $file_temp->uri,
      ),
      'error' => array(
        'field_contract_files_und_0' => 0,
      ),
      'size' => array(
        'field_contract_files_und_0' => 173988,
      ),
    );*/

    return $files;
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

  /**
   * Fill URL field.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $values
   *   A URL string or an array of URL strings.
   */
  public function fillUrlField($field_name, $values) {
    $this->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $index = 0;
    foreach ($values as $value) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['url'] = $value;
      $index++;
    }
  }

  public function fillTaxonomyAutocomplete($field_name, $values) {
    $this->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $this->form_state['values'][$field_name][LANGUAGE_NONE] = implode(
      ",",
      $values
    );
  }

  public function fillTermReferenceTree($field_name, $values) {
    $this->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $index = 0;
    foreach ($values as $value) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['tid'] = $value;
      $index++;
    }
  }

  public function fillAutocompleteDeluxeTaxonomy($field_name, $values) {
    $this->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $this->form_state['values'][$field_name][LANGUAGE_NONE] = array(
      'textfield' => '',
      'value_field' => '""' . implode('"" ""', $values) . '""',
    );
  }

  public function fillOptionsOnoff($field_name, $value) {
    $this->emptyField($field_name);

    if ($value) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE] = $value;
    }
  }

  public function fillOptionsSelect($field_name, $values) {
    $this->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $field = field_info_field($field_name);
    if ($field['cardinality'] == 1) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE] = implode(
        ",",
        $values
      );
    }
    else {
      $index = 0;
      foreach ($values as $value) {
        $this->form_state['values'][$field_name][LANGUAGE_NONE][$index] = $value;
        $index++;
      }
    }
  }

  public function fillTextTextfield($field_name, $values) {
    $this->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $index = 0;
    foreach ($values as $value) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['value'] = $value;
      $index++;
    }
  }

  public function fillDatePopup($field_name, $values) {
    $this->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $index = 0;
    foreach ($values as $value) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['value']['date'] = $value;
    }
  }

  /**
   * Fill entity reference field.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $values
   *   A single entity id or an array of entity ids.
   */
  public function fillEntityreferenceField($field_name, $values) {
    $this->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $index = 0;
    foreach ($values as $value) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['target_id'] = $value;
      $index++;
    }
  }

  public function fillEntityreferenceViewWidget($field_name, $values) {
    $this->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $index = 0;
    foreach ($values as $value) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['target_id'] = $value;
      $index++;
    }
  }

  /**
   * Fill email field.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $values
   *   A single email address or an array of email addresses.
   */
  public function fillEmailField($field_name, $values) {
    $this->emptyField($field_name);

    if (is_array($values)) {
      $index = 0;
      foreach ($values as $value) {
        $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['email'] = $value;
        $index++;
      }
    }
    else {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][0]['email'] = $values;
    }
  }

  /**
   * Fill OG Group Reference field.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $values
   *   A single group id or an array of group ids.
   */
  public function fillOGGroupReferenceWidgetField($field_name, $values) {
    $this->emptyField($field_name);

    if (is_string($values)) {
      $values = array($values);
    }

    $index = 0;
    foreach ($values as $val) {
      $this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['default'] = $val;
      $index++;
    }
  }

  /**
   * This function is used for value in inline entity form field
   *
   * @param $field_name
   *   This is name of field
   * @param $value
   *   This is value of field
   */
  public function fillInlineEntityFormWidgetField($field_name, $value) {
    $this->emptyField($field_name);

    if (!is_array($value)) {
      $value = array($value);
    }
    $index = 0;
    foreach ($value as $val) {
      /*$this->form_state['values'][$field_name][LANGUAGE_NONE][$index]['target_id'] = $val;
      $form = $this->getForm();
      $scope = $form['field_scope'];
      $ief_id = $scope['und']['#ief_id'];
      $node = node_load($val);
      unset($node->nid);
      unset($node->vid);
      $this->form_state['inline_entity_form'][$ief_id]['entities'][$index]['entity'] = $node;
      $this->form_state['inline_entity_form'][$ief_id]['entities'][$index]['weight'] = 0;
      $this->form_state['inline_entity_form'][$ief_id]['entities'][$index]['form'] = null;
      $this->form_state['inline_entity_form'][$ief_id]['entities'][$index]['needs_save'] = TRUE;
      $index++;

      $this->form_state['values'][$field_name][LANGUAGE_NONE]['form']['title'] = 'Anil Scope';
      $this->form_state['values'][$field_name][LANGUAGE_NONE]['form']['status'] = 1;
      $this->form_state['values'][$field_name][LANGUAGE_NONE]['form']['actions']['ief_add_save'] = 'Create node';
      $this->form_state['values'][$field_name][LANGUAGE_NONE]['form']['actions']['ief_add_cancel'] = 'Cancel';*/
    }
  }

  /**
   * This function is used for fillup event date field
   *
   * @param string $field_name
   *   Field name.
   * @param string $start_date
   *   Start date.
   * @param string $start_time
   *   Start time.
   * @param string $end_date
   *   End date.
   * @param string $end_time
   *   End time.
   */
  function fillDateWidgetField(
    $field_name,
    $start_date,
    $start_time,
    $end_date,
    $end_time
  ) {
    $this->emptyField($field_name);

    $this->form_state['values'][$field_name][LANGUAGE_NONE][0]['show_date'] = 1;
    $this->form_state['values'][$field_name][LANGUAGE_NONE][0]['value']['date'] = $start_date;
    $this->form_state['values'][$field_name][LANGUAGE_NONE][0]['value']['time'] = $start_time;
    $this->form_state['values'][$field_name][LANGUAGE_NONE][0]['value2']['date'] = $end_date;
    $this->form_state['values'][$field_name][LANGUAGE_NONE][0]['value2']['time'] = $end_time;
  }

  /**
   * This function is used for purl field value
   *
   * @param $field_name
   *   This is field name
   * @param $value
   *   This is value of filed
   */
  function fillPurlWidgetField($field_name, $value) {
    $this->emptyField($field_name);

    $this->form_state['values'][$field_name]['value'] = $value;
    $this->form_state['values'][$field_name]['provider'] = 'spaces_og';
    $this->form_state['values'][$field_name]['id'] = 1773;
  }

  /**
   * Set radio button field.
   *
   * @param string $field_name
   *   Field name.
   * @param string $value
   *   Value of the radio button to be set to.
   */
  public function fillRadioButtonsWidgetField($field_name, $value) {
    $this->emptyField($field_name);
    $this->form_state['values'][$field_name][LANGUAGE_NONE]['value'] = $value;
  }

  /**
   * Fill OG Vocab field.
   *
   * @param string $field_name
   *   Field name.
   * @param int $gid
   *   Group id.
   */
  public function fillVocabOGRelationWidgetField($field_name, $gid) {
    $this->emptyField($field_name);
    $this->form_state['values'][$field_name]['group_type'] = 'node';
    $this->form_state['values'][$field_name]['gid'] = $gid;
  }

  /**
   * This function is used for content type field values in vocabulary
   *
   * @param $field_name
   *   This is name of field
   */
  public function fillOGContentTypeWidgetField($field_name) {
    $this->emptyField($field_name);
    $this->form_state['values']['og_vocab'][$field_name]['status'] = 1;
    $this->form_state['values']['og_vocab'][$field_name]['widget_type'] = 'entityreference_autocomplete_tags';
    $this->form_state['values']['og_vocab'][$field_name]['required'] = FALSE;
  }

  /**
   * This function is used for vocabulary machine name
   *
   * @param  $value
   *   This is vocabulary machine name
   */
  public function fillTermVocabWidgetField($value) {
    $this->form_state['values']['vocabulary_machine_name'] = $value;
  }

  /**
   * This function is used for vocabulary id
   *
   * @param  $value
   *   This is vocabulary id
   */
  public function fillTermVocabVidWidgetField($value) {
    $this->form_state['values']['vid'] = $value;
  }

  /**
   * This function is used for OG vocabulary field
   *
   * @param $field_name
   *    Field Name
   * @param $vid
   *   This is name of field
   * @param $value
   *  This is id of vocabulary
   */
  public function fillOgVocabularyWidgetField($field_name, $vid, $value) {
    $this->emptyField($field_name);
    $this->form_state['values'][$field_name][LANGUAGE_NONE][0][$vid] = $value;
  }

  public function fillInlineEntityFormVocab($parents, $value) {
    // $this->form_state['values'][$field_name][LANGUAGE_NONE][0][$vid] = $value;

    $ajax_post = $this->ajax_post;
    if (empty($ajax_post)) {
      $ajax_post = array();
    }
    drupal_array_set_nested_value($ajax_post, $parents, $value);
    $this->ajax_post = $ajax_post;


  }

  /**
   * This Function is for adding the Form in inline entity form
   *
   * @param $field_name
   *   This is field name
   */
  // Remove the form ID We can get using Current form
  public function addNewNode(
    $field_name,
    $ginfo,
    $triggering_element_name = FALSE,
    $field_parents = FALSE
  ) {
    $form = $this->getForm();
    if ($triggering_element_name == FALSE) {
      $triggering_element_name = $form[$field_name][LANGUAGE_NONE]['actions']['ief_add']['#name'];
    }
    $form_build_id = $form['#build_id'];
    $token = $form['form_token']['#default_value'];

    $_POST['og_group_ref'][LANGUAGE_NONE][0]['default'] = $ginfo;
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
    $_POST['form_build_id'] = $form_build_id;
    $_POST['form_token'] = $token;
    $_POST['form_id'] = $form['form_id']['#value'];
    $_POST['_triggering_element_name'] = $triggering_element_name;
    $_POST['_triggering_element_value'] = 'Add new node';
    $_POST['ajax_iframe_upload'] = 1;

    // Click on Add New Node button
    $sub_form = ajax_form_callback();
    unset($_POST);
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);

    // If More then 1 level
    if ($field_parents) {
      $element = $sub_form;
      drupal_array_set_nested_value($form, $field_parents, $element);
      // Update the Form with Latest form
      $this->form = $form;
    }
    else {
      $this->form[$field_name][LANGUAGE_NONE] = $sub_form;
    }
  }

  /**
   * This Function is for adding the form field value in ajax_post
   *
   * @param $field_name
   *  This is parent node field
   * @param $field
   *  This is child node field name
   * $param $value
   *   This is field value
   */
  // Remove the form ID We can get using Current form
  /*public function fillInlineEntityForm2($field_name, $field, $value){
      $this->ajax_post[$field_name][LANGUAGE_NONE]['form'][$field]=$value;
  }*/

  // Remove the form ID We can get using Current form
  public function fillInlineEntityForm($parents, $value) {
    $ajax_post = $this->ajax_post;
    if (empty($ajax_post)) {
      $ajax_post = array();
    }
    drupal_array_set_nested_value($ajax_post, $parents, $value);
    $this->ajax_post = $ajax_post;
  }


  public function fillInlineEntityFormFile($parents, $value) {

    $parents->alt = $value->alt;
    $parents->fid = $value->fid;
    $parents->display = $value->display;
    $parents->width = $value->width;
    $parents->height = $value->height;
    $ajax_post = $this->ajax_post;
    drupal_array_set_nested_value($ajax_post, $parents, $value);
    $this->ajax_post = $ajax_post;


  }


  public function fillInlineEntityFormImageWidgetField(
    $field_name,
    $image_paths
  ) {
    if (is_string($image_paths)) {
      $image_paths = array($image_paths);
    }
    $index = 0;
    foreach ($image_paths as $image_path) {
      $filename = drupal_basename($image_path);
      $full_image_path = 'tests/assets/' . $image_path;
      $file_temp = file_get_contents($full_image_path);
      $file_temp = file_save_data(
        $file_temp,
        'public://' . $filename,
        FILE_EXISTS_RENAME
      );
      // Set file status to temporary otherwise there is validation error.
      $file_temp->status = 0;
      file_save($file_temp);

      $image_info = image_get_info($file_temp->uri);
      $this->upload_image = array(
        'alt' => '',
        'fid' => $file_temp->fid,
        'display' => 1,
        'width' => $image_info['width'],
        'height' => $image_info['height'],
      );
      $index++;
    }

    return $this->upload_image;
  }

  /**
   * This Function is for adding the Form in inline entity form
   *
   * @param $field_name
   *   This is field name
   * @param $type
   *   This is content type name
   *
   * @return boolean
   */
  // Remove the form ID We can get using Current form
  public function createNode(
    $field_name,
    $type,
    $triggering_element_name = FALSE,
    $ief_id = FALSE
  ) {
    $form = $this->getForm();
    if ($triggering_element_name == FALSE) {
      $triggering_element_name = $form[$field_name]['und']['form']['actions']['ief_add_save']['#name'];
    }
    $form_build_id = $form['#build_id'];
    $token = $form['form_token']['#default_value'];
    $_POST = $this->ajax_post;
    $_POST = $this->ajax_post;
    $_POST['form_build_id'] = $form_build_id;
    $_POST['form_token'] = $token;
    $_POST['form_id'] = $form['form_id']['#value'];
    $_POST['_triggering_element_name'] = $triggering_element_name;
    $_POST['_triggering_element_value'] = 'Create node';
    $_POST['ajax_iframe_upload'] = 1;

    // Rest set the Validation Function
    drupal_static_reset('drupal_validate_form');
    drupal_static_reset('og_field_widget_form');
    drupal_static_reset('og_vocab_field_widget_form');

    // Click on Create Node Button
    $sub_node = ajax_form_callback();
    $errors = form_get_errors();
    if (!$errors) {
      // Get ief_id ID
      if ($ief_id == FALSE) {
        $form = $this->getForm();
        $form_field = $form[$field_name];
        $ief_id = $form_field['und']['#ief_id'];
      }

      $this->form[$field_name][LANGUAGE_NONE] = $sub_node;

      if (!empty($this->form_state['inline_entity_form'][$ief_id]['entities'])) {
        $delta = count(
          $this->form_state['inline_entity_form'][$ief_id]['entities']
        );
      }
      else {
        $delta = 0;
      }

      $entities = array();
      $entities_form = $sub_node['entities'];
      $entities['entity'] = $entities_form[$delta]['#entity'];
      $entities['needs_save'] = $entities_form[$delta]['#needs_save'];
      $entities['weight'] = $entities_form[$delta]['#weight'];
      $entities['form'] = $entities_form[$delta]['#form'];

      // Set Form State Values
      $this->form_state['inline_entity_form'][$ief_id]['form'] = '';
      $this->form_state['inline_entity_form'][$ief_id]['settings']['entity_type'] = 'node';
      $this->form_state['inline_entity_form'][$ief_id]['settings']['bundles'][0] = $type;
      $this->form_state['inline_entity_form'][$ief_id]['settings']['create_bundles'][0] = $type;
      $this->form_state['inline_entity_form'][$ief_id]['settings']['column'] = 'target_id';
      $this->form_state['inline_entity_form'][$ief_id]['entities'][] = $entities;

      return TRUE;
    }
    else {

      return FALSE;
    }
  }

  public function __call($name, $arguments) {
    if (strpos($name, 'fill') === 0) {
      // Function name starts with "get".
      $field_name = Utilities::convertTitleCaseToUnderscore(substr($name, 3));
      $field = field_info_field($field_name);
      if (is_null($field)) {
        return;
      }
    }
  }
}


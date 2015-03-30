<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 3/25/15
 * Time: 12:16 PM
 */

namespace RedTest\core\fields;

use RedTest\core\forms\Form;
use RedTest\core\Utils;

class File extends Field {

  public static function fillDefaultValues(Form $formObject, $field_name) {
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $function = 'fillDefault' . Utils::makeTitleCase(
          $instance['widget']['type']
        ) . 'Values';

      return self::$function($formObject, $field_name);
    }
  }

  /**
   * Fill generic file. Upload images.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   *
   * @return mixed
   *   A path or an array of paths of images which are to be uploaded.
   */
  public static function fillDefaultFileGenericValues(
    Form $formObject,
    $field_name
  ) {
    $num = 1;
    $file_extensions = 'txt';
    $scheme = 'public';
    $show_description = FALSE;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $file_extensions = $instance['settings']['file_extensions'];
      $scheme = $field['settings']['uri_scheme'];
      $show_description = $instance['settings']['description_field'];
    }

    $extensions = str_replace(" ", "|", $file_extensions);
    $files = file_scan_directory(
      'tests/assets',
      '/^.*\.(' . $extensions . ')$/i'
    );
    $filenames = array();
    foreach ($files as $file_name => $file_array) {
      $filenames[] = $file_array->filename;
    }

    if (!sizeof($filenames)) {
      return array(
        FALSE,
        array(),
        "Could not find any file to attach with any of the following extensions: " . $file_extensions
      );
    }

    $files = array();
    for ($i = 0; $i < $num; $i++) {
      if ($show_description) {
        $files[] = array(
          'filename' => $filenames[Utils::getRandomInt(
            0,
            sizeof($filenames) - 1
          )],
          'description' => Utils::getRandomText(20),
        );
      }
      else {
        $files[] = $filenames[Utils::getRandomInt(0, sizeof($filenames) - 1)];
      }
    }

    $files = Utils::normalize($files);

    return self::fillFileGeneric($formObject, $field_name, $files, $scheme);
  }

  /**
   * Fill generic file. Upload images.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param string|array $image_paths
   *   An image path or an array of image paths relative to tests/assets
   *   folder. The acceptable formats are:
   *   (1) "Filename.jpg"
   *   (2) array(
   *         'filename' => 'Filename.jpg",
   *         'description' => 'File description',
   *       )
   *   (3) array("Filename1.jpg", "Filename2.jpg")
   *   (4) array(
   *         array(
   *           'filename' => 'Filename1.jpg",
   *           'description' => 'File description 1',
   *         ),
   *         array(
   *           'filename' => 'Filename2.jpg",
   *           'description' => 'File description 2',
   *         ),
   *       )
   * @param string $scheme
   *   URI scheme where file needs to be stored.
   *
   * @return mixed $image_paths
   *   A path or an array of paths of images which are to be uploaded.
   */
  public static function fillFileGeneric(
    Form $formObject,
    $field_name,
    $image_paths,
    $scheme = 'public'
  ) {
    $formObject->emptyField($field_name);

    if (is_string($image_paths)) {
      $image_paths = array(array('filename' => $image_paths));
    }
    elseif (is_array($image_paths) && array_key_exists(
        'filename',
        $image_paths
      )
    ) {
      $image_paths = array($image_paths);
    }
    elseif (is_array($image_paths)) {
      $final_image_paths = array();
      foreach ($image_paths as $key => $image_path) {
        if (is_string($image_path)) {
          $final_image_paths[] = array('filename' => $image_path);
        }
      }
    }

    $index = 0;
    $input = array();
    $files = array();
    foreach ($image_paths as $image_path) {
      $filename = drupal_basename($image_path['filename']);
      $full_image_path = 'tests/assets/' . $image_path['filename'];
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
      if (!empty($image_path['description'])) {
        $input[$index]['description'] = $image_path['description'];
      }

      $triggering_element_name = $field_name . '_' . LANGUAGE_NONE . '_' . (sizeof(
            $input
          ) - 1) . '_upload_button';
      $formObject->addMore($field_name, $input, $triggering_element_name);

      /*$old_form_state_values = !empty($this->form_state['values']) ? $this->form_state['values'] : array();
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

      unset($this->form_state['values'][$button_name]);*/

      $index++;
    }

    return array(TRUE, $files, "");
  }
}
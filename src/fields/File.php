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
use RedTest\core\entities\Entity;

class File extends Field {

  /**
   * Fill generic file field with random files.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param array $options
   *   Options array.
   *
   * @return array
   *   An array with 3 values:
   *   (1) $success: Whether default values could be filled in the field.
   *   (2) $values: Values that were filled for the field.
   *   (3) $msg: Message in case there is an error. This will be empty if
   *   $success is TRUE.
   */
  public static function fillDefaultValues(
    Form $formObject,
    $field_name,
    $options = array()
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
      $filenames[] = $file_array->uri;
    }

    if (!sizeof($filenames)) {
      return array(
        FALSE,
        array(),
        "Could not find a file to attach with any of the following extensions: " . $file_extensions
      );
    }

    $files = array();
    for ($i = 0; $i < $num; $i++) {
      if ($show_description) {
        $files[] = array(
          'uri' => $filenames[Utils::getRandomInt(0, sizeof($filenames) - 1)],
          'description' => Utils::getRandomText(20),
          'scheme' => $scheme,
        );
      }
      else {
        $files[] = array(
          'uri' => $filenames[Utils::getRandomInt(0, sizeof($filenames) - 1)],
          'scheme' => $scheme,
        );
      }
    }

    $files = Utils::normalize($files);

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($files);
  }

  /**
   * Upload file.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param string|array $file_info
   *   An image path or an array of image paths relative to Drupal root
   *   folder. The acceptable formats are:
   *   (1) "tests/assets/Filename.jpg"
   *   (2) array(
   *         'uri' => 'Directory1/Filename.jpg",
   *         'description' => 'File description', // this is an optional
   *                                              // parameter,
   *         'scheme' => 'private', // this is an optional parameter
   *       )
   *   (3) array("Directory1/Filename1.jpg", "Directory2/Filename2.jpg")
   *   (4) array(
   *         array(
   *           'uri' => 'Directory1/Filename1.jpg",
   *           'description' => 'File description 1', // this is an optional
   *                                                  // parameter
   *           'scheme' => 'private', // this is an optional parameter
   *         ),
   *         array(
   *           'uri' => 'Directory2/Filename2.jpg",
   *           'description' => 'File description 2', // this is an optional
   *                                                  // parameter
   *           'scheme' => 'public', // this is an optional parameter
   *         ),
   *       )
   *
   * @return mixed $image_paths
   *   A path or an array of paths of images which are to be uploaded.
   */
  public static function fillValues(Form $formObject, $field_name, $file_info) {
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $field_class = get_called_class();

    $values = $field_class::normalizeInput($file_info);

    list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
    $short_field_class = Utils::makeTitleCase($field['type']);
    $field_class = "RedTest\\core\\fields\\" . $short_field_class;

    $original_values = $formObject->getValues($field_name);
    $original_values = !empty($original_values[LANGUAGE_NONE]) ? $original_values[LANGUAGE_NONE] : array();

    $return = array();
    $input = array();
    for ($i = 0; $i < sizeof($original_values); $i++) {
      if ($original_values[$i]['fid']) {
        list($success, $msg) = $formObject->pressButton(
          $field_name . '_' . LANGUAGE_NONE . '_0_remove_button',
          array('ajax' => TRUE)
        );
        if (!$success) {
          return array(FALSE, array(), $msg);
        }
      }
    }
    for ($i = 0; $i < sizeof($values); $i++) {
      unset($_SESSION['messages']['error']);
      $file_temp = $field_class::saveFile($values[$i]);
      if (!$file_temp) {
        $errors = $_SESSION['messages']['error'];
        $errors[] = 'Make sure that the destination directory, i.e. sites/default/files, is writable by PHP CLI.';
        $formObject->setErrors($errors);
        return array(FALSE, $return, implode(", ", $formObject->getErrors()));
      }

      $input[$i] = $field_class::createInput($file_temp, $values[$i]);
      $return[$i] = $input[$i];
      $return[$i]['uri'] = $file_temp->uri;
      $triggering_element_name = $field_class::getTriggeringElementName(
        $field_name,
        $i
      );
      $formObject->setValues($field_name, array(LANGUAGE_NONE => $input));
      list($success, $msg) = $formObject->pressButton(
        $triggering_element_name,
        array('ajax' => TRUE)
      );
      if (!$success) {
        return array(FALSE, Utils::normalize($return), $msg);
      }
    }

    return array(TRUE, Utils::normalize($return), "");
  }

  /**
   * Normalizes the input values so that they are in the acceptable input
   * format 4.
   *
   * @param string|array $file_info
   *   An image path or an array of image paths relative to Drupal root folder.
   *   The acceptable formats are:
   *   (1) "tests/assets/Filename.jpg"
   *   (2) array(
   *         'uri' => 'Directory1/Filename.jpg",
   *         'description' => 'File description', // this is an optional
   *                                              // parameter
   *         'scheme' => 'private', // this is an optional parameter
   *       )
   *   (3) array("Directory1/Filename1.jpg", "Directory2/Filename2.jpg")
   *   (4) array(
   *         array(
   *           'uri' => 'Directory1/Filename1.jpg",
   *           'description' => 'File description 1', // this is an optional
   *                                                  // parameter
   *           'scheme' => 'private', // this is an optional parameter
   *         ),
   *         array(
   *           'uri' => 'Directory2/Filename2.jpg",
   *           'description' => 'File description 2', // this is an optional
   *                                                  // parameter
   *           'scheme' => 'public', // this is an optional parameter
   *         ),
   *       )
   *
   * @return array
   *   A standardized array format: acceptable format 4 mentioned above.
   *   array(
   *     array(
   *       'uri' => 'Directory1/Filename1.jpg",
   *       'description' => 'File description 1', // this is an optional
   *                                              // parameter
   *       'scheme' => 'private', // this is an optional parameter
   *     ),
   *     array(
   *       'uri' => 'Directory2/Filename2.jpg",
   *       'description' => 'File description 2', // this is an optional
   *                                              // parameter
   *       'scheme' => 'public', // this is an optional parameter
   *     ),
   *   )
   *
   */
  protected static function normalizeInput($file_info) {
    if (is_string($file_info)) {
      // File paths are provided in the form of first acceptable format.
      $file_info = array(array('uri' => $file_info));
    }
    elseif (is_array($file_info) && array_key_exists('uri', $file_info)) {
      // File paths are provided in the form of second acceptable format.
      $file_info = array($file_info);
    }
    elseif (is_array($file_info)) {
      foreach ($file_info as $key => $image_path) {
        if (is_string($image_path)) {
          // File paths are provided in the form of third acceptable format.
          $file_info[$key] = array('uri' => $image_path);
        }
        elseif (is_array($image_path) && array_key_exists('uri', $image_path)) {
          // File paths are provided in the form of fourth acceptable format.
          $file_info[$key] = $image_path;
        }
      }
    }

    return $file_info;
  }

  /**
   * Saves the file as a temporary managed file.
   *
   * @param array $file_info
   *   An array of information about the file. It should be in the following
   *   format:
   *   array(
   *     'uri' => 'Directory1/Filename1.jpg",
   *     'name' => 'Filename1.jpg', // this is an optional parameter
   *     'description' => 'File description 1', // this is an optional
   *                                            // parameter
   *     'scheme' => 'private', // this is an optional parameter
   *   )
   *
   * @return object
   *   Saved file object.
   */
  protected static function saveFile($file_info) {
    $filename = !empty($file_info['name']) ? $file_info['name'] : drupal_basename(
      $file_info['uri']
    );
    $scheme = !empty($file_info['scheme']) ? $file_info['scheme'] : 'public';
    $file_temp = file_get_contents($file_info['uri']);
    // We add a random string in front of the file name to avoid race condition
    // in file upload with the same name in case we are running multiple
    // concurrent PHPUnit processes.
    $file_temp = file_save_data(
      $file_temp,
      $scheme . '://' . Utils::getRandomString(20) . '_' . $filename,
      FILE_EXISTS_RENAME
    );

    if (!$file_temp) {
      return FALSE;
    }

    // Set file status to temporary otherwise there is validation error.
    $file_temp->status = 0;
    file_save($file_temp);

    return $file_temp;
  }

  /**
   * Creates an input array based on file object and information.
   *
   * @param object $file
   *   File object.
   * @param $file_info
   *   File information array.
   *
   * @return array
   *   Input array that can be sent in the form POST.
   */
  protected static function createInput($file, $file_info) {
    $input = array(
      'fid' => $file->fid,
      'display' => 1,
    );

    if (!empty($file_info['description'])) {
      $input['description'] = $file_info['description'];
    }

    return $input;
  }

  /**
   * Get the name of the triggering element based on the field name and the
   * input array.
   *
   * @param string $field_name
   *   Field name.
   * @param int $index
   *   Index of the button in multivalued field.
   *
   * @return string
   *   Triggering element name.
   */
  public static function getTriggeringElementName($field_name, $index) {
    $triggering_element_name = $field_name . '_' . LANGUAGE_NONE . '_' . $index . '_upload_button';

    return $triggering_element_name;
  }

  public static function getFileGenericValues(
    Entity $entityObject,
    $field_name,
    $post_process = FALSE
  ) {
    $field = $entityObject->getFieldItems($field_name);
    if (!$post_process) {
      return $field;
    }

    $output = array();
    foreach ($field as $fid => $file) {
      $output[] = $fid;
    }

    return Utils::normalize($output);
  }

  public static function checkFileGenericValues(
    Entity $entity,
    $field_name,
    $values
  ) {
    $function = "get" . Utils::makeTitleCase($field_name) . "Values";
    $actual_values = $entity->$function();

    $field_class = get_called_class();

    return $field_class::compareFileGenericValues($actual_values, $values);
  }

  public static function compareFileGenericValues($actual_values, $values) {
    $field_class = get_called_class();
    $values = $field_class::normalizeInputForCompare($values);
    $actual_values = $field_class::normalizeInputForCompare($actual_values);

    if (sizeof($actual_values) != sizeof($values)) {
      return array(FALSE, "Number of values do not match.");
    }

    // Iterate over values and make sure that all the keys match.
    foreach ($values as $index => $value_array) {
      foreach ($value_array as $key => $value) {
        if ($actual_values[$index][$key] != $value) {
          return array(FALSE, "Key " . $key . " does not match.");
        }
      }
    }

    return array(TRUE, "");
  }

  private static function normalizeInputForCompare($values) {
    $formatted_values = array();
    if (is_numeric($values)) {
      $formatted_values[] = array('fid' => $values);
    }
    elseif (is_object($values) && property_exists($values, 'fid')) {
      $file = get_object_vars($values);
      $formatted_values[] = $file;
    }
    elseif (is_array($values)) {
      if (array_key_exists('fid', $values)) {
        $formatted_values[] = $values;
      }
      else {
        foreach ($values as $key => $value) {
          if (is_numeric($value)) {
            $formatted_values[] = array('fid' => $value);
          }
          elseif (is_object($value) && property_exists($value, 'fid')) {
            $file = get_object_vars($value);
            $formatted_values[] = $file;
          }
          elseif (is_array($value)) {
            if (array_key_exists('fid', $value)) {
              $formatted_values[] = $value;
            }
          }
        }
      }
    }

    return $formatted_values;
  }
}
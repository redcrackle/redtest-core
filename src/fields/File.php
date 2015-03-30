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

      $field_class = get_called_class();

      return $field_class::$function($formObject, $field_name);
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
   * Upload file.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param string|array $image_paths
   *   An image path or an array of image paths relative to Drupal root
   *   folder. The acceptable formats are:
   *   (1) "tests/assets/Filename.jpg"
   *   (2) array(
   *         'uri' => 'Directory1/Filename.jpg",
   *         'description' => 'File description',
   *       )
   *   (3) array("Directory1/Filename1.jpg", "Directory2/Filename2.jpg")
   *   (4) array(
   *         array(
   *           'uri' => 'Directory1/Filename1.jpg",
   *           'description' => 'File description 1',
   *         ),
   *         array(
   *           'uri' => 'Directory2/Filename2.jpg",
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

    $field_class = get_called_class();

    $image_paths = $field_class::normalizeInput($image_paths);

    $index = 0;
    $input = array();
    $output = array();
    foreach ($image_paths as $image_path) {
      $file_temp = $field_class::saveFile($image_path, $scheme);
      $input[$index] = $field_class::createInput($file_temp, $image_path);
      $output[$index] = $input[$index];
      $output[$index]['uri'] = $file_temp->uri;
      $triggering_element_name = $field_class::getTriggeringElementName(
        $field_name,
        $input
      );
      $formObject->addMore($field_name, $input, $triggering_element_name);

      $index++;
    }

    return array(TRUE, Utils::normalize($output), "");
  }

  /**
   * Normalizes the input values so that they are in the acceptable input
   * format 4.
   *
   * @param string|array $image_paths
   *   An image path or an array of image paths relative to Drupal root folder.
   *   The acceptable formats are:
   *   (1) "tests/assets/Filename.jpg"
   *   (2) array(
   *         'uri' => 'Directory1/Filename.jpg",
   *         'description' => 'File description', // this is an optional
   *                                              // parameter
   *       )
   *   (3) array("Directory1/Filename1.jpg", "Directory2/Filename2.jpg")
   *   (4) array(
   *         array(
   *           'uri' => 'Directory1/Filename1.jpg",
   *           'description' => 'File description 1', // this is an optional
   *                                                  // parameter
   *         ),
   *         array(
   *           'uri' => 'Directory2/Filename2.jpg",
   *           'description' => 'File description 2', // this is an optional
   *                                                  // parameter
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
   *     ),
   *     array(
   *       'uri' => 'Directory2/Filename2.jpg",
   *       'description' => 'File description 2', // this is an optional
   *                                              // parameter
   *     ),
   *   )
   *
   */
  protected static function normalizeInput($image_paths) {
    if (is_string($image_paths)) {
      // File paths are provided in the form of first acceptable format.
      $image_paths = array(array('uri' => $image_paths));
    }
    elseif (is_array($image_paths) && array_key_exists('uri', $image_paths)) {
      // File paths are provided in the form of second acceptable format.
      $image_paths = array($image_paths);
    }
    elseif (is_array($image_paths)) {
      foreach ($image_paths as $key => $image_path) {
        if (is_string($image_path)) {
          // File paths are provided in the form of third acceptable format.
          $image_paths[$key] = array('uri' => $image_path);
        }
      }
    }

    return $image_paths;
  }

  /**
   * Saves the file as a temporary managed file.
   *
   * @param array $image_path
   *   An array of information about the file. It should be in the following
   *   format:
   *   array(
   *     'uri' => 'Directory1/Filename1.jpg",
   *     'name' => 'Filename1.jpg', // this is an optional parameter
   *     'description' => 'File description 1', // this is an optional
   *                                            // parameter
   *   )
   * @param string $scheme
   *   URI scheme.
   *
   * @return object
   *   Saved file object.
   */
  protected static function saveFile($image_path, $scheme) {
    $filename = !empty($image_path['name']) ? $image_path['name'] : drupal_basename(
      $image_path['uri']
    );
    $file_temp = file_get_contents($image_path['uri']);
    $file_temp = file_save_data(
      $file_temp,
      $scheme . '://' . $filename,
      FILE_EXISTS_RENAME
    );
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
   * @param array $input
   *   Input array.
   *
   * @return string
   *   Triggering element name.
   */
  protected static function getTriggeringElementName($field_name, $input) {
    $triggering_element_name = $field_name . '_' . LANGUAGE_NONE . '_' . (sizeof(
          $input
        ) - 1) . '_upload_button';

    return $triggering_element_name;
  }
}
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

class Image extends Field {

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

  public static function fillDefaultImageImageValues(
    Form $formObject,
    $field_name
  ) {
    $num = 1;
    $show_title = FALSE;
    $show_alt = FALSE;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $uri_scheme = $field['settings']['uri_scheme'];
      $file_extensions = explode(' ', $instance['settings']['file_extensions']);
      $max_filesize = $instance['settings']['max_filesize'];
      $max_resolution = $instance['settings']['max_resolution'];
      $min_resolution = $instance['settings']['min_resolution'];
      $show_title = $instance['settings']['alt_field'];
      $show_alt = $instance['settings']['title_field'];
    }

    $min_width = '';
    $max_width = '';
    $min_height = '';
    $max_height = '';
    if (!empty($min_resolution)) {
      list($min_width, $min_height) = explode('x', $min_resolution);
    }
    if (!empty($max_resolution)) {
      list($max_width, $max_height) = explode('x', $max_resolution);
    }

    $files = file_scan_directory(
      'tests/assets',
      '/.*\.(' . implode('|', $file_extensions) . ')$/',
      array('recurse' => TRUE)
    );

    $valid_files = array();
    foreach ($files as $uri => $file) {
      $image_info = image_get_info($uri);

      if (!empty($max_filesize) && $image_info['file_size'] > $max_filesize) {
        continue;
      }

      if (!empty($min_width) && $image_info['width'] < $min_width) {
        continue;
      }

      if (!empty($max_width) && $image_info['width'] > $max_width) {
        continue;
      }

      if (!empty($min_height) && $image_info['height'] < $min_height) {
        continue;
      }

      if (!empty($max_height) && $image_info['height'] > $max_height) {
        continue;
      }

      $valid_files[$uri] = get_object_vars($file);
    }

    if (empty($valid_files)) {
      return array(
        FALSE,
        array(),
        'Appropriate image could not be found for ' . $field_name
      );
    }

    $files = array();
    foreach (range(0, $num - 1) as $index) {
      $files[$index] = $valid_files[array_rand($valid_files)];
      if ($show_title) {
        $files[$index]['title'] = Utils::getRandomText(20);
      }
      if ($show_alt) {
        $files[$index]['alt'] = Utils::getRandomText(20);
      }
    }

    return self::fillImageImageValues($formObject, $field_name, $files, $uri_scheme);
  }

  /**
   * Fills image field in the form with specified values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param string|array $image_paths
   *   Path to the image or an array of paths relative to Drupal root
   *   directory. Following formats are acceptable:
   *   (1) "ImageDirectory/Image.jpg"
   *   (2) array("ImageDirectory1/Image1.jpg", "ImageDirectory2/Image2.jpg")
   *   (3) array(
   *         'uri' => 'ImageDirectory/Image1.jpg',
   *         'filename' => 'Image1.jpg', // this is an optional parameter
   *         'name' => 'Image1', // this is an optional parameter
   *         'alt' => 'Alt text 1', // This is an optional parameter
   *         'title' => 'Title text 1', // This is an optional parameter
   *       )
   *   (4) array(
   *         array(
   *           'uri' => 'ImageDirectory1/Image1.jpg',
   *           'filename' => 'Image1.jpg', // this is an optional parameter
   *           'name' => 'Image1', // this is an optional parameter
   *           'alt' => 'Alt text 1', // This is an optional parameter
   *           'title' => 'Title text 1', // This is an optional parameter
   *         ),
   *         array(
   *           'uri' => 'ImageDirectory2/Image2.jpg',
   *           'filename' => 'Image2.jpg', // this is an optional parameter
   *           'name' => 'Image2', // this is an optional parameter
   *           'alt' => 'Alt text 2', // This is an optional parameter
   *           'title' => 'Title text 2', // This is an optional parameter
   *         ),
   *       )
   * @param string $scheme
   *   URI scheme for saving the image.
   *
   * @return array
   */
  public static function fillImageImageValues(
    Form $formObject,
    $field_name,
    $image_paths,
    $scheme = 'public'
  ) {
    $formObject->emptyField($field_name);

    if (is_string($image_paths)) {
      // Image paths are provided in the form of first acceptable format.
      $image_paths = array(array('uri' => $image_paths));
    }
    elseif (is_array($image_paths) && !array_key_exists('uri', $image_paths)) {
      $index = 0;
      foreach ($image_paths as $image_path) {
        if (is_string($image_path)) {
          // Image paths are provided in the form of second acceptable format.
          $image_paths[$index] = array('uri' => $image_path);
        }
        $index++;
      }
    }
    elseif (is_array($image_paths) && array_key_exists('uri', $image_paths)) {
      // Image paths are provided in the form of third acceptable format.
      $image_paths = array($image_paths);
    }

    $stored_file_uris = array();
    $index = 0;
    $input = array();
    foreach ($image_paths as $image_path) {
      $filename = !empty($image_path['name']) ? $image_path['name'] : drupal_basename(
        $image_path
      );
      //$full_image_path = 'tests/assets/' . $image_path;
      $file_temp = file_get_contents($image_path['uri']);
      $file_temp = file_save_data(
        $file_temp,
        $scheme . '://' . $filename,
        FILE_EXISTS_RENAME
      );
      // Set file status to temporary otherwise there is validation error.
      $file_temp->status = 0;
      file_save($file_temp);
      $stored_file_uris[$index] = $file_temp->uri;

      $image_info = image_get_info($file_temp->uri);
      $input[$index] = array(
        'fid' => $file_temp->fid,
        'display' => 1,
        'width' => $image_info['width'],
        'height' => $image_info['height'],
      );

      if (!empty($image_path['alt'])) {
        $input[$index]['alt'] = $image_path['alt'];
      }

      if (!empty($image_path['title'])) {
        $input[$index]['title'] = $image_path['title'];
      }

      $triggering_element_name = $field_name . '_' . LANGUAGE_NONE . '_' . (sizeof(
            $input
          ) - 1) . '_upload_button';
      $formObject->addMore($field_name, $input, $triggering_element_name);

      $index++;
    }

    //$formObject->setValues($field_name, array(LANGUAGE_NONE => $input));

    return array(TRUE, Utils::normalize($input), "");
  }
}
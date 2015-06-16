<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 3/25/15
 * Time: 12:16 PM
 */

namespace RedTest\core\fields;

use RedTest\core\forms\Form;
use RedTest\core\Response;
use RedTest\core\Utils;
use RedTest\core\entities\Entity;

class Image extends File {

  /**
   * Fill image field with random images.
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
  public static function fillRandomValues(
    Form $formObject,
    $field_name,
    $options = array()
  ) {
    $num = 1;
    $show_title = FALSE;
    $show_alt = FALSE;
    $scheme = 'public';
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $scheme = $field['settings']['uri_scheme'];
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

      if (!empty($max_filesize) && $image_info['file_size'] > parse_size(
          $max_filesize
        )
      ) {
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
      return new Response(
        FALSE,
        array(),
        "Appropriate image could not be found for $field_name."
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
      $files[$index]['scheme'] = $scheme;
    }

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

    return $formObject->$function($files);
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
   *         'scheme' => 'private', // this is an optional parameter
   *       )
   *   (4) array(
   *         array(
   *           'uri' => 'ImageDirectory1/Image1.jpg',
   *           'filename' => 'Image1.jpg', // this is an optional parameter
   *           'name' => 'Image1', // this is an optional parameter
   *           'alt' => 'Alt text 1', // This is an optional parameter
   *           'title' => 'Title text 1', // This is an optional parameter
   *           'scheme' => 'private', // this is an optional parameter
   *         ),
   *         array(
   *           'uri' => 'ImageDirectory2/Image2.jpg',
   *           'filename' => 'Image2.jpg', // this is an optional parameter
   *           'name' => 'Image2', // this is an optional parameter
   *           'alt' => 'Alt text 2', // This is an optional parameter
   *           'title' => 'Title text 2', // This is an optional parameter
   *           'scheme' => 'public', // this is an optional parameter
   *         ),
   *       )
   *
   * @return array
   */
  public static function fillImageImageValues(
    Form $formObject,
    $field_name,
    $image_paths
  ) {
    return parent::fillValues($formObject, $field_name, $image_paths);
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
    $image_info = image_get_info($file->uri);
    $input = array(
      'fid' => $file->fid,
      'width' => $image_info['width'],
      'height' => $image_info['height'],
    );
    if (!empty($file_info['alt'])) {
      $input['alt'] = $file_info['alt'];
    }

    if (!empty($file_info['title'])) {
      $input['title'] = $file_info['title'];
    }

    return $input;
  }

  public static function getImageImageValues(
    Entity $entityObject,
    $field_name,
    $post_process = FALSE
  ) {
    return parent::getFileGenericValues(
      $entityObject,
      $field_name,
      $post_process
    );
  }

  public static function checkImageImageValues(
    Entity $entity,
    $field_name,
    $values
  ) {
    $function = "get" . Utils::makeTitleCase($field_name) . "Values";
    $actual_values = $entity->$function();

    $field_class = get_called_class();

    return $field_class::compareImageImageValues($actual_values, $values);
  }

  public static function compareImageImageValues($actual_values, $values) {
    return parent::compareFileGenericValues($actual_values, $values);
  }
}
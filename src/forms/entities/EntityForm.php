<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 11/15/14
 * Time: 11:44 PM
 */

namespace RedTest\core\forms\entities;

use RedTest\core\forms\Form;
use RedTest\core\Utils;
use RedTest\core\fields\Field;

abstract class EntityForm extends Form {

  /**
   * @var Entity
   */
  private $entityObject;

  public function getEntityObject() {
    return $this->entityObject;
  }

  public function setEntityObject($entityObject) {
    $this->entityObject = $entityObject;
  }

  public function fillDefaultTaxonomyShsValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $vocabularyClassName = $this->getVocabularyClassNameFromVocabularyName(
      $field['settings']['allowed_values'][0]['vocabulary']
    );

    list($success, $termObjects, $msg) = $vocabularyClassName::createDefault(
      $num
    );
    if (!$success) {
      return array(FALSE, $termObjects, $msg);
    }

    $this->fillTaxonomyShs($field_name, Utils::getId($termObjects));

    return array(TRUE, $termObjects, "");
  }

  /**
   * Fill generic file. Upload images.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $image_paths
   *   A path or an array of paths of images which are to be uploaded.
   */
  public function fillDefaultFileGenericValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    $extensions = str_replace(
      " ",
      "|",
      $instance['settings']['file_extensions']
    );
    $files = file_scan_directory(
      'tests/assets',
      '/^.*\.(' . $extensions . ')$/i'
    );
    $filenames = array();
    foreach ($files as $file_name => $file_array) {
      $filenames[] = $file_array->filename;
    }

    if (!sizeof($filenames)) {
      return array(FALSE, array(), "Could not attach a file.");
    }

    $files = array();
    for ($i = 0; $i < $num; $i++) {
      $files[] = $filenames[Utils::getRandomInt(0, sizeof($filenames) - 1)];
    }
    $values = $this->fillFileGeneric(
      $field_name,
      $files,
      $field['settings']['uri_scheme']
    );

    return array(TRUE, $values, "");
  }

  public function fillDefaultOptionsSelectValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    if ($field['type'] == 'taxonomy_term_reference') {
      $vocabularyClassName = $this->getVocabularyClassNameFromVocabularyName(
        $field['settings']['allowed_values'][0]['vocabulary']
      );

      list($success, $termObjects, $msg) = $vocabularyClassName::createDefault(
        $num
      );
      if (!$success) {
        return array(FALSE, $termObjects, $msg);
      }

      $this->fillOptionsSelect($field_name, Utils::getId($termObjects));

      return array(TRUE, $termObjects, "");
    }
  }

  public function fillDefaultNumberValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[] = Utils::getRandomInt(-255, 255);
    }

    $this->fillNumber($field_name, $values);

    if (sizeof($values) == 1) {
      $values = $values[0];
    }

    return array(TRUE, $values, "");
  }

  public function fillDefaultDatePopupValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $date_format = $instance['widget']['settings']['input_format'];
    $date_format_custom = $instance['widget']['settings']['input_format_custom'];
    $year_range = $instance['widget']['settings']['year_range'];
    $increment = $instance['widget']['settings']['increment'];
    $granularity = $field['settings']['granularity'];
    $timezone = $field['settings']['tz_handling'];

    // @todo Generate default date based on parameters.
    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[] = Utils::getRandomDate(
        $date_format,
        "1/1/2000"
      );
    }

    //$this->fillDatePopup($field_name, $values);

    if (sizeof($values) == 1) {
      $values = $values[0];
    }

    return array(TRUE, $values, "");
  }

  public function fillDefaultOptionsOnoffValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    // @todo We don't know yet what to do with multivalued boolean values.
    $value = Utils::getRandomInt(0, 1);
    $this->fillOptionsOnoff($field_name, $value);

    return array(TRUE, $value, "");
  }

  public function fillDefaultTaxonomyAutocompleteValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $vocabularyClassName = $this->getVocabularyClassNameFromVocabularyName(
      $field['settings']['allowed_values'][0]['vocabulary']
    );

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      if (Utils::getRandomInt(0, 1)) {
        $values[] = Utils::getRandomString();
      }
      else {
        list($success, $termObject, $msg) = $vocabularyClassName::createDefault(
        );
        if (!$success) {
          return array(FALSE, $values, $msg);
        }

        $values[] = $termObject->getLabel();
      }
    }

    $this->fillTaxonomyAutocomplete($field_name, $values);

    if (sizeof($values) == 1) {
      $values = $values[0];
    }

    return array(TRUE, $values, "");
  }

  public function fillDefaultEntityreferenceViewWidgetValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $view = $instance['widget']['settings']['view'];
    list($view_name, $display_name) = explode("|", $view);
    $result = views_get_view_result($view_name, $display_name);
    $rows = array();
    foreach ($result as $row) {
      $data = $row->_field_data;
      foreach ($data as $key => $val) {
        $rows[] = $val['entity']->$key;
      }
    }
    shuffle($rows);
    $values = array_slice($rows, 0, $num);

    $this->fillEntityreferenceViewWidget($field_name, $values);

    if (sizeof($values) == 1) {
      $values = $values[0];
    }

    return array(TRUE, $values, "");
  }

  public function fillDefaultTermReferenceTreeValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    return array(TRUE, array(), "");
  }

  public function fillDefaultImageImageValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $uri_scheme = $field['settings']['uri_scheme'];
    $file_extensions = explode(' ', $instance['settings']['file_extensions']);
    $max_filesize = $instance['settings']['max_filesize'];
    $max_resolution = $instance['settings']['max_resolution'];
    $min_resolution = $instance['settings']['min_resolution'];
    list($min_width, $min_height) = explode('x', $min_resolution);
    list($max_width, $max_height) = explode('x', $max_resolution);

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

      $valid_files[$uri] = $file;
    }

    if (empty($valid_files)) {
      return array(
        FALSE,
        array(),
        'Appropriate image could not be found for ' . $field_name
      );
    }

    $stored_file_uris = $this->fillImageImage(
      $field_name,
      array_keys($valid_files),
      $uri_scheme
    );

    return array(TRUE, $stored_file_uris, "");
  }

  public function fillDefaultTextTextareaValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $values[] = Utils::getRandomString(100);
    }

    $this->fillTextTextarea($field_name, $values);

    if (sizeof($values) == 1) {
      $values = $values[0];
    }

    return array(TRUE, $values, "");
  }

  public function fillDefaultTextTextfieldValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      $length = Utils::getRandomInt(1, $field['settings']['max_length']);
      $values[] = Utils::getRandomString($length);
    }

    $this->fillTextTextfield($field_name, $values);

    if (sizeof($values) == 1) {
      $values = $values[0];
    }

    return array(TRUE, $values, "");
  }

  public function fillDefaultAutocompleteDeluxeTaxonomyValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);

    $vocabularyClassName = $this->getVocabularyClassNameFromVocabularyName(
      $field['settings']['allowed_values'][0]['vocabulary']
    );

    $values = array();
    for ($i = 0; $i < $num; $i++) {
      if (Utils::getRandomInt(0, 1)) {
        $values[] = Utils::getRandomString();
      }
      else {
        list($success, $termObject, $msg) = $vocabularyClassName::createDefault(
        );
        if (!$success) {
          return array(FALSE, $values, $msg);
        }

        $values[] = $termObject->getLabel();
      }
    }

    $this->fillAutocompleteDeluxeTaxonomy($field_name, $values);

    return array(TRUE, $values, "");
  }

  public function __call($name, $arguments) {
    if (strpos($name, 'fillDefault') === 0 && strrpos(
        $name,
        'Values'
      ) == strlen($name) - 6
    ) {
      // Function name starts with "fillDefault" and ends with "Values".
      $field_name = Utils::makeSnakeCase(
        substr($name, 11, -6)
      );

      return $this->fillDefaultFieldValues($field_name);
    }
    elseif (strpos($name, 'fill') === 0) {
      // Function name starts with "fill".
      $field_name = Utils::makeSnakeCase(substr($name, 4));

      return $this->fillFieldValues($field_name, $arguments);
    }
  }

  public function fillFieldValues($field_name, $values) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    $is_property = FALSE;
    if (is_null($field)) {
      $is_property = TRUE;
    }
    else {
      if (is_null($instance)) {
        $is_property = TRUE;
      }
      else {
        return Field::fillValues($this, $field_name, $values);
      }
    }

    if ($is_property) {
      if (is_array($values)) {
        $values = $values[0];
      }
      $this->fillValues(array($field_name => $values));

      return array(TRUE, $values, "");
    }
  }

  public function fillDefaultFieldValues($field_name) {
    list($field, $instance, $num) = $this->getFieldDetails($field_name);
    $is_property = FALSE;
    if (is_null($field)) {
      $is_property = TRUE;
    }
    else {
      if (is_null($instance)) {
        $is_property = TRUE;
      }
      else {
        return Field::fillDefaultValues($this, $field_name);
      }
    }

    if ($is_property) {
      $function = "fillDefault" . Utils::makeTitleCase(
          $field_name
        ) . "Values";

      return $this->$function();
    }
  }

  public function fillDefaultValuesExcept($skip = array()) {
    // First get all field instances.
    $field_instances = $this->entityObject->getFieldInstances();

    // Iterate over all the field instances and unless they are in $skip array, fill default values for them.
    $fields = array();
    foreach ($field_instances as $field_name => $field_instance) {
      if (!in_array($field_name, $skip)) {
        $function = "fillDefault" . Utils::makeTitleCase(
            $field_name
          ) . "Values";
        list($success, $values, $msg) = $this->$function();
        $fields[$field_name] = $values;
        if (!$success) {
          return array(FALSE, $fields, $msg);
        }
      }
    }

    return array(TRUE, $fields, "");
  }

  /**
   * @param $cardinality
   *
   * @return int
   */
  private function getNumberOfItemsFromCardinality($cardinality) {
    if ($cardinality == -1) {
      $num = Utils::getRandomInt(2, 5);

      return $num;
    }
    elseif ($cardinality == 1) {
      $num = 1;

      return $num;
    }
    else {
      $num = Utils::getRandomInt(2, $cardinality);

      return $num;
    }
  }

  /**
   * @param $vocabulary
   *
   * @return mixed|string
   */
  private function getVocabularyClassNameFromVocabularyName($vocabulary) {
    $vocabularyClassName = Utils::makeTitleCase(
      str_replace(" ", "_", $vocabulary)
    );
    $base_path = "tests\\phpunit_tests\\custom\\entities\\taxonomy_term\\";
    $vocabularyClassName = $base_path . $vocabularyClassName;

    return $vocabularyClassName;
  }

  /**
   * @param $field_name
   *
   * @return mixed
   * @throws \EntityMalformedException
   */
  private function getFieldInstance($field_name) {
    list(, , $bundle) = entity_extract_ids(
      $this->entityObject->getEntityType(),
      $this->entityObject->getEntity()
    );
    $instance = field_info_instance(
      $this->entityObject->getEntityType(),
      $field_name,
      $bundle
    );

    return $instance;
  }

  /**
   * @param $field_name
   */
  private function getFieldInfo($field_name) {
    return field_info_field($field_name);
  }

  /**
   * @param $field_name
   *
   * @return array
   */
  public function getFieldDetails($field_name) {
    $instance = NULL;
    $num = 0;
    $field = $this->getFieldInfo($field_name);
    if (!is_null($field)) {
      $instance = $this->getFieldInstance($field_name);
      $num = $this->getNumberOfItemsFromCardinality($field['cardinality']);
    }

    return array($field, $instance, $num);
  }
}

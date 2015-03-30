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

class TaxonomyTermReference extends Field {

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
  public static function fillDefaultTaxonomyAutocompleteValues(
    Form $formObject,
    $field_name
  ) {
    $num = 1;
    $vocabulary = '';
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    // Create new taxonomy terms in the specified vocabulary.
    $vocabulary_class = Utils::makeTitleCase($vocabulary);
    /**
     * @todo Remove the require statement once autoload is working.
     */
    require_once 'tests/RedTest/entities/TaxonomyTerm/Tags.php';
    $terms = $vocabulary_class::createDefault($num);

    return self::fillTaxonomyAutocompleteValues($formObject, $field_name, $terms);
  }
}
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

class Entityreference extends Field {

  /**
   * Fill autocomplete taxonomy term reference fields with random values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param array $options
   *   Options array.
   *
   * @return mixed
   *   A path or an array of paths of images which are to be uploaded.
   */
  public static function fillTaxonomyAutocompleteRandomValues(
    Form $formObject,
    $field_name,
    $options = array()
  ) {
    $num = 1;
    $vocabulary = '';
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    // Create new taxonomy terms in the specified vocabulary.
    $vocabulary_class = "RedTest\\entities\\TaxonomyTerm\\" . Utils::makeTitleCase(
        $vocabulary
      );
    $terms = $vocabulary_class::createRandom($num);

    return self::fillTaxonomyAutocompleteValues(
      $formObject,
      $field_name,
      $terms
    );
  }

  public static function fillOptionsButtonsRandomValues(
    Form $formObject,
    $field_name
  ) {
    $num = 1;
    $target_entity_type = 'node';
    $target_bundles = array();
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $target_entity_type = $field['settings']['target_type'];
      $target_bundles = $field['settings']['handler_settings']['target_bundles'];
    }

    $objects = array();
    for ($i = 0; $i < $num; $i++) {
      if ($target_entity_type == 'user' && empty($target_bundles)) {
        $class = "RedTest\\src\\entities\\User";
      }
      elseif ($target_entity_type == 'node' && empty($target_bundles)) {
        // Get all the content types here and load them in $target_bundles.
      }
      elseif ($target_entity_type == 'node') {
        $target_bundle = array_rand($target_bundles);
        $class = "RedTest\\entities\\Node\\" . Utils::makeTitleCase(
            $target_bundle
          );
      }
      list($success, $object, $msg) = $class::createRandom();
      if (!$success) {
        return new Response(
          FALSE,
          $objects,
          "Could not create " . $target_entity_type . " of bundle " . $target_bundle . " for the field " . $field_name
        );
      }

      $objects[] = $object;
    }

    return self::fillOptionsButtonsValues($formObject, $field_name, $objects);
  }

  /**
   * Fills taxonomy term reference checkboxes field of a form with provided
   * values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param int|object|array $values
   *   An integer taxonomy term id, a term object or an array of tids or term
   *   objects. Here are the acceptable formats:
   *   (a) 23
   *   (b) array(
   *         'tid' => 23,
   *       )
   *   (c) (object) array(
   *         'tid' => 23,
   *       )
   *   (d) (Tag) array(
   *         'entity' => Entity object,
   *         'entity_term' => 'taxonomy_term',
   *       )
   *   (e) array(23, 3)
   *   (f) array(
   *         array(
   *           'tid' => 23,
   *         ),
   *         array(
   *           'tid' => 3,
   *         ),
   *       )
   *   (g) array(
   *         (object) array(
   *           'tid' => 23,
   *         ),
   *         (object) array(
   *           'tid' => 3,
   *         ),
   *       )
   *   (h) array(
   *         (Tag) array(
   *           'entity' => Entity object,
   *           'entity_term' => 'taxonomy_term',
   *         ),
   *         (Tag) array(
   *           'entity' => Entity object,
   *           'entity_term' => 'taxonomy_term',
   *         ),
   *       )
   *
   * @return array
   */
  public static function fillOptionsButtonsValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    if (!Field::hasFieldAccess($formObject, $field_name)) {
      if (is_array($field_name)) {
        $field_name = array_pop($field_name);
      }
      return new Response(FALSE, "", "Field $field_name is not accessible.");
    }

    $vocabulary = '';
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    $tids = array();
    if (is_object($values)) {
      $parent_class = get_parent_class($values);
      if ($parent_class == "RedTest\\core\\entities\\TaxonomyTerm") {
        $tids = array(Utils::getId($values));
      }
      else {
        $tids = array($values->tid);
      }
    }
    elseif (is_array($values)) {
      if (array_key_exists('tid', $values)) {
        $tids = array($values['tid']);
      }
      foreach ($values as $key => $value) {
        if (is_numeric($value)) {
          $tids[] = $value;
        }
        elseif (is_object($value)) {
          $parent_class = get_parent_class($value);
          if ($parent_class == "RedTest\\core\\entities\\TaxonomyTerm") {
            $tids[] = Utils::getId($value);
          }
          else {
            $tids[] = $value->tid;
          }
        }
        elseif (is_array($value)) {
          $tids[] = $value['tid'];
        }
      }
    }

    //$terms = taxonomy_term_load_multiple($tids);
    $termObjects = array();
    foreach ($tids as $tid) {
      //$vocabulary = $terms[$tid]->vocabulary_machine_name;
      $term_class = "RedTest\\entities\\TaxonomyTerm\\" . Utils::makeTitleCase(
          $vocabulary
        );
      $termObjects[] = new $term_class($tid);
    }

    return $formObject->fillValues(
      $field_name,
      array(LANGUAGE_NONE => drupal_map_assoc($tids))
    );
  }

  public static function fillOptionsSelectRandomValues(
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
    $vocabulary_class = "RedTest\\entities\\TaxonomyTerm\\" . Utils::makeTitleCase(
        $vocabulary
      );
    $response = $vocabulary_class::createRandom($num);
    if (!$response->getSuccess()) {
      return $response;
    }

    return self::fillOptionsSelectValues(
      $formObject,
      $field_name,
      $response->getVar()
    );
  }

  /**
   * Fills taxonomy term reference checkboxes field of a form with provided
   * values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param int|object|array $values
   *   An integer taxonomy term id, a term object or an array of tids or term
   *   objects. Here are the acceptable formats:
   *   (a) 23
   *   (b) array(
   *         'tid' => 23,
   *       )
   *   (c) (object) array(
   *         'tid' => 23,
   *       )
   *   (d) (Tag) array(
   *         'entity' => Entity object,
   *         'entity_term' => 'taxonomy_term',
   *       )
   *   (e) array(23, 3)
   *   (f) array(
   *         array(
   *           'tid' => 23,
   *         ),
   *         array(
   *           'tid' => 3,
   *         ),
   *       )
   *   (g) array(
   *         (object) array(
   *           'tid' => 23,
   *         ),
   *         (object) array(
   *           'tid' => 3,
   *         ),
   *       )
   *   (h) array(
   *         (Tag) array(
   *           'entity' => Entity object,
   *           'entity_term' => 'taxonomy_term',
   *         ),
   *         (Tag) array(
   *           'entity' => Entity object,
   *           'entity_term' => 'taxonomy_term',
   *         ),
   *       )
   *
   * @return array
   */
  public static function fillOptionsSelectValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    if (!Field::hasFieldAccess($formObject, $field_name)) {
      return new Response(
        FALSE,
        "",
        "Field " . Utils::getLeaf($field_name) . " is not accessible."
      );
    }

    $vocabulary = '';
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    $tids = array();
    if (is_object($values)) {
      $parent_class = get_parent_class($values);
      if ($parent_class == "RedTest\\core\\entities\\TaxonomyTerm") {
        $tids = array(Utils::getId($values));
      }
      else {
        $tids = array($values->tid);
      }
    }
    elseif (is_array($values)) {
      if (array_key_exists('tid', $values)) {
        $tids = array($values['tid']);
      }
      foreach ($values as $key => $value) {
        if (is_numeric($value)) {
          $tids[] = $value;
        }
        elseif (is_object($value)) {
          $parent_class = get_parent_class($value);
          if ($parent_class == "RedTest\\core\\entities\\TaxonomyTerm") {
            $tids[] = Utils::getId($value);
          }
          else {
            $tids[] = $value->tid;
          }
        }
        elseif (is_array($value)) {
          $tids[] = $value['tid'];
        }
      }
    }

    $termObjects = array();
    foreach ($tids as $tid) {
      $term_class = "RedTest\\entities\\TaxonomyTerm\\" . Utils::makeTitleCase(
          $vocabulary
        );
      $termObjects[] = new $term_class($tid);
    }

    return $formObject->fillValues($field_name, array(LANGUAGE_NONE => $tids));
  }
}
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
use RedTest\entities\TaxonomyTerm;

class TaxonomyTermReference extends Field {

  public static function fillDefaultValues(Form $formObject, $field_name) {
    $num = 1;
    $vocabulary = '';
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    // Create new taxonomy terms in the specified vocabulary.
    $vocabulary_class = Utils::makeTitleCase($vocabulary);
    $vocabulary_class = "RedTest\\entities\\TaxonomyTerm\\" . $vocabulary_class;
    list($success, $termObjects, $msg) = $vocabulary_class::createDefault($num);
    if (!$success) {
      return array(
        FALSE,
        $termObjects,
        "Could not create taxonomy terms for the field " . $field_name . ": " . $msg
      );
    }

    $function = "fill" . Utils::makeTitleCase($field_name);

    return $formObject->$function($termObjects);
  }

  /**
   * Fill taxonomy autocomplete values.
   *
   * @param Form $formObject
   *   Form object.
   * @param string $field_name
   *   Field name.
   * @param array $values
   *   An array of values.
   *
   * @return mixed
   *   A path or an array of paths of images which are to be uploaded.
   */
  public static function fillTaxonomyAutocompleteValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $tids = self::convertValues($values);
    $term_names = self::convertValues($values, TRUE);
    if (is_array($term_names)) {
      $term_names = implode(",", $term_names);
    }
    $formObject->setValues($field_name, array(LANGUAGE_NONE => $term_names));
    $termObjects = self::createTermObjectsFromTids($tids);

    return array(TRUE, $termObjects, "");
  }

  public static function fillOptionsButtonsValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $tids = self::convertValues($values);
    $formObject->setValues(
      $field_name,
      array(LANGUAGE_NONE => drupal_map_assoc($tids))
    );
    $termObjects = self::createTermObjectsFromTids($tids);

    return array(TRUE, $termObjects, "");
  }

  public static function fillOptionsSelectValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $tids = self::convertValues($values);
    $formObject->setValues($field_name, array(LANGUAGE_NONE => $tids));
    $termObjects = self::createTermObjectsFromTids($tids);

    return array(TRUE, $termObjects, "");
  }

  /**
   * @param $values
   *   An integer taxonomy term id, a term object or an array of tids or term
   *   objects. Here are the acceptable formats:
   *   (a) 23
   *   (b) Term 1
   *   (c) array(
   *         'tid' => 23,
   *       )
   *   (d) array(
   *         'name' => 'Term1',
   *       )
   *   (e) array(
   *         'name' => 'Term 1',
   *         'vocabulary' => 'vocabulary_machine_name_1',
   *       )
   *   (f) (object) array(
   *         'tid' => 23,
   *       )
   *   (g) (object) array(
   *         'name' => 'Term 1',
   *       )
   *   (h) (object) array(
   *         'name' => 'Term 1',
   *         'vocabulary' => 'vocabulary_machine_name_1',
   *       )
   *   (i) (Tag) array(
   *         'entity' => Entity object,
   *         'entity_term' => 'taxonomy_term',
   *       )
   *   (j) array(23, 3)
   *   (k) array('Term 1', 'Term 2')
   *   (l) array(
   *         array(
   *           'tid' => 23,
   *         ),
   *         array(
   *           'tid' => 3,
   *         ),
   *       )
   *   (m) array(
   *         array(
   *           'name' => 'Term 1',
   *         ),
   *         array(
   *           'name' => 'Term 2',
   *         ),
   *       )
   *   (n) array(
   *         array(
   *           'name' => 'Term 1',
   *           'vocabulary' => 'vocabulary_machine_name_1',
   *         ),
   *         array(
   *           'name' => 'Term 2',
   *           'vocabulary' => 'vocabulary_machine_name_2',
   *         ),
   *       )
   *   (o) array(
   *         (object) array(
   *           'tid' => 23,
   *         ),
   *         (object) array(
   *           'tid' => 3,
   *         ),
   *       )
   *   (p) array(
   *         (object) array(
   *           'name' => 'Term 1',
   *         ),
   *         (object) array(
   *           'name' => 'Term 2',
   *         ),
   *       )
   *   (q) array(
   *         (object) array(
   *           'name' => 'Term 1',
   *           'vocabulary' => 'vocabulary_machine_name_1',
   *         ),
   *         (object) array(
   *           'name' => 'Term 2',
   *           'vocabulary' => 'vocabulary_machine_name_2',
   *         ),
   *       )
   *   (r) array(
   *         (Tag) array(
   *           'entity' => Entity object,
   *           'entity_term' => 'taxonomy_term',
   *         ),
   *         (Tag) array(
   *           'entity' => Entity object,
   *           'entity_term' => 'taxonomy_term',
   *         ),
   *       )   *
   *
   * @return array
   */
  private static function convertValues($values, $return_name = FALSE) {
    $tids = array();
    $names = array();
    if (is_object($values)) {
      $parent_class = get_parent_class($values);
      if ($parent_class == "RedTest\\core\\entities\\TaxonomyTerm") {
        // $values follows acceptable format (i).
        if ($return_name) {
          $names = array(Utils::getLabel($values));
        }
        else {
          $tids = array(Utils::getId($values));
        }
      }
      elseif (property_exists($values, 'tid')) {
        // $values follows acceptable format (f).
        if ($return_name) {
          $term = taxonomy_term_load($values->tid);
          $names = array($term->name);
        }
        else {
          $tids = array($values->tid);
        }
      }
      elseif (property_exists($values, 'name')) {
        $terms = array();
        if (property_exists($values, 'vocabulary')) {
          // $values follows acceptable input format (h).
          $terms = taxonomy_get_term_by_name(
            $values->name,
            $values->vocabulary
          );
        }
        else {
          // $values follows acceptable input format (g).
          $terms = taxonomy_get_term_by_name($values->name);
        }
        if (sizeof($terms)) {
          $term = array_shift($terms);
          if ($return_name) {
            $names = array($term->name);
          }
          else {
            $tids = array($term->tid);
          }
        }
      }
    }
    elseif (is_array($values)) {
      if (array_key_exists('tid', $values)) {
        // $values follows acceptable input format (c).
        if ($return_name) {
          $term = taxonomy_term_load($values['tid']);
          $names = array($term->name);
        }
        else {
          $tids = array($values['tid']);
        }
      }
      elseif (array_key_exists('name', $values)) {
        $terms = array();
        if (array_key_exists('vocabulary', $values)) {
          // $values follows acceptable input format (e).
          $terms = taxonomy_get_term_by_name(
            $values['name'],
            $values['vocabulary']
          );
        }
        else {
          // $values follows acceptable input format (d).
          $terms = taxonomy_get_term_by_name($values['name']);
        }
        if (sizeof($terms)) {
          $term = array_shift($terms);
          if ($return_name) {
            $names = array($term->name);
          }
          else {
            $tids = array($term->tid);
          }
        }
      }
      foreach ($values as $key => $value) {
        if (is_numeric($value)) {
          if ($term = taxonomy_term_load($value)) {
            // $values follows acceptable input format (j).
            if ($return_name) {
              $names[] = $term->name;
            }
            else {
              $tids[] = $term->tid;
            }
            continue;
          }
        }

        if (is_string($value)) {
          // $values follows acceptable input format (k).
          $terms = taxonomy_get_term_by_name($value);
          if (sizeof($terms)) {
            $term = array_shift($terms);
            if ($return_name) {
              $tids[] = $term->name;
            }
            else {
              $tids[] = $term->tid;
            }
          }
        }
        elseif (is_object($value)) {
          $parent_class = get_parent_class($value);
          if ($parent_class == "RedTest\\core\\entities\\TaxonomyTerm") {
            // $values follows acceptable format (r).
            if ($return_name) {
              $names[] = Utils::getLabel($value);
            }
            else {
              $tids[] = Utils::getId($value);
            }
          }
          elseif (property_exists($value, 'tid')) {
            // $values follows acceptable format (o).
            if ($return_name) {
              $term = taxonomy_term_load($values['tid']);
              $names[] = $term->name;
            }
            else {
              $tids[] = $value->tid;
            }
          }
          elseif (property_exists($value, 'name')) {
            $terms = array();
            if (property_exists($value, 'vocabulary')) {
              // $values follows acceptable input format (q).
              $terms = taxonomy_get_term_by_name(
                $value->name,
                $value->vocabulary
              );
            }
            else {
              // $values follows acceptable format (p).
              $terms = taxonomy_get_term_by_name($value->name);
            }
            if (sizeof($terms)) {
              $term = array_shift($terms);
              if ($return_name) {
                $names[] = $term->name;
              }
              else {
                $tids[] = $term->tid;
              }
            }
          }
        }
        elseif (is_array($value)) {
          if (array_key_exists('tid', $value)) {
            // $values follows acceptable format (l).
            if ($return_name) {
              $names[] = $value['name'];
            }
            else {
              $tids[] = $value['tid'];
            }
          }
          elseif (array_key_exists('name', $value)) {
            $terms = array();
            if (array_key_exists('vocabulary', $value)) {
              // $values follows acceptable format (n).
              $terms = taxonomy_get_term_by_name(
                $value['name'],
                $value['vocabulary']
              );
            }
            else {
              // $values follows acceptable format (m).
              $terms = taxonomy_get_term_by_name($value['name']);
            }
            if (sizeof($terms)) {
              $term = array_shift($terms);
              if ($return_name) {
                $names[] = $term->name;
              }
              else {
                $tids[] = $term->tid;
              }
            }
          }
        }
      }
    }

    return $return_name ? $names : $tids;
  }

  /**
   * @param $tids
   *
   * @return array
   */
  private static function createTermObjectsFromTids($tids) {
    $terms = taxonomy_term_load_multiple($tids);

    $termObjects = array();
    foreach ($tids as $tid) {
      $vocabulary = $terms[$tid]->vocabulary_machine_name;
      $term_class = "RedTest\\entities\\TaxonomyTerm\\" . Utils::makeTitleCase(
          $vocabulary
        );
      $termObjects[] = new $term_class($tid);
    }

    return $termObjects;
  }
}
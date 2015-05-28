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
use RedTest\core\entities\TaxonomyTerm;

class TaxonomyTermReference extends Field {

  public static function fillDefaultValues(
    Form $formObject,
    $field_name,
    $options
  ) {
    $num = 1;
    //$vocabulary = '';
    $widget_type = '';
    $references = array();
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
      $widget_type = $instance['widget']['type'];

      if (isset($options['references']['taxonomy_terms'])) {
        foreach ($options['references']['taxonomy_terms'] as $term) {
          if ($termObject = static::isTermObject($term, $vocabulary)) {
            $references[] = $termObject;
          }
        }
      }
      $num = min($num, sizeof($references));
      shuffle($references);
      $references = array_slice($references, 0, $num);
    }

    // Create new taxonomy terms in the specified vocabulary.
    /*$vocabulary_class = Utils::makeTitleCase($vocabulary);
    $vocabulary_class = "RedTest\\entities\\TaxonomyTerm\\" . $vocabulary_class;*/

    $termObjects = array();

    for ($i = 0; $i < $num; $i++) {
      if ($widget_type == 'taxonomy_autocomplete') {
        if (sizeof($references)) {
          // Use taxonomy terms that are provided.
          $termObjects[] = Utils::getLabel($references[$i]);
        }
        else {
          // Instead of creating a new term, we just pass its name so that
          // Drupal creates a new one automatically.
          $termObjects[] = Utils::getRandomText(20);
        }
      }
      elseif (sizeof($references)) {
        // For select lists and radio buttons, we can not create a new term
        // here. The reason is that if the form has an AJAX-based Add More
        // button, then it will be cached. So all the options in the select or
        // checkbox/radio list will be the original options even though new
        // taxonomy terms are created later. It's like creating a new taxonomy
        // term after a form with taxonomy term is already opened in another
        // tab.
        /*list($success, $termObject, $msg) = $vocabulary_class::createDefault();
        if (!$success) {
          return array(
            FALSE,
            $termObjects,
            "Could not create taxonomy terms for the field " . $field_name . ": " . $msg
          );
        }*/
        $termObjects[] = Utils::getLabel($references[$i]);
      }
      else {
        // $references is an empty array.
        return array(
          FALSE,
          NULL,
          "Could not find any existing taxonomy term that can be referenced by $field_name."
        );
      }
    }

    $function = "fill" . Utils::makeTitleCase($field_name) . "Values";

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
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $formObject->emptyField($field_name);

    $vocabulary = NULL;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    $field_class = get_called_class();
    $names = $field_class::convertValues($values, $vocabulary, TRUE, FALSE);
    $field_value = is_array($names) ? implode(",", $names) : $names;
    $formObject->setValues($field_name, array(LANGUAGE_NONE => $field_value));

    $termObjects = TaxonomyTerm::createTermObjectsFromNames(
      $names,
      $vocabulary,
      FALSE
    );

    return array(TRUE, Utils::normalize($termObjects), "");
  }

  public static function checkValues(
    Entity $entityObject,
    $field_name,
    $values
  ) {
    $function = "get" . Utils::makeTitleCase($field_name) . "Values";
    $actual_values = $entityObject->$function();

    $field_class = get_called_class();
    return $field_class::compareValues($actual_values, $values);
  }

  public static function getValues(
    Entity $entityObject,
    $field_name,
    $post_process = FALSE
  ) {
    $field = $entityObject->getFieldItems($field_name);

    return $field;
  }

  public static function compareValues($actual_values, $values) {
    if (!$actual_values && !$values) {
      // both values are empty or FALSE.
      return array(TRUE, "");
    }

    $field_class = get_called_class();
    $actual_values = $field_class::convertValues($actual_values, NULL);
    $values = $field_class::convertValues($values, NULL);
    if (sizeof($values) == 1 && !$values[0]) {
      // Converted $values is FALSE which means that term could not be found.
      return array(FALSE, "");
    }

    if ($actual_values === $values) {
      return array(TRUE, "");
    }
    else {
      return array(FALSE, "Values do not match.");
    }
  }

  public static function fillOptionsButtonsValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $formObject->emptyField($field_name);

    $vocabulary = NULL;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    $field_class = get_called_class();
    $tids = $field_class::convertValues($values, $vocabulary);
    $formObject->setValues(
      $field_name,
      array(LANGUAGE_NONE => drupal_map_assoc($tids))
    );
    $termObjects = TaxonomyTerm::createTermObjectsFromTids($tids, $vocabulary);

    return array(TRUE, $termObjects, "");
  }

  public static function fillOptionsSelectValues(
    Form $formObject,
    $field_name,
    $values
  ) {
    $access_function = "has" . Utils::makeTitleCase($field_name) . "Access";
    $access = $formObject->$access_function();
    if (!$access) {
      return array(FALSE, "", "Field $field_name is not accessible.");
    }

    $formObject->emptyField($field_name);

    $vocabulary = NULL;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    $field_class = get_called_class();
    $tids = $field_class::convertValues($values, NULL);
    $formObject->setValues($field_name, array(LANGUAGE_NONE => $tids));
    $termObjects = TaxonomyTerm::createTermObjectsFromTids($tids, $vocabulary);

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
   *       )
   * @param null|string $vocabulary
   * @param bool $return_name
   * @param bool $false_on_invalid
   *
   * @return array
   *
   * @todo Refactor the code to make it more understandable.
   */
  private static function convertValues(
    $values,
    $vocabulary = NULL,
    $return_name = FALSE,
    $false_on_invalid = TRUE
  ) {
    $output = array();
    $function = $return_name ? "getLabel" : "getId";
    $field_class = get_called_class();
    if ($termObject = $field_class::isTermObject($values, $vocabulary)) {
      $output[] = Utils::$function($termObject);
    }
    elseif (!$false_on_invalid && !is_array($values) && (is_numeric(
          $values
        ) || is_string($values))
    ) {
      $output[] = $values;
    }
    elseif (is_array($values)) {
      foreach ($values as $key => $value) {
        if ($termObject = $field_class::isTermObject($value, $vocabulary)) {
          $output[] = Utils::$function($termObject);
        }
        elseif (!$false_on_invalid && !is_array($value) && (is_numeric(
              $value
            ) || is_string($value))
        ) {
          $output[] = $value;
        }
        elseif ($false_on_invalid) {
          $output[] = FALSE;
        }
      }
    }
    elseif ($false_on_invalid) {
      $output[] = FALSE;
    }

    return $output;
  }

  private static function isTermObject($value, $vocabulary) {
    if ($termObject = TaxonomyTerm::termExistsForTid($value, $vocabulary)) {
      // $values follows acceptable format (a).
      return $termObject;
    }
    elseif ($termObject = TaxonomyTerm::termExistsForName(
      $value,
      $vocabulary
    )
    ) {
      // $values follows acceptable format (b).
      return $termObject;
    }
    elseif (is_object($value)) {
      $parent_class = get_parent_class($value);
      if ($parent_class == "RedTest\\core\\entities\\TaxonomyTerm") {
        // $values follows acceptable format (i).
        return $value;
      }
      elseif (property_exists(
          $value,
          'tid'
        ) && $termObject = TaxonomyTerm::termExistsForTid(
          $value->tid,
          $vocabulary
        )
      ) {
        // $values follows acceptable format (f).
        return $termObject;
      }
      elseif (property_exists(
          $value,
          'name'
        ) && $termObject = TaxonomyTerm::termExistsForTid(
          $value->tid,
          property_exists(
            $value,
            'vocabulary'
          ) ? $value->vocabulary : $vocabulary
        )
      ) {
        // $values follows acceptable format (g).
        return $termObject;
      }
    }
    elseif (is_array($value)) {
      if (array_key_exists(
          'tid',
          $value
        ) && $termObject = TaxonomyTerm::termExistsForTid(
          $value['tid'],
          $vocabulary
        )
      ) {
        // $values follows acceptable format (c).
        return $termObject;
      }
      elseif (array_key_exists(
          'name',
          $value
        ) && $termObject = TaxonomyTerm::termExistsForName(
          $value['name'],
          array_key_exists(
            'vocabulary',
            $value
          ) ? $value['vocabulary'] : $vocabulary
        )
      ) {
        // $values follows acceptable format (d).
        return $termObject;
      }
    }
  }
}
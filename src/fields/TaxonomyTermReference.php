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

  public static function fillDefaultValues(Form $formObject, $field_name) {
    $num = 1;
    $vocabulary = '';
    $widget_type = '';
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
      $widget_type = $instance['widget']['type'];
    }

    // Create new taxonomy terms in the specified vocabulary.
    $vocabulary_class = Utils::makeTitleCase($vocabulary);
    $vocabulary_class = "RedTest\\entities\\TaxonomyTerm\\" . $vocabulary_class;

    $termObjects = array();
    for ($i = 0; $i < $num; $i++) {
      if ($widget_type == 'taxonomy_autocomplete' && Utils::getRandomBool()) {
        // Instead of creating a new term, we just pass its name so that Drupal
        // creates a new one automatically.
        $termObjects[] = Utils::getRandomText(10);
      }
      else {
        list($success, $termObject, $msg) = $vocabulary_class::createDefault();
        if (!$success) {
          return array(
            FALSE,
            $termObjects,
            "Could not create taxonomy terms for the field " . $field_name . ": " . $msg
          );
        }
        $termObjects[] = $termObject;
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
    $formObject->emptyField($field_name);

    $vocabulary = NULL;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    $names = self::convertValues($values, $vocabulary, TRUE, FALSE);
    $field_value = is_array($names) ? implode(",", $names) : $names;
    $formObject->setValues($field_name, array(LANGUAGE_NONE => $field_value));

    $termObjects = TaxonomyTerm::createTermObjectsFromNames(
      $names,
      $vocabulary,
      FALSE
    );

    return array(TRUE, $termObjects, "");
  }

  public static function checkValues(
    Entity $entityObject,
    $field_name,
    $values
  ) {
    $function = "get" . Utils::makeTitleCase($field_name) . "Values";
    $actual_values = $entityObject->$function();

    return self::compareValues($actual_values, $values);
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
    $actual_values = self::convertValues($actual_values, NULL);
    $values = self::convertValues($values, NULL);

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
    $formObject->emptyField($field_name);

    $vocabulary = NULL;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    $tids = self::convertValues($values, $vocabulary);
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
    $formObject->emptyField($field_name);

    $vocabulary = NULL;
    if (method_exists($formObject, 'getEntityObject')) {
      // This is an entity form.
      list($field, $instance, $num) = $formObject->getFieldDetails($field_name);
      $vocabulary = $field['settings']['allowed_values'][0]['vocabulary'];
    }

    $tids = self::convertValues($values, NULL);
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
    if ($termObject = self::isTermObject($values, $vocabulary)) {
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
        if ($termObject = self::isTermObject($value, $vocabulary)) {
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
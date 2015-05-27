<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 11/19/14
 * Time: 10:52 PM
 */

namespace RedTest\core\entities;

use RedTest\core\Utils;

class TaxonomyTerm extends Entity {

  /**
   * Default constructor for the term object.
   *
   * @param int $tid
   *   TaxonomyTerm id if an existing term is to be loaded.
   */
  protected function __construct($tid = NULL) {
    $class = new \ReflectionClass(get_called_class());
    $vocabulary_name = Utils::makeSnakeCase($class->getShortName());

    if (!is_null($tid) && is_numeric($tid)) {
      $term = taxonomy_term_load($tid);
      if ($term->vocabulary_machine_name == $vocabulary_name) {
        parent::__construct($term);
      }
    }
    else {
      $term = (object) array(
        'name' => '',
        'description' => '',
        'format' => NULL,
        'vocabulary_machine_name' => $vocabulary_name,
        'tid' => NULL,
        'weight' => 0,
      );
      parent::__construct($term);
    }
  }

  public function deleteProgrammatically() {
    taxonomy_term_delete($this->getId());
    return TRUE;
  }

  /**
   * @param $tid
   * @param $vocabulary
   *
   * @return bool|object
   */
  public static function termExistsForTid($tid, $vocabulary = NULL) {
    if (!is_numeric($tid)) {
      return FALSE;
    }

    $term = taxonomy_term_load($tid);
    if (!$term) {
      return FALSE;
    }

    if (!is_null(
        $vocabulary
      ) && $term->vocabulary_machine_name != $vocabulary
    ) {
      return FALSE;
    }

    $short_class = Utils::makeTitleCase($term->vocabulary_machine_name);
    $full_class = "RedTest\\entities\\TaxonomyTerm\\" . $short_class;
    $termObject = new $full_class($term->tid);

    return $termObject;
  }

  public static function termExistsForName($name, $vocabulary = NULL) {
    if (!is_string($name) && !is_numeric($name)) {
      return FALSE;
    }

    $terms = taxonomy_get_term_by_name($name, $vocabulary);
    if (!sizeof($terms)) {
      return FALSE;
    }

    $term = array_shift($terms);

    $short_class = Utils::makeTitleCase($term->vocabulary_machine_name);
    $full_class = "RedTest\\entities\\TaxonomyTerm\\" . $short_class;
    $termObject = new $full_class($term->tid);

    return $termObject;
  }

  /**
   * @param $tids
   *
   * @return array
   */
  public static function createTermObjectsFromTids($tids, $vocabulary = NULL, $false_on_invalid = TRUE) {
    $terms = taxonomy_term_load_multiple($tids);

    $termObjects = array();
    foreach ($tids as $tid) {
      if (empty($terms[$tid])) {
        if ($false_on_invalid) {
          $termObjects[] = FALSE;
        }
        else {
          $termObjects = $tid;
        }
        continue;
      }

      $vocabulary = $terms[$tid]->vocabulary_machine_name;
      $term_class = "RedTest\\entities\\TaxonomyTerm\\" . Utils::makeTitleCase(
          $vocabulary
        );
      $termObjects[] = new $term_class($tid);
    }

    return $termObjects;
  }

  public static function createTermObjectsFromNames($names, $vocabulary = NULL, $false_on_invalid = TRUE) {
    $termObjects = array();
    foreach ($names as $name) {
      if ($termObject = TaxonomyTerm::termExistsForName($name, $vocabulary)) {
        $termObjects[] = $termObject;
      }
      elseif (!$false_on_invalid) {
        $termObjects[] = $name;
      }
      else {
        $termObjects[] = FALSE;
      }
    }

    return $termObjects;
  }
}
<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 11/19/14
 * Time: 10:52 PM
 */

namespace RedTest\core\entities;

use tests\phpunit_tests\core\Utilities as Utilities;

class TaxonomyTerm extends Entity {

  /**
   * Default constructor for the term object.
   *
   * @param int $tid
   *   TaxonomyTerm id if an existing term is to be loaded.
   */
  protected function __construct($tid = NULL) {
    $class = new \ReflectionClass(get_called_class());
    $vocabulary_name = Utilities::convertTitleCaseToUnderscore($class->getShortName());

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
}
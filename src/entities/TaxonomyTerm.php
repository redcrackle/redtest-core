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

  public function delete() {
    taxonomy_term_delete($this->getId());
  }
}
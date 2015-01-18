<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/15/14
 * Time: 6:00 PM
 */

namespace tests\phpunit_tests\core\forms;


class TaxonomyFormVocabulary extends Form {
  function __construct() {
	parent::__construct('taxonomy_form_vocabulary');
  }
  
  /**
   * This function is used for submit vocabulary form
   * @param NULL
   * @return $form_state if success else error message 
   */
  public function submit() {
    $this->fillValues(array('op' => t('Save')));
	$output = parent::submit($this->fillValues);
	if (is_array($output)) {
		// There was an error.
		return $output;
	}
    else {
		$form_state = $this->getFormState();
		return $form_state['values'];
    } 
  }
 
  /**
   * This function is used for vocabulary relation group field
   * @param $value
   *  This is combination of title and group id
   */
  public function fillOgRelationField($value) {
    $this->fillVocabOGRelationWidgetField('og_vocab_relation', $value);
  }

  /**
   * This function is used for vocabulary enable with which
      content type 
   * @param $value
   *  This is name of content type
   */
  public function fillOgContentTypeField($field_name) {
    $this->fillOGContentTypeWidgetField($field_name);
  }

} 

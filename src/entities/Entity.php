<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 11/13/14
 * Time: 6:16 PM
 */

namespace RedTest\core\entities;

use RedTest\core\Response;
use RedTest\core\fields\Field;
use RedTest\core\Utils;
use RedTest\core\forms;

/**
 * Class Entity
 *
 * @package tests\phpunit_tests\custom\entities
 */
abstract class Entity {

  /**
   * Entity object.
   *
   * @var object $entity
   */
  private $entity;

  /**
   * Entity type.
   *
   * @var string $entity_type
   *   Type of entity.
   */
  private $entity_type;

  /**
   * @var bool
   */
  private $initialized;

  /**
   * @var array|string
   */
  private $errors;

  /**
   * Prevent an object from being constructed.
   *
   * @param object $entity
   *   Entity object.
   */
  protected function __construct($entity) {
    $this->entity = $entity;
    $this->entity_type = $this->getEntityType();
    $this->setInitialized(TRUE);
  }

  /**
   * Returns the $initialized variable.
   *
   * @return bool
   *   Initialized variable.
   */
  public function getInitialized() {
    return $this->initialized;
  }

  /**
   * Sets the initialized variable.
   *
   * @param $initialized
   *   Initialized variable.
   */
  public function setInitialized($initialized) {
    $this->initialized = $initialized;
  }

  /**
   * Returns an array of errors.
   *
   * @return array|string
   *   Array of errors.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Set errors array. This is needed is a field wants to set an error.
   *
   * @param array|string $errors
   *   An array of errors.
   */
  public function setErrors($errors) {
    $this->errors = $errors;
  }

  /**
   * Clear errors from a form.
   */
  public function clearErrors() {
    unset($this->errors);
  }

  /**
   * Verify that the class got initialized correctly.
   *
   * @param string|\PHPUnit_Framework_TestCase $testClass
   *   Test class.
   *
   * @return $this
   *   The form object.
   */
  public function verify($testClass) {
    if (is_string($testClass)) {
      $testClass = new $testClass();
    }
    $testClass->assertTrue($this->getInitialized(), $this->getErrors());
    return $this;
  }

  /**
   * Returns the entity id.
   *
   * This is a copy of entity_id() function from entity.module. We are not
   * using the entity_id() function since entity_id module may not have been
   * installed.
   *
   * @return int $id
   *   Entity id.
   */
  public function getId() {
    if (method_exists($this->entity, 'identifier')) {
      return $this->entity->identifier();
    }
    $info = entity_get_info($this->getEntityType());
    $key = isset($info['entity keys']['name']) ? $info['entity keys']['name'] : $info['entity keys']['id'];

    return isset($this->entity->$key) ? $this->entity->$key : NULL;
  }

  /**
   * Returns entity type from the called class.
   *
   * @return bool|string
   *   Entity type if one exists, FALSE otherwise.
   */
  private static function getClassEntityType() {
    $classes = class_parents(get_called_class());
    if (sizeof($classes) >= 2) {
      // If there are at least 2 parent classes, such as Entity and Node.
      $classnames = array_values($classes);
      $classname = $classnames[sizeof($classes) - 2];
      $class = new \ReflectionClass($classname);
      $entity_type = Utils::makeSnakeCase(
        $class->getShortName()
      );

      return $entity_type;
    }
    elseif (sizeof($classes) == 1) {
      // If an entity such as User is calling the class directly, then entity type will be User itself.
      $classname = get_called_class();
      $class = new \ReflectionClass($classname);
      $entity_type = Utils::makeSnakeCase(
        $class->getShortName()
      );

      return $entity_type;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns entity type from the object.
   *
   * @return bool|string
   *   Entity type if one exists, FALSE otherwise.
   */
  private function getObjectEntityType() {
    // If the function is being called from static context and $this->entity_type is defined, then return it.
    if (!is_null($this->entity_type)) {
      return $this->entity_type;
    }

    return self::getClassEntityType();
  }

  /**
   * Reloads the entity from database.
   */
  public function reload() {
    $entity_id = $this->getId();
    if (empty($entity_id)) {
      $this->entity = NULL;

      return;
    }

    $entities = entity_load(
      $this->entity_type,
      array($entity_id),
      array(),
      TRUE
    );
    if (!empty($entities[$entity_id])) {
      $this->entity = $entities[$entity_id];
    }
    else {
      $this->entity = NULL;
    }
  }

  /**
   * Returns the entity object.
   *
   * @return object $entity
   *   Entity object.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Sets the entity object.
   *
   * @param object $entity
   *   Entity object.
   */
  public function setEntity($entity) {
    $this->entity = $entity;
  }

  /**
   * Returns the view of the entity for a given view mode. This is copied from
   * entity_view() function since entity module may not be installed.
   *
   * @param string $view_mode
   *   View mode. If view mode is not specified, then default view mode is
   *   assumed.
   *
   * @return array $view
   *   A renderable array of the entity for the provided view mode. If there is
   *   any error, then FALSE is returned.
   */
  public function view($view_mode = 'full') {
    $entities = array($this->entity);
    $langcode = NULL;
    $page = NULL;

    $output = array();
    $info = entity_get_info($this->entity_type);
    if (isset($info['view callback'])) {
      $entities = entity_key_array_by_property(
        $entities,
        $info['entity keys']['id']
      );

      $output = $info['view callback'](
        $entities,
        $view_mode,
        $langcode,
        $this->entity_type
      );
    }
    elseif (in_array(
      'EntityAPIControllerInterface',
      class_implements($info['controller class'])
    )) {
      $output = entity_get_controller($this->entity_type)->view(
        $entities,
        $view_mode,
        $langcode,
        $page
      );
    }

    if (!empty($output[$this->entity_type][$this->getId()])) {
      return $output[$this->entity_type][$this->getId()];
    }

    return array();
  }

  /**
   * Saves the entity to database. This is copied from entity_save() function
   * since entity module may not be installed.
   */
  public function saveProgrammatically() {
    $info = entity_get_info($this->entity_type);
    if (method_exists($this->entity, 'save')) {
      $this->entity->save();
    }
    elseif (isset($info['save callback'])) {
      $info['save callback']($this->entity);
    }
    elseif (in_array(
      'EntityAPIControllerInterface',
      class_implements($info['controller class'])
    )) {
      entity_get_controller($this->entity_type)->save($this->entity);
    }
    elseif ($this->entity_type == 'node') {
      node_save($this->entity);
    }
    elseif ($this->entity_type == 'user') {
      user_save($this->entity);
    }
    elseif ($this->entity_type == 'taxonomy_term') {
      taxonomy_term_save($this->entity);
    }
  }

  /**
   * Deletes the entity from database. This is copied from the entity_delete()
   * function since entity module may not be installed.
   */
  public function deleteProgrammatically() {
    $entity_class = "RedTest\\core\\entities\\" . Utils::makeTitleCase(
        $this->entity_type
      );

    $info = entity_get_info($this->entity_type);
    if (isset($info['deletion callback'])) {
      $info['deletion callback']($this->getId());
      return TRUE;
    }
    elseif (in_array(
      'EntityAPIControllerInterface',
      class_implements($info['controller class'])
    )) {
      entity_get_controller($this->entity_type)->delete(array($this->getId()));
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Whether user has access for the "create" operation. This function is
   * called from static method "hasAccess".
   *
   * @param string $op
   *   This has to be "create" since this is the only operation that can be
   *   called statically.
   *
   * @return bool
   *   TRUE if user has access and FALSE otherwise.
   */
  public static function hasClassAccess($op) {
    if ($op !== 'create') {
      return FALSE;
    }

    $entity_class = get_called_class();
    $entity_type = $entity_class::getEntityType();
    if (module_exists('entity')) {
      return entity_access($op, $entity_type);
    }
    elseif ($entity_type == 'node') {
      $class = new \ReflectionClass($entity_class);
      $bundle = Utils::makeSnakeCase($class->getShortName());

      return node_access($op, $bundle);
    }
    elseif (($info = entity_get_info(
      )) && isset($info[$entity_type]['access callback'])
    ) {
      return $info[$entity_type]['access callback'](
        $op,
        NULL,
        NULL,
        $entity_type
      );
    }

    return FALSE;
  }

  /**
   * Whether user has access to update, view or delete the entity.
   *
   * @param string $op
   *   This can either be "update", "view" or "delete".
   *
   * @return bool
   *   TRUE if user has access and FALSE otherwise.
   */
  public function hasObjectAccess($op) {
    if (!in_array($op, array('update', 'view', 'delete'))) {
      return FALSE;
    }

    $entity_type = $this->getEntityType();
    if (module_exists('entity')) {
      return entity_access($op, $entity_type, $this->getEntity());
    }
    elseif ($entity_type == 'node') {
      return node_access($op, $this->getEntity());
    }
    elseif ($entity_type == 'comment' && $op == 'update') {
      return comment_access('edit', $this->getEntity());
    }
    elseif (($info = entity_get_info(
      )) && isset($info[$entity_type]['access callback'])
    ) {
      return $info[$entity_type]['access callback'](
        $op,
        $this->getEntity(),
        NULL,
        $entity_type
      );
    }

    return FALSE;
  }

  /**
   * Returns whether currently logged in user has access to create the entity.
   *
   * @return bool
   *   TRUE if user has access and FALSE otherwise.
   */
  public static function hasCreateAccess() {
    $entity_class = get_called_class();

    return $entity_class::hasAccess('create');
  }

  /**
   * Returns whether currently logged in user has access to view the entity.
   *
   * @return bool
   *   TRUE if user has access and FALSE otherwise.
   */
  public function hasViewAccess() {
    return $this->hasAccess('view');
  }

  /**
   * Returns whether currently logged in user has access to update the entity.
   *
   * @return bool
   *   TRUE if user has access and FALSE otherwise.
   */
  public function hasUpdateAccess() {
    return $this->hasAccess('update');
  }

  /**
   * Returns whether currently logged in user has access to delete the node.
   *
   * @return bool $out
   *   TRUE if user has access and FALSE otherwise.
   */
  public function hasDeleteAccess() {
    return $this->hasAccess('delete');
  }

  /**
   * Returns whether currently logged in user has access to do specified
   * operation on the given field.
   *
   * @param string $field_name
   *   Field name.
   * @param string $op
   *   Operation. It can be either 'view' or 'edit'.
   *
   * @return bool|null
   *   If field exists, then this function returns TRUE or FALSE depending on
   *   the access. If field does not exist, then it returns NULL.
   */
  public function hasFieldAccess($field_name, $op = 'view') {
    if (($field = field_info_field($field_name)) && in_array(
        $op,
        array('edit', 'view')
      )
    ) {
      return field_access($op, $field, $this->getEntityType(), $this->entity);
    }

    return NULL;
  }

  /**
   * Returns text field as viewed by the logged in user in the provided view
   * mode.
   *
   * @param string $field_name
   *   Field name.
   * @param string $view_mode
   *   View mode. If this is not provided, then "full" is assumed.
   * @param bool $post_process
   *   Whether to post process the field values before returning.
   * @param bool $from_entity_view
   *   Whether to return the field values using field_view_field() function or
   *   building the entity view and returning the field value from there. If
   *   code uses entity_view_alter() or node_view_alter(), then the two values
   *   can differ. If you don't expect them to be different, then it is
   *   recommended to keep this argument to FALSE since it will be faster.
   *
   * @return null|array $view
   *   Renderable array of the field if it exists, NULL otherwise.
   */
  public function viewText(
    $field_name,
    $view_mode = 'full',
    $post_process = TRUE,
    $from_entity_view = FALSE
  ) {
    $view = array();
    if ($from_entity_view) {
      $view = $this->view($view_mode);
      if (!empty($view[$field_name])) {
        $view = $view[$field_name];
      }
    }
    else {
      $view = field_view_field(
        $this->entity_type,
        $this->entity,
        $field_name,
        $view_mode,
        NULL
      );
    }

    if (!$post_process) {
      return $view;
    }

    $output = array();
    foreach (element_children($view) as $key) {
      $output[] = $view[$key]['#markup'];
    }

    if (sizeof($output) == 1) {
      return $output[0];
    }

    return $output;
  }

  public function viewTextLong(
    $field_name,
    $view_mode = 'full',
    $post_process = TRUE,
    $from_entity_view = FALSE
  ) {
    if ($from_entity_view) {
      $view = $this->view($view_mode);
      if (!empty($view[$field_name])) {
        $view = $view[$field_name];
      }
    }
    else {
      $view = field_view_field(
        $this->entity_type,
        $this->entity,
        $field_name,
        $view_mode,
        NULL
      );
    }

    if (!$post_process) {
      return $view;
    }

    $output = array();
    foreach (element_children($view) as $key) {
      $output[] = $view[$key]['#markup'];
    }

    if (sizeof($output) == 1) {
      return $output[0];
    }

    return $output;
  }

  public function viewDatetime(
    $field_name,
    $view_mode = 'full',
    $post_process = TRUE,
    $from_entity_view = FALSE
  ) {
    $view = array();
    if ($from_entity_view) {
      $view = $this->view($view_mode);
      if (!empty($view[$field_name])) {
        $view = $view[$field_name];
      }
    }
    else {
      $view = field_view_field(
        $this->entity_type,
        $this->entity,
        $field_name,
        $view_mode,
        NULL
      );
    }

    if (!$post_process) {
      return $view;
    }

    $output = array();
    foreach (element_children($view) as $key) {
      $output[] = $view[$key]['#markup'];
    }

    if (sizeof($output) == 1) {
      return $output[0];
    }

    return $output;
  }

  public function viewFile(
    $field_name,
    $view_mode = 'full',
    $post_process = TRUE,
    $from_entity_view = FALSE
  ) {
    if ($from_entity_view) {
      $view = $this->view($view_mode);
      if (!empty($view[$field_name])) {
        $view = $view[$field_name];
      }
    }
    else {
      $view = field_view_field(
        $this->entity_type,
        $this->entity,
        $field_name,
        $view_mode,
        NULL
      );
    }

    if (!$post_process) {
      return $view;
    }

    $output = array();
    foreach (element_children($view) as $key) {
      $output[] = drupal_render($view[$key]);
    }

    if (sizeof($output) == 1) {
      return $output[0];
    }

    return $output;
  }

  public function viewEntityreference(
    $field_name,
    $view_mode = 'full',
    $post_process = TRUE,
    $from_entity_view = FALSE
  ) {
    if ($from_entity_view) {
      $view = $this->view($view_mode);
      if (!empty($view[$field_name])) {
        $view = $view[$field_name];
      }
    }
    else {
      $view = field_view_field(
        $this->entity_type,
        $this->entity,
        $field_name,
        $view_mode,
        NULL
      );
    }

    if (!$post_process) {
      return $view;
    }

    $output = array();
    foreach (element_children($view) as $key) {
      $output[] = $view[$key]['#markup'];
    }

    if (sizeof($output) == 1) {
      return $output[0];
    }

    return $output;
  }

  public function viewTaxonomyTermReference(
    $field_name,
    $view_mode = 'full',
    $post_process = TRUE,
    $from_entity_view = FALSE
  ) {
    if ($from_entity_view) {
      $view = $this->view($view_mode);
      if (!empty($view[$field_name])) {
        $view = $view[$field_name];
      }
    }
    else {
      $view = field_view_field(
        $this->entity_type,
        $this->entity,
        $field_name,
        $view_mode,
        NULL
      );
    }

    if (!$post_process) {
      return $view;
    }

    $output = array();
    foreach (element_children($view) as $key) {
      $output[] = $view[$key]['#title'];
    }

    if (sizeof($output) == 1) {
      return $output[0];
    }

    return $output;
  }

  /**
   * Returns field as viewed by the logged in user in the provided view mode.
   *
   * @param string $field_name
   *   Field name.
   * @param string $view_mode
   *   View mode. If this is not provided, then "full" is assumed.
   * @param bool $post_process
   *   Whether to post process the field values before returning.
   * @param bool $from_entity_view
   *   Whether to return the field values using field_view_field() function or
   *   building the entity view and returning the field value from there. If
   *   code uses entity_view_alter() or node_view_alter(), then the two values
   *   can differ. If you don't expect them to be different, then it is
   *   recommended to keep this argument to FALSE since it will be faster.
   *
   * @return null|array $view
   *   Renderable array of the field if it exists, NULL otherwise.
   */
  public function viewField(
    $field_name,
    $view_mode = 'full',
    $post_process = TRUE,
    $from_entity_view = FALSE
  ) {
    if ($instance = $this->getFieldInstance($field_name)) {
      // Field instance exists.
      // If post-processing is not required, then just return the field values
      // as provided by Drupal.
      if (!$post_process) {
        if ($from_entity_view) {
          $view = $this->view($view_mode);
          if (!empty($view[$field_name])) {
            $view = $view[$field_name];
          }
        }
        else {
          $view = field_view_field(
            $this->entity_type,
            $this->entity,
            $field_name,
            $view_mode,
            NULL
          );
        }

        return $view;
      }

      if (!$this->hasFieldAccess($field_name)) {
        return NULL;
      }

      // Get the field instance value here.
      $function = 'view' . Utils::makeTitleCase(
          $instance['widget']['type']
        );

      // Check if a function exists for getting value from this particular field
      // instance.
      if (method_exists($this, $function)) {
        return $this->$function(
          $field_name,
          $view_mode,
          $post_process,
          $from_entity_view
        );
      }
      else {
        // Check if a function exists for getting value from this particular
        // field type.
        $field = $this->getFieldInfo($field_name);
        $function = 'view' . Utils::makeTitleCase(
            $field['type']
          );
        if (method_exists($this, $function)) {
          return $this->$function(
            $field_name,
            $view_mode,
            $post_process,
            $from_entity_view
          );
        }
      }

      // Field instance exists but no function is defined to get value from it.
      return NULL;
    }

    // There is no such field instance for the given entity. Check if it's a
    // property.
    if (!empty($this->entity->$field_name)) {
      return $this->entity->$field_name;
    }

    return NULL;
  }

  /**
   * Sets values in the entity object.
   *
   * @param array $values
   *   An array of values.
   */
  public function setValues($values) {
    foreach ($values as $key => $value) {
      $this->entity->$key = $value;
    }
  }

  /**
   * Returns label of the entity.
   *
   * @return bool|string $label
   *   Entity label.
   */
  public function getLabel() {
    return entity_label($this->getEntityType(), $this->entity);
  }

  /**
   * Magic method. This function will be executed when a matching static
   * function is not found. Currently this supports getEntityType() function.
   *
   * @param string $name
   *   Called function name.
   * @param $arguments
   *   Function arguments.
   *
   * @return mixed
   *   Output depends on which function ultimately gets called.
   */
  public static function __callStatic($name, $arguments) {
    if ($name == 'getEntityType') {
      return self::getClassEntityType();
    }
    elseif ($name == 'hasAccess') {
      $class = get_called_class();

      return call_user_func_array(array($class, 'hasClassAccess'), $arguments);
    }
  }

  /**
   * Magic method. This function will be executed when a matching function is
   * not found. Currently this supports three kinds of functions:
   * getEntityType(), get<FieldName>() and has<FieldName><View|Edit>Access().
   *
   * @param string $name
   *   Called function name.
   * @param string $arguments
   *   Function arguments.
   *
   * @return mixed $output
   *   Output depends on which function ultimately gets called.
   */
  public function __call($name, $arguments) {
    if ($name == 'getEntityType') {
      return $this->getObjectEntityType();
    }
    elseif ($name == 'hasAccess') {
      return call_user_func_array(array($this, 'hasObjectAccess'), $arguments);
    }
    elseif ($this->isHasFieldAccessFunction($name)) {
      // Function name starts with "has" and ends with "Access". Function name
      // is not one of "hasCreateAccess", "hasUpdateAccess", "hasViewAccess" or
      // "hasDeleteAccess" otherwise code execution would not have reached this
      // function. This means that we are checking if a field is accessible.
      $name = substr($name, 3, -6);
      $op = '';
      $field_name = '';
      if (Utils::endsWith($name, 'View')) {
        $op = 'view';
        $field_name = Utils::makeSnakeCase(
          substr($name, 0, -4)
        );
      }
      elseif (Utils::endsWith($name, 'Update')) {
        $op = 'edit';
        $field_name = Utils::makeSnakeCase(
          substr($name, 0, -6)
        );
      }

      if (in_array($op, array('view', 'edit'))) {
        return $this->hasFieldAccess($field_name, $op);
      }
    }
    elseif ($this->isGetFieldValuesFunction($name)) {
      // Function name starts with "get" and ends with "Values". This means that
      // we need to return value of a field.
      array_unshift(
        $arguments,
        Utils::makeSnakeCase(substr($name, 3, -6))
      );

      return call_user_func_array(array($this, 'getFieldValues'), $arguments);
    }
    elseif (strpos($name, 'view') === 0) {
      // Function name starts with "view".
      array_unshift(
        $arguments,
        Utils::makeSnakeCase(substr($name, 4))
      );

      return call_user_func_array(array($this, 'viewField'), $arguments);
    }
    elseif ($this->isCheckFieldValuesFunction($name)
    ) {
      // Function name starts with "check" and ends with "Values".
      $field_name = Utils::makeSnakeCase(substr($name, 5, -6));
      array_unshift($arguments, $field_name);

      return call_user_func_array(array($this, 'checkFieldValues'), $arguments);
    }
    elseif (strpos($name, "check") === 0 && strrpos($name, 'Views') == strlen(
        $name
      ) - 5
    ) {
      // Function name starts with "check" and ends with "Values".
      $field_name = Utils::makeSnakeCase(
        substr($name, 5, -5)
      );
      array_unshift($arguments, $field_name);

      return call_user_func_array(array($this, 'checkFieldViews'), $arguments);
    }
  }

  public function checkDatetimeItems($field_name, $testClass, $values) {
    $function = "get" . Utils::makeTitleCase($field_name);
    /*$testClass->assertEquals(
      $values,
      $this->$function(),
      "Values for " . $field_name . " do not match."
    );*/
  }

  public function checkDatetimeViews(
    $field_name,
    $testClass,
    $values,
    $view_mode = 'default'
  ) {
    $function = 'view' . Utils::makeTitleCase($field_name);
  }

  public function checkFileItems($field_name, $testClass, $values) {
    $function = "get" . Utils::makeTitleCase($field_name);
  }

  public function checkFileViews(
    $field_name,
    $testClass,
    $values,
    $view_mode = 'default'
  ) {
    $files = call_user_func(
      array(
        $this,
        "view" . Utils::makeTitleCase($field_name)
      ),
      $view_mode
    );

    /*$testClass->assertEquals(
      $values,
      $files,
      "Values for " . $field_name . " do not match."
    );*/
  }

  public function checkFieldViews(
    $field_name,
    $testClass,
    $values,
    $view_mode = 'default'
  ) {
    if ($instance = $this->getFieldInstance($field_name)) {
      $function = 'check' . Utils::makeTitleCase(
          $instance['widget']['type']
        ) . 'Views';
      if (method_exists($this, $function)) {
        $this->$function($field_name, $testClass, $values, $view_mode);
      }
      else {
        $field = $this->getFieldInfo($field_name);
        $function = "check" . Utils::makeTitleCase(
            $field['type']
          ) . "Views";
        if (method_exists($this, $function)) {
          $this->$function($field_name, $testClass, $values, $view_mode);
        }
        else {
          $testClass->assertEquals(
            $values,
            call_user_func(
              array(
                $this,
                "view" . Utils::makeTitleCase($field_name)
              ),
              $view_mode
            ),
            "Values for " . $field_name . " do not match."
          );
        }
      }

      return;
    }

    // Field instance does not exist. Check if a property exists and its value
    // matches.
    $testClass->assertObjectHasAttribute(
      $field_name,
      $this->entity,
      "Field " . $field_name . " not found."
    );
    $testClass->assertEquals(
      $values,
      $this->entity->$field_name,
      "Values of the " . $field_name . " do not match."
    );
  }

  public function checkFieldValues($field_name, $values) {
    list($field, $instance, $num) = Field::getFieldDetails($this, $field_name);
    if (!is_null($field) && !is_null($instance)) {
      $short_field_class = Utils::makeTitleCase($field['type']);
      $field_class = "RedTest\\core\\fields\\" . $short_field_class;

      return $field_class::checkValues($this, $field_name, $values);
    }

    // Field instance does not exist. Check if a property exists and its value
    // matches.
    if (!property_exists($this->entity, $field_name)) {
      return new Response(FALSE, NULL, "Field " . $field_name . " not found.");
    }

    if ($this->entity->$field_name != $values) {
      return new Response(FALSE, NULL, "Values of $field_name do not match.");
    }

    return new Response(TRUE, NULL, "");
  }

  public function checkTaxonomyTermReferenceItems(
    $field_name,
    $testClass,
    $values
  ) {
    $testClass->assertEquals(
      Utils::getId($values),
      call_user_func(
        array(
          $this,
          "get" . Utils::makeTitleCase($field_name)
        )
      ),
      "Values of " . $field_name . " do not match."
    );
  }

  public function checkTaxonomyTermReferenceViews(
    $field_name,
    $testClass,
    $values,
    $view_mode = 'default'
  ) {
    if (is_array($values)) {
      $labels = array();
      foreach ($values as $value) {
        if (is_string($value)) {
          $labels[] = $value;
        }
        else {
          $labels[] = $value->getLabel();
        }
      }
      $testClass->assertEquals(
        $labels,
        call_user_func(
          array(
            $this,
            "view" . Utils::makeTitleCase($field_name)
          ),
          $view_mode
        ),
        "Values of " . $field_name . " for " . $view_mode . " do not match."
      );
    }
    elseif (is_object($values)) {
      $testClass->assertEquals(
        Utils::getLabel($values),
        call_user_func(
          array(
            $this,
            "view" . Utils::makeTitleCase($field_name)
          ),
          $view_mode
        ),
        "Values of " . $field_name . " for " . $view_mode . " do not match."
      );
    }
    else {
      $testClass->assertEquals(
        $values,
        call_user_func(
          array(
            $this,
            "view" . Utils::makeTitleCase($field_name)
          ),
          $view_mode
        ),
        "Values of " . $field_name . " for " . $view_mode . " do not match."
      );
    }
  }

  public function checkAutocompleteDeluxeTaxonomyItems(
    $field_name,
    $testClass,
    $values
  ) {
    $term_labels = call_user_func(
      array($this, "get" . Utils::makeTitleCase($field_name))
    );

    $testClass->assertEquals(
      $values,
      $term_labels,
      "Values of the " . $field_name . " do not match."
    );
  }

  public function checkAutocompleteDeluxeTaxonomyViews(
    $field_name,
    $testClass,
    $values,
    $view_mode = 'default'
  ) {
    $term_labels = call_user_func(
      array(
        $this,
        "view" . Utils::makeTitleCase($field_name)
      ),
      $view_mode
    );

    $testClass->assertEquals(
      $values,
      $term_labels,
      "Values of the " . $field_name . " do not match."
    );
  }

  public function checkTaxonomyAutocompleteItems(
    $field_name,
    $testClass,
    $values
  ) {
    $current_tids = call_user_func(
      array($this, "get" . Utils::makeTitleCase($field_name))
    );

    if (!is_array($current_tids)) {
      $term = taxonomy_term_load($current_tids);
      $testClass->assertEquals(
        $values,
        $term->name,
        "Values of the " . $field_name . " do not match."
      );
    }
    else {
      $terms = taxonomy_term_load_multiple($current_tids);
      $term_labels = array();
      foreach ($terms as $tid => $term) {
        $term_labels[] = $term->name;
      }
      $testClass->assertEquals(
        $values,
        $term_labels,
        "Values of the " . $field_name . " do not match."
      );
    }
  }

  public function viewListBoolean(
    $field_name,
    $view_mode = 'full',
    $post_process = TRUE,
    $from_entity_view = FALSE
  ) {
    if ($from_entity_view) {
      $view = $this->view($view_mode);
      if (!empty($view[$field_name])) {
        $view = $view[$field_name];
      }
    }
    else {
      $view = field_view_field(
        $this->entity_type,
        $this->entity,
        $field_name,
        $view_mode,
        NULL
      );
    }

    if (!$post_process) {
      return $view;
    }

    $output = array();
    foreach (element_children($view) as $key) {
      $output[] = $view[$key]['#markup'];
    }

    if (sizeof($output) == 1) {
      return $output[0];
    }

    return $output;
  }

  public function checkListBooleanViews(
    $field_name,
    $testClass,
    $values,
    $view_mode = 'default'
  ) {
    $field = $this->getFieldInfo($field_name);
    $instance = $this->getFieldInstance($field_name);

    $current_markup = call_user_func(
      array(
        $this,
        "view" . Utils::makeTitleCase($field_name)
      ),
      $view_mode
    );
    if (is_array($values)) {
      $values = array_walk(
        $values,
        function ($value, $key, $map) { return $map[$value]; },
        $field['settings']['allowed_values']
      );
      $testClass->assertEquals(
        $values,
        $current_markup,
        "View of " . $field_name . " for " . $view_mode . " view mode does not match."
      );
    }
    else {
      $testClass->assertEquals(
        $field['settings']['allowed_values'][$values],
        $current_markup,
        "View of " . $field_name . " for " . $view_mode . " view mode does not match."
      );
    }
  }

  public function viewNumberInteger(
    $field_name,
    $view_mode = 'full',
    $post_process = TRUE,
    $from_entity_view = FALSE
  ) {
    if ($from_entity_view) {
      $view = $this->view($view_mode);
      if (!empty($view[$field_name])) {
        $view = $view[$field_name];
      }
    }
    else {
      $view = field_view_field(
        $this->entity_type,
        $this->entity,
        $field_name,
        $view_mode,
        NULL
      );
    }

    if (!$post_process) {
      return $view;
    }

    $output = array();
    foreach (element_children($view) as $key) {
      $output[] = $view[$key]['#markup'];
    }

    if (sizeof($output) == 1) {
      return $output[0];
    }

    return $output;
  }

  /**
   * Get value of a field.
   *
   * @param string $field_name
   *   Field name.
   * @param bool $post_process
   *   Whether to post process the field values before returning.
   *
   * @return mixed $output
   *   Value of the field.
   *
   * @throws \EntityMalformedException
   */
  public function getFieldValues($field_name, $post_process = TRUE) {
    list($field, $instance, $num) = Field::getFieldDetails($this, $field_name);
    if (!is_null($field) && !is_null($instance)) {
      // Field instance exists.
      $short_field_class = Utils::makeTitleCase($field['type']);
      $field_class = "RedTest\\core\\Fields\\" . $short_field_class;

      return $field_class::getValues($this, $field_name, $post_process);
    }

    // There is no such field instance for the given entity. Check if it's a
    // property.
    if (isset($this->entity->$field_name)) {
      return $this->entity->$field_name;
    }

    return NULL;
  }

  /**
   * Returns field instance from field name for the given entity.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return mixed $instance
   *   An instance array if one exists, FALSE otherwise.
   *
   * @throws \EntityMalformedException
   */
  public function getFieldInstance($field_name) {
    list(, , $bundle) = entity_extract_ids(
      $this->entity_type,
      $this->entity
    );

    return field_info_instance(
      $this->entity_type,
      $field_name,
      $bundle
    );
  }

  public function getFieldInstances() {
    list(, , $bundle) = entity_extract_ids(
      $this->entity_type,
      $this->entity
    );

    return field_info_instances($this->entity_type, $bundle);
  }

  /**
   * Returns field info from field name.
   *
   * @param string $field_name
   *   Field name.
   *
   * @returns array|null $field
   *   Field info array if one exists, NULL otherwise.
   */
  public function getFieldInfo($field_name) {
    return field_info_field($field_name);
  }

  /**
   * Get field items.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   An array of field items.
   */
  public function getFieldItems($field_name) {
    return field_get_items($this->entity_type, $this->entity, $field_name);
  }

  /**
   * Get the class name of the form for the current entity along with full path.
   *
   * @return string
   *   Class name of the form.
   */
  public static function getFormClassName() {
    // Get the form class based on the entity that needs to be created.
    $entity_type = self::getEntityType();
    $original_class = get_called_class();
    $class = new \ReflectionClass($original_class);
    $formClass = "RedTest\\forms\\entities\\" . Utils::makeTitleCase(
        $entity_type
      ) . "\\" . $class->getShortName() . 'Form';

    return $formClass;
  }

  /**
   * Process the form and options array before random entities are created. The
   * main purpose here is for taxonomy term reference and entity reference
   * fields to create entities that can be attached to fields.
   *
   * @param array $options
   *   Options array.
   */
  protected static function processBeforeCreateRandom(&$options) {
    // We need to use "static" here and not "self" since "getFormClass" needs to
    // be called from individual Entity class to get the correct value.
    $formClass = static::getFormClassName();

    // Instantiate the form class.
    $classForm = new $formClass();

    // First get all field instances.
    $field_instances = $classForm->getEntityObject()->getFieldInstances();

    // Iterate over all the field instances and if the field is to be filled,
    // then process it.
    foreach ($field_instances as $field_name => $field_instance) {
      if (Field::isToBeFilled($classForm, $field_name, $options)) {
        // Check if the field is a taxonomy term field or an entity reference field.
        list($field_class, $widget_type) = Field::getFieldClass(
          $classForm,
          $field_name
        );

        $field_class::processBeforeCreateRandom(
          $classForm,
          $field_name,
          $options
        );
      }
    }
  }

  /**
   * Create new entities with default field values.
   *
   * @param int $num
   *   Number of entities to create.
   * @param array $options
   *   An associative options array. It can have the following keys:
   *   (a) skip: An array of field names which are not to be filled.
   *   (b) required_fields_only: TRUE if only required fields are to be filled
   *   and FALSE if all fields are to be filled.
   *
   * @return Response
   *   Response object.
   */
  public static function createRandom($num = 1, $options = array()) {
    // First get the references that need to be created.
    static::processBeforeCreateRandom($options);

    // We need to use "static" here and not "self" since "getFormClass" needs to
    // be called from individual Entity class to get the correct value.
    $formClass = static::getFormClassName();

    $output = array();
    for ($i = 0; $i < $num; $i++) {
      // Instantiate the form class.
      $classForm = new $formClass();
      if (!$classForm->getInitialized()) {
        return new Response(FALSE, $output, $classForm->getErrors());
      }

      // Fill default values in the form. We don't check whether the created
      // entity has the correct field since some custom function could be
      // changing the field values on creation. For checking field values on
      // entity creation, a form needs to be initialized in the test.
      $response = $classForm->fillRandomValues($options);
      if (!$response->getSuccess()) {
        $response->setVar($output);
        return $response;
      }

      // Submit the form to create the entity.
      $response = $classForm->submit();
      if (!$response->getSuccess()) {
        return new Response(
          FALSE,
          $output,
          "Could not create " . get_called_class(
          ) . " entity: " . $response->getMsg()
        );
      }

      // Make sure that there is an id.
      $object = $response->getVar();
      if (!$object->getId()) {
        return new Response(
          FALSE,
          $output,
          "Could not get Id of the created " . get_called_class(
          ) . " entity: " . $response->getMsg()
        );
      }

      // Store the created entity in the output array.
      $output[] = $object;
    }

    return new Response(TRUE, Utils::normalize($output), "");
  }

  public function checkMarkup(
    $testClass,
    $values,
    $skip = array(),
    $view_mode = 'default'
  ) {
    $instances = $this->getFieldInstances();

    $checked_fields = array();
    // Iterate over each field and check markup for those who are also present
    // in $values array.
    foreach ($instances as $field_name => $instance) {
      if (isset($values[$field_name])) {
        if (!in_array($field_name, $skip)) {
          $function = "check" . Utils::makeTitleCase($field_name) . "Markup";
          $this->$function($testClass, $values[$field_name], $view_mode);
        }
        // Field has been checked so add it to $checked_fields array.
        $checked_fields[] = $field_name;
      }
    }

    // Create an array of fields that have not been checked yet.
    $unchecked_fields = array_diff(array_keys($values), $checked_fields);
    $unchecked_fields = array_diff($unchecked_fields, $skip);

    // Unchecked fields could be properties.
    foreach ($unchecked_fields as $field_name) {
      $testClass->assertObjectHasAttribute(
        $field_name,
        $this->entity,
        "Field " . $field_name . " not found."
      );
      $function = "get" . Utils::makeTitleCase($field_name);
      $testClass->assertEquals(
        $values[$field_name],
        $this->$function(),
        "Values of the " . $field_name . " do not match."
      );
      unset($unchecked_fields[$field_name]);
    }

    $this->assertCount(
      0,
      sizeof($unchecked_fields),
      "Following fields or properties could not be found: " . print_r(
        $unchecked_fields,
        TRUE
      )
    );
  }

  /**
   * @param $values
   * @param array $skip
   *
   * @return Response
   *   Response object.
   */
  public function checkValues($values, $skip = array()) {
    $instances = $this->getFieldInstances();

    $checked_fields = array();
    // Iterate over each field and check values for those who are also present
    // in $values array.
    foreach ($instances as $field_name => $instance) {
      if (isset($values[$field_name])) {
        if (!in_array($field_name, $skip)) {
          $function = "check" . Utils::makeTitleCase($field_name) . "Values";
          $response = $this->$function($values[$field_name]);
          if (!$response->getSuccess()) {
            return new Response(
              FALSE,
              NULL,
              "Field " . $field_name . ": " . $response->getMsg()
            );
          }
        }
        // Field has been checked so add it to $checked_fields array.
        $checked_fields[] = $field_name;
      }
    }

    // Create an array of fields that have not been checked yet.
    $unchecked_fields = array_diff(array_keys($values), $checked_fields);
    $unchecked_fields = array_diff($unchecked_fields, $skip);

    // Unchecked fields could be properties.
    foreach ($unchecked_fields as $field_name) {
      if (!property_exists($this->entity, $field_name)) {
        return new Response(
          FALSE, NULL, "Field " . $field_name . " not found."
        );
      }

      $function = "get" . Utils::makeTitleCase($field_name) . "Values";
      if ($this->$function() != $values[$field_name]) {
        return new Response(
          FALSE,
          NULL,
          "Values of " . $field_name . " do not match."
        );
      }

      $unchecked_fields = array_diff($unchecked_fields, array($field_name));
    }

    if (sizeof($unchecked_fields)) {
      return new Response(
        FALSE,
        NULL,
        "Following fields or properties could not be found: " . print_r(
          $unchecked_fields,
          TRUE
        )
      );
    }

    return new Response(TRUE, NULL, "");
  }

  public function checkFieldStructure($testClass) {
    $field_instances = $this->getFieldInstances();

    // Make sure that field instances match.
    $called_class = get_called_class();
    //$testClass->assertEquals(array_keys($field_instances), array_keys($called_class::$fields));

    foreach ($field_instances as $field_name => $instance) {
      $widget = $instance['widget']['type'];
      $field = $this->getFieldInfo($field_name);
      $type = $field['type'];
      $this->assertEquals(
        $called_class::$fields['type'],
        $type,
        "Type of " . $field_name . " does not match."
      );
      $this->assertEquals(
        $called_class::$fields['widget'],
        $widget,
        "Widget of " . $field_name . " does not match."
      );
    }
  }

  public function checkEntityPermissions($testClass, $skip = array()) {
    if (!in_array('view', $skip)) {
      $testClass->assertTrue(
        $this->hasViewAccess(),
        "User does not have permission to view the " . $this->entity_type
      );
    }

    if (!in_array('update', $skip)) {
      $testClass->assertTrue(
        $this->hasUpdateAccess(),
        "User does not have permission to update the " . $this->entity_type
      );
    }

    if (!in_array('delete', $skip)) {
      $testClass->assertTrue(
        $this->hasDeleteAccess(),
        "User does not have permission to delete the " . $this->entity_type
      );
    }
  }

  public function checkFieldPermissions(
    $testClass,
    $viewSkip = array(),
    $editSkip = array()
  ) {
    foreach ($this->getFieldInstances() as $field_name => $instance) {
      if (!in_array($field_name, $viewSkip)) {
        $function = "has" . Utils::makeTitleCase($field_name) . "ViewAccess";
        $testClass->assertTrue(
          call_user_func(array($this, $function)),
          "User does not have view access to " . $field_name
        );
      }
      if (!in_array($field_name, $editSkip)) {
        $function = "has" . Utils::makeTitleCase($field_name) . "UpdateAccess";
        $testClass->assertTrue(
          call_user_func(array($this, $function)),
          "User does not have edit access to " . $field_name
        );
      }
    }
  }

  /**
   * Whether the function name matches the pattern for determining whether user
   * has access to a particular field.
   *
   * @param string $name
   *   Function name.
   *
   * @return bool
   *   TRUE if it matches and FALSE otherwise.
   */
  private function isHasFieldAccessFunction($name) {
    return (Utils::startsWith($name, 'has') && Utils::endsWith(
        $name,
        'Access'
      ));
  }

  /**
   * Whether the function name matches the pattern for determining whether
   * field values need to be returned.
   *
   * @param string $name
   *   Function name.
   *
   * @return bool
   *   TRUE if it matches and FALSE otherwise.
   */
  private function isGetFieldValuesFunction($name) {
    return (Utils::startsWith($name, 'get') && Utils::endsWith(
        $name,
        'Values'
      ));
  }

  /**
   * Whether the function name matches the pattern for determining whether
   * field values need to be checked.
   *
   * @param string $name
   *   Function name.
   *
   * @return bool
   *   TRUE if it matches and FALSE otherwise.
   */
  private function isCheckFieldValuesFunction($name) {
    return (Utils::startsWith($name, 'check') && Utils::endsWith(
        $name,
        'Values'
      ));
  }
}

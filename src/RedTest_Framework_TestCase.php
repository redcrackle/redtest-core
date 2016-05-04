<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 5/30/15
 * Time: 7:03 PM
 */

namespace RedTest\core;


use RedTest\core\entities\User;
use Patchwork;

if (!defined('DRUPAL_ROOT')) {
  /**
   * Drupal root directory.
   */
  define('DRUPAL_ROOT', getcwd());
}
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
// We need to provide a non-empty SERVER_SOFTWARE so that execution doesn't get
// treated as command-line execution by drupal_is_cli() function. If it is
// treated as command-line execution, then drupal_session_start() doesn't invoke
// session_start(). As a result, session_destroy() in User::logout() function
// throws an error. Although this does not affect RedTest execution or even
// session handling, it's better to not let Drupal throw this error in the first
// place.
/*if (empty($_SERVER['SERVER_SOFTWARE'])) {
  drupal_override_server_variables(array('SERVER_SOFTWARE' => 'RedTest'));
}*/
drupal_override_server_variables();
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

Patchwork\redefine('drupal_goto', function($path = '', array $options = array(), $http_response_code = 302) {
  // A destination in $_GET always overrides the function arguments.
  // We do not allow absolute URLs to be passed via $_GET, as this can be an attack vector.
  if (isset($_GET['destination']) && !url_is_external($_GET['destination'])) {
    $destination = drupal_parse_url($_GET['destination']);
    $path = $destination['path'];
    $options['query'] = $destination['query'];
    $options['fragment'] = $destination['fragment'];
  }

  // In some cases modules call drupal_goto(current_path()). We need to ensure
  // that such a redirect is not to an external URL.
  if ($path === current_path() && empty($options['external']) && url_is_external($path)) {
    // Force url() to generate a non-external URL.
    $options['external'] = FALSE;
  }

  drupal_alter('drupal_goto', $path, $options, $http_response_code);

  global $drupal_goto;
  $drupal_goto['path'] = $path;
  $drupal_goto['options'] = $options;
  $drupal_goto['http_response_code'] = $http_response_code;
});

/**
 * Class RedTest_Framework_TestCase
 *
 * @package RedTest\core
 */
class RedTest_Framework_TestCase extends \PHPUnit_Framework_TestCase {

  /**
   * @var array
   */
  protected $backupGlobalsBlacklist = array(
    'user',
    'entities',
    'language',
    'language_url',
    'language_content',
    '_SESSION'
  );

  /**
   * @var bool
   */
  protected static $deleteCreatedEntities = TRUE;

  protected static $deleteMailLog = TRUE;

  public static function tearDownAfterClass() {
    User::logout();
    if (static::$deleteCreatedEntities) {
      Utils::deleteCreatedEntities();
    }
    if (static::$deleteMailLog && module_exists('redtest_helper_mail_logger')) {
      Mail::delete();
    }
  }
}
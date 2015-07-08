<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 12/21/14
 * Time: 12:02 PM
 */

namespace RedTest\core;

use RESTServer;

/**
 * Class Service
 *
 * @package RedTest\core
 */
class Service {

  /**
   * @var RESTServer
   */
  private $server;

  /**
   * @var string
   */
  private $endpoint_name;

  /**
   * @var RESTServerFactory
   */
  private $rest_server_factory;

  /**
   * @var string
   */
  private $endpoint_path;

  /**
   * Default constructor.
   *
   * @param string $endpoint_name
   *   Name of the endpoint. This is the path for the server, for e.g. "rest".
   */
  public function __construct($endpoint_name) {
    module_load_include('inc', 'services', 'includes/services.runtime');
    $this->endpoint_name = $endpoint_name;
    $endpoint = services_endpoint_load($this->endpoint_name);
    $server = $endpoint->server;

    services_set_server_info_from_array(
      array(
        'module' => $server,
        'endpoint' => $this->endpoint_name,
        'endpoint_path' => $endpoint->path,
        'debug' => $endpoint->debug,
        'settings' => $endpoint->server_settings,
      )
    );

    $this->endpoint_path = services_get_server_info(
      'endpoint_path',
      'services/rest'
    );

    $services_rest_server_factory = variable_get(
      'services_rest_server_factory_class',
      'ServicesRESTServerFactory'
    );
    $this->rest_server_factory = new $services_rest_server_factory(
      array('endpoint_path' => $this->endpoint_path)
    );
  }

  /**
   * Execute the servive and return the results.
   *
   * @param string $path
   *   Actual path of the service, for e.g. node/2.json
   * @param array $options
   *   An array of query string.
   *
   * @return string
   *   String output by the service.
   */
  public function execute($path, $options = array()) {
    $_GET = $options;
    $_GET['q'] = $this->endpoint_path . '/' . $path;

    /* @var $rest_server RESTServer */
    $this->server = $this->rest_server_factory->getRESTServer();

    /**
     * @var string
     */
    $result = $this->server->handle($path, $this->endpoint_name);

    unset($_GET);

    return $result;
  }
}
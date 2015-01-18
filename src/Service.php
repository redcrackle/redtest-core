<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 12/21/14
 * Time: 12:02 PM
 */

namespace RedTest\core;


class Service {

  private $server;

  private $endpoint_name;

  public function __construct($endpoint_name) {
    module_load_include('inc', 'services', 'services.runtime');
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

    $this->server = new \RESTServer();
  }

  /**
   * @param $path
   * @param array $options
   *
   * @return string
   */
  public function execute($path, $options = array()) {
    $_GET = $options;
    $_REQUEST = $options;
    /**
     * @var string
     */
    $result = $this->server->handle($path, $this->endpoint_name);
    unset($_GET);
    unset($_REQUEST);
    return $result;
  }
}
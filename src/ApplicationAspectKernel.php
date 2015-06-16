<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 6/13/15
 * Time: 10:59 AM
 */

namespace RedTest\core;

use Go\Core\AspectKernel;
use Go\Core\AspectContainer;
use RedTest\core\Aspect\MonitorAspect;

class ApplicationAspectKernel extends AspectKernel {
  protected function configureAop(AspectContainer $container) {
    xdebug_break();
    $container->registerAspect(new MonitorAspect());
  }
}
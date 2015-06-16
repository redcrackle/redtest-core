<?php

namespace RedTest\core\Aspect;

use Go\Aop\Aspect;
use Go\Aop\Intercept\FieldAccess;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\After;
use Go\Lang\Annotation\Before;
use Go\Lang\Annotation\Around;
use Go\Lang\Annotation\Pointcut;

/**
 * Monitor aspect
 */
class MonitorAspect implements Aspect {

  /**
   * Method that will be called before real method
   *
   * @param MethodInvocation $invocation Invocation
   * @Before("execution(public **->*(*))")
   */
  public function beforeMethodExecution(MethodInvocation $invocation) {
    xdebug_break();
    $obj = $invocation->getThis();
    echo 'Calling Before Interceptor for method: ',
    is_object($obj) ? get_class($obj) : $obj,
    $invocation->getMethod()->isStatic() ? '::' : '->',
    $invocation->getMethod()->getName(),
    '()',
    ' with arguments: ',
    json_encode($invocation->getArguments()),
    "<br>\n";
  }

  /**
   * Fluent interface advice
   *
   * @Around("execution(public **->*(*))")
   *
   * @param MethodInvocation $invocation
   * @return mixed|null|object
   */
  protected function aroundMethodExecution(MethodInvocation $invocation)
  {
    $result = $invocation->proceed();
    return $result!==null ? $result : $invocation->getThis();
  }
}
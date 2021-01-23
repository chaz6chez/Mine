<?php
namespace Example\Common\Aspect;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Before;
use Go\Lang\Annotation\After;
use Go\Lang\Annotation\Around;

/**
 * Logging aspect
 *
 * @see http://go.aopphp.com/blog/2013/07/21/implementing-logging-aspect-with-doctrine-annotations/
 */
class TimeLoggingAspect implements Aspect {

    /**
     * This advice intercepts an execution of loggable methods
     *
     * We use "Before" type of advice to log only class name, method name and arguments before
     * method execution.
     * You can choose your own logger, for example, monolog or log4php.
     * Also you can choose "After" or "Around" advice to access an return value from method.
     *
     * To inject logger into this aspect you can look at Warlock framework with DI+AOP
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Around("@execution(Example\Common\Annotation\TimeLoggable)")
     */
    public function aroundMethodExecution(MethodInvocation $invocation) {
        dump('around');
    }
}
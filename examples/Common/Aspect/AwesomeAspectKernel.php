<?php
namespace Example\Common\Aspect;

use Go\Core\AspectKernel;
use Go\Core\AspectContainer;

/**
 * Awesome Aspect Kernel class
 */
class AwesomeAspectKernel extends AspectKernel {
    /**
     * Configure an AspectContainer with advisors, aspects and pointcuts
     *
     * @param AspectContainer $container
     *
     * @return void
     */
    protected function configureAop(AspectContainer $container) {
        $container->registerAspect(new LoggingAspect());
        $container->registerAspect(new TimeLoggingAspect());
    }
}
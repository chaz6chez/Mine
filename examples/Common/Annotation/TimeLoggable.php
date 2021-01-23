<?php
namespace Example\Common\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class TimeLoggable extends Annotation {

}
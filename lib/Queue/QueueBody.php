<?php
namespace Mine\Queue;

use Structure\Struct;

class QueueBody extends Struct {
    /**
     * @var
     * @required true|class
     */
    public $class;
    /**
     * @var
     * @required true|method
     */
    public $method;
    /**
     * @var
     * @required true|params
     */
    public $params;
}
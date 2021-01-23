<?php
namespace Example\Common\Controller;

use Mine\Base\Controller;
use Mine\Helper\Tools;
use Example\Common\Annotation\TimeLoggable;

class Test extends Controller {

    /**
     * @TimeLoggable
     */
    public function index(){
        $this->output()->success(Tools::uuid());
    }
}
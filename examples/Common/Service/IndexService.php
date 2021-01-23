<?php
namespace Example\Common\Service;

use Mine\Base\Service;
use Example\Common\Annotation\Loggable;

class IndexService extends Service {
    /**
     * @Loggable
     * @return \Mine\Core\Response
     */
    public function A(){
        return $this->response()->success('A');
    }

    public function B(){
        return $this->response()->success('B');
    }

    public function C(){
        return $this->response()->success('C');
    }
}
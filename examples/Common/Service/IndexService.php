<?php
namespace Example\Common\Service;

use Mine\Base\Service;

class IndexService extends Service {
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
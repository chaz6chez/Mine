<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2019/12/2            #
# -------------------------- #
namespace Example\Text\Controller;

use Mine\Base\Controller;
use Mine\Helper\Tools;

class Index extends Controller {

    public function index(){
        $this->output()->success(Tools::uuid());
    }
}
<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/17          #
# -------------------------- #
namespace Mine\Db;

use Mine\Core\Config;
use Mine\Core\Instance;
use Mine\Definition\Define;

class Db extends Instance {

    protected $_servers = [];
    protected $_config  = [];

    /**
     * 初始加载
     */
    protected function _initConfig() {

        $this->_config = Config::get(Define::CONFIG_DB);
        foreach ($this->_config as $name => $conf) {
            if (!isset($this->_servers[$name]) or !$this->_servers[$name] instanceof Connection) {
                $this->_servers[$name] = $this->connect();
            }
        }
    }

    /**
     * 创建连接对象
     * @return Connection
     */
    public function connect(){
        return new Connection();
    }

    /**
     * @param string $name
     * @param array $conf
     * @return Connection
     */
    public function dbName($name = 'default',$conf = []) {
        if(!$conf){
            $conf = $this->_config[$name];
        }
        if (!isset($this->_servers[$name]) and !$this->_servers[$name] instanceof Connection) {
            $this->_servers[$name] = $this->connect();
        }
        try{
            $this->_servers[$name]->setActive($conf);
        }catch (\Exception $e){
            //todo 日志
            log_add("mysql server exception ->{$e->getMessage()}",'MYSQL',__METHOD__);
        }
        return $this->_servers[$name];
    }
}
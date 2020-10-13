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
use Mine\Helper\Tools;

/**
 * 1.配置示例
 *  'db' =>[
 *      'service' => [
 *          'database_type' => env('database.type'),
 *          'server'        => env('database.hostname'),
 *          'username'      => env('database.username'),
 *          'password'      => env('database.password'),
 *          'database_file' => '',
 *          'port'          => env('database.hostport'),
 *          'charset'       => 'utf8',
 *          'database_name' => env('database.database'),
 *          'option'        => [
 *              PDO::ATTR_EMULATE_PREPARES => true,  # 本地预处理
 *              PDO::ATTR_PERSISTENT       => false, # 长连接
 *              PDO::ATTR_TIMEOUT          => 2,
 *          ],
 *          'prefix'        => env('database.prefix'),
 *          'slave'         => [
 *              'database_type' => env('database.type'),
 *              'server'        => env('database.hostname'),
 *              'username'      => env('database.username'),
 *              'password'      => env('database.password'),
 *              'database_file' => '',
 *              'port'          => env('database.hostport'),
 *              'charset'       => 'utf8',
 *              'database_name' => env('database.database'),
 *              'option'        => [
 *                  PDO::ATTR_EMULATE_PREPARES => true,  # 本地预处理
 *                  PDO::ATTR_PERSISTENT       => false, # 长连接
 *                  PDO::ATTR_TIMEOUT          => 2,
 *              ],
 *              'prefix'        => env('database.prefix'),
 *          ],
 *      ],
 *  ]
 * Db
 * Class Db
 * @package Mine\Db
 */
class Db extends Instance {

    protected $_servers = [];
    /**
     * @var array
     */
    protected $_config  = [];

    /**
     * 初始加载
     */
    protected function _initConfig() {
        $this->_config = Config::get(Define::CONFIG_DB);
    }

    /**
     * 创建连接对象
     * @return Connection
     */
    public function connect(){
        return new Connection();
    }

    /**
     * 主数据库
     * @param string $name
     * @param array $conf
     * @return Connection
     */
    public function dbName($name = 'default',$conf = []) {
        if(!$conf){
            $conf = $this->_config[$name];
        }
        if (
            !isset($this->_servers[$name]) or
            !$this->_servers[$name] instanceof Connection
        ) {
            $this->_servers[$name] = $this->connect();
        }
        try{
            $this->_servers[$name]->setActive($conf);
        }catch (\Exception $e){
            $this->_log($e);
        }
        return $this->_servers[$name];
    }

    /**
     * 从数据库
     * @param string $name
     * @param array $conf
     * @return mixed
     */
    public function dbNameSlave($name = 'default',$conf = []){
        if(!$conf){
            $conf = isset($this->_config[$name]['slave']) ?? [];
        }
        if (
            !isset($this->_servers[$name]) and
            !$this->_servers[$name] instanceof Connection
        ) {
            $this->_servers[$name] = $this->connect();
        }
        try{
            $this->_servers[$name]->setActive($conf);
        }catch (\Exception $e){
            $this->_log($e);
        }
        return $this->_servers[$name];
    }

    /**
     * log
     * @param \Exception $exception
     */
    protected function _log(\Exception $exception){
        Tools::log(Define::CONFIG_DB,[
            $exception->getCode(),
            $exception->getMessage()
        ]);
    }
}
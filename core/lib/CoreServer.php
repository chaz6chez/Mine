<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/15            #
# -------------------------- #
namespace core\lib;

use core\App;
use Workerman\Protocols\Http;
use Workerman\WebServer;

/**
 *  1.不使用独立入口php文件做导向
 *  2.Worker start时加载框架程序
 *  3.不支持独立运行的php脚本
 *  4.其他文件格式以文件方式输出
 *
 * Class FastServer
 * @package core\lib
 */
class CoreServer extends WebServer {

    public $allowed        = [];  # 授权的路由
    public $forbidden      = [];  # 拒绝的路由
    public $defaultPath    = '';  # 默认路径
    /**
     * @var App
     */
    private $coreApp       = null;

    /**
     * Run
     */
    public function run() {
        $this->_onWorkerStart = $this->onWorkerStart;
        $this->onClose        = [$this, 'onClose'];
        $this->onConnect      = [$this, 'onConnect'];
        $this->onWorkerStop   = [$this, 'onWorkerStop'];
        $this->onWorkerReload = [$this, 'onWorkerReload'];
        parent::run();
    }

    /**
     * 进程重载
     */
    public function onWorkerReload(){
        $this->coreApp = null;
    }

    /**
     * 进程退出
     */
    public function onWorkerStop(){
        $this->coreApp = null;
    }

    /**
     * Worker启动
     * @throws \Exception
     */
    public function onWorkerStart() {
        if(!defined('WORKER_MAN') or !WORKER_MAN){
            self::safeEcho('WORKER_MAN not defined');
            exit;
        }
        if(!$this->coreApp or !$this->coreApp instanceof App){
            $this->coreApp = new App();
        }
        if($this->defaultPath){
            $this->coreApp->setDefaultRoute($this->defaultPath);
        }
        if($this->allowed){
            $this->coreApp->setAllowedRoute($this->allowed);
        }
        if($this->forbidden){
            $this->coreApp->setForbiddenRoute($this->forbidden);
        }
        $this->coreApp->init();
        parent::onWorkerStart();
    }

    /**
     * @param \Workerman\Connection\TcpConnection $connection
     */
    public function onClose($connection){
        if(DEBUG){
            self::safeEcho("$this->id - $connection->id :closed\n");
        }
    }

    /**
     * @param \Workerman\Connection\TcpConnection $connection
     */
    public function onConnect($connection){
        if(DEBUG){
            self::safeEcho("$this->id - $connection->id :connect\n");
        }
    }

    /**
     * Emit when http message coming.
     *
     * @param \Workerman\Connection\TcpConnection $connection
     * @return void
     */
    public function onMessage($connection) {
        # 域名解析
        $urlInfo = parse_url('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        if (!$urlInfo) {
            Http::header('HTTP/1.1 400 Bad Request');
            $connection->close('<h1>400 Bad Request</h1>');
            return;
        }
        # path解析
        $path                 = isset($urlInfo['path']) ? $urlInfo['path'] : '/';
        $pathInfo             = pathinfo($path);
        $pathInfoExtension    = isset($pathInfo['extension']) ? $pathInfo['extension'] : false;
        $siteConfig           =
            isset($this->serverRoot[$_SERVER['SERVER_NAME']]) ?
                $this->serverRoot[$_SERVER['SERVER_NAME']] :
                current($this->serverRoot);
        $rootDir              = $siteConfig['root'];

        $file = null;
        switch ($pathInfoExtension){
            case 'php':         # php脚本
                Http::header('HTTP/1.1 403 Forbidden');
                $connection->close('<h1>403 Forbidden</h1>');
                return;
                break;
            case 'html':        # html文件
                $file = "{$rootDir}/{$path}";
                break;
            case (false or ''): # 不是文件(框架路由)

                break;
            default:            # 其他文件(图片等)
                $file = "$rootDir/$path";
                break;
        }

        if(isset($siteConfig['additionHeader'])){
            Http::header($siteConfig['additionHeader']);
        }

        # 框架响应
        if($file === null){
            $path = substr($path,strrpos($path,'.php')); # 兼容唯一入口

            $cwd = getcwd();
            chdir($rootDir);
            ini_set('display_errors', 'off');
            ob_start();
            # 执行框架内容响应.
            try {
                # $_SERVER.
                $_SERVER['REMOTE_ADDR'] = $connection->getRemoteIp();
                $_SERVER['REMOTE_PORT'] = $connection->getRemotePort();
                $_SERVER['PATH_INFO']   = $path;
                $this->coreApp->run();
            } catch (\Exception $e) {
                # Jump_exit?
                if ($e->getMessage() != 'jump_exit') {
                    self::safeEcho($e);
                }
            }
            $content = ob_get_clean();
            ini_set('display_errors', 'on');
            if (strtolower($_SERVER['HTTP_CONNECTION']) === "keep-alive") {
                $connection->send($content);
            } else {
                $connection->close($content);
            }
            chdir($cwd);
            return;
        }

        # 文件响应
        if (is_file($file)) {
            # 安全性检查(输出文件锁死在$rootDir)
            if ((
                    !($requestRealPath = realpath($file)) ||
                    !($requestRootPath = realpath($rootDir))) ||
                0 !== strpos($requestRealPath, $requestRootPath)
            ) {
                Http::header('HTTP/1.1 400 Bad Request');
                $connection->close('<h1>400 Bad Request</h1>');
                return;
            }

            $file = realpath($file);
            # 发送文件
            return self::sendFile($connection, $file);
        }

        # 404
        Http::header("HTTP/1.1 404 Not Found");
        if(isset($siteConfig['custom404']) && file_exists($siteConfig['custom404'])){
            $html404 = file_get_contents($siteConfig['custom404']);
        }else{
            $html404 = '<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>';
        }
        $connection->close($html404);
        return;
    }
}
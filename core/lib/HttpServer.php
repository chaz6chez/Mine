<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/15            #
# -------------------------- #
namespace core\lib;

use Workerman\Protocols\Http;
use Workerman\WebServer;

class HttpServer extends WebServer {

    protected $serverRoute = [];          # 服务路由
    protected $defaultFile = 'index.php'; # 默认入口文件
    public $mqStart = false;              # 队列服务器启动
    protected $mqServer = null;

    //todo 服务路由

    /**
     * 设置入口文件
     * @param $fileName
     * @return string
     */
    public function setEntrance($fileName) {
        $fileName = (string)$fileName;
        $file = explode('.', $fileName);
        $fileSuffix = strtolower(array_pop($file));
        if($fileSuffix === 'php'){
            $this->defaultFile = $fileName;
        }
        return $this->defaultFile;
    }

    /**
     * run
     */
    public function run() {
        $this->_onWorkerStart = $this->onWorkerStart;
        $this->onClose        = [$this, 'onClose'];
        $this->onConnect      = [$this, 'onConnect'];
        parent::run();
    }

    /**
     * worker 子进程启动
     * @throws \Exception
     */
    public function onWorkerStart() {
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
        $path              = isset($urlInfo['path']) ? $urlInfo['path'] : '/';
        $pathInfo          = pathinfo($path);
        $parsePath         = explode('/',$path);
        $pathInfoExtension = isset($pathInfo['extension']) ? $pathInfo['extension'] : false;
        $basename          = $path;

        $parsePathInfo     = '/';
        # 如果不是文件
        if(!$pathInfoExtension){
            # 分析第一位是否是入口文件
            $paresEntrance     = (isset($parsePath[1]) and $parsePath[1]) ? $parsePath[1] : $this->defaultFile;
            $pathInfo          = pathinfo($paresEntrance);
            # 如果是php文件
            if(isset($pathInfo['extension']) and $pathInfo['extension'] === 'php'){
                $basename      = $pathInfo['basename'];
                $parsePathInfo = '/'.implode('/',array_slice($parsePath,2));
                $pathInfoExtension = $pathInfo['extension'];
            }
            else{
                foreach ($parsePath as $path){
                    $thePath = pathinfo($path);
                    if(isset($thePath['extension'])){
                        $parsePathInfo = '/';
                        break;
                    }
                    $parsePathInfo = $parsePathInfo = implode('/',$parsePath);
                }
                $basename          = $this->defaultFile;
                $pathInfoExtension = 'php';
            }
        }
        # 如果是php
        else if($pathInfoExtension === 'php'){
            # 请求倒转
            $basename      = array_pop($parsePath);
            $parsePathInfo = implode('/',$parsePath);
        }
        # 设置PATH_INFO
        $_SERVER['PATH_INFO'] = $parsePathInfo;
        $siteConfig = isset($this->serverRoot[$_SERVER['SERVER_NAME']]) ? $this->serverRoot[$_SERVER['SERVER_NAME']] : current($this->serverRoot);
        $rootDir = $siteConfig['root'];
        $file = "$rootDir/$basename";

        if(isset($siteConfig['additionHeader'])){
            Http::header($siteConfig['additionHeader']);
        }
        # 入口文件不存在
        if ($pathInfoExtension === 'php' && !is_file($file)) {
            $file              = "$rootDir/index.html";
            $pathInfoExtension = 'html';
        }
        # 输出文件
        if (is_file($file)) {
            # 安全性检查(输出文件锁死在public内部)
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
            # PHP文件响应
            if ($pathInfoExtension === 'php') {
                $cwd = getcwd();
                chdir($rootDir);
                ini_set('display_errors', 'off');
                ob_start();
                # 尝试include.
                try {
                    // $_SERVER.
                    $_SERVER['REMOTE_ADDR'] = $connection->getRemoteIp();
                    $_SERVER['REMOTE_PORT'] = $connection->getRemotePort();
                    include $file;
                } catch (\Exception $e) {
                    // Jump_exit?
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
            # 发送文件
            return self::sendFile($connection, $file);
        }
        else {
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
}
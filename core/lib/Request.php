<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/18           #
# -------------------------- #
namespace core\lib;

use core\helper\Arr;

class Request {

    protected $files      = [];
    protected $filesPath  = [];
    protected $uploadPath = UPLOAD_PATH;
    protected $fileAllow  = [
        'jpg' => 'image',
        'jpeg' => 'image',
        'png' => 'image',
        'gif' => 'image',
        'bmp' => 'image',
        'mp3' => 'media',
        'wmv' => 'media',
        'wav' => 'video',
        'mp4' => 'video',
        'mov' => 'video',
        'avi' => 'video',
        '3gp' => 'video',
        'rm' => 'video',
        'rmvb' => 'video',
        'xlsx' => 'file',
        'xls' => 'file',
        'doc' => 'file',
        'docx' => 'file',
        'zip' => 'file',
        'rar' => 'file',
        'txt' => 'file',
        'csv' => 'file',
    ];

    /**
     * 获取HTTP头类型
     * @return string
     */
    public function getHttpType() {
        $httpType = (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                &&
                $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return $httpType;
    }

    /**
     * 获取客户端请求的域名地址
     * @param bool $isTop 是否获取顶级域名
     * @return string
     */
    public function getDomain($isTop = false) {
        $domain = $_SERVER['HTTP_HOST'];
        if ($isTop) {
            $arr = explode('.', $domain);
            return $arr[count($arr) - 2] . '.' . $arr[count($arr) - 1];
        }
        return $domain;
    }

    /**
     * 获取uri值
     * @return mixed
     */
    public function getUri() {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * 获取客户端请求的主机域名地址(带http头)
     * @return string
     */
    public function getHost() {
        return $this->getHttpType() . $this->getDomain();
    }

    /**
     * 判断客户端是否是移动终端
     * @return bool
     */
    public function isMobile() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $mobile_agents = [
            "Mobile", "iPhone", "iPad", "iphone", "240x320", "acer",
            "acoon", "acs-", "abacho", "ahong", "airness", "alcatel",
            "amoi", "android", "anywhereyougo.com", "applewebkit/525",
            "applewebkit/532", "applewebkit/536", "asus", "audio",
            "au-mic", "avantogo", "becker", "benq", "bilbo", "bird",
            "blackberry", "blazer", "bleu", "cdm-", "compal", "coolpad",
            "danger", "dbtel", "dopod", "elaine", "eric", "etouch",
            "fly ", "fly_", "fly-", "go.web", "goodaccess", "gradiente",
            "grundig", "haier", "hedy", "hitachi", "htc", "huawei",
            "hutchison", "inno", "ipad", "ipaq", "ipod", "jbrowser",
            "kddi", "kgt", "kwc", "lenovo", "lg ", "lg2", "lg3", "lg4",
            "lg5", "lg7", "lg8", "lg9", "lg-", "lge-", "lge9", "longcos",
            "maemo", "mercator", "meridian", "micromax", "midp", "mini",
            "mitsu", "mmm", "mmp", "mobi", "mot-", "moto", "nec-",
            "netfront", "newgen", "nexian", "nf-browser", "nintendo",
            "nitro", "nokia", "nook", "novarra", "obigo", "palm",
            "panasonic", "pantech", "philips", "phone", "pg-",
            "playstation", "pocket", "pt-", "qc-", "qtek", "rover",
            "sagem", "sama", "samu", "sanyo", "samsung", "sch-",
            "scooter", "sec-", "sendo", "sgh-", "sharp", "siemens",
            "sie-", "softbank", "sony", "spice", "sprint", "spv",
            "symbian", "tablet", "talkabout", "tcl-", "teleca",
            "telit", "tianyu", "tim-", "toshiba", "tsm", "up.browser",
            "utec", "utstar", "verykool", "virgin", "vk-", "voda",
            "voxtel", "vx", "wap", "wellco", "wig browser", "wii",
            "windows ce", "wireless", "xda", "xde", "zte"
        ];
        $is_mobile = false;
        foreach ($mobile_agents as $device) {
            if (stristr($user_agent, $device)) {
                $is_mobile = true;
                break;
            }
        }
        return $is_mobile;
    }

    /**
     * 获取客户端访客IP地址
     * @param int $type 返回类型 0:返回IP地址 1:返回IPV4地址数字
     * @param bool $adv
     * @return mixed
     */
    public function getIp($type = 0, $adv = false) {
        $type = $type ? 1 : 0;
        static $ip = null;
        if ($ip !== null) {
            return $ip[$type];
        }
        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[$type];
    }

    /**
     * 判断是否是post请求
     * @return bool
     */
    public function isPost() {
        return (strtolower($_SERVER['REQUEST_METHOD']) == 'post') ? true : false;
    }

    /**
     * 判断是否是get请求
     * @return bool
     */
    public function isGet() {
        return (strtolower($_SERVER['REQUEST_METHOD']) == 'get') ? true : false;
    }

    /**
     * 判断是否ajax请求
     * @return boolean
     */
    public function isAjax() {
        if (
            //常规ajax
            isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            or
            //json
            strpos(strtolower(isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : ''), 'json') != false
            //todo 其他再此拓展
        ) {
            return true;
        }
        return false;
    }

    /**
     * worker man GET
     * @param string $key
     * @param string $default
     * @return string
     */
    public function get($key = '', $default = ''){
        if ($key) {
            return isset($_GET[$key]) ? $_GET[$key] : $default;
        }
        return $_GET;
    }

    /**
     * worker man POST
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getPost($key = '', $default = ''){
        if ($key) {
            return isset($_POST[$key]) ? $_POST[$key] : $default;
        }
        return $_POST;
    }

    /**
     * worker man REQUEST
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getAll($key = '', $default = ''){
        if ($key) {
            return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
        }
        return $_REQUEST;
    }

    /**
     * 获取上传的文件信息
     * @param string $name
     * @return array|mixed
     */
    public function getFile($name = '') {
        $file = isset($_FILES) ? $_FILES : [];
        $files = [];
        if($file){
            foreach ($file as $f){
                //todo 文件二进制流筛选过滤，当前仅简单处理
                $realType = file_real_type($f['file_data']);
                if(!array_key_exists($realType,$this->fileAllow)){
                    $type = substr($f['file_type'],strpos($f['file_type'],'/') + 1);
                    if(!array_key_exists($type,$this->fileAllow)){
                        wm_403("Illegal file{$f['file_name']}");
                    }
                }
                //todo 文件大小的限制

                $files[$f['name']][$f['file_name']] = $f;
            }
        }
        $this->files = Arr::merge($this->files,$files);
        if($name and isset($this->files[$name])){
            return $this->files[$name];
        }
        return $this->files;
    }

    /**
     * 上传文件的本地保存
     * @param string $name
     * @param string $fileName
     * @param null $public
     * @return array|string
     * 定位具体文件名  string 文件地址
     * 其他情况返回    array  文件地址集合
     */
    public function filesPath($name = '',$fileName = '',$public = null){
        if(!$this->files){
            $this->getFile();
        }
        if(!is_dir($this->uploadPath)){
            if(!mkdir($this->uploadPath,0755,true)){
                wm_500("directory {$this->uploadPath} creation failed");
            }
        }
        $paths = [];
        $string = new_token(uniqid('.,#$%^&@'));
        if(!$public){
            $path = str_replace(PUBLIC_PATH,'',$this->uploadPath);
        }else{
            $path = str_replace($public,'',$this->uploadPath);
        }
        if(!$name){
            foreach ($_FILES as $file){
                $fileRename = $string.'.'.file_suffix($file['file_name']);
                if(file_put_contents($this->uploadPath.$fileRename, $file['file_data'])){
                    $paths[$file['name']][] = $path.$fileRename;
                }
            }
            $this->filesPath = Arr::merge($this->filesPath,$paths);
        }
        if(isset($this->files[$name])){
            if(!$fileName){
                foreach ($this->files[$name] as $file){
                    $fileRename = $string.'.'.file_suffix($file['file_name']);
                    file_put_contents($this->uploadPath.$fileRename, $file['file_data']);
                    $paths[$name][] = $path.$fileRename;
                }
                $this->filesPath = Arr::merge($this->filesPath,$paths);

            }else if(isset($this->files[$name][$fileName])){
                file_put_contents($this->uploadPath.$fileName, $this->files[$name][$fileName]['file_data']);
                $paths[$name][$fileName] = $path.$fileName;
                $this->filesPath = Arr::merge($this->filesPath,$paths);
                return $this->uploadPath.$fileName;
            }
        }
        return $this->filesPath;
    }

    /**
     * 仅保存每个key的第一个文件并返回路径
     * @param $name
     * @param null $path
     * @param bool $public
     * @return string
     */
    public function getFilePath($name,$path = null,$public = null){
        if(!$this->files){
            $this->getFile();
        }
        if($path){
            $this->uploadPath = $path;
        }
        if(!is_dir($this->uploadPath)){
            if(!mkdir($this->uploadPath,0755,true)){
                wm_500("directory {$this->uploadPath} creation failed");
            }
        }
        $filePath = '';
        $string = new_token(uniqid('.,#$%^&@'));
        if(!$public){
            $path = str_replace(PUBLIC_PATH,'',$this->uploadPath);
        }else{
            $path = str_replace($public,'',$this->uploadPath);
        }

        if(isset($this->files[$name])){
            $file = $this->files[$name];
            $file = array_shift($file);
            $fileRename = $string.'.'.file_suffix($file['file_name']);
            file_put_contents($this->uploadPath.$fileRename, $file['file_data']);
            $filePath = $path.$fileRename;
        }
        return $filePath;
    }

    /**
     * 设置TmpPath
     * @param $path
     * @return $this
     */
    public function setTmpPath($path){
        if(!is_dir($path)){
            if(!mkdir($path,0755,true)){
                wm_500("directory {$path} creation failed");
            }else{
                $this->uploadPath = $path;
            }
        }
        return $this;
    }
}
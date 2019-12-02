<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19            #
# -------------------------- #

/**
 * 进程超时检查函数
 */
if(!function_exists('process_time_checker')){
    /**
     * @param int $timeout 秒
     */
    function process_timeout_checker(int $timeout = 5){
        global $PROCESS_TIME;
        $PROCESS_TIME = time();
        declare(ticks=1);
        register_tick_function(function ($timeout){
            global $PROCESS_TIME;
            if(time() - $PROCESS_TIME > $timeout){
                exit("Timeout {$timeout} seconds");
            }
        },$timeout);
    }
}

/**
 * 打印调试
 */
if (!function_exists('dump')) {
    /**
     * @param $var
     * @param array $moreVars
     * @return array|mixed
     */
    function dump($var, $moreVars = []) {
        return \Mine\Helper\Tools::Dump($var, $moreVars);
    }
}

/**
 * 判断是否是unix时间戳
 */
if (!function_exists('is_timestamp')){
    /**
     * @param $timestamp
     * @return bool
     */
    function is_timestamp($timestamp) {
        if(strtotime(date('Y-m-d H:i:s', $timestamp)) === $timestamp) {
            return $timestamp;
        } else {
            return false;
        }
    }
}

/**
 * 日志
 *
 *  例：
 *      log_add('welcome', $GLOBALS['API_MODULE'], __METHOD__);
 *  日志保存在该文件同级的log目录下，保存内容为：
 *      06:07:14 [Api\V1\Controller\Index::index] welcome
 *
 */
if (!function_exists('log_add')) {

    /**
     * @param array|int|string|object $msg
     * @param null|string $module
     * @param null|string $tag
     * @return bool|int
     */
    function log_add($msg, $module = null, $tag = null) {
        $dir = defined('LOG_PATH') ? LOG_PATH :  __DIR__ . '/log'; # 默认目录
        $name = date('Y_m_d').'.log';                             # 默认文件名
        $tag = $tag ? $tag : 'LOG';                                      # 默认标记

        if($module){
            $dir = "{$dir}/{$module}";
        }

        if(!is_dir($dir)){
            if(!mkdir($dir,0755,true)){
                return false;
            }
        }
        if($msg instanceof Exception){
            $msg = $msg->getMessage() . ':' . $msg->getCode();
        }else{
            $msg = is_scalar($msg) ? (string)$msg : json_encode($msg,JSON_UNESCAPED_UNICODE);
        }

        if(file_exists($path = "{$dir}/{$name}")){
            return file_put_contents($path, date('H:i:s') . " [{$tag}] {$msg}\n",FILE_APPEND | LOCK_EX);
        }else{
            return file_put_contents($path, date('H:i:s') . " [{$tag}] {$msg}\n", LOCK_EX);
        }
    }
}

/**
 * 设置配置文件
 */
if (!function_exists('set_config')) {
    /**
     * @param $key
     * @param null $value
     * @return array|mixed|null
     * @throws Exception
     */
    function set_config($key, $value = null) {
        if (is_null($value)) {
            return \Mine\Core\Config::get($key);
        }
        return \Mine\Core\Config::set($key, $value);
    }
}

/**
 * 驼峰转下划线
 */
if(!function_exists('camel2lower')) {
    /**
     * @param $str
     * @return string
     */
    function camel2lower($str) {
        return \Mine\Helper\Tools::CamelToLower($str);
    }
}

/**
 * 下划线转驼峰
 */
if(!function_exists('lower2camel')) {
    /**
     * @param $str
     * @return string
     */
    function lower2camel($str) {
        return \Mine\Helper\Tools::LowerToCamel($str);
    }
}

/**
 * 创建PHP文件
 */
if(!function_exists('build_php')){
    /**
     * @param $path
     * @return string
     */
    function build_php($path) {
        $content = '<?php ';
        $path = (array)$path;
        $files = [];

        foreach ($path as $p) {
            $files = array_merge($files, glob($p . '*.php'));
            $files = array_merge($files, glob($p . '*/*.php'));
        }

        foreach ($files as $f) {
            $c = php_strip_whitespace($f);
            $c = trim(str_replace(['<?php', '?>'], '', $c));
            $reg = '/^\s*(namespace\s+.+?);/sm';
            if (preg_match($reg, $c)) {
                $c = preg_replace($reg, '$1 {', $c) . "}\n";
            } else {
                $c = "namespace {" . $c . "}\n";
            }
            $content .= $c;
        }
        return $content;
    }
}

/**
 * 根据PHP各种类型变量生成唯一标识号
 */
if(!function_exists('to_guid_string')){
    /**
     * @param mixed $mix 变量
     * @return string
     */
    function to_guid_string($mix) {
        if (is_object($mix)) {
            return spl_object_hash($mix);
        } elseif (is_resource($mix)) {
            $mix = get_resource_type($mix) . strval($mix);
        } else {
            $mix = serialize($mix);
        }
        return md5($mix);
    }
}

/**
 * 数组转xml
 */
if(!function_exists('array2xml')) {
    /**
     * @param $arr
     * @return string
     */
    function array2xml($arr) {
        return \Mine\Helper\Tools::ArrayToXml($arr);
    }
}

/**
 * 数组与字符串之间的相互简易转换
 */
if(!function_exists('arr_str')) {
    /**
     * @param $input
     * @param string $tag
     * @return array|string
     */
    function arr_str($input,$tag = 'ARRAY') {
        if(is_array($input)){
            return $tag.serialize($input);
        }
        return unserialize(mb_substr($input,mb_strlen($tag)));
    }
}

/**
 * 数组与字符串之间的相互简易转换
 */
if(!function_exists('arr_uri')) {
    /**
     * @param array|string $input
     * @return array|string
     */
    function arr_uri($input) {
        if(is_array($input)){
            $uri = '';
            foreach ($input as $k => $v){
                $uri .= "&{$k}={$v}";
            }
            return ltrim($uri,'&');
        }
        $input = explode('&',$input);
        $array = [];
        foreach ($input as $v){
            $v = explode('=',$v);
            if (count($v) > 1) $array[$v[0]] = $v[1];
        }
        return $array;
    }
}

/**
 * 对象转数组
 */
if(!function_exists('object2array')) {
    /**
     * @param $object
     * @return array
     */
    function object2array($object) {
        return json_decode(json_encode($object), true);
    }
}

/**
 * 获取request实例
 */
if (!function_exists('request')) {
    /**
     * @return \Mine\Core\Request
     */
    function request()
    {
        return new \Mine\Core\Request();
    }
}

/**
 * json post请求器
 */
if(!function_exists('http_post_json')){
    /**
     * curl post json
     * @param $url
     * @param $json
     * @param int $timeOut
     * @param array $extraHeader
     * @return array
     */
    function http_post_json($url, $json, $timeOut = 30, $extraHeader = []){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);            # 超时时间
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);        # 文件流返回
        $header = [
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($json)
        ];
        if ($extraHeader and is_array($extraHeader)) {
            foreach ($extraHeader as $key => $v) {
                $header[] = $key . ':' . $v;
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $res = curl_exec($ch);
        if(curl_errno($ch)) {       # 错误捕获
            return [ false, curl_error($ch)];
        }
        curl_close($ch);
        return [ true, $res];
    }
}

/**
 * 判断是否是序列化字符串
 */
if(!function_exists('is_serialize')){

    /**
     * @param $data
     * @return bool
     */
    function is_serialized($data) {
        if (is_array($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data)
            return true;
        if (!preg_match('/^([adObis]):/', $data, $badions))
            return false;
        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }
}

/**
 * 获取当前毫秒时间
 */
if(!function_exists('get_millisecond')){
    /**
     * @return float
     */
    function get_millisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
}

/**
 * 获取内存占用
 */
if(!function_exists('get_memory_used')){
    /**
     * @return float
     */
    function get_memory_used(){
        return \Mine\Helper\Tools::getMemoryUsed();
    }
}

/**
 * 获取变量名
 */
if(!function_exists('get_variable_name')){
    /**
     * @param $var
     * @param null|object|array $scope
     * @return false|int|string
     */
    function get_variable_name(&$var, $scope = null){
        $scope = $scope==null? $GLOBALS : $scope;
        $tmp = $var;
        $var = 'tmp_value_'.mt_rand();
        if(is_object($scope)){
            $scope = object2array($scope);
        }
        $name = array_search($var, $scope,true);
        $var = $tmp;
        return $name;
    }
}

/**
 * WorkerMan 断开通讯 exit
 */
if(!function_exists('wm_close')){

    function wm_close(){
        \Mine\Helper\Tools::Close();
    }
}


/**
 * WorkerMan 子进程 exit
 */
if(!function_exists('wm_end')){

    function wm_end($msg = '',$close = false){
        \Mine\Helper\Tools::End($msg, $close);
    }
}

/**
 * WorkerMan 控制台输出 debug下
 */
if(!function_exists('cli_echo_debug')){

    /**
     * @param string $msg
     * @param string $tag
     * @return array|mixed
     */
    function cli_echo_debug($msg = '',$tag = '#'){
        return \Mine\Helper\Tools::SafeEcho($msg, $tag);
    }
}

/**
 * WorkerMan header
 */
if(!function_exists('wm_header')){
    /**
     * @param $content
     * @param bool $replace
     * @param int $http_response_header
     */
    function wm_header($content,$replace = true,$http_response_header = 0){
        \Mine\Helper\Tools::Header($content, $replace, $http_response_header);
    }
}

/**
 * WorkerMan 500
 */
if(!function_exists('wm_500')){
    /**
     * @param string $msg
     */
    function wm_500($msg = '500 Internal Server Error'){
        \Mine\Helper\Tools::Http500($msg);
    }
}

/**
 * WorkerMan 404
 */
if(!function_exists('wm_404')){
    /**
     * @param string $msg
     */
    function wm_404($msg = '404 Not Found'){
        \Mine\Helper\Tools::Http404($msg);
    }
}

/**
 * WorkerMan 403
 */
if(!function_exists('wm_403')){
    /**
     * @param string $msg
     */
    function wm_403($msg = '403 Forbidden'){
        \Mine\Helper\Tools::Http403($msg);
    }
}

/**
 * WorkerMan echo
 */
if(!function_exists('safe_echo')){
    /**
     * @param string $msg
     * @param bool $decorated
     */
    function safe_echo($msg = 'echo', $decorated = false){
        \Workerman\Worker::safeEcho("$msg\n", $decorated);
    }
}

/**
 * 生成token
 */
if(!function_exists('new_token')){
    /**
     * @param int $uid
     * @return string
     */
    function new_token($uid = 0){
        return \Mine\Helper\Tools::randomString($uid);
    }
}

/**
 * 生成sn
 */
if(!function_exists('new_sn')){
    /**
     * @param int $uid
     * @return string
     */
    function new_sn($uid = 0){
        if($uid != 0){
            return md5(rand(0,10000).$uid.uniqid(microtime(true),true));
        }
        return md5(rand(0,10000).uniqid(microtime(true),true));
    }
}

if(!function_exists('format_money')){
    /** 保留位数，截取不四舍五入
     * @param $num
     * @param $precision
     * @return float
     */
    function format_money($num,$precision = COIN_UNIT){
        return floatval(substr(sprintf('%.'.($precision+1).'f', $num), 0, -1));
    }
}

if(!function_exists('float_comps')){
    /**
    比较两个浮点数是否相等，最后一个参数只和等于前面所有数之和
    @return int $a==$b 返回 0 | $a<$b 返回 -1 | $a>$b 返回 1
     */
    function float_comps($a,$b){
        $args = func_get_args();
        $num = func_num_args();
        $sum = '0';
        for($i=0;$i<$num-1;$i++){
            $sum = bcadd($sum,sprintf('%.'.COIN_UNIT.'f', $args[$i]),COIN_UNIT);
        }
        return bccomp($sum,sprintf('%.'.COIN_UNIT.'f', $args[$num-1]),COIN_UNIT);
    }
}

if(!function_exists('float_adds')){
    /**
    求多个参数之和
    @return float 所有之和
     */
    function float_adds($a,$b){
        $args = func_get_args();
        $num = func_num_args();
        $sum = '0';
        for($i=0;$i<$num;$i++){
            $sum = bcadd($sum,sprintf('%.'.COIN_UNIT.'f', $args[$i]),COIN_UNIT);
        }
        return $sum;
    }
}

if(!function_exists('float_muls')){
    /**
    求多个参数之积
    @return float 所有之积
     */
    function float_muls($a,$b){
        $args = func_get_args();
        $num = func_num_args();
        $sum = '1';
        for($i=0;$i<$num;$i++){
            $sum = bcmul($sum,sprintf('%.'.COIN_UNIT.'f', $args[$i]),COIN_UNIT);
        }
        return $sum;
    }
}

/**
 * 高精度除法 a / b 舍去
 */
if(!function_exists('float_bcdiv')){
    /** 高精度除法 a / b
     * @param $a
     * @param $b
     * @return string
     */
    function float_bcdiv($a,$b){
        return bcdiv(sprintf('%.'.COIN_UNIT.'f', $a), sprintf('%.'.COIN_UNIT.'f', $b), COIN_UNIT);
    }
}

/**
 * 高精度除法 a / b 四舍五入
 */
if(!function_exists('bcdiv_new')){
    /**
     * @param $a
     * @param $b
     * @param int $scale
     * @param bool $number_format
     * @return string|null
     */
    function bcdiv_new($a,$b,int $scale = 8, $number_format = false){
        if ($number_format) {
            return number_format(bcdiv($a,$b,$scale + 1),$scale);
        }
        return bcdiv($a,$b,$scale + 1);
    }
}

/**
 * 获取首字母
 */
if(!function_exists('first_chart')){
    /**
     * @param $str
     * @return bool|string
     */
    function first_chart($str) {
        $str = iconv('UTF-8','gb2312', $str);
        if (preg_match("/^[\x7f-\xff]/", $str)) {
            $firstChar = ord($str[0]);
            if( $firstChar >= ord("A") and
                $firstChar <= ord("z")
            ){
                return strtoupper($str[0]);
            }
            $a = $str;
            $val = ord($a[0]) * 256 + ord($a[1]) - 65536;
            if($val>=-20319 and $val<=-20284)return "A";
            if($val>=-20283 and $val<=-19776)return "B";
            if($val>=-19775 and $val<=-19219)return "C";
            if($val>=-19218 and $val<=-18711)return "D";
            if($val>=-18710 and $val<=-18527)return "E";
            if($val>=-18526 and $val<=-18240)return "F";
            if($val>=-18239 and $val<=-17923)return "G";
            if($val>=-17922 and $val<=-17418)return "H";
            if($val>=-17417 and $val<=-16475)return "J";
            if($val>=-16474 and $val<=-16213)return "K";
            if($val>=-16212 and $val<=-15641)return "L";
            if($val>=-15640 and $val<=-15166)return "M";
            if($val>=-15165 and $val<=-14923)return "N";
            if($val>=-14922 and $val<=-14915)return "O";
            if($val>=-14914 and $val<=-14631)return "P";
            if($val>=-14630 and $val<=-14150)return "Q";
            if($val>=-14149 and $val<=-14091)return "R";
            if($val>=-14090 and $val<=-13319)return "S";
            if($val>=-13318 and $val<=-12839)return "T";
            if($val>=-12838 and $val<=-12557)return "W";
            if($val>=-12556 and $val<=-11848)return "X";
            if($val>=-11847 and $val<=-11056)return "Y";
            if($val>=-11055 and $val<=-10247)return "Z";
        } else {
            return strtoupper(mb_substr($str,0,1));
        }
    }
}

/**
 * 多维数组分类排序
 */
if(!function_exists('array_group_sort')){

    /**
     * @param array $array
     * @param string $key
     * @param int $type 降序：SORT_DESC 升序 SORT_ASC
     * @return array|bool
     */
    function array_group_sort(array $array,$key,$type = SORT_ASC) {
        $column = array_column($array,$key);
        if(!$column){
            return false;
        }
        if(array_multisort($column,$type,$array)){
            return $array;
        }
        return false;
    }
}

/**
 * 向列表数组中插入group字段
 */
if(!function_exists('array_add_group')){

    /**
     * @param array $array
     * @param string $key
     */
    function array_add_group(array &$array,$key) {
        if($array){
            foreach ($array as &$v) {
                if (isset($v[$key]) and $g = first_chart($v[$key])) {
                    $v['group'] = $g;
                } else {
                    $v['group'] = '#';
                }
            }
        }
    }
}

/**
 * 统一时间格式输出
 */
if(!function_exists('to_date')){

    /**
     * @param int $time
     * @return false|string
     */
    function to_date($time) {
        if(!$time){
            return '';
        }
        return date('Y-m-d H:i:s',$time);
    }
}

/**
 * 文件格式判断
 */
if(!function_exists('file_real_type')){

    /**
     * @param $fileData
     * @return string
     */
    function file_real_type($fileData) {
        $info = @unpack('C2chars', $fileData);
        $typeCode = intval($info['chars1'].$info['chars2']);
        switch ($typeCode) {
            case 7790:
                $fileType = 'exe';
                break;
            case 7784:
                $fileType = 'midi';
                break;
            case 8075:
                $fileType = 'zip';
                break;
            case 8297:
                $fileType = 'rar';
                break;
            case 255216:
                $fileType = 'jpg';
                break;
            case 7173:
                $fileType = 'gif';
                break;
            case 6677:
                $fileType = 'bmp';
                break;
            case 13780:
                $fileType = 'png';
                break;
            default:
                $fileType = 'unknown';
                break;
        }
        return $fileType;
    }
}

/**
 * 获取后缀文件
 */
if(!function_exists('file_suffix')){

    /**
     * @param $fileName
     * @param bool $path
     * @return bool|string
     */
    function file_suffix($fileName,$path = false) {
        if($path){
            return pathinfo($fileName,PATHINFO_EXTENSION);
        }
        return substr(strrchr($fileName, '.'), 1);
    }
}

/**
 * 二维数组搜索
 */
if(!function_exists('search_in_array')){

    /**
     * @param array $array 数组
     * @param array $where ['关键字'=>'值']
     * @param bool $get 是否获取该数组值
     * @return array|false|int|mixed|string
     */
    function search_in_array(array $array,array $where,$get = false) {
        $key = key($where);
        $val = $where[$key];
        $column = array_column($array,$key);
        if($column){
            $arrayKey = array_search($val,$column);
            if($get){
                return $array[$arrayKey];
            }
            return $arrayKey;
        }
        return $column;
    }
}

/**
 * 是否是JSON
 */
if(!function_exists('is_json')){

    /**
     * @param $string
     * @param bool $get
     * @return bool|mixed
     */
    function is_json($string,$get = false){
        return \Mine\Helper\Tools::isJson($string, $get);
    }
}

/**
 * 去除空格
 */
if(!function_exists('trimall')){
    /**
     * @param $str
     * @return mixed
     */
    function trimall($str) {
        return preg_replace('# #','',$str);
    }
}

/**
 * 获取错误码
 */
if(!function_exists('error_code')){

    /**
     * @param $msg
     * @return mixed|null
     */
    function error_code($msg) {
        $err = explode('|',$msg);
        if(is_array($err) and count($err) > 1){
            return $err[0];
        }
        return $msg;
    }
}

/**
 * 获取提示信息
 */
if(!function_exists('notice_msg')){

    /**
     * @param $msg
     * @return mixed|null
     */
    function notice_msg($msg) {
        $data = explode('|',$msg);
        if(is_array($data) and count($data) > 1){
            return $data[1];
        }
        return $msg;
    }
}

/**
 * 密码检查
 */
if(!function_exists('password_checker')){

    /**
     * @param $password
     * @param string $preg
     * @return false|int
     */
    function password_checker($password,$preg = '/^(?=.*[A-Za-z])(?=.*\d)[\s\S]{8,}$/') {
        # 默认 至少8个字符，1个字母和1个数字，其他可以是任意字符
        return preg_match($preg,$password);
    }
}

/**
 * 获取返回信息的 tag
 */
if(!function_exists('get_tag')){

    /**
     * @param $string
     * @return mixed
     */
    function get_tag($string) {
        preg_match_all('/\[(?<tag>[\s\S]*?)\]/',$string,$res);
        return isset($res['tag'][0]) ? $res['tag'][0] : null;
    }
}

/**
 * 设置返回信息的 tag
 */
if(!function_exists('set_tag')){

    /**
     * @param string $string
     * @param string $tag
     * @return string
     */
    function set_tag(string $string,string $tag) {
        if($tag){
            return "{$string}[{$tag}]";
        }
        return $string;
    }
}

/**
 * 内容解密
 */
if(!function_exists('base64_urlencode')){
    /**
     * @param $string
     * @return mixed|string
     */
    function base64_urlencode($string) {
        $data = base64_encode($string);
        $data = str_replace(
            ['+','/','='],
            ['-','_',''],
            $data
        );
        return $data;
    }
}


/**
 * 内容加密
 */
if(!function_exists('base64_urldecode')){

    /**
     * @param $string
     * @return bool|string
     */
    function base64_urldecode($string) {
        $data = str_replace(
            ['-','_'],
            ['+','/'],
            $string
        );
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}

/**
 * 内容解密
 */
if(!function_exists('lock_decode')){

    /**
     * @param $txt
     * @param string $key
     * @return string
     */
    function lock_decode($txt,$key = 'ukexpay_workerman') {
        $txt = passport_key(base64_urldecode((string)$txt), $key);
        $tmp = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $tmp .= $txt[$i] ^ $txt[++$i];
        }
        return $tmp;
    }
}

/**
 * 内容加密
 */
if(!function_exists('lock_encode')){

    /**
     * @param $txt
     * @param string $key
     * @return string
     */
    function lock_encode($txt,$key = 'ukexpay_workerman') {
        srand((double)microtime() * 1000000);
        $encrypt_key = md5(rand(0, 32000));
        $ctr = 0;
        $tmp = '';
        for($i = 0; $i < strlen($txt); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $encrypt_key[$ctr].($txt[$i] ^ $encrypt_key[$ctr++]);
        }
        return base64_urlencode(passport_key($tmp, $key));
    }
}

/**
 * Passport 密匙处理函数
 */
if(!function_exists('passport_key')){
    /**
     * @param $txt
     * @param $encrypt_key
     * @return string
     */
    function passport_key($txt, $encrypt_key) {
        $encrypt_key = md5($encrypt_key);
        $ctr = 0;
        $tmp = '';
        for($i = 0; $i < strlen($txt); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $txt[$i] ^ $encrypt_key[$ctr++];
        }
        return $tmp;
    }
}

/**
 * Passport 信息(数组)编码函数
 */
if(!function_exists('passport_encode')){
    /**
     *
     * @param                array           待编码的数组
     *
     * @return       string          数组经编码后的字串
     */
    function passport_encode($array) {

        // 数组变量初始化
        $arrayenc = array();

        // 遍历数组 $array，其中 $key 为当前元素的下标，$val 为其对应的值
        foreach($array as $key => $val) {
            // $arrayenc 数组增加一个元素，其内容为 "$key=经过 urlencode() 后的 $val 值"
            $arrayenc[] = $key.'='.urlencode($val);
        }

        // 返回以 "&" 连接的 $arrayenc 的值(implode)，例如 $arrayenc = array('aa', 'bb', 'cc', 'dd')，
        // 则 implode('&', $arrayenc) 后的结果为 ”aa&bb&cc&dd"
        return implode('&', $arrayenc);
    }
}


/**
 * 环境变量获取
 */
if(!function_exists('env')){
    /**
     * @param null $name
     * @param null $default
     * @return array|bool|false|mixed|null|string
     */
    function env($name = null,$default = null) {
        \Mine\Core\Env::init();
        return \Mine\Core\Env::get($name,$default);
    }
}

/**
 * response检查
 */
if(!function_exists('response_checker_add')){
    /**
     * @param \Mine\Core\Response $response
     * @return \Mine\Core\Response
     */
    function response_checker_add(\Mine\Core\Response $response) {
        return $GLOBALS['RESPONSE_QUEUE'][] = $response;
    }
}

/**
 * response检查
 */
if(!function_exists('response_checker_do')){

    /**
     * @return \Mine\Core\Response
     */
    function response_checker_do() {
        if(is_array($GLOBALS['RESPONSE_QUEUE']) and $GLOBALS['RESPONSE_QUEUE']){
            foreach ($GLOBALS['RESPONSE_QUEUE'] as $key => $response){
                unset($GLOBALS['RESPONSE_QUEUE'][$key]);
                if($response instanceof \Mine\Core\Response){
                    if($response->hasError()){
                        return $response;
                    }
                }
            }
        }
        $GLOBALS['RESPONSE_QUEUE'] = null;
        return new \Mine\Core\Response();
    }
}

/**
 * response检查
 */
if(!function_exists('response_checker_do')){

    /**
     * @return \Mine\Core\Response|mixed
     */
    function response_checker_do() {
        if(is_array($GLOBALS['RESPONSE_QUEUE']) and $GLOBALS['RESPONSE_QUEUE']){
            foreach ($GLOBALS['RESPONSE_QUEUE'] as $key => $response){
                unset($GLOBALS['RESPONSE_QUEUE'][$key]);
                if($response instanceof \Mine\Core\Response){
                    if($response->hasError()){
                        return $response;
                    }
                }
            }
        }
        $GLOBALS['RESPONSE_QUEUE'] = null;
        return new \Mine\Core\Response;
    }
}

/**
 * 数组key中下划线替换为中横线
 */
if(!function_exists('array_key_replace')){

    /**
     * @param array $array
     * @return array
     */
    function array_key_replace(array $array) {
        $result = [];
        foreach ($array as $key => $value){
            $k = str_replace('_','-',$key);
            $result[$k] = $value;
        }
        return $result;
    }
}

if(!function_exists('get_float_length')){

    /**
     * @param $num
     * @return int
     */
    function get_float_length($num) {
        $count = 0;
        $temp = explode ( '.', (string) $num );
        if (sizeof ( $temp ) > 1) {
            $decimal = end ( $temp );
            $count = strlen ( $decimal );
        }
        return $count;
    }
}
<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19            #
# -------------------------- #
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
        \Symfony\Component\VarDumper\VarDumper::dump($var);

        foreach ($moreVars as $var) {
            \Symfony\Component\VarDumper\VarDumper::dump($var);
        }

        if (1 < func_num_args()) {
            return func_get_args();
        }

        return $var;
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
            return \core\lib\Config::get($key);
        }
        return \core\lib\Config::set($key, $value);
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
        return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $str), '_'));
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
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $str));
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
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . array2xml($val) . "</" . $key . ">";
            } elseif (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
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
     * @return \core\lib\Request
     */
    function request()
    {
        return new \core\lib\Request();
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
        return round(memory_get_usage(false) / 1024 / 1024, 2);
    }
}

/**
 * WorkerMan 断开通讯 exit
 */
if(!function_exists('wm_close')){

    function wm_close(){
        $_SERVER['HTTP_CONNECTION'] = 'close';
    }
}


/**
 * WorkerMan 子进程 exit
 */
if(!function_exists('wm_end')){

    function wm_end($msg = '',$close = false){
        if($close){
            $_SERVER['HTTP_CONNECTION'] = 'close';
        }
        \Workerman\Protocols\Http::end($msg);
    }
}

/**
 * WorkerMan 控制台输出
 */
if(!function_exists('cli_echo')){

    function cli_echo($msg = '',$tag = '#'){
        if(is_array($msg)){
            \Workerman\Worker::safeEcho("[{$tag}] ", false);
            dump($msg);
        }else{
            \Workerman\Worker::safeEcho("[{$tag}] $msg\n", false);
        }
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
        \Workerman\Protocols\Http::header($content,$replace,$http_response_header);
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
        ob_clean();
        wm_header("HTTP/1.1 500 Internal Server Error");
        if(defined('DEBUG') and DEBUG){
            $msg = explode('|',$msg);
            if(count($msg)>1){
                cli_echo($msg[0] . ' : ' . $msg[1],'SYSTEM ERROR');
            }else{
                cli_echo($msg[0],'SYSTEM ERROR');
            }
        }
        wm_end(
            '<html><head><title>500 Internal Server Error</title></head><body><center><h3>500 Internal Server Error</h3></center></body></html>'
            ,true);
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
        ob_clean();
        wm_header("HTTP/1.1 404 Not Found");
        if(defined('DEBUG') and DEBUG){
            $msg = explode('|',$msg);
            if(count($msg)>1){
                cli_echo($msg[0] . ' : ' . $msg[1],'SYSTEM ERROR');
            }else{
                cli_echo($msg[0],'SYSTEM ERROR');
            }
        }
        wm_end(
            '<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>'
        ,true);
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
        ob_clean();
        wm_header("HTTP/1.1 403 Forbidden");
        if(defined('DEBUG') and DEBUG){
            $msg = explode('|',$msg);
            if(count($msg)>1){
                cli_echo($msg[0] . ' : ' . $msg[1],'SYSTEM ERROR');
            }else{
                cli_echo($msg[0],'SYSTEM ERROR');
            }
        }
        wm_end(
            '<h1>403 Forbidden</h1>'
            ,true);
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
        if($uid != 0){
            return md5(md5(rand(0, 10000) .$uid. md5(time()), md5(uniqid())));
        }
        return md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
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
        if(@json_decode($string)){
            if(json_last_error() != JSON_ERROR_NONE){
                return false;
            }
            if($get){
                return json_decode($string,true);
            }
            return true;
        }
        return false;
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
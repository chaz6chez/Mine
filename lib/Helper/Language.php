<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/11/26            #
# -------------------------- #
namespace Mine\Helper;

/**
 * 语言助手
 *
 *      (1).子类继承 Language 并重写 $_rootPath 例：V1Language extend Language
 *      (2).$_rootPath 为当前模块语言包根路径 如：/home/wwwroot/project/language/
 *      (3).不同语言以不同php文件区分，内容为数组 例：zh_cn.php 内容为return['MS1001'=>'10001|这是例子']
 *      (4).
 *          方法一  V1Language::instance('zh_cn')->parse('MS1001') 输出 10001|这是例子
 *          方法二  V1Language::instance()->parse('zh_cn:MS1001') 输出 10001|这是例子
 *          方法三  V1Language::output('zh_cn:MS1001') 输出 10001|这是例子
 *
 * Class Language
 * @package core\helper
 */
class Language {
    /**
     * @var Language
     */
    private static $_instance;

    protected static $_messageList = [];

    protected $_rootPath = '/Language/';

    protected $_language;

    protected $_defaultLanguage;

    /**
     * Language constructor.
     */
    final private function __construct() {

    }

    /***
     * 输出
     * @param $code
     * @param null $tag
     * @return mixed|null
     */
    final public static function output($code,$tag = null){
        $codes = self::_rule((string)$code);
        if(is_array($codes) and count($codes) < 2){
            return null;
        }
        return self::instance()->parse($code,$tag);
    }

    /**
     * 获取当前实例 用于调试
     * @return Language
     */
    public function getInstance(){
        return self::$_instance;
    }

    /**
     * 单例
     * @param $language
     * @return Language
     */
    final public static function instance($language = false) {
        $class = get_called_class();
        if (!self::$_instance or !self::$_instance instanceof $class) {
            return self::$_instance = new $class();
        }
        if($language){
            return self::$_instance->load($language);
        }
        return self::$_instance;
    }

    /**
     * 加载语言包
     *
     *  1.当前语言与实例语言相同则不加载
     *  2.未找到语言包则输出默认语言包
     *  3.如果默认语言包也不存在则抛出HTTP 500错误
     *
     * @param $language
     * @return $this
     */
    final private function load($language){
        $path = "{$this->_rootPath}{$language}.php";
        if($language != $this->_language){
            if(is_file($path) and file_exists($path)){
                self::$_messageList = require $path;
                $this->_language = $language;
            }else{
                $path = "{$this->_rootPath}{$this->_defaultLanguage}.php";
                if(!is_file($path) or !file_exists($path)){
                    Tools::Http500("language package was found :{$path}");
                }
                self::$_messageList = require $path;
                $this->_language = $this->_defaultLanguage;
            }
        }
        return $this;
    }

    /**
     * 解析输出
     * @param $code
     * @param null $tag
     * @return mixed|null
     */
    public function parse($code,$tag = null){
        $codes = self::_rule((string)$code);
        if(is_array($codes) and count($codes) > 1){
            $this->load($codes[0]);
        }
        if($tag){
            return isset(self::$_messageList[$codes[1]]) ? self::$_messageList[$codes[1]]."[{$tag}]" : null;
        }
        return isset(self::$_messageList[$codes[1]]) ? self::$_messageList[$codes[1]] : null;
    }

    /**
     * 内部规则
     *  用于解析传入字符串
     * @param string $code
     * @return array
     */
    private static function _rule(string $code){
        return explode(':',$code);
    }
}
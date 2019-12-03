<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/11/7            #
# -------------------------- #
namespace Mine\Helper;

use Mine\Core\Instance;

class Template extends Instance{

    protected $_leftDe   = '{';
    protected $_rightDe  = '}';
    protected $_safeMode = false;
    protected $_tplExt   = '.tpl';
    protected $_tplVars  = [];
    protected $_language = 'zh-cn';
    protected $_title    = 'title';

    private static $_tplDir   = '/Template';

    protected function _initConfig() {}

    /**
     * @return string
     */
    public static function getTplDir() : string {
        return self::$_tplDir;
    }

    /**
     * @param string $path
     */
    public static function setTplDir(string $path){
        self::$_tplDir = $path;
    }

    /**
     * 获取标题
     * @return string
     */
    public function getTitle(){
        return $this->_title;
    }

    /**
     * @param $ext
     * @return $this
     */
    public function setExt($ext){
        $this->_tplExt = $ext;
        return $this;
    }

    /**
     * @param $language
     * @return $this
     */
    public function language($language){
        if(is_dir(self::$_tplDir."/{$language}/")){
            $this->_language = $language;
        }else{
            $this->_language = 'en-uk';
        }
        return $this;
    }

    /**
     * 数据分配
     * @param $tplVar
     * @param null $value
     * @param string $default
     */
    public function assign($tplVar, $value = null, $default = null) {
        if($default === null){
            $default = '{$' . $tplVar . '}';
        }
        if (is_array($tplVar)) {
            foreach ($tplVar as $key => $val) {
                if ($key != '') {
                    $this->_tplVars[$key] = !$val ? $default : $val;
                }
            }
        } else {
            if ($tplVar != '') {
                $this->_tplVars[$tplVar] = !$value ? $default : $value;
            }
        }
    }

    /**
     * 模板拼装
     * @param $templateName
     * @return bool|mixed|null|string|string[]
     */
    public function assemble($templateName) {
        $content = '';
        $tpl = self::$_tplDir."/{$this->_language}/{$templateName}{$this->_tplExt}";
        if(is_file($tpl)){
            $content = file_get_contents($tpl);
            $content = $this->_compile($content);
        }
        return $content;
    }

    /**
     * 替换
     * @param $matches
     * @return null|string|string[]
     */
    private function _match($matches) {
        $content = $matches[1];
        //替换变量或输出变量(包括对象成员变量或函数)
        $safe = false;
        $content = preg_replace_callback('/@?\$(\w+)([\s]*\.[\s]*(\w+))*/ms', function ($m) use (&$safe) {
            if ($m[0][0] === '@') {
                $safe = true;
                $m[0] = substr($m[0], 1);
            }
            $arr = explode('.', $m[0]);
            array_shift($arr);

            $r = $m[0];
            if(isset($this->_tplVars[$m[1]])){
                $r = $this->_tplVars[$m[1]];
                foreach ($arr as $a) {
                    $r .= "['" . trim($a) . "']";
                }
            }

            return $r;
        }, $content);
        return $content;
    }

    /**
     * 编译
     * @param $content
     * @return mixed|null|string|string[]
     */
    private function _compile($content) {
        $content = trim($content);
        preg_match('@<title[^>]*>(.*?)<\/title>@si',$content,$title);
        if(isset($title[1])){
            $this->_title = $title[1];
        }
        $leftDelimiterQuote = preg_quote($this->_leftDe);
        $rightDelimiterQuota = preg_quote($this->_rightDe);
        # 替换强调变量
        $content = str_replace("\"{!", "{", $content);
        $content = str_replace("!}\"", "}", $content);
        # 安全模式, 替换php可执行代码
        if ($this->_safeMode) {
            $pattern = "/\\<\\?.*\\?>/msUi";
            $content = preg_replace($pattern, '<!-- PHP CODE REPLACED ON SAFE MODE -->', $content);
        }
        //调用_match函数编译
        $pattern = '/' . $leftDelimiterQuote . '([\\\\@$\w\\/].*)' . $rightDelimiterQuota . '/msU';
        $content = preg_replace_callback($pattern, [&$this, '_match'], $content);
        return $content;
    }
}

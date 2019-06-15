<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/18           #
# -------------------------- #
namespace core\lib;

class Response {

    private $status  = 0;
    private $code    = '0';
    private $message = 'success';
    private $data    = null;
    private $ext     = null;

    /**
     * Response constructor.
     * @param array $data
     */
    public function __construct(array $data = []) {
        if($data){
            $this->ext     = isset($data['ext']) ? $data['ext'] : null;
            $this->data    = isset($data['data']) ? $data['data'] : null;
            $this->message = isset($data['message']) ? $data['message'] : 'success';
            $this->status  = isset($data['status']) ? $data['status'] : 1;
            $this->code    = isset($data['code']) ? $data['code'] : '0';
        }
    }

    /**
     * @param $msg
     * @param string $code
     * @param null $data
     * @param null $ext
     * @return Response
     */
    public function error($msg,$code = '500',$data = null,$ext = null){
        $this->status = 0;
        $this->code = $code;
        $this->message = $msg;
        $err = explode('|', $msg);
        if (is_array($err) && count($err) > 1) {
            $this->code = $err[0];
            $this->message = $err[1];
        }
        if($data){
            $this->data = $data;
        }
        if($ext){
            $this->ext = $ext;
        }
        return clone $this;
    }

    /**
     * @param $data
     * @param string $code
     * @param null $ext
     * @param string $msg
     * @return Response
     */
    public function success($data = null,$code = '0',$ext = null,$msg = 'success'){
        $this->status = 1;
        $this->data = $data;
        $this->message = $msg;
        if($code){
            $this->code = $code;
            $code = explode('|', $code);
            if (is_array($code) && count($code) > 1) {
                $this->code = $code[0];
                $this->message = $code[1];
            }
        }
        if($ext){
            $this->ext = $ext;
        }
        return clone $this;
    }

    /**
     * @return Response
     */
    public function throwError(){
        return clone $this;
    }

    /**
     * 反射获取对象属性
     * @param bool $object
     * @return array|\ReflectionProperty[]
     */
    public function getFields($object = false) {
        try{
            $class = new \ReflectionClass($this);
            $private = $class->getProperties(\ReflectionProperty::IS_PRIVATE);
            if($object){
                return $private;
            }
            $res = [];
            foreach ($private as $item){
                $name = $item->getName();
                $res[$name] = $this->$name;
            }
            return $res;
        }catch (\Exception $exception){
            return [];
        }
    }

    /**
     * 有错误信息
     * @return $this|bool
     */
    public function hasError(){
        if($this->status != 1){
            return true;
        }
        return false;
    }

    /**
     * @return int
     */
    public function getStatus(){
        return (int)$this->status;
    }

    /**
     * @param $status
     */
    public function setStatus($status){
        $this->status = (int)$status;
    }

    /**
     * 获取信息内容
     * @return string
     */
    public function getMessage(){
        return (string)$this->message;
    }

    /**
     * @param $message
     */
    public function setMessage($message){
        $this->message = (string)$message;
    }

    /**
     * @return string
     */
    public function getCode(){
        return (string)$this->code;
    }

    /**
     * @param $code
     */
    public function setCode($code){
        $this->code = (string)$code;
    }

    /**
     * @param null $key
     * @return null
     */
    public function getData($key = null){
        if($key){
            return isset($this->data[$key]) ? $this->data[$key] : null;
        }
        return $this->data;
    }

    /**
     * @param $data
     */
    public function setData($data){
        $this->data = $data;
    }

    /**
     * @return null
     */
    public function getExt(){
        return $this->ext;
    }

    /**
     * @param $ext
     */
    public function setExt($ext){
        $this->ext = $ext;
    }
}
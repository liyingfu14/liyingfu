<?php

namespace app\common\model;


use think\Log;
use think\Model;



/**
 * 业务码  表
 * Class SystemLog
 * @package app\common\model
 */
class Code extends Model
{
    const _DEF_MSG_KEY_ = 'default';
    const _DEF_LANG_  = 'zh_cn';
    public $pk = 'id';
    public $error = ['msg'=>'unknown error code','code'=> 5400,'data'=>[] ]; // 异常未知 业务错误
    public $lang = 'zh_cn';

    /**
     * 通 业务 错误 名 获取 code , msg
     * @param $name
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function getByName($name)
    {
        try {
            if(!is_string($name)){
                throw  new \Exception('异常业务 name : '.$name);
            }
            $data = $this->where(['name' => $name])->cache(['code','msg'])->select(['code','msg']);
            if(empty($data)){
                throw new \Exception('未知业务错误 :'.$name);
            }
            if(empty($data['msg'])){
                throw new \Exception('未定义msg , 业务错误名称 : '.$name);
            }
            $msg = $data['msg'];
            if(isset($data['msg'][$this->lang])){
                $msg = $data['msg'][$this->lang];
            }
            if(empty($msg) && isset($data['msg'][self::_DEF_MSG_KEY_]) ){
                $msg = $data['msg'][self::_DEF_MSG_KEY_];
            }
            $data['msg']  = $msg ;
            return $data;
        } catch (\Exception $e) {
            Log::error(json_encode($this->error)." => [ code : $e->getcode() ] ". $e->getFile() .' '.$e->getLine().' '.$e->getMessage() );
        }
        return $this->error;
    }

    /**
     * 通 业务 错误 名 获取 code , msg
     * @param $code
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function getByCode($code)
    {
        try {
            if(!is_numeric($code)){
                throw  new \Exception('异常参数 code : '.$code);
            }
            $data = $this->field(['code','msg'])->where(['code' => $code])->cache(['code','msg'])->find();
            if(empty($data)){
                throw new \Exception('未知code :'.$code);
            }
            if(empty($data['msg'])){
                throw new \Exception('未定义msg , code : '.$code);
            }
            $data['msg'] =  json_decode($data['msg'],true);
            $msg = $data['msg'];
            if(isset($data['msg'][$this->lang])){
                $msg = $data['msg'][$this->lang];
            }
            if(empty($msg) && isset($data['msg'][self::_DEF_MSG_KEY_]) ){
                    $msg = $data['msg'][self::_DEF_MSG_KEY_];
            }
            $data['msg']  = $msg ;
            return $data;

        } catch (\Exception $e) {
            Log::error(__FILE__.' at line '.__LINE__.' '.json_encode($this->error)." => [ code :  ". $e->getCode().' ] '. $e->getFile() .' '.$e->getLine().' '.$e->getMessage() );
        }
        return $this->error;
    }

}
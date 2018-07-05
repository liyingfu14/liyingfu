<?php
/**
 * Created by TianQi.
 * User: weblinuxgame
 * Email: 994685563@qq.com
 * Date: 2018-05-02
 * Time: 16:26
 */

namespace app\common\lib;

use  think\Config;
use  app\libs\Sms as Sender;
use  think\Log;


class Sms
{
    const RATE_MAX = 1 ;  //  发一次 短信
    const TIME_DIV = 180 ; // 3 分钟内
    const CODE_DEF_LEN = 6 ;// code 长度
    private  $error ;
    private  $types = [] ;
    private  $config = null ;
    private static $instance ;
    private  $client ;
    private  $Sender ;
    private  $product = "Dysmsapi";
    /**
     * Sms constructor.
     * @throws \Exception
     */
    private function __construct()
    {
            $this->config = Config::load(CONF_PATH.'sms'.DS.'config.php','sms');
            $this->types = $this->config['types'];
            $config = [
                'app_key'    => $this->config['APPKEY'],
                'app_secret' => $this->config['APPSECRET'],
            ];
            $this->Sender = Sender::Instance($this->config) ;
            $this->redis = Utils::redis(1);
    }

    /**
     * 发送 短信
     * @param $mobile
     * @param $type
     * @return bool
     */
    public static function  send($mobile,$type)
    {
        $self = self::Instance();
        $code = self::getCode();
        if(Config::get('app_debug')){
            self::save($mobile,$code);
            return true;
        }
        $data = $self->Sender->sendSms($mobile,$code,$type);
        if(!$data ||  ( is_array($data) && isset($data['code']) && $data['code'] != 0  ) ){
            return false;
        }
        $self->result = $data ;
        $self->error = $data ;
        self::save($mobile,$code);
        return true;
    }

    /**
     * 缓存 code
     * @param $mobile
     * @param $code
     */
    public static function save($mobile,$code)
    {
        $key = self::getSaveKey($mobile);
        self::Instance()->redis->set($key,$code,self::TIME_DIV);
    }

    /**
     * 获取 缓存 code key
     * @param $mobile
     * @return string
     */
    public static function getSaveKey($mobile)
    {
        return 'code_'.$mobile;
    }

    /**
     * 验证短信 码
     * @param $mobile
     * @param $code
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public static function verify($mobile,$code)
    {
        $key = self::getSaveKey($mobile);
        $_code = self::Instance()->redis->get($key);
        if(empty($_code)){
            return Utils::error(1048,'验证码已过期');
        }
        if($_code === $code){
            return true;
        }
        return false;
    }

    /**
     * 获取 随机码
     * @param int $length
     * @return string
     */
    public static function getCode($length = self::CODE_DEF_LEN)
    {
        return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * 判断 短信发送类型
     * @param $type
     * @return bool
     */
    public static function smsType($type)
    {
        $self = self::Instance() ;
        try{


            $keys = array_keys($self->types);
            if(in_array($type,$keys)){
                return true;
            }
        }catch (\Exception $e){
            Log::error(Utils::exportError($e));
            Log::error(json_encode($self));
           // $self->error = $e->getMessage();
            return false;
        }

        return false;
    }

    /**
     * 获取 短信 对象
     * @return Sms
     */
    public static function Instance()
    {
        try{
            if(empty(self::$instance)){
                self::$instance  = new self();
            }
        }catch (\Exception $e){
            Log::error(' file :'.$e->getFile().' at line : '.$e->getLine().' msg: '.$e->getMessage());
        }
        return self::$instance ;
    }


    /**
     * 判断 手机号
     * @param null $mobile
     * @return bool
     */
    public static function isMobile($mobile = null)
    {
        if(empty($mobile)){
            return false;
        }
        if(strlen($mobile)<11){
            return false;
        }
        if(!preg_match( '/^1[34578]{1}[0-9]{9}$/',$mobile)){
            return false;
        }
        return true;
    }

    /**
     * 流控
     * @param $mobile
     * @param int $rate
     * @param int $time
     * @return bool
     */
    public static function  limitRate($mobile,$rate = self::RATE_MAX ,$time = self::TIME_DIV )
    {
        if(empty($mobile)){
            return false;
        }
        $code = self::Instance()->redis->get(self::getSaveKey($mobile));
        if(empty($code)){
           return false;
        }
        return true;
    }

    public static function  error()
    {
        return self::Instance()->error ;
    }
}
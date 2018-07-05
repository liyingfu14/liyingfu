<?php
namespace app\common\model;

use app\common\controller\BaseApi;
use app\common\lib\Utils;
use Phinx\Util\Util;
use think\Config;
use think\Hook;
use think\Log;
use think\Model;
use think\Response;
use think\Route;

class Auth extends Model
{

    const STATUS_OVER_VERSION =  -2 ;
    const STATUS_DELETED = -1 ;
    const STATUS_OFF = 0;
    const STATUS_ON  = 1 ;
    const STATUS_DEVELOP = 2 ;
    const IS_PUBLIC = 1 ;
    const IS_INNER  = 0 ;
    const IS_AUTH  = 2 ;
    const LOCALHOST = '127.0.0.1';
    const TOKEN_KEY = 'app_token_name';
    const  CACHE_TIME  = 3600 ;
    const  APP_CHECK  = 'x-app';
    /**
     * 接口 权限 检查
     * @param BaseApi $Api
     * @return bool|mixed
     */
    public function checkAuth(BaseApi &$Api)
    {
        $router = $Api->request->dispatch();

        $action = implode('/',$router['module']);
        $method = strtolower($Api->request->method());
        try{
            $Auth =  $this->where(['uri'=>$action,'request_method'=>$method])->cache(true,self::CACHE_TIME)->find();
            if(is_null($Auth) || !is_object($Auth)){
               $Auth = $this->where(['uri'=>$Api->request->path(),'request_method'=>$method])->cache(true,self::CACHE_TIME)->find();
               if(!$Auth){
                   return  Utils::error(5000,'api未授权');
               }
            }
            if( $Auth->public === self::IS_INNER &&  self::LOCALHOST === $Api->request->ip() ){
                return true ;
            }
            if( $Auth->public === self::IS_AUTH  ){
                $name = Config::get(self::TOKEN_KEY);
                $_token = $Api->request->header($name) ;
                $token = Utils::verifyToken($_token, Utils::getTokenEncodeKey() ,['uid']);
                if($token != true ){
                    return $token;
                }
                $Api->token = Utils::verifyToken($_token,Utils::getTokenEncodeKey());
                $Api->token->_token = $_token;
            }
            $deny_host = $Auth->deny_host ;
            if(!empty($deny_host)){
                $deny_host = explode(',',$deny_host);
                if(in_array($Api->request->ip(),$deny_host)){
                    // 禁止访问
                    return Utils::error(1004,'接口无权访问');
                }
            }
            // 启动 mock 数据
            if( $Auth->status === self::STATUS_DEVELOP && !empty($Auth->mock) ){
                return json_decode($Auth->mock);
            }
            if( $Auth->status === self::STATUS_OFF ){
                // 接口 已关闭
                return Utils::error(1005,'接口已关闭');
            }
            if($Auth->status === self::STATUS_OVER_VERSION){
                // 过期 接口
                return Utils::error(1006,'接口版本过旧');
            }
            // todo [ 中间 检测  ]
            /* if(!empty($Auth->middleware)){
                *$data = $this->checkAuthMethod($this->middleware);
                 if(Utils::isError($data)){
                     return $data ;
                 }
             }*/
            return true;
        }catch (\Exception $e){
            Log::error('file : '.$e->getFile().' at line : '.$e->getLine().' msg :'.$e->getMessage());
        }
        return $Api->Code->error;
    }

    /**
     * 检查 授权 检测方法
     * @param $middlewares
     * @return bool|mixed
     */
    private function checkAuthMethod($middlewares)
    {
        try{
            echo '1';
        }catch (\Exception $e){
            Log::error('file :'.$e->getFile().' at line : '.$e->getLine().' msg :'.$e->getMessage());
        }
        return false;
    }

}
<?php

namespace app\common\controller;


use think\Cache;
use think\Config;
use think\Hook;
use think\Request;
use think\Response;
use think\controller\Rest;
use app\common\model\Auth;
use app\common\model\Rbac;
use app\common\model\Code;
use app\common\lib\Utils;
use think\cache\driver\Redis;
use app\common\lib\Generator;
use think\Db;

class BaseApi extends Rest
{
    const TOKEN_NAME  = 'x-token';
    const FROM_ANDROID  = 1;
    const FROM_IOS = 3 ;
    const FROM_H5 = 2 ;

    protected $header = [];

    protected $is_auth = false;
    /**
     * @var Auth
     */
    public $Auth = null;
    /**
     * @var Request
     */
    public $request = null;
    /**
     * @var Rbac
     */
    public $Rabc = null;
    /**
     * @var   \Mobile_Detect
     */
    public $device = null;
    /**
     * @var string
     */
    protected $return_type = 'json';
    /**
     * @var \app\common\model\Code
     */
    public $Code = null;
    /**
     * 设备 识别
     * @var bool
     */
    protected $isIos = false;
    protected $isAndroid = false;
    protected $isBackend = false;
    protected $isMobile = false;
    protected $isTablet = false;
    protected $x_token = null ; // 登录 token
    protected $x_app = null ;
    protected $uid   = null;
    protected $rid   = null ;
    protected $agent = null ;
    protected $is_alive_token = false;
    protected $cable_list = [];
    protected $code = 0 ;
    protected $error = '';
    protected $cache ;

    public function __construct()
    {
        parent::__construct();
        $this->__initialise();
    }

    /**
     * 初始化
     */
    protected function __initialise()
    {
        $this->request = Request::instance();
        $this->Code = new Code();
        $this->__initDeviceInfo();
        Hook::listen('action_begin', $this);
        $this->__initToken();
        $this->__initCache();
        $this->__initAccess();
        $this->__initRbac();
    }

    /**
     * 初始 缓存对象
     */
    protected function __initCache()
    {
        // 初始 化 缓存
        if (class_exists('Redis')) {
            $this->cache = new Redis(Config::get('redis'));
        } else {
            $this->cache = new Cache();
        }
    }

    /**
     * 初始角色控制
     */
    protected function __initRbac()
    {
        $this->Rabc = new Rbac();
        // 检查 用户 角色权限
        $rbac = $this->Rabc->checkRbac($this);
        if (!empty($rbac) && is_array($rbac) && isset($rbac['code'])) {
            $this->response($rbac)->send();
            exit;
        }
    }

    /**
     * 初始 访问控制
     */
    protected function __initAccess()
    {
        $this->Auth = new Auth();
        $app_name = $this->request->header(Auth::APP_CHECK);
        if(!$app_name){
            $this->response(Utils::error(5301,'非法应用'))->send();
            exit;
        }
        $this->x_app = $app_name ;
        // 检查 登录 和 接口 权限
        $auth = $this->Auth->checkAuth($this);
        if (!empty($auth) && (Utils::isError($auth) || Utils::isReturn($auth) )) {
            $this->response($auth)->send();
            exit;
        }
    }

    /**
     * 初始化 用户信息
     */
    protected function __initToken()
    {
        $this->x_token = $this->getToken();
        if(!empty($this->x_token)){
            $data = Utils::isAliveToken($this->x_token);

            if(!empty($data)){
                $this->is_alive_token = true;
                $this->uid = $data->uid;
                $this->agent = $data->agent;
            }
            if(empty($data)){
                $user = Utils::verifyToken($this->x_token);
            }
            if(empty($this->uid) && !empty($user)){
                if(isset($user->expire) && $user->expire > time() ){
                    $this->error = '账号在他处登录';
                    $this->code  = 4403;
                }
            }
            if(!empty($data->from)){
                $this->__initDeviceInfo($data->from);
            }
        }
    }

    /**
     *  获取设备信息
     * @param null $_from
     */
    protected function __initDeviceInfo($_from = null)
    {
        $from = $this->request->param('from') ;
        $from = empty($from)? $_from : $from ;
        $from = empty($from)? $this->request->header('from') : $from;

        if(empty($from)){
            $this->device = Utils::getDevice();
            $this->isMobile = $this->device->isMobile();
            $this->isIos = $this->device->is('ios')  || preg_match('/ios/',$this->device->getUserAgent());
            $this->isAndroid = $this->device->is('android') || preg_match('/Android/',$this->device->getUserAgent());
            $this->isTablet = $this->device->isTablet();
        }
        else{
            $from = intval($from);
            if(self::FROM_ANDROID === $from){
                $this->isMobile = true ;
                $this->isAndroid = true ;
                $this->isIos = false;
            }
            if(self::FROM_IOS === $from){
                $this->isMobile = true ;
                $this->isAndroid = false ;
                $this->isIos = true;
            }
        }
        if($this->isIos || $this->isAndroid){
            defined('IS_MOBILE') or define('IS_MOBILE',true);
        }
        if($this->isIos){
            defined('IS_IOS') or define('IS_IOS',self::FROM_IOS);
        }
        if($this->isAndroid){
            defined('IS_ANDROID') or define('IS_ANDROID',self::FROM_ANDROID);
        }
    }

    /**
     * 设置 头部信息
     * @param $name
     * @param $value
     */
    protected function setHeader($name, $value)
    {
        if (empty($name)) {
            return;
        }
        if (is_array($name)) {
            $this->header = array_merge($this->header, $name);
        } else {
            $this->header[$name] = $value;
        }
    }

    /**
     * 重写 response 添加头部
     * @param mixed $data
     * @param string $type
     * @param int $code
     * @return $this|Response
     */
    protected function response($data, $type = 'json', $code = 200)
    {
        if(is_numeric($data)  && !Utils::isError($data)){
            $data = Utils::error($data,$type);
        }
        if(!Utils::isError($data) && !Utils::isReturn($data)){
            $data = Utils::success($data,$type);
        }
        if(!in_array($type,['html','text','json','xml'])){
            $type = 'json';
        }
        $params = array('context' => $this, 'data' => $data, 'type' => $type, 'code' => $code);
        Hook::listen('action_end', $params);
        $token_name  = Config::get('app_token_name');
        $token = $this->request->header($token_name);
        if(!empty($token)){
            $this->setHeader($token_name,$token_name);
        }
        return parent::response($data, $type, $code)->header($this->header);
    }

    /**
     * 获取 响应 头部信息
     * @param string $name
     * @return array|mixed|null
     */
    public function getHeader($name = '')
    {
        if (empty($name)) {
            return $this->header;
        }
        if (isset($this->header[$name])) {
            return $this->header[$name];
        }
        return null;
    }

    /**
     * 获取 请求中的token
     * @param string $name
     * @return mixed|string
     */
    public function getToken($name = self::TOKEN_NAME )
    {
        if(!empty($this->x_token) && $name === self::TOKEN_NAME){
            return $this->x_token;
        }
        $token = $this->request->header($name);
        if(empty($token)){
            $token = $this->request->param($name);
        }
        return $token ;
    }

    /**
     * 批量获取变量
     * @param array $params
     * @return array
     */
    public function params($params = [])
    {
        $data = [] ;
        if(!is_array($params)){
            $params = [$params];
        }
        foreach ($params as $v){
            $data[$v] = $this->request->param($v);
        }
       return $data;
    }

    /**
     * 析构
     */
    public function __destruct()
    {
        Hook::listen('app_end', $this);
        // TODO: Implement __destruct() method.
    }


}
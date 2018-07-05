<?php

namespace app\api\model;

use app\common\lib\Utils;
use think\Config;
use think\Db;
use think\Model;
use think\Log;
use app\common\lib\Sms;
use app\api\model\Collection as Collections;
use app\admin\model\OaPdbPay;

class User extends Model
{
    const DEFAULT_AGENT = 'default';
    const FIELDS_CACHE_TIME = 10;
    const APP_AREA_KEY = 'app';
    const ADMIN_AREA_KEY = 'admin';
    const EXCLUDED_KEY = '!';
    const AUTO_NAME_PREFIX = 'tq_';
    const MIN_LEN_USERNAME = 6;
    const MIN_LEN_PASSWORD = 6;
    const FROM_ANDROID = 1; // 注册来源 android
    const FROM_H5 = 2; // 注册来源 h5
    const FROM_IOS = 3; // 注册来源 ios
    const IS_ON = 2; // 游戏正常
    const IS_DEL = 1;// 游戏逻辑删除
    const IS_SUB = 1; // 已订阅
    const IS_APP = 1; // APP签到
    const IS_SDK = 2; // SDK签到
    const PASS_ENCODE_KEY_CONF = 'app_password_salt';// 密码 加密 秘钥 名
    protected $is_sdk_game = '/^tq4c\.\S+game$/';
    protected $sdk_user_table = 'c_members';
    protected $sdk_game_table = 'c_game';
    protected $app_game_table = 'c_app_game';
    protected $app_post_table = 'c_app_post';
    protected $app_sub_table = 'c_app_subs_log';

    // sdk 数据库链接
    protected static $sdk;

    protected $table = 'c_app_users';

    // 数据自动完成
    protected $auto = [
        'agent', 'create_at', 'update_at', 'username', 'last_login_at'
    ];

    /**
     * @var array 自动隐藏字段
     */
    protected $hidden = [
        'password', 'id_card'
    ];

    /**
     * @var array 作用域 只返回 对应 字段 [接口返回|过滤字段]
     */
    protected $area = [
        'app' => [
            '!password', '!score'
        ],
        'base' => [
            'level', 'nickname', 'username', 'mobile', 'signature', 'balance', 'portrait', 'played_game_list'
        ],
        'base3' => [
            'level', 'nickname', 'username', 'mobile', 'signature', 'balance', 'portrait', 'played_game_list', 'idcard'
        ],
        'base2' => [
            'level', 'nickname', 'username', 'mobile', 'signature', 'balance', 'portrait'
        ],
        'update' => [
            'nickname', 'mobile', 'signature', 'portrait', 'played_game_list', 'password', 'imei', 'idcard', 'realname'
        ],
        'filter' => [
            'signature', 'played_game_list', 'id', 'level'
        ],
        'post' => [
            'is_top', 'title', 'status'
        ],
        'post_all' => [
            'id', 'app_id', 'is_top', 'title', 'create_at', 'status', 'thumbs', 'browse', 'content', 'pics'
        ],
        'user_usually_info' => [
            'nickname', 'portrait'
        ],
        'gift_fields' => [
            'title', 'start_time', 'end_time', 'content', 'icon', 'app_id', 'id', 'code'
        ],
        'game_fields' => [
            'id,name', 'icon', 'device_android_url', 'device_ios_url', 'android_version', 'ios_version', 'ios_size', 'android_size', 'ios_bundle_id'
        ],
        'my_appointment' => [
            'g.icon', 'g.name', 'g.create_time'
        ],
    ];

    protected function setCreateAt()
    {
        return time();
    }

    protected function setUpdateAt()
    {
        return time();
    }

    protected function beforeSave($data)
    {

    }

    protected function afterFind()
    {

    }

    protected function afterSelect(&$resultSet = [])
    {
        if (empty($resultSet)) {
            return;
        }
        foreach ($resultSet as $key => $data) {
            if (isset($data->pics)) {
                $data->pics = explode(',', $data->pics);
            }
        }
        return $resultSet;
    }

    /**
     * 返回作用域
     * @return array
     */
    public function _getArea()
    {
        if (empty($this->area)) {
            $this->area = [self::ADMIN_AREA_KEY => [], self::APP_AREA_KEY => []];
        }
        return $this->area;
    }

    /**
     * 设置作用 域 | 添加 作用域
     * @param $attr
     * @param string $range
     * @return bool
     */
    public function _setArea($attr, $range = self::APP_AREA_KEY)
    {
        if (empty($this->area[$range])) {
            $this->area[$range] = $attr;
            return true;
        }
        if (is_array($attr)) {
            $this->area[$range] = array_merge($this->area[$range], $attr);
            return true;
        }
        array_push($this->area[$range], $attr);
    }

    /**
     * 查询 获取字段 过滤
     * @param array $attr
     * @return $this
     */
    public function filter($attr = [])
    {
        $_attr = $this->cache(true, self::FIELDS_CACHE_TIME)->getTableInfo($this->table, 'fields');
        $_area = array_keys($this->_getArea());
        if (empty($attr)) {
            return $this->field(true);
        }
        if (is_string($attr)) {
            $key = str_replace(self::EXCLUDED_KEY, '', $attr);
            $flag = false;
            if (in_array($key, $_attr)) {
                $attr = [$attr];
                $flag = true;
            }
            if (in_array($key, $_area)) {
                $attr = $this->area[$key];
                $flag = true;
            }
            if (!$flag) {
                $attr = [];
            }
        }
        $field = [];
        $except = [];
        foreach ($attr as $value) {
            if (preg_match('/^' . self::EXCLUDED_KEY . '/', $value) && in_array(str_replace(self::EXCLUDED_KEY, '', $value), $_attr)) {
                array_push($except, $value);
                continue;
            }
            if (in_array($value, $_attr)) {
                array_push($field, $value);
                continue;
            }
        }
        if (empty($field)) {
            $field = true;
        } else {
            $field = implode(',', $field);
        }
        if (empty($except)) {
            $except = false;
        } else {
            $except = implode(',', $except);
        }
        return $this->field($field, $except);
    } 


    /**
     * 验证 密码 | 加密 密码
     * @param $data [密码原文]
     * @param $password [加密后的密码]
     * @return bool
     */
    public static function password($data, $password = null)
    {
        if (empty($data)) {
            return false;
        }
        $salt = Config::get(self::PASS_ENCODE_KEY_CONF);
        if (empty($password)) {
            return md5(md5($salt . $data) . $salt);
        }
        $encode = self::password($data);
        if ($encode && $encode === $password) {
            return true;
        }
        return false;
    }

    /**
     * 用户注册
     * @param $data
     * @return bool
     */
    public function register($data)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        if (empty($data['password'])) {
            return Utils::error(5012, '密码不能为空');
        }
        $result = false;
        try {
            // 密码加密
            $data['password'] = self::password($data['password']);
            // 数据 自动填充 处理
            $this->autoFillRegisterData($data);
            $rs = $this->platformUserRegister($data);
            if (!$rs) {
                return Utils::error(1079, '平台用户注册异常');
            }
            $result = $this->insert($data, false, true);
            if (Config::get('app_debug')) {
                Log::info(' register : ' . json_encode($data));
            }
        } catch (\Exception $e) {
            Log::error($this->getLastSql());
            Log::error(Utils::exportError($e));
        }

        return $result;
    }


    /**
     * 检查 渠道 并更新 渠道
     * @param $agent
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public function checkAgent(&$agent)
    {
        if (empty($agent)) {
            return Utils::error(5030, '数据异常,用户信息为空');
        }
        if (empty($agent)) {
            $agent = self::DEFAULT_AGENT;
        }

        if ($this->agent !== $agent && $this->agent === self::DEFAULT_AGENT) {
            $this->agent = $agent;
            $this->save();
        }
        $agent = $this->agent;
        return $agent;
    }

    /**
     * 获取用户信息
     * @param $data
     * @param string $area
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public function getUser($data, $area = self::APP_AREA_KEY)
    {
        try {
            $result = $this->filter($area)->find($data);
            return $result;
        } catch (\Exception $e) {
            Log::error($e->getFile() . ' ' . $e->getMessage());
        }
        return Utils::error(5027, '用户获取异常');
    }


    /**
     * 平台 sdk 同步注册信息
     * @param $data
     * @return bool
     */
    public function platformUserRegister($data)
    {
        if (empty($data)) {
            return false;
        }
        $sdk = null;
        try {
            $sdk = self::getSdk();
            $table = $this->sdk_user_table;
            $this->updatePlatformFilter($data);
            $rs = $sdk->table($table)->insert($data, false, true);
            if (!is_null($rs)) {
                return true;
            }
        } catch (\Exception $e) {
            Log::error($sdk->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return false;
    }

    /**
     * 检查 用户是否 已存在
     * @param $data
     * @return bool
     */
    public static function exists($data)
    {
        $User = new User;
        try {
            $sdk_flag = false;
            $app_flag = false;
            $ret = $User->where($data)->find();
            if ($ret) {
                $app_flag = true;
            }
            // 平台 用户 检查
            if ($User->platformUser($data, $app_flag)) {
                $sdk_flag = true;
            }

            // 自动注册 平台
            if (!$sdk_flag && $app_flag) {
                $tmp = $ret->toArray();
                unset($tmp['id']);
                $User->platformUserRegister($tmp);
            }
            return $sdk_flag || $app_flag;
        } catch (\Exception $e) {
            Log::error($User->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return false;
    }

    /**
     * 自动 同步 sdk 用户
     * @param $data
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public function pull($data)
    {
        try {
            if ($data instanceof Model) {
                $data = $data->toArray();
            }
            $rs = $this->checkData($data);
            if (Utils::isError($rs)) {
                return $rs;
            }
            if (!empty($data['id'])) {
                unset($data['id']);
            }
            if (!empty($data['email'])) {
                unset($data['email']);
            }
            $rs = $this->insert($data, false, true);
            if (!is_null($rs) && $rs) {
                Log::log('[ platform pull SDK user success ] id : ' . $rs . ' data : ' . json_encode($data));
                return true;
            }
            Log::error('[ platform pull SDK user failed ] data : ' . json_encode($data));

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return false;
    }

    /**
     * @return bool|\think\db\Connection
     * @throws \think\Exception
     */
    private static function getSdk()
    {
        $config = Config::load(CONF_PATH . 'sdk' . DS . 'config.php', 'sdk');
        if (empty($config)) {
            return false;
        }
        if (empty(self::$sdk)) {
            self::$sdk = Db::connect($config['database']);
        }
        return self::$sdk;
    }

    /**
     * 平台 用户 检查
     * @param $data
     * @param bool $flag
     * @return bool
     */
    public function platformUser($data, $flag = true)
    {
        if (empty($data)) {
            return false;
        }
        // 从缓存中 未获取到 token
        $table = $this->sdk_user_table;
        $sdk = null;
        try {
            $sdk = self::getSdk();
            $rs = $sdk->table($table)->where($data)->find();
            if (!is_null($rs)) {
                // 同步 用户信息
                if (!$flag) $this->pull($rs);
                return true;
            }
        } catch (\Exception $e) {
            Log::error($sdk->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return false;
    }

    /**
     * 请求 平台 token
     * @return \Buzz\Message\MessageInterface
     */
    public function platformToken()
    {
        $app_key = Config::get('platform.sdk_app_key');
        $app_secret = Config::get('platform.sdk_app_secret');
        $api = Config::get('platform.sdk_token_api');
        $app_name = Config::get('platform.app_name');
        $ret = Utils::brower()->post($api, ['x-app' => $app_name], ['app_key' => $app_key, 'app_secret' => $app_secret, 'timestamp' => time()]);
        if (Utils::isSuccess($ret)) {
            // todo save_token
            Utils::sdkToken($ret);
            //  dump($ret);
        }
        return $ret;

    }

    /**
     * 自动生成 用户名
     * @param int $lenx
     * @return string
     */
    public function autoUserName($lenx = self::MIN_LEN_USERNAME)
    {
        $id = $this->getLastInsID();
        $prefix = self::AUTO_NAME_PREFIX;
        $name = $prefix . $id;
        $len = strlen($name);
        if ($lenx < self::MIN_LEN_USERNAME) {
            $lenx = self::MIN_LEN_USERNAME;
        }
        if ($len < $lenx) {
            $name = $name . Utils::random($lenx - $len);
        }
        try {
            $rs = self::exists(['username' => $name]);
            if ($rs) {
                return $this->autoUserName($lenx + 1);
            }
            return $name;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return $prefix . time() . Utils::random(self::MIN_LEN_PASSWORD + 3);
    }

    /**
     * 获取 用户 基本信息
     * @param null $id
     * @return array
     */
    public function getUserInfo($id = null)
    {
        if (empty($id)) {
            return [];
        }
        try {
            // 检查 游戏
            $this->checkGameList(request()->param('packagelist'), $id);
            // 基础信息
            $user = $this->filter('base2')->where(['id' => $id])->find();
            //0609改 user表balance字段未用,使用db_oa.c_ptb_mem下的remain字段
            $oa = new OaPdbPay();
            $user->data['balance'] = $oa->getUserBalance($user->data['username']);
            $data = $user->toArray();
            return $data;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return [];

    }

    public function setUserInfo($id){
        try{
            $this->where('id',$id)->update(['last_login_at'=>time()]);
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return [];
    }

    /**
     * 获取 我的订阅 信息
     * @param null $uid
     * @return array
     */
    public function getMySubscribe($uid = null)
    {
        if (empty($uid) && empty($this->id)) {
            Log::error('订阅查询 异常: id 为空');
            return [];
        }
        $table = $this->app_sub_table;
        try {
            $fields = 'comm_id';
            $rs = $this->table($table)->where(['uid' => $uid, 'is_subs' => self::IS_SUB])->field($fields)->select();
            if (empty($rs)) {
                Log::log('[ 查询 用户订阅 为空 ] id: ' . $uid);
                return [];
            }
            $data = [];
            if (is_array($rs)) {
                foreach ($rs as $v) {
                    if (!empty($v)) {
                        $tmp = $v->toArray();
                        array_push($data, $tmp['comm_id']);
                    }
                }
                return $data;
            }
            return $rs->toArray();
        } catch (\Exception $e) {

            Log::error(Utils::exportError($e));
        }
        return [];
    }

    /**
     * 获取 我的 礼包 信息
     * @param null $uid
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function getGifts($uid = null)
    {
        if (empty($uid) && empty($this->id)) {
            Log::error('礼包查询 异常: id 为空');
            return [];
        }
        if (empty($uid)) {
            $uid = $this->id;
        }
        $gift = Config::get('database.prefix') . 'gift';
        $code = Config::get('database.prefix') . 'gift_code';
        try {
            $data = $this
                ->table($code)
                ->alias('c')
                ->join($gift . ' g ', ' g.id=c.gf_id ', 'LEFT')
                ->field('g.app_id,g.title,g.start_time,g.end_time,g.content,g.icon,g.id,c.code,c.update_time')
                ->where('c.mem_id', $uid)
                ->order('c.update_time desc')
                ->select();

            if (empty($data)) {
                Log::log('[ 用户礼包查询 为空 ] id : ' . $uid);
                return [];
            }
            return $data;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return [];
    }

    /**
     * 获取 用户帖子
     * @param null $uid
     * @param bool $filter
     * @return array
     */
    public function getPost($uid = null, $filter = true)
    {
        if (empty($uid) && empty($this->id)) {
            Log::error('帖子查询 异常: id 为空');
            return [];
        }
        if (empty($uid)) {
            $uid = $this->id;
        }
        try {
            $table = $this->app_post_table;
            $fields = is_bool($filter) && $filter ? implode(',', $this->area['post_all']) : implode(',', $this->area['post']);
            $rs = $this->table($table)->where(['uid' => $uid])->field($fields)->select();
            $user = null;
            $info = null;
            if ($filter) {
                $user = $this->where(['id' => $uid])->field(implode(',', $this->area['user_usually_info']))->find();
                if (empty($user)) {
                    Log::log('[ 用户不存在 ] id : ' . $uid);
                    return [];
                }
                $info = $user->toArray();
            }

            if (empty($rs)) {
                Log::log('[ 用户帖子 为空 ] id : ' . $uid);
                return [];
            }

            $data = [];

            if (is_array($rs)) {
                $this->afterSelect($rs);
                foreach ($rs as $v) {
                    if (!empty($v)) {
                        $tmp = $v->toArray();
                        if ($filter) $tmp['author'] = $info;
                        array_push($data, $tmp);
                    }
                }
                return $data;
            }
            return $rs->toArray();
        } catch (\Exception $e) {
            Log::error($this->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return [];

    }

    /**
     * 获取 我的游戏
     * @param $game_list
     * @return mixed
     */
    public function getMyGame($game_list)
    {
        if (empty($game_list)) {
            Log::error('查询游戏失败: 游戏列表为空 ');
            return [];
        }
        if (is_string($game_list)) {
            $game_list = explode(',', $game_list);
        }
        try {
            return $this->getGameInfoByIdList($game_list);
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return [];
    }

    /**
     * 获取 我的收藏
     * @param $uid
     * @return array
     */
    public function getCollection($uid)
    {
        $collection = new Collections();
        return $collection->getCollection($uid);
    }

    /**
     * 获取 vip 信息
     * @param $uid
     * @return array
     */
    public function getVip($uid)
    {
        $vip = new Vip();
        $info = $vip->getVipUserInfo($uid);
        return $info;
    }

    /**
     * 添加我的收藏
     * @param $uid
     * @param $data
     * @return bool|int|string
     */
    public function addCollection($uid, $data)
    {
        $Collections = new Collections();
        $rs = $Collections->add(['uid' => $uid, 'cid' => $data['cid'], 'type' => $data]);
        return $rs;
    }

    /**
     * 用户信息 更新 编辑
     * @param $data
     * @return $this|array|bool|false|int|\PDOStatement|string|\think\Collection
     */
    public function edit($data)
    {
        $rs = $this->checkData($data);
        if (Utils::isError($rs)) {
            return $rs;
        }
        try {
            if (!empty($data['id'])) {
                $User = $this->where(['id' => $data['id']])->find();
                if (empty($User)) {
                    return Utils::error(4488, '用户不存在');
                }
                $username = $User->username;

                $rs = $User->save($data, [], implode(',', $this->area['update']));

                if ($rs === false || is_null($rs)) {
                    return false;
                }
                Log::log('[ edit user info success ] 用户信息修改: ' . json_encode($data));
                unset($data['id']);

                $data['username'] = $username;

                $rs = $this->PlatformUserEdit($data);

                if (empty($rs)) {
                    // 失败 记录
                    Log::error('[ failed edit ] 平台sdk 信息同步失败 ' . json_encode($data));
                }
                return true;
            }
            if (!empty($data['mobile'])) {
                $User = $this->where(['mobile' => $data['mobile']])->find();
                if (empty($User)) {
                    return Utils::error(4488, '用户不存在');
                }
                $username = $User->username;
                $rs = $User->save($data);
                if (empty($rs)) {
                    return false;
                }
                unset($data['mobile']);
                $data['username'] = $username;
                $rs = $this->platformUserEdit($data);
                if (empty($rs)) {
                    // 失败 记录
                    Log::error('[ failed edit ] 平台sdk 信息同步失败 ' . json_encode($data));
                }
                return true;
            }
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4450, '用户已信息,修改失败');
    }

    /**
     * 更新 sdk 数据
     * @param $data
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public function platformUserEdit($data)
    {
        if (empty($data)) {
            return Utils::error(4443, '未知操作');
        }

        try {
            $sdk = self::getSdk();
            $table = $this->sdk_user_table;
            $username = $data['username'];
            unset($data['username']);
            $this->updatePlatformFilter($data);
            $rs = $sdk->table($table)->where(['username' => $username])->update($data);
            if (!empty($rs)) {
                Log::log('username : ' . $username . ' [ platform update success ] ' . $rs . ' data : ' . json_encode($data));
            }
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4442, '操作失败');
    }

    /**
     * 过滤 平台 更新 键值
     * @param $data
     * @return mixed
     */
    public function updatePlatformFilter(&$data)
    {
        if (empty($data) || !is_array($data)) {
            return $data;
        }
        $fields = $this->area['filter'];
        foreach ($fields as $v) {
            if (isset($data[$v])) {
                unset($data[$v]);
            }
        }
        return $data;
    }

    /**
     * 更新 数据检查 校验
     * @param $data
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public function checkData(&$data)
    {
        if (empty($data)) {
            return Utils::error(4443, '未知操作');
        }
        if (!is_array($data)) {
            return Utils::error(4438, '参数类型不匹配');
        }
        if (empty($data['mobile']) && empty($data['id'])) {
            return Utils::error(4449, '缺少参数');
        }
        try {
            if (!empty($data['id']) && !$data['id'] < 0) {
                Log::error('[ id type error ] ' . $data['id']);
                return Utils::error(4438, '参数类型不匹配');
            }
            if (!empty($data['mobile']) && !Sms::isMobile($data['mobile'])) {
                Log::error('[ mobile type error ] ' . $data['mobile']);
                return Utils::error(4438, '参数类型不匹配');
            }
            if (!empty($data['password']) && strlen($data['password']) < self::MIN_LEN_PASSWORD) {
                Log::error('[ password length error ] ' . $data['password']);
                return Utils::error(4438, '参数类型不匹配');
            }
            if (!empty($data['nickname'])) {
                $tmp = Utils::replaceLimitWord($data['nickname']);
                $tmp = Utils::replaceUrl($tmp, Utils::REP_URL, Utils::REP_FLAG);
                $data['nickname'] = $tmp;
            }
            if (!empty($data['portrait']) &&  empty(filter_var($data['portrait'], FILTER_VALIDATE_URL)) )  {
                return Utils::error(4455, '非法url');
            }
            if (!empty($data['signature'])) {
                $tmp = Utils::replaceLimitWord($data['signature']);
                $tmp = Utils::replaceUrl($tmp, Utils::REP_URL);
                $data['signature'] = $tmp;
            }
            if (!empty($data['realname'])) {
                $tmp = Utils::replaceLimitWord($data['realname']);
                $tmp = Utils::replaceUrl($tmp, Utils::REP_URL);
                $data['realname'] = $tmp;
            }
            if (!empty($data['balance'])) {
                $data['balance'] = $data['balance'] < 0 ? 0 : $data['balance'];
            }
            if (!empty($data['score']) && $data['score'] < 0) {
                unset($data['score']);
            }
            if (!empty($data['score_coin'])) {
                $data['score_coin'] = $data['score_coin'] < 0 ? 0 : $data['score_coin'];
            }
            if (!empty($data['update_at'])) {
                $data['update_at'] = $data['update_at'] > time() ? time() : $data['update_at'];
            }
            if (!empty($data['played_game_list'])) {
                if (is_array($data['played_game_list'])) {
                    $data['played_game_list'] = array_filter($data['played_game_list'], function ($k, $v) {
                        return is_numeric($v) && $v > 0;
                    }, ARRAY_FILTER_USE_BOTH);
                    $data['played_game_list'] = array_unique($data['played_game_list']);// 去除重复 gid
                    $data['played_game_list'] = implode(',', $data['played_game_list']);
                }
            }
            return $data;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4438, '参数类型不匹配');
    }

    /**
     * 检查 并更新 我的游戏 列表
     * @param array $game_lists
     * @param null $uid
     * @return array|bool
     */
    public function checkGameList($game_lists = [], $uid = null)
    {
        try {
            $data = $this->getGameInfoByPackageName($game_lists);
            if (empty($data['list'])) {
                return false;
            }
            $user = null;
            if (empty($this->played_game_list)) {
                $user = self::get(['id' => $uid]);
                if (empty($uid)) {
                    Log::error('[ 用户数据异常 ] id: ' . $uid);
                    return false;
                }
                $user_game_list = empty($user->played_game_list) ? '' : explode(',', $user->played_game_list);
            } else {
                $user_game_list = explode(',', $this->played_game_list);
                $user = $this;
            }
            if (empty($user_game_list)) {
                $user->save(['played_game_list' => 1===count($data['list']) ? $data['list'][0] : implode(',', $data['list'])]);
            }
            $data = array_unique(array_merge($user_game_list, $data['list']));
            sort($data);
            $data = array_filter($data, function ($item, $K) {
                if (empty($item) && $K) {
                    return false;
                }
                return true;
            }, ARRAY_FILTER_USE_BOTH);
            $flag = array_diff($data, $user_game_list);

            if (!empty($data) && !empty($flag)) {
                $user->save(['played_game_list' => implode(',', $data)]);
            }
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return false;
    }

    /**
     * 通包名 获取游戏 信息
     * @param string $package_list
     * @return array
     */
    public function getGameInfoByPackageName($package_list = '')
    {
        if (empty($package_list)) {
            return [];
        }
        if (is_string($package_list) && strpos($package_list, ',') <= 0 && !preg_match($this->is_sdk_game, $package_list)) {
            return [];
        }
        if (!is_array($package_list)) {
            $package_list = explode(',', $package_list);
        }
        Log::log(' [ 游戏包名参数 ] ' . json_encode($package_list));
        $pattern = $this->is_sdk_game;
        $package_list = array_filter($package_list, function ($item, $k) use ($pattern) {
            if (preg_match($pattern, $item) && is_numeric($k)) {
                return true;
            }
            return false;
        }, ARRAY_FILTER_USE_BOTH);
        if (empty($package_list)) {
            return [];
        }
        try {
            if ( defined('IS_IOS') ){
                $list = $this->table($this->app_game_table)->whereIn('ios_mac_id', $package_list)->field(implode(',', $this->area['game_fields']))->select();
            }else{
                $list = $this->table($this->app_game_table)->whereIn('android_mac_id', $package_list)->field(implode(',', $this->area['game_fields']))->select();
            }
            if (empty($list)) {
                Log::log('[ 未知 应用 包 ]' . json_encode($package_list));
                return [];
            }
            $data = ['list' => []];
            foreach ($list as $item) {
                if (!empty($item)) {
                    $id = $item->id;
                    array_push($data['list'], $id);
                    $tmp = $item->toArray();
                    $data[$id] = $tmp;
                }
            }
            sort($data['list']);
            return $data;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return [];
    }

    /**
     * 注册数据 自动填充 部分
     * @param $data
     * @return array
     */
    public function autoFillRegisterData(&$data)
    {
        if (empty($data) || !is_array($data)) {
            return [];
        }
        if (empty($data['username'])) {
            $data['username'] = $this->autoUserName();
        }
        if (empty($data['from'])) {
            $_from = request()->param('from');
            if (empty($_from) || !in_array($_from, [self::FROM_ANDROID, self::FROM_H5, self::FROM_IOS])) {
                $_from = self::FROM_ANDROID;
            }
            $data['from'] = $_from;
        }
        if (empty($data['imei'])) {
            $imei = request()->param('imeil');
            $data['imei'] = empty($imei) ? '' : $imei;
        }
        if (empty($data['reg_ip'])) {
            $data['reg_ip'] = request()->ip();
        }
        if (empty($data['create_at'])) {
            $data['create_at'] = time();
        }
        if (empty($data['update_at'])) {
            $data['update_at'] = time();
        }
        if (empty($data['last_login_ua'])) {
            $user_agent = request()->header('user-agent');
            if (Config::get('app_debug')) Log::log('user-agent: ' . $user_agent);
            $data['last_login_ua'] = $user_agent;
        }
        if (empty($data['played_game_list'])) {
            $data['played_game_list'] = '';
        }
        $tmp = request()->param('packagelist');
        if (!empty($data['packagelist'])) {
            unset($data['packagelist']);
        }
        if (!empty($tmp)) {
            $item = $this->getGameInfoByPackageName($tmp);
            if (!empty($item) && !empty($item['list'])) {
                $data['played_game_list'] = 1 === count($item['list'])? $item['list'][0] :implode(',', $item['list']);
            }
        }
        return $data;
    }


    /**
     * 获取 我的游戏 信息
     * @param string $list
     * @return array
     */
    public function getGameInfoByIdList($list = '')
    {
        if (empty($list)) {
            return [];
        }

        if (is_string($list) && strpos($list, ',') < 0) {
            return [];
        }
        if (!is_array($list)) {
            $list = explode(',', $list);
        }

        $package_list = array_filter($list, function ($item, $k) {
            if (empty($item) && is_numeric($k)) {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
        sort($package_list);

        if (empty($package_list)) {
            return [];
        }
        try {
            $lists = $this->table($this->app_game_table)->where(['is_delete' => self::IS_ON])->whereIn('id', $package_list)->field(implode(',', $this->area['game_fields']))->select();

            if (empty($list)) {
                Log::log('[ 未知 应用 包 ]' . json_encode($package_list));
                return [];
            }
            $data = [];

            foreach ($lists as $item) {
                if (!empty($item)) {
                    $tmp = $item->toArray();
                    array_push($data, $tmp);
                }
            }
            return $data;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return [];

    }

    /**
     * 检查 机器码
     * @param string $imei
     * @return bool
     */
    public function checkImei($imei = '')
    {
        if (empty($imei)) {
            return false;
        }
        if (empty($this->id)) {
            return false;
        }
        if (empty($this->imei)) {
            $this->edit(['id' => $this->id, 'imei' => $imei]);
        } else {
            if ($this->imei !== $imei) {
                $this->edit(['id' => $this->id, 'imei' => $this->imei . '|' . $imei]);
            }
        }
    }

    /**
     * 常检测量
     */
    public function autoCheck()
    {
        $agent = request()->param('agent');
        $this->checkAgent($agent);
//        $this->checkGameList(request()->param('packagelist'));
        $this->checkImei(request()->param('imeil'));
    }

    // 我的预约
    public function myAppointment($uid)
    {
        if (empty($uid)) {
            return Utils::error(2500, '用户ID为空');
        }
        try {
            $game = Config::get('database.prefix') . 'game';
            $meet = Config::get('database.prefix') . 'meet_log';
            $appoint = $this->table($meet)
                ->alias('m')
                ->join($game . ' g ', ' g.id = m.app_id ')
                ->field(implode(',', $this->area['my_appointment']))
                ->where('m.uid', $uid)
                ->select();
            return ['content' => $appoint];
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 我的游戏[信息]
    public function getOrtherInfo($id = null)
    {
        if (empty($id)) {
            return [];
        }
        try {
            // 检查 游戏
            $this->checkGameList(request()->param('packagelist'), $id);
            // 基础信息
            $user = $this->field(implode(',', $this->area['base3']))->where(['id' => $id])->find();
            if (empty($user)) {
                return [];
            }
            $oa = new OaPdbPay();
            $user->data['balance'] = $oa->getUserBalance($user->data['username']);
            if (empty($user->data['idcard'])){
                $user->data['idcard'] = false;
            }else{
                $user->data['idcard'] = true;
            }
            $data = $user->toArray();
            unset($data['id']);
            // 我的游戏 [信息]
            $data['played_game_list'] = $this->getGameInfoByIdList($data['played_game_list']);
            // vip
            $data['vip'] = $this->getVip($id);
            // 签到
            $sign_in = Config::get('database.prefix') . 'sign_in_log';
            $now = time();
            $zone_time = strtotime(date('Y-m-d', $now)); // 0 点
            $sign = $this->table($sign_in)->where(['uid' => $id])->whereBetween('create_at', [$zone_time, $now])->find();
            $data['is_sign'] = 0;
            if (!empty($sign)) {
                $data['is_sign'] = 1;
            }
            // 我收藏
//            $data['collection'] = $this->getCollection($id);
            // 我的 订阅
//            $data['subscribe'] = $this->getMySubscribe($id);
            // 我的 礼包
//            $data['gifts'] = $this->getGifts($id);
            // 我的帖子
//            $data['posts'] = $this->getPost($id, false);

            return $data;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return [];
    }

    // 签到
    public function signIn($uid)
    {
        try {
            $sign_in = Config::get('database.prefix') . 'sign_in_log';
            $user = Config::get('database.prefix') . 'users';
            $now = time();
            $zone_time = strtotime(date('Y-m-d', $now)); // 0 点
            $sign = $this->table($sign_in)->where(['uid' => $uid])->whereBetween('create_at', [$zone_time, $now])->find();
            if (empty($sign)) {
                $data = ['uid' => $uid, 'create_at' => $now, 'type' => self::IS_APP];
                $this->table($sign_in)->insert($data);
                $this->table($user)->where('id', $uid)->setInc('score');
                $this->table($user)->where('id', $uid)->setInc('score_coin');
                $this->table($user)->where('id', $uid)->setInc('level');
                return true;
            }
            if (!empty($sign)) {
                return Utils::error(2600, '今日已签到');
            }

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return false;
    }

    // 我的游戏
    public function myGame($id)
    {
        try {
            $user = $this->field('played_game_list')->where(['id' => $id])->find();
            if (empty($user)) {
                return [];
            }
            $data = $user->toArray();
            unset($data['id']);

            $data = $this->getGameInfoByIdList($data['played_game_list']);

            return $data;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return false;
    }
}
<?php
/**
 * Created by TianQi.
 * User: weblinuxgame
 * Email: 994685563@qq.com
 * Date: 2018-04-25
 * Time: 09:19
 */

namespace app\common\lib;

use app\common\model\Code;
use app\common\model\LimitWord;
use Mobile_Detect as Device;
use Firebase\JWT\JWT;
use think\cache\driver\Redis;
use Buzz\Browser as Http;
use think\Config;
use think\Log;
use Singiu\WordBan\WordBan;
use think\Model;
use think\Response;

class Utils
{
    const TIME_OUT_TOKEN = 302400; // 7天时间
    const SUCCESS_CODE = 200; // 成功 code
    const APP_LOGIN_TOKEN = 0; // app 登录token 标记
    const ADMIN_LOGIN_TOKEN = 1;// 后台登录 token 标记
    const UPLOAD_TOKEN = 2;// 上传 token 标记
    const APP_LOGIN_TOKEN_PREFIX = 'login_'; // app 登录前缀
    const ADMIN_LOGIN_TOKEN_PREFIX = 'admin_';// 后台 登录前缀
    const UPLOAD_TOKEN_PREFIX = 'upload_';// upload 上传token前缀
    const DEF_ALG = 'HS256';// token 默认加密方式
    const REP_FLAG = '*';// 默认 敏感词替换符
    const HAVE_URL = 0; // 是否含有 url
    const REP_URL = 1; // 是否 替换url
    const REP_URL_FLAG = '4c游戏';// url 替换词
    const SCOPE_APP = 'app'; // app
    const SCOPE_PLATFORM = 'platform'; // sdk
    const SCOPE_UPLOAD = 'upload'; // upload
    const SCOPE_ADMIN = 'admin';// backend

    const ANDROID = 1;
    const IOS = 3;
    const H5 = 2;
    const APP_ANDROID_ID = 2;
    const APP_IOS_ID = 3;
    const APP_H5_ID = 4;


    private static $token_redis;

    /**
     * 替换 敏感词
     * @param string $data [被替换原文]
     * @param string $flag [替换标识符]
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function replaceLimitWord($data = '', $flag = self::REP_FLAG)
    {
        if (empty($data)) {
            return $data;
        }
        $limit_words = LimitWord::getLimitWord();
        if (empty($limit_words)) {
            return $data;
        }
        if (empty($flag)) {
            $flag = self::REP_FLAG;
        }

        WordBan::load($limit_words);
        WordBan::setEscapeChar($flag);
        $result = WordBan::escape($data);
        return $result;
    }

    /**
     * url 过滤
     * @param $data
     * @param int $type
     * @param string $re
     * @return bool|null|string|string[]
     */
    public static function replaceUrl($data, $type = self::HAVE_URL, $re = self::REP_URL_FLAG)
    {
        if (empty($data)) {
            return $data;
        }
        $pattern = '/(https?|ftp|file):\/\/[-A-Za-z0-9+&@#\/%?=~_|!:,.;]+[-A-Za-z0-9+&@#\/%=~_|]/';
        if (self::HAVE_URL === $type) {
            $flag = preg_match($pattern, $data);
            return $flag && $flag > 0 ? true : false;
        }
        if (self::REP_URL === $type) {
            $data = preg_replace($pattern, $re, $data);
        }
        return $data;
    }

    /**
     * 检查 是否含有敏感词
     * @param $data
     * @return array|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function hasLimitWord($data)
    {
        if (empty($data)) {
            return $data;
        }
        $limit_words = LimitWord::getLimitWord();
        if (empty($limit_words)) {
            return 0;
        }
        WordBan::load($limit_words);
        $result = WordBan::scan($data);
        return is_array($result) ? count($result) : $result;
    }

    /**
     * 展开 错误信息
     * @param \Exception|null $e
     * @return string
     */
    public static function exportError(\Exception $e = null)
    {
        if (empty($e) || !($e instanceof \Exception)) {
            return '';
        }
        return 'error code [ ' . $e->getCode() . '] file: ' . $e->getFile() . ' at line: ' . $e->getLine() . '  error msg : ' . $e->getMessage();
    }

    /**
     * 获取 token 秘钥
     * @return mixed
     */
    public static function getTokenEncodeKey()
    {
        return Config::get('app_token_key');
    }

    /**
     * @return Device
     */
    public static function getDevice()
    {
        static $dev;
        if (empty($dev)) {
            $dev = new Device();
        }
        return $dev;
    }

    /**
     * 检查 是否为 错误 消息 提示
     * @param null $data
     * @return bool
     */
    public static function isError($data = null)
    {
        if (empty($data)) {
            return false;
        }
        if (is_array($data) && isset($data['msg']) && isset($data['code']) && $data['code'] !== self::SUCCESS_CODE) {
            return true;
        }
        if (is_object($data) && isset($data->msg) && isset($data->code) && $data->code !== self::SUCCESS_CODE) {
            return true;
        }
        return false;
    }

    /**
     * 错误码 返回
     * @param $code
     * @param string $lang
     * @param $msg
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public static function error($code, $lang = null, $msg = null)
    {
        if (empty($code)) {
            Log::error('异常 错误设置 ');
            return false;
        }
        $Code = new Code();
        if (empty($msg) && !empty($lang) && strpos($lang, '_') >= 0) {
            $msg = $lang;
            $lang = null;
        }
        if (in_array($msg, ['json', 'xml', 'html', 'text'])) {
            $msg = '请求失败';
        }
        $Code->lang = empty($lang) ? Code::_DEF_LANG_ : $lang;
        try {
            $data = $Code->where(['code' => $code])->find();
            if (empty($data->code)) {
                // 自动记录错误码
                $info = ['code' => $code, 'msg' => json_encode([$Code->lang => $msg]), 'module' => 'common', 'create_at' => time(), 'update_at' => time()];
                $Code->data($info)->save();
            }
            $error = $Code->getByCode($code);
            $error['data'] = NullClass();
            return $error;
        } catch (\Exception $e) {
            Log::error(__FILE__ . ' at line ' . __LINE__ . ' ' . $e->getMessage());
        }
        return $Code->error;
    }

    /**
     * 返回 成功 信息
     * @param array $data
     * @param string $msg
     * @return array
     */
    public static function success($data = [], $msg = '')
    {
        if (in_array($msg, ['json', 'html', 'text', 'xml']) || empty($msg)) {
            $msg = '请求成功';
        }
        return ['data' => $data, 'code' => 200, 'msg' => $msg];
    }

    /**
     * 判断 是否为返回
     * @param array $data
     * @return bool
     */
    public static function isReturn(&$data = [])
    {
        if (empty($data)) {
            return false;
        }
        try {
            if ($data instanceof Model) {
                $tmp = $data->toArray();
                $data = ['data' => $tmp, 'code' => 200, 'msg' => '请求成功'];
            }
        } catch (\Exception $e) {
            $data = self::error(4848, '请求异常');
            Log::error(self::exportError($e));
        }
        if (is_array($data) && isset($data['data']) && isset($data['code']) && $data['code'] === 200) {
            return true;
        }

        if (is_object($data)) {
            $vars = get_object_vars($data);
            $keys = array_keys($vars);
            $re = ['code', 'msg', 'data'];
            if (empty($keys)) {
                return false;
            }
            $len = count($re);
            $count = 0;
            foreach ($keys as $v) {
                if (in_array($v, $keys)) {
                    $count++;
                }
            }
            if ($count !== $len) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Token 生成
     * @param null $data
     * @param string $scope
     * @param null $key
     * @param string $alg
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public static function token($data = null, $scope = self::SCOPE_APP, $key = null, $alg = self::DEF_ALG)
    {
        if (empty($data)) {
            // 空参数
            return self::error(1001);
        }
        if (empty($key)) {
            $key = self::getTokenEncodeKey();
        }
        try {
            $data['scope'] = $scope;
            return JWT::encode($data, $key, $alg);
        } catch (\Exception $e) {
            Log::error(self::exportError($e));
        }
        // token 失败
        return self::error(1002, '登录认证失败');
    }

    /**
     * 验证 token | 解码 token
     * @param null $token
     * @param null $key
     * @param array $data
     * @param array $alg
     * @return array|bool|false|object|\PDOStatement|string|\think\Collection
     */
    public static function verifyToken($token = null, $key = null, $data = [], $alg = array(self::DEF_ALG))
    {
        if (empty($token)) {
            // 空参数
            return Utils::error(1001, '空参数错误');
        }
        if (empty($key)) {
            $key = self::getTokenEncodeKey();
        }
        try {
            $decode = JWT::decode($token, $key, $alg);
            if (empty($data)) {
                return $decode;
            }
            foreach ($data as $v) {
                if (empty($decode[$v])) {
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        // 非法 token
        return self::error(1003, '非法token');
    }

    /**
     * token 是否 还存活
     * @param $token
     * @param int $type
     * @return array|bool|false|object|\PDOStatement|string|\think\Collection
     */
    public static function isAliveToken($token, $type = self::APP_LOGIN_TOKEN)
    {
        if (empty($token)) {
            return false;
        }
        $data = self::verifyToken($token);
        Log::log('user token ----->' . $token);
        Log::log('user token data ----->' . json_encode($data));
        if (Utils::isError($data)) {
            return false;
        }
        if (empty($data->expire)) {
            return false;
        }
        if ($data->expire <= time()) {
            return false;
        }
        if (empty($data->uid) || empty($data->agent)) {
            return false;
        }
        $key = self::getTokenKeyByType($type, ['uid' => $data->uid, 'scope' => $data->scope]);
        $redis = self::getTokenSaver();
        $_token = $redis->get($key);
        Log::log(' token key ----->' . $key);
        Log::log(' token in redis  ----->' . json_encode($_token));
        if ($_token === $token) {
            if (!isset($data->app_id)) {
                $data->app_id = self::getAppIdByAppName();
            }
            return $data;
        }
        return false;
    }

    /**
     * 生成 token 并保存
     * @param array $data
     * @param int $expire
     * @param int $type
     * @param string $scope
     * @param null $key
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public static function tokenSave($data = [], $expire = self::TIME_OUT_TOKEN, $type = self::APP_LOGIN_TOKEN, $scope = self::SCOPE_APP, $key = null)
    {
        if (empty($data)) {
            return Utils::error(1010, '登录用户信息为空');
        }
        if (!is_array($data) || empty($data['uid']) || empty($data['agent'])) {
            return Utils::error(1011, '参数信息校验失败');
        }
        $Redis = self::getTokenSaver();
        if (!is_numeric($expire) || $expire < 0) {
            return Utils::error(1012, '服务器参数出错');
        }
        if (empty($key)) {
            $key = Config::get('app_token_key');
        }
        if (empty($data['time'])) {
            $data['time'] = time();
        }
        $data['expire'] = time() + $expire;
        $data['scope'] = $scope;
        if(!empty($data['from']) && empty($data['app_id'])){
            $data['app_id'] = self::getAppIdByFrom($data['from']);
        }
        /**
         * @ver \Redis
         */
        try {
            $save_key = self::getTokenKeyByType($type, $data);
            Log::log('old key ----->' . $save_key);
            $token = $Redis->get($save_key);
            if (empty($token)) {
                $token = Utils::token($data, $scope, $key);
            } else {
                $_data = self::isAliveToken($token);
                $is_new = false;
                Log::log('old data ----->' . json_encode($_data));
                Log::log('redis data ----->' . json_encode($data));
                if (empty($_data)) {
                    $token = Utils::token($data, $scope, $key);
                    $is_new = true;
                }
                if ( (isset($_data->from) && intval($_data->from) !== intval($data['from'])) || ( isset($_data->imeil) && $_data->imeil !== $data['imeil']) ) {
                    $token = Utils::token($data, $scope, $key);
                    $is_new = true;
                }
                if(!$is_new){
                    return $token ;
                }
            }
            if (empty($save_key) || empty($token)) {
                return false;
            }
            $Redis->set($save_key, $token, $expire);
            return $token;
        } catch (\Exception $e) {
            Log::error(__FILE__ . ' at line ' . __LINE__ . ' ' . self::exportError($e));
        }
        return false;
    }


    /**
     * 各类 token key
     * @param $type
     * @param $data
     * @return string
     */
    public static function getTokenKeyByType($type, $data)
    {
        if (is_object($data)) {
            $data = (array)$data;
        }
        if (!isset($data['uid']) || !isset($data['scope'])) {
            return false;
        }
        $key = $data['uid'] . $data['scope'];
        if (self::APP_LOGIN_TOKEN === $type) {
            return self::APP_LOGIN_TOKEN_PREFIX . md5($key);
        }
        if (self::ADMIN_LOGIN_TOKEN === $type) {
            return self::ADMIN_LOGIN_TOKEN_PREFIX . md5($key);
        }
        if (self::UPLOAD_TOKEN === $type) {
            return self::UPLOAD_TOKEN_PREFIX . md5($key);
        }
        return md5($key);
    }

    /**
     * 通过 设备类型 返回 appId
     * @param int $from
     * @return int
     */
    public static function getAppIdByFrom($from = 0)
    {
        if (empty($from) || self::ANDROID === $from) {
            return self::APP_ANDROID_ID;
        }
        if (self::IOS === $from) {
            return self::APP_IOS_ID;
        }
        if (self::H5 === $from) {
            return self::APP_H5_ID;
        }
        return self::APP_ANDROID_ID;
    }

    /**
     * 是否可以返回
     * @param $data
     * @return bool
     */
    public static function returnAble(&$data)
    {
        if (self::isError($data) || self::isSuccess($data)) {
            return true;
        }
        if ($data instanceof Response) {
            $data = $data->getData();
            return true;
        }
        return false;
    }

    /**
     * 返回 http client
     * @return Http
     */
    public static function brower()
    {
        return new Http();
    }

    /**
     * 请求成功
     * @param $data
     * @return bool
     */
    public static function isSuccess($data)
    {
        if (empty($data)) {
            return false;
        }
        if (is_array($data) && !empty($data['code']) && $data['code'] == self::SUCCESS_CODE && !empty($data['data'])) {
            return true;
        }
        if (is_object($data) && isset($data->code) && $data->code == self::SUCCESS_CODE && isset($data->data)) {
            return true;
        }
        return false;
    }

    /**
     * 请求 失败
     * @param $data
     * @return bool
     */
    public static function isFailed($data)
    {
        return !self::isSuccess($data);
    }

    /**
     * 平台 token 获取
     * @param $data
     * @return array|bool|false|mixed|\PDOStatement|string|\think\Collection
     */
    public static function sdkToken($data = null)
    {
        $Redis = new Redis(Config::get('token_save'));
        $key = Config::get('platform.sdk_token');
        try {
            if (empty($data)) {
                $data = $Redis->get($key);
                if (empty($data)) {
                    return Utils::error(1016, 'token为空');
                }
                return json_decode($data, true);
            }
            if (!is_array($data) || empty($data['expire']) || empty($data['token']) || empty($data['scope'])) {
                return Utils::error(1017, '异常token数据');
            }
            $Redis->set($key, json_encode($data), $data['expire'] + 5);
            return true;
        } catch (\Exception $e) {
            Log::error('file ' . $e->getFile() . ' at line ' . $e->getLine() . ' msg ' . $e->getMessage());
        }
        return Utils::error(5400);
    }

    /**
     * 获取 redis 库
     * @param int $db
     * @return Redis
     */
    public static function redis($db = 0)
    {
        $redis = null;
        if (empty($redis)) {
            $config = Config::get('redis');
            $config['select'] = $db;
            $redis = new Redis($config);
        }
        return $redis;
    }

    /**
     * 获取 token 存储 redis
     * @return Redis
     */
    public static function getTokenSaver()
    {
        if (empty(self::$token_redis)) {
            self::$token_redis = new Redis(Config::get('token_save'));
        }
        return self::$token_redis;
    }

    /**
     * 随机 生成 字符串
     * @param $numeric [只随机数字]
     * @param int $length [随机字符串长度]
     * @return string
     */
    public static function random($length = 1, $numeric = 0)
    {
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }

    /**
     * 通过 x-app 获取 appid
     * @return int|string
     */
    public static function getAppIdByAppName()
    {
        $app_key = Config::get('app_token_name');
        $app_name = request()->header($app_key, '');
        if ($app_name === '4cgame_sdk') {
            return 1;
        }
        if ($app_name === '4cgame_app') {
            return 2;
        }
        if ($app_name === '4cgame_admin_app') {
            return 3;
        }
        return $app_name;
    }

}
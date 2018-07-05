<?php

namespace app\api\controller;

use app\api\model\Init;
use app\common\controller\BaseApi;
use app\api\model\User as Users;
use app\common\lib\Utils;
use app\common\lib\Sms;
use app\api\model\Collection;
use think\Response;
use think\Log;
use app\common\lib\RealName;

class User extends BaseApi
{
    const  DEF_PAGE_LEN = 10;
    const  DEF_PAGE = 1;
    const  DEF_USER_ID = '*';
    const  IS_NORMAL = 1;


    protected $model = null;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Users();
    }

    /**
     * 初始化 临时身份
     */
    public function init()
    {
        extract($this->params(['imeil', 'agentname']));
        $init = new Init();
        if (empty($agentname)) {
            $agentname = Users::DEFAULT_AGENT;
        }
        if (empty($imeil)) {
            $imeil = $init->makeImei();
        }
        $xcode = $init->getAccessUser($imeil, $agentname, $this->request->header('user-agent'));
        return $this->response($xcode);
    }

    /***
     * 用户 登录 接口
     * @return $this|\think\Response
     */
    public function login()
    {
        extract($this->params(['account', 'password', 'agent', 'agentname', 'imeil', 'from']));
        if (empty($account)) {
            return $this->response(5011, '用户账号不能为空');
        }
        if (empty($password)) {
            return $this->response(5012, '用户密码不能为空');
        }
        if (empty($agent)) {
            $agent = empty($agentname) ? Users::DEFAULT_AGENT : $agentname;
        }
        try {
            $user = null;
            $_password = Users::password($password);

            if (is_numeric($account) && strlen($account) >= 11) {
                $result = $this->loginByMobile($account, $_password, $agent, $imeil, $from);
            } else {
                $result = $this->loginByAccount($account, $_password, $agent, $imeil, $from);
            }
            if ($result instanceof Response) {
                return $result;
            }
            if (Utils::isError($result)) {
                return $this->response($result);
            }
            if (!is_array($result) && empty($result['token'])) {
                return $this->response(5014, '数据异常,登录失败');
            }
            return $this->response($result, '登录成功');
        } catch (\Exception $e) {
            Log::error(' ' . $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage());
        }
        return $this->response(5013, '登录失败');
    }

    /**
     * 用户 注销登录
     * @return $this|User|array|\think\Response
     */
    public function logout()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid) || empty($this->x_token)) {
            return $this->response(4444, '请先登录');
        }
        $key = Utils::getTokenKeyByType(Utils::APP_LOGIN_TOKEN, ['uid' => $this->uid, 'agent' => $this->agent, 'scope' => Utils::SCOPE_APP]);
        $db = Utils::getTokenSaver();
        $db->rm($key);
        return $this->response(NullClass(), '注销成功');
    }

    /**
     * 手机号登录
     * @param $mobile [用户手机号]
     * @param $password [用户密码]
     * @param $agent [用户渠道名]
     * @param $imeil
     * @param $from
     * @return User|array|\think\Response
     */
    private function loginByMobile($mobile, $password, $agent, $imeil, $from)
    {
        if (empty($mobile)) {
            return $this->response(5011, '用户账号不能为空');
        }
        if (empty($password)) {
            return $this->response(5012, '密码不能为空');
        }
        if (!Users::exists(['mobile' => $mobile])) {
            return $this->response(5016, '用户不存在');
        }
        $user = Users::get(['mobile' => $mobile]);

        if ($user->status !== self::IS_NORMAL) {
            return $this->response(5018, '账号已冻结');
        }
        if (empty($user->password) || $user->password !== $password) {
            return $this->response(5017, '密码不正确');
        }
        if ($agent !== $user->agent && $agent !== Users::DEFAULT_AGENT) {
            $user->agent = $agent;
        }
        // 检查 渠道 机器码 游戏
        $user->autoCheck();
        $token = Utils::tokenSave(['imeil' => $imeil, 'from' => $from, 'uid' => $user->id, 'agent' => $user->agent, 'username' => $user->username]);
        if (empty($token)) {
            return $this->response(5049, '系统繁忙');
        }
        $this->initUser($user, $token);
        $data = ['token' => $token];
        $base = $this->getUserInfo();
        $this->setUserInfo();           //0615更新用户登录时间
        $data = array_merge($data, $base);
        return $data;
    }

    /**
     * 用户账号登录
     * @param $account [用户账号]
     * @param $password [ 用户密码 ]
     * @param $agent [ 用户渠道号 ]
     * @param $imeil
     * @param $from
     * @return User|array|\think\Response
     */
    private function loginByAccount($account, $password, $agent, $imeil, $from)
    {
        if (empty($account)) {
            return $this->response(5014, '账号不能为空');
        }
        if (empty($password)) {
            return $this->response(5012, '密码不能为空');
        }
        if (!Users::exists(['username' => $account])) {
            return $this->response(5016, '用户不存在');
        }
        $user = Users::get(['username' => $account]);
        if (empty($user->password) || $user->password !== $password) {
            return $this->response(5017, '密码不正确');
        }
        if ($user->status !== self::IS_NORMAL) {
            return $this->response(5018, '账号已冻结');
        }
        if ($agent !== $user->agent && $agent !== Users::DEFAULT_AGENT) {
            $user->agent = $agent;
        }
        // 检查 渠道 机器码 游戏
        $user->autoCheck();
        $token = Utils::tokenSave(['imeil' => $imeil, 'from' => $from, 'uid' => $user->id, 'agent' => $user->agent, 'username' => $user->username]);
        if (empty($token)) {
            return $this->response(5019, '系统繁忙');
        }
        $this->initUser($user, $token);
        $data = ['token' => $token];
        $base = $this->getUserInfo();
        $this->setUserInfo();           //0615更新用户登录时间
        $data = array_merge($data, $base);
        return $data;
    }

    protected function setUserInfo(){
        $this->model->setUserInfo($this->uid);
    }

    protected function getUserInfo()
    {
        $data = $this->model->getUserInfo($this->uid);
        return $data;
    }

    /**
     * 初始化用户 对象
     * @param $user
     * @param $token
     */
    protected function initUser($user, $token)
    {
        $this->uid = $user->id;
        $this->agent = $user->agent;
        $this->x_token = $token;
    }

    /**
     * 用户手机号注册
     * @return $this|\think\Response
     */
    public function registerByMobile()
    {
        extract($this->params(['mobile', 'password', 'agentname', 'code', 'package_list', 'imeil', 'from']));
        if (empty($mobile)) {
            return $this->response(5019, '请填写好手机号');
        }
        if (!Sms::isMobile($mobile)) {
            return $this->response(5020, '手机号格式不正确');
        }
        if (empty($code)) {
            return $this->response(5022, '验证码不能为空');
        }
        if (empty($password)) {
            return $this->response(5024, '密码不能为空');
        }
        if (empty($agentname)) {
            $agentname = Users::DEFAULT_AGENT;
        }
        try {
            $result = Users::exists(['mobile' => $mobile]);
            if ($result) {
                return $this->response(5021, '用户手机号已注册');
            }
            $r = Sms::verify($mobile, $code);
            if (Utils::isError($r)) {
                return $this->response($r);
            }
            if (!$r) {
                return $this->response(5023, '验证码错误');
            }
            $data = [
                'mobile' => $mobile,
                'password' => $password,
                'agent' => $agentname,
                'device_info' => $this->request->param('deviceinfo'),
                'reg_ua' => $this->request->param('userua'),
                'from' => intval($this->request->param('from'))
            ];

            $result = $this->model->register($data);
            if (Utils::isError($result)) {
                return $this->response($result);
            }
            if (!$result) {
                return $this->response(5029, '注册失败,请检查信息是否完整');
            }
            // 注册成功
            $token = $this->loginByMobile($mobile, Users::password($password), $agentname, $imeil, $from);
            if (is_array($token) && !empty($token['token'])) {
                return $this->response($token, '注册成功,自动登录');
            }
            if (Utils::returnAble($token)) {
                return $this->response($token);
            }
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return $this->response(5034, '服务器异常,自动登录失败');
    }

    /**
     * 用户 账号注册
     * @param $account [用户账号]
     * @param $password [用户密码]
     * @param $agent [用户渠道名]
     * @return $this|\think\Response
     */
    public function registerByAccount()
    {
        extract($this->params(['username', 'password', 'agentname', 'imeil', 'from']));
        if (empty($username)) {
            return $this->response(Utils::error(5011, '用户账号不能为空'));
        }
        if (empty($password)) {
            return $this->response(Utils::error(5012, '密码不能为空'));
        }
        if (strlen($username) < 6) {
            return $this->response(Utils::error(5024, '用户账号长度不能低于6位'));
        }
        /*if (is_numeric($username)) {
            return $this->response(Utils::error(5025, '用户账号不能使用纯数字'));
        }*/
        if (empty($agentname)) {
            $agentname = Users::DEFAULT_AGENT;
        }
        if (empty($from)) {
            $from = 1;
        }
        if (empty($imeil)) {
            $imeil = '';
        }
        try {
            $r = Users::exists(['username' => $username]);
            if ($r) {
                return $this->response(Utils::error(5026, '用户账号已存在'));
            }
            $data = [
                'username' => $username,
                'password' => $password,
                'agent' => $agentname,
                'device_info' => $this->request->param('deviceinfo'),
                'reg_ua' => $this->request->param('userua'),
                'from' => intval($from)
            ];

            $result = $this->model->register($data);

            if (Utils::isError($result)) {
                return $this->response($result);
            }
            if (!$result) {
                return $this->response(5029, '注册失败,请检查信息是否完整');
            }
            // 注册成功
            $token = $this->loginByAccount($username, Users::password($password), $agentname, $imeil, $from);
            if (is_array($token) && !empty($token['token'])) {
                return $this->response($token, '注册成功,自动登录');
            }
            if (Utils::returnAble($token)) {
                return $this->response($token);
            }
        } catch (\Exception $e) {
            Log::error($this->model->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return $this->response(5034, '服务器异常,自动登录失败');
    }

    /**
     * 找回密码
     * @return $this|\think\Response
     */
    public function forgetPassword()
    {
        $code = $this->request->param('code');
        $password = $this->request->param('password');
        $mobile = $this->request->param('mobile');
        if (empty($mobile)) {
            return $this->response(5019, '请填写好手机号');
        }
        if (!Sms::isMobile($mobile)) {
            return $this->response(5020, '手机号格式不正确');
        }
        $r = Sms::verify($mobile, $code);
        if (Utils::isError($r)) {
            return $this->response($r);
        }
        $rs = $this->model->edit(['mobile' => $mobile, 'password' => Users::password($password)]);
        if (Utils::isError($rs)) {
            return $this->response($rs);
        }
        return $this->response('ok', '重置密码成功');

    }

    /**
     * 重置 密码
     * @return $this|\think\Response
     */
    public function resetPassword()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $password = $this->request->param('new_password');
        $old_password = $this->request->param('old_password');
        try {
            $user = Users::get(['id' => $this->uid]);
        } catch (\Exception $e) {
            $user = null;
            Log::error(Utils::exportError($e));
        }
        if (empty($user)) {
            return $this->response(1110, '登录已过期');
        }
        $_password = $user->password;
        if (!Users::password($old_password, $_password)) {
            return $this->response(1119, '原密码不真正确');
        }
        $rs = $this->model->edit(['id' => $this->uid, 'password' => Users::password($password)]);
        if (Utils::isError($rs)) {
            return $this->response($rs);
        }
        // 重新登录
        Utils::getTokenSaver()->rm(Utils::getTokenKeyByType(Utils::APP_LOGIN_TOKEN, ['uid' => $this->uid, 'agent' => $this->agent]));
        return $this->response(NullClass(), '密码设置成功');
    }

    /**
     * 实名认证
     * @return $this|\think\Response
     */
    public function realName()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $user = $this->model->getUser(['id'=>$this->uid],'base3');
        if(empty($user)){
            return $this->response(4444, '请先登录');
        }
        if(!empty($user->idcard)){
            return $this->response(200,'已实名认证,无需再次认证');
        }
        $realname = $this->request->param('realname');
        $idcard = $this->request->param('id_card');
        if (empty($idcard)) {
            return $this->response(5122, '身份证号不能为空');
        }
        if (empty($realname)) {
            return $this->response(5123, '真实姓名不能为空');
        }
        $checkCard = RealName::validation_filter_id_card($idcard);
        if (empty($checkCard)) {
            return $this->response(5124, '身份证号不合法');
        }
        $rs = $this->model->edit(['id' => $this->uid, 'idcard' => $idcard, 'realname' => $realname]);
        if (Utils::isError($rs)) {
            return $this->response($rs);
        }
        return $this->response(NullClass(), '认证成功');
    }

    /**
     * 手机号绑定
     * @return $this|\think\Response
     */
    public function bindMobile()
    {
        if (empty($this->x_token)) {
            return $this->response(4444, '请先登录');
        }
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $mobile = $this->request->param('mobile');
        $code = $this->request->param('code');
        if (empty($mobile)) {
            return $this->response(5019, '请填写好手机号');
        }
        if (!Sms::isMobile($mobile)) {
            return $this->response(5020, '手机号格式不正确');
        }
        $r = Sms::verify($mobile, $code);
        if (Utils::isError($r)) {
            return $this->response($r);
        }
        $info = $this->model->getUser(['id' => $this->uid], 'base');
        if (empty($info)) {
            return $this->response(5021, '用户登录异常');
        }
        if (isset($info->mobile) && $mobile === $info->mobile) {
            return $this->response(200, '手机号已绑定');
        }
        $exist = $this->model->getUser(['mobile' => $mobile], 'base');
        if (!empty($exist) && isset($exist->mobile) && $mobile === $exist->mobile) {
            return $this->response(5402, '手机号已被其他账号绑定');
        }
        $rs = $this->model->edit(['mobile' => $mobile, 'id' => $this->uid]);
        if (Utils::isError($rs)) {
            return $this->response($rs);
        }
        return $this->response(NullClass(), '手机号绑定成功');

    }

    /**
     * 用户 编辑 信息 接口
     * @return $this|\think\Response
     */
    public function edit()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $data = $this->params(['nickname', 'signature', 'portrait']);
        $data = array_filter($data, function ($item, $k) {
            if (!is_null($k) && empty($item)) {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
        if (empty($data)) {
            return $this->response([], '修改成功');
        }
        $data['id'] = $this->uid;
        $rs = $this->model->edit($data);
        if (Utils::isError($rs)) {
            return $this->response($rs);
        }
        return $this->response(NullClass(), '修改成功');
    }

    /**
     * 获取用户信息
     * @return $this|\think\Response
     */
    public function getUser()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $result = $this->getUserInfo();
        if (Utils::isError($result)) {
            return $this->response($result);
        }
        return $this->response($result, '获取信息成功');
    }

    /**
     * 后台查找 用户(*|1)
     * @param string $id
     * @param int $length
     * @param int $page
     * @return $this|\think\Response
     */
    public function getUsers()
    {
        extract($this->params(['id', 'length', 'page']));
        if (empty($id)) {
            return $this->response(1001, '空参数错误');
        }
        if (empty($length)) {
            $length = self::DEF_PAGE_LEN;
        }
        if (empty($page)) {
            $page = self::DEF_PAGE;
        }
        $token = $this->getToken();
        if (empty($token)) {
            return $this->response(1101, '登录授权异常');
        }
        $data = Utils::isAliveToken($token, Utils::ADMIN_LOGIN_TOKEN);
        if (!$data) {
            return $this->response(1110, '登录已过期');
        }

        try {
            if (is_array($id)) {
                foreach ($id as $v) {
                    if (!is_numeric($v) && self::DEF_USER_ID !== $v) {
                        return $this->response(1007, '参数类型不匹配');
                    }
                }
                $result = $this->model->whereIn('id', $id)->filter(Users::APP_AREA_KEY)->page($page, $length)->find();
            } else if (!is_numeric($id) && self::DEF_USER_ID !== $id) {
                return $this->response(1007, '参数类型不匹配');
            } else {
                $result = $this->model->filter([])->find(['id' => $id]);
            }
            if ($result !== false) {
                return $this->response($result, '查询成功');
            }
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return $this->response(5040, '查询失败');

    }

    // 我的帖子
    public function myPost()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $result = $this->model->getPost($this->uid);
        if (Utils::isError($result)) {
            return $this->response($result);
        }
        return $this->response($result, '获取信息成功');
    }

    // 我的收藏-----帖子
    public function collectionPost()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $model = new Collection();
        $result = $model->collectionPost($this->uid);
        if (Utils::isError($result)) {
            return $this->response($result);
        }
        return $this->response($result, '获取信息成功');
    }

    // 我的收藏-----攻略
    public function collectionStrategy()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $model = new Collection();
        $result = $model->collectionStrategy($this->uid);
        if (Utils::isError($result)) {
            return $this->response($result);
        }
        return $this->response($result, '获取信息成功');
    }

    // 我的预约
    public function myAppointment()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $model = new Users();
        $result = $model->myAppointment($this->uid);
        if (Utils::isError($result)) {
            return $this->response($result);
        }
        return $this->response($result, '获取信息成功');
    }

    // 我的信息
    public function myInfo()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $model = new Users();
        $result = $model->getOrtherInfo($this->uid);
        if (Utils::isError($result)) {
            return $this->response($result);
        }
        return $this->response($result, '获取信息成功');
    }

    // 我的礼包
    public function myGift()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $model = new Users();
        $result = $model->getGifts($this->uid);
        if (Utils::isError($result)) {
            return $this->response($result);
        }
        return $this->response($result, '获取信息成功');
    }

    // 签到
    public function signIn()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $model = new Users();
        $result = $model->signIn($this->uid);
        if (Utils::isError($result)) {
            return $this->response($result);
        }
        if ($result) {
            return $this->response(NullClass(), '签到成功');
        }
        return $this->response(4777, '签到失败');
    }

    // 我的游戏
    public function myGame()
    {
        if (!$this->is_alive_token) {
            return $this->response(empty($this->code) ? 1110 : $this->code, empty($this->error) ? '登录已过期' : $this->error);
        }
        if (empty($this->uid)) {
            return $this->response(4444, '请先登录');
        }
        $model = new Users();
        $result = $model->myGame($this->uid);
        if (Utils::isError($result)) {
            return $this->response($result);
        }
        return $this->response($result, '获取我的游戏成功');
    }


}
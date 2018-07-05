<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use \think\Validate;
use app\api\model\User;
use think\Session;
use think\Config;
use app\common\lib\Fun;

class Manager extends Model
{
    const ROLE_NUM = 10;        //显示条数
    const NORMAL = '正常';
    const BAN = '禁止';
    const DEL = '删除';
    const DEL_ID = 2;
    const TIME_OUT_TOKEN = 1200;        //20分钟
    const ADMIN_LOGIN_TOKEN = 1;        // 后台登录 token 标记
    const SCOPE_ADMIN = 'admin';    
    const NORMAL_NUM = 0;   //正常
    const BLACK = 1;    //拉黑 禁止

    const UPD_MANAGER = 35;
    const BLACK_MANAGER = 36;
    const RESET_MANAGER = 37;
    const DEL_MANAGER = 38;
    const ADD_MANAGER = 39;
    //管理员列表
    public function managerList($key,$role_id,$page){
        try{
            $n = self::ROLE_NUM;
            $m = ($page-1)*$n;

            $roleModel = new Role();
            $roleList = $roleModel->getRole();
            // dump($roleList);
            $field = 'id,username,role_id,real_name,last_ip,last_time,mobile,qq,user_state';
            if (empty($role_id)) {
                $result = $this->field($field)
                    ->where('user_state','<>',self::DEL_ID)
                    ->where('username','like',$key.'%')
                    ->order('id asc')
                    ->limit($m,$n)
                    ->select();

                $countSql = $this->field($field)
                    ->where('user_state','<>',self::DEL_ID)
                    ->where('username','like',$key.'%')
                    ->count();

                // ->paginate(self::ROLE_NUM);
            }else{
                $result = $this->field($field)
                    ->where('user_state','<>',self::DEL_ID)
                    ->where('username','like',$key.'%')
                    ->where('role_id',$role_id)
                    ->order('id asc')
                    ->limit($m,$n)
                    ->select();

                $countSql = $this->field($field)
                    ->where('user_state','<>',self::DEL_ID)
                    ->where('username','like',$key.'%')
                    ->where('role_id',$role_id)
                    ->count();
                // ->paginate(self::ROLE_NUM);
            }
            Session::set('page_sum',$countSql);

            //获取对应角色和对应状态
            $userState = [
                0   =>  self::NORMAL,
                1   =>  self::BAN,
                2   =>  self::DEL
            ];
            foreach ($result as $v) {
                $v->data['role'] = $roleList[ $v->data['role_id'] ];
                $v->data['state']  = $userState[ $v->data['user_state'] ];
            }
            if($result){
                return $result;
            }else{
                return false;
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4507,'查管理员数据失败');
    }

    //添加管理员
    public function addManager($username,$password,$repassword,$real_name,$mobile,$qq,$role_id){
        try{
            $rule = [
                'username'  =>  'require|length:5,15|alphaNum',
                'password'  =>  'require|length:8,25',
                'repassword'  =>  'require|confirm:password',
                'real_name' =>  'require|chs',
                'role_id'   =>  'require',
                // 'mobile'    =>  ['regex'=>'^((13[0-9])|(14[5|7])|(15([0-3]|[5-9]))|(18[0,5-9]))\\d{8}$'],
            ];
            $msg = [
                'username.require' => '名称必须',
                'username.length' => '名称5到15个字符',
                'username.alphaNum' => '名称字母和数值组合',
                'password.require' => '密码必须',
                'password.length' => '密码8到25个字符',
                'repassword.require' => '确认密码必须',
                'repassword.confirm' => '密码必须一直',
                'real_name.require' => '联系人必须',
                'real_name.chs' => '联系人中文',
                'role_id.require' => '角色必须',
                // 'mobile.regex' => '手机格式不正确',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                'repassword'  => $repassword,
                'real_name' => $real_name,
                'role_id'   => $role_id,
                'mobile'    => $mobile,
            ];
            $validate = new Validate($rule, $msg);
            $result   = $validate->check($data);        //验证
            if($result){
                $password = User::password($password);      //密码md5加密
                $data = [
                    'username'  =>  $username,
                    'password'  =>  $password,
                    'role_id'   =>  $role_id,
                    'real_name' =>  $real_name,
                    'mobile'    =>  $mobile,
                    'qq'        =>  $qq
                ];

                $result = $this->insertGetId($data,false,true);     //添加用户入库
                if($result){
                    Fun::logWriter(self::ADD_MANAGER,$result);
                    return true;
                }else{
                    return Utils::error(4512,'添加失败');
                }
            }else{
                return $validate->getError();
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4507,'添加管理员失败');
    }

    //删除管理员
    public function delManager($id){
        try{
            $result = $this->where('id',$id)->delete();
            if($result){
                Fun::logWriter(self::DEL_MANAGER,$id);
                return true;
            }else{
                return Utils::error(4516,'删除失败');
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4515,'删除失败');
    }

    //拉黑管理员
    public function joinBlack($id){
        try{
            $manager = $this->where('id',$id)->find();
            $manager_status = $manager->user_state;
            if ($manager_status == self::NORMAL_NUM) {
                $result = $this->where('id',$id)->update(['user_state'=>self::BLACK]);
            }elseif ($manager_status == self::BLACK) {
                $result = $this->where('id',$id)->update(['user_state'=>self::NORMAL_NUM]);
            }

            if($result){
                if ($manager_status == 0){
                    Fun::logWriter(self::BLACK_MANAGER,$id);
                }elseif ($manager_status == 1){
                    Fun::logWriter(self::RESET_MANAGER,$id);
                }
                return true;
            }else{
                return Utils::error(4519,'拉黑失败');
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4520,'拉黑失败');
    }   

    //编辑管理员
    public function editManager($id){
        try{
            $result = $this->where('id',$id)->find();
            if($result){
                return $result;
            }else{
                return Utils::error(4524,'编辑管理员失败');
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4525,'编辑管理员失败');
    }

    //编辑管理员入库
    public function updateManager($id,$username,$password,$real_name,$mobile,$qq,$role_id,$user_state){
        try{
            $rule = [
                'username'  =>  'require|length:5,15|alphaNum',
                'password'  =>  'length:8,25',
                'real_name' =>  'require|chs',
                'role_id'   =>  'require',
                // 'mobile'    =>  ['regex'=>'^((13[0-9])|(14[5|7])|(15([0-3]|[5-9]))|(18[0,5-9]))\\d{8}$'],
            ];
            $msg = [
                'username.require' => '名称必须',
                'username.length' => '名称5到15个字符',
                'username.alphaNum' => '名称字母和数值组合',
                'password.length' => '密码8到25个字符',
                'real_name.require' => '联系人必须',
                'real_name.chs' => '联系人中文',
                'role_id.require' => '角色必须',
                // 'mobile.regex' => '手机格式不正确',
            ];
            //密码加密
            if (!empty($password)) {
                $password = User::password($password);      //密码md5加密
            }
            if (empty($password)) {
                $data = [
                    'username'  => $username,
                    'real_name' => $real_name,
                    'role_id'   => $role_id,
                    'mobile'    => $mobile,
                    'user_state'=> $user_state
                ];
            }else{
                $data = [
                    'username'  => $username,
                    'password'  => $password,
                    'real_name' => $real_name,
                    'role_id'   => $role_id,
                    'mobile'    => $mobile,
                    'user_state'=> $user_state
                ];
            }
            $validate = new Validate($rule, $msg);
            $result   = $validate->check($data);        //验证
            if($result){
                $result = $this->where('id',$id)->update($data);     //更新用户入库
                if($result){
                    Fun::logWriter(self::UPD_MANAGER,$id);
                    return true;
                }else{
                    return Utils::error(4527,'更新管理员失败');
                }
            }else{
                return $validate->getError();
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4526,'编辑管理员失败');
    }

    // 验证登录
    public function checkLogin($username,$password,$vcode,$ip){
        try{
            $rule = [
                'username'  =>  'require',
                'password'  =>  'require',
            ];
            $msg = [
                'username.require' => '名称必须',
                'password.require' => '密码必须',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
            ];
            $validate = new Validate($rule, $msg);
            $result   = $validate->check($data);        //验证
            
            if ($result) {
                $isUser = $this->where('username',$username)->where('user_state',self::NORMAL_NUM)->find();
                if (!$isUser) {
                    return false;       //找不到用户
                }
                $password = User::password($password);      //密码md5加密
                $check = $this->where('username',$username)->where('password',$password)->find();
                if (!$check) {
                    return false;       //密码错误
                }
                $uid = $check->data['id'];
                $username = $check->data['username'];
                $role_id = $check->data['role_id'];     //角色ID

                $roletable = Config::get('database.prefix').'role';
                $permission_ids = $this->table($roletable)->where('id',$role_id)->find()->data['permission_ids'];

                // $powers = $this->table($roletable)->where('id',$role_id)->find();
                $permission = Config::get('database.prefix').'permission';
                if ($permission_ids == '*') {
                    $permission_ids = '*';
                }else{
                    $lists = $this->table($permission)->where('id','in',$permission_ids)->select();
                }
                $powers = array();
                foreach ($lists as $v) {
                    $powers[ $v->data['pid'] ][ $v->data['uri'] ] = $v->data['title'];
                }
                Session::set('powers',$powers);
                $data = [
                    'uid'   =>  $uid,
                    'username'  =>  $username,
                    'agent' => 'default',
                    'from'  =>  2,
                    'app_id'    => 4
                ];
                $expire = self::TIME_OUT_TOKEN;
                $type = self::ADMIN_LOGIN_TOKEN;
                $scope = self::SCOPE_ADMIN;

                $getToken = Utils::tokenSave($data,$expire,$type,$scope);           //生成并保存token
//                dump($getToken);die;
                Session::set('token',$getToken);
                Session::set('username',$username);
                // Session::set('login_time',time());
                Session::set('uid',$uid);

                //更新最后登录时间
                $data = [
                    'last_time' =>  time(),
                    'last_ip'   =>  $ip
                ];
                $this->where('id',$uid)->update($data);
                return true;
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4533,'登录失败');
    }
}

<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;
use think\Session;
use app\admin\model\OaPdbPay;
use app\common\lib\Fun;

class UserData extends Model
{
    protected $table = 'c_app_users';

    const FROM_AND = 1; // 注册设备android
    const FROM_H5 = 2; // 注册设备h5
    const FROM_IOS = 3; // 注册设备ios
    const STATUS_NORMAL = 1; // 正常用户
    const STATUS_BAN = 2; // 冻结用户

    const USER_NUM = 10;        //平台用户数据显示条数

    const FORZEN_USER = 6;
    const THAW_USER = 7;
    const UPD_USER = 8;

    //  用户管理
    public function user($start_time, $end_time, $username, $nickname,$page)
    {
        try {
            $n = self::USER_NUM;
            $m = ($page-1)*$n;

            $vip = Config::get('database.prefix') . 'vip_users';
            $vip_users = Config::get('database.prefix') . 'vip_info';

            $user = $this
                ->alias('u')
                ->join($vip . ' v ', ' u.id = v.uid ', ' LEFT ')
                ->join($vip_users . ' vs ', ' vs.id = v.vid ', ' LEFT ')
                ->field('u.id,u.username,u.nickname,u.create_at,u.from,u.imei,vs.name,u.idcard,u.mobile,u.last_login_ip,u.status,u.last_login_at')
                ->where('u.username', 'like', '%' . $username . '%')
                ->where('u.nickname', 'like', '%' . $nickname . '%')
                ->whereBetween('u.create_at', [$start_time, $end_time])
                // ->paginate(5, false, ['query' => request()->param()]);
                ->limit($m,$n)
                ->select();
            
            $countSql = $this
                ->alias('u')
                ->join($vip . ' v ', ' u.id = v.uid ', ' LEFT ')
                ->join($vip_users . ' vs ', ' vs.id = v.vid ', ' LEFT ')
                ->field('u.id,u.username,u.nickname,u.create_at,u.from,u.imei,vs.name,u.idcard,u.mobile,u.last_login_ip,u.status,u.last_login_at')
                ->where('u.username', 'like', '%' . $username . '%')
                ->where('u.nickname', 'like', '%' . $nickname . '%')
                ->whereBetween('u.create_at', [$start_time, $end_time])
                ->count();
            Session::set('page_sum',$countSql);
            
            $oa = new OaPdbPay();
            foreach ($user as $v) {
                // 注册设备
                if ($v->data['from'] == self::FROM_AND) {
                    $v->data['from'] = 'Android';
                } elseif ($v->data['from'] == self::FROM_H5) {
                    $v->data['from'] = 'H5';
                } elseif ($v->data['from'] == self::FROM_IOS) {
                    $v->data['from'] = 'IOS';
                }
                // 是否实名认证
                if (empty($v->data['idcard'])) {
                    $v->data['idcard'] = '否';
                } else {
                    $v->data['idcard'] = '是';
                }
                // 是否是正常用户
                if ($v->data['status'] == self::STATUS_NORMAL) {
                    $v->data['status'] = '正常';
                } elseif ($v->data['status'] == self::STATUS_BAN) {
                    $v->data['status'] = '冻结';
                }
                $v->data['remain'] = $oa->getUserBalance($v->data['username']);
            }


            return $user;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 冻结帐号
    public function ban($id)
    {
        try {
            $status = $this->field('status')->where('id', $id)->find();
            $type = $status->status;

            if ($type == 1) {
                $this->where('id', $id)->update(['status' => 2]);
                Fun::logWriter(self::FORZEN_USER,$id);
            } elseif ($type == 2) {
                $this->where('id', $id)->update(['status' => 1]);
                Fun::logWriter(self::THAW_USER,$id);
            }

            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 修改帐号信息
    public function edit($id)
    {
        try {
            $edit = $this->where('id',$id)->field('id,username,agent')->find();
            Fun::logWriter(self::UPD_USER,$id);
            return $edit;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 修改帐号信息数据
    public function editUser($id, $password, $agent)
    {
        try {
            $data = $this->where('id',$id)->field('password')->find();
            $pass = $data->password;

            if(empty($password)){
                $this->where('id',$id)->update(['password'=>$pass,'agent'=>$agent]);
            }else{
                $this->where('id',$id)->update(['password'=>$password,'agent'=>$agent]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    //获取平台用户数据
    public function getPlatUserData($getMonth,$page){
        try{
            $n = self::USER_NUM;
            $m = ($page-1)*$n;
//            $user_data = Config::get('database.prefix') . 'user_data';
            if (empty($getMonth)) {
                $sql = "SELECT * FROM c_app_user_data WHERE 1 LIMIT {$m},{$n}";
                $data = $this->query($sql);
                $countSql = "SELECT COUNT(*) AS sum FROM c_app_user_data WHERE 1";
                $count = $this->query($countSql)[0]['sum'];
                Session::set('page_sum',$count);
            }else{
                $reg = '-'.$getMonth.'-';
                $sql = "SELECT * FROM c_app_user_data WHERE `day` regexp '{$reg}' LIMIT {$m},{$n}";
                $data = $this->query($sql);
                $countSql = "SELECT COUNT(*) AS sum FROM c_app_user_data WHERE  `day` regexp '{$reg}'";
                $count = $this->query($countSql)[0]['sum'];
                Session::set('page_sum',$count);
            }
            return $data;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '获取平台用户数据失败');
    }

    //平台用户数据入库
    public function setPlatData(){
        try{
            $users = Config::get('database.prefix') . 'users';
            $userData = Config::get('database.prefix') . 'user_data';
            $yesterTime = time()-3600*24;
            //昨天字符串时间
            $strYestTime=date('Y-m-d',$yesterTime);
            //当前时间
            $intdate = time();
            //当前字符串时间
            $day = date('Y-m-d',$intdate);
            //注册总数      总用户数
            $register = $this->table($users)->count();
            //当日活跃人数
            $active = $this->table($users)->where('last_login_at','>',$yesterTime)->count();
            //截止昨天注册总人数
            $field = 'register';
            $yesterUserRegister = $this->table($userData)->field($field)->where('day',$strYestTime)->find();
            if (empty($yesterUserRegister)){
                $yesterUserRegister = 0;
            }else{
                $yesterUserRegister = $yesterUserRegister->data['register'];
            }
            //今天注册人数(当日新增)
            $new_add = $register - $yesterUserRegister;
            //昨日新增
            $field = 'new_add';
            $yesterUserNewAdd = $this->table($userData)->field($field)->where('day',$strYestTime)->find();
            if (empty($yesterUserNewAdd)){
                $yesterUserNewAdd = 0;
            }else{
                $yesterUserNewAdd = $yesterUserNewAdd->data['new_add'];
            }
            //次日留存
            if ($yesterUserNewAdd == 0) {
                $next_retain = 0;
            }else{
                $next_retain = ($active-$new_add)/$yesterUserNewAdd;        
            }
            if ($next_retain < 0) {
                $next_retain = 0;
            }
            //三日留存
            $threeAgoTime = time()-3600*24*4;       
            $threeNewAddUser = $this->table($userData)->where('intdate','>',$threeAgoTime)->sum('new_add'); //三日前的新增
            $twoAgoTime = time()-3600*24*3;       
            $twoNewAddUser = $this->table($userData)->where('intdate','>',$threeAgoTime)->sum('new_add');      //两日前+今日 = 三日新增总人数
            if ($threeNewAddUser == 0) {
                $three_retain = 0;
            }else{
                $three_retain = (int)ceil(($active-$twoNewAddUser)/$threeNewAddUser);
            }
            if ($three_retain < 0) {
                $three_retain = 0;
            }
            //七日留存
            $weekAgoTime = time()-3600*24*8;       
            $weekNewAddUser = $this->table($userData)->where('intdate','>',$weekAgoTime)->sum('new_add'); //三日前的新增
            $sixAgoTime = time()-3600*24*7;       
            $sixNewAddUser = $this->table($userData)->where('intdate','>',$weekAgoTime)->sum('new_add');      //七日前+今日 = 七日新增总人数
            if ($weekNewAddUser == 0) {
                $week_retain = 0;
            }else{
                $week_retain = (int)ceil(($active-$sixNewAddUser)/$weekNewAddUser);
            }
            if ($week_retain < 0) {
                $week_retain = 0;
            }

            $data = [
                'day'   =>  $day,
                'register'  =>  $register,
                'active'    =>  $active,
                'new_add'   =>  $new_add,
                'next_retain' =>    $next_retain,
                'three_retain'  =>  $three_retain,
                'week_retain'   =>  $week_retain
            ];
            $result = $this->table($userData)->insert($data);
            if ($result) {
                return true;
            }else{
                return false;
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
    }

}
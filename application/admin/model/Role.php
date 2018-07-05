<?php

namespace app\admin\model;

use think\Model;
use think\Config;
use think\Log;
use app\common\lib\Utils;
use \think\Validate;
use think\Session;
use app\common\lib\Fun;

class Role extends Model
{
    const GET_NUM = 10;
    const ADD_ROLE = 40;
    const DEL_ROLE = 41;
    //获取角色
    public function getRole(){
        try{
            $result = $this->field('id,role_name')->select();
            $roleList = array();
            foreach ($result as $v) {
                $roleList[ $v->data['id'] ] = $v->data['role_name'];
            }
            if(sizeof($roleList) != 0){
                return $roleList;
            }else{
                return Utils::error(4509,'获取角色失败');
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4508,'获取角色失败');
    }

    //角色列表
    public function roleList($page){
        try{
            $n = self::GET_NUM;
            $m = ($page-1)*$n;

            $data = $this->limit($m,$n)->select();

            $countSql = $this->count();
            Session::set('page_sum',$countSql);

            //获取对应权限名
            foreach($data as $v){
                $permission_ids = $v->data['permission_ids'];
                if($permission_ids == '*'){
                    $v->data['permission'] = '所有权限';
                }else{
                    $permission = Config::get('database.prefix') . 'permission';
                    $permissionList = $this->table($permission)->where('id','in',"$permission_ids")->select();
                    // dump($result);
                    $str = '|';
                    foreach ($permissionList as $v2) {
                        $str .= $v2->data['title'].'|';
                    }
                    $v->data['permission'] = $str;
                }
            }
            return $data;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4508,'角色列表失败');
    }

    //添加角色
    public function roleUpdate($role_name,$checkbox){
        try{
            $rule = [
                'role_name'  =>  'require',
                'permission_ids'  =>  'require',
            ];
            $msg = [
                'role_name.require' => '名称必须',
                'permission_ids.require' => '权限必须',
            ];
            $check = [
                'role_name'  => $role_name,
                'permission_ids'  => $checkbox,
            ];
            $validate = new Validate($rule, $msg);
            $result   = $validate->check($check);        //验证
            
            if ($result) {
                $checkbox = implode(',',$checkbox);
                $data = [
                    'role_name' =>  $role_name,
                    'permission_ids'    =>  $checkbox
                ];
                $result = $this->insertGetId($data);
                Fun::logWriter(self::ADD_ROLE,$result);
                return true;
            }else{
                return $validate->getError();
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4508,'角色列表失败');
    }

    //删除角色
    public function delRole($id){
        try{
            $manager = Config::get('database.prefix') . 'manager';
            $has_manager = $this->table($manager)->where('role_id',$id)->find();
            if (!empty($has_manager)) {
                return false;
            }
            $result = $this->where('id',$id)->delete();
            if($result){
                Fun::logWriter(self::DEL_ROLE,$id);
                return true;
            }else{
                return Utils::error(4532,'删除失败');
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4533,'删除失败');
    }
    
}

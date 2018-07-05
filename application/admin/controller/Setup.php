<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\Manager;
use app\admin\model\Role;
use app\admin\model\Permission;
use app\common\lib\Utils;
use app\admin\controller\Wall;
use think\Session;
use app\common\lib\Fun;

class Setup extends Wall
{
    const NORMAL = '正常';
    const BAN = '禁止';
    const NUM = 10;
    public function __construct()
    {
        parent::__construct();
        $this->managerModel = new Manager();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }

    //管理员列表
    public function index(Request $request){
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }

        $key = $request->param('key');
        $role_id = $request->param('role_id');
        // //获取管理员列表
        $lists = $this->managerModel->managerList($key,$role_id,$page);   
        if ($lists == false) {
            $this->assign('lists',[]);
        }else{
            $this->assign('lists',$lists);
        }

        // 角色分类
        $roleList = $this->roleModel->getRole();
        $this->assign('roleList',$roleList);
        $this->assign('key',$key);
        $this->assign('role_id',$role_id);
        $powers = $this->permissionModel->getManagerListPower();
        $this->assign('powers',$powers);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('setup/index');
    }

    //添加管理员
    public function add(Request $request){
        if (!empty($request->param('username'))) {
            $username = $request->param('username');
            $password = $request->param('password');
            $repassword = $request->param('repassowrd');
            $real_name = $request->param('real_name');
            $mobile = $request->param('mobile');
            $qq = $request->param('qq');
            $role_id = $request->param('role_id');
            $result = $this->managerModel->addManager($username,$password,$repassword,$real_name,$mobile,$qq,$role_id);
            if($result === true){
                $this->success('新增成功','admin/setup/index');
            } else {
                $this->error($result);
            }
        }
        // 角色分类
        $roleList = $this->roleModel->getRole();
        $this->assign('roleList',$roleList);

        return $this->fetch('setup/add');
    }

    //删除管理员
    public function del(Request $request){
        $id = $request->param('id');
        if(!is_numeric($id)){
            return Utils::error(4514,'参数异常');
        }
        $result = $this->managerModel->delManager($id);
            if($result){
                $this->success('删除成功', 'admin/setup/index');
        } else {
            $this->error('删除失败');
        }
    }

    //拉黑管理员
    public function blackList(Request $request){
        $id = $request->param('id');
        if(!is_numeric($id)){
            return Utils::error(4517,'参数异常');
        }
        $result = $this->managerModel->joinBlack($id); 
        if($result){
            $this->success('修改状态成功', 'admin/setup/index');
        } else {
            $this->error('修改状态失败');
        }
    }

    //编辑管理员
    public function edit(Request $request){
        $id = $request->param('id');
        if(!is_numeric($id)){
            return Utils::error(4522,'参数异常');
        }
        //获取用户编辑信息
        $edits = $this->managerModel->editManager($id);
        $this->assign('edits', $edits);

        //用户状态
        $states = [
            0   =>  self::NORMAL,
            1   =>  self::BAN
        ];
        $this->assign('states',$states);
        // 角色分类
        $roleList = $this->roleModel->getRole();
        $this->assign('roleList',$roleList);
        return $this->fetch('setup/edit');
    }

    //编辑管理员处理
    public function update(Request $request){
        $id = $request->param('id');
        $username = $request->param('username');
        $password = $request->param('password');
        $real_name = $request->param('real_name');
        $mobile = $request->param('mobile');
        $qq = $request->param('qq');
        $role_id = $request->param('role_id');
        $user_state = $request->param('user_state');

        $result = $this->managerModel->updateManager($id,$username,$password,$real_name,$mobile,$qq,$role_id,$user_state);
        if($result){
            $this->success('编辑成功', '/admin/setup/index');
        } else {
            $this->error('编辑失败');
        }
    }

}

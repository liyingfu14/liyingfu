<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\Role;
use app\admin\model\Permission;
use app\common\lib\Utils;
use app\admin\controller\Wall;
use app\common\lib\Fun;
use think\Session;

class Roles extends Wall
{
    const NUM = 10;
    public function __construct()
    {
        parent::__construct();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }
    public function index(Request $request){
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }

        //角色列表
        $lists = $this->roleModel->roleList($page);   
        $this->assign('lists',$lists);
        // //分页
        // $page = $lists->render();
        // $this->assign('page', $page);

        $powers = $this->permissionModel->getRolesListPower();
        $this->assign('powers',$powers);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('role/index');
    }

    //添加角色
    public function add(Request $request){
        if (Request::instance()->isPost()) {
            $role_name = $request->param('role_name');
            $checkbox = $request->param('checkbox/a');
            $result = $this->roleModel->roleUpdate($role_name,$checkbox);
            if($result===true){
                $this->success('新增成功','admin/roles/index');
            } else {
                $this->error($result);
            }
        }
        //获取权限列表
        $permissionList = $this->permissionModel->getPermissionList();
        // dump($permissionList);
        $this->assign('permissionList',$permissionList);        

        return $this->fetch('role/add');
    }

    //删除角色
    public function del(Request $request){
        $id = $request->param('id');
        if(!is_numeric($id)){
            return Utils::error(4531,'参数异常');
        }
        $result = $this->roleModel->delRole($id);
        if ($result === false) {
            $this->error('该角色存在用户');
        }
        if($result){
            $this->success('删除成功', 'admin/roles/index');
        } else {
            $this->error('删除失败');
        }
    }
    
}
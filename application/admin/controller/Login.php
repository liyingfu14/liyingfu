<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\Manager;
use think\Session;

class Login extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        if ($request->isPost()) {
            $managerModel = new Manager();
    
            $username = $request->param('username');
            $password = $request->param('password');
            $vcode = $request->param('vcode');
            $ip = $request->ip();
            $result = $managerModel->checkLogin($username,$password,$vcode,$ip);
            if($result === true){
                $this->success('登录成功', 'index/index');
            } else {
                $this->error('登录失败');
            }
        }
        
        return $this->fetch('login/index');
    }

    //登出
    public function logout(){
        Session::clear();
        $this->redirect('/');
    }
}

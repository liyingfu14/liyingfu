<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\controller\Wall;
use think\Session;

class Index extends Wall
{
    public function index(){
        return $this->fetch();
    }
    public function head(){
        $username = Session::get('username');
        $this->assign('username',$username);
        $powers = Session::get('powers')[0];
        $this->assign('powers',$powers);
        return $this->fetch();
    }
    public function left(){
        return $this->fetch();
    }
    public function leftsetup(){
        $powers = Session::get('powers')[6];
        $this->assign('powers',$powers);
        return $this->fetch();
    }
    public function leftgame(){
        $powers = Session::get('powers')[1];
        $this->assign('powers',$powers);
        return $this->fetch();
    }
    public function leftdata(){
        $powers = Session::get('powers')[2];
        $this->assign('powers',$powers);
        return $this->fetch();
    }
    public function leftbanner(){
        $powers = Session::get('powers')[3];
        $this->assign('powers',$powers);
        return $this->fetch();
    }
    public function leftpost(){
        $powers = Session::get('powers')[5];
        $this->assign('powers',$powers);
        return $this->fetch();
    }
}

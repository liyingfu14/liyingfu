<?php

namespace app\admin\controller;

use think\Controller;
use think\Session;
use think\Request;

class Wall extends Controller
{
    const PTBDAY = 'admin/data/ptbDayCount';
    const RECHARGEDAY = 'admin/data/rechargeDayCount';
    const PLATDATA = 'admin/data/platData';
    public function __construct()
    {
        parent::__construct();
        if (Request::instance()->path() != self::PTBDAY && Request::instance()->path() != self::RECHARGEDAY && Request::instance()->path() != self::PLATDATA) {
            if (!Session::has('username')) {
                $this->redirect('/');
            }
        }
        $powers = Session::get('powers');
        // $current = '/'.strtolower(request()->module()).'/'.strtolower(request()->controller()).'/'.strtolower(request()->action());
    }
}

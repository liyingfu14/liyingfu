<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\UserData;
use app\admin\model\Permission;
use app\admin\model\PostGames;
use app\admin\model\Gift;
use app\admin\model\Game;
use app\admin\model\OaPdbPay;
use app\admin\model\DayPtb;
use app\admin\model\Operation;
use app\admin\model\SystemOptionLog;
use think\Session;
use app\common\lib\Fun;

class Data extends Wall
{
    const ANDROID = 1;
    const H5 = 2;
    const IOS = 3;
    const NUM = 10;
    const UNFIND = 4;
    protected $userModel;
    protected $postGameModel;
    protected $giftGameModel;
    protected $gameModel;
    protected $dayPtbModel;
    protected $operationModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserData();
        $this->postGameModel = new PostGames();
        $this->giftGameModel = new Gift();
        $this->permissionModel = new Permission();
        $this->oaPtbPayModel = new OaPdbPay();
        $this->gameModel = new game();
        $this->dayPtbModel = new DayPtb();
        $this->operationModel = new Operation();
        $this->logModel = new SystemOptionLog();
    }

    // 用户列表
    public function user(Request $request)
    {
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }

        $start_time = $request->param('start_time', date("Y-m-d", 1514736000));
        $this->view->assign('start_time', $start_time);

        $end_time = $request->param('end_time', date("Y-m-d", 1546185600));
        $this->view->assign('end_time', $end_time);

        $username = $request->param('username');
        $this->assign('username', $username);

        $nickname = $request->param('nickname');
        $this->assign('nickname', $nickname);

        $lists = $this->userModel->user(strtotime($start_time), strtotime($end_time), $username, $nickname,$page);
        // $page = $lists->render();
        // $this->assign('page', $page);

        $this->assign('lists', $lists);

        $powers = $this->permissionModel->getUserListPower();
        $this->assign('powers', $powers);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('data/user');
    } 

    // 用户冻结
    public function ban(Request $request)
    {
        $id = $request->param('id');
        $del = $this->userModel->ban($id);

        if ($del) {
            $this->success('操作成功', 'admin/data/user');
        } else {
            $this->error('操作失败');
        }
    }

    // 编辑用户
    public function editUser(Request $request)
    {
        $id = $request->param('id');
        $edit = $this->userModel->edit($id);
        $this->assign('edit', $edit);
        return $this->fetch('data/editUser');
    }

    // 编辑用户数据
    public function editUserData(Request $request)
    {
        $id = $request->param('id');
        $password = $request->param('password');
        $agent = $request->param('agent');
        $edit = $this->userModel->editUser($id, $password, $agent);
        if ($edit) {
            $this->success('修改成功', 'admin/data/user');
        } else {
            $this->error('修改失败');
        }
    }

    // 礼包列表
    public function gift(Request $request)
    {
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }

        // 时间下拉
        $time = [
            ['id' => 1, 'value' => '未过期'],
            ['id' => 2, 'value' => '已过期'],
        ];
        $this->assign('time', $time);

        // 游戏下拉
        $game = $this->postGameModel->GameList();
        $this->assign('game', $game);


        $name = $request->param('name');
        $this->assign('name', $name);

        $gid = $request->param('gid');


        $times = $request->param('time');
        $lists = $this->giftGameModel->gift($name, $gid, $times, $page);

        // $page = $lists->render();
        // $this->assign('page', $page);

        $this->assign('lists', $lists);

        $powers = $this->permissionModel->getGiftListPower();
        $this->assign('powers', $powers);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('data/gift');
    }

    // 删除礼包
    public function delGift(Request $request)
    {
        $id = $request->param('id');
        $del = $this->giftGameModel->del($id);

        if ($del) {
            $this->success('删除成功', 'admin/data/gift');
        } else {
            $this->error('删除失败');
        }
    }

    // 添加礼包
    public function addGift()
    {
        // 游戏下拉
        $game = $this->postGameModel->GameList();
        $this->assign('game', $game);

        //礼包类型
        $giftlist = [
            ['id' => 1, 'value' => 'VIP1礼包'],
            ['id' => 2, 'value' => 'VIP2礼包'],
            ['id' => 3, 'value' => 'VIP3礼包'],
            ['id' => 4, 'value' => 'VIP4礼包'],
            ['id' => 5, 'value' => 'VIP5礼包'],
            ['id' => 6, 'value' => 'VIP6礼包'],
            ['id' => 7, 'value' => 'VIP7礼包'],
            ['id' => 8, 'value' => 'VIP8礼包'],
            ['id' => 9, 'value' => '平台礼包'],
        ];
        $this->assign('giftlist', $giftlist);

        return $this->fetch('data/addGift');
    }

    //获取平台用户数据
    public function userData(Request $request)
    {
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }
        $getMonth = $request->param('month');
        $this->assign('getMonth', $getMonth);
        $dataLists = $this->userModel->getPlatUserData($getMonth, $page);
        $this->assign('dataLists', $dataLists);
        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $this->assign('months', $months);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('data/userdata');
    }

    // 添加礼包数据
    public function addGiftData(Request $request)
    {
        $game = $request->param('game');
        $title = $request->param('title');
        $code = $request->param('code');
        $content = $request->param('content');
        $directions = $request->param('directions');
        $start_time = $request->param('start_time');
        $end_time = $request->param('end_time');
        $type = $request->param('type');
        if (empty($game)) {
            $this->error('游戏名称为空');
        }
        if (empty($type)) {
            $this->error('礼包类型为空');
        }
        if (empty($title)) {
            $this->error('礼包名称为空');
        }
        if (empty($code)) {
            $this->error('礼包码为空');
        }
        if (empty($content)) {
            $this->error('礼包内容为空');
        }
        if (empty($directions)) {
            $this->error('使用说明为空');
        }
        if (empty($start_time)) {
            $this->error('开始兑换时间为空');
        }
        if (empty($end_time)) {
            $this->error('结束时间为空');
        }
        if (preg_match("/[\x7f-\xff]/", $code)) {
            $this->error('礼包码请勿用中文');
        }

        $del = $this->giftGameModel->add($game, $title, $code, $content, $directions, strtotime($start_time), strtotime($end_time), $type);

        if ($del) {
            $this->success('添加成功', 'admin/data/gift');
        } else {
            $this->error('添加失败');
        }
    }

    // 编辑礼包页面
    public function editGift(Request $request)
    {
        // 游戏下拉
        $game = $this->postGameModel->GameList();
        $this->assign('game', $game);


        //礼包类型
        $giftlist = [
            ['id' => 1, 'value' => 'VIP1礼包'],
            ['id' => 2, 'value' => 'VIP2礼包'],
            ['id' => 3, 'value' => 'VIP3礼包'],
            ['id' => 4, 'value' => 'VIP4礼包'],
            ['id' => 5, 'value' => 'VIP5礼包'],
            ['id' => 6, 'value' => 'VIP6礼包'],
            ['id' => 7, 'value' => 'VIP7礼包'],
            ['id' => 8, 'value' => 'VIP8礼包'],
            ['id' => 9, 'value' => '平台礼包'],
        ];
        $this->assign('giftlist', $giftlist);

        $id = $request->param('id');
        $lists = $this->giftGameModel->edit($id);
        $this->assign('lists', $lists);

        $code = $this->giftGameModel->code($id);
        $this->assign('code', $code);
        return $this->fetch('data/editGift');
    }

    // 编辑礼包数据
    public function editGiftData(Request $request)
    {
        $id = $request->param('id');
        $game = $request->param('game');
        $type = $request->param('type');
        $title = $request->param('title');
        $content = $request->param('content');
        $directions = $request->param('directions');
        $start_time = $request->param('start_time');
        $end_time = $request->param('end_time');

        $data = $this->giftGameModel->editGift($id, $game, $type, $title, $content, $directions, strtotime($start_time), strtotime($end_time));
        if ($data) {
            $this->success('修改成功', 'admin/data/gift');
        } else {
            $this->error('修改失败');
        }
    }

    //游戏平台数据处理入库  定时任务?
    public function platData(Request $request)
    {
        $this->userModel->setPlatData();
    }

    //消费数据
    public function consumption(Request $request){
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }
        $orderKey = $request->param('orderKey');
        if (!empty($orderKey)) {
            $this->assign('orderKey',$orderKey);
        }else{
            $this->assign('orderKey','');
        }
        $userKey = $request->param('userKey');
        if (!empty($userKey)) {
            $this->assign('userKey',$userKey);
        }else{
            $this->assign('userKey','');
        }
        $nickKey = $request->param('nickKey');
        if (!empty($nickKey)) {
            $this->assign('nickKey',$nickKey);
        }else{
            $this->assign('nickKey','');
        }

        $appGameList = $this->gameModel->getAllGame();      //获取app库游戏列表
        $gameLists = $this->oaPtbPayModel->getGameList($appGameList);    //筛选出app和oa共有游戏列表
        if (!empty($gameLists)) {
            $this->assign('gameLists',$gameLists);
        }else{
            $this->assign('gameLists',[]);
        }
        $gameKey = $request->param('gameKey');
        if (!empty($gameKey)) {
            $this->assign('gameKey',$gameKey);
        }else{
            $this->assign('gameKey','');
        }

        $lists = $this->oaPtbPayModel->getPtbPay($orderKey,$userKey,$nickKey,$gameKey,$page);
        if (empty($lists)) {
            $this->assign('lists',[]);
        }else{
            $this->assign('lists',$lists);
        }

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('data/consumption');
    }

    //充值汇总
    public function rechargeSum(Request $request){
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }
        $monthLists = [
            '1' =>  '01月',
            '2' =>  '02月',
            '3' =>  '03月',
            '4' =>  '04月',
            '5' =>  '05月',
            '6' =>  '06月',
            '7' =>  '07月',
            '8' =>  '08月',
            '9' =>  '09月',
            '10' =>  '10月',
            '11' =>  '11月',
            '12' =>  '12月'
        ];
        $this->assign('monthLists',$monthLists);
        $monthKey = $request->param('monthKey');
        if(empty($monthKey)){
            $this->assign('monthKey','');
        }else{
            $this->assign('monthKey',$monthKey);
        }
        $lists = $this->dayPtbModel->getRechargeSum($monthKey,$page);
        if (empty($lists)) {
            $this->assign('lists',[]);
        }else{
            $this->assign('lists',$lists);
        }

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('data/rechargeSum');
    }

    //充值数据
    public function rechargeData(Request $request){
        $param = $request->param();
        $page = $request->param('p');

        $orderKey = $request->param('orderKey');
        if (!empty($orderKey)) {
            $this->assign('orderKey',$orderKey);
        }else{
            $this->assign('orderKey','');
        }

        $userKey = $request->param('userKey');
        if(!empty($userKey)){
            $this->assign('userKey',$userKey);
        }else{
            $this->assign('userKey','');
        }

        $sTimeKey = $request->param('sTimeKey');
        if(!empty($sTimeKey)){
            $this->assign('sTimeKey',$sTimeKey);
        }else{
            $this->assign('sTimeKey','');
        }

        $eTimeKey = $request->param('eTimeKey');
        if (!empty($eTimeKey)) {
            $this->assign('eTimeKey',$eTimeKey);
        }else{
            $this->assign('eTimeKey','');
        }

        $payWayLists = $this->oaPtbPayModel->getPayWay();
        if(!empty($payWayLists)){
            $this->assign('payWayLists',$payWayLists);
        }else{
            $this->assign('payWayLists',[]);
        }

        $payWayKey = $request->param('payWayKey');
        if(!empty($payWayKey)){
            $this->assign('payWayKey',$payWayKey);
        }else{
            $this->assign('payWayKey','');
        }

        $lists = $this->oaPtbPayModel->getRecharge($page,$orderKey,$userKey,$sTimeKey,$eTimeKey,$payWayKey);  
        if (empty($lists)) {
            $this->assign('lists',[]);
        }else{
            $this->assign('lists',$lists);
        }
        
        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);
        return $this->fetch('data/rechargeData'); 
    }

    //C币管理
    public function ptbAdministration(Request $request){
        $param = $request->param();
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }
        $sTimeKey = $request->param('sTimeKey');
        if(!empty($sTimeKey)){
            $this->assign('sTimeKey',$sTimeKey);
        }else{
            $this->assign('sTimeKey','');
        }

        $eTimeKey = $request->param('eTimeKey');
        if (!empty($eTimeKey)) {
            $this->assign('eTimeKey',$eTimeKey);
        }else{
            $this->assign('eTimeKey','');
        }
        
        $lists = $this->dayPtbModel->getPtbAdministration($param,$sTimeKey,$eTimeKey,$page);
        if (empty($lists)) {
            $this->assign('lists',[]);
        }else{
            $this->assign('lists',$lists);
        }

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('data/ptbAdministration');
    }

    //C币发放/扣除
    public function ptbGrant(Request $request){
        if(Request::instance()->isPost()){
            $param = $request->param();
            if ($param['player'] == 0){
                $result = $this->oaPtbPayModel->userPtbGrant($param);
                if ($result === true) {
                    $user = Session::get('username');
                    $log = '用户'.$user.'发放了C币';
                    $this->operationModel->addOperationRecord($type = 2, $log);
                    $this->success('操作成功', 'admin/data/ptbGrant');
                }elseif($result === false){
                    $this->error('操作失败');
                }else if ($result === self::UNFIND) {
                    $this->error('用户不存在');
                }else{
                    $this->error($result);
                }
            }elseif ($param['player'] == 1){
                $result = $this->oaPtbPayModel->userPtbDel($param);
                if ($result === true) {
                    $user = Session::get('username');
                    $log = '用户'.$user.'扣除了C币';
                    $this->operationModel->addOperationRecord($type = 2, $log);
                    $this->success('操作成功', 'admin/data/ptbGrant');
                }elseif($result === false){
                    $this->error('操作失败');
                }else if ($result === self::UNFIND) {
                    $this->error('用户不存在');
                }else{
                    $this->error($result);
                }
            }
        }
        return $this->fetch('data/ptbGrant');
    }

    //C币管理统计 定时任务
    public function ptbDayCount(Request $request){
        $data = $this->oaPtbPayModel->getResultPtbDayCount();
        $this->dayPtbModel->setDayPtb($data);
    }

    //充值汇总 定时任务
    public function rechargeDayCount(Request $request){
        $data = $this->oaPtbPayModel->getResultRechargeDayCount();
        $re = $this->dayPtbModel->setDayRecharge($data);
    }

    //操作日志
    public function handlerlog(Request $request){
        $param = $request->param();
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }

        $sTimeKey = $request->param('sTimeKey');
        if(!empty($sTimeKey)){
            $this->assign('sTimeKey',$sTimeKey);
        }else{
            $this->assign('sTimeKey','');
        }

        $eTimeKey = $request->param('eTimeKey');
        if (!empty($eTimeKey)) {
            $this->assign('eTimeKey',$eTimeKey);
        }else{
            $this->assign('eTimeKey','');
        }

        $userKey = $request->param('userKey');
        if (!empty($userKey)) {
            $this->assign('userKey',$userKey);
        }else{
            $this->assign('userKey','');
        }

        $searchKey = $request->param('search');
        if (!empty($searchKey)) {
            $this->assign('searchKey',$searchKey);
        }else{
            $this->assign('searchKey','');
        }

        $lists = $this->logModel->getLogLists($page,$sTimeKey,$eTimeKey,$userKey,$searchKey);
        if (empty($lists)) {
            $this->assign('lists',[]);
        }else{
            $this->assign('lists',$lists);
        }

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('data/handlerLog');
    }

    //导出日志
    public function exportLog(){

        $this->logModel->exportLogReport();
        
    }
}
<?php

namespace app\admin\controller;

use app\admin\model\Community;
use think\Request;
use app\admin\model\BannerIndex;
use app\admin\model\PostGames;
use app\admin\model\Upload;
use app\admin\model\Permission;
use think\Config;
use app\common\lib\Fun;
use think\Session;

class Banner extends Wall
{
    const NUM = 5;
    const GET_NUM = 10;

    protected $bannerModel;
    protected $communityModel;
    protected $postGameModel;
    protected $uploadGameModel;

    public function __construct()
    {
        parent::__construct();
        $this->bannerModel = new BannerIndex();
        $this->communityModel = new Community();
        $this->postGameModel = new PostGames();
        $this->uploadGameModel = new Upload();
        $this->permissionModel = new Permission();
    }


    // 轮播图列表1
    public function index(Request $request)
    {
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }

        //广告位下拉
        $adlist = [
            ['id' => 1, 'value' => '首页-banner'],
            ['id' => 2, 'value' => '首页-广告图'],
            ['id' => 4, 'value' => '首页-推荐图'],
            ['id' => 3, 'value' => '游戏-精选图'],
        ];
        $this->assign('adlist', $adlist);


        // 状态下拉
        $status = [
            ['id' => 1, 'value' => '已启用'],
            ['id' => 2, 'value' => '已停用'],
        ];
        $this->assign('status', $status);

        $start_time = $request->param('start_time', date("Y-m-d", 1514736000));
        $this->view->assign('start_time', $start_time);

        $end_time = $request->param('end_time', date("Y-m-d", 1546185600));
        $this->view->assign('end_time', $end_time);

        // 轮播图列表
        $banner = $request->param('ad');    // 获取广告位
        $status = $request->param('status');    // 获取状态

        $ad = $this->bannerModel->ad($banner, $status, strtotime($start_time), strtotime($end_time), $page);
        $this->assign('ad', $ad);

        $powers = $this->permissionModel->getBannerListPower();
        $this->assign('powers', $powers);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('banner/index');
    }

    // 删除轮播图
    public function del(Request $request)
    {
        $id = $request->param('id');
        $del = $this->bannerModel->del($id);

        if ($del) {
            $this->success('删除成功', 'admin/banner/index');
        } else {
            $this->error('删除失败');
        }
    }

    // 停用轮播图
    public function stop(Request $request)
    {
        $id = $request->param('id');
        $del = $this->bannerModel->stop($id);

        if ($del) {
            $this->success('操作成功', 'admin/banner/index');
        } else {
            $this->error('操作失败');
        }
    }

    // 社区列表
    public function community(Request $request)
    {
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }

        // 状态下拉
        $status = [
            ['id' => 1, 'value' => '已启用'],
            ['id' => 2, 'value' => '已停用'],
        ];
        $this->assign('status', $status);

        $start_time = $request->param('start_time', date("Y-m-d", 1514736000));
        $this->view->assign('start_time', $start_time);

        $end_time = $request->param('end_time', date("Y-m-d", 1546185600));
        $this->view->assign('end_time', $end_time);

        $name = $request->param('name');
        $status = $request->param('status');    // 获取状态
        $this->assign('name', $name);
        $lists = $this->communityModel->community($name, $status, strtotime($start_time), strtotime($end_time),$page);
        // $page = $lists->render();
        // $this->assign('page', $page);
        $this->assign('lists', $lists);

        $powers = $this->permissionModel->getCommunityListPower();
        $this->assign('powers', $powers);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::GET_NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('banner/community');
    }

    // 停用社区
    public function stopComm(Request $request)
    {
        $id = $request->param('id');
        $del = $this->communityModel->stop($id);

        if ($del) {
            $this->success('操作成功', 'admin/banner/community');
        } else {
            $this->error('操作失败');
        }
    }

    // 删除社区
    public function delComm(Request $request)
    {
        $id = $request->param('id');
        $del = $this->communityModel->del($id);
        if ($del) {
            $this->success('删除成功', 'admin/banner/community');
        } else {
            $this->error('删除失败');
        }
    }

    // 添加轮播图页面展示
    public function addBanner()
    {
        $adminConfig = Config::load(CONF_PATH . 'admin.php', 'admin');
        $url = $adminConfig['upload_token_url'];
        $login_token = session('token');
        $data = ['type' => 'image'];
        $xApp = $adminConfig['upload_p'];
        $getUploadToken = Fun::uploadToken($url, $login_token, $data, $xApp);
        if (empty(json_decode($getUploadToken)->data)) {
            return $this->error('该账号已在其他地方登陆','admin/login/index');
        }
        $uploadToken = json_decode($getUploadToken)->data->token;

        $this->assign('xapp', $xApp);
        $this->assign('uploadToken', $uploadToken);
        $this->assign('token', $login_token);

        //广告位下拉
        $adlist = [
            ['id' => 1, 'value' => '首页-banner'],
            ['id' => 2, 'value' => '首页-广告图'],
            ['id' => 4, 'value' => '首页-推荐图'],
            ['id' => 3, 'value' => '游戏-精选图'],
        ];
        $this->assign('adlist', $adlist);

        //广告位下拉
        $isUse = [
            ['id' => 1, 'value' => '启用'],
            ['id' => 2, 'value' => '停用'],
        ];
        $this->assign('isUse', $isUse);

        // 游戏下拉
        $game = $this->postGameModel->GameList();
        $this->assign('game', $game);

        return $this->fetch('banner/addBanner');
    }

    // 添加轮播图数据
    public function addBannerData(Request $request)
    {
        $type = $request->param('type');
        $game = $request->param('game');
        $use = $request->param('use');
        $banner = $request->param('index_banner');
        if (empty($type) || $type <= 0) {
            $this->error('请选择轮播图类型');
        }
        if (empty($game) || $game <= 0) {
            $this->error('请选择游戏');
        }
        if (empty($use) || $use <= 0) {
            $this->error('请勾选是否使用');
        }
        if (empty($banner)) {
            $this->error('请选择图片');
        }
        $add = $this->bannerModel->addBanner($type, $game, $use, $banner);

        if ($add) {
            $this->success('添加成功', 'admin/banner/index');
        } else {
            $this->error('添加失败');
        }
    }

    // 编辑轮播图页面
    public function editBanner(Request $request)
    {
        $token = Fun::getUploadToken();
        if ($token == false){
            return $this->error('验证过期','admin/login/index');
        }
        $this->assign('xapp', $token['xapp']);
        $this->assign('uploadToken', $token['uploadToken']);
        $this->assign('token', $token['token']);

        //广告位下拉
        $adlist = [
//            ['id' => 1, 'value' => '首页-banner'],
//            ['id' => 2, 'value' => '首页-广告图'],
//            ['id' => 3, 'value' => '游戏-精选图'],
            ['id' => 1, 'value' => '首页-banner'],
            ['id' => 2, 'value' => '首页-广告图'],
            ['id' => 4, 'value' => '首页-推荐图'],
            ['id' => 3, 'value' => '游戏-精选图'],
        ];
        $this->assign('adlist', $adlist);

        //广告位下拉
        $isUse = [
            ['id' => 1, 'value' => '启用'],
            ['id' => 2, 'value' => '停用'],
        ];
        $this->assign('isUse', $isUse);

        // 游戏下拉
        $game = $this->postGameModel->GameList();
        $this->assign('game', $game);

        $id = $request->param('id');
        $lists = $this->bannerModel->edit($id);
        $this->assign('lists', $lists);
        return $this->fetch('banner/editBanner');
    }

    // 编辑修改轮播图页面数据
    public function editBannerData(Request $request)
    {
        $id = $request->param('id');
        $type = $request->param('type');
        $game = $request->param('game');
        $use = $request->param('use');
        $pic = $request->param('pics');
        if (empty($type) || $type <= 0) {
            $this->error('请选择轮播图类型');
        }
        if (empty($game) || $game <= 0) {
            $this->error('请选择游戏');
        }
        if (empty($use) || $use <= 0) {
            $this->error('请勾选是否使用');
        }

        $add = $this->bannerModel->editBanner($id, $type, $game, $use, $pic);

        if ($add) {
            $this->success('修改成功', 'admin/banner/index');
        } else {
            $this->error('修改失败');
        }
    }

    //添加社区页面
    public function addCommunity(Request $request)
    {
        $token = Fun::getUploadToken();
        if($token === false){
            return $this->error('验证过期','admin/login/index');
        }
        $this->assign('xapp', $token['xapp']);
        $this->assign('uploadToken', $token['uploadToken']);
        $this->assign('token', $token['token']);

        //广告位下拉
        $isUse = [
            ['id' => 1, 'value' => '启用'],
            ['id' => 2, 'value' => '停用'],
        ];
        $this->assign('isUse', $isUse);

        // 游戏下拉
        $game = $this->postGameModel->GameList();
        $this->assign('game', $game);

        return $this->fetch('banner/addCommunity');
    }

    // 添加社区数据
    public function addCommunityData(Request $request)
    {
        $title = $request->param('title');
        $content = $request->param('content');
        $game = $request->param('game');
        $use = $request->param('use');
        $commImg = $request->param('index_community');
        if (empty($title)) {
            $this->error('请填写社区标题');
        }
        if (empty($content)) {
            $this->error('请填写社区介绍');
        }
        if (empty($game) || $game <= 0) {
            $this->error('请选择游戏');
        }
        if (empty($use) || $use <= 0) {
            $this->error('请勾选是否使用');
        }
        $add = $this->communityModel->addCommunity($title, $content, $game, $use, $commImg);

        if ($add) {
            $this->success('添加成功', 'admin/banner/community');
        } else {
            $this->error('添加失败');
        }
    }

    // 编辑社区页面
    public function editCommunity(Request $request)
    {
        $token = Fun::getUploadToken();
        if ($token === false){
            return $this->error('验证过期','admin/login/index');
        }
        $this->assign('xapp', $token['xapp']);
        $this->assign('uploadToken', $token['uploadToken']);
        $this->assign('token', $token['token']);

        //广告位下拉
        $isUse = [
            ['id' => 1, 'value' => '启用'],
            ['id' => 2, 'value' => '停用'],
        ];
        $this->assign('isUse', $isUse);

        // 游戏下拉
        $game = $this->postGameModel->GameList();
        $this->assign('game', $game);

        $id = $request->param('id');
        $lists = $this->communityModel->edit($id);
        $this->assign('lists', $lists);
        return $this->fetch('banner/editCommunity');
    }

    // 编辑社区数据
    public function editCommunityData(Request $request)
    {
        $id = $request->param('id');
        $title = $request->param('title');
        $content = $request->param('content');
        $game = $request->param('game');
        $use = $request->param('use');
        $pic = $request->param('pics');
        if (empty($title)) {
            $this->error('请填写社区标题');
        }
        if (empty($content)) {
            $this->error('请填写社区介绍');
        }
        if (empty($game) || $game <= 0) {
            $this->error('请选择游戏');
        }
        if (empty($use) || $use <= 0) {
            $this->error('请勾选是否使用');
        }
        $add = $this->communityModel->editCommunity($id, $title, $content, $game, $use, $pic);

        if ($add) {
            $this->success('修改成功', 'admin/banner/community');
        } else {
            $this->error('修改失败');
        }
    }
}
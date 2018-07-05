<?php

namespace app\admin\controller;

use think\Request;
use app\admin\model\GameCategory;
use app\admin\model\GameTags;
use app\admin\model\Game;
use app\admin\model\Menu;
use app\admin\model\Permission;
use think\Session;
use app\common\lib\Utils;
use app\common\lib\Fun;


class Games extends Wall
{
    protected $cateModel;
    protected $tagsModel;
    protected $gameModel;

    const GAME_NUM = 8;     //游戏显示条数  ***要与模型一致***
    const ADD_GAME = 1;
    const UPD_GAME = 2;

    public function __construct()
    {
        parent::__construct();
        $this->cateModel = new GameCategory();
        $this->tagsModel = new GameTags();
        $this->gameModel = new Game();
        $this->permissionModel = new Permission();
        $this->menuModel = new Menu();
    }

    //游戏列表
    public function index(Request $request){
        $game_status = $request->param('game_status');      //游戏状态正常下架发布
        $this->assign('game_status',$game_status);
        $cate_id = $request->param('cate_id');      //所属分类
        $this->assign('cate_id',$cate_id);
        $is_select = $request->param('is_select');      //是否精选 热门 普通
        $this->assign('is_select',$is_select);
        $up_time = $request->param('up_time');      //上线时间
        $this->assign('up_time',$up_time);
        $page = $request->param('p');      //页码

        if (empty($page)) {
            $page = 1;
        }

        //获取上线时间(3天 7天 1个月)
        $threeDay = time() - 24*3600*3;
        $oneWeek = time() - 24*3600*7;
        $oneMonth = time() - 24*3600*30;

        $timeLists = [
            '1'   =>  '三天内',
            '2'    =>  '七天内',
            '3'   =>  '一个月内'
        ];
        $this->assign('timeLists',$timeLists);

        //游戏状态(搜索栏)
        $gameStatus = [
            [ 'id' => 3, 'value' => '正常' ],
            [ 'id' => 1, 'value' => '下架' ],
            [ 'id' => 2, 'value' => '未发布' ]
        ];
        if (!empty($game_status)) {
            $this->assign('gameCheck',$game_status);
        }else{
            $this->assign('gameCheck',[]);
        }
        $this->assign('gameStatus',$gameStatus);   
        //获取一级游戏分类列表(搜索栏)
        $cateLists = $this->cateModel->getCateMenu();         
        $this->assign('cateLists',$cateLists);
        //是否精选 精选 热门 普通(搜索栏)
        $tagsLists = $this->tagsModel->getTagsStatus();      
        $this->assign('tagsLists',$tagsLists);

        if(!empty($up_time)){
            $this->assign('timeKey',$up_time);
        }else{
            $this->assign('timeKey','');
        }

        if ($up_time == 1) {
            $up_time = $threeDay;
        }elseif($up_time == 2){
            $up_time = $oneWeek;
        }elseif($up_time == 3){
            $up_time = $oneMonth;
        }

        //获取游戏列表
        $gameLists = $this->gameModel->gameList($game_status,$cate_id,$is_select,$up_time,$page);          
        $this->assign('gameLists',$gameLists);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::GAME_NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        $powers = $this->permissionModel->getGameListPower();
        $this->assign('powers',$powers);
        // dump($powers);

        return $this->fetch('game/index');
    }

    //删除游戏
    public function del(Request $request){
        $gameId = $request->param('id');
        if(!is_numeric($gameId)){
            return Utils::error(4537,'参数异常');
        }
        $result = $this->gameModel->delGame($gameId);
        if($result){
             $this->success('删除成功', 'admin/games/index');
        } else {
            $this->error('删除失败');
        }
    }

    //下架游戏
    public function lowerFrame(Request $request){
        $gameId = $request->param('id');
        if(!is_numeric($gameId)){
            return Utils::error(4538,'参数异常');
        }
        $result = $this->gameModel->lowerFrameGame($gameId);
        if($result){

            $this->success('修改成功', 'admin/games/index');
        } else {
            $this->error('下架失败');
        }
    }

    //添加游戏
    public function add(Request $request){
        $token = Fun::getUploadToken();
        if ($token === false){
            return $this->error('验证过期','admin/login/index');
        }
        $this->assign('xapp',$token['xapp']);
        $this->assign('uploadToken',$token['uploadToken']);
        $this->assign('token',$token['token']);

        if (Request::instance()->isPost()) {
            $param = $request->param();
            $result = $this->gameModel->addGame($param);
            if($result === true){
                $this->success('新增成功','admin/games/index');
            } else {
                $this->error($result);
            }
        }
        //获取一级分类
        $topCategoryLists = $this->cateModel->getCateMenu();        
        $this->assign('topCategoryLists', $topCategoryLists);
        //精选 推荐 普通 单选列表
        $tagsStatusLists = $this->tagsModel->getTagsStatus();  
        $this->assign('tagsStatusLists',$tagsStatusLists);

        //首页列表
        $menus = $this->menuModel->getIndexMenu();
        $this->assign('menus',$menus);

        //获取最新最热
        $hot_news = $this->tagsModel->getHotOrNew();
        $this->assign('hot_news',$hot_news);
        
        return $this->fetch('game/add');
    }

    //获取二级分类
    public function getSceondCategory(Request $request){
        $topCategoryId = $request->param('id');
        $secondCategory = $this->cateModel->getSecondMenu($topCategoryId);
        return json_encode($secondCategory);
    }

    //编辑游戏
    public function upd(Request $request){
        $token = Fun::getUploadToken();
        if ($token === false){
            return $this->error('验证过期','admin/login/index');
        }
        $this->assign('xapp',$token['xapp']);
        $this->assign('uploadToken',$token['uploadToken']);
        $this->assign('token',$token['token']);

        $id = $request->param('id');
        if(!is_numeric($id)){
            return Utils::error(4545,'参数异常');
        }
        //获取用户编辑信息
        $edit = $this->gameModel->editGame($id);
        $edit['c_time'] = substr($edit['create_time'],0,10);
        $this->assign('edit', $edit);
        //获取编辑游戏的分类
        $gameCate = '';
        $gameCate = $this->cateModel->updCate($id);
        $gameCate = array_keys($gameCate);
        if (!empty($gameCate)) {
            $this->assign('PCate', $gameCate[0]);
            $this->assign('CCate', $gameCate[1]);
            $childCateLists = $this->cateModel->getSecondMenu($gameCate[0]);
            $childCateLists = $this->cateModel->changeArr($childCateLists);
            $this->assign('childCateLists', $childCateLists);
        }

        //获取游戏标签
        $gameTag = $this->tagsModel->getGameTags($id);
        $gameTag = implode(',',$gameTag);
        $this->assign('gameTag',$gameTag);

        //游戏是否精选 热门 普通
        $is_select = $this->tagsModel->getIsHotOrIsSelect($id);
        if (empty($is_select)) {
            $is_select = '';
        }else{
            $is_select = $is_select[0];
        }
        $this->assign('is_select',$is_select);

        //获取一级分类
        $topCategoryLists = $this->cateModel->getCateMenu();        
        $this->assign('topCategoryLists', $topCategoryLists);
        //精选 推荐 普通 单选列表
        $tagsStatusLists = $this->tagsModel->getTagsStatus();  
        $this->assign('tagsStatusLists',$tagsStatusLists);

        $menus = $this->menuModel->getIndexMenu();
        $this->assign('menus',$menus);

        //游戏是否首页
        $is_menu = $this->menuModel->getIsMenu($id);
        if (sizeof($is_menu) != 0) {
            $is_menu = $is_menu[0];        
            $this->assign('is_menu',$is_menu);
        }else{
            $is_menu = '';
            $this->assign('is_menu',$is_menu);
        }

        //获取最新最热
        $hot_news = $this->tagsModel->getHotOrNew();
        $this->assign('hot_news',$hot_news);
        $selectHotNew = $this->tagsModel->getSelectHotNew($id);
        if (sizeof($selectHotNew) != 0) {
            $selectHotNew = $selectHotNew[0];        
            $this->assign('selectHotNew',$selectHotNew);
        }else{
            $selectHotNew = '';
            $this->assign('selectHotNew',$selectHotNew);
        }
        return $this->fetch('game/upd');
    }

    //编辑游戏处理
    public function updgame(Request $request){
        $param = $request->param();
        $result = $this->gameModel->updGame($param);

        if($result){

            $this->success('编辑成功','admin/games/index');
        } else {
            $this->error($result);
        }
    }
}


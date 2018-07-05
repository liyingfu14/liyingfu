<?php

namespace app\api\controller;

use app\common\controller\BaseApi;
use app\api\model\Gift ;
use think\Request;
use app\api\model\Game;
use app\api\model\GameServer;
use app\api\model\GameCommentList;
use app\api\model\Gamenews;
use app\api\model\GameBanner;
use app\api\model\GameCategory;
use app\common\lib\Utils ;
use think\Log ;

class Games extends BaseApi
{
    const GET_ALL = 1 ; // 获取 所有 游戏信息 标志
    const TYPE_SELECT = 1; // 游戏精选
    const TYPE_NEW = 2; // 最新
    const TYPE_RANK = 3;    // 游戏排行


    //获取游戏礼包     
    public function gifts(Request $request){
        $gameId = $request -> param('id');  //获取游戏id
        //根据token获取用户uid
        $userId = null;
        if ($this->x_token && $this->is_alive_token) {
            $userId = $this->uid;
        }
        $model = new Gift;
        $this->setHeader('x-token',time());
        $data = $model -> giftList($gameId,$userId);
        if (!empty($data['code']) && !empty($data['msg'])) {
            return $this->response($data);
        }
        return $this->response(Utils::success($data));
    }

    //获取礼包详情
    public function giftinfo(Request $request){
        $gid = $request->param('gid');
        $userId = null;
        if ($this->x_token && $this->is_alive_token) {
            $userId = $this->uid;
        }
        $model = new Gift;
        $this->setHeader('x-token',time());
        $data = $model -> giftDetail($gid,$userId);
        if (!empty($data['code']) && !empty($data['msg'])) {
            return $this->response($data);
        }
        return $this->response(Utils::success($data));
    }

    //领取礼包
    public function getGift(Request $request){
        $giftId = $request -> param('gid');     //获取礼包id
        //根据token获取用户uid
        $userId = null;
        if ($this->x_token && $this->is_alive_token) {
            $userId = $this->uid;
        } else {
            return $this->response(4444, '未登录状态无法访问');
        }
        $model = new Gift;
        $this->setHeader('x-token',time());
        $data = $model -> userGetGift($giftId,$userId);
        if (!empty($data['code']) && !empty($data['msg'])) {
            return $this->response($data);
        }
        return $this->response(Utils::success($data));
    }

    //获取游戏信息 
    public function info(Request $request){
        $gameId = $request -> param('id');   //获取游戏id
        $gameName = $request -> param('name');    //获取游戏名
        $gameType = $request -> param('type');    //获取游戏标签
        $gameAll = $request -> param('all');    //获取所有游戏

        $model = new Game();
        if($gameAll == self::GET_ALL ){
            $data = $model -> gameAll();
            return $this -> response(['data'=>$data,'code'=>200],'json',200);
        }elseif (!empty($gameId)) {
            $data = $model -> gameById($gameId);
            if(empty($data)){
                return $this->response(Utils::error(4011,'获取游戏失败'));
            }
            return $this->response(Utils::success($data));
        }elseif(!empty($gameName)){
            $data = $model -> gameByName($gameName);
            return $this->response(Utils::success($data));
        }elseif(!empty($gameType)){
            $data = $model -> gameByType($gameType);
            return $this->response(Utils::success($data));
        }else{
            return $this->response(Utils::error(4011,'获取游戏失败'));
        }
    }

    //搜索游戏
    public function searchGame(Request $request){
            $model = new Game();
            $gameName = $request -> param('name');    //获取游戏名
            $data = $model -> gameByName($gameName);
//            if(empty($data)){
//                return $this->response(Utils::error(4012,'获取游戏失败'));
//            }
            return $this->response(Utils::success($data,'获取游戏成功'));
    }

    //获取游戏开服信息
    public function serverInfo(Request $request){
            $ide = $request -> param('type');   //获取开服状态
            $page = $request -> param('page');   //获取游戏id
            $long = $request -> param('long');   //获取游戏id
            $model = new GameServer();

            $data = $model -> gameServerInfo($ide,$page,$long);
            if($data){
                return $this->response(Utils::success($data,'获取开服信息成功'));
            }else{
                return $this->response(Utils::error(4003,'获取开服信息失败'));
            }
    }

    //获取游戏总评分
    public function gameScore(Request $request){
            $gameId = $request -> param('id');   //获取游戏id
            $gameModel = new Game();
            $data = $gameModel -> sumScore($gameId);
            if($data){
                return $this->response(Utils::success($data));
            }else{
                return $this->response(['msg'=>'获取游戏总评分失败','code'=>4004]);
            }
    }

    //通过游戏id获取游戏的用户评分
    public function userScore(Request $request){
        $gameId = $request -> param('id');   //获取游戏id
        $page = $request -> param('page');   //获取游戏页码
        $long = $request -> param('long');   //获取一页显示条数

        $uid = null ;
        if($this->x_token && $this->is_alive_token){
            $uid = $this->uid ;
        }
        if (empty($long) || preg_match("/^[0-9]{1,2}$/",$long)){        //显示条数验证
            $GamecommentModel = new GameCommentList();
            $data = $GamecommentModel -> getUserScore($gameId,$page,$long,$uid);
            return $this->response(Utils::success($data));
        }else{
            return $this->response(Utils::error(4005,'获取游戏失败'));
        }
    }

    //通过游戏id获取游戏攻略(body)
    public function strategy(Request $request){
            $gameId = $request -> param('id');   //获取游戏id
            $page = $request -> param('page');   //获取游戏页码
            $postType = $request -> param('type');   //
            $long = $request -> param('long');   //获取一页显示条数
            $key = $request -> param('key');   //关键字
            if (empty($page)) {
                $page = 1;
            }
            if (empty($long) || preg_match("/^[0-9]{1,2}$/",$long)){        //显示条数验证
                $GamenewsModel = new Gamenews();
                $data = $GamenewsModel -> getStrategy($gameId,$page,$postType,$key,$long);
                return $this->response(Utils::success($data));
            }else{
                return $this->response(Utils::error(4006,'获取游戏失败'));
            }
    }

    //通过游戏id获取游戏攻略(head)
    public function strategyTop(Request $request){
            $postType = $request -> param('type');   //获取游戏页码
            $GamenewsModel = new Gamenews();
            $data = $GamenewsModel -> getStrategyTop($postType);
            if($data){
                return $this->response(Utils::success($data));
            }else{
                return $this->response(Utils::error(4006,'获取攻略分类失败'));
            }
    }

    //通过游戏id获取游戏资讯
    public function gameInformation(Request $request){
            $gameId = $request -> param('id');   //获取游戏id
            $GamenewsModel = new Gamenews();
            $data = $GamenewsModel -> getInformation($gameId);
            if(empty($data)){
                // return $this->response(Utils::error(4007,'获取资讯失败'));
                return $this->response([]);
            }
            return $this->response(Utils::success($data));
    }

    //发表游戏评论
    public function review(Request $request){
        $appId = $request -> param('id');        //获取游戏ID
        $uid = $request -> param('uid');            //获取用户ID
        $score = $request -> param('score');        //用户评分
        $thumbs = 0;
        $comment = $request -> param('comment');    //用户评论
        $commentTime = time();
        
        $uid = null ;
        if($this->x_token && $this->is_alive_token){
            $uid = $this->uid ;
        }else{
            return $this->response(4444,'未登录状态无法访问');
        }
        $comment = Utils::replaceLimitWord($comment);
        $data = [
            'app_id'    =>  $appId,
            'uid'       =>  $uid,
            'score'     =>  $score,
            'comment'   =>  $comment,
            'comment_time'  =>  $commentTime,
            'thumbs'    =>  $thumbs,
        ];
        // dump($data);
        if($data['app_id'] == '' || $data['uid'] == '' || $data['score'] == '' || $data['comment'] == '' || $data['comment_time'] == ''){
            return $this->response(Utils::error(4008,'评论失败'));
        }else{
            $GamecommentModel = new GameCommentList();
            $res = $GamecommentModel->insert($data);
            $res = [];
            return $this->response(Utils::success($res));
            // return $this -> response(['data'=>$res,'msg'=>'评论成功','code'=>200],'json',200);
        }
    }

    //用户点赞其他用户的评论
    public function clickThumbs(Request $request){
        $uid = null ;
        if($this->x_token && $this->is_alive_token){
            $uid = $this->uid ;
        }else{
            return $this->response(4444,'未登录状态无法访问');
        }
        $commentId = $request -> param('cid');      //评论的ID
        $GamecommentModel = new GameCommentList();
        $data = $GamecommentModel -> userClick($commentId,$uid);
        if(empty($data)){
            return $this->response(Utils::error(4010,'点赞失败'));
        }
        $data = [];
        return $this->response(Utils::success($data));
    }

    //游戏版本接口  //mechine未定义,接口暂时不用,需要再开启
    public function gameVerion(Request $request){
            $gameModel = new Game();
            $gameId = $request -> param('id');      //获取游戏id
            $gameVer = $request -> param('ver');      //获取游戏版本
            // dump($gameId);
            if($this->isIos){
                $mechine = 'device_ios_url';
            }
            if($this->isAndroid){
                $mechine = 'device_android_url';
            }
            $data = $gameModel -> checkVersion($gameId,$mechine,$gameVer);
            if (!empty($data['code']) && !empty($data['msg'])) {
                return $this->response($data);
            }
            // return $this -> response(['data'=>$data,'code'=>200],'json',200);
            return $this->response(Utils::success($data));
    }

    // 首页轮播图和广告图
    public function banner(){
        $model = new GameBanner();
        $data = $model->getBanner();
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response(Utils::success($data));
    }

    //游戏分类接口
    public function gameCategory(Request $request){
            $cateModel = new GameCategory();
            $data = $cateModel -> getCategory();
            if($data){
                return $this->response(Utils::success($data,'获取分类成功'));
            }else{
                return $this->response(Utils::error(4014,'获取分类失败'));
            }
    }

    //游戏热搜接口
    public function gameHot(Request $request){
        $gameModel = new Game();
        $data = $gameModel -> getIsHot();
        if (!empty($data)) {
            return $this->response(Utils::success($data,'热搜词'));
        }
        return $this->response(Utils::error(4013,'获取热搜失败'));
    }

    //分类页通过分类获取游戏(头部)
    public function cateGameTop(Request $request){
            $parentId = $request -> param('pid');     //获取一级分类ID
            $childId = $request -> param('cid');     //获取二级分类ID
            // $type = $request -> param('type');     //获取最新或最热
            // $page = $request -> param('page');     //获取页码
            // $long = $request -> param('long');     //获取显示长度
            $gameModel = new Game();
            // $data = $gameModel -> getCateGame($parentId,$childId,$type,$page,$long);
            $data = $gameModel -> getCateGameTop($parentId,$childId);
            if($data){
                return $this->response(Utils::success($data,'获取分类游戏成功'));
            }else{
                return $this->response(Utils::error(4015,'获取分类游戏失败'));
            }
    }

    //分类页通过分类获取游戏(主体)
    public function cateGameBody(Request $request){
            $parentId = $request -> param('pid');     //获取一级分类ID
            $childId = $request -> param('cid');     //获取二级分类ID
            $type = $request -> param('type');     //获取最新或最热
            $page = $request -> param('page');     //获取页码
            $long = $request -> param('long');     //获取显示长度
            $gameModel = new Game();
            $data = $gameModel -> getCateGameBody($parentId,$childId,$type,$page,$long);
//            if($data){
                return $this->response(Utils::success($data,'获取分类游戏成功'));
//            }else{
//                return $this->response(Utils::error(4015,'获取分类游戏失败'));
//            }
    }

    // 精选游戏、最新游戏列表、游戏排行
    public function newGame(){
            $newGame = new Game();
            $page = $this->request->param('page', 1);   // 获取页码
            $length = $this->request->param('length', 10);   // 获取页长
            $type = $this->request->param('type',self::TYPE_SELECT);   // 游戏接口类型。1为精选游戏，2为最新游戏，3为游戏排行。默认为1
    
            if($type == self::TYPE_SELECT){
                $data = $newGame->getSelectGame($page, $length); 
            }
            if($type == self::TYPE_NEW){
                $data = $newGame->getNewGame($page, $length);
            }
            if($type == self::TYPE_RANK){
                $data = $newGame->rankByDownload($page, $length);
            }
    
            if (Utils::isError($data)) {
                return $this->response($data);
            }
    
            return $this->response(Utils::success($data));
    }

    //精选页背景图
    public function gameBG(Request $request){
            $gbModel = new GameBanner();
            $data = $gbModel -> getSelectedBanner();
            if($data){
                return $this->response(Utils::success($data));
            }else{
                return $this->response(Utils::error(4020,'获取失败'));
            }
    }

    // 游戏预约
    public function gameAppointment(){
        $appointment = new Game();
        $page = $this->request->param('page', 1);   // 获取页码
        $length = $this->request->param('length', 10);   // 获取页长
        $data = $appointment->newGameAppointment($page, $length);
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response(Utils::success($data));
    }

    // 预约
    public function appointment($app_id){
        $uid = null ;

        if($this->x_token && $this->is_alive_token){
            $uid = $this->uid ;
        }else{
            return $this->response(4444,'未登录状态无法访问');
        }
        $appointment = new Game();

        $data = $appointment->appointment(intval($app_id), intval($uid));
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response([],'预约成功');
    }

    //礼包页四个推荐游戏
    public function fourGame(){
        $gameModel = new Game();
        $data = $gameModel -> getFourGame();
        return $this->response($data);
    }

    //ios返回包名
    public function allBundleId(){
        $gameModel = new Game();
        $data = $gameModel->getAllBundleId();
        if($data){
            return $this->response(Utils::success($data));
        }else{
            return $this->response(Utils::error(4020,'获取所有boundleId失败'));
        }
    }

    //搜索攻略
    public function searchStrategy(Request $request){
        $gamenewsModel = new Gamenews();
        $gameId = $request -> param('id');
        $postType = $request -> param('type');
        $key = $request->param('key');
        $data = $gamenewsModel->getSearchStrategy($gameId,$postType,$key);
        if($data){
            return $this->response(Utils::success($data));
        }else{
            return $this->response(Utils::error(4547,'搜索攻略失败'));
        }
    }
}

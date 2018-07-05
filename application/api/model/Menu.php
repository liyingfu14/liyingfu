<?php
namespace app\api\model;

use think\Model;
use think\Db;
use think\Config ;
use think\Log;
use app\common\lib\Utils;
use app\api\model\Game;
use app\api\model\GameBanner;

class Menu extends Model 
{
    const MENU_1 = '推荐游戏';
    const MENU_2 = '网游';
    const MENU_3 = 'H5游戏';
    const MENU_4 = '单机游戏';
    const MENU_1_NUM = 5;
    const MENU_2_NUM = 3;
    const MENU_3_NUM = 4;
    const MENU_4_NUM = 4;
    const GIFT_ON = 1;      //礼包上架状态
    const GIFT_OFF= 2;      //礼包下架状态

    const IS_DELETE = 2;    //游戏是否删除 1为删除 3为正常
    const NORMAL = 3;       //正常

    public function getMenu(){
        try{
            $gameModel = new Game();
            $game = Config::get('database.prefix').'game';
            $gift = Config::get('database.prefix').'gift';
            $arr = array();
            $data = $this->select();
            foreach ($data as $v) {
                $menu = $v->data['menu_name'];
                //菜单一数据(推荐游戏)
                if( $menu == self::MENU_1 ){
                    $field = 'game_id';
                    $menuGameId = $this->field($field)->where('menu_name',$menu)->find();
                    $menuGameId = $menuGameId->data['game_id'];     //获取菜单游戏ID 
                    
                    #首个推荐游戏 
                    $field = 'id,icon,name,advertisement,download,ios_size,android_size,device_ios_url,device_android_url,backgroup,android_mac_id,ios_mac_id';
                    if (defined('IS_IOS')){
                        $gameList = $this->table($game)
                            ->field($field)
                            ->where('ios_bundle_id','<>','')
                            ->where('id','in',$menuGameId)
                            ->where('is_delete',self::IS_DELETE)
                            ->where('status',self::NORMAL)
                            ->where('create_time','<',time())
                            ->order('download desc')->find();
                    }else{
                        $gameList = $this->table($game)
                            ->field($field)
                            ->where('id','in',$menuGameId)
                            ->where('is_delete',self::IS_DELETE)
                            ->where('status',self::NORMAL)
                            ->where('create_time','<',time())
                            ->order('download desc')->find();
                    }
                    if (empty($gameList)) {
                        $arr['recommend'] = [];     //无推荐游戏
                    }else{
                        $bannerModel = new GameBanner();
                        $gameList->data['backgroup'] = $bannerModel->getFirstRecommendBanner();
                        $gameId = $gameList->data['id'];
                        $cate_name = $this->getCategory($gameId);
                        if (sizeof($cate_name) != 0) {
                            $gameList->data['cate_name'] = $cate_name[0];        //按二级分类排序取第一个
                        }
                        if (sizeof($cate_name) == 0) {
                            $gameList->data['cate_name'] = '无';        //按二级分类排序取第一个
                        }
                        $gameList->data['is_H5'] = $gameModel->isHGame($gameId);
                        $arr['first_recommend'] = $gameList;
    
                        #获取推荐游戏信息 
                        $field = 'id,icon,name,advertisement';
                        if (defined('IS_IOS')){
                            $gameList = $this->table($game)
                                ->field($field)
                                ->where('ios_bundle_id','<>','')
                                ->where('id','in',$menuGameId)
                                ->where('is_delete',self::IS_DELETE)
                                ->where('status',self::NORMAL)
                                ->where('create_time','<',time())
                                ->order('download desc')
                                ->limit(1,self::MENU_1_NUM)
                                ->select();
                        }else{
                            $gameList = $this->table($game)
                                ->field($field)
                                ->where('id','in',$menuGameId)
                                ->where('is_delete',self::IS_DELETE)
                                ->where('status',self::NORMAL)
                                ->where('create_time','<',time())
                                ->order('download desc')
                                ->limit(1,self::MENU_1_NUM)
                                ->select();
                        }
                        foreach ($gameList as $gv) {
                            $gameId = $gv->data['id'];      //game表游戏ID
                            $giftCount = $this->table($gift)
                                ->where('app_id',$gameId)
                                ->where('end_time','>',time())
                                ->where('is_on',self::GIFT_ON)
                                ->find();           //统计对应游戏礼包数量
                            if ($giftCount) {
                                $gv->data['is_gift'] = true;
                            }else{
                                $gv->data['is_gift'] = false;
                            }
                            $gv->data['is_H5'] = $gameModel->isHGame($gameId);
                        }
                        $arr['recommend'] = $gameList;
                    }
                }

                //菜单二数据(网游)
                $arr['online_game'] = $this->getMenuGame( self::MENU_2 );

                //菜单二数据(H5游戏)
                $arr['H5_game'] = $this->getMenuGame( self::MENU_3 );

                //菜单二数据(单机游戏)
                $arr['single_game'] = $this->getMenuGame( self::MENU_4 );
//                if( $menu == self::MENU_4 ){
//                    $field = 'game_id';
//                    $menuGameId = $this->field($field)->where('menu_name',$menu)->find();
//                    $menuGameId = $menuGameId->data['game_id'];     //获取菜单游戏ID
//
//                    $field = 'id,icon,name';
//                    $gameList = $this->table($game)
//                        ->field($field)
//                        ->where('id','in',$menuGameId)
//                        ->where('is_delete',self::IS_DELETE)
//                        ->where('status',self::NORMAL)
//                        ->where('create_time','<',time())
//                        ->order('download desc')
//                        ->limit(self::MENU_4_NUM)
//                        ->select();   //获取推荐游戏信息
//                    foreach ($gameList as $gv) {
//                        $gameId = $gv->data['id'];
//                        $gv->data['is_H5'] = $gameModel->isHGame($gameId);
//                    }
//
//                    $arr['single_game'] = $gameList;
//                }
            }
            return $arr;
        }catch(\Exception $e){
            Log::error(Utils::exportError($e));
        }
        return [];
    }

    //游戏id获取标签
    protected function getTag($gameId){
        $game = Config::get('database.prefix').'game';
        $tags = Config::get('database.prefix').'game_tags';
        $field = 'tags';
        $result = $this->table($game)->field($field)->where('id',$gameId)->find();
        $strTags = $result->data['tags'];
        $tag = explode(',',$strTags)[0];        //获取第一个标签ID

        $result = $this->table($tags)->where('id',$tag)->find();
        return $result->data['tag_name'];
    }

    //游戏id获取分类
    protected function getCategory($gameId){
        $cates = array();
        $game = Config::get('database.prefix').'game';
        $category = Config::get('database.prefix').'game_category';
        $field = 'id,cate_name,game_id';
        $result = $this->table($category)->field($field)->where('pid','<>',0)->order('sort desc')->select();
        $arr = [];
        foreach ($result as $v) {
            $arr[] = $v->data;
        }
        foreach ($arr as $v2) {
            $arr2 = array();
            $arr2 = explode(',',$v2['game_id']);
            if(in_array($gameId,$arr2)){
                $cates[] = $v2['cate_name'];
            }
        }
        return $cates;
    }


    public function getMenuGame($menu){
        try{
            $gameModel = new Game();
            $game = Config::get('database.prefix').'game';
            $field = 'game_id';
            $menuGameId = $this->field($field)->where('menu_name',$menu)->find();
            $menuGameId = $menuGameId->data['game_id'];     //获取菜单游戏ID
            $field = 'id,icon,name,advertisement,download,ios_size,android_size,device_ios_url,device_android_url,android_mac_id,ios_mac_id';
            if (defined('IS_IOS')){
                $gameList = $this->table($game)
                    ->field($field)
                    ->where('ios_bundle_id','<>','')
                    ->where('id','in',$menuGameId)
                    ->where('is_delete',self::IS_DELETE)
                    ->where('status',self::NORMAL)
                    ->where('create_time','<',time())
                    ->order('download desc')
                    ->limit(self::MENU_2_NUM)->select();   //获取推荐游戏信息
            }else{
                $gameList = $this->table($game)
                    ->field($field)
                    ->where('id','in',$menuGameId)
                    ->where('is_delete',self::IS_DELETE)
                    ->where('status',self::NORMAL)
                    ->where('create_time','<',time())
                    ->order('download desc')
                    ->limit(self::MENU_2_NUM)->select();   //获取推荐游戏信息
            }

            foreach ($gameList as $gv) {
                $gameId = $gv->data['id'];
                $cate_name = $this->getCategory($gameId);
                if (sizeof($cate_name) != 0) {
                    $gv->data['cate_name'] = $cate_name[0];        //按二级分类排序取第一个
                }
                if (sizeof($cate_name) == 0) {
                    $gv->data['cate_name'] = '无';        //按二级分类排序取第一个
                }
                $gv->data['is_H5'] = $gameModel->isHGame($gameId);
            }
            unset($cate_name);
            return $gameList;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
    }

}

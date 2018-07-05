<?php
namespace app\api\model;

use think\Model;
use app\common\lib\Utils;
use think\Config ;
use think\Log;

class Game extends Model
{
    const GET_NUM = 10;
    const GET_HOT = 5;
    const IS_NEW = '最新';
    const IS_HOT = '最热';
    const IS_SELECT = '精选';
    const RAND_NUM = 4;

    const IS_DELETE = 2;    //游戏是否删除 1为删除 2为正常
    const NORMAL = 3;
    const TYPE_SELECT = 1; // 游戏精选
    const TYPE_NEW = 2; // 最新
    const TYPE_RANK = 3;    // 游戏排行


    protected $dateFormat = false; // 时间格式 Unix

    protected $table = 'c_app_game';



    //根据游戏id获取游戏
    public function gameById($gameId){
        $table = Config::get('database.prefix').'gift';
        $remain_gift = $this->table($table)->where('app_id',$gameId)->where('mem_id',0)->count();
        $total = $this->where('id',$gameId)->find();
        if(empty($total)){
            return;
        }
        $total->data['remain_gift'] = $remain_gift;
        $extend_pic = explode(',',$total->data['extend_pic']);
        $total->data['extend_pic'] = $extend_pic;
        $total->data['is_H5'] = $this->isHGame($gameId);
        return $total;
    }

    //根据游戏名获取
    public function gameByName($gameName){
        try{
            $field = 'id,name';
            $total = $this->field($field)
                ->where('name','like',$gameName.'%')
                ->where('is_delete',self::IS_DELETE)
                ->where('status',self::NORMAL)
                ->where('create_time','<',time())
                ->select();
            return $total;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }

    //根据标签获取
    public function gameByType($gameType){
        $total = $this->where('type',$gameType)->select();
        return $total;
    }

    //获取所有游戏
    public function gameAll(){
        $total = $this->where('is_delete',self::IS_DELETE)->where('status',self::NORMAL)->select();
        return $total;
    }

    //获取游戏总评分
    public function sumScore($gameId){
        $field = 'score';
        $total = $this->field($field)->where('id',$gameId)->find();
        return $total;
    }

    //检查游戏版本
    public function checkVersion($gameId,$mechine,$gameVer){
        $table = Config::get('database.prefix').'game';
        $data = $this->where('id',$gameId)->find();
        if(!$data){
            return ['msg' => '数据异常', 'code' => 4013];
        }
        $ver = $data->data['version'];
        if($gameVer != $ver){
            return $data->data[ $mechine ];
        }else{
            return "无需更新";
        }
    }

    //热搜
    public function getIsHot(){
        try{
            $field = "id,name";
            if (defined('IS_IOS')){
                $data = $this->field($field)
                    ->where('ios_bundle_id','<>','')
                    ->where('is_delete',self::IS_DELETE)
                    ->where('status',self::NORMAL)
                    ->where('create_time','<',time())
                    ->order('download desc')
                    ->limit(self::GET_HOT)
                    ->select();
            }else{
                $data = $this->field($field)
                    ->where('is_delete',self::IS_DELETE)
                    ->where('status',self::NORMAL)
                    ->where('create_time','<',time())
                    ->order('download desc')
                    ->limit(self::GET_HOT)
                    ->select();
            }
            return $data;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }

    //分类页通过分类ID获取游戏      type: 0为最新  1为最热
    public function getCateGameTop($parentId,$childId){
        try{
            $cate = Config::get('database.prefix').'game_category';     //分类表
            $tags = Config::get('database.prefix').'game_tags';         //标签表
            $comment = Config::get('database.prefix').'game_comment_list';         //评论表
            $field = 'id,cate_name,pid';
            $cate_list = $this->table($cate)->field($field)->where('id',$parentId)->whereOr('pid',$parentId)->select();
            foreach ($cate_list as $v) {
                
                if ($v->data['pid'] == 0) {
                    $v->data['cate_name'] = '全部';
                }
            }
            return $cate_list;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }


        //分类页通过分类ID获取游戏      type: 0为最新  1为最热
    public function getCateGameBody($parentId,$childId,$type,$page,$long){
        try{
            $cate = Config::get('database.prefix').'game_category';     //分类表
            $tags = Config::get('database.prefix').'game_tags';         //标签表
            $comment = Config::get('database.prefix').'game_comment_list';         //评论表

            if(empty($parentId)){
                return '';
            }
            if(empty($long)){
                $long = self::GET_NUM;  //默认条数
            }
            if(empty($page)){
                $page = 1;      //默认第一页
            }
            if(empty($childId)||$childId == 0){
                $cateId = $parentId;
            }elseif(!empty($childId)){
                $cateId = $childId;
            }
            // 获取要查询的标签
            if ($type == 0) {
                $tag_name = self::IS_NEW;
                $con = 'create_time';
            }elseif($type == 1){
                $tag_name = self::IS_HOT;
                $con = 'download';
            }
            $field = "id,game_id";
            $sql = "SELECT {$field} FROM `$cate` WHERE `id` = {$cateId} ";
            $cateGameId = $this->query($sql);
            $cateGameId = $cateGameId[0]['game_id'];      //获取分类game_id字段

            $field = "id,game_id";
            $sql = "SELECT {$field} FROM `$tags` WHERE `tag_name` = '{$tag_name}' ";
            $tagGameId = $this->query($sql);
            $tagGameId = $tagGameId[0]['game_id'];      //获取标签game_id字段

            $field = 'id,name,icon,device_ios_url,device_android_url,score,advertisement,ios_size,android_size,android_mac_id,ios_mac_id';
            if (defined('IS_IOS')){
                $res = $this->field($field)
                    ->where('ios_bundle_id','<>','')
                    ->where('id','in',$cateGameId)
                    ->where('id','in',$tagGameId)
                    ->where('is_delete',self::IS_DELETE)
                    ->where('status',self::NORMAL)
                    ->where('create_time','<',time())
                    ->page($page)
                    ->paginate($long);
            }else{
                $res = $this->field($field)
                    ->where('id','in',$cateGameId)
                    ->where('id','in',$tagGameId)
                    ->where('is_delete',self::IS_DELETE)
                    ->where('status',self::NORMAL)
                    ->where('create_time','<',time())
                    ->page($page)
                    ->paginate($long);
            }
            $game_list = array();
            foreach ($res as $v) {
                $gameId = $v->data['id'];
                $count = $this->table($comment)->where('app_id',$gameId)->count();
                $v->data['count'] = $count;
                $v->data['is_H5'] = $this->isHGame($gameId);
                $game_list[] = $v->data;
            }
            return $game_list;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }


    // 获取最新游戏列表     //加判断是否H5
    public function getNewGame($page = 1, $length = 10, $type = self::TYPE_NEW){
        $comment = Config::get('database.prefix') . 'game_comment_list';
        $gift = Config::get('database.prefix') . 'gift';
        if (!is_numeric($length) || !is_numeric($page)) {
            return Utils::error(4016, '参数类型错误');
        }
        if (empty($page) || empty($length)) {
            return Utils::error(4017, '参数不能为空');
        }
        if ($page < 0 || $length < 0) {
            return Utils::error(4018, '参数不能为负');
        }
        if (empty($type) || $type > self::TYPE_RANK) {
            return Utils::error(4019, '类型为空');
        }
        try{
            if (defined('IS_IOS')){
                $data = $this->alias('g')
                    ->join($comment . ' c ', ' g.id = c.app_id ', 'LEFT' )
                    ->join($gift . ' f ', ' g.id = f.app_id ', 'LEFT' )
                    ->cache(true, 60)
                    ->page($page, $length)
                    ->field('g.id, g.icon, g.name, g.score, g.device_ios_url, g.device_android_url, g.advertisement, g.ios_size, g.android_size, g.create_time,g.android_mac_id,g.ios_mac_id, g.ios_bundle_id, count(c.app_id) as sum_comment')
                    ->where('g.ios_bundle_id','<>','')
                    ->where('g.create_time','<',time())
                    ->where('g.is_delete',self::IS_DELETE)
                    ->group('g.id')
                    ->order('g.create_time desc')
                    ->select();
            }else{
                $data = $this->alias('g')
                    ->join($comment . ' c ', ' g.id = c.app_id ', 'LEFT' )
                    ->join($gift . ' f ', ' g.id = f.app_id ', 'LEFT' )
                    ->cache(true, 60)
                    ->page($page, $length)
                    ->field('g.id, g.icon, g.name, g.score, g.device_ios_url, g.device_android_url, g.advertisement, g.ios_size, g.android_size, g.create_time,g.android_mac_id,g.ios_mac_id, g.ios_bundle_id, count(c.app_id) as sum_comment')
                    ->where('g.create_time','<',time())
                    ->where('g.is_delete',self::IS_DELETE)
                    ->group('g.id')
                    ->order('g.create_time desc')
                    ->select();
            }
            $arr = array();
            foreach ($data as $v) {
                $gameId = $v->data['id'];
                $comm = $this->table($comment)->where('app_id',$gameId)->count();
                $v->data['sum_comment'] = $comm;
                $giftCount = $this->table($gift)->where('app_id',$gameId)->where('mem_id',0)->count();      //统计对应游戏礼包数量
                if ($giftCount == 0) {
                    $v->data['is_gift'] = false;
                }else{
                    $v->data['is_gift'] = true;
                }
                $v->data['is_H5'] = $this->isHGame($gameId);
                $arr[] = $v->data;
            }
            return $data;
            // return ['content' => $data, 'page' => $page, 'length' => count($data), 'count' => $count];
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }

        return Utils::error(2006, '数据异常');
    }

    // 游戏排行-------by排序
    public function rankBySort($page = 1, $length = 10, $type = self::TYPE_RANK){
        $comment = Config::get('database.prefix') . 'game_comment_list';
        $gift = Config::get('database.prefix') . 'gift';
        if (!is_numeric($length) || !is_numeric($page)) {
            return Utils::error(4016, '参数类型错误');
        }
        if (empty($page) || empty($length)) {
            return Utils::error(4017, '参数不能为空');
        }
        if ($page < 0 || $length < 0) {
            return Utils::error(4018, '参数不能为负');
        }
        if (empty($type) || $type > self::TYPE_RANK) {
            return Utils::error(4019, '类型为空');
        }
        try{
            $count = $this->count();
            $data = $this->alias('g')
                ->join($comment . ' c ', ' g.id = c.app_id ', 'LEFT' )
                ->cache(true, 60)
                ->page($page, $length)
                ->field('g.id, g.icon, g.name, g.score, g.device_ios_url, g.device_android_url, g.advertisement, g.ios_size, g.android_size, g.create_time, g.sort, count(c.app_id) as sum_comment')
                ->group('g.id')
                ->order('g.sort desc')
                ->select();
            #加入判断礼包
            foreach ($data as $v) {
                $gameId = $v->data['id'];
                $comm = $this->table($comment)->where('app_id',$gameId)->count();
                $v->data['sum_comment'] = $comm;
                $giftCount = $this->table($gift)->where('app_id',$gameId)->where('mem_id',0)->count();      //统计对应游戏礼包数量
                if ($giftCount == 0) {
                    $v->data['is_gift'] = false;
                }else{
                    $v->data['is_gift'] = true;
                }
                $arr[] = $v->data;
            }
            return $data;
            // return ['content' => $data, 'page' => $page, 'length' => count($data), 'count' => $count, 'type' => $type];
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }

        return Utils::error(2006, '数据异常');
    }

    // 游戏排行-------by评分
    public function rankByScore($page = 1, $length = 10, $type = self::TYPE_RANK){
        $comment = Config::get('database.prefix') . 'game_comment_list';
        $gift = Config::get('database.prefix') . 'gift';
        if (!is_numeric($length) || !is_numeric($page)) {
            return Utils::error(4016, '参数类型错误');
        }
        if (empty($page) || empty($length)) {
            return Utils::error(4017, '参数不能为空');
        }
        if ($page < 0 || $length < 0) {
            return Utils::error(4018, '参数不能为负');
        }
        if (empty($type) || $type > self::TYPE_RANK) {
            return Utils::error(4019, '类型为空');
        }
        try{
            $count = $this->count();
            $data = $this->alias('g')
                ->join($comment . ' c ', ' g.id = c.app_id ', 'LEFT' )
                ->cache(true, 60)
                ->page($page, $length)
                ->field('g.id, g.icon, g.name, g.score, g.device_ios_url, g.device_android_url, g.advertisement, g.ios_size, g.android_size, g.create_time, g.sort, count(c.app_id) as sum_comment')
                ->group('g.id')
                ->order('g.score desc')
                ->select();
            #加入判断礼包
            foreach ($data as $v) {
                $gameId = $v->data['id'];
                $comm = $this->table($comment)->where('app_id',$gameId)->count();
                $v->data['sum_comment'] = $comm;
                $giftCount = $this->table($gift)->where('app_id',$gameId)->where('mem_id',0)->count();      //统计对应游戏礼包数量
                if ($giftCount == 0) {
                    $v->data['is_gift'] = false;
                }else{
                    $v->data['is_gift'] = true;
                }
                $arr[] = $v->data;
            }
            return $arr;
            // return ['content' => $data, 'page' => $page, 'length' => count($data), 'count' => $count, 'type' => $type];
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }

        return Utils::error(2006, '数据异常');
    }

    // 游戏排行-------by下载量
    public function rankByDownload($page = 1, $length = 10, $type = self::TYPE_RANK){
        $comment = Config::get('database.prefix') . 'game_comment_list';
        $gift = Config::get('database.prefix') . 'gift';
        if (!is_numeric($length) || !is_numeric($page)) {
            return Utils::error(4016, '参数类型错误');
        }
        if (empty($page) || empty($length)) {
            return Utils::error(4017, '参数不能为空');
        }
        if ($page < 0 || $length < 0) {
            return Utils::error(4018, '参数不能为负');
        }
        if (empty($type) || $type > self::TYPE_RANK) {
            return Utils::error(4019, '类型为空');
        }
        try{
            if (defined('IS_IOS')){
                $data = $this->alias('g')
                    ->join($comment . ' c ', ' g.id = c.app_id ', 'LEFT' )
                    ->cache(true, 60)
                    ->page($page, $length)
                    ->field('g.id, g.download, g.icon, g.name, g.score, g.device_ios_url, g.device_android_url, g.advertisement, g.ios_size, g.android_size, g.create_time, g.sort,g.android_mac_id,g.ios_mac_id, g.ios_bundle_id, count(c.app_id) as sum_comment')
                    ->where('g.ios_bundle_id','<>','')
                    ->where('g.create_time','<',time())
                    ->where('is_delete',self::IS_DELETE)
                    ->where('status',self::NORMAL)
                    ->group('g.id')
                    ->order('g.download desc')
                    ->select();
            }else{
                $data = $this->alias('g')
                    ->join($comment . ' c ', ' g.id = c.app_id ', 'LEFT' )
                    ->cache(true, 60)
                    ->page($page, $length)
                    ->field('g.id, g.download, g.icon, g.name, g.score, g.device_ios_url, g.device_android_url, g.advertisement, g.ios_size, g.android_size, g.create_time, g.sort,g.android_mac_id,g.ios_mac_id, g.ios_bundle_id, count(c.app_id) as sum_comment')
                    ->where('g.create_time','<',time())
                    ->where('is_delete',self::IS_DELETE)
                    ->where('status',self::NORMAL)
                    ->group('g.id')
                    ->order('g.download desc')
                    ->select();
            }
            #加入判断礼包
            foreach ($data as $v) {
                $gameId = $v->data['id'];
                $comm = $this->table($comment)->where('app_id',$gameId)->count();
                $v->data['sum_comment'] = $comm;
                $giftCount = $this->table($gift)->where('app_id',$gameId)->where('mem_id',0)->count();      //统计对应游戏礼包数量
                if ($giftCount == 0) {
                    $v->data['is_gift'] = false;
                }else{
                    $v->data['is_gift'] = true;
                }
                $v->data['is_H5'] = $this->isHGame($gameId);
                $arr[] = $v->data;
            }
            return $arr;
            // return ['content' => $data, 'page' => $page, 'length' => count($data), 'count' => $count, 'type' => $type];
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }

    //精选      //加判断是否H5
    public function getSelectGame($page = 1, $length = 10, $type = self::TYPE_SELECT){
        if (!is_numeric($length) || !is_numeric($page)) {
            return Utils::error(4016, '参数类型错误');
        }
        if (empty($page) || empty($length)) {
            return Utils::error(4017, '参数不能为空');
        }
        if ($page < 0 || $length < 0) {
            return Utils::error(4018, '参数不能为负');
        }
        if (empty($type) || $type > self::TYPE_RANK) {
            return Utils::error(4019, '类型为空');
        }
        try{
            $tags = Config::get('database.prefix') . 'game_tags';
            $comment = Config::get('database.prefix') . 'game_comment_list';
            $gift = Config::get('database.prefix') . 'gift';
            $field = 'id,tag_name,game_id';
            $tag = $this->table($tags)->field($field)->where('tag_name',self::IS_SELECT)->find();

            $strTag = $tag->data['game_id'];

            $field = 'id,name,icon,device_ios_url,device_android_url,score,advertisement,ios_size,android_size,android_mac_id,ios_mac_id,ios_bundle_id';
            if (defined('IS_IOS')){
                $data = $this->field($field)
                    ->where('ios_bundle_id','<>','')
                    ->where('id','in',$strTag)
                    ->where('is_delete',self::IS_DELETE)
                    ->where('status',self::NORMAL)
                    ->where('create_time','<',time())
                    ->order('sort desc')
                    ->page($page)
                    ->paginate($length);
            }else{
                $data = $this->field($field)
                    ->where('id','in',$strTag)
                    ->where('is_delete',self::IS_DELETE)
                    ->where('status',self::NORMAL)
                    ->where('create_time','<',time())
                    ->order('sort desc')
                    ->page($page)
                    ->paginate($length);
            }
            $arr = array();
            foreach ($data as $v) {
                $gameId = $v->data['id'];
                $comm = $this->table($comment)->where('app_id',$gameId)->count();
                $v->data['sum_comment'] = $comm;
                $giftCount = $this->table($gift)->where('app_id',$gameId)->where('mem_id',0)->count();      //统计对应游戏礼包数量
                if ($giftCount == 0) {
                    $v->data['is_gift'] = false;
                }else{
                    $v->data['is_gift'] = true;
                }
                $v->data['is_H5'] = $this->isHGame($gameId);
                $arr[] = $v->data;
            }
            return $arr;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }

    
    // 新游预约
    public function newGameAppointment($page = 1, $length = 10){
        $mem_log = Config::get('database.prefix') . 'meet_log';

        if (!is_numeric($length) || !is_numeric($page)) {
            return Utils::error(2003, '参数类型错误');
        }
        if (empty($page) || empty($length)) {
            return Utils::error(2004, '参数不能为空');
        }
        if ($page < 0 || $length < 0) {
            return Utils::error(2005, '参数不能为负');
        }
        try{
            $count = $this->where('create_time > ' . time())->count();
            $data = $this->alias('g')
                ->join($mem_log . ' m ', ' g.id = m.app_id ', 'LEFT' )
                ->cache(true, 60)
                ->page($page, $length)
                ->field('g.id, g.icon, g.name, g.introduction, g.extend_pic, g.android_version, g.ios_version, g.create_time, count(m.app_id) as meet, g.ios_page_url, g.ios_bundle_id, g.device_ios_url, g.device_android_url')
                ->group('g.id')
                ->where('g.create_time > ' . time())
                ->order('g.create_time desc')
                ->select();
            $this->afterSelect($data);

            return ['content' => $data, 'page' => $page, 'length' => count($data), 'count' => $count];
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }

        return Utils::error(2006, '数据异常');
    }

    // 预约
    public function appointment($appId,$uid){
        $meet_log = Config::get('database.prefix') . 'meet_log';
        $meet_time = time();
        if (empty($appId) || $appId <= 0) {
            return Utils::error(2011, '游戏ID为空或游戏ID错误');
        }

        if (empty($uid) || $uid <= 0) {
            return Utils::error(2012, '用户ID为空或用户ID错误');
        }

        try{
            $log = $this->table($meet_log)->where(['app_id' => $appId, 'uid' => $uid])->find();
            if(empty($log)){
                $data = ['uid' => $uid, 'app_id' => $appId, 'create_at' => $meet_time];
                $this->table($meet_log)->insert($data);
            }

            if(!empty($log)){
                return Utils::error(2040, '该用户已预约');
            }

            return true;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }

        return Utils::error(2006, '数据异常');
    }

    //  遍历图片
    protected function afterSelect(&$resultSet = [])
    {
        if (empty($resultSet)) {
            return;
        }
        foreach ($resultSet as $key => $data) {
            if (isset($data->extend_pic)) {
                $data->extend_pic = explode(',', $data->extend_pic);
            }
        }
        return $resultSet;
    }

    //随机获取4个推荐游戏
    public function getFourGame(){
        try{
            $table = Config::get('database.prefix').'game';
            $limitNum = self::RAND_NUM;
            $field = 'id,name,ios_size,android_size,device_ios_url,device_android_url,icon,android_mac_id,ios_mac_id,android_mac_id,ios_mac_id';
            if (defined('IS_IOS')){
                $sql = "SELECT {$field} FROM $table WHERE `is_delete` = ".self::IS_DELETE." AND `status` = ".self::NORMAL." AND `create_time`<".time()." AND `ios_bundle_id` != '' ORDER BY RAND() limit ".$limitNum;
            }else{
                $sql = "SELECT {$field} FROM $table WHERE `is_delete` = ".self::IS_DELETE." AND `status` = ".self::NORMAL." AND `create_time`<".time()." ORDER BY RAND() limit ".$limitNum;
            }
            $result = $this->query($sql);

            $arr = array();
            foreach ($result as $v) {
                $cate_name = $this->getCategory( $v['id'] );
                if (sizeof($cate_name) != 0) {
                    $v['cate_name'] = $cate_name[0];    ////按二级分类排序取第一个
                }
                if (sizeof($cate_name) == 0) {
                    $v['cate_name'] = '无';    ////按二级分类排序取第一个
                }
                $arr[] = $v;
            }
            if($arr){
                return $arr;
            }else{
                return Utils::error(4130,'获取失败');
            }
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
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

    //判断是否是H5游戏
    public function isHGame($gameId){
        try{
            $category = Config::get('database.prefix') . 'game_category';
            $ids = $this->table($category)->where('cate_name','H5游戏')->find();
            $HgameIds = $ids->data['game_id'];
            $arr = explode(',',$HgameIds);
            if (in_array($gameId,$arr)) {
                return true;
            }else{
                return false;
            }
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
        return Utils::error(4141, '判断是否H5异常');
    }

    //获取所有BoundleId
    public function getAllBundleId(){
        try{
            $field = 'ios_bundle_id';
            $data = $this->field($field)->where('status',self::NORMAL)->where('create_time','<',time())->select();
            $arr = array();
            foreach ($data as $v) {
                $arr[] = $v->data['ios_bundle_id'];
            }
            return $arr;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
        return Utils::error(4146, '获取所有boundleId失败');
    }

}
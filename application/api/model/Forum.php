<?php
/**
 * 社区 论坛
 * User: Administrator
 * Date: 2018-04-26
 * Time: 15:39
 */


namespace app\api\model;

use app\common\lib\Utils;
use think\Config;
use think\Log;
use think\Model;

class Forum extends Model
{
    protected $table = 'c_app_community';
    const  IS_MATER = 0;// 查贴类型 楼主
    const  IS_ALL = 1; // 查贴类型 所有
    const  LIMIT_PIC_COUNT = 3;// 限制发图数
    const  MAX_CONTENT_LEN = 200; // 帖子长度
    const  MAX_TITLE_LEN = 30;    // 帖子标题长度
    const  MAX_COMMENT_LEN = 50;  // 评论长度
    const  REP_WORD_FLAG = '*'; // 禁止词替换符号
    const  DIV_WORD_FLAG = ','; // 分词标志符
    const  SUBS_SUCCESS = 1;    //  标识为订阅成功
    const  SUBS_CANCEL = 0;    //  标识为取消订阅
    const  IS_STRATEGY = 1;     // 为攻略
    const  IS_POST = 2;         // 为帖子
    const  IS_EFFECTIVE = 1;    // 有效
    const  IS_DELETE = 2;    // 删除
    const  IS_USE = 1;    // 是否启用状态
    protected $dateFormat = 'U'; // 时间格式 Unix


    // 获取社区首页
    public function getForum($page = 1, $length = 10)
    {
        $game = Config::get('database.prefix') . 'game';
        if (!is_numeric($length) || !is_numeric($page)) {
            return Utils::error(2003, '参数类型错误');
        }
        if (empty($page) || empty($length)) {
            return Utils::error(2004, '参数不能为空');
        }
        if ($page < 0 || $length < 0) {
            return Utils::error(2005, '参数不能为负');
        }
        try {
            $count = $this->count();
            $forum = $this
//                ->cache(true, 60)
                ->alias('c')
                ->join($game . ' g ', ' g.id = c.app_id ', ' LEFT ')
                ->field('c.id,c.app_id,c.introduction,c.image,c.community_name,c.create_at,c.sort,c.is_use,g.subs,g.icon')
                ->where('c.is_use',self::IS_USE)
                ->page($page, $length)
                ->select();
            return ['content' => $forum, 'page' => $page, 'length' => count($forum), 'count' => $count];
        } catch (\Exception $e) {
            Log::error(json_encode($e));
        }
        return Utils::error(2006, '数据异常');
    }

    //  通过游戏ID获取帖子列表
    public function getPost($appId, $page = 1, $length = 15, $uid)
    {
        $post = Config::get('database.prefix') . 'post';
        $user = Config::get('database.prefix') . 'users';
        $subs_log = Config::get('database.prefix') . 'subs_log';
        $game = Config::get('database.prefix') . 'game';
        $like = Config::get('database.prefix') . 'like_log';
        if (empty($appId) || $appId <= 0) {
            return Utils::error(2011, '游戏ID为空或游戏ID错误');
        }
        if (!is_numeric($length) || !is_numeric($page)) {
            return Utils::error(2003, '参数类型错误');
        }
        if (empty($page) || empty($length)) {
            return Utils::error(2004, '参数不能为空');
        }
        if ($page < 0 || $length < 0) {
            return Utils::error(2005, '参数不能为负');
        }
        try {
            $count = $this->table($post)->where(['app_id' => $appId, 'status' => 1])->count();
            $sum_subs = $this->table($game)->field('subs')->where(['id' => $appId])->find();
            if(!empty($sum_subs)){
                $subs = $sum_subs->subs;
            }else{
                $subs = 0;
            }
//            $subs = $sum_subs->subs;


            $data = $this->table($post)
                ->alias('p')
                ->join($user . ' u ', ' p.uid = u.id ', 'LEFT')
                ->field(' p.*, u.nickname, u.portrait ')
                ->page($page, $length)
                ->where(['app_id' => $appId, 'p.status' => 1])
                ->order('p.create_at desc')
                ->select();


            foreach ($data as $v){
                if(empty($uid)){
                    $v->data['uid_likes'] = false;
                }else{

                    $arr = explode(',',$v->data['uid_likes']);
                    if(in_array($uid,$arr)){
                        $v->data['uid_likes'] = true;
                    }else{
                        $v->data['uid_likes'] = false;
                    }
                }
                if(empty($v->data['pics'])){
                    $v->data['pics'] = [];
                }else{
                    $v->data['pics'] = explode(',',$v->data['pics']);
                }
            }
//            dump($data);
//            $this->afterSelect($data);

            $is_sub = $this->table($subs_log)->field('is_subs')->where(['app_id' => $appId, 'uid' => $uid])->find();
            // dump($is_sub['is_subs']);
            if (empty($is_sub) || $is_sub['is_subs'] == 0) {
                $is_sub = false;
            } else {
                $is_sub = true;
            }


            return ['content' => $data, 'page' => $page, 'length' => count($data), 'count' => $count, 'post_count' => $count, 'sum_subs' => $subs, 'is_sub' => $is_sub];

        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');

    }

    // 通过帖子ID进入帖子详情
    public function getPostDetails($postId, $uid)
    {
        $post = Config::get('database.prefix') . 'post';
        $user = Config::get('database.prefix') . 'users';
        $like = Config::get('database.prefix') . 'like_log';

        if (empty($postId) || $postId <= 0) {
            return Utils::error(2013, '帖子ID为空或帖子ID错误');
        }

        try {
            $this->table($post)->where(['id' => $postId])->setInc('browse');

            $details = $this->table($post)
//                ->cache(true, 60)
                ->alias('p')
                ->join($user . ' u ', ' p.uid = u.id ', 'LEFT')
                ->field(' p.id, p.title, p.content, p.create_at, p.thumbs, p.pics, u.nickname, u.portrait,p.uid_likes ')
                ->where(['p.id' => $postId])
                ->select();
            foreach ($details as $v){
                if(empty($uid)){
                    $v->data['uid_likes'] = false;
                }else{
                    $arr = explode(',',$v->data['uid_likes']);
                    if(in_array($uid,$arr)){
                        $v->data['uid_likes'] = true;
                    }else{
                        $v->data['uid_likes'] = false;
                    }

                }
                if(empty($v->data['pics'])){
                    $v->data['pics'] = [];
                }else{
                    $v->data['pics'] = explode(',',$v->data['pics']);
                }
            }

            $is_like = $this->table($like)->where(['post_id' => $postId, 'uid' => $uid])->find();
            if (empty($is_like)) {
                $is_like = false;
            } else {
                $is_like = true;
            }


            return ['content' => $details, 'is_like' => $is_like];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }

    //  通过帖子ID获取当前帖子评论
    public function getComment($postId, $type = self::IS_ALL,$uid, $page = 1, $length = 10)
    {
        $post = Config::get('database.prefix') . 'post';
        $user = Config::get('database.prefix') . 'users';
        $comment = Config::get('database.prefix') . 'gamecomment';
        if (empty($postId) || $postId <= 0) {
            return Utils::error(2013, '帖子ID为空或帖子ID错误');
        }
        if ($type < 0 || $type > 1) {
            return Utils::error(2010, '回复类型错误');
        }
        if (!is_numeric($length) || !is_numeric($page)) {
            return Utils::error(2003, '参数类型错误');
        }
        if (empty($page) || empty($length)) {
            return Utils::error(2004, '参数不能为空');
        }
        if ($page < 0 || $length < 0) {
            return Utils::error(2005, '参数不能为负');
        }
        try {
            $count = $this->table($comment)->where(['post_id' => $postId])->count('id');
            $comm = [];
            if ($type == self::IS_ALL) {
                $comm = $this->table($comment)
//                    ->cache(true, 60)
                    ->alias('c')
                    ->join($user . ' u ', ' c.uid = u.id ', 'LEFT')
                    ->join($post . ' p ', ' p.id = c.post_id ')
                    ->field('p.id, u.nickname, u.portrait, c.id as cid, c.uid, c.thumbs, c.comment, c.comment_at,c.uid_likes')
                    ->where(['c.post_id' => $postId])
                    ->order('c.comment_at desc')
                    ->page($page, $length)
                    ->select();

                foreach ($comm as $v){
                    if(empty($uid)){
                        $v->data['uid_likes'] = false;
                    }else{
                        $arr = explode(',',$v->data['uid_likes']);
                        if(in_array($uid,$arr)){
                            $v->data['uid_likes'] = true;
                        }else{
                            $v->data['uid_likes'] = false;
                        }
                    }
                }
            }

            if ($type == self::IS_MATER) {
                $data = $this->table($post)->field('uid')->where(['id' => $postId])->find();
                $comm = $this->table($comment)
//                    ->cache(true, 60)
                    ->alias('c')
                    ->join($user . ' u ', ' c.uid = u.id ', 'LEFT')
                    ->field(' u.nickname, u.portrait, c.id as cid, c.uid, c.thumbs, c.comment, c.comment_at,c.uid_likes')
                    ->where(['c.post_id' => $postId, 'c.uid' => $data->uid])
                    ->order('c.comment_at desc')
                    ->page($page, $length)
                    ->select();

                foreach ($comm as $v){
                    if(empty($uid)){
                        $v->data['uid_likes'] = false;
                    }else{
                        $arr = explode(',',$v->data['uid_likes']);
                        if(in_array($uid,$arr)){
                            $v->data['uid_likes'] = true;
                        }else{
                            $v->data['uid_likes'] = false;
                        }
                    }
                }
            }

            return ['content' => $comm, 'page' => $page, 'length' => count($comm), 'count' => $count];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }

    // 当前游戏ID论坛下发帖
    public function sendPost($appId, $uid, $title, $content, $pics)
    {
        $post = Config::get('database.prefix') . 'post';
        $create_time = time();
        try {
            if (empty($appId) || $appId <= 0) {
                return Utils::error(2011, '游戏ID为空或游戏ID错误');
            }

            if (empty($uid) || $uid <= 0) {
                return Utils::error(2012, '用户ID为空或用户参数错误');
            }

            if (is_string($pics)) {
                $pics = [$pics];
            }
            if (is_array($pics) && count($pics) <= self::LIMIT_PIC_COUNT) {
                foreach ($pics as $key => $v) {
                    if (!filter_var($v, FILTER_VALIDATE_URL)) {
                        unset($pics[$key]);
                        sort($pics);
                    }
                }
            } else {
                $pics = [];
            }
            if (Utils::hasLimitWord($title) > 0) {
                return Utils::error(4553, '请文明发帖');
            }
            if (empty($title) || strlen($title) > self::MAX_TITLE_LEN) {
                return Utils::error(2019, '帖子标题长度不符合要求');
            }
            if (empty($content) || strlen($content) > self::MAX_CONTENT_LEN) {
                return Utils::error(2020, '帖子内容长度不符合要求');
            }

            $content = Utils::replaceLimitWord($content);
            $data = ['app_id' => $appId, 'uid' => $uid, 'title' => $title, 'content' => $content, 'create_at' => $create_time, 'pics' => implode(',', $pics), 'update_at' => time()];
            $this->table($post)->insert($data);
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');

    }

    // 当前帖子下评论
    public function sendComment($app_id, $uid, $post_id, $comment)
    {
        $comm = Config::get('database.prefix') . 'gamecomment';
        $comment_time = time();
        if (empty($app_id) || $app_id <= 0) {
            return Utils::error(2011, '游戏ID为空或游戏ID错误');
        }

        if (empty($uid) || $uid <= 0) {
            return Utils::error(2012, '用户ID为空或用户ID错误');
        }

        if (empty($post_id) || $post_id <= 0) {
            return Utils::error(2013, '帖子ID为空或帖子ID错误');
        }


        try {
//            if( Utils::hasLimitWord($comment) > 0){
//                return Utils::error(4554,'请文明评论');
//            }

            if (empty($comment) || strlen($comment) > self::MAX_COMMENT_LEN) {
                return Utils::error(2025, '评论长度不符合要求');
            }
            $comment = Utils::replaceLimitWord($comment);

            $data = ['app_id' => $app_id, 'uid' => $uid, 'post_id' => $post_id, 'comment' => $comment, 'comment_at' => $comment_time];
            $this->table($comm)->insert($data);
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }

    // 点赞
    public function like($postId, $commentId, $uid)
    {
        $post = Config::get('database.prefix') . 'post';
        $comment = Config::get('database.prefix') . 'gamecomment';
        $like_log = Config::get('database.prefix') . 'like_log';
        $like_time = time();
        try {

            if (empty($postId) && empty($commentId)) {
                return Utils::error(2026, '帖子ID和评论ID不能同时为空');
            }
            if ($postId <= 0 && $commentId <= 0) {
                return Utils::error(2027, '帖子ID和评论ID错误');
            }

            if ($postId < 0 || $commentId < 0) {
                return Utils::error(2032, '帖子ID和评论ID参数错误');
            }

            if (empty($uid) || $uid <= 0) {
                return Utils::error(2012, '用户ID为空或用户ID错误');
            }

            if (!empty($postId) && !empty($commentId)) {
                return Utils::error(2028, '参数异常');
            }

            if (empty($postId) && !empty($commentId)) {
                $uid_like = $this->table($like_log)->where(['uid' => $uid, 'comment_id' => $commentId])->find();
            } elseif (!empty($postId) && empty($commentId)) {
                $uid_like = $this->table($like_log)->where(['uid' => $uid, 'post_id' => $postId])->find();
            }


            if (!empty($uid_like)) {
                return Utils::error(2034, '该用户已点赞');
            }

            // 取消点赞
//            if ($uid_like['is_like'] == 1) {
//
//                if (!empty($uid_like['post_id']) && empty($uid_like['comment_id'])) {
//                    $this->table($like_log)->where(['uid' => $uid, 'post_id' => $uid_like['post_id']])->update(['is_like' => 0]);
//
//                    $this->table($post)->field('id')->where(['id' => $uid_like['post_id']])->find();
//                    $this->table($post)->where(['id' => $uid_like['post_id']])->setDec('thumbs');
//
//                } elseif (empty($uid_like['post_id']) && !empty($uid_like['comment_id'])) {
//                    $this->table($like_log)->where(['uid' => $uid, 'comment_id' => $uid_like['comment_id']])->update(['is_like' => 0]);
//
//                    $this->table($comment)->field('id')->where(['id' => $uid_like['comment_id']])->find();
//                    $this->table($comment)->where(['id' => $uid_like['comment_id']])->setDec('thumbs');
//                }
//                return ['msg' => '取消点赞成功', 'code' => 200];
//            }

            // 点赞后信息入库
            if (empty($uid_like)) {

                if (empty($postId) && !empty($commentId)) {
                    $data = ['post_id' => $postId, 'comment_id' => $commentId, 'uid' => $uid, 'is_like' => 1, 'like_at' => $like_time];
                    $this->table($like_log)->insert($data);


                    $is_like = $this->table($comment)->field('uid_likes')->where('id',$commentId)->find();
                    $like_id = $is_like->uid_likes;


                    if(empty($like_id)){
                        $like_id = $uid;
                    }else{
                        $like_id = $like_id.','.$uid;
                    }
                    $this->table($comment)->where('id',$commentId)->update(['uid_likes'=>$like_id]);

                    $this->table($comment)->field('id')->where(['id' => $commentId])->find();
                    $this->table($comment)->where(['id' => $commentId])->setInc('thumbs');

                } elseif (!empty($postId) && empty($commentId)) {
                    $data = ['post_id' => $postId, 'comment_id' => $commentId, 'uid' => $uid, 'is_like' => 1, 'like_at' => $like_time];
                    $this->table($like_log)->insert($data);

                    $is_likes = $this->table($post)->field('uid_likes')->where('id',$postId)->find();
                    $like_uid = $is_likes->uid_likes;

                    if(empty($like_uid)){
                        $like_uid = $uid;
                    }else{
                        $like_uid = $like_uid.','.$uid;
                    }
                    $this->table($post)->where('id',$postId)->update(['uid_likes'=>$like_uid]);


                    $this->table($post)->field('id')->where(['id' => $postId])->find();
                    $this->table($post)->where(['id' => $postId])->setInc('thumbs');
                }
                return true;
            }

            // 信息存在，修改字段，点赞成功
//            if ($uid_like['is_like'] == 0) {
//
//                if (empty($uid_like['post_id']) && !empty($uid_like['comment_id'])) {
//                    $this->table($like_log)->where(['uid' => $uid, 'comment_id' => $uid_like['comment_id']])->update(['is_like' => 1, 'like_at' => $like_time]);
//
//                    $this->table($comment)->field('id')->where(['id' => $commentId])->find();
//                    $this->table($comment)->where(['id' => $commentId])->setInc('thumbs');
//
//                } elseif (!empty($uid_like['post_id']) && empty($uid_like['comment_id'])) {
//                    $this->table($like_log)->where(['uid' => $uid, 'post_id' => $uid_like['post_id']])->update(['is_like' => 1, 'like_at' => $like_time]);
//
//                    $this->table($post)->field('id')->where(['id' => $postId])->find();
//                    $this->table($post)->where(['id' => $postId])->setInc('thumbs');
//                }
//                return ['msg' => '点赞成功', 'code' => 200];
//            }

        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');

    }

    // 订阅游戏
    public function subs($app_id, $uid)
    {
        $subs = Config::get('database.prefix') . 'subs_log';
        $game = Config::get('database.prefix') . 'game';
        $subs_time = time();
        if (empty($app_id) || $app_id <= 0) {
            return Utils::error(2011, '游戏ID为空或游戏ID错误');
        }

        if (empty($uid) || $uid <= 0) {
            return Utils::error(2012, '用户ID为空或用户ID错误');
        }

        try {
            $appId = $this->table($game)->where(['id' => $app_id])->find();
            $appID = $appId->id;
            $subs_log = $this->table($subs)->where(['app_id' => $appID, 'uid' => $uid])->find();
            // 若该用户从未订阅当前社区,则新添记录
            if (empty($subs_log)) {
                $data = ['app_id' => $appID, 'uid' => $uid, 'is_subs' => 1, 'subs_at' => $subs_time];
                $this->table($subs)->insert($data);

                $this->table($game)->field('id')->where(['id' => $appID])->find();
                $this->table($game)->where(['id' => $appID])->setInc('subs');

                return self::SUBS_SUCCESS;

            }

            // 若有该用户订阅记录，且订阅记录为已订阅状态，则更改为未订阅状态

            if ($subs_log['is_subs'] == 1) {
                $this->table($subs)->where(['app_id' => $appID, 'uid' => $uid])->update(['is_subs' => 0]);

                $this->table($game)->field('id')->where(['id' => $appID])->find();
                $this->table($game)->where(['id' => $appID])->setDec('subs');

                return self::SUBS_CANCEL;
            }

            if ($subs_log['is_subs'] == 0) {
                $this->table($subs)->where(['app_id' => $appID, 'uid' => $uid])->update(['is_subs' => 1, 'subs_at' => $subs_time]);

                $this->table($game)->field('id')->where(['id' => $appID])->find();
                $this->table($game)->where(['id' => $appID])->setInc('subs');

                return self::SUBS_SUCCESS;
            }


        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');

    }

    // 收藏帖子
    public function collectionPost($uid, $post_id, $type = self::IS_POST)
    {

        if (empty($uid) || $uid <= 0) {
            return Utils::error(2012, '用户ID为空或用户ID错误');
        }
        if (empty($post_id) || $post_id <= 0) {
            return Utils::error(2013, '帖子ID为空或帖子ID错误');
        }

        try {
            $collection = Config::get('database.prefix') . 'collection';
            $create_time = time();
            $collections = $this->table($collection)->where(['uid' => $uid, 'cid' => $post_id])->select();

            if (!empty($collections)) {
                return Utils::error(2800, '已收藏该帖子');
            }

            $data = ['uid' => $uid, 'type' => $type, 'cid' => $post_id, 'create_time' => $create_time, 'status' => self::IS_EFFECTIVE];
            $this->table($collection)->insert($data);

            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }

    // 收藏攻略
    public function collectStrategy($uid, $str_id, $type = self::IS_STRATEGY)
    {

        if (empty($uid) || $uid <= 0) {
            return Utils::error(2012, '用户ID为空或用户ID错误');
        }
        if (empty($str_id) || $str_id <= 0) {
            return Utils::error(2900, '攻略ID为空或攻略ID错误');
        }

        try {
            $collection = Config::get('database.prefix') . 'collection';
            $create_time = time();
            $collections = $this->table($collection)->where(['uid' => $uid, 'cid' => $str_id])->select();

            if (!empty($collections)) {
                return Utils::error(2880, '已收藏该攻略');
            }

            $data = ['uid' => $uid, 'type' => $type, 'cid' => $str_id, 'create_time' => $create_time, 'status' => self::IS_EFFECTIVE];
            $this->table($collection)->insert($data);

            return true;
        } catch (\Exception $e) {
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
            if (isset($data->pics)) {
                $data->pics = explode(',', $data->pics);
            }
        }
        return $resultSet;
    }

    //  数组中的一张图片
    protected function afterFind(&$result = null)
    {
        if (empty($result)) {
            return;
        }
        if (isset($result->pics)) {
            $result->pics = explode(',', $result->pics);
        }

        return $result;
    }

    // 遍历敏感词汇
    protected function afterSelectWord(&$resultWord = [])
    {
        if (empty($resultWord)) {
            return;
        }
        foreach ($resultWord as $key => $word_data) {
            if (isset($word_data->word_list)) {
                $word_data->word_list = explode(',', $word_data->word_list);
            }
        }
        return $resultWord;
    }

    //搜索社区
    public function getSearchForum($key){
        try{
            $field = 'c.id,c.app_id,c.introduction,c.image,c.community_name,c.create_at,c.is_use,g.subs,g.icon';
            $game = Config::get('database.prefix') . 'game';
            if (defined('IS_IOS')){
                $total = $this->field($field)->alias('c')
                    ->join($game . ' g ', ' g.id = c.app_id ', 'LEFT' )
                    ->where('g.ios_bundle_id','<>','')
                    ->where('c.community_name','like',$key.'%')
                    ->where('c.is_use',self::IS_USE)
                    ->order('c.subs','desc')
                    ->select();
            }else{
                $field = 'c.id,c.app_id,c.introduction,c.image,c.community_name,c.create_at,c.is_use,g.subs,g.icon';
                $total = $this->field($field)->alias('c')
                    ->join($game . ' g ', ' g.id = c.app_id ', 'LEFT' )
                    ->where('c.community_name','like',$key.'%')
                    ->where('c.is_use',self::IS_USE)
                    ->order('c.subs','desc')
                    ->select();
            }
            return $total;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return Utils::error(2006, '数据异常');
    }
}
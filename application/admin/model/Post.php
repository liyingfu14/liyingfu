<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;
use think\Session;
use app\common\lib\Fun;

class Post extends Model
{
    const POST_STAY = 0;    // 帖子未通过审核
    const POST_PASS = 1;    // 帖子通过审核
    const POST_BAN = 2;    // 帖子禁止下架
    const POST_DEL = 3;    // 帖子逻辑删除
    const STRATEGY_POST = 1;    // 攻略
    const NEWS_POST = 2;    // 新闻
    const ORDER_POST = 3;    // 其他
    const NOT_STRATEGY = 2; // 判断发帖子还是攻略
    const NO_TOP = 0; // 不置顶
    const IS_TOP = 1; // 置顶
    const GET_NUM = 10;

    const UPD_POST = 24;
    const DOWN_POST = 25;
    const UP_POST = 26;
    const DEL_POST = 27;
    const DEL_COMMENT = 30;
    const ADD_POST = 31;
    protected $table = 'c_app_post';

    //帖子列表
    public function postList($name, $title, $start_time, $end_time, $page)
    {
        try {
            $n = self::GET_NUM;
            $m = ($page-1)*$n;

            $game = Config::get('database.prefix') . 'game';
            $user = Config::get('database.prefix') . 'users';
            $comment = Config::get('database.prefix') . 'gamecomment';

            $post = $this->alias('p')
                ->join($game . ' g ', ' p.app_id = g.id ', ' LEFT ')
                ->join($user . ' u ', ' p.uid = u.id ', ' LEFT ')
                ->field('p.id,p.app_id,p.uid,p.title,p.create_at,p.status,p.browse,p.sort,g.name,u.nickname')
                ->where('g.name', 'like', '%' . $name . '%')
                ->where('p.title', 'like', '%' . $title . '%')
                ->whereBetween('p.create_at', [$start_time, $end_time])
                ->order('p.create_at desc')
                // ->paginate(5, false, ['query' => request()->param()]);
                ->limit($m,$n)->select();
            $countSql = $this->alias('p')
                ->join($game . ' g ', ' p.app_id = g.id ', ' LEFT ')
                ->join($user . ' u ', ' p.uid = u.id ', ' LEFT ')
                ->field('p.id,p.app_id,p.uid,p.title,p.create_at,p.status,p.browse,p.sort,g.name,u.nickname')
                ->where('g.name', 'like', '%' . $name . '%')
                ->where('p.title', 'like', '%' . $title . '%')
                ->whereBetween('p.create_at', [$start_time, $end_time])
                ->count();
            
            Session::set('page_sum',$countSql);

            foreach ($post as $v) {
                $postId = $v->data['id'];
                $commentCount = $this->table($comment)->where('post_id', $postId)->count();      //统计对应帖子评论数量
                $v->data['sum_comment'] = $commentCount;

                if ($v->data['status'] == self::POST_STAY) {
                    $v->data['sta'] = '待发布';
                } elseif ($v->data['status'] == self::POST_PASS) {
                    $v->data['sta'] = '已上线';
                } elseif ($v->data['status'] == self::POST_BAN) {
                    $v->data['sta'] = '禁止下架';
                } else {
                    $v->data['sta'] = '其他';
                }
                $arr[] = $v->data;
            }
            return $post;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    //帖子评论列表
    public function commentPost($gameName, $postId, $comment, $page)
    {
        try {
            $n = self::GET_NUM;
            $m = ($page-1)*$n;

            $comm = Config::get('database.prefix') . 'gamecomment';
            $game = Config::get('database.prefix') . 'game';
            $user = Config::get('database.prefix') . 'users';

            $commentResult = $this
                ->table($comm)
                ->alias('c')
                ->join($game . ' g ', ' c.app_id = g.id ', ' LEFT ')
                ->join($user . ' u ', ' c.uid = u.id ', ' LEFT ')
                ->field('c.id,c.uid,c.app_id,c.post_id,c.comment,g.name,u.nickname')
                ->where('g.name', 'like', '%' . $gameName . '%')
                ->where('c.post_id', 'like', '%' . $postId . '%')
                ->where('c.comment', 'like', '%' . $comment . '%')
                ->order('c.id')
                // ->paginate(5, false, ['query' => request()->param()]);
                ->limit($m,$n)->select();

            $countSql = $this
                ->table($comm)
                ->alias('c')
                ->join($game . ' g ', ' c.app_id = g.id ', ' LEFT ')
                ->join($user . ' u ', ' c.uid = u.id ', ' LEFT ')
                ->field('c.id,c.uid,c.app_id,c.post_id,c.comment,g.name,u.nickname')
                ->where('g.name', 'like', '%' . $gameName . '%')
                ->where('c.post_id', 'like', '%' . $postId . '%')
                ->where('c.comment', 'like', '%' . $comment . '%')
                ->order('c.id')
                ->count();
            Session::set('page_sum',$countSql);

            return $commentResult;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 添加帖子
    public function addPost($title, $content, $type = self::NOT_STRATEGY, $tags, $game, $username, $label = self::NO_TOP, $url, $news, $pics)
    {
        if (empty($title)) {
            return Utils::error(2102, '标题不能为空');
        }
        if ($type == self::STRATEGY_POST) {
            if (empty($tags)) {
                return Utils::error(2110, '请选择标签');
            }
        }
        if (empty($game)) {
            return Utils::error(2111, '请选择游戏');
        }

        if ($type == self::NOT_STRATEGY) {
            if (empty($username)) {
                return Utils::error(2112, '帐号不能为空');
            }
        }

        try {

            $strategy = Config::get('database.prefix') . 'gamenews';
            $post = Config::get('database.prefix') . 'post';
            $user = Config::get('database.prefix') . 'users';
            $create_time = time();

            if ($type == self::STRATEGY_POST) {
                $data = ['app_id' => $game, 'type' => $news, 'author' => '官方', 'post' => $title, 'post_type' => $tags, 'image' => $pics, 'url' => $url, 'time' => $create_time];

                $postId = $this->table($strategy)->insertGetId($data);
                Fun::logWriter(self::ADD_POST,$postId);
            }
            if ($type == self::NOT_STRATEGY) {
                $id = $this->table($user)->field('id')->where(['username' => $username])->find();
                if(!$id){
                    return Utils::error(4540, '帐号不能为空');
                }
                $uid = $id->id;
                $data = ['app_id' => $game, 'uid' => $uid, 'tag' => $label, 'title' => $title, 'content' => $content, 'create_at' => $create_time, 'status' => 0, 'update_at' => $create_time, 'pics' => $pics];
                $postId = $this->table($post)->insertGetId($data);
                Fun::logWriter(self::ADD_POST,$postId);
            }
            return true;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 删除帖子
    public function delPost($id)
    {
        try {
            $this->where('id', $id)->delete();
            Fun::logWriter(self::DEL_POST,$id);
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 删除评论
    public function delComment($id)
    {
        try {
            $comment = Config::get('database.prefix') . 'gamecomment';
            $this->table($comment)->where('id', $id)->delete();
            Fun::logWriter(self::DEL_COMMENT,$id);
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑帖子
    public function edit($id)
    {
        try {
            $user = Config::get('database.prefix') . 'users';
            $data = $this->alias('p')
                ->join($user . ' u ', ' u.id=p.uid ', ' LEFT ')
                ->field('p.*,u.username')
                ->where('p.id', $id)
                ->find();

            return $data;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑帖子--修改数据
    public function editPost($id, $title, $label, $username, $game, $content, $pic)
    {
        try {
            $user = Config::get('database.prefix') . 'users';
            $ids = $this->table($user)->where('username', $username)->find();
            $uid = $ids->id;

            if (empty($pic)) {
                $data = ['app_id' => $game, 'uid' => $uid, 'tag' => $label, 'title' => $title, 'content' => $content, 'update_at' => time()];
            } else {
                $data = ['app_id' => $game, 'uid' => $uid, 'tag' => $label, 'title' => $title, 'content' => $content, 'pics' => $pic, 'update_at' => time()];
            }

            $this->where('id', $id)->update($data);
            Fun::logWriter(self::UPD_POST,$id);
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 是否发布帖子
    public function sendPost($id)
    {
        try {
            $status = $this->where('id', $id)->field('status')->find();
            $type = $status->status;
            if ($type == 0) {
                $this->where('id', $id)->update(['status' => 1]);
                Fun::logWriter(self::UP_POST,$id);
            } elseif ($type == 1) {
                $this->where('id', $id)->update(['status' => 0]);
                Fun::logWriter(self::DOWN_POST,$id);
            }
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

}
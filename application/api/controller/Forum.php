<?php
/**
 * 社区 论坛模块
 * User: Administrator
 * Date: 2018-04-26
 * Time: 15:37
 */

namespace app\api\controller;

use app\common\controller\BaseApi;
use app\api\model\Forum as ForumModel;
use app\common\lib\Utils;
use think\Request;

class Forum extends BaseApi
{
    // 获取论坛首页
    public function index()
    {
        $model = new ForumModel();
        $data = $model->getForum($this->request->param('page', 1), $this->request->param('length', 10));
        return $this->response(Utils::success($data));
    }

    //搜索论坛
    public function searchForum(){
        $model = new ForumModel();
        $data = $model->getSearchForum($this->request->param('key',''));
        if (Utils::isError($data)) {
            return $this->response($data);
        }
        return $this->response(Utils::success($data));
    }

    // 通过游戏ID获取帖子列表
    public function post()
    {
        $uid = null;

        if ($this->x_token && $this->is_alive_token) {
            $uid = $this->uid;
        }
        $postmodel = new ForumModel();
        $appid = $this->request->param('app_id');   // 获取游戏ID
        $page = $this->request->param('page', 1);   // 获取页码
        $length = $this->request->param('length', 15);   // 获取页长
        $data = $postmodel->getPost(intval($appid), $page, $length, $uid);
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response(Utils::success($data));
    }

    // 通过帖子ID获取帖子详细内容
    public function postDetails()
    {
        $uid = null;

        if ($this->x_token && $this->is_alive_token) {
            $uid = $this->uid;
        }
        $details = new ForumModel();
        $postid = $this->request->param('id');  // 获取帖子ID
        $data = $details->getPostDetails(intval($postid), $uid);
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response(Utils::success($data));

    }

    // 通过帖子ID获取当前帖子评论
    public function postComment()
    {
        $uid = null;

        if ($this->x_token && $this->is_alive_token) {
            $uid = $this->uid;
        }
        $comment = new ForumModel();
        $postid = $this->request->param('post_id');  //获取帖子ID
        $type = $this->request->param('type');  //  获取回帖类型
        $data = $comment->getComment(intval($postid), $type,$uid);
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response(Utils::success($data));
    }

    // 获取当前游戏ID，在当前论坛下发帖
    public function sendPost($app_id, $title, $content, $pics)
    {
        $uid = null;

        if ($this->x_token && $this->is_alive_token) {
            $uid = $this->uid;
        } else {
            return $this->response(4444, '未登录状态无法访问');
        }
        $sendPost = new ForumModel();

        $data = $sendPost->sendPost(intval($app_id), intval($uid), $title, $content, $pics);
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response([], '发帖成功');
    }

    // 获取当前帖子，在当前帖子下评论
    public function sendComment($app_id, $post_id, $comment)
    {
        $uid = null;

        if ($this->x_token && $this->is_alive_token) {
            $uid = $this->uid;
        } else {
            return $this->response(4444, '未登录状态无法访问');
        }
        $sendComment = new ForumModel();

        $data = $sendComment->sendComment(intval($app_id), intval($uid), intval($post_id), $comment);
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response([], '评论成功');
    }

    // 帖子、评论点赞
    public function like($post_id, $comment_id)
    {
        $uid = null;

        if ($this->x_token && $this->is_alive_token) {
            $uid = $this->uid;
        } else {
            return $this->response(4444, '未登录状态无法访问');
        }
        $like = new ForumModel();

        $data = $like->like(intval($post_id), intval($comment_id), intval($uid));
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response([], '点赞成功');
    }

    // 订阅
    public function subs($app_id)
    {
        $uid = null;

        if ($this->x_token && $this->is_alive_token) {
            $uid = $this->uid;
        } else {
            return $this->response(4444, '未登录状态无法访问');
        }
        $subs = new ForumModel();

        $data = $subs->subs(intval($app_id), intval($uid));
        if (Utils::isError($data)) {
            return $this->response($data);
        }
        if (ForumModel::SUBS_SUCCESS === $data) {
            return $this->response(['ok' => 1], '订阅成功');
        }
        if (ForumModel::SUBS_CANCEL === $data) {
            return $this->response(['ok' => 0], '取消订阅成功');
        }
        return $this->response(2006, '数据异常');
    }

    // 收藏帖子
    public function collectionPost(Request $request)
    {
        $uid = null;

        if ($this->x_token && $this->is_alive_token) {
            $uid = $this->uid;
        } else {
            return $this->response(4444, '未登录状态无法访问');
        }
        $model = new ForumModel();
        $post_id = $request->param('post_id');
        $data = $model->collectionPost($uid, $post_id);
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response([], '收藏成功');
    }

    // 收藏攻略
    public function strategy(Request $request)
    {
        $uid = null;

        if ($this->x_token && $this->is_alive_token) {
            $uid = $this->uid;
        } else {
            return $this->response(4444, '未登录状态无法访问');
        }
        $model = new ForumModel();
        $str_id = $request->param('str_id');
        $data = $model->collectStrategy($uid, $str_id);
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response([], '收藏成功');
    }

}
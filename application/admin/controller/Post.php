<?php

namespace app\admin\controller;

use app\admin\model\BanWord;
use app\admin\model\Strategy;
use think\Request;
use app\admin\model\Post as Posts;
use app\admin\model\PostGames;
use app\admin\model\StrategyType;
use app\admin\model\Permission;
use think\Config;
use app\common\lib\Fun;
use think\Session;

class Post extends Wall
{
    const NUM = 10;
    protected $postModel;
    protected $postGameModel;
    protected $strategyTypeModel;
    protected $banWordModel;
    protected $strategyModel;
    protected $permissionModel;

    public function __construct()
    {
        parent::__construct();
        $this->postModel = new Posts();
        $this->postGameModel = new PostGames();
        $this->strategyTypeModel = new StrategyType();
        $this->banWordModel = new BanWord();
        $this->strategyModel = new Strategy();
        $this->permissionModel = new Permission();
    }

    public function post(Request $request)
    {
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }
        // 获取帖子列表
        $name = $request->param('name');
        $this->view->assign('name', $name);

        $title = $request->param('title');
        $this->view->assign('title', $title);

        $start_time = $request->param('start_time', date("Y-m-d", 1514736000));
        $this->view->assign('start_time', $start_time);

        $end_time = $request->param('end_time', date("Y-m-d", 1546185600));
        $this->view->assign('end_time', $end_time);


        $lists = $this->postModel->postList($name, $title, strtotime($start_time), strtotime($end_time), $page);
        $this->assign('lists', $lists);
        // $page = $lists->render();
        // $this->assign('page', $page);

        $powers = $this->permissionModel->getPostListPower();
        $this->assign('powers', $powers);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('post/post');

    }

    // 删除帖子
    public function delPost(Request $request)
    {
        $id = $request->param('id');
        $del = $this->postModel->delPost($id);
        if ($del) {
            $this->success('删除成功', 'admin/post/post');
        } else {
            $this->error('删除失败');
        }

    }

    // 删除评论
    public function delComment(Request $request)
    {
        $id = $request->param('id');
        $del = $this->postModel->delComment($id);
        if ($del) {
            $this->success('删除成功', 'admin/post/comment');
        } else {
            $this->error('删除失败');
        }

    }

    // 评论列表
    public function comment(Request $request)
    {
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }
        // 获取评论列表

        $gameName = $request->param('gameName');   // 获取游戏名称
        $this->view->assign('gameName', $gameName);

        $postId = $request->param('postId');   // 获取帖子ID
        $this->view->assign('postId', $postId);

        $comment = $request->param('comment');   // 获取评论内容
        $this->view->assign('comment', $comment);

        $comments = $this->postModel->commentPost($gameName, $postId, $comment, $page);
        $this->assign('comments', $comments);
        // $page = $comments->render();
        // $this->assign('page', $page);

        $powers = $this->permissionModel->getPostListPower();
        $this->assign('powers', $powers);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('post/comment');
    }

    //新建帖子显示
    public function add()
    {
        $token = Fun::getUploadToken();
        if ($token === false){
            return $this->error('验证过期','admin/login/index');
        }
        $this->assign('xapp', $token['xapp']);
        $this->assign('uploadToken', $token['uploadToken']);
        $this->assign('token', $token['token']);

        //帖子状态
        $postStatus = [
            ['id' => 1, 'value' => '置顶'],
            ['id' => 2, 'value' => '精选'],
            ['id' => 3, 'value' => '热门'],
            ['id' => 4, 'value' => '最新'],
        ];
        $this->assign('postStatus', $postStatus);

        // 是否添加到攻略
        $strategy = [
            ['id' => 2, 'value' => '添加到帖子'],
            ['id' => 1, 'value' => '添加到攻略'],
        ];
        $this->assign('strategy', $strategy);

        // 类型
        $newsType = [
            ['id' => 1, 'value' => '攻略'],
            ['id' => 2, 'value' => '新闻'],
            ['id' => 3, 'value' => '其他'],
        ];
        $this->assign('newsType', $newsType);

        // 游戏下拉
        $game = $this->postGameModel->GameList();
        $this->assign('game', $game);

        // 攻略关键字下拉
        $type = $this->strategyTypeModel->Type();
        $this->assign('type', $type);
        return $this->fetch('post/add');
    }

    // 发布帖子
    public function addPost(Request $request)
    {
        // 发布帖子
        $title = $request->param('title');   // 获取帖子标题
        $content = $request->param('content');  // 获取帖子内容
        $type = $request->param('type');    //  是否添加到攻略
        $tags = $request->param('tags');        // 关键词
        $game = $request->param('game');    // 游戏
        $username = $request->param('username'); // 用户帐号
        $url = $request->param('strategy'); // 攻略链接
        $news = $request->param('news'); // 类型,帖子还是新闻
        $pics = $request->param('pics'); // 图片
        // $label = $request->param()['label'];
        $label = $request->param('label/a');        //0607q 修改

        $content = str_replace('<p>','',$content);      //0607q 修改
        $content = str_replace('</p>','',$content);     //0607q 修改

        if (empty($title)) {
            $this->error('请填写帖子标题');
        }
        if (empty($type)) {
            $this->error('请选择发布到帖子或者发布到攻略');
        }
        if ($type == 2) {
            if (empty($label)) {
                $this->error('请选择是否置顶');
            }
        }

        if ($type == 1) {
            if (empty($tags)) {
                $this->error('请选择关键字');
            }
        }
        if ($type == 2) {
            if (empty($username)) {
                $this->error('请填写帐号');
            }
        }
        if (empty($game) || $game <= 0) {
            $this->error('请选择游戏');
        }
        if (!empty($label)) {
            $label = implode(',',$label);
        }

        $add = $this->postModel->addPost($title, $content, $type, $tags, $game, $username, $label, $url, $news, $pics);

        if ($add === true) {
            $this->success('发布成功', 'admin/post/post');
        } else {
            $this->error('发布失败,'.$add['msg']);
        }
    }

    // 词库列表
    public function wordList()
    {

        $lists = $this->banWordModel->Type();
        $this->assign('lists', $lists);

        $powers = $this->permissionModel->getWordListPower();
        $this->assign('powers', $powers);

        return $this->fetch('post/wordList');
    }

    // 添加禁忌词汇类型页面
    public function addBan()
    {
        return $this->fetch('post/addBan');
    }

    // 添加禁忌词汇类型
    public function addWord(Request $request)
    {
        $word = $request->param('word');   // 获取违禁词
        $rang = $request->param('rang');   // 获取违禁词
        if (empty($rang)) {
            $this->error('请填写违禁词类型');
        }
        $add = $this->banWordModel->addBan($word, $rang);
        if ($add) {
            $this->success('添加成功', 'admin/post/wordList');
        } else {
            $this->error('添加失败');
        }
    }

    // 删除禁忌词
    public function delWord(Request $request)
    {
        $id = $request->param('id');
        $del = $this->banWordModel->del($id);
        if ($del) {
            $this->success('删除成功', 'admin/post/wordList');
        } else {
            $this->error('删除失败');
        }
    }

    // 编辑禁忌词
    public function editBan(Request $request)
    {
        $id = $request->param('id');
        $lists = $this->banWordModel->Edit($id);
        $this->assign('lists', $lists);
        return $this->fetch('post/editBan');
    }

    // 编辑
    public function edit(Request $request)
    {
        $word_list = $request->param('word');
        $range = $request->param('range');
        $edit = $this->banWordModel->editWord($word_list, $range);
        if ($edit) {
            $this->success('修改成功', 'admin/post/wordList');
        } else {
            $this->error('修改失败');
        }
    }

    // 官方攻略列表
    public function strategy(Request $request)
    {
        $page = $request->param('p');
        if (empty($page)) {
            $page = 1;
        }

        $name = $request->param('name');
        $this->view->assign('name', $name);

        $title = $request->param('title');
        $this->view->assign('title', $title);

        $start_time = $request->param('start_time', date("Y-m-d", 1514736000));
        $this->view->assign('start_time', $start_time);

        $end_time = $request->param('end_time', date("Y-m-d", 1546185600));
        $this->view->assign('end_time', $end_time);
        $lists = $this->strategyModel->strategy($name, $title, strtotime($start_time), strtotime($end_time), $page);
        $this->assign('lists', $lists);
        // $page = $lists->render();
        // $this->assign('page', $page);

        $powers = $this->permissionModel->getPostListPower();
        $this->assign('powers', $powers);

        $url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $pageSize = self::NUM;
        $total = Session::get('page_sum');
        $page_list = Fun::page($url,$pageSize,$total,$page);
        $this->assign('page_list',$page_list);

        return $this->fetch('post/strategy');
    }

    // 删除攻略
    public function delStrategy(Request $request)
    {
        $id = $request->param('id');
        $del = $this->strategyModel->del($id);
        if ($del) {
            $this->success('删除成功', 'admin/post/strategy');
        } else {
            $this->error('删除失败');
        }
    }

    // 编辑攻略
    public function editStrategy(Request $request)
    {
        $token = Fun::getUploadToken();
        if ($token === false){
            return $this->error('验证过期','admin/login/index');
        }
        $this->assign('xapp', $token['xapp']);
        $this->assign('uploadToken', $token['uploadToken']);
        $this->assign('token', $token['token']);

        // 游戏下拉
        $game = $this->postGameModel->GameList();
        $this->assign('game', $game);

        // 攻略类型下拉
        $type = $this->strategyTypeModel->Type();
        $this->assign('type', $type);

        // 类型
        $newsType = [
            ['id' => 1, 'value' => '攻略'],
            ['id' => 2, 'value' => '新闻'],
            ['id' => 3, 'value' => '其他'],
        ];
        $this->assign('newsType', $newsType);

        $id = $request->param('id');
        $lists = $this->strategyModel->edit($id);
        $this->assign('lists', $lists);

        return $this->fetch('post/editStrategy');
    }

    // 编辑攻略--修改数据
    public function editStraData(Request $request)
    {
        $id = $request->param('id');
        $title = $request->param('title');
        $tags = $request->param('tags');
        $game = $request->param('game');
        $content = $request->param('content');
        $url = $request->param('strategy');
        $news = $request->param('news');
        $pic = $request->param('pics');

        if (empty($title)) {
            $this->error('攻略标题不能为空');
        }
        if (empty($tags)) {
            $this->error('关键词不能为空');
        }
        if (empty($game)) {
            $this->error('游戏不能为空');
        }

        $edit = $this->strategyModel->editStra($id, $title, $tags, $game, $content, $url, $news, $pic);
        if ($edit) {
            $this->success('修改成功', 'admin/post/strategy');
        } else {
            $this->error('修改失败');
        }
    }

    // 编辑帖子
    public function editPost(Request $request)
    {
        $token = Fun::getUploadToken();
        if ($token === false){
            return $this->error('验证过期','admin/login/index');
        }
        $this->assign('xapp', $token['xapp']);
        $this->assign('uploadToken', $token['uploadToken']);
        $this->assign('token', $token['token']);

        //帖子状态
        $postStatus = [
            ['id' => 2, 'value' => '不置顶'],
            ['id' => 1, 'value' => '置顶'],
        ];
        $this->assign('postStatus', $postStatus);

        // 游戏下拉
        $game = $this->postGameModel->GameList();
        $this->assign('game', $game);

        // 攻略类型下拉
        $type = $this->strategyTypeModel->Type();
        $this->assign('type', $type);

        $id = $request->param('id');
        $lists = $this->postModel->edit($id);
        $this->assign('lists', $lists);
        return $this->fetch('post/editPost');
    }

    // 编辑帖子---修改数据
    public function editPostData(Request $request)
    {
        $id = $request->param('id');
        $title = $request->param('title');
        $username = $request->param('username');
        $game = $request->param('game');
        $content = $request->param('content');
        $pic = $request->param('pics');
        $label = $request->param()['label'];
        if (empty($game)) {
            $this->error('请选择游戏');
        }
        $edit = $this->postModel->editPost($id, $title, $label, $username, $game, $content, $pic);
        if ($edit) {
            $this->success('修改成功', 'admin/post/post');
        } else {
            $this->error('修改失败');
        }
    }

    // 是否发布帖子
    public function sendPost(Request $request)
    {
        $id = $request->param('id');
        $send = $this->postModel->sendPost($id);
        if ($send) {
            $this->success('操作成功', 'admin/post/post');
        } else {
            $this->error('操作失败');
        }
    }
}
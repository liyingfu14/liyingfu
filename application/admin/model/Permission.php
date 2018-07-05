<?php

namespace app\admin\model;

use think\Model;
use think\Config;
use think\Log;
use app\common\lib\Utils;
use think\Session;

class Permission extends Model
{
    //
    public function getPermissionList(){
        try{
            $data = $this->select();
            $tree = array();
            foreach ($data as $v) {
                $tree[] = $v->data;
            }
            // dump($tree);
            $result = $this->cateTree($tree);
            if($result){
                return $result;
            }else{
                return Utils::error(4529,'权限列表查询失败');
            }          
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4528,'获取权限列表失败');
    }


    //无限极递归分类
    protected function cateTree($data,$pid=0,$level=1){
        $tree = array();
        foreach($data as $k => $v){
            if($v['pid'] == $pid){
                $v['level'] = $level;
                $v['child'] = $this->cateTree($data,$v['id'],$level+1);
                $tree[] = $v;
            }
        }
        return $tree;
    }

    public function getGameListPower(){
        $id = $this->where('title','游戏列表')->find()->data['id'];
        $powers = Session::get('powers')[$id];
        return $powers;
    }

    //数据中心->用户管理
    public function getUserListPower(){
        $id = $this->where('title','用户管理')->find()->data['id'];
        $powers = Session::get('powers')[$id];
        return $powers;
    }

    public function getBannerListPower(){
        $id = $this->where('title','轮播图管理')->find()->data['id'];
        $powers = Session::get('powers')[$id];
        return $powers;
    }

    public function getCommunityListPower(){
        $id = $this->where('title','社区管理')->find()->data['id'];
        $powers = Session::get('powers')[$id];
        return $powers;
    }

    public function getManagerListPower(){
        $id = $this->where('title','管理员列表')->find()->data['id'];
        $powers = Session::get('powers')[$id];
        return $powers;
    }

    public function getRolesListPower(){
        $id = $this->where('title','角色列表')->find()->data['id'];
        $powers = Session::get('powers')[$id];
        return $powers;
    }

    public function getGiftListPower(){
        $id = $this->where('title','礼包管理')->find()->data['id'];
        $powers = Session::get('powers')[$id];
        return $powers;
    }

    public function getPostListPower(){
        $id = $this->where('title','帖子管理')->find()->data['id'];
        $powers = Session::get('powers')[$id];
        return $powers;
    }

    public function getWordListPower(){
        $id = $this->where('title','违规禁词')->find()->data['id'];
        $powers = Session::get('powers')[$id];
        return $powers;
    }
}

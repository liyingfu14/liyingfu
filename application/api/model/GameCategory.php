<?php
namespace app\api\model;

use think\Model;
use think\Db;
use think\Config ;

//查询分类信息
class GameCategory extends Model
{
    public function getCategory(){
        $field = 'id,cate_name,icon,pid';
        $data = $this -> field($field) -> select();
        // dump($data);
        $cates = array();
        foreach ($data as $key => $value) {
            $cates[] = $value->data;
        }
        $cateTree = $this->cateTree($cates);        //分类
        if($cateTree){
            return $cateTree;
        }else{
            return false;
        }
    }

    //无限极递归分类
    protected function cateTree($data,$pid=0){
        $tree = array();
        foreach($data as $k => $v){
            if($v['pid'] == $pid){
                $v['child'] = $this->cateTree($data,$v['id']);
                $tree[] = $v;
            }
        }
        return $tree;
    }
}
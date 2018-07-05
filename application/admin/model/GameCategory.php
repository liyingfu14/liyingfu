<?php

namespace app\admin\model;

use think\Model;
use think\Config;
use app\common\lib\Utils;
use think\Log;

class GameCategory extends Model
{
    //游戏id获取一级游戏分类
    public function getCategory($gameId){
        try{
            $cates = array();
            $category = Config::get('database.prefix').'game_category';
            $field = 'id,cate_name,game_id';
            $result = $this->table($category)->field($field)->where('pid',0)->order('sort asc')->select();     //根据排序获取分类
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
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4504,'获取游戏分类失败');
        
    }

    //游戏一级分类列表
    public function getCateMenu(){
        try{
            $field = 'id,cate_name';
            $result = $this->field($field)->where('pid',0)->select();
            if($result){
                return $result;
            }else{
                return Utils::error(4535,'获取游戏分类列表失败');
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4534,'获取游戏分类列表失败');
    }

    //获取对应一级分类的二级分类
    public function getSecondMenu($id){
        try{
            $field = 'id,cate_name';
            $result = $this->field($field)->where('pid',$id)->select();
            if($result){
                return $result;
            }else{
                return Utils::error(4541,'获取二级分类列表失败');
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4541,'获取二级分类列表失败');
    }

    //获取游戏的分类
    public function updCate($id){
        try{
            $field = 'id,cate_name,pid,game_id';
            $cates = $this->field($field)->cache(true, 60)->select();

            $arr = array();
            foreach ($cates as $v) {
                $arrCate = explode(',',$v->data['game_id']);
                if (in_array( $id, $arrCate)) {
                    $arr[ $v->data['id'] ] = $v->data['cate_name'];
                }
            }
            return $arr;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4541,'获取分类失败');
    }

    public function changeArr($childCateLists){
        $arr = array();
        foreach ($childCateLists as $v) {
            $arr[] = $v->data;
        }
        return $arr;
    }

}

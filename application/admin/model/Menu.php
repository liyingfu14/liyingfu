<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;

class Menu extends Model
{
    public function getIndexMenu(){
        try{
            $result = $this->select();
            return $result;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4555,'获取首页菜单失败');
    }

    public function getIsMenu($id){
        try{
            $arrTag = array();
            $field = 'id,menu_name,game_id';
            $result = $this->field($field)->select();         //获取首页
            foreach ($result as $v) {
                $arr = array();
                $arr = explode(',',$v->data['game_id']);
                if(in_array($id,$arr)){
                    $arrTag[] = $v->data['id'];
                }
            }
            return $arrTag;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4555,'获取游戏首页菜单失败');
    }

    public function getMenuName($gameId){
        try{
            $arrMenu = array();
            $field = 'id,menu_name,game_id';
            $result = $this->field($field)->select();         //获取首页
            foreach ($result as $v) {
                $arr = array();
                $arr = explode(',',$v->data['game_id']);
                if(in_array($gameId,$arr)){
                    $arrMenu[] = $v->data['menu_name'];
                }
            }
            return $arrMenu;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4558,'获取首页菜单名失败');
    }
}

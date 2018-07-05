<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;

class GameTags extends Model
{
    const SELECTED = '精选';
    const HOT ='热门';
    const ORDINARY = '普通';
    const BEST_NEW = '最新';
    const BEST_HOT = '最热';
    // 是否精选,热门,普通 游戏列表页下拉菜单
    public function getTagsStatus(){
        try{
            $field = 'id,tag_name';
            $result = $this->field($field)->where('tag_name',self::SELECTED)->whereOr('tag_name',self::HOT)->whereOr('tag_name',self::ORDINARY)->select();
            if($result){
                return $result;
            }else{
                return Utils::error(4501,'获取失败');
            }
        }catch (\Exception $e){
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4502,'获取失败');
    }

    //游戏ID判断是否是精选,热门,普通
    public function getIsHotOrIsSelect($gameId){
        try{
            $arrTag = array();
            $tags = Config::get('database.prefix').'game_tags';
            $field = 'id,tag_name,game_id';
            $result = $this->table($tags)
                ->field($field)
                ->where('tag_name',self::SELECTED)
                ->whereOr('tag_name',self::HOT)
                ->whereOr('tag_name',self::ORDINARY)
                ->select();         //获取精选,热门,普通标签
    
            foreach ($result as $v) {
                $arr = array();
                $arr = explode(',',$v->data['game_id']);
                if(in_array($gameId,$arr)){
                    $arrTag[] = $v->data['tag_name'];
                }
            }
            return $arrTag;
        }catch (\Exception $e){
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4505,'判断是否是精选,热门,普通失败');
        
    }

    //获取该游戏的标签
    public function getGameTags($gameId){
        try{
            $arrTag = array();
            $tags = Config::get('database.prefix').'game_tags';
            $field = 'id,tag_name,game_id';
            $result = $this->table($tags)
                ->field($field)
                ->where('tag_name','<>',self::SELECTED)
                ->where('tag_name','<>',self::HOT)
                ->where('tag_name','<>',self::ORDINARY)
                ->where('tag_name','<>',self::BEST_NEW)
                ->where('tag_name','<>',self::BEST_HOT)
                ->select();         //排除精选,热门,普通标签
    
            foreach ($result as $v) {
                $arr = array();
                $arr = explode(',',$v->data['game_id']);
                if(in_array($gameId,$arr)){
                    $arrTag[] = $v->data['tag_name'];
                }
            }
            return $arrTag;
        }catch (\Exception $e){
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4505,'获取游戏标签');
    }

    public function getHotOrNew(){
        try{
            $field = 'id,tag_name';
            $result = $this->field($field)->where('tag_name',self::BEST_NEW)->whereOr('tag_name',self::BEST_HOT)->select();
            return $result;
        }catch (\Exception $e){
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4567,'获取最新最热ID');
    }

    public function getSelectHotNew($id){
        try{
            $arrTag = array();
            $field = 'id,tag_name,game_id';
            $result = $this->field($field)
                ->where('tag_name',self::BEST_HOT)
                ->whereOr('tag_name',self::BEST_NEW)
                ->select();     //获取最新最热
            foreach ($result as $v) {
                $arr = array();
                $arr = explode(',',$v->data['game_id']);
                if (in_array($id,$arr)) {
                    $arrTag[] = $v->data['tag_name'];
                }
            }
            return $arrTag;
        }catch (\Exception $e){
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4569,'判断是否最新最热失败');
    }
}

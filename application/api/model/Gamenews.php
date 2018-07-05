<?php
namespace app\api\model;

use think\Model;
use think\Config;
use think\Log;

class Gamenews extends Model
{
    const GET_NUM = 10 ; // 每页查询条数
    const GET_TYPE = 1 ; // 查询类型
    const LIMIT_NUM = 3;    //资讯显示条数
    protected $dateFormat = 'U';

    public function getStrategy($gameId,$page,$postType,$key,$long){
        // $total = $this->where('app_id',$gameId)->select();
        if(empty($long)){
            $long = self::GET_NUM;
        }
        if(empty($postType) || $postType == self::GET_TYPE){
            $p = ($page-1)*$long;
            $sql = "SELECT * FROM `c_app_gamenews` WHERE  `app_id` = $gameId  AND `type` = '1' AND `post` like '".$key."%' LIMIT $p,$long ";
            $total = $this->query($sql);
            return $total;
        }else{
            $p = ($page-1)*$long;
            $total = $this->query("SELECT * FROM `c_app_gamenews` WHERE  `app_id` = $gameId  AND `type` = '1'  AND `post_type` = $postType LIMIT $p,$long ");
            return $total;
        }
        
    }

    public function getStrategyTop($postType){
        if(empty($postType)){
            $table = Config::get('database.prefix').'strategy_type';
            $field = 'id,type';
            $data = $this->field($field)->cache(true,60)->table($table)->select();
            return $data;
        }else{
            $table = Config::get('database.prefix').'strategy_type';
            $field = 'id,type';
            $data = $this->field($field)->table($table)->select();
            return $data;
        }
    }

    // 通过游戏id获取游戏资讯
    public function getInformation($gameId){
        return $data = $this->where('app_id',$gameId)->where('type',2)->order('time desc')->limit(self::LIMIT_NUM)->select();
    }

    //搜索攻略
    public function getSearchStrategy($gameId,$postType,$key){
        try{
            if(empty($postType)){
                $data = $this
                    ->where('app_id',$gameId)
                    ->where('type',self::GET_TYPE)
                    ->where('post','like',$key.'%')
                    ->select();
            }else{
                $data = $this
                    ->where('app_id',$gameId)
                    ->where('type',self::GET_TYPE)
                    ->where('post_type',$postType)
                    ->where('post','like',$key.'%')
                    ->select();
            }
            return $data;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
    }
}
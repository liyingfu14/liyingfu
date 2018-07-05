<?php

namespace app\api\model;

use app\common\lib\Utils;
use think\Config;
use think\Log;
use think\Model;

class GameBanner extends Model
{
    const SHOW_BANNER = 1;  // 为展示在首页的轮播图
    const BANNER_TYPE_ONE = 1;  // 首页轮播图
    const BANNER_TYPE_TWO = 2;  // 首页广告
    const MENU_RECOMMEND = 4;  // 关联游戏信息推荐广告图
    const SELECT_BN = 3;    //精选页背景图
    const IS_USE = 1;    //
    protected $table = 'c_app_index_banner';
    protected $dateFormat = 'U'; // 时间格式 Unix

    // 获取APP首页轮播图和广告图
    public function getBanner($use = self::SHOW_BANNER)
    {

        try {
            $type_one = $this->field('type')->where(['is_use' => $use, 'type' => self::BANNER_TYPE_ONE])->find();
            $type_two = $this->field('type')->where(['is_use' => $use, 'type' => self::BANNER_TYPE_TWO])->find();
            if($type_one){
                $banner = $this->where(['is_use' => $use, 'type' => self::BANNER_TYPE_ONE])->select();
            }

            if($type_two){
                $ad_banner = $this->where(['is_use' => $use, 'type' => self::BANNER_TYPE_TWO])->select();
            }
            return ['banner' => $banner, 'ad_banner' =>$ad_banner ];

        } catch (\Exception $e) {
            Log::error(json_encode($e));
        }
        return Utils::error(2006, '数据异常');
    }

    public function getSelectedBanner(){
        try{
            $field = 'id,app_id,banner';
            $banner = $this->field($field)->where('type',self::SELECT_BN)->where('is_use',self::IS_USE)->order('create_at desc')->find();
            return $banner;
        }catch (\Exception $e) {
            Log::error(json_encode($e));
        }
    }

    //获取首页首个推荐游戏banner
    public function getFirstRecommendBanner(){
        try{
            $field = 'id,app_id,banner';
            $banner = $this->field($field)->where('type',self::MENU_RECOMMEND)->where('is_use',self::IS_USE)->order('create_at desc')->find();
            if ($banner) {
                return $banner->data['banner'];
            }else{
                return '';
            }
            return $banner;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
        return Utils::error(4145, '获取推荐游戏banner失败');
    }
}

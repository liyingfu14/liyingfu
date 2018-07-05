<?php

namespace app\api\model;

use app\common\lib\Utils;
use think\Config;
use think\Log;
use think\Model;

class Appinfo extends Model
{
    protected $table = 'c_app_info';
    protected $dateFormat = 'U'; // 时间格式 Unix

    // APP版本信息
    public function getVersion()
    {
        try {
            $info = $this->field('version,app_name,update_at,create_at,info,url,is_update')->order('create_at desc')->find();
            return $info;
        } catch (\Exception $e) {
            Log::error(json_encode($e));
        }
        return Utils::error(2006, '数据异常');
    }


    // 关于我们
    public function aboutUs()
    {
        try{
            $contact = Config::get('database.prefix') . 'game_contact';

            $about = $this->table($contact)->select();
            return $about;

        }catch (\Exception $e){
            Log::error(json_encode($e));
        }
        return Utils::error(2006, '数据异常');
    }


}

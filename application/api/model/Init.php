<?php
/**
 * Created by TianQi.
 * User: weblinuxgame
 * Email: 994685563@qq.com
 * Date: 2018-05-04
 * Time: 13:08
 */

namespace app\api\model;

use app\common\model\Base ;
use app\common\lib\Utils;
use think\Log;

class Init extends Base
{
    const  TMP_ACCESS_LEN = 4;
    const  IMEI_MAKE_LEN = 8 ;
    public $table = 'c_app_init';

    public function makeImei()
    {
        $ip = request()->ip();
        $time = time();
        $rs  = Utils::random(self::IMEI_MAKE_LEN);
        return md5($ip.$time.$rs);
    }

    /**
     * 访问 获取 code
     * @param $imeil
     * @param $agent
     * @param $user_agent
     * @return array|bool|false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getAccessUser($imeil,$agent,$user_agent)
    {
        try {
            $_data = ['imei'=>$imeil,'agent'=>$agent] ;

            $count = $this->count('id');
            $data = $this->where($_data)->find();
            if(empty($data)){
                $_data['access_ip'] = request()->ip();
                $_data['code'] = Utils::random(self::TMP_ACCESS_LEN);
                $_data['create_at'] = time();
                $_data['user_agent']= $user_agent;
                $_data['xcode'] = md5($count.$imeil.$agent.$user_agent.$_data['code'].$_data['create_at']);
                $rs = $this->insert($_data,false,true);
                if($rs){
                    return ['x-code'=>$_data['xcode'],'imeil'=>$imeil ];
                }
            }
            if(is_object($data) && isset($data->xcode)){
                return [ 'x-code'=>$data->xcode ];
            }
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(1338,'平台用户信息,初始失败');
    }

}
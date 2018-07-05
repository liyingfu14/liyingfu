<?php
namespace app\api\model;

use think\Model;
use think\Db;
use think\Config ;
use app\api\model\User;
use app\common\lib\Utils;
use think\Log;

class Gift extends Model
{
    const GIFT_LEN = 5;
    const SET_LONG = 5;
    const GIFT_ON = 1;
    const GIFT_OFF = 2;

    public function giftList($gameId,$userId)
    {
        try{
            if(empty($long)){
                $long = self::SET_LONG;
            }
            if (!empty($userId)) {
                $userModel = new User();
                $userVip = $userModel->getVip($userId);       //获取用户vip等级信息
            }
            if (isset($userVip->data)) {
                $userVipLevel = $userVip->data['name'];
                $userVipLevel = (int)substr($userVipLevel,3);       //获取用户vip等级
            }else{
                $userVipLevel = 9;      //否则为平台用户
            }
            $table = Config::get('database.prefix').'gift';
            $code = Config::get('database.prefix').'gift_code';
            #显示礼包列表(未登录)
            if ( empty($userId) && !empty($gameId) ) {
                $arr = array();
                $field = 'id,icon,title,content,type,directions,start_time,end_time';
                $time = time();
                $data = $this->table($table)        //获取对应游戏ID礼包
                ->field($field)
                    ->where('app_id',$gameId)
                    ->where('end_time','>',$time)
                    ->where('is_on',self::GIFT_ON)
                    ->order('update_time desc')
                    ->select();
                if ($data) {
                    foreach ($data as $v) {
                        //礼包类型
                        if ($v->data['type'] == 9) {
                            $v->data['type'] = 'APP专属';
                        }elseif($v->data['type']>=1 && $v->data['type']<=8){
                            $v->data['type'] = '贵族'.$v->data['type'].'可领';
                        }else{
                            return Utils::error(4101,'礼包类型异常');
                        }
                        $v->data['total'] = $this->table($code)->where('gf_id',$v->data['id'])->count();      //礼包总数量
                        $v->data['remain'] = $this->table($code)->where('gf_id',$v->data['id'])->where('mem_id',0)->count();      //礼包剩余数量
                        $v->data['code'] = '';
                        $v->data['is_get'] = false;
                        $v->data['sign'] = false;
                        $arr[] = $v->data;
                    }
                }
                array_multisort(array_column($arr,'remain'),SORT_DESC,$arr);
                return $arr;
            }elseif( !empty($userId) && !empty($gameId) ){        //显示礼包列表(登录)
                $arr = array();
                $field = 'id,icon,title,content,type,directions,start_time,end_time';
                $time = time();
                $data = $this->table($table)
                    ->field($field)
                    ->where('app_id',$gameId)
                    ->where('end_time','>',$time)
                    ->where('is_on',self::GIFT_ON)
                    ->order('update_time desc')
                    ->select();
                foreach ($data as $v) {
                    $v->data['total'] = $this->table($code)->where('gf_id',$v->data['id'])->count();      //礼包总数量
                    $v->data['remain'] = $this->table($code)->where('gf_id',$v->data['id'])->where('mem_id',0)->count();      //礼包剩余数量
                    if (($v->data['type'] <= $userVipLevel && $userVipLevel != 9) || ($v->data['type'] == 9 && $userVipLevel != 9 )) {
                        $sign = $this->table($code)->where('gf_id',$v->data['id'])->where('mem_id',$userId)->find();
                        if ($sign) {
                            $v->data['is_get'] = true;      //已领取
                            $v->data['code'] = $sign->data['code'];     //已领取的code
                            $v->data['sign'] = true;       //等级够
                        }else{
                            $v->data['is_get'] = false;     //未领取
                            $v->data['sign'] = true;
                        }
                    }elseif($v->data['type'] == 9 && $userVipLevel == 9){
                        $sign = $this->table($code)
                            ->where('gf_id',$v->data['id'])
                            ->where('mem_id',$userId)
                            ->find();
                        if ($sign) {
                            $v->data['is_get'] = true;      //已领取
                            $v->data['code'] = $sign->data['code'];     //已领取的code
                            $v->data['sign'] = true;
                        }else{
                            $v->data['is_get'] = false;     //未领取
                            $v->data['sign'] = true;
                        }
                    }else{
                        $v->data['sign'] = false;       //等级不够
                        $v->data['is_get'] = false;
                    }
                    //礼包类型
                    if ($v->data['type'] == 9) {
                        $v->data['type'] = 'APP专属';
                    }elseif($v->data['type']>=1 && $v->data['type']<=8){
                        $v->data['type'] = '贵族'.$v->data['type'].'可领';
                    }else{
                        return Utils::error(4101,'礼包类型异常');
                    }
                    $arr[] = $v->data;
                }
                array_multisort(array_column($arr,'remain'),SORT_DESC,$arr);
                return $arr;
            }
            return ['msg' => '数据异常', 'code' => 4014];
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }

        return Utils::error(2006, '数据异常');
    }

    //领取礼包
    public function userGetGift($giftId,$userId){
        try{
            $code = Config::get('database.prefix').'gift_code';
            $is_get = $this->table($code)->where('gf_id',$giftId)->where('mem_id',$userId)->find();
            if ($is_get) {
                return Utils::error(4560,'已领取过');
            }
            $gift = $this->table($code)->where('gf_id',$giftId)->where('mem_id',0)->find();
            if (empty($gift)) {
                return Utils::error(4143,'礼包已领取完');
            }
            $id = $gift->data['id'];
            $result = $this->table($code)->where('id',$id)->update(['mem_id'=>$userId]);
            if ($result) {
                $giftResult = $this->table($code)->where('id',$id)->find();
                return ['code' => $giftResult->data['code']];
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4559,'领取礼包异常');
    }

    public function giftDetail($giftId,$userId){
        try{
            if (!empty($userId)) {
                $userModel = new User();
                $userVip = $userModel->getVip($userId);       //获取用户vip等级信息
            }
            if (isset($userVip->data)) {
                $userVipLevel = $userVip->data['name'];
                $userVipLevel = (int)substr($userVipLevel,3);       //获取用户vip等级
            }else{
                $userVipLevel = 9;      //否则为平台用户
            }

            $table = Config::get('database.prefix').'gift';
            $code = Config::get('database.prefix').'gift_code';
            if( empty($userId) && !empty($giftId)){       
                $field = 'id,icon,title,content,type,start_time,end_time,directions';
                $gift = $this->table($table)->field($field)->where('id',$giftId)->where('is_on',self::GIFT_ON)->find();
                $total = $this->table($code)->where('gf_id',$giftId)->count();
                $remain = $this->table($code)->where('gf_id',$giftId)->where('mem_id',0)->count();
                //礼包类型
                if ($gift->data['type'] == 9) {
                    $gift->data['type'] = 'APP专属';
                }elseif($gift->data['type']>=1 && $gift->data['type']<=8){
                    $gift->data['type'] = '贵族'.$gift->data['type'].'可领';
                }else{
                    return Utils::error(4101,'礼包类型异常');
                }
                $gift['total'] = $total;        //礼包总数量
                $gift['remain'] = $remain;      //剩余数量
                $gift['is_get'] = false;     //未领取
                $gift['sign'] = false;
                return $gift;
            }elseif( !empty($userId) && !empty($giftId)){
                $field = 'id,icon,title,content,type,start_time,end_time,directions';
                $gift = $this->table($table)->field($field)->where('id',$giftId)->where('is_on',self::GIFT_ON)->find();

                $sign = $this->table($code)->where('gf_id',$giftId)->where('mem_id',$userId)->find();

                $gift->data['total'] = $this->table($code)->where('gf_id',$giftId)->count();      //礼包总数量
                $gift->data['remain'] = $this->table($code)->where('gf_id',$giftId)->where('mem_id',0)->count();      //礼包剩余数量
                if ($sign) {
                    $gift->data['code'] = $sign->data['code'];
                    $gift->data['is_get'] = true;      //已领取
                    $gift->data['sign'] = true;
                    return $gift;
                    }else{
                        if (($gift->data['type'] <= $userVipLevel && $userVipLevel != 9) || ($gift->data['type'] == 9 && $userVipLevel != 9) ) {
                            $sign = $this->table($code)->where('gf_id',$gift->data['id'])->where('mem_id',$userId)->find();
                            if ($sign) {
                                $gift->data['is_get'] = true;      //已领取
                                $gift->data['code'] = $sign->data['code'];     //已领取的code
                                $gift->data['sign'] = true;       //等级够
                            }else{
                                $gift->data['is_get'] = false;     //未领取
                                $gift->data['sign'] = true;
                            }
                        }elseif($gift->data['type'] == 9 && $userVipLevel == 9){
                            $sign = $this->table($code)
                                ->where('gf_id',$gift->data['id'])
                                ->where('mem_id',$userId)
                                ->find();
                            if ($sign) {
                                $gift->data['is_get'] = true;      //已领取
                                $gift->data['code'] = $sign->data['code'];     //已领取的code
                                $gift->data['sign'] = true;
                            }else{
                                $gift->data['is_get'] = false;     //未领取
                                $gift->data['sign'] = true;
                            }
                    }else{
                        $gift->data['sign'] = false;       //等级不够
                        $gift->data['is_get'] = false;
                    }
                }
                return $gift;
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4142,'查看礼包详情失败');
    }
}
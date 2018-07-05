<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;
use think\Session;

class DayPtb extends Model
{
    const NUM = 10;
    //C币管理
    public function getPtbAdministration($param,$sTimeKey,$eTimeKey,$page){
        try{
            $n = self::NUM;
            $m = ($page-1)*$n;

            $sTimeCon = '';
            if(!empty($sTimeKey)){
                $sTimeCon = " AND `date`>='".$sTimeKey."'";
            }

            $eTimeCon = '';
            if(!empty($eTimeKey)){
                $eTimeCon = " AND `date`<='".$eTimeKey."'";
            }

            $sql = "SELECT * FROM `c_app_day_ptb` WHERE 1".$sTimeCon.$eTimeCon." ORDER BY `date` DESC LIMIT {$m},{$n}";
            $data = $this->query($sql);

            $countSql = "SELECT count(*) AS sum FROM `c_app_day_ptb` WHERE 1".$sTimeCon.$eTimeCon;

            $count = $this->query($countSql)[0]['sum'];
            Session::set('page_sum',$count);

            return $data;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4572,'查询C币管理失败');
    }

    //充值汇总数据列表
    public function getRechargeSum($monthKey,$page){
        try{
            $n = self::NUM;
            $m = ($page-1)*$n;

            if(empty($monthKey)){
                $sql = "SELECT * FROM `c_app_day_recharge` WHERE 1 ORDER BY `date` DESC";
                $countSql = "SELECT count(*) AS sum FROM `c_app_day_recharge` WHERE 1";
            }else{
                $sql = "SELECT * FROM `c_app_day_recharge` WHERE month(`date`)='{$monthKey}' ORDER BY `date` DESC";
                $countSql = "SELECT count(*) AS sum FROM `c_app_day_recharge` WHERE month(`date`)='{$monthKey}'";
            }
            $data = $this->query($sql);

            $count = $this->query($countSql)[0]['sum'];
            Session::set('page_sum',$count);

            return $data;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4575,'获取充值汇总数据失败');
    }

    //充值汇总入库
    public function setDayRecharge($data){
        try{
            $strdate = strtotime($data['date']);
            $users = Config::get('database.prefix') . 'users';
            //当日活跃人数    
            $active = $this->table($users)->where('last_login_at','>',$strdate)->count();
            if ($active == 0 || $data['pay_user'] == 0) {
                $data['pay_rate'] = 0;
                $data['day_arpu'] = 0;
                $data['pay_arpu'] = 0;
            }else{
                $data['pay_rate'] = $data['pay_user']/$active;
                $data['day_arpu'] = $data['sum_money']/$active;
                $data['pay_arpu'] = $data['sum_money']/$data['pay_user'];
            }
            $recharge = Config::get('database.prefix') . 'day_recharge';
            $re = $this->table($recharge)->insert($data);
            return $re;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4576,'充值数据入库失败');
    }

    //C币管理入库
    public function setDayPtb($data){
        try{
            $dayPtb = Config::get('database.prefix') . 'day_ptb';
            $this->table($dayPtb)->insert($data);
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4577,'C币管理入库失败');
    }

}

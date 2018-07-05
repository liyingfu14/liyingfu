<?php

namespace app\admin\model;

use think\Session;
use app\common\lib\Utils;
use think\Log;
use app\common\model\BaseModel;
use \think\Validate;
use app\common\lib\Fun;

class OaPdbPay extends BaseModel
{
    const NUM = 10;
    const ANDROID = 1;
    const H5 = 2;
    const IOS = 3;
    const UNFIND = 4;

    const C_GRANT = 12;
    const DEL_GRANT = 42;

    protected $oa;
    protected $db_oa;
    protected $db_app;

    protected $table = 'c_pay';
    protected $otherConfigFile = 'platform/config.php';
    protected $otherRangeName = 'platform';
    protected $ptb_mem = 'c_ptb_mem';

    public function __construct(){
        if(!file_exists(CONF_PATH.'platform'.DS.'config.php')){
           $config = file_get_contents(CONF_PATH.'database.php');
           $config = str_replace('db_app','db_oa',$config);
           file_put_contents(CONF_PATH.'platform'.DS.'config.php',$config);
        }
        parent::__construct();
    }

    public function getPtbPay($orderKey,$userKey,$nickKey,$gameKey,$page){
        try{
            if (empty($page)) {
                $page = 1;
            }
            $n = self::NUM;
            $m = ($page-1)*$n;

            $orderCon = '';
            if (!empty($orderKey)) {
                $orderCon = " AND `pp`.order_id LIKE '".$orderKey."%'";
            }
            $userCon = '';
            if (!empty($userKey)) {
                $userKey = " AND `mem`.`username` LIKE '".$userKey."%'";
            }
            $nickCon = '';
            if (!empty($nickKey)) {
                $nickCon = " AND `mem`.`nickname` LIKE '".$nickKey."%'";
            }
            $gameCon = '';
            if (!empty($gameKey)) {
                $gameCon = " AND `pp`.`app_id`=".$gameKey;
            }

            $arr = array();
            $sql = "SELECT `pp`.*,`mem`.`username`,`mem`.`nickname`,`g`.`name`,`pm`.`remain` 
                FROM `c_ptb_pay` `pp` 
                LEFT JOIN `c_members` `mem` 
                ON `pp`.`mem_id`=`mem`.`id` 
                LEFT JOIN `c_game` `g` 
                ON `pp`.`app_id`=`g`.`id` 
                LEFT JOIN `c_ptb_mem` `pm` 
                ON `pm`.`mem_id`=`mem`.`id` 
                WHERE 1".$orderCon.$userKey.$nickCon.$gameCon." 
                ORDER BY `create_time` DESC 
                LIMIT {$m},{$n}";
            $data = $this->query($sql);

            foreach ($data as $v) {
                if ($v['status'] == 1) {
                    $v['status'] = '待处理';
                }elseif($v['status'] == 2){
                    $v['status'] = '成功';
                }elseif($v['status'] == 3){
                    $v['status'] = '失败';
                }else{
                    $v['status'] = '无';
                }

                $sql = "SELECT * FROM `c_ptb_mem` WHERE `mem_id`={$v['mem_id']}";
                $re = $this->query($sql);
                if (sizeof($re) != 0) {
                    $v['ptb_balance'] = $re[0]['total'];
                }else{
                    $v['ptb_balance'] = 0;
                }
                // dump($v['ptb_balance']);
                $sql = "SELECT * FROM `c_ptb_pay` WHERE `mem_id`={$v['mem_id']} ORDER BY `create_time`";
                $re = $this->query($sql);
                
                foreach ($re as $v2) {
                    if ($v2['create_time']<=$v['create_time']) {
                        $v['ptb_balance'] -= $v2['ptb_cnt'];
                    }
                }

                $arr[] = $v;
            }

            $countSql = "SELECT count(*) AS sum 
                FROM `c_ptb_pay` `pp` 
                LEFT JOIN `c_members` `mem` 
                ON `pp`.`mem_id`=`mem`.`id` 
                LEFT JOIN `c_game` `g` 
                ON `pp`.`app_id`=`g`.`id` 
                LEFT JOIN `c_ptb_mem` `pm` 
                ON `pm`.`mem_id`=`mem`.`id` 
                WHERE 1".$orderCon.$userKey.$nickCon.$gameCon;

            $count = $this->query($countSql)[0]['sum'];
            Session::set('page_sum',$count);

            return $arr;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4561,'获取消费数据失败');
    }

    //获取oa游戏列表
    public function getGameList($appGame){
        try{
            $appGameList = array();
            foreach ($appGame as $v) {
                $appGameList[] = $v->data['name'];
            }
            $field = 'id,name';
            // $oaGame = $this->oa->table('c_game')->field($field)->select();
            $sql = "SELECT `id`,`name` FROM `c_game` WHERE 1";
            $oaGame = $this->query($sql);

            $hasGame = array();
            foreach ($oaGame as $v) {
                if (array_search($v['name'],$appGameList) != false) {
                    $hasGame[ $v['id'] ] = $v['name'];
                }
            }
            return $hasGame;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4565,'获取oa和APP游戏失败');
    }

    public function getRecharge($page,$orderKey,$userKey,$sTimeKey,$eTimeKey,$payWayKey){
        try{
            if (empty($page)) {
                $page = 1;
            }
            $n = self::NUM;
            $m = ($page-1)*$n;

            $orderCon = '';
            if (!empty($orderKey)) {
                $orderCon = " AND `cpg`.order_id LIKE '".$orderKey."%'";
            }

            $userCon = '';
            if (!empty($userKey)) {
                $userCon = " AND `mem`.username LIKE '".$userKey."%'";
            }

            $sTimeCon = '';
            if(!empty($sTimeKey)){
                $sTimeKey = strtotime($sTimeKey);
                $sTimeCon = " AND `cpg`.`create_time`>".$sTimeKey;
            }

            $eTimeCon = '';
            if(!empty($eTimeKey)){
                $eTimeKey = strtotime($eTimeKey);
                $eTimeCon = " AND `cpg`.`create_time`<".$eTimeKey;
            }

            $payWayCon = '';
            if(!empty($payWayKey)){
                $payWayCon = " AND `pw`.`id`=".$payWayKey;
            } 

            $sql = "SELECT `cpg`.*,`mem`.`nickname`,`mem`.`username`,`pw`.`disc`,`cu`.`agent_name` 
                FROM `c_ptb_given` `cpg` 
                LEFT JOIN `c_members` `mem` 
                ON `cpg`.`mem_id` = `mem`.`id`
                LEFT JOIN `c_payway` as `pw` 
                ON `pw`.`payname` = `cpg`.`payway` 
                LEFT JOIN `c_users` as `cu`
                ON `cu`.`id` = `cpg`.`agent_id`
                WHERE 1".$orderCon.$userCon.$sTimeCon.$eTimeCon.$payWayCon."
                ORDER BY `create_time` DESC 
                LIMIT {$m},{$n}";
            $data = $this->query($sql);
            
            $arr = array();
            foreach ($data as $v) {
                $sql = "SELECT * FROM `c_ptb_given` WHERE `mem_id`={$v['mem_id']} AND `status`=2  ORDER BY `create_time`";
                $re = $this->query($sql);
                $v['ptb_total'] = 0;
                foreach ($re as $v2) {
                    if ($v2['create_time']<=$v['create_time']) {
                        $v['ptb_total'] += $v2['ptb_cnt'];
                    }
                }

                if ($v['status'] == 1) {
                    $v['status'] = '待支付';
                }elseif($v['status'] == 2){
                    $v['status'] = '支付完成';
                }elseif($v['status'] == 3){
                    $v['status'] = '支付失败';
                }else{
                    $v['status'] = '无';
                }
                // dump($v);
                $arr[] = $v;
            }

            $countSql = "SELECT count(*) AS sum  
                FROM `c_ptb_given` `cpg` 
                LEFT JOIN `c_members` `mem` 
                ON `cpg`.`mem_id` = `mem`.`id`
                LEFT JOIN `c_payway` as `pw` 
                ON `pw`.`id` = `cpg`.`payway` 
                WHERE 1".$orderCon.$userCon.$sTimeCon.$eTimeCon.$payWayCon;

            $count = $this->query($countSql)[0]['sum'];
            Session::set('page_sum',$count);
            return $arr;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4566,'获取充值数据失败');
    }

    //获取支付方式
    public function getPayWay(){
        try{
            $sql = "SELECT * FROM `c_payway` WHERE 1";
            $data = $this->query($sql);
            return $data;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4567,'获取支付方式');
    }

    //C币管理每日统计   次日凌晨开启
    public function getResultPtbDayCount(){
        try{
            $start_time = strtotime(date("Y-m-d",time()-3600*24));
            $end_time = strtotime(date("Y-m-d",time()))-1;
            $str_time = date("Y-m-d",$start_time);
            $sql = "SELECT sum(`ptb_cnt`) AS `ptb_sum` FROM `c_ptb_charge` WHERE `create_time`>={$start_time} AND `create_time`<{$end_time}";
            $ptb_sum = $this->query($sql);
            $ptb_sum = (int)$ptb_sum[0]['ptb_sum'];      //今日总充值C币
            if (empty($ptb_sum)) {
                $ptb_sum = 0;
            }
            $sql = "SELECT sum(`ptb_cnt`) AS `ptb_cnt` FROM `c_ptb_pay` WHERE `create_time`>={$start_time} AND `create_time`<{$end_time}";
            $ptb_cnt = $this->query($sql);
            $ptb_cnt = (int)$ptb_cnt[0]['ptb_cnt'];      //今日总消费C币
            if (empty($ptb_cnt)) {
                $ptb_cnt = 0;
            }
            // dump($ptb_cnt);

            $sql = "SELECT sum(`remain`) AS `remain` FROM `c_ptb_mem` WHERE `create_time`<={$start_time}";
            $history_ptb = $this->query($sql);
            $history_ptb = (int)$history_ptb[0]['remain'];      //今日总消费C币
            if (empty($history_ptb)) {
                $history_ptb = 0;
            }
            $data = [
                'date'  =>  $str_time,
                'ptb_sum'   =>  $ptb_sum,
                'ptb_cnt'   =>  $ptb_cnt,
                'history_ptb'   =>  $history_ptb
            ];
            return $data;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4573,'统计每日C币失败');
    }

    //C币发放
    public function userPtbGrant($param){
        try{
            $rule = [
                'username'  =>  'require',
                'remain'    =>  'require|number',
                'grant'     =>  'require|number',
                'regrant'     =>  'require|confirm:grant',
                'desc'     =>  'require',
                'secondpwd'     =>  'require'
            ];
            $msg = [
                'username.require'  =>  '账号名必须',
                'remain.require'  =>  '已有C币数量必须',
                'remain.number'  =>  '已有C币数量数值类型错误',
                'grant.require'  =>  '充值C币数量必须',
                'grant.require'  =>  '充值C币数量数值类型错误',
                'regrant.require'  =>  '确认C币数量必须',
                'regrant.confirm'  =>  '确认C币数量不一致',
                'desc.require'  =>  '描述必须',
                'secondpwd.require'  =>  '二级密码必须',
            ];
            $data = [
                'username'  =>  $param['username'],
                'remain'    =>  $param['remain'],
                'grant'     =>  $param['grant'],
                'regrant'   =>  $param['regrant'],
                'desc'      =>  $param['desc'],
                'secondpwd' =>  $param['secondpwd']
            ];
            $validate = new Validate($rule, $msg);
            $result   = $validate->check($data);        //验证
            $checkResult = $this->checkSecondpwd($param['secondpwd']);
            if (!$checkResult) {
                return '二级密码错误';
            }
            if($result){
                $sql = "SELECT `id`,`username` FROM `c_members` WHERE `username`='{$param['username']}'";
                $user = $this->query($sql);
                if (!empty($user)) {        //用户存在
                    $userId = $user[0]['id'];      //用户ID
                    $sql = "SELECT * FROM `c_ptb_mem` WHERE `mem_id`={$userId}";
                    $result = $this->query($sql);
                    if (!empty($result)) {      //c_ptb_mem已有记录
                        $new_remain = $param['remain']+$param['grant'];
                        $result = $this->table($this->ptb_mem)->where('mem_id',$userId)->update(['remain'=>$new_remain,'update_time'=>time()]);
                        // $sql = "UPDATE `c_ptb_mem` SET `remain`={$new_remain} where `mem_id`={$userId}";
                        // $result = $this->query($sql);
                        if ($result) {
                            Fun::logWriter(self::C_GRANT,$userId);
                            return true;
                        }else{
                            return false;
                        }
                    }else{
                        $data = [
                            'mem_id'    =>  $userId,
                            'sum_money' =>  0,
                            'total'     =>  0,
                            'remain'    =>  $param['remain'],
                            'create_time'   =>  time()
                        ];
                        $result = $this->insertGetId($data);
                        if ($result) {
                            Fun::logWriter(self::C_GRANT,$result);
                            return true;
                        }else{
                            return false;
                        }
                    }
                }else{
                    return self::UNFIND;
                }
            }else{
                return $validate->getError();
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4574,'C币发放失败');
    }

    //C币扣除
    public function  userPtbDel($param){
        try{
            $rule = [
                'username'  =>  'require',
                'remain'    =>  'require|number',
                'grant'     =>  'require|number',
                'regrant'     =>  'require|confirm:grant',
                'desc'     =>  'require',
                'secondpwd'     =>  'require'
            ];
            $msg = [
                'username.require'  =>  '账号名必须',
                'remain.require'  =>  '已有C币数量必须',
                'remain.number'  =>  '已有C币数量数值类型错误',
                'grant.require'  =>  '扣除C币数量必须',
                'grant.require'  =>  '扣除C币数量数值类型错误',
                'regrant.require'  =>  '确认C币数量必须',
                'regrant.confirm'  =>  '确认C币数量不一致',
                'desc.require'  =>  '扣除原因必须',
                'secondpwd.require'  =>  '二级密码必须',
            ];
            $data = [
                'username'  =>  $param['username'],
                'remain'    =>  $param['remain'],
                'grant'     =>  $param['grant'],
                'regrant'   =>  $param['regrant'],
                'desc'      =>  $param['desc'],
                'secondpwd' =>  $param['secondpwd']
            ];
            $validate = new Validate($rule, $msg);
            $result   = $validate->check($data);        //验证
            $checkResult = $this->checkSecondpwd($param['secondpwd']);
            if (!$checkResult) {
                return '二级密码错误';
            }
            if($result){
                $sql = "SELECT `id`,`username` FROM `c_members` WHERE `username`='{$param['username']}'";
                $user = $this->query($sql);
                if (!empty($user)) {        //用户存在
                    $userId = $user[0]['id'];      //用户ID
                    $sql = "SELECT * FROM `c_ptb_mem` WHERE `mem_id`={$userId}";
                    $result = $this->query($sql);
                    if (!empty($result)) {      //c_ptb_mem已有记录
                        $new_remain = $param['remain']-$param['grant'];
                        if ($new_remain<0){
                            return false;
                        }
                        $result = $this->table($this->ptb_mem)->where('mem_id',$userId)->update(['remain'=>$new_remain,'update_time'=>time()]);
                        if ($result) {
                            Fun::logWriter(self::DEL_GRANT,$userId);
                            return true;
                        }else{
                            return false;
                        }
                    }else{
                        return false;
                    }
                }else{
                    return self::UNFIND;
                }
            }else{
                return $validate->getError();
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4574,'C币扣除失败');
    }


    //检查二级密码
    protected function checkSecondpwd($secondPwd){
        if($secondPwd == '123456'){
            return true;
        }else{
            return false;
        }
    }

    //充值汇总每日统计  //次日凌晨开启
    public function getResultRechargeDayCount(){
        try{
            $start_time = strtotime(date("Y-m-d",time()-3600*24));
            $end_time = strtotime(date("Y-m-d",time()))-1;
            $str_time = date("Y-m-d",$start_time);
            // $end_time = date("Y-m-d H:i:s",$end_time);

            $sql = "SELECT sum(`amount`) AS `sum_money`,sum(`ptb_cnt`) AS `sum_ptb` FROM `c_ptb_pay` WHERE `create_time`>={$start_time} AND `create_time`<{$end_time}";
            $ptb_sum = $this->query($sql);      //当日充值金额,充值C币

            $sum_money = $ptb_sum[0]['sum_money'];
            if (empty($sum_money)) {
                $sum_money = 0;
            }
            $sum_ptb = $ptb_sum[0]['sum_ptb'];
            if (empty($sum_ptb)) {
                $sum_ptb = 0;
            }

            $sql = "SELECT sum(`ptb_cnt`) AS `total_ptb` FROM `c_ptb_pay` WHERE 1";     //总c币量
            $total_ptb = $this->query($sql);
            $total_ptb = $total_ptb[0]['total_ptb'];
            if (empty($total_ptb)) {
                $total_ptb = 0;
            }

            $sql = "SELECT `mem_id` FROM `c_ptb_pay` WHERE `create_time`>={$start_time} AND `create_time`<{$end_time} GROUP BY `mem_id`";
            $pay_user = $this->query($sql);
            $pay_user = sizeof($pay_user);      //充值人数

            $data = [
                'date'  =>  $str_time,
                'sum_money' =>  $sum_money,
                'sum_ptb'   =>  $sum_ptb,
                'total_ptb' =>  $total_ptb,
                'pay_user'  =>  $pay_user
            ];

            return $data;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4573,'统计每日C币失败');
    }

    //根据用户名查询用户C币余额
    public function getUserBalance($username){
        try{
            $sql = "SELECT `cm`.`id`,`cm`.`username`,`cpm`.`remain` FROM `c_members` `cm` 
                INNER JOIN `c_ptb_mem` `cpm` 
                ON `cpm`.`mem_id`=`cm`.`id`
                WHERE `username`='".$username."'";
            $user = $this->query($sql);

            if (empty($user)) {
                return 0;
            }else{
                return $user[0]['remain'];
            }
        }catch (\Exception $e) {
            Log::error(__FILE__.' at line '.__LINE__.' '.Utils::exportError($e));
        }
        return Utils::error(2006, '查询用户C币失败');
    }
}

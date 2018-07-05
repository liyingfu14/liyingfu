<?php

namespace app\admin\model;

use think\Model;
use think\Session;
use app\common\lib\Utils;
use think\Log;
use app\common\model\BaseModel;
use app\common\lib\Fun;

/**
 * 操作记录模型
 */
class Operation extends BaseModel
{
    protected $otherConfigFile = 'platform/config.php';
    protected $otherRangeName = 'platform';
    protected $ptb_mem = 'c_ptb_mem';
    protected $operation_table = 'c_oa_operation_record';

    /**
     * 操作类型取值范围
     * @var array
     */
    protected $type = array(

        '1' => '登录系统',
        '2' => '发放UU币',
        '3' => '充值UU币',
        '4' => '下载游戏包',
        '5' => '导出游戏列表',
        '6' => '更新用户信息',
        '7' => '新增用户',
        '8' => '更改用户状态',
        '9' => '新增用户组',
        '10' => '更新用户组信息',
        '11' => '更改每日限额',
        '12' => '游戏版本更新',
        '13' => '添加权限列表',
        '14' => '更新权限列表',
        '15' => '更新权限列表',
        '16' => '用户申请开通IOS游戏',
        '17' => '用户取消开通IOS游戏',
        '21' => '添加员工请假记录',
        '22' => '添加小组未开服日期',
    );




    /**
     * 添加操作记录
     * @param $type
     * @param $log
     * @return int
     *
     * type取值范围
     *
     * '1'=>'登录系统',
     * '2'=>'发放UU币',
     * '3'=>'充值UU币',
     * '4'=>'下载游戏包',
     * '5'=>'导出游戏列表',
     * '6'=>'更新用户信息',
     * '7'=>'新增用户',
     * '8'=>'更改用户状态',
     * '9'=>'新增用户组',
     * '10'=>'更新用户组信息',
     * '11'=>'更改每日限额',
     * '12'=>'游戏版本更新',
     * '13'=>'添加权限列表',
     * '14'=>'更新权限列表',
     * '15'=>'更新权限列表',
     * '16'=>'用户申请开通IOS游戏',
     * '17'=>'用户取消开通IOS游戏',
     *
     * '21'=>'添加员工请假记录',
     * '22'=>'添加小组未开服日期',
     * '23'=>'修改经验衰减记录-月最终经验',
     * '24'=>'导出经验衰减记录'
     * '25'=>'终止任务'
     * '26'=>'导出工资'
     * '27'=>'改为达标'
     *
     */
    public function addOperationRecord($type = 2, $log = '')
    {   
        try{
            if (empty($type) || empty($log)) {
                return false;
            }
            $user_id = Session::get('uid');
            if (!empty($user_id)) {
                $data = [
                    'user_id'   =>  $user_id,
                    'type'  =>  $type,
                    'log'   =>  $log . '(IP:' . Fun::getClientIp() . ')',
                    'create_time'   =>  time(),
                ];
                $this->table($this->operation_table)->insert($data);
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4574,'发放C币加入记录失败');
    }


}

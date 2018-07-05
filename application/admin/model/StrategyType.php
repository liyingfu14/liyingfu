<?php
namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;

class StrategyType extends Model
{
    protected $table = 'c_app_strategy_type';

    // 攻略类型
    public function Type()
    {
        try {
            $type = $this->select();

            return $type;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }
}
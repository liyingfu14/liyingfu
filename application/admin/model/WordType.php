<?php
namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;

class WordType extends Model
{
    protected $table = 'c_app_limit_word';

    // 攻略类型
    public function Type()
    {
        try {
            $word = $this->field('id,range')->select();

            return $word;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }
}
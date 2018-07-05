<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use app\common\lib\Fun;

class BanWord extends Model
{
    const UPD_WORD = 32;
    const DEL_WORD = 33;
    const ADD_WORD = 34;
    protected $table = 'c_app_limit_word';

    // 添加禁用词
    public function addBan($word, $rang)
    {
        try {
            $data = ['word_list' => $word, 'range' => $rang, 'create_at' => time(), 'update_at' => time()];
            $id = $this->insert($data);
            Fun::logWriter(self::ADD_WORD,$id);
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 禁用词汇类型
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

    // 删除类型下的所有禁忌词
    public function del($id)
    {
        try {
            $this->where('id', $id)->delete();
            Fun::logWriter(self::DEL_WORD,$id);
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑禁忌词页面
    public function Edit($id)
    {
        try {
            $word = $this->field('id,range,word_list')->where('id', $id)->find();

            return $word;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑禁忌词
    public function editWord($word_list,$range)
    {
        try {
            $this->where('range',$range)->update(['word_list'=>$word_list]);
            Fun::logWriter(self::UPD_WORD,$range);
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }


}
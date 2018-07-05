<?php
namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;

class PostGames extends Model
{
    protected $table = 'c_app_game';

    // 添加帖子的游戏下拉
    public function GameList()
    {
        try {
            $game = $this->field('id,name')->select();

            return $game;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }
}
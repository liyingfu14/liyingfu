<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;
use think\Session;
use app\common\lib\Fun;

class Strategy extends Model
{
    const GET_NUM = 10;

    const UPD_STRATEGY = 28;
    const DEL_STRATEGY = 29;

    protected $table = 'c_app_gamenews';

    // 攻略列表展示
    public function strategy($name, $title, $start_time, $end_time, $page)
    {
        try {
            $n = self::GET_NUM;
            $m = ($page-1)*$n;

            $game = Config::get('database.prefix') . 'game';
            $strategy_type = Config::get('database.prefix') . 'strategy_type';
            $strategy = $this
                ->alias('s')
                ->join($game . ' g ', ' g.id = s.app_id ', ' LEFT ')
                ->join($strategy_type . ' t ', ' t.id = s.post_type ', ' LEFT ')
                ->field('s.*,g.name,t.type as tname')
                ->where('g.name', 'like', '%' . $name . '%')
                ->where('s.post', 'like', '%' . $title . '%')
                ->whereBetween('s.time', [$start_time, $end_time])
                ->order('s.id desc')
                // ->paginate(5, false, ['query' => request()->param()]);
                ->limit($m,$n)->select();

            $countSql = $this
                ->alias('s')
                ->join($game . ' g ', ' g.id = s.app_id ', ' LEFT ')
                ->join($strategy_type . ' t ', ' t.id = s.post_type ', ' LEFT ')
                ->field('s.*,g.name,t.type as tname')
                ->where('g.name', 'like', '%' . $name . '%')
                ->where('s.post', 'like', '%' . $title . '%')
                ->whereBetween('s.time', [$start_time, $end_time])
                ->count();
            Session::set('page_sum',$countSql);

            foreach ($strategy as $v) {
                //获取上线状态
                if ($v->data['type'] == 1) {
                    $v->data['type'] = '攻略';
                }
                if ($v->data['type'] == 2) {
                    $v->data['type'] = '新闻';
                }
                if ($v->data['type'] == 3) {
                    $v->data['type'] = '其他';
                }
            }
            return $strategy;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 删除攻略
    public function del($id)
    {
        try {
            $this->where('id', $id)->delete();
            Fun::logWriter(self::DEL_STRATEGY,$id);
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑攻略页面
    public function edit($id)
    {
        try {
            $edit = $this->where('id', $id)->find();
            return $edit;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑攻略
    public function editStra($id, $title, $tags, $game, $content, $url, $news, $pic)
    {
        try {
            if (empty($pic)) {
                $data = ['type' => $news, 'post' => $title, 'post_type' => $tags, 'app_id' => $game, 'url' => $url];
            } else {
                $data = ['type' => $news, 'post' => $title, 'post_type' => $tags, 'app_id' => $game, 'url' => $url, 'image' => $pic];
            }
            $this->where('id', $id)->update($data);
            Fun::logWriter(self::UPD_STRATEGY,$id);
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }
}
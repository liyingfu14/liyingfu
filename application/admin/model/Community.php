<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;
use think\Session;
use app\common\lib\Fun;


class Community extends Model
{
    const INDEX_BANNER = 1; // 首页banner
    const AD_BANNER = 2; // 首页广告位banner
    const GAME_BANNER = 3; // 游戏推荐banner
    const NO_USE = 2; // 未使用轮播图
    const IS_USE = 1; // 使用中轮播图
    const GET_NUM = 5;

    const UPD_COMMUNITY = 19;
    const STOP_COMMUNITY = 20;
    const START_COMMUNITY = 21;
    const DEL_COMMUNITY = 22;
    const ADD_COMMUNITY = 23;

    protected $table = 'c_app_community';

    // 社区列表
    public function community($name, $status, $start_time, $end_time, $page)
    {
        try {
            $n = self::GET_NUM;
            $m = ($page-1)*$n;

            $data = [];
            if ($status != 0) {
                $data['is_use'] = ['=', $status];
            }

            $game = Config::get('database.prefix') . 'game';
            $list = $this->alias('c')
                ->join($game . ' g ', ' g.id=c.app_id ', 'LEFT')
                ->field('c.*,g.name')
                ->where($data)
                ->where('c.community_name', 'like', '%' . $name . '%')
                ->whereBetween('c.create_at', [$start_time, $end_time])
                // ->paginate(5, false, ['query' => request()->param()]);
                ->limit($m,$n)->select();

            $countSql = $this->alias('c')
                ->join($game . ' g ', ' g.id=c.app_id ', 'LEFT')
                ->field('c.*,g.name')
                ->where($data)
                ->where('c.community_name', 'like', '%' . $name . '%')
                ->whereBetween('c.create_at', [$start_time, $end_time])
                ->count();
            Session::set('page_sum',$countSql);

            foreach ($list as $v) {
                if ($v->data['is_use'] == self::NO_USE) {
                    $v->data['is_use'] = '已停用';
                } elseif ($v->data['is_use'] == self::IS_USE) {
                    $v->data['is_use'] = '已启用';
                }

            }

            return $list;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 停用社区
    public function stop($id)
    {
        try {
            $use = $this->where('id', $id)->find();
            $is_use = $use->is_use;
            if ($is_use == 1) {
                $this->where('id', $id)->update(['is_use' => 2]);
                Fun::logWriter(self::STOP_COMMUNITY,$id);
            } elseif ($is_use == 2) {
                $this->where('id', $id)->update(['is_use' => 1]);
                Fun::logWriter(self::START_COMMUNITY,$id);
            }
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 删除社区
    public function del($id)
    {
        try {
            $this->where('id', $id)->delete();
            Fun::logWriter(self::DEL_COMMUNITY,$id);
            return true;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 添加社区
    public function addCommunity($title, $content, $game, $use, $commImg)
    {
        try {
            $data = ['app_id' => $game, 'introduction' => $content, 'image' => $commImg, 'community_name' => $title, 'create_at' => time(), 'is_use' => $use];
            $id = $this->insertGetId($data);
            Fun::logWriter(self::ADD_COMMUNITY,$id);
            return true;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑社区页面
    public function edit($id)
    {
        try {
            $data = $this->where('id', $id)->find();
            return $data;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑社区数据
    public function editCommunity($id, $title, $content, $game, $use, $pic)
    {
        try {
            if (empty($pic)) {
                $data = ['app_id' => $game, 'introduction' => $content, 'community_name' => $title, 'is_use' => $use];
            } else {
                $data = ['app_id' => $game, 'introduction' => $content, 'image' => $pic, 'community_name' => $title, 'is_use' => $use];
            }
            $this->where('id', $id)->update($data);
            Fun::logWriter(self::UPD_COMMUNITY,$id);
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }
}
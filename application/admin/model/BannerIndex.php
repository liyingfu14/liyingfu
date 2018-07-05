<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Session;
use app\common\lib\Fun;

class BannerIndex extends Model
{
    const INDEX_BANNER = 1; // 首页banner
    const AD_BANNER = 2; // 首页广告位banner
    const GAME_BANNER = 3; // 游戏推荐banner
    const MENU_RECOMMEND = 4;  // 关联游戏信息推荐广告图
    const NO_USE = 2; // 未使用轮播图
    const IS_USE = 1; // 使用中轮播图
    const GET_NUM = 5;

    const UPD_BANNER = 14;
    const STOP_BANNER = 15;
    const START_BANNER = 16;
    const DEL_BANER = 17;
    const ADD_BANNER = 18;
    protected $table = 'c_app_index_banner';

    // 广告位列表
    public function ad($banner, $status, $start_time, $end_time, $page)
    {
        try {
            $n = self::GET_NUM;
            $m = ($page-1)*$n;

            $map = [];
            if ($banner != 0) {
                $map['type'] = ['=', $banner];
            }

            $data = [];
            if ($status != 0) {
                $map['is_use'] = ['=', $status];
            }


            $ad = $this->where($map)
                ->where($data)
                ->whereBetween('create_at', [$start_time, $end_time])
                // ->paginate(5, false, ['query' => request()->param()]);
                ->limit($m,$n)->select();

            $countSql = $this->where($map)
                ->where($data)
                ->whereBetween('create_at', [$start_time, $end_time])
                ->count();
            Session::set('page_sum',$countSql);

            foreach ($ad as $v) {
                if ($v->data['type'] == self::INDEX_BANNER) {
                    $v->data['type'] = '首页-banner';
                } elseif ($v->data['type'] == self::AD_BANNER) {
                    $v->data['type'] = '首页-广告图';
                } elseif ($v->data['type'] == self::GAME_BANNER) {
                    $v->data['type'] = '游戏-精选图';
                } elseif ($v->data['type'] == self::MENU_RECOMMEND) {
                    $v->data['type'] = '首页-推荐图';
                }

                if ($v->data['is_use'] == self::NO_USE) {
                    $v->data['is_use'] = '已停用';
                } elseif ($v->data['is_use'] == self::IS_USE) {
                    $v->data['is_use'] = '已启用';
                }

            }

            return $ad;

        } catch (\Exception $e) {
            Log::error($this->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 删除轮播图
    public function del($id)
    {
        try {
            $this->where('id', $id)->delete();
            Fun::logWriter(self::DEL_BANER,$id);
            return true;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 停用轮播图
    public function stop($id)
    {
        try {
            $use = $this->where('id', $id)->find();
            $is_use = $use->is_use;
            if ($is_use == 1) {
                $this->where('id', $id)->update(['is_use' => 2]);
                Fun::logWriter(self::STOP_BANNER,$id);
            } elseif ($is_use == 2) {
                $this->where('id', $id)->update(['is_use' => 1]);
                Fun::logWriter(self::START_BANNER,$id);
            }


            return true;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 添加轮播图
    public function addBanner($type, $game, $use, $banner)
    {
        try {
            $data = ['banner' => $banner, 'app_id' => $game, 'is_use' => $use, 'type' => $type, 'create_at' => time()];
            $id = $this->insertGetId($data);
            Fun::logWriter(self::ADD_BANNER,$id);
            return true;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑轮播图
    public function edit($id)
    {
        try {

            $data = $this->where('id', $id)->find();
            Fun::logWriter(self::UPD_BANNER,$id);
            return $data;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 修改轮播图数据
    public function editBanner($id, $type, $game, $use, $pic)
    {
        try {
            if (empty($pic)) {
                $data = ['app_id' => $game, 'is_use' => $use, 'type' => $type];
            } else {
                $data = ['banner' => $pic, 'app_id' => $game, 'is_use' => $use, 'type' => $type];
            }
            $this->where('id', $id)->update($data);

            return true;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }
}
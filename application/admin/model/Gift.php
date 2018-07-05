<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;
use think\Session;
use app\common\lib\Fun;

class Gift extends Model
{
    const VIP_ONE = 1; // vip1礼包
    const VIP_TWO = 2; // vip2礼包
    const VIP_THREE = 3; // vip3礼包
    const VIP_FOUR = 4; // vip4礼包
    const VIP_FIVE = 5; // vip5礼包
    const VIP_SIX = 6; // vip6礼包
    const VIP_SEVEN = 7; // vip7礼包
    const VIP_EIGHT = 8; // vip8礼包
    const APP_GIFT = 9; // 平台礼包
    const IS_OVERDUE = 1;   // 已过期
    const NO_OVERDUE = 0;   // 未过期
    const IS_DELETE = 1;   // 伪删除
    const IS_NORMAL = 2;  // 正常
    const IS_APP = 1;   // APP专属
    const NO_APP = 0;   // APP专属
    const GET_NUM = 10;

    const UPD_GIFT = 9;
    const DEL_GIFT = 10;
    const ADD_GIFT = 11;

    protected $table = 'c_app_gift';

    // 礼包列表
    public function gift($name, $gid, $times, $page)
    {
        try {
            $n = self::GET_NUM;
            $m = ($page-1)*$n;

            $map = [];
            if ($gid != 0) {
                $map['app_id'] = ['=', $gid];
            }

            $game = Config::get('database.prefix') . 'game';
            $code = Config::get('database.prefix') . 'gift_code';
            if ($times == 1) {
                $gift = $this->alias('f')
                    ->join($game . ' g ', ' g.id = f.app_id ', ' LEFT ')
                    ->field('f.id,f.create_time,f.title,g.name,f.type,f.start_time,f.end_time,f.status')
                    ->where('f.status = 2')
                    ->where('f.title', 'like', '%' . $name . '%')
                    ->where($map)
                    ->where('f.end_time', '>', time())

                    ->order('f.id desc')
                    // ->paginate(5, false, ['query' => request()->param()]);
                    ->limit($m,$n)
                    ->select();

                $countSql = $this->alias('f')
                    ->join($game . ' g ', ' g.id = f.app_id ', ' LEFT ')
                    ->field('f.id,f.create_time,f.title,g.name,f.type,f.start_time,f.end_time,f.status')
                    ->where('f.status = 2')
                    ->where('f.title', 'like', '%' . $name . '%')
                    ->where($map)
                    ->where('f.end_time', '>', time())
                    ->count();
            } elseif ($times == 2) {
                $gift = $this->alias('f')
                    ->join($game . ' g ', ' g.id = f.app_id ', ' LEFT ')
                    ->field('f.id,f.create_time,f.title,g.name,f.type,f.start_time,f.end_time,f.status')
                    ->where('f.status = 2')
                    ->where('f.title', 'like', '%' . $name . '%')
                    ->where($map)
                    ->where('f.end_time', '<', time())
                    ->order('f.id desc')
                    // ->paginate(5, false, ['query' => request()->param()]);
                    ->limit($m,$n)
                    ->select();

                $countSql = $this->alias('f')
                    ->join($game . ' g ', ' g.id = f.app_id ', ' LEFT ')
                    ->field('f.id,f.create_time,f.title,g.name,f.type,f.start_time,f.end_time,f.status')
                    ->where('f.status = 2')
                    ->where('f.title', 'like', '%' . $name . '%')
                    ->where($map)
                    ->where('f.end_time', '<', time())
                    ->count(); 
            } else {
                $gift = $this->alias('f')
                    ->join($game . ' g ', ' g.id = f.app_id ', ' LEFT ')
                    ->field('f.id,f.create_time,f.title,g.name,f.type,f.start_time,f.end_time,f.status')
                    ->where('f.status = 2')
                    ->where('f.title', 'like', '%' . $name . '%')
                    ->where($map)
                    ->order('f.id desc')
                    // ->paginate(5, false, ['query' => request()->param()]);
                    ->limit($m,$n)
                    ->select();

                $countSql = $this->alias('f')
                    ->join($game . ' g ', ' g.id = f.app_id ', ' LEFT ')
                    ->field('f.id,f.create_time,f.title,g.name,f.type,f.start_time,f.end_time,f.status')
                    ->where('f.status = 2')
                    ->where('f.title', 'like', '%' . $name . '%')
                    ->where($map)
                    ->count();
            }

            Session::set('page_sum',$countSql);
            foreach ($gift as $v) {
                $giftId = $v->data['id'];
                // 统计对应礼包数量
                $giftCount = $this->table($code)->where('gf_id', $giftId)->count();
                $v->data['all_gift'] = $giftCount;
                // 统计对应剩余礼包数量
                $surplusGiftCount = $this->table($code)->where(['gf_id' => $giftId, 'mem_id' => 0])->count();
                $v->data['surplus_gift'] = $surplusGiftCount;
                if ($v->data['type'] == self::VIP_ONE) {
                    $v->data['type'] = 'VIP1礼包';
                } elseif ($v->data['type'] == self::VIP_TWO) {
                    $v->data['type'] = 'VIP2礼包';
                } elseif ($v->data['type'] == self::VIP_THREE) {
                    $v->data['type'] = 'VIP3礼包';
                } elseif ($v->data['type'] == self::VIP_FOUR) {
                    $v->data['type'] = 'VIP4礼包';
                } elseif ($v->data['type'] == self::VIP_FIVE) {
                    $v->data['type'] = 'VIP5礼包';
                } elseif ($v->data['type'] == self::VIP_SIX) {
                    $v->data['type'] = 'VIP6礼包';
                } elseif ($v->data['type'] == self::VIP_SEVEN) {
                    $v->data['type'] = 'VIP7礼包';
                } elseif ($v->data['type'] == self::VIP_EIGHT) {
                    $v->data['type'] = 'VIP8礼包';
                } elseif ($v->data['type'] == self::APP_GIFT) {
                    $v->data['type'] = '平台礼包';
                }

            }

            return $gift;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 删除礼包
    public function del($id)
    {
        try {

            $this->where('id', $id)->delete();
            Fun::logWriter(self::DEL_GIFT,$id);
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 添加礼包
    public function add($game, $title, $code, $content, $directions, $start_time, $end_time, $type)
    {
        if (empty($game)) {
            return Utils::error(2116, '游戏名称为空');
        }
        if (empty($title)) {
            return Utils::error(2117, '礼包名称为空');
        }
        if (empty($code)) {
            return Utils::error(2118, '礼包码为空');
        }
        if (empty($content)) {
            return Utils::error(2119, '礼包内容为空');
        }
        if (empty($directions)) {
            return Utils::error(2120, '使用说明为空');
        }
        if (empty($start_time)) {
            return Utils::error(2120, '开始兑换时间为空');
        }
        if (empty($end_time)) {
            return Utils::error(2120, '结束时间为空');
        }
        if (preg_match("/[\x7f-\xff]/", $code)) {
            return Utils::error(2121, '礼包码请勿用中文');
        }
        if (empty($type)) {
            return Utils::error(2122, '礼包类型为空');
        }
        try {

            $codes = Config::get('database.prefix') . 'gift_code';


            $data = ['app_id' => $game, 'title' => $title, 'start_time' => $start_time, 'end_time' => $end_time, 'status' => self::IS_NORMAL, 'create_time' => time(), 'update_time' => time(), 'content' => $content, 'directions' => $directions, 'type' => $type];
            $giftId = $this->insertGetId($data);

            $gift = $this->field('id')->where(['app_id' => $game, 'title' => $title])->find();
            $gf_id = $gift->id;

            $codearr = explode(",", $code);
            foreach ($codearr as $val) {
                if (empty($val)) {
                    continue;
                }
                $dataList = array('gf_id' => $gf_id, 'code' => $val);
                $this->table($codes)->insert($dataList);
            }
            Fun::logWriter(self::ADD_GIFT,$giftId);
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑页面
    public function edit($id)
    {
        try {

            $codes = Config::get('database.prefix') . 'gift_code';

            $list = $this->alias('g')
                ->join($codes . ' c ', ' c.gf_id=g.id ')
                ->where('g.id', $id)
                ->field('g.id,g.app_id,g.type,g.title,c.code,g.content,g.directions,g.start_time,g.end_time')
                ->find();


            return $list;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 礼包码展示
    public function code($id)
    {
        try {
            $codes = Config::get('database.prefix') . 'gift_code';
            $c = $this->table($codes)->where('gf_id', $id)->field('code')->select();
            foreach ($c as $key => $v) {
                $a[] = $v->data['code'];
            }
            $code = implode(',', $a);
            return $code;
        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }

    // 编辑数据
    public function editGift($id, $game, $type, $title, $content, $directions, $start_time, $end_time)
    {
        try {

            $codes = Config::get('database.prefix') . 'gift_code';

            $data = ['app_id' => $game, 'title' => $title, 'start_time' => $start_time, 'end_time' => $end_time, 'update_time' => time(), 'content' => $content, 'directions' => $directions, 'type' => $type];
            $this->where('id', $id)->update($data);
            Fun::logWriter(self::UPD_GIFT,$id);
            return true;

        } catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(2006, '数据异常');
    }


}
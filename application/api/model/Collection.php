<?php
/**
 * Created by TianQi.
 * User: weblinuxgame
 * Email: 994685563@qq.com
 * Date: 2018-05-04
 * Time: 17:19
 */

namespace app\api\model;


use app\common\lib\Utils;
use app\common\model\Base;
use think\Log;

class Collection extends Base
{
    const STATUS_OK = 1;
    const STATUS_DEL = 2;

    const TYPE_STRATEGY = 1;
    const TYPE_POST = 2;
    const TYPE_GAME = 3;
    protected $table = 'c_app_collection';

    protected $area = [
        'info' => [
            'type', 'cid',
        ]
    ];

    /**
     * @param $uid
     * @return array
     */
    public function getCollection($uid)
    {
        if (empty($uid)) {
            return [];
        }
        try {
            $data = $this->where(['uid' => $uid, 'status' => self::STATUS_OK])
                ->field(implode(',', $this->area['info']))
                ->select();
            if (empty($data)) {
                return [];
            }
            $data = $this->afterSelect($data);
            return $data;
        } catch (\Exception $e) {
            Log::error($this->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return [];
    }

    // 收藏帖子
    public function collectionPost($uid,$type = self::TYPE_POST)
    {
        if (empty($uid)) {
            return [];
        }

        $table = 'c_app_post';
        $user_table = 'c_app_users';
        $game_table = 'c_app_game';
        try {

//            $posts = $this->table($table)->alias('p')
//                ->join($game_table . ' g ', ' p.app_id = g.id ', 'LEFT')
//                ->join( $this->table.' c ',' p.id = c.cid ', 'RIGHT')
//                ->whereIn('p.id', $post)
//                ->where(['c.uid' => $uid])
//                ->field('p.*,g.name,c.create_time as collection_time')
//                ->select();
            $rs = $this->where(['uid' => $uid,'type'=>$type])->field('cid,create_time as collection_time')->select();
            if(empty($rs)){
                Log::log(' 收藏帖子为空 '.$uid);
                return [];
            }
            $post = [];
            $items = [];
            foreach ($rs as $item){
                if(!empty($item) && !empty($item->cid)){
                    array_push($post,$item->cid);
                    if(empty($item[$item->cid])){
                        $items[$item->cid] = $item->collection_time;
                    }
                }
            }

            $posts = $this->table($table)
                ->alias('p')
                ->join($game_table . ' g ', ' p.app_id = g.id ', 'LEFT')
                ->whereIn('p.id', $post)
                ->field('p.*,g.name')
                ->select();

            $user = $this->table($user_table)->where(['id' => $uid])->field('nickname,portrait')->find();

            if (empty($posts)) {
                Log::log('[ 用户帖子 为空 ] id : ' . $uid);
                return [];
            }
            if (empty($user)) {
                Log::log('[ 用户不存在 ] id : ' . $uid);
                return [];
            }
            $data = [];
            $info = $user->toArray();
            if (is_array($posts)) {
                $this->afterSelectPics($posts);
                foreach ($posts as $v) {
                    if (!empty($v)) {
                        $tmp = $v->toArray();
                        $tmp['author'] = $info;
                        $tmp['collection_time'] = $items[$tmp['id']];
                        array_push($data, $tmp);
                    }
                }
                return $data;
            }
            return $posts->toArray();
        } catch (\Exception $e) {
            Log::error($this->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return [];
    }


    // 收藏攻略
    public function collectionStrategy($uid, $type = self::TYPE_STRATEGY)
    {
        if (empty($uid)) {
            return [];
        }
        $table = 'c_app_gamenews';
        $game_table = 'c_app_game';
        try {
            $rs = $this->where(['uid' => $uid,'type'=>$type])->field('cid,create_time as collection_time')->select();
            if(empty($rs)){
                Log::log(' 收藏攻略为空 '.$uid);
                return [];
            }

            $strategy = [];
            $items = [];
            foreach ($rs as $item){
                if(!empty($item) && !empty($item->cid)){
                    array_push($strategy,$item->cid);
                    if(empty($item[$item->cid])){
                        $items[$item->cid] = $item->collection_time;
                    }
                }
            }

            $strategys = $this->table($table)
                ->alias('n')
                ->join($game_table . ' g ', ' n.app_id = g.id ', 'LEFT')
                ->whereIn('n.id', $strategy)
                ->field('n.*,g.name')
                ->select();

            if (empty($strategys)) {
                Log::log('[ 用户攻略 为空 ] id : ' . $uid);
                return [];
            }
            $data = [];
            if (is_array($strategys)) {
                foreach ($strategys as $v) {
                    if (!empty($v)) {
                        $tmp = $v->toArray();
                        $tmp['collection_time'] = $items[$tmp['id']];
                        array_push($data, $tmp);
                    }
                }
                return $data;
            }
            return $strategys->toArray();
        } catch (\Exception $e) {
            Log::error($this->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return [];
    }

    protected function afterSelectPics(&$resultSet = [])
    {
        if (empty($resultSet)) {
            return;
        }
        foreach ($resultSet as $key => $data) {
            if (isset($data->pics)) {
                $data->pics = explode(',', $data->pics);
            }
        }
        return $resultSet;
    }

    protected function afterSelect(&$Sets = [])
    {
        if (empty($Sets) || !is_array($Sets)) {
            return [];
        }
        $result = [];
        foreach ($Sets as $collection) {
            if (empty($collection)) {
                continue;
            }
            $data = $collection->getData();
            if (!empty($data['type'])) {
                $type = $this->changeTypes($data['type']);
                if (!isset($result[$type])) {
                    $result[$type] = [];
                }
                array_push($result[$type], $data['cid']);
            }
        }
        return $result;
    }

    protected function changeTypes($type = null)
    {
        if (empty($type)) {
            return '';
        }
        if (self::TYPE_POST === $type) {
            return 'post';
        }
        if (self::TYPE_GAME === $type) {
            return 'game';
        }
        if (self::TYPE_STRATEGY === $type) {
            return 'strategy';
        }
        return '';
    }

    /**
     * 添加 收藏记录
     * @param array $data
     * @return bool|int|string
     */
    public function add($data = [])
    {
        if (empty($data)) {
            Log::error('添加收藏失败: 空信息异常');
            return false;
        }
        if (!is_array($data)) {
            Log::error('添加收藏失败: 参数类型异常' . var_export($data, true));
            return false;
        }
        if (empty($data['uid'])) {
            Log::error('添加收藏失败: 无用户id' . var_export($data, true));
            return false;
        }
        if (empty($data['type']) || empty($data['cid'])) {
            Log::error('添加收藏失败: 收藏信息缺失' . var_export($data, true));
            return false;
        }
        if (!in_array($data['type'], [self::TYPE_STRATEGY, self::TYPE_POST, self::TYPE_GAME])) {
            Log::error('添加收藏失败: 未定义收藏类型' . var_export($data, true));
            return false;
        }
        $rs = $this->insert($data, false, true);
        return $rs;
    }

}
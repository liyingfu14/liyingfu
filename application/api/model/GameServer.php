<?php
namespace app\api\model;

use think\Model;
use think\Db;
use think\Config ;

class GameServer extends Model
{
    const LIMIT_NUM = 8;    //开服显示条数
    protected $dateFormat = 'U';
    //根据游戏id获取游戏开服信息        0:已开服    1:未开服
    public function gameServerInfo($ide,$page,$long){
        if(empty($long)){
            $long = self::LIMIT_NUM;
        }
        $table = Config::get('database.prefix').'game';
        $tags = Config::get('database.prefix').'game_tags';
        $arr = array();
        if($ide == 0){
            $t = time();
            $total = $this->alias(' g ')
                ->join($table.' s ',' s.id=g.app_id')
                ->where('g.create_time','<',$t)
                ->field(' g.*,s.icon,s.name,s.type as gtype,s.device_ios_url,s.device_android_url,s.ios_size,s.android_size,s.android_mac_id,s.ios_mac_id' )
                ->order('g.create_time desc')
                ->page($page)
                ->paginate($long); 
            // foreach ($total as $v) {
            //     $tag = explode(',',$v->data['tags'])[0];        //获取标签首id
            //     $tag = $this->table($tags)->field('tag_name')->where('id',$tag)->find();
            //     $v['tag'] = $tag->tag_name;
            //     $arr[] = $v;
            // }
            foreach ($total as $gv) {
                $gameId = $gv->data['id'];
                $cate_name = $this->getCategory($gameId);
                if (sizeof($cate_name) != 0) {
                    $gv->data['cate_name'] = $cate_name[0];        //按二级分类排序取第一个
                }
                $arr[] = $gv;
            }
        }elseif($ide == 1){
            $t = time();
            $total = $this->alias(' g ')
                ->join($table.' s ',' s.id=g.app_id')
                ->where('g.create_time','>',$t)
                ->field(' g.*,s.icon,s.name,s.type as gtype,s.device_ios_url,s.device_android_url,s.ios_size,s.android_size,s.android_mac_id,s.ios_mac_id' )
                ->order('g.create_time desc')
                ->page($page)
                ->paginate($long);
            // foreach ($total as $v) {
            //     $tag = explode(',',$v->data['tags'])[0];        //获取标签首id
            //     $tag = $this->table($tags)->field('tag_name')->where('id',$tag)->find();
            //     $v['tag'] = $tag->tag_name;
            //     $arr[] = $v;
            // }
            foreach ($total as $gv) {
                $gameId = $gv->data['id'];
                $cate_name = $this->getCategory($gameId);
                if (sizeof($cate_name) != 0) {
                    $gv->data['cate_name'] = $cate_name[0];        //按二级分类排序取第一个
                }
                $arr[] = $gv;
            }
        }
        return $arr;
    }

    //游戏id获取分类
    protected function getCategory($gameId){
        $cates = array();
        $game = Config::get('database.prefix').'game';
        $category = Config::get('database.prefix').'game_category';
        $field = 'id,cate_name,game_id';
        $result = $this->table($category)->field($field)->where('pid','<>',0)->order('sort desc')->select();
        $arr = [];
        foreach ($result as $v) {
            $arr[] = $v->data;
        }
        foreach ($arr as $v2) {
            $arr2 = array();
            $arr2 = explode(',',$v2['game_id']);
            if(in_array($gameId,$arr2)){
                $cates[] = $v2['cate_name'];
            }
        }
        return $cates;
    }

}
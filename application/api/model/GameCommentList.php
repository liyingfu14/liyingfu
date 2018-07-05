<?php
namespace app\api\model;

use think\Model;
use think\Db;
use think\Config ;
use think\Log;

//查询用户评论信息
class GameCommentList extends Model
{
    const GET_NUM = 10 ; // 每页查询条数
    public function getUserScore($gameId,$page,$long,$uid){
        try{
            if(empty($long)){
                $long = self::GET_NUM;
            }
            $table = Config::get('database.prefix').'users';
            $total = $this->alias(' g ')
                ->join($table.' s ',' s.id=g.uid')
                ->field('  g.*,s.nickname,s.portrait  ')
                ->where('g.app_id',$gameId)
                ->order('g.comment_time desc')
                ->page($page)
                ->paginate($long);
            foreach ($total as $v) {
                if(empty($uid)){
                    $v->data['uid_likes'] = false;
                }else{
                    $arr = explode(',',$v->data['uid_likes']);
                    if(in_array($uid,$arr)){
                        $v->data['uid_likes'] = true;
                    }else{
                        $v->data['uid_likes'] = false;
                    }
                }
            }
            return $total;
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
    }

    public function userClick($commentId,$uid){
        // $total = $this->where('id', $commentId)->setInc('thumbs');
        $comment = $this->field('id,thumbs,uid_likes')->where('id',$commentId)->find();
        if (empty($comment->data['uid_likes'])) {
            $data = [
                'thumbs'    =>  $comment->data['thumbs'] + 1,
                'uid_likes' =>  $uid
            ];
        }else{
            $data = [
                'thumbs'    =>  $comment->data['thumbs'] + 1,
                'uid_likes' =>  $comment->data['uid_likes'].','.$uid
            ];
        }
        $result = $this->where('id',$commentId)->update($data);
        return $result;
    }
}
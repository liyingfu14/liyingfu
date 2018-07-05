<?php
/**
 * Created by TianQi.
 * User: weblinuxgame
 * Email: 994685563@qq.com
 * Date: 2018-05-04
 * Time: 15:11
 */

namespace app\common\model;


class LimitWord extends Base
{
    protected $table ='c_app_limit_word';
    const FIELDS_CACHE_TIME = 10 ;

    /**
     * 获取 敏感词汇
     * @param null $range
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLimitWord($range = null)
    {
        $self = new LimitWord();
        if(empty($range)){
            $result = $self->cache(true,self::FIELDS_CACHE_TIME)->field('word_list')->select();
        }
        else{
            $result = $self->cache(true,self::FIELDS_CACHE_TIME)->where(['range'=>$range])->field('word_list')->select();
        }
        if(empty($result)){
            return [];
        }
         $self->afterSelect($result);
        return $self->wordList($result) ;
    }

    protected function wordList($data)
    {
        if(empty($data)){
            return [];
        }
        if(!is_array($data)){
            $data = [$data];
        }
        $result = [];
        foreach ($data as $obj){
            if( $obj && !empty($obj->word_list)&& is_array($obj->word_list)){
                $result = array_merge($result,$obj->word_list);
            }
        }
        return $result ;
    }

    public function afterSelect(&$Sets = [])
    {
        if(empty($Sets)){
            return [];
        }

        if(!is_array($Sets)){
            return $this->afterFind($Sets);
        }
        foreach ($Sets as $word){
            if($word && !empty($word->word_list)){
                $this->afterFind($word);
            }
        }
    }

    public function afterFind(&$One = null)
    {
        if(empty($One)){
            return $One;
        }
        if($One && !empty($One->word_list)){
            $One->word_list = explode(',',$One->word_list);
        }
    }

}
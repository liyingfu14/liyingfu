<?php

namespace app\admin\model;

use think\Model;
use app\admin\model\GameCategory;
use app\admin\model\GameTags;
use app\common\lib\Utils;
use think\Log;
use think\Db;
use think\Config;
use think\Session;
use \think\Validate;
use app\common\lib\Fun;

class Game extends Model
{
    const IS_DELETE = 2;    //游戏是否删除 1为删除 2为正常
    const GAME_NUM = 8;     //游戏显示条数
    const LOWER_GAME = 1;       //下架游戏标识  1:下架 
    const UNPUBLISHED = 2;       //游戏状态     2:未发布
    const NORMAL = 3;       //游戏状态 3:正常

    const SELECTED = '精选';
    const HOT ='热门';
    const ORDINARY = '普通';
    const BEST_NEW = '最新';
    const BEST_HOT = '最热';

    const ADD_GAME = 1;
    const UPD_GAME = 2;
    const DOWN_GAME = 3;
    const UP_GAME = 4;
    const DEL_GAME = 5;

    //游戏列表
    public function gameList($game_status,$cate_id,$is_select,$up_time,$page){
        try{
            $n = self::GAME_NUM;        //条数
            $m = ($page-1)*$n;          //偏移量

            //游戏状态(搜索)
            $statusCon = '';
            if ($game_status == self::NORMAL) {
                $now_time = time();
                $statusCon = " and `status`={$game_status} and `create_time`<{$now_time}";
            }
            if ($game_status == self::LOWER_GAME) {
                $statusCon = " and `status`={$game_status}";
            }
            if ($game_status == self::UNPUBLISHED) {
                $now_time = time();
                $statusCon = " and `status`=".self::NORMAL." and `create_time`>{$now_time}";
            }
            
            //游戏分类(搜索)
            $cateCon = '';
            if(!empty($cate_id)){
                $category = Config::get('database.prefix').'game_category';
                $strGameId = $this->table($category)->where('id',$cate_id)->find()->data['game_id'];        //获取对应分类的game_id
                if (!empty($strGameId)) {
                    $cateCon = " and `id` in ({$strGameId})";
                }
            }

            //是否精选 热门 普通(搜索)
            $selectCon = '';
            if (!empty($is_select)) {
                $tags = Config::get('database.prefix').'game_tags';
                $strGameId = $this->table($tags)->where('id',$is_select)->find()->data['game_id'];      //获取对应标签(精选/热门/普通)的game_id
                if (!empty($strGameId)) {
                    $selectCon = " and `id` in ({$strGameId})";
                }
            }

            //上线时间(搜索)
            $timeCon = '';
            if (!empty($up_time)) {
                $timeCon = " and `create_time`>{$up_time}";
            }

            $field = 'id,name,icon,create_time,status';
            //is_delete 游戏是否删除 1为删除 2为正常
            // $result = $this->field($field)->where('is_delete',self::IS_DELETE)->paginate(self::GAME_NUM);
            $sql = "select {$field} from c_app_game where is_delete=".self::IS_DELETE.$statusCon.$cateCon.$selectCon.$timeCon." limit {$m},{$n}";
            $result = $this->query($sql);
            //统计结果
            $countSql = "select count(*) as sum from c_app_game where is_delete=".self::IS_DELETE.$statusCon.$cateCon.$selectCon.$timeCon;
            $count = $this->query($countSql)[0]['sum'];
            Session::set('page_sum',$count);
            $cateModel = new GameCategory();
            $tagsModel = new GameTags();
            $menuModel = new Menu();
            $arr = array();
            foreach ($result as $v) {
                $gameId = $v['id'];       //获取游戏ID
                //获取上线状态
                if ($v['status'] == 3) {
                    $v['status_c'] = '正常';
                }
                if($v['status'] == 1){
                    $v['status_c'] = '下架';
                }
                if ($v['create_time'] > time()) {
                    $v['status_c'] = '未发布';
                }
                //获取游戏分类
                $cate_name = $cateModel->getCategory($gameId);
                if (sizeof($cate_name) != 0) {
                    $v['cate_name'] = $cate_name[0];        //根据排序取第一个分类
                }
                if (sizeof($cate_name) == 0) {
                    $v['cate_name'] = '无';        //根据排序取第一个分类
                }

                //获取是否精选,热门,普通
                $isSelect = $tagsModel->getIsHotOrIsSelect($gameId);
                if (sizeof($isSelect) != 0) {
                    $v['isSelect'] = $isSelect[0];        //根据排序取第一个分类
                }
                if (sizeof($isSelect) == 0) {
                    $v['isSelect'] = '无';        //根据排序取第一个分类
                }

                //是否首页
                $isMenu = $menuModel->getIsMenu($gameId);
                $menuName = $menuModel->getMenuName($gameId);
                if (sizeof($menuName) != 0) {
                    $v['menuName'] = $menuName[0];        //根据排序取第一个分类
                }
                if (sizeof($menuName) == 0) {
                    $v['menuName'] = '否';        //根据排序取第一个分类
                }
                //获取该款游戏标签
                $tags = $tagsModel->getGameTags($gameId);
                if (sizeof($tags) != 0) {
                    $v['tags'] = implode(',',$tags);        //根据排序取第一个分类
                }
                if (sizeof($tags) == 0) {
                    $v['tags'] = '无';        //根据排序取第一个分类
                }
                $arr[] = $v;
            }
            return $arr;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4504,'获取游戏列表失败');
    }

    //游戏删除
    public function delGame($gameId){
        try{
            $result = $this->where('id',$gameId)->update(['is_delete'=>1]);
            if($result){
                Fun::logWriter(self::DEL_GAME,$gameId);
                return true;
            }else{
                return Utils::error(4536,'删除游戏失败');
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4535,'删除游戏失败');
    }

    //游戏下架
    public function lowerFrameGame($gameId){ 
        try{
            $use = $this->where('id',$gameId)->find();
            $status = $use->status;
            if ($status == self::NORMAL) {
                $result = $this->where('id',$gameId)->update(['status'=>self::LOWER_GAME]);
            }elseif ($status == self::LOWER_GAME) {
                $result = $this->where('id',$gameId)->update(['status'=>self::NORMAL]);
            }

            if($result){
                if($status == self::NORMAL){
                    Fun::logWriter(self::DOWN_GAME,$gameId);
                }elseif ($status == self::LOWER_GAME){
                    Fun::logWriter(self::UP_GAME,$gameId);
                }
                return true;
            }else{
                return Utils::error(4539,'下架游戏失败');
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4540,'下架游戏失败');
    }

    //添加游戏
    public function addGame($param){
        try{
            $rule = [
                'name'  =>  'require',
                'device_android_url'  =>  'require',
                'device_ios_url'  =>  'require',
                'android_mac_id' =>  'require',
                'ios_mac_id'   =>  'require',
//                'ios_bundle_id'   =>  'require',
                'advertisement'   =>  'require',
                'introduction'   =>  'require',
                'ios_size'   =>  'require',
                'android_size'   =>  'require',
                'ios_version'   =>  'require',
                'android_version'   =>  'require',
                'download'   =>  'require',
                'extend_pic'   =>  'require',
                'icon'   =>  'require',
                'backgroup'   =>  'require',
                'check_parent_cate'   =>  'require|number',
                'check_child_cate'   =>  'require|number',
                'check_is_select'   =>  'require|number',
                'score'   =>  'require',
            ];
            $msg = [
                'name.require' => '游戏名必须',
                'device_android_url.require' => '安卓游戏包链接必须',
                'device_ios_url.require' => 'ios游戏包链接必须',
                'android_mac_id.require' => 'Android包名必须',
                'ios_mac_id.require' => 'IOS包名必须',
//                'ios_bundle_id.require' => 'bundle ID必须',
                'advertisement.require' => '一句话描述必须',
                'introduction.require' => '游戏描述必须',
                'ios_size.require' => 'ios包大小必须',
                // 'ios_size.number' => 'ios包大小必须是数字',
                'android_size.require' => 'Android包大小必须',
                // 'android_size.number' => 'Android包大小必须是数字',
                'ios_version.require' => 'ios游戏版本必须',
                'android_version.require' => 'Android游戏版本必须',
                'download.require' => '下载次数必须',
                'extend_pic.require' => '游戏截图必须',
                'icon.require' => 'icon必须',
                'backgroup.require' => '背景图必须',
                'check_parent_cate.require' => '分类必须',
                'check_parent_cate.number' => '一级分类参数异常',
                'check_child_cate.number' => '二级分类参数异常',
                'check_is_select.require' => '是否精选必须',
                'check_is_select.number' => '是否精选参数异常',
                'score.require' => '评分必须',
            ];
            $data = [
                'name'  =>  $param['name'],
                'device_android_url'  =>  $param['device_android_url'],
                'device_ios_url'  =>  $param['device_ios_url'],
                'android_mac_id'  =>  $param['android_mac_id'],
                'ios_mac_id'  =>  $param['ios_mac_id'],
                'ios_bundle_id'  =>  $param['ios_bundle_id'],
                'advertisement'  =>  $param['advertisement'],
                'introduction'  =>  $param['introduction'],
                'ios_size'  =>  $param['ios_size'],
                'android_size'  =>  $param['android_size'],
                'ios_version'  =>  $param['ios_version'],
                'android_version'  =>  $param['android_version'],
                'sort'  =>  $param['sort'],
                'download'  =>  $param['download'],
                'create_time'   =>  time(),
                'extend_pic' => $param['extend_pic'],
                'icon' => $param['icon'],
                'backgroup' => $param['backgroup'],
                'check_parent_cate'   =>  $param['parent_cate'],
                'check_child_cate'   =>  $param['child_cate'],
                'check_is_select' =>  $param['is_select'],
                'score' => $param['score'],
            ];
            $gameData = [
                'name'  =>  $param['name'],
                'device_android_url'  =>  $param['device_android_url'],
                'device_ios_url'  =>  $param['device_ios_url'],
                'android_mac_id'  =>  $param['android_mac_id'],
                'ios_mac_id'  =>  $param['ios_mac_id'],
                'ios_bundle_id'  =>  $param['ios_bundle_id'],
                'advertisement'  =>  $param['advertisement'],
                'introduction'  =>  $param['introduction'],
                'ios_size'  =>  $param['ios_size'],
                'android_size'  =>  $param['android_size'],
                'ios_version'  =>  $param['ios_version'],
                'android_version'  =>  $param['android_version'],
                'sort'  =>  $param['sort'],
                'download'  =>  $param['download'],
                'create_time'   =>  time(),
                'extend_pic' => $param['extend_pic'],
                'icon' => $param['icon'],
                'backgroup' => $param['backgroup'],
                'score' => $param['score'],
                'ios_page_url'  =>  $param['ios_page_url']
            ];
            if (!empty($param['pushTime'])) {
                $gameData['create_time'] = strtotime($param['pushTime']);
            }
            $validate = new Validate($rule, $msg);
            $result   = $validate->check($data);        //验证
            if ($result) {
                $gameResultId = $this->insertGetId($gameData);     //添加游戏入库,返回主键ID值
                $parent_cate = $param['parent_cate'];
                $child_cate = $param['child_cate'];
                $addParentChildCategoryResult = $this->addParentChildCategory($gameResultId,$parent_cate,$child_cate);
                //游戏标签入库
                $tags = $param['tags'];
                $tagResult = $this->newGameAddTags($gameResultId,$tags);        //成功true 失败false
                //精选 热门 普通入库
                $is_select = $param['is_select'];
                $addIsSelect = $this->addSelectHot($gameResultId,$is_select);
                //是否首页
                $is_menu = $param['is_menu'];
                $addIsMenu = $this->addMenu($gameResultId,$is_menu); 
                //是否最新最热
                $hot_new = $param['hot_new'];
                $addHotNew = $this->addHotOrNew($gameResultId,$hot_new);
                if( $gameResultId && $addParentChildCategoryResult && $tagResult && $addIsSelect && $addIsMenu && $addHotNew ){
                    Fun::logWriter(self::ADD_GAME,$gameResultId);
                    return true;
                }else{
                    return Utils::error(4512,'添加失败');
                }
            }else{
                return $validate->getError();
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4542,'添加游戏失败');
    }

    //添加游戏标签入库
    protected function newGameAddTags($gameResultId,$tags){
        try{
            $tags = explode(',',$tags);
            $gameTag = Config::get('database.prefix').'game_tags';
            foreach ($tags as $key => $tag) {
                $sign = $this->table($gameTag)->where('tag_name',$tag)->find();     //是否已存在该标签
                if ($sign) {
                    $tagGameId = $sign->data['id'];
                    $tagGameIds = $sign->data['game_id'];
                    if (empty($tagGameIds)) {
                        $tagGameIds = $gameResultId;
                    }else{
                        $tagGameIds = $tagGameIds.','.$gameResultId;     //获取对应顶级分类ID列表,把新增游戏ID加入
                    }
                    $tagData = [
                        'update_at' =>  time(),
                        'game_id'   =>  $tagGameIds
                    ];
                    $tagsResult = $this->table($gameTag)->where('id',$tagGameId)->update($tagData);
                    if ($tagsResult == false) {
                       return false;
                    }
                }else{
                    $tagData = [
                        'tag_name' => $tag,
                        'create_at' =>  time(),
                        'game_id'   =>  $gameResultId
                    ];
                    $tagsResult = $this->table($gameTag)->insert($tagData);
                    if ($tagsResult == false) {
                        return false;
                    }
                }
            }
            // $tagsGame = $this->table($gameTag)->select();
            return true;

        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4543,'添加标签入库失败');
    }

    //游戏精选 热门 普通入库
    public function addSelectHot($gameResultId,$is_select){
        try{
            $gameTag = Config::get('database.prefix').'game_tags';
            $tagResult = $this->table($gameTag)->where('id',$is_select)->find();
            $gameId = $tagResult->data['game_id'];
            
            if (empty($gameId)) {
                $gameId = $gameResultId;
            }else{
                $gameId = $gameId.','.$gameResultId;     //获取对应,把新增游戏ID加入
            }

            $tagData = [
                'update_at' =>  time(),
                'game_id'   =>  $gameId
            ];
            $selectResult = $this->table($gameTag)->where('id',$is_select)->update($tagData);
            return $selectResult;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4544,'精选热门普通入库失败');

    }

    //获取编辑游戏数据
    public function editGame($id){
        try{
            $result = $this->where('id',$id)->find();
            return $result;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4546,'获取编辑数据失败');
    }

    //编辑游戏处理
    public function updGame($param){
        try{
            $gameId =$param['gameId'];
            $extend_pic = $param['extend_pic'];
            $icon = $param['icon'];
            $backgroup = $param['backgroup'];
            $parent_cate = $param['parent_cate'];
            $child_cate = $param['child_cate'];
            $tags = $param['tags'];
            $old_tags = $param['old_tags'];
            $is_select = $param['is_select'];
            $old_select = $param['old_select'];
            $is_menu = $param['is_menu'];
            $old_menu = $param['old_menu'];
            $old_hot_new = $param['old_hot_new'];
            $hot_new = $param['hot_new'];
            $pushTime = strtotime($param['pushTime']);
            // dump($pushTime);
            $gameData = [
                'name'  =>  $param['name'],
                'device_android_url'  =>  $param['device_android_url'],
                'device_ios_url'  =>  $param['device_ios_url'],
                'android_mac_id'  =>  $param['android_mac_id'],
                'ios_mac_id'  =>  $param['ios_mac_id'],
                'ios_bundle_id'  =>  $param['ios_bundle_id'],
                'advertisement'  =>  $param['advertisement'],
                'introduction'  =>  $param['introduction'],
                'ios_size'  =>  $param['ios_size'],
                'android_size'  =>  $param['android_size'],
                'ios_version'  =>  $param['ios_version'],
                'android_version'  =>  $param['android_version'],
                'sort'  =>  $param['sort'],
                'download'  =>  $param['download'],
                'update_time'   =>  time(),
                'create_time'  =>  $pushTime,
                'ios_page_url'  =>  $param['ios_page_url'],
                'score' =>  $param['score']
            ];
            if (!empty($extend_pic)) {
                $extend_pic_result = $this->where('id',$gameId)->update(['extend_pic'=>$extend_pic]);
            }
            if (!empty($icon)) {
                $icon_result = $this->where('id',$gameId)->update(['icon'=>$icon]);
            }
            if (!empty($backgroup)) {
                $backgroup_result = $this->where('id',$gameId)->update(['backgroup'=>$backgroup]);
            }

            $gameResult = $this->where('id',$gameId)->update($gameData);     //编辑游戏入库
            //一级分类剔除
            $delParentCategoryResult = $this->delParentCategory($gameId);

            //二级分类剔除
            $delChildCategoryResult = $this->delChildCategory($gameId,$delParentCategoryResult);

            //添加到一级和二级分类
            $addParentChildCategoryResult = $this->addParentChildCategory($gameId,$parent_cate,$child_cate);

            //剔除标签 加入更新标签
            if ($old_tags != $tags) {
                $delTagsResult = $this->delTags($gameId,$old_tags);
                $addTagsResult = $this->newGameAddTags($gameId,$tags);
            }

            //剔除旧精选热门普通 加入精选热门普通
            $resetIsSelectResult = $this->resetIsSelect($gameId,$is_select,$old_select);

            //剔除菜单,重新加入
            $resetIsMenuResult = $this->resetIsMenu($gameId,$is_menu,$old_menu);

            //剔除最新最热
            if (!empty($old_hot_new)) {
                $removeHotNewResult = $this->removeHotNew($gameId,$old_hot_new);
            }

            //加入最新最热
            if (!empty($hot_new)) {
                $addHotNewResult = $this->addHotOrNew($gameId,$hot_new);
            }
            

            if ($gameResult && $delParentCategoryResult && $delChildCategoryResult && $addParentChildCategoryResult && $resetIsMenuResult && $removeHotNewResult && $resetIsMenuResult) {
                Fun::logWriter(self::UPD_GAME,$gameId);
                return true;
            }else{
                return Utils::error(4553,'编辑失败');
            }
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4548,'编辑游戏失败');
    }

    //一级分类剔除 返回Id
    protected function delParentCategory($gameId){
        try{
            $cate = Config::get('database.prefix').'game_category';
            $field = 'id,game_id';
            $result = $this->table($cate)->field($field)->where('pid',0)->select();
            $arr = array();
            foreach ($result as $v) {      
                $arrGameId = explode(',',$v->data['game_id']);
                
                $newGameId = array();
                foreach ($arrGameId as $key => $value) {
                    if ($value != $gameId) {
                        $newGameId[] = $value;
                    }else{
                        $arr[] = $v->data['id'];
                    }
                }
                $newGameId = implode(',',$newGameId);
                $gameIdResult = $this->table($cate)->where('id',$v->data['id'])->update(['game_id'=>$newGameId]);
            }

            return $arr;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4549,'剔除一级分类');
    }

    //二级分类剔除
    protected function delChildCategory($gameId,$delParentCategoryResult){
        try{
            $cate = Config::get('database.prefix').'game_category';
            $field = 'id,game_id';
            foreach ($delParentCategoryResult as $k => $v1) {
                $result = $this->table($cate)->field($field)->where('pid',$v1)->select();
                foreach ($result as $v) {      
                    $arrGameId = explode(',',$v->data['game_id']);
                    
                    $newGameId = array();
                    foreach ($arrGameId as $key => $value) {
                        if ($value != $gameId) {
                            $newGameId[] = $value;
                        }else{
                            $arr[] = $v->data['id'];
                        }
                    }
                    $newGameId = implode(',',$newGameId);
                    $gameIdResult = $this->table($cate)->where('id',$v->data['id'])->update(['game_id'=>$newGameId]);
                }
            }
            return true;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4550,'剔除二级分类');
    }


    //添加一级和二级分类
    protected function addParentChildCategory($gameResultId,$parent_cate,$child_cate){
        try{
            $cate = Config::get('database.prefix').'game_category';
            $field = 'id,game_id';
    
            //一级分类game_id 字段处理
            $parentCateGame = $this->table($cate)->field($field)->where('id',$parent_cate)->find(); 
            $parentCateGameId = $parentCateGame->data['id'];        //该条数据ID  
            $parentCateGameIds = $parentCateGame->data['game_id'];    
            if (empty($parentCateGameIds)) {
                $parentCateGameIds = $gameResultId;
            }else{
                $parentCateGameIds = $parentCateGameIds.','.$gameResultId;     //获取对应顶级分类ID列表,把新增游戏ID加入
            }
            $parentCateResult = $this->table($cate)->where('id',$parentCateGameId)->update(['game_id'=>$parentCateGameIds]);
    
            //二级分类game_id 字段处理
            $childCateGame = $this->table($cate)->field($field)->where('id',$child_cate)->find(); 
            $childCateGameId = $childCateGame->data['id'];        //该条数据ID  
            $childCateGameIds = $childCateGame->data['game_id'];    
            if (empty($childCateGameIds)) {
                $childCateGameIds = $gameResultId;
            }else{
                $childCateGameIds = $childCateGameIds.','.$gameResultId;     //获取对应顶级分类ID列表,把新增游戏ID加入
            }
            $childCateResult = $this->table($cate)->where('id',$childCateGameId)->update(['game_id'=>$childCateGameIds]);

            return true;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4551,'添加一二级分类失败');
    }

    //剔除旧标签
    protected function delTags($gameId,$old_tags){
        try{
            $tag = Config::get('database.prefix').'game_tags';
            $field = 'id,tag_name,game_id';
            $result = $this->table($tag)
                ->field($field)
                ->where('tag_name','<>',self::SELECTED)
                ->where('tag_name','<>',self::HOT)
                ->where('tag_name','<>',self::ORDINARY)
                ->where('tag_name','<>',self::BEST_NEW)
                ->where('tag_name','<>',self::BEST_HOT)
                ->select();         //排除精选,热门,普通标签

            foreach ($result as $v) {      
                $arrGameId = explode(',',$v->data['game_id']);
                
                $newGameId = array();
                foreach ($arrGameId as $key => $value) {
                    if ($value != $gameId) {
                        $newGameId[] = $value;
                    }else{
                        $arr[] = $v->data['id'];
                    }
                }
                $newGameId = implode(',',$newGameId);
                $gameIdResult = $this->table($tag)->where('id',$v->data['id'])->update(['game_id'=>$newGameId]);
            }

        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4552,'剔除标签失败');
    }

    //重置 推荐 精选 普通
    protected function resetIsSelect($gameId,$is_select,$old_select){
        try{
            $tag = Config::get('database.prefix').'game_tags';
            $field = 'id,tag_name,game_id';
            $result = $this->table($tag)
                ->field($field)
                ->where('tag_name',self::SELECTED)
                ->whereOr('tag_name',self::HOT)
                ->whereOr('tag_name',self::ORDINARY)
                ->select();   
            //剔除
            foreach ($result as $v) {      
                $arrGameId = explode(',',$v->data['game_id']);
                
                $newGameId = array();
                foreach ($arrGameId as $key => $value) {
                    if ($value != $gameId) {
                        $newGameId[] = $value;
                    }else{
                        $arr[] = $v->data['id'];
                    }
                }
                $newGameId = implode(',',$newGameId);
                $gameIdResult = $this->table($tag)->where('id',$v->data['id'])->update(['game_id'=>$newGameId]);
            }  

            //加入
            $tagGame = $this->table($tag)->field($field)->where('id',$is_select)->find(); 
            $tagGameId = $tagGame->data['id'];        //该条数据ID  
            $tagGameIds = $tagGame->data['game_id'];    
            if (empty($tagGameIds)) {
                $tagGameIds = $gameId;
            }else{
                $tagGameIds = $tagGameIds.','.$gameId;     //获取对应顶级分类ID列表,把新增游戏ID加入
            }
            $tagGameResult = $this->table($tag)->where('id',$is_select)->update(['game_id'=>$tagGameIds]);
            return true;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4552,'剔除标签失败');
    }

    //加入首页
    protected function addMenu($gameResultId,$is_menu){
        try{
            $menu = Config::get('database.prefix').'menu';
            $menus = $this->table($menu)->where('id',$is_menu)->find();
            $menuGameIds = $menus->data['game_id'];
            if (empty($menuGameIds)) {
                $menuGameIds = $gameResultId;
            }else{
                $menuGameIds = $menuGameIds.','.$gameResultId;     //获取对应顶级分类ID列表,把新增游戏ID加入
            }
            $menuResult = $this->table($menu)->where('id',$is_menu)->update(['game_id'=>$menuGameIds]);
            return $menuResult;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4556,'加入菜单失败');
    }

    //加入最新或最热
    protected function addHotOrNew($gameResultId,$hot_new){
        try{
            $tagsModel = Config::get('database.prefix').'game_tags';
            $tags = $this->table($tagsModel)->where('id',$hot_new)->find();
            $tagsGameIds = $tags->data['game_id'];
            if (empty($tagsGameIds)) {
                $tagsGameIds = $gameResultId;
            }else{
                $tagsGameIds = $tagsGameIds.','.$gameResultId;     //获取对应顶级分类ID列表,把新增游戏ID加入
            }
            $tagsResult = $this->table($tagsModel)->where('id',$hot_new)->update(['game_id'=>$tagsGameIds]);
            return $tagsResult;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4568,'加入最新最热失败');
    }

    //重置首页菜单game_id
    protected function resetIsMenu($gameId,$is_menu,$old_menu){
        try{
            $menu = Config::get('database.prefix').'menu';
            $field = 'id,menu_name,game_id';
            $result = $this->table($menu)->field($field)->select();   
            //剔除
            foreach ($result as $v) {      
                $arrGameId = explode(',',$v->data['game_id']);
                
                $newGameId = array();
                foreach ($arrGameId as $key => $value) {
                    if ($value != $gameId) {
                        $newGameId[] = $value;
                    }else{
                        $arr[] = $v->data['id'];
                    }
                }
                $newGameId = implode(',',$newGameId);
                $gameIdResult = $this->table($menu)->where('id',$v->data['id'])->update(['game_id'=>$newGameId]);
            }  

            //加入
            $menuGame = $this->table($menu)->field($field)->where('id',$is_menu)->find(); 
            $menuGameId = $menuGame->data['id'];        //该条数据ID  
            $menuGameIds = $menuGame->data['game_id'];    
            if (empty($menuGameIds)) {
                $menuGameIds = $gameId;
            }else{
                $menuGameIds = $menuGameIds.','.$gameId;     //获取对应顶级分类ID列表,把新增游戏ID加入
            }
            $menuGameResult = $this->table($menu)->where('id',$is_menu)->update(['game_id'=>$menuGameIds]);
            return true;
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4557,'重置菜单game_id');
    }

    //获取所有游戏
    public function getAllGame(){
        try{
            $field = 'id,name';
            return $this->field($field)->select();
        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4560,'获取所有游戏失败');
    }

    //剔除最新最热
    public function removeHotNew($gameId,$old_hot_new){
        try{
            $tag = Config::get('database.prefix').'game_tags';
            $field = 'id,tag_name,game_id';
            $result = $this->table($tag)
                ->field($field)
                ->where('tag_name',self::BEST_NEW)
                ->whereOr('tag_name',self::BEST_HOT)
                ->select();         

            foreach ($result as $v) {      
                $arrGameId = explode(',',$v->data['game_id']);
                
                $newGameId = array();
                foreach ($arrGameId as $key => $value) {
                    if ($value != $gameId) {
                        $newGameId[] = $value;
                    }else{
                        $arr[] = $v->data['id'];
                    }
                }
                $newGameId = implode(',',$newGameId);
                $gameIdResult = $this->table($tag)->where('id',$v->data['id'])->update(['game_id'=>$newGameId]);
            }

        }catch (\Exception $e) {
            Log::error(Utils::exportError($e));
        }
        return Utils::error(4570,'剔除最新最热失败');
    }

}

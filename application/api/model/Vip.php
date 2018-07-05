<?php
/**
 * Created by TianQi.
 * User: weblinuxgame
 * Email: 994685563@qq.com
 * Date: 2018-05-04
 * Time: 17:12
 */

namespace app\api\model;


use app\common\lib\Utils;
use app\common\model\Base;
use think\Log;

class Vip extends  Base
{
    protected $table = 'c_app_vip_users';

    protected $vip_info_table = 'c_app_vip_info';

    protected $_alias = 'v';
    protected $_alias_info = 'i';

    protected $area = [
         'info' => [
              'vip_score','vid','exp'
         ],
        'left_join' => [
            'v.vip_score','v.exp','i.name','i.icon'
        ]
    ];

    public function getVipUserInfo($uid)
    {
        try {
            $data = $this->alias($this->_alias)
                    ->join($this->vip_info_table.' '.$this->_alias_info,$this->_alias.'.vid='.$this->_alias_info.'.id','LEFT')
                    ->field(implode(',',$this->area['left_join']))
                    ->where([$this->_alias.'.uid'=>$uid])
                    ->find();
            if(empty($data)){
                return NullClass();
            }
            return $data ;
        } catch (\Exception $e) {
            Log::error($this->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return NullClass();
    }
}
<?php
namespace app\common\model;

use app\common\controller\BaseApi;
use think\Model;

class Rbac extends Model
{
    public function checkRbac(BaseApi &$Api)
    {
        return true ;
    }
}
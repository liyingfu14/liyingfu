<?php

namespace app\api\controller;

use app\common\controller\BaseApi;
use think\Request;
use app\common\lib\Utils ;
use think\Log ;
use app\api\model\Menu;

//APP首页
class Index extends BaseApi
{
    public function showMenu(Request $request){
        try{
            $menuModel = new Menu();
            $data = $menuModel -> getMenu();
            if($data){
                return $this->response(Utils::success($data,'获取菜单成功'));
            }else{
                return $this->response(Utils::error(4015,'获取菜单失败'));
            }
        }catch(\Exception $e){
            Log::error($e->getMessage());
        }
    }
}
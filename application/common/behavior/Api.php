<?php

namespace app\common\behavior;

use app\common\controller\BaseApi;
use app\common\model\SystemLog;
use app\common\model\Access;
use app\common\model\Rbac;
use app\common\model\Auth;
use think\Console;
use think\Log;
use think\Db;

class Api
{
    public function run(&$params)
    {
        dump('run');
    }

    public function appInit(&$params)
    {
        return;
    }

    public function actionBegin(&$params)
    {
       if($params instanceof  BaseApi){
            return $params->Auth->checkAuth($params);
       }
       return true;
    }

    public function appEnd(&$params)
    {
        return;
    }

    public function actionEnd(&$params)
    {
        $context = $params['context'];
        if (empty($context) || !($context instanceof BaseApi)) {
            return ;
        }
        $context->response = array('header'=>$context->getHeader(),'data' => $params['data'], 'type' => $params['type'], 'code' => $params['code']);
        $model = new SystemLog();
        try{
            $model->add($context->request, $context->response);
        }catch (\Exception $e){
           Log::error('file :'.$e->getFile().' at line : '.$e->getLine().' msg :'.$e->getMessage());
        }
    }
}
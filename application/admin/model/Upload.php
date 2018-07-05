<?php

namespace app\admin\model;

use think\Model;
use app\common\lib\Utils;
use think\Log;
use think\Config;

class Upload extends Model
{
    public function uploadToken()
    {
        $login_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjE0LCJhZ2VudCI6ImRlZmF1bHQiLCJ0aW1lIjoxNTI2MjgxNDI2LCJleHBpcmUiOjE1NTg2ODE0MjYsInNjb3BlIjoiYXBwIn0.bKDjnqI3XjPo1uv7WkWHC-TQM8OxucueafeJOMT7BAc';
        $api = 'http://upload.com/index.php/api/upload/token';
        $heaher = ['x-app'=>'4cgame_app','x-token'=>$login_token,'Content-Type'=>'application/json;charset=utf-8'];
        $http = Utils::brower() ;
        $data = ['type'=>'image'];
        $rs = $http->post($api,$heaher,json_encode($data));
        dump($rs->getContent());
    }
}
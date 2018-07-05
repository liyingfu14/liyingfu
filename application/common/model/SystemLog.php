<?php
namespace app\common\model;

use think\Log;
use think\Model;
use think\Request;
use app\common\lib\Utils;

/**
 * 系统 访问日志 表
 * Class SystemLog
 * @package app\common\model
 */
class SystemLog extends Model
{
    public $pk = 'id';


    public function add(Request $request,$response)
    {
        try{
            $data = array(
                'method'=> $request->method(),
                'protocol'=> $request->protocol(),
                'body'=> json_encode($request->param()),
                'header'=> json_encode($request->header()),
                'device'=> json_encode($request->header('user-agent')),
                'ip'=> $request->ip(),
                'create_at'=> time(),
                'uri'=> $request->pathinfo(),
                'response'=> json_encode($response['data']),
                'response_header'=> json_encode($response['header']),
                'status_code'=> $response['code']
            );
            return $this->data($data)->save();
        }catch (\Exception $e){
            Log::error($this->getLastSql());
            Log::error(Utils::exportError($e));
        }
        return -1;
    }

    public function search()
    {

    }

}
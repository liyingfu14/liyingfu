<?php
/**
 * App 信息接口
 * User: Administrator
 * Date: 2018-04-28
 * Time: 19:22
 */

namespace app\api\controller;

use app\common\controller\BaseApi;
use app\api\model\Appinfo;
use app\common\lib\Utils;
use app\common\lib\Sms;
use think\Config;

class App extends BaseApi
{
    // 获取APP版本信息
    public function info()
    {
        $model = new Appinfo();
        $data = $model->getVersion();
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response(Utils::success($data));
    }

    /**
     * 短信发送接口
     * @param $mobile
     * @param $type
     * @return $this|\think\Response
     */
    public function sendSms($mobile, $type)
    {
        $app_name = $this->request->header('x-app');

        if (empty($app_name) || $app_name !== Config::get('app_name')) {
            return $this->response(Utils::error(1040, '天启流控'));
        }
        if (!Sms::isMobile($mobile)) {
            return $this->response(Utils::error(1041, '手机号不正确'));
        }
        if (!Sms::smsType($type)) {
            return $this->response(Utils::error(1042, '未定义短信类型'));
        }
        if (Sms::limitRate($mobile)) {
            return $this->response(Utils::error(1043, '天启流控,同一手机号3分钟内发送短信数量受限'));
        }
        if (Sms::send($mobile, $type)) {
            return $this->response(['success' => 1], '发送成功,请注意查看短信');
        }
        return $this->response(1103, Sms::error());
    }

    //推送
    // public function mobPush(){

    //     $http = Utils::brower();
    //     $config = Config::load(CONF_PATH.'push'.DS.'config.php','push');
    //     // $data = [];

    //     // $api = $config['api'];
    //     // $result = $http->post($api,[],$data);
    //     $url = 'http://api.push.mob.com/push';
    //     $data = [
    //         'androidContent'    =>  ["第一行","第二行"],
    //         "androidTitle"      =>  "PUSHDEMO",
    //         "androidstyle"      =>  3,
    //         "appkey"            =>  "moba6b6c6d6",
    //         "content"           =>  "收箱模式的DEMO33",
    //         "plats"             =>  [1],
    //         "registrationIds"   =>  ["5a17d35b6bebf533e380bc91"],
    //         "target"            =>  4,
    //         "type"              =>  1,
    //         "unlineTime"        =>  1
    //     ];


    //     $data = json_encode($data).$config['APPSECRET'];
    //     $sign = md5($data);
    //     $httpHeader = [
    //         'key'   =>  $config['APPKEY'],
    //         'sign'  =>  $sign
    //     ];
    //     $result = $http -> post($url,$httpHeader,$data);
    //     dump($result);
    // }

    // 关于我们
    public function aboutUs()
    {
        $about = new Appinfo();
        $data = $about->aboutUs();
        if (Utils::isError($data)) {
            return $this->response($data);
        }

        return $this->response(Utils::success($data));
    }

}
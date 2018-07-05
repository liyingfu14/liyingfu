<?php

namespace app\libs;

ini_set("display_errors", "on");
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

require_once __DIR__ . DS . 'Aliyun' . DS . 'vendor' . DS . 'autoload.php';
require_once __DIR__ . DS . 'bootstrap.php';

use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\SendBatchSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;

// 加载区域结点配置
Config::load();

/**
 * Class SmsDemo
 *
 * Created on 17/10/17.
 * 短信服务API产品的DEMO程序,工程中包含了一个SmsDemo类，直接通过
 * 执行此文件即可体验语音服务产品API功能(只需要将AK替换成开通了云通信-短信服务产品功能的AK即可)
 * 备注:Demo工程编码采用UTF-8
 */
class Sms
{

    static $acsClient = null;
    private $config = [];
    private static $instance;

    private function __construct($config = [])
    {
        $this->config = $config;
    }

    public static function Instance($config = [])
    {
        if (empty(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * 取得AcsClient
     *
     * @return DefaultAcsClient|bool
     */
    public function getAcsClient()
    {
        //获取阿里云配置信息
        $yunconfig = [];

        if (empty($yunconfig)) {
            $yunconfig = self::Instance()->config;
        }

        if (empty($yunconfig)) {
            return false;
        }

        $accessKeyId = $yunconfig['APPKEY'];

        $accessKeySecret = $yunconfig['APPSECRET'];

        //短信API产品名（短信产品名固定，无需修改）
        $product = "Dysmsapi";

        //短信API产品域名（接口地址固定，无需修改）
        $domain = "dysmsapi.aliyuncs.com";

        //暂时不支持多Region（目前仅支持cn-hangzhou请勿修改）
        $region = "cn-hangzhou";

        // 初始化用户Profile实例
        $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

        // 增加服务结点
        DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);

        // 初始化AcsClient用于发起请求
        $acsClient = new DefaultAcsClient($profile);

        return $acsClient;
    }

    /**
     * 发送短信
     * @param $mobile
     * @param $code
     * @param $type
     * @param string $product
     * @return array | boolean
     */
    public  function sendSms($mobile, $code, $type, $product = '')
    {

        // 配置
        $yunconfig = self::Instance()->config;
        //
        if (empty($product)) {
            $product = "Dysmsapi";
        }
        // 发送 短信 内容
        $content = [
            'code' => "$code",
            "product" => $product
        ];
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();
        // 必填，设置短信接收号码
        $request->setPhoneNumbers($mobile);    //$moblie是我前台传入的电话
        // 必填，设置签名名称
        $request->setSignName($yunconfig['SIGNNAME']);      //此处需要填写你在阿里上创建的签名
        // 必填，设置模板CODE
        $_type = $yunconfig['types'];
        $request->setTemplateCode($yunconfig[$_type[$type]]);
        //短信模板编号
        $request->setTemplateParam(json_encode($content));
        //发起访问请求
        $acsResponse = self::Instance()->getAcsClient()->getAcsResponse($request);

        //返回请求结果
        $result = json_decode(json_encode($acsResponse), true);
        if ($result == true) {
            $data['code'] = 0;
            $data['msg'] = '发送成功';
        } else {
            $data['code'] = -2;
            $data['msg'] = '发送失败';
        }
        return $data;
    }

    /**
     * 批量发送短信
     * @return stdClass
     */
    public static function sendBatchSms()
    {

        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendBatchSmsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        // 必填:待发送手机号。支持JSON格式的批量调用，批量上限为100个手机号码,批量调用相对于单条调用及时性稍有延迟,验证码类型的短信推荐使用单条调用的方式
        $request->setPhoneNumberJson(json_encode(array(
            "1500000000",
            "1500000001",
        ), JSON_UNESCAPED_UNICODE));

        // 必填:短信签名-支持不同的号码发送不同的短信签名
        $request->setSignNameJson(json_encode(array(
            "云通信",
            "云通信"
        ), JSON_UNESCAPED_UNICODE));

        // 必填:短信模板-可在短信控制台中找到
        $request->setTemplateCode("SMS_1000000");

        // 必填:模板中的变量替换JSON串,如模板内容为"亲爱的${name},您的验证码为${code}"时,此处的值为
        // 友情提示:如果JSON中需要带换行符,请参照标准的JSON协议对换行符的要求,比如短信内容中包含\r\n的情况在JSON中需要表示成\\r\\n,否则会导致JSON在服务端解析失败
        $request->setTemplateParamJson(json_encode(array(
            array(
                "name" => "Tom",
                "code" => "123",
            ),
            array(
                "name" => "Jack",
                "code" => "456",
            ),
        ), JSON_UNESCAPED_UNICODE));

        // 可选-上行短信扩展码(扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段)
        // $request->setSmsUpExtendCodeJson("[\"90997\",\"90998\"]");

        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);

        return $acsResponse;
    }

    /**
     * 短信发送记录查询
     * @return stdClass
     */
    public static function querySendDetails()
    {

        // 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
        $request = new QuerySendDetailsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        // 必填，短信接收号码
        $request->setPhoneNumber("12345678901");

        // 必填，短信发送日期，格式Ymd，支持近30天记录查询
        $request->setSendDate("20170718");

        // 必填，分页大小
        $request->setPageSize(10);

        // 必填，当前页码
        $request->setCurrentPage(1);

        // 选填，短信发送流水号
        $request->setBizId("yourBizId");

        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);

        return $acsResponse;
    }

}


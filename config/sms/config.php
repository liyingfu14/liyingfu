<?php
/**
 * Created by TianQi.
 * User: weblinuxgame
 * Email: 994685563@qq.com
 * Date: 2018-05-02
 * Time: 16:51
 */

return [
    'APPKEY' => 'LTAIdFHnklwquUdA',
    'APPSECRET' => 'Cuec5CDXtFKmqQjFwio58UtO9i4Dim',
    'PRODUCT' => '天启互动娱乐',
    'SIGNNAME' => '天启互动娱乐',    /*签名名称*/
    'SMSTEMPAUTH' => 'SMS_133961727',  //身份验证验证码
    'SMSBACKPW' => 'SMS_133971807',  // 忘记密码
    'SMSTEMPREG' => 'SMS_133961726',   //用户注册验证码
    'SMSTEMPINFOMOD' => 'SMS_133966850',  //绑定手机验证码
    'SMSTEMPPWDMOD' => 'SMS_133971806',   //修改密码验证码
    'SMSTEMPTEST' => '#aliyunsmstemptest#',  //短信测试
    'SMSTEMPLOGIN' => '#aliyunsmstemplogin#',  //登录确认验证码
    'SMSTEMPLOGINERROR' => '#aliyunsmstemploginerror#', //登录异常验证码
    'SMSTEMPACT' => '#aliyunsmstempact#',  //活动确认验证码
    //1.注册 2.忘记密码 3.绑定手机 4.修改密码
    'types' => [
        "1" => "SMSTEMPREG", // 用户注册验证码
        "2" => "SMSBACKPW", // 忘记密码
        "3" => "SMSTEMPINFOMOD", // 绑定手机验证码
        "4" => "SMSTEMPPWDMOD" // 修改密码验证码
    ]
];
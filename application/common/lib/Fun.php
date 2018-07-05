<?php

namespace app\common\lib;

use app\common\model\OrderCommentModel;
use think\Config;
use think\Request;
use think\Session;
use app\admin\model\SystemOptionLog;

class Fun
{
    //获取上传token
    public static function uploadToken($url,$login_token,$data,$xApp)
    {
        $heaher = ['x-app'=>$xApp,'x-token'=>$login_token,'Content-Type'=>'application/json;charset=utf-8'];
        $rs = Utils::brower()->post($url,$heaher,json_encode($data));
        return $rs->getContent();
    }

    /**
     * 分页
     * @param $pageSize 分页显示条数
     * @param $total    数据总数
     * @param $showPage    显示页码
     * @param $url    跳转地址
     * @param $page    页码
     * @return string
     */
    public static function page($url,$pageSize,$total,$page,$showPage=5){
        // $pageSize = 10; //显示条数
        // $total = xxx;   //数据总数
        $total_pages = ceil($total/$pageSize);   //总页数
        // $showPage = 5; //显示页码
        $page_banner = "<div class='page'>";
        //计算偏移量
        $pageoffset = ($showPage-1)/2;
        if($page > 1){
            // $page_banner .= "<a href='".$url."?p=1'>首页</a>";
            $page_banner .= "<a href='".$url."?p=".($page-1)."'>&lt;</a>";
        }else{
            // $page_banner .= "<span class='disable'>首页</span>";
            $page_banner .= "<span class='disable'>&lt;</span>";
        }
        //初始化数据
        $start = 1;
        $end = $total_pages;
        if($total_pages > $showPage){
            if($page > $pageoffset + 1){
                // $page_banner .= '...';
            }
            if($page > $pageoffset){
                $start = $page - $pageoffset;
                $end = $total_pages > $page+$pageoffset ? $page+$pageoffset : $total_pages;
            }else{
                $start = 1;
                $end = $total_pages > $showPage ? $showPage : $total_pages;
            }
            if($page + $pageoffset > $total_pages){
                $start = $start - ($page + $pageoffset - $end);
            }
        }

        for($i = $start; $i <= $end; $i++){
            if($page == $i){
                $page_banner .= "<span class='current'>{$i}</span>";
            }else{
                $page_banner .= "<a href='".$url."?p=".$i."'>{$i}</a>";
            }
        }

        //尾部省略
        if($total_pages > $showPage && $total_pages > $page + $pageoffset){
            // $page_banner .= '...';
        }
        if($page < $total_pages){
            $page_banner .= "<a href='".$url."?p=".($page+1)."'>&gt;</a>";
            // $page_banner .= "<a href='".$url."?p=".$total_pages."'>尾页</a>";
        }else{
            $page_banner .= "<span class='disable'>&gt;</span>";
            // $page_banner .= "<span class='disable'>尾页</span>";
        }
        $page_banner .= "&ensp;共{$total_pages}页&ensp;";
        $page_banner .= "<form action='' method='get'>";
        $page_banner .= "到第<input type='text' size='2' name='p'>页";
        $page_banner .= "<input type='submit' value='确定'>";
        $page_banner .= "</form></div>";
        return $page_banner;
    }

    /**
     * 获取访客IP
     * @param int $type
     * @param bool $adv
     * @return mixed
     */
    public static function getClientIp($type = 0, $adv = false)
    {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL) return $ip[$type];
        if ($adv) {

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) unset($arr[$pos]);
                $ip = trim($arr[0]);
            }

            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            }

            if (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    public static function getUploadToken(){
        $adminConfig = Config::load(CONF_PATH.'admin.php','admin');
        $url = $adminConfig['upload_token_url'];
        $login_token = session('token');
        $data = ['type'=>'image'];
        $xApp = $adminConfig['upload_p'];
        $token = self::uploadToken($url,$login_token,$data,$xApp);
        if (empty(json_decode($token)->data)) {
            return false;
        }
        $uploadToken = json_decode($token)->data->token;
        $data = [
            'xapp'  =>  $xApp,
            'uploadToken' =>  $uploadToken,
            'token' =>  $login_token,
        ];
        return $data;
    }

    //用户操作日志
    public static function logWriter($option,$id = ''){
        $logModel = new SystemOptionLog($option);
        $opt_name = $logModel->getLogCode($option);
        $username = Session::get('username');
        $content = '用户 [ '.$username.' ] 执行了 [ '.$opt_name['opt_name'].'('.$option.') ] ,任务ID [ '.$id.' ] ,IP:'.self::getClientIp();
        $uid = Session::get('uid');
        if (empty($uid)){
            $login_status = 0;
        }else{
            $login_status = 1;
        }
        $request_url = DS.strtolower(request()->module()).DS.strtolower(request()->controller()).DS.strtolower(request()->action());
        $request_method = Request::instance()->method();
        $data = [
            'option'    =>  $option,
            'content'   =>  $content,
            'length'    =>  strlen($content),
            'uid'       =>  $uid,
            'create_at' =>  time(),
            'login_status'  =>  $login_status,
            'request_url'   =>  $request_url,
            'request_method'    =>  $request_method,
            'ip'    =>  self::getClientIp()
        ];
        $logModel->insertLog($data);
    }
}
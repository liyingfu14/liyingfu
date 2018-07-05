<?php

namespace app\common\controller;

use think\Request;
use app\common\model\Code;
use think\Hook;

class BaseWeb extends BaseApi
{
    protected $template_ext = '.html';
    protected $empty_html = 'error/error.html';

    protected function __initialise()
    {
        $this->request = Request::instance();
        $this->Code = new Code();
        $this->__initDeviceInfo();
        Hook::listen('action_begin', $this);
        $this->__initToken();
        $this->__initRbac();
    }

    public function template()
    {
        $url = $this->request->pathinfo();
        $ext = $this->template_ext;
        if (preg_match("/$ext$/", $url)) {
            $file = $this->parse($url);
            return file_get_contents($file);
        }
        return '<h1>页面去了火星,请联系管理员!</h1>';
    }

    protected function parse($url)
    {
        $theme = $this->getThemePath();
        if (empty($url)) {
            return  $theme.$this->empty_html;
        }
        list($module, $page) = explode('view', $url);
        if (empty($module) && empty($page)) {
            return $this->empty_html;
        }
        $page = str_replace('/', DS, $page);
        $file = $theme . $page;
        if (file_exists($file)) {
            return $file;
        }
        $file = $theme . $module . DS . $page;
        if (file_exists($file)) {
            return $file;
        }
        return $theme.$this->empty_html;
    }

    protected function getThemePath()
    {
        return dirname(APP_PATH) . DS . 'themes' . DS;
    }

    protected function html($html)
    {
        echo $html;
    }

}
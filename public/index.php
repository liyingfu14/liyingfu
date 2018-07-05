<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// DIR
defined('DS') or define('DS',DIRECTORY_SEPARATOR);
// 定义应用目录
define('APP_PATH', dirname(__DIR__) . DS .'application'.DS);
// 单独定义 配置 目录
defined('CONF_PATH') or define('CONF_PATH',dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR );
// 引入 libs
require_once dirname(__DIR__).DS.'libs'.DS.'bootstrap.php';
define('EXTEND_PATH',__DIR__.'/../libs');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';

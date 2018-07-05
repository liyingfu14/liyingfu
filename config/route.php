<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route ;

Route::post('api/user/register/mobile' , 'api/User/registerByMobile');
Route::post('api/user/register/account' , 'api/User/registerByAccount');
Route::post('api/user/login' , 'api/User/login');
Route::post('api/user/edit','api/User/edit');
Route::get('api/user/info','api/User/info');
Route::post('api/init','api/User/init');
Route::post('api/user/bind/mobile','api/User/bindMobile');
Route::post('api/user/reset/password','api/User/resetPassword');
Route::post('api/user/forget/password','api/User/forgetPassword');
Route::get('api/user/logout','api/User/logout');
// todo
Route::post('api/user/identify','api/User/realname');

// admin
// Route::get('admin/view/:page','admin/Index/index',[],['__pattern__'=>[
//       'page'=>'\w|\d'
// ]]);
// Route::get('admin/view','admin/Index/index',[],['__pattern__'=>[
//     'page'=>'\w|\d'
// ]]);


Route::rule('/','admin/login/index');

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]' => [
        ':id' => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],
    '[forum]' => [
        ':app_id' => ['forum/sendPost,forum/sendComment,forum/subs', ['method' => 'post']],
        ':uid' => ['forum/sendPost,forum/sendComment,forum/like,forum/subs', ['method' => 'post']],
        ':title' => ['forum/sendPost', ['method' => 'post']],
        ':content' => ['forum/sendPost', ['method' => 'post']],
        ':pics' => ['forum/sendPost', ['method' => 'post']],
        ':post_id' => ['forum/sendComment,forum/like', ['method' => 'post']],
        ':comment' => ['forum/sendComment', ['method' => 'post']],
        ':comment_id' => ['forum/like', ['method' => 'post']],
        ':comm_id' => ['forum/subs', ['method' => 'post']],
    ],
    '[app]' => [
        ':mobile' => ['app/sendSms', ['method' => 'post']],
        ':type' => ['app/sendSms', ['method' => 'post']]
    ],
    '[games]' => [
        ':app_id' => ['games/appointment', ['method' => 'post']],
        ':uid' => ['games/appointment', ['method' => 'post']],
    ],

];

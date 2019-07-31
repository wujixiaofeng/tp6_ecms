<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;
if(!defined('InEmpireCMS'))exit();
global $inthinkphp;
$inthinkphp=true;
require 'vendor/autoload.php';
$app=new App();
if(strpos($_SERVER['HTTP_HOST'],'domain.com')===false)$app->debug(true);
$http = $app->http;
if(defined('BIND_APP'))$http->name(BIND_APP);
$response = $http->run();
$response->send();
$http->end($response);
$inthinkphp=false;
?>
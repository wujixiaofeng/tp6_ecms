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

// +----------------------------------------------------------------------
// | 会话设置
// +----------------------------------------------------------------------

return [
	// session name
	'name' => '',
	// SESSION_ID的提交变量,解决flash上传跨域
	'var_session_id' => '',
	// 驱动方式 支持file redis memcache memcached
	'type' => 'file',
	'prefix'=>'',
	// 过期时间
	'expire' => 0,
];
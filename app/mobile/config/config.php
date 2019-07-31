<?php
//Request::root()
//Request::app()
return [
	//模板相关配置
	'tmpl_replace' => array(
		'__SKIN__' => 'http://www.domain.com/skin/pai2',
		'__IMG__' => '/images',
		'__CSS__' => '/files',
		'__JS__' => '/files',
	),
	//无头像时使用随机默认头像
	'rand_avatar'=>true,
];
?>
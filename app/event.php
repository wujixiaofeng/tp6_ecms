<?php
return [
	'listen' => [
		'AppInit' => [],
		'HttpRun' => [],
	
		'CancelZanNews'=>[],
		'ZanNews'=>[],
		'CancelFavNews'=>[],
		'FavNews'=>[],
		'DeleteNews'=>[],
		'DeleteNewsFile'=>[],
		'DeleteOtherFile'=>[],
		'AddPinglun'=>['app\common\listener\AddPinglun'],
	],
	'subscribe' => [
		//设置subscribe 需要设置listen 并设置命名空间为空
		'app\common\subscribe\App',
		'app\common\subscribe\DeleteFile',
	],
];
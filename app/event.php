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
		//����subscribe ��Ҫ����listen �����������ռ�Ϊ��
		'app\common\subscribe\App',
		'app\common\subscribe\DeleteFile',
	],
];
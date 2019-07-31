<?php
use think\facade\Db;
function showing_class(){
	global $class_r;
	$classidarr=array();
	$list=Db::name('HdWapclass')->where('mobile',1)->order('myorder asc')->select()->toArray();
	foreach($list as $k=>$v){
		$classidarr[$v['classid']]=$v['classname'];
	}
	return $classidarr;
}
function short_class(){
	global $class_r;
	$classidarr=array();
	$list=Db::name('HdWapclass')->where('mobile2',1)->order('myorder asc')->select()->toArray();
	foreach($list as $k=>$v){
		$classidarr[$v['classid']]=$v['shortname'];
	}
	return $classidarr;
}
?>
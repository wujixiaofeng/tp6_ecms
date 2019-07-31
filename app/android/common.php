<?php
use think\facade\Db;



//过滤app更新强制更新设置字符串
function update_force_replace($str){
	//先将var和sdk替换成!和@
	$str=str_ireplace(array('sdk','ver'),array('!','@'),$str);
	//过滤字符串
	$str=preg_replace('%[^0-9\>\<\&\|\(\)\!\@\=]%','',$str);
	//将!和@替换回var和sdk
	$str=str_replace(array('!','@'),array('sdk','ver'),$str);
	return $str;
}

//替换app更新强制更新设置字符串
function update_force_replace2($str){
	$str=str_replace(array('ver','sdk','&','|','=','====','>==','<=='),array('$appVerCode','$appSDK',' and ',' or ','==','==','>=','<='),$str);
	return $str;
}




function showing_class(){
	global $class_r;
	$classidarr=array();
	$list=Db::name('HdWapclass')->where('app',1)->order('myorder asc')->select()->toArray();
	foreach($list as $k=>$v){
		$tbname=$class_r[$v['classid']]['tbname'];
		//if($tbname=='pictures')$tbname='pic';
		$classidarr[]=array('classid'=>$v['classid'],'classname'=>$v['classname'],'name'=>$v['classname'],'shortname'=>$v['shortname'],'tbname'=>$tbname);
	}
	return $classidarr;
}
?>
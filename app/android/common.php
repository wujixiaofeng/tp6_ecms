<?php
use think\facade\Db;



//����app����ǿ�Ƹ��������ַ���
function update_force_replace($str){
	//�Ƚ�var��sdk�滻��!��@
	$str=str_ireplace(array('sdk','ver'),array('!','@'),$str);
	//�����ַ���
	$str=preg_replace('%[^0-9\>\<\&\|\(\)\!\@\=]%','',$str);
	//��!��@�滻��var��sdk
	$str=str_replace(array('!','@'),array('sdk','ver'),$str);
	return $str;
}

//�滻app����ǿ�Ƹ��������ַ���
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
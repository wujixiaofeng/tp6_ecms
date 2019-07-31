<?php
use think\facade\Db;
/*
 * 显示焦点图信息
 * fields 列名
 * count 数量
 * tbs 可以是classid列表或表名列表 classid和表名可以组合使用 可以是数组或者是逗号分隔的字符串
 * where 附加的查询条件
*/
function res_focuslist($fields='',$count=5,$tbs='news,pictures,zhibo',$where=''){
	global $dbtbpre;
	if(!$fields)$fields='classid,id,focusImg,titleurl,ftitle,title,smalltext,newstime,userid,username';
	if(is_array($fields)){
		$fields=tp_arr2str($fields);
	}
	if(!in_array('newstime',tp_str2arr($fields))){
		$fields.=',newstime';
	}
	$sql='';
	$tbs=res_tbs($tbs);
	$where1='';
	foreach($tbs[1] as $classid){
		$where1.=($where1?' or ':'')." classid=$classid ";
	}
	if($where1)$where1='('.$where1.')';
	if($where1)$where.=($where?' and ':'').$where1;
	$where=$where?$where:' id>0 ';
	foreach($tbs[0] as $tbname){
		$sql.=($sql?' union ':'')." select {$fields},'{$tbname}' as tbname from {$dbtbpre}ecms_{$tbname} where $where and focusImg!='' and firsttitle=1 ";
	}
	$focus=DB::query($sql." order by newstime desc limit {$count}");
	return $focus;
}
function res_tbs($tbs){
	global $class_r;
	if(!is_array($tbs))$tbs=tp_str2arr($tbs);
	$classids=[];
	$tbnames=[];
	foreach($tbs as $v){
		if(is_numeric($v)){
			$classids[]=$v;
			$tbname=$class_r[$v]['tbname'];
		}else{
			$tbname=$v;
		}
		if($tbname&&!in_array($tbname,$tbnames))$tbnames[]=$tbname;
	}
	return [$tbnames,$classids];
}

function res_cache_clicklist($fields='',$count=10,$time=0,$tbs='news,pictures,youji,zhibo',$where=''){
	$key=md5('clicklist_'.$fields.'_'.$count.'_'.$time.'_'.$tbs.'_'.$where);
	$res=cache($key);
	if(!$res){
		$res=res_clicklist($fields,$count,$time,$tbs,$where);
		cache($key,$res,3600);
	}
	return $res;
}
/*
 * 显示点击排行信息
 * fields 列名
 * count 数量
 * time 时间可以是时间戳 或者是多少天数以内 或者是 strtotime()的参数
 * tbs 可以是classid列表或表名列表 classid和表名可以组合使用 可以是数组或者是逗号分隔的字符串
 * where 附加的查询条件
*/
function res_clicklist($fields='',$count=10,$time=0,$tbs='news,pictures,youji,zhibo',$where=''){
	global $dbtbpre;
	if(!$fields)$fields='classid,id,titlepic,titleurl,ftitle,title,smalltext,newstime,userid,username';
	if(is_array($fields)){
		$fields=tp_arr2str($fields);
	}
	$sql='';
	$tbs=res_tbs($tbs);
	$where1='';
	foreach($tbs[1] as $classid){
		$where1.=($where1?' or ':'')." classid=$classid ";
	}
	if($where1)$where1='('.$where1.')';
	if(is_numeric($time)){
		if($time>0){
			if($time<10000){
				$time=strtotime("- $time days");
			}
			$where.=($where?' and ':'')." newstime > $time ";
		}
	}elseif(is_string($time)){
		if($time){
			$time=strtotime($time);
			$where.=($where?' and ':'')." newstime > $time ";
		}
	}
	if($where1)$where.=($where?' and ':'').$where1;
	if($where)$where=' where '.$where;
	foreach($tbs[0] as $tbname){
		if(in_array($tbname,tp_str2arr('news,pictures,zhibo'))){
			//$clickas=",TIMESTAMPDIFF(DAY,from_unixtime(newstime,'%Y-%m-%d'),'".date('Y-m-d')."')*beishu+jishu as click";
			//(2*($info['beishu']+$info['jishu']+$info['onclick']))
			//$clickas=",(beishu+jishu+onclick) as click";
			$clickas=",onclick as click";
		}else{
			$clickas=',onclick as click';
		}
		$sql.=($sql?' union ':'')." select {$fields},onclick{$clickas},'{$tbname}' as tbname from {$dbtbpre}ecms_{$tbname} $where";
	}
	$list=DB::query($sql." order by onclick desc limit {$count}");
	return $list;
}
/*
 * 显示最新信息
 * fields 列名
 * count 数量
 * tbs 可以是classid列表或表名列表 classid和表名可以组合使用 可以是数组或者是逗号分隔的字符串
 * where 附加的查询条件
*/
function res_newlist($fields='',$count=10,$tbs='news,pictures,youji,zhibo',$where=''){
	global $dbtbpre;
	if(!$fields)$fields='classid,id,titlepic,titleurl,ftitle,title,smalltext,newstime,userid,username';
	if(is_array($fields)){
		$fields=tp_arr2str($fields);
	}
	if(!in_array('newstime',tp_str2arr($fields))){
		$fields.=',newstime';
	}
	$sql='';
	$tbs=res_tbs($tbs);
	$where1='';
	foreach($tbs[1] as $classid){
		$where1.=($where1?' or ':'')." classid=$classid ";
	}
	if($where1)$where1='('.$where1.')';
	if($where1)$where.=($where?' and ':'').$where1;
	if($where)$where=' where '.$where;
	foreach($tbs[0] as $tbname){
		//if($tbname=='news')$timewhere=($where?' and ':' where ')." newstime > ".strtotime(' -100 days ');
		$sql.=($sql?' union ':'')." select {$fields},'{$tbname}' as tbname from {$dbtbpre}ecms_{$tbname} $where ";
	}
	$list=DB::query($sql." order by newstime desc limit {$count}");
	return $list;
}
/*
 * 显示随机信息
 * fields 列名
 * count 数量
 * time 时间可以是时间戳 或者是多少天数以内 或者是 strtotime()的参数
 * tbs 可以是classid列表或表名列表 classid和表名可以组合使用 可以是数组或者是逗号分隔的字符串
 * where 附加的查询条件
*/
function res_randlist($fields='',$count=10,$time=0,$tbs='news,pictures,youji,zhibo',$where=''){
	global $dbtbpre;
	if(!$fields)$fields='classid,id,titlepic,titleurl,ftitle,title,smalltext,newstime,userid,username';
	if(is_array($fields)){
		$fields=tp_arr2str($fields);
	}
	$tbs=res_tbs($tbs);
	$where1='';
	foreach($tbs[1] as $classid){
		$where1.=($where1?' or ':'')." classid=$classid ";
	}
	if($where1)$where1='('.$where1.')';
	if(is_numeric($time)){
		if($time>0){
			if($time<10000){
				$time=strtotime("- $time days");
			}
			$where.=($where?' and ':'')." newstime > $time ";
		}
	}elseif(is_string($time)){
		if($time){
			$time=strtotime($time);
			$where.=($where?' and ':'')." newstime > $time ";
		}
	}
	if($where1)$where.=($where?' and ':'').$where1;
	if($where)$where=' where '.$where;
	$rscount=0;
	$sql='';
	foreach($tbs[0] as $tbname){
		$sql.=($sql?' union ':'')." select {$fields},'{$tbname}' as tbname,rand() as r from {$dbtbpre}ecms_{$tbname} $where";
	}
	$list=DB::query($sql." order by r desc limit {$count}");
	return $list;
}
/*
 * 显示推荐信息
 * isgood 推荐等级
 * fields 列名
 * count 数量
 * tbs 可以是classid列表或表名列表 classid和表名可以组合使用 可以是数组或者是逗号分隔的字符串
 * where 附加的查询条件
*/
function res_isgoodlist($isgood=1,$fields='',$count=10,$tbs='news,pictures,youji,zhibo',$where=''){
	global $dbtbpre;
	if(!$fields)$fields='classid,id,titlepic,titleurl,ftitle,title,smalltext,newstime,userid,username';
	if(is_array($fields)){
		$fields=tp_arr2str($fields);
	}
	if(!in_array('newstime',tp_str2arr($fields))){
		$fields.=',newstime';
	}
	$sql='';
	$tbs=res_tbs($tbs);
	$where1='';
	foreach($tbs[1] as $classid){
		$where1.=($where1?' or ':'')." classid=$classid ";
	}
	if($where1)$where1='('.$where1.')';
	if($where1)$where.=($where?' and ':'').$where1;
	$where.=($where?' and ':'')." isgood={$isgood} ";
	if($where)$where=' where '.$where;
	foreach($tbs[0] as $tbname){
		$sql.=($sql?' union ':'')." select {$fields},'{$tbname}' as tbname from {$dbtbpre}ecms_{$tbname} $where";
	}
	$list=DB::query($sql." order by newstime desc limit {$count}");
	return $list;
}
/*
 * 显示头条信息
 * firsttitle 头条等级
 * fields 列名
 * count 数量
 * tbs 可以是classid列表或表名列表 classid和表名可以组合使用 可以是数组或者是逗号分隔的字符串
 * where 附加的查询条件
*/
function res_firsttitlelist($firsttitle=1,$fields='',$count=10,$tbs='news,pictures,youji,zhibo',$where=''){
	global $dbtbpre;
	if(!$fields)$fields='classid,id,titlepic,titleurl,ftitle,title,smalltext,newstime,userid,username';
	if(is_array($fields)){
		$fields=tp_arr2str($fields);
	}
	if(!in_array('newstime',tp_str2arr($fields))){
		$fields.=',newstime';
	}
	$sql='';
	$tbs=res_tbs($tbs);
	$where1='';
	foreach($tbs[1] as $classid){
		$where1.=($where1?' or ':'')." classid=$classid ";
	}
	if($where1)$where1='('.$where1.')';
	if($where1)$where.=($where?' and ':'').$where1;
	$where.=($where?' and ':'')." firsttitle={$firsttitle} ";
	if($where)$where=' where '.$where;
	foreach($tbs[0] as $tbname){
		$sql.=($sql?' union ':'')." select {$fields},'{$tbname}' as tbname from {$dbtbpre}ecms_{$tbname} $where";
	}
	$list=DB::query($sql." order by newstime desc limit {$count}");
	return $list;
}
?>
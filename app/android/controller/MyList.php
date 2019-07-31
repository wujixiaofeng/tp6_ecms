<?php
namespace app\android\controller;
use think\facade\Db;

class MyList extends Base{
	public function cate() {
		return json(showing_class());
	}

	public function search() {
		global $class_r,$dbtbpre;
		$tbname=input('get.tbname');
		$page=max(1,intval($_GET['page']));
		$pagesize=8;
		$offset=($page-1)*$pagesize;
		$keyword=input2('get.keyword');
		if($tbname=='news'){
			$showingclass=showing_class();
			foreach($showingclass as $k=>$v){
				$showingclass[$k]=$v['classid'];
			}
		}
		$rscount=DB::table("{$dbtbpre}ecms_{$tbname}")->where(" (title like '%$keyword%' or ftitle like '%$keyword%')".($showingclass?" and classid in(".implode(',',$showingclass).")":''))->count();
		if($tbname=="youji")$isOriginalStr="'0' as ";
		$list=DB::query("select classid,id,titlepic as img,ftitle,title,smalltext as `desc`,{$isOriginalStr}isOriginal,userid,ismember,username,newstime from {$dbtbpre}ecms_{$tbname} where (title like '%$keyword%' or ftitle like '%$keyword%')".($showingclass?" and classid in(".implode(',',$showingclass).")":'')." order by newstime desc limit $offset,$pagesize");
		$pagecount=ceil($rscount/$pagesize);
		foreach($list as $k=>$v){
			if(!$v['ftitle'])$v['ftitle']=$v['title'];
			$v['desc']=tp_subtext($v['desc'],50);
			$v['ismember']=($v['ismember']=='1');
			$v['newstime']=date("Y-m-d",$v['newstime']);
			$v['tbname']=$class_r[$v['classid']]['tbname'];
			$list[$k]=$v;
		}
		return json(['list'=>$list,'pagecount'=>$pagecount,'rscount'=>$rscount]);
	}

	public function list() {
		global $class_r,$dbtbpre;
		$classid=input('get.classid/d',0);
		$tbname=$class_r[$classid]['tbname'];
		$page=max(1,intval($_GET['page']));
		$pagesize=8;
		$offset=($page-1)*$pagesize;
		$where=[['isurl','=',0]];
		if($classid>0)array_push($where,['classid','=',$classid]);
		$filed=['classid','id','titlepic'=>'img','ftitle','title','userid','ismember','username','newstime'];
		$filed1=$filed;
		array_push($field1,'isOriginal');
		$filed2=$filed;
		array_push($field2,["'0'"=>'isOriginal']);
		
		if($tbname){
			if($tbname=="youji")$isOriginalStr="'0' as ";
			$sql="select classid,id,{$isOriginalStr}isOriginal,titlepic as img,ftitle,title,userid,ismember,username,newstime from {$dbtbpre}ecms_{$tbname} where classid=$classid	and isurl=0 order by newstime desc limit $offset,$pagesize";
		}else{
			$sql="
				select classid,id,isOriginal,titlepic as img,ftitle,title,userid,ismember,username,newstime,isurl from {$dbtbpre}ecms_news where isurl=0
				union 
				select classid,id,'0' as isOriginal,titlepic as img,ftitle,title,userid,ismember,username,newstime,isurl from {$dbtbpre}ecms_pictures where isurl=0
				union
				select classid,id,'0' as isOriginal,titlepic as img,ftitle,title,userid,ismember,username,newstime,isurl from {$dbtbpre}ecms_youji where isurl=0
				union
				select classid,id,'0' as isOriginal,titlepic as img,ftitle,title,userid,ismember,username,newstime,isurl from {$dbtbpre}ecms_zhibo
				where isurl=0
				order by newstime desc limit $offset,$pagesize";
		}
		$list=DB::query($sql);
		foreach($list as $k=>$v){
			$list[$k]['tbname']=$class_r[$v['classid']]['tbname'];
			if(!$list[$k]['ftitle'])$list[$k]['ftitle']=$v['title'];
			$list[$k]['ismember']=$v['ismember']=='1';
			$list[$k]['newstime']=date("Y-m-d",$v['newstime']);
		}
		return json(['list'=>$list,'tbname'=>$tbname]);
	}

	public function focus() {
		global $class_r;
		$classid=input('get.classid/d',0);
		$tbname=$class_r[$classid]['tbname'];
		$where=[
			['focusImg','<>',''],
			['isurl','=',0],
			['firsttitle','=',1]
		];
		if($classid>0)array_push($where,['classid','=',$classid]);
		$filed=['classid','id','focusImg'=>'img','ftitle','title'/*,'smalltext'=>'desc'*/,'userid','ismember','username','newstime'];
		if($tbname){
			if($tbname=="youji"){
				$list=[];
			}else{
				$list=DB::name('Ecms'.ucwords($tbname))->field($filed)->limit(0,5)->where($where)->select()->toArray();
			}
		}else{
			//$a=DB::name('EcmsShipin')->field($filed)->where($where)->buildSql();
			$b=DB::name('EcmsPictures')->field($filed)->where($where)->buildSql();
			$c=DB::name('EcmsZhibo')->field($filed)->where($where)->buildSql();
			$e=DB::name('EcmsNews')->field($filed)->where($where)->union([/*$a,*/$b,$c])->buildSql();
			$list=DB::table($e.' as e')->limit(0,5)->order('newstime desc')->select()->toArray();
		}
		foreach($list as $k=>$v){
			$list[$k]['tbname']=$class_r[$v['classid']]['tbname'];
			$list[$k]['ismember']=!!$v['ismember'];
		}
		return json(['list'=>$list]);
	}
}
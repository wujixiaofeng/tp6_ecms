<?php
namespace app\mobile\controller;
use think\facade\Db;
use think\facade\Config;

class News extends Base {
	use \app\common\traits\CommonTrait;
	use \app\common\traits\NewsTrait;
	public function show($classid=0,$id=0){
		global $class_r;
		$tbname=$class_r[$classid]['tbname'];
		$info=$this->info($classid,$id);
		if(!$info)return $this->err404();
		$checked=$info['checked'];
		if(!$checked){
			if(($info['ismember']!=1||tp_login()!=$info['userid'])&&!session('admin_user_auth')/*&&input('get.showchecked')!=1*/){
				return $this->err404();
			}
		}
		$info['tuijian']=$this->tuijian();
		$info['xiangguan']=$this->xiangguan($info['keyboard']);
		return $this->view($tbname,
		[
			'mipurl'=>$this->mipurl($info['titleurl']),
			'pcurl'=>$this->pcurl($info['titleurl']),
			'info'=>$info,
			'classid'=>$classid,
			'id'=>$id,
			'pagetitle'=>$info['title'],
			'pagekeywords'=>$info['keyboard'],
			'pagedesc'=>$info['desc']
		]);
	}
	public function xiangguan($keywords=''){
		global $dbtbpre;
		if($keywords){
			$keywords=explode(",",$keywords);
			$sqladd="";
			foreach($keywords as $v){
				if($v)$sqladd.=($sqladd?" or ":"")." keyboard like '%{$v}%' ";
			}
			if($sqladd){
				return DB::query("select classid,id,titleurl,title,newstime,userid,username from {$dbtbpre}ecms_news where $sqladd order by newstime desc limit 10");
			}
		}
		return [];
	}
	public function search(){
		global $class_r;
		$tbname=input('get.tbname');
		if($tbname){
			$pagesize=20;
			$page=input('get.page/d',1);
			$keywords=input('get.keywords');
			$where=[['title|ftitle','like','%'.$keywords.'%']];
			$count=enews($tbname)->getCount($where);
			$pagehtml=getmpagehtml($count,$pagesize);
			if($tbname=='youji')$writeras="'' as ";
			$list=enews($tbname)->getList($where,$page,$pagesize,"classid,id,titlepic,titleurl,title,smalltext,newstime,{$writeras}writer,username,'{$tbname}' as tbname");
			return $this->view('',['pagetitle'=>'ËÑË÷¡°'.$keywords.'¡±','list'=>$list,'tbname'=>$tbname,'keywords'=>$keywords,'islist'=>true,'pagehtml'=>$pagehtml,'classid'=>$classid]);
		}else{
			$res['pagetitle']='ËÑË÷';
			return $this->view('',$res);
		}
	}
	public function newslist($classid=0,$page=1){
		global $class_r,$dbtbpre;
		$pagesize=20;
		$tbname=$class_r[$classid]['tbname'];
		$count=enews($tbname)->getCount($classid);
		$pagehtml=getmpagehtml($count,$pagesize,$page,'index_[PAGE].html');
		$field="classid,id,titlepic,titleurl,title,smalltext,newstime,ismember,userid,username";
		$list=enews($tbname)->getList($classid,$page,$pagesize,$field);
		$view='list';
		return $this->view($view,
		[
			'pagetitle'=>$class_r[$classid]['classname'],
			'list'=>$list,
			'islist'=>true,
			'pagehtml'=>$pagehtml,
			'classid'=>$classid
		]);
	}
	private function mipurl($url){
		$url=str_replace("http://www.domain.com","",$url);
		$url=str_replace("http://m.domain.com","",$url);
		$url="http://mip.domain.com".$url;
		return $url;
	}
	private function pcurl($url){
		$url=str_replace("http://www.domain.com","",$url);
		$url=str_replace("http://m.domain.com","",$url);
		$url="http://www.domain.com".$url;
		return $url;
	}
	private function tuijian(){
		global $dbtbpre;
		$fields='titlepic,titleurl,title,smalltext,newstime,username';
		$list=res_clicklist($fields,4,500,'news');
		return $list;
	}
}
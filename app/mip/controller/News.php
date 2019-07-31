<?php
namespace app\mip\controller;
use think\facade\Db;
use think\facade\Config;

class News extends Base {
	use \app\common\traits\NewsTrait;
	public function show($classid=0,$id=0){
		global $class_r;
		$tbname=$class_r[$classid]['tbname'];
		$info=$this->info($classid,$id);
		if(!$info)return $this->err404();
		$checked=$info['checked'];
		if(!$checked){
			if(($info['ismember']!=1||tp_login()!=$info['userid'])&&!session('admin_user_auth')){
				return $this->err404();
			}
		}
		if($tbname=='news'){
			$newstext=stripslashes($info['html']);
			$newstext=preg_replace("%\<img.*?src=[\'\"]{1}(.*?)[\'\"]{1}.*?\>%si","<mip-img src=\"\${1}\"></mip-img>",$newstext);
			$newstext=preg_replace("/ style\=([\"\']{1})(.*?)([\"\']{1})/si","",$newstext);
			$info['html']=$newstext;
		}
		return $this->view($tbname,
		[
			'murl'=>$this->murl($info['titleurl']),
			'info'=>$info,
			'classid'=>$classid,
			'id'=>$id,
			'pagetitle'=>$info['title'],
			'pagekeywords'=>$info['keyboard'],
			'pagedesc'=>$info['desc']
		]);
	}
	public function newslist($classid=0,$page=1){
		global $class_r;
		$pagesize=20;
		$tbname=$class_r[$classid]['tbname'];
		$count=enews($tbname)->getCount($classid);
		$pagehtml=getmpagehtml($count,$pagesize,$page,'index_[PAGE].html');
		if($tbname=='youji')$writeras="'' as ";
		$list=enews($tbname)->getList($classid,$page,$pagesize,"classid,id,titlepic,titleurl,title,smalltext,newstime,{$writeras}writer,username,'{$tbname}' as tbname");
		$mipurl="http://mip.domain.com/".$class_r[$classid]['classpath']."/index".($page==1?"":"_".$page).".html";
		$pcurl="http://www.domain.com/".$class_r[$classid]['classpath']."/index".($page==1?"":"_".$page).".html";
		if($tbname=='pictures'||$tbname=='youji'){
			$view='piclist';
		}else{
			$view='list';
		}
		return $this->view($view,
		[
			'mipurl'=>$mipurl,
			'pcurl'=>$pcurl,
			'pagetitle'=>$class_r[$classid]['classname'],
			'list'=>$list,
			'pagetitle'=>$class_r[$classid]['classname'],
			'islist'=>true,
			'pagehtml'=>$pagehtml,
			'classid'=>$classid
		]);
	}
	private function murl($url){
		$url=str_replace("http://www.domain.com","",$url);
		$url=str_replace("http://mip.domain.com","",$url);
		$url="http://m.domain.com".$url;
		return $url;
	}
}
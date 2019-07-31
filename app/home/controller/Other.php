<?php
namespace app\home\controller;
use app\common\model\Db;
use app\common\model\HdDiqu as Diqu;
use app\common\model\Enewsmember as User;

class Other extends Base {
	public function indexcont3more(){
		global $dbtbpre;
		$page=input('get.page/d',1);
		$pagesize=12;
		$offset=($page-1)*$pagesize;
		$showedids=explode(',',$_GET[showedids]);
		foreach($showedids as $k=>$v){
			$showedids[$k]=intval($v);
		}
		$list=DB::query("select classid,id,titlepic,titleurl,title,smalltext from {$dbtbpre}ecms_news where classid!=403".(count($showedids)>0?" and id not in (".implode(",",$showedids).")":"")." and isgood=1 order by newstime desc limit $offset,$pagesize");
		return $this->view('',['list'=>$list]);
	}
	public function zuozhelist($userid,$ismember=0,$classid=0,$page=1){
		global $dbtbpre;
		$userid=intval($userid);
		$ismember=intval($ismember);
		$classid=intval($classid);
		$page=intval($page);
		$utbname=$ismember>0?"enewsmember":"enewsuser";
		$userinfo=DB::getRow("select username,email from {$dbtbpre}{$utbname} where userid=$userid");
		if(empty($userinfo[username])){
			$userinfo=DB::getRow("select username from {$dbtbpre}ecms_news where userid=$userid and ismember=$ismember");
			if(empty($userinfo[username])){
				$userinfo=DB::getRow("select username from {$dbtbpre}ecms_pictures where userid=$userid and ismember=$ismember");
			}
		}
		if(!$userinfo['username'])return $this->err404();
		if($ismember){
			$usergroup="投稿编辑";
		}else{
			$usergroup=DB::getValue("select groupname as total from {$dbtbpre}enewsgroup as g,{$dbtbpre}enewsuser as u where g.groupid=u.groupid and u.userid=$userid");
		}
		$useravatar=user_avatar($userid,0,!$ismember);
		$rscount=(int)DB::getValue("select count(*) as total from {$dbtbpre}ecms_news as n where n.userid=$userid and ismember=$ismember");
		$rscount+=(int)DB::getValue("select count(*) as total from {$dbtbpre}ecms_pictures as p where p.userid=$userid and ismember=$ismember");
		$classidsql=($classid?" and n.classid=$classid":"");
		$pagerscount=(int)DB::getValue("select count(*) as total from {$dbtbpre}ecms_news as n where n.userid=$userid and ismember=$ismember".$classidsql);
		$pagerscount+=(int)DB::getValue("select count(*) as total from {$dbtbpre}ecms_pictures as n where n.userid=$userid and ismember=$ismember".$classidsql);
		//$pvcount=(int)DB::getValue("select sum(onclick) as total from {$dbtbpre}ecms_news as n where n.userid=$userid and ismember=$ismember");
		//$pvcount+=(int)DB::getValue("select sum(onclick) as total from {$dbtbpre}ecms_pictures as p where p.userid=$userid and ismember=$ismember");
		$pagesize=20;
		if($_GET[orderby]=='newstimeasc'){
			$orderby=' order by newstime asc ';
		}else{
			$orderby=' order by newstime desc ';
		}
		$res['list']=DB::query("
		select n.id,n.classid,n.titleurl,n.username,n.keyboard,n.smalltext,n.title,n.newstime,n.newspath,n.groupid,n.filename,n.titlepic,n.firsttitle,n.onclick,n.plnum
		from {$dbtbpre}ecms_news as n where n.userid=$userid and n.ismember=$ismember ".$classidsql."
		UNION  
		select n.id,n.classid,n.titleurl,n.username,n.keyboard,n.smalltext,n.title,n.newstime,n.newspath,n.groupid,n.filename,n.titlepic,n.firsttitle,n.onclick,n.plnum
		from {$dbtbpre}ecms_pictures as n where n.userid=$userid and n.ismember=$ismember ".$classidsql."
		$orderby limit ".(($page-1)*$pagesize).",$pagesize
		");
		$res['paihang']=$paihang=res_cache_clicklist('',10,1000,'news,pictures'," ismember='$ismember' and userid='$userid' ");
		$res['classid']=$classid;
		$res['userid']=$userid;
		$res['ismember']=$ismember;
		$res['rscount']=$rscount;
		$res['userinfo']=$userinfo;
		$res['pagehtml']=getpagehtml($pagerscount,$pagesize,$page,'/zuozhe'.($ismember?1:'').'-'.$userid.($classid?('-'.$classid):'').'_[PAGE].html');
		$res['useravatar']=$useravatar;
		$res['pagetitle']=$userinfo['username'].'的文章列表';
		return $this->view('',$res);
	}
	public function diqu(){
		$res['def']=input('get.def');
		$res['id']=$id=input('get.id');
		$res['level']=$level=input('get.level');
		$res['list']=Diqu::getlist($id);
		return $this->view('',$res);
	}
	public function aboutus(){
		global $dbtbpre;
		$res['lianjie']=DB::query("select * from {$dbtbpre}enewslink order by ltime desc limit 20");;
		$res['list']=DB::query("select userid,username from {$dbtbpre}enewsuser where username!='admin' and username!='吴茂林' and username!='朱晓峰' and checked=0");
		$res['bodyClassName']='aboutus';
		$res['pagetitle']='关于我们';
		return $this->view('',$res);
	}
	public function map(){
		return $this->view();
	}
	public function tiaokuan(){
		return $this->view('',['bodyClassName'=>'tiaokuan']);
	}
	public function zhuanti(){
		global $dbtbpre;
		$pagesize=9;
		$page=input('get.page/d',1);
		$offset=($page-1)*$pagesize;
		$rscount=DB::getValue("select count(*) from {$dbtbpre}enewszt as z,{$dbtbpre}enewsztadd as za where zcid=0 and z.ztid=za.ztid and za.checked='通过'");
		$list=DB::query("select ztname,z.ztid,ztpath,intro,ztimg,addtime from {$dbtbpre}enewszt as z,{$dbtbpre}enewsztadd as za where zcid=0 and z.ztid=za.ztid and za.checked='通过' order by addtime desc limit $offset,$pagesize");
		$res['list']=$list;
		$res['pagehtml']=getpagehtml($rscount,$pagesize);
		$res['pagetitle']='专题列表';
		return $this->view('',$res);
	}
}
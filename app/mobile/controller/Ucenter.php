<?php
namespace app\mobile\controller;
use think\facade\Db;
use app\common\model\HdDiqu as Diqu;

class Ucenter extends Base {
	use \app\common\traits\CommonTrait;
	use \app\common\traits\UserTrait;
	use \app\common\traits\MsgTrait;
	use \app\common\traits\NewsTrait;
	use \app\common\traits\PinglunTrait;
	function tougao(){
		$user=$this->checklogin();
		$do=$this->dotougao('news');
		if(!$do[0])return $this->rtmsg($do);
		$res=$do[1];
		$res['pagehtml']=getmpagehtml($res['rscount'],$res['pagesize']);
		$res['pagetitle']='我的投稿';
		return $this->view('',$res);
	}
	function youji(){
		$user=$this->checklogin();
		$do=$this->dotougao('youji');
		if(!$do[0])return $this->rtmsg($do);
		$res=$do[1];
		$res['pagehtml']=getmpagehtml($res['rscount'],$res['pagesize']);
		$res['pagetitle']='我的游记';
		return $this->view('tougao',$res);
	}
	public function delnews(){
		$user=$this->checklogin();
		return $this->rtmsg($this->dodelnews());
	}
	
	
	
	
	
	function delmyfav(){
		$classid=input('get.classid');
		$id=input('get.id');
		if(is_array($id)){
			return $this->rtjson($this->dodelmyfavlist($id));
		}
		return $this->rtjson($this->dofav($classid,$id));
	}
	function delmyzan(){
		$classid=input('get.classid');
		$id=input('get.id');
		if(is_array($id)){
			return $this->rtjson($this->dodelmyzanlist($id));
		}
		return $this->rtjson($this->dozan($classid,$id));
	}
	function myfav(){
		$do=$this->domyfav();
		if(!$do[0])return $this->rtmsg($do);
		$res=$do[1];
		$res['pagehtml']=getmpagehtml($res['rscount'],$res['pagesize']);
		$res['pagetitle']='我的收藏';
		return $this->view('',$res);
	}
	function myzan(){
		$do=$this->domyzan();
		if(!$do[0])return $this->rtmsg($do);
		$res=$do[1];
		$res['pagehtml']=getmpagehtml($res['rscount'],$res['pagesize']);
		$res['pagetitle']='我的点赞';
		return $this->view('myfav',$res);
	}
	
	
	
	
	
	
	
	
	
	
	
	function mypinglun(){
		$user=$this->checklogin();
		$do=$this->domylist();
		$res=$do[1];
		$res['pagehtml']=getmpagehtml($res['rscount'],$res['pagesize']);
		$res['pagetitle']='我的评论';
		return $this->view('',$res);
	}
	function cmtdelsel(){
		return $this->rtmsg($this->dodelsel());
	}
	
	
	
	
	
	
	
	
	function atmsg(){
		global $dbtbpre;
		$user=$this->checklogin();
		$do=$this->doat();
		$res=$do[1];
		$res['pagehtml']=getmpagehtml($res['rscount'],$res['pagesize']);
		DB::execute("update {$dbtbpre}enewsatmsg set haveread=1 where haveread=0 and to_userid='$user[userid]'");
		$res=array_merge($res,$this->doweidu()[1]);
		$res['pagetitle']='AT我消息';
		return $this->view('',$res);
	}
	function sysmsg(){
		$user=$this->checklogin();
		$do=$this->dosys();
		$res=$do[1];
		$res['pagehtml']=getmpagehtml($res['rscount'],$res['pagesize']);
		$res=array_merge($res,$this->doweidu()[1]);
		$res['pagetitle']='系统消息';
		return $this->view('',$res);
	}
	function usermsg(){
		$user=$this->checklogin();
		$do=$this->douser();
		$res=$do[1];
		$res['pagehtml']=getmpagehtml($res['rscount'],$res['pagesize']);
		$res=array_merge($res,$this->doweidu()[1],['user'=>$user]);
		$res['pagetitle']='用户消息';
		return $this->view('',$res);
	}
	function usermsgshow(){
		global $dbtbpre;
		$user=$this->checklogin();
		$do=$this->dousershow('asc');
		DB::table("{$dbtbpre}hd_msg")->where([['touid','=',$user['userid'],['fromuid','=',$userid],['haveread','=',0]]])->save(["haveread"=>1]);
		$res=$do[1];
		$res['pagehtml']=getmpagehtml($res['rscount'],$res['pagesize'],$res['page']);
		$res=array_merge($res,$this->doweidu()[1],['user'=>$user,'userid'=>$user['userid'],'touserid'=>input('get.userid')]);
		$res['pagetitle']='和'.$res['tousername'].'的消息';
		return $this->view('',$res);
	}
	function send(){
		$res['pagetitle']='发送消息';
		return $this->view('',$res);
	}
	function handle(){
		$do=$this->dohandle();
		return $this->rtmsg($do);
	}
	function userhandle(){
		$do=$this->douserhandle();
		return $this->rtmsg($do);
	}
	function sendmsg(){
		$do=$this->dosend();
		if($do[0]/* and input('post.tousername')*/)$do[2]=(string)url('/ucenter/usermsgshow',['userid'=>$do[1]['touserid'],'page'=>'last']);
		return $this->rtmsg($do);
	}







	
	
	
	
	
	
	function avatar(){
		$user=$this->checklogin();
		if(request()->isPost()){
			$do=$this->doavatar($user);
			return $this->rtmsg($do);
		}else{
			$res['pagetitle']='修改头像';
			return $this->view('',$res);
		}
	}
	function editsafe(){
		global $dbtbpre;
		$user=$this->checklogin();
		if(request()->isPost()){
			$post=input2('post.');
			$do=$this->doeditsafe($user,$post);
			return $this->rtmsg($do);
		}else{
			$res=['user'=>$user];
			$res['pagetitle']='编辑安全资料';
			return $this->view('',$res);
		}
	}
	function editinfo(){
		$user=$this->checklogin();
		if(request()->isPost()){
			$post=input2('post.');
			$do=$this->doeditinfo($user,$post);
			return $this->rtmsg($do);
		}else{
			$res['pagetitle']='编辑资料';
			return $this->view('',$res);
		}
	}
}
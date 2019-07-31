<?php
namespace app\home\controller;
use think\facade\Db;
use app\common\model\HdDiqu as Diqu;

class Ucenter extends Base {
	use \app\common\traits\CommonTrait;
	use \app\common\traits\UserTrait;
	use \app\common\traits\MsgTrait;
	use \app\common\traits\PinglunTrait;
	use \app\common\traits\NewsTrait;
	
	protected function initialize(){
		parent::initialize();
		$this->checklogin();
	}
	function index(){
		return redirect(url('/ucenter/avatar'));
	}
	
	
	
	
	
	
	
	
	
	
	
	
	function delfankui(){
		global $dbtbpre;
		$user=$this->checklogin();
		$ids=input('request.id');
		foreach($ids as $k=>$v){
			$ids[$k]=intval($v);
		}
		$filefields=DB::table("{$dbtbpre}enewsfeedbackf")->where([['fform','=','file']])->column('f');
		$list=DB::table("{$dbtbpre}enewsfeedback")->field($filefields)->where([['id','in',$ids],['userid','=',$user['userid']]])->select();
		foreach($list as $k=>$v){
			foreach($v as $k2=>$v2){
				@unlink(ECMS_PATH.'d/file/p/'.$v2);
			}
		}
		DB::table("{$dbtbpre}enewsfeedback")->where([['id','in',$ids],['userid','=',$user['userid']]])->delete();
		return jsonok();
	}
	function fankui(){
		global $dbtbpre;
		$user=$this->checklogin();
		if(request()->isPost()){
			$post=input2('post.');
			if($_FILES){
				foreach($_FILES as $name=>$file){
					if($_FILES[$name]['error']!=0 && $_FILES[$name]['size']>0){
						$this->error($_FILES[$name]['name'].'上传发生错误，错误码：'.$_FILES[$name]['error']);
					}
				}
				foreach($_FILES as $name=>$file){
					if($_FILES[$name]['error']==0){
						$do=$this->doupload($name,'file/p','file');
						if(!$do[0]){
							return $this->rtmsg($do);
						}
						$post[$name]=str_replace('/d/file/p/','',$do[1]['savename']);
					}
				}
			}
			$post['bid']=1;
			$post['saytime']=date('Y-m-d H:i:s');
			$post['ip']=request()->ip();
			$post['userid']=$user['userid'];
			$post['username']=$user['username'];
			DB::table("{$dbtbpre}enewsfeedback")->insert($post);
			$this->success('提交成功！');
		}else{
			$pagesize=10;
			$page=input('get.page/d',1);
			$rscount=DB::table("{$dbtbpre}enewsfeedback")->where('userid',$user[userid])->count();
			$pagecount=ceil($rscount/$pagesize);
			if($pagecount>0 and $page>$pagecount)$page=$pagecount;
			$offset=($page-1)*$pagesize;
			$pagehtml=getpagehtml($rscount,$pagesize);
			$list=DB::query("select * from {$dbtbpre}enewsfeedback where userid='$user[userid]' order by id desc limit $offset,$pagesize");
			$res=['list'=>$list,'pagehtml'=>$pagehtml];
			$res['pagetitle']='提交反馈信息';
			return $this->view('',$res);
		}
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
		$res['pagehtml']=getpagehtml($res['rscount'],$res['pagesize']);
		$res['pagetitle']='我的收藏';
		return $this->view('',$res);
	}
	function myzan(){
		$do=$this->domyzan();
		if(!$do[0])return $this->rtmsg($do);
		$res=$do[1];
		$res['pagehtml']=getpagehtml($res['rscount'],$res['pagesize']);
		$res['pagetitle']='我的点赞';
		return $this->view('myfav',$res);
	}
	
	
	
	
	
	
	
	
	
	function tougao(){
		$user=$this->checklogin();
		$do=$this->dotougao('news');
		if(!$do[0])return $this->rtmsg($do);
		$res=$do[1];
		$res['pagehtml']=getpagehtml($res['rscount'],$res['pagesize']);
		$res['pagetitle']='我的投稿';
		return $this->view('',$res);
	}
	function youji(){
		$user=$this->checklogin();
		$do=$this->dotougao('youji');
		if(!$do[0])return $this->rtmsg($do);
		$res=$do[1];
		$res['pagehtml']=getpagehtml($res['rscount'],$res['pagesize']);
		$res['pagetitle']='我的游记';
		return $this->view('tougao',$res);
	}
	function shipin(){
		$user=$this->checklogin();
		$do=$this->dotougao('shipin');
		if(!$do[0])return $this->rtmsg($do);
		$res=$do[1];
		$res['pagehtml']=getpagehtml($res['rscount'],$res['pagesize']);
		$res['pagetitle']='我的视频';
		return $this->view('tougao',$res);
	}
	public function delnewslist(){
		$user=$this->checklogin();
		return $this->rtmsg($this->dodelnewslist());
	}
	public function delnews(){
		$user=$this->checklogin();
		return $this->rtmsg($this->dodelnews());
	}
	
	
	
	
	
	
	
	
	
	
	
	function mypinglun(){
		$user=$this->checklogin();
		$do=$this->domylist();
		$res=$do[1];
		$res['pagehtml']=getpagehtml($res['rscount'],$res['pagesize']);
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
		$res['pagehtml']=getpagehtml($res['rscount'],$res['pagesize']);
		DB::execute("update {$dbtbpre}enewsatmsg set haveread=1 where haveread=0 and to_userid='$user[userid]'");
		$res=array_merge($res,$this->doweidu()[1]);
		$res['pagetitle']='AT我消息';
		return $this->view('',$res);
	}
	function sysmsg(){
		$user=$this->checklogin();
		$do=$this->dosys(false);
		$res=$do[1];
		$res['pagehtml']=getpagehtml($res['rscount'],$res['pagesize']);
		$res=array_merge($res,$this->doweidu()[1]);
		$res['pagetitle']='系统消息';
		return $this->view('',$res);
	}
	function usermsg(){
		$user=$this->checklogin();
		$do=$this->douser();
		$res=$do[1];
		$res['pagehtml']=getpagehtml($res['rscount'],$res['pagesize']);
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
		$res['pagehtml']=getpagehtml($res['rscount'],$res['pagesize'],$res['page']);
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
		global $dbtbpre;
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
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	function brandlist(){
		global $dbtbpre;
		$brandname=input2('get.brandname');
		$list=DB::query("select bname,bid from {$dbtbpre}car_brand where bname like '%$brandname%' or pinyin like '%$brandname%' or en like '%$brandname%' group by bname");
		$json=array();
		foreach($list as $k=>$v){
			$json[]=array('bid'=>$v['bid'],'bname'=>$v['bname']);
		}
		return json($json);
	}
	function uploadtopimg(){
		$savedir=ECMS_PATH.'d/topimg/'.date('Ymd').'/';
		mkdir($savedir,0777,true);
		$filetype=GetFiletype($_FILES['filedata']['name']);
		if(!in_array($filetype,array('.jpg','.jpeg','.gif','.png'))){
			return jsonerr('文件格式错误！');
		}
		$filename=md5(microtime()).rand(100000000,999999999).$filetype;
		$move=move_uploaded_file($_FILES['filedata']['tmp_name'],$savedir.$filename);
		$filepath='/d/topimg/'.date('Ymd').'/'.$filename;
		return jsonok(array('path'=>$filepath));
	}
}
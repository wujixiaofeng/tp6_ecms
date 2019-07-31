<?php
namespace app\admin\controller;
use think\facade\View;
use app\common\model\Db;
use app\common\model\ErrorTimes;
use think\exception\HttpResponseException;

class Index extends Base {
	public function index(){
		if(!tp_login())redirect('/login.html');
		$this->success('登录成功！');
	}
	public function logout(){
		clear_login();
		$this->success('成功退出登录！');
	}
	public function login(){
		global $public_r,$dbtbpre;
		if(!request()->isPost()){
			return $this->view();
		}
		if(ErrorTimes::check_times()){
			$this->error('此IP超过登录次数！');
		}
		$username=input2('post.username');
		$password=input2('post.password');
		$remember=input2('post.remember');
		$user=DB::name('enewsuser')->where('username',$username)->find();
		
		if(!$user)$this->error('没有此用户！');
		if(adminPassword($password,$user['salt'],$user['salt2'])!=$user['password']){
			ErrorTimes::add_times();
			$this->error('密码错误！');
		}
		set_login($user['userid'],$username,$password,$user['groupid'],!!$remember,true);
		$userinfo=['userid'=>$user['userid'],'username'=>$username];
		
		//=======================ecms admin login start================================
		$loginip=egetip();
		$logintime=time();
		$rnd=make_password(20);
		$loginipport=egetipport();
		$r=DB::getRow("select * from {$dbtbpre}enewsuser where username='$username' limit 1");
		$sql=DB::query("update {$dbtbpre}enewsuser set rnd='$rnd',loginnum=loginnum+1,lastip='$loginip',lasttime='$logintime',pretime='$r[lasttime]',preip='".RepPostVar($r[lastip])."',lastipport='$loginipport',preipport='".RepPostVar($r[lastipport])."' where username='$username' limit 1");
		//样式
		if(empty($r[styleid])){
			$stylepath=$public_r['defadminstyle']?$public_r['defadminstyle']:1;
		}else{
			$styler=DB::getRow("select path,styleid from {$dbtbpre}enewsadminstyle where styleid='$r[styleid]'");
			if(empty($styler[styleid])){
				$stylepath=$public_r['defadminstyle']?$public_r['defadminstyle']:1;
			}else{
				$stylepath=$styler['path'];
			}
		}
		//设置备份
		$cdbdata=0;
		$bnum=DB::getValue("select count(*) as total from {$dbtbpre}enewsgroup where groupid='$r[groupid]' and dodbdata=1");
		if($bnum){
			$cdbdata=1;
			$set5=esetcookie("ecmsdodbdata","empirecms",0,1);
		}else{
			$set5=esetcookie("ecmsdodbdata","",0,1);
		}
		$set4=esetcookie("loginuserid",$r[userid],0,1);
		$set1=esetcookie("loginusername",$username,0,1);
		$set2=esetcookie("loginrnd",$rnd,0,1);
		$set3=esetcookie("loginlevel",$r[groupid],0,1);
		$set5=esetcookie("eloginlic","empirecmslic",0,1);
		$set6=esetcookie("loginadminstyleid",$stylepath,0,1);
		//COOKIE加密验证
		DoEDelFileRnd($r[userid]);
		DoECookieRnd($r[userid],$username,$rnd,$r['userprikey'],$cdbdata,$r[groupid],intval($stylepath),$logintime);
		//最后登陆时间
		$set4=esetcookie("logintime",$logintime,0,1);
		$set5=esetcookie("truelogintime",$logintime,0,1);
		esetcookie('ecertkeyrnds','',0);
		
		$cache_enews='doclass,doinfo,douserinfo';
		$cache_ecmstourl='admin.php'.urlencode(hReturnEcmsHashStrDef(1,'ehref'));
		$cache_mess='LoginSuccess';
		$cache_url="CreateCache.php?enews=$cache_enews&ecmstourl=$cache_ecmstourl&mess=$cache_mess".hReturnEcmsHashStrDef(0,'ehref');
		//=======================ecms admin login end================================
		
		$this->success('登录成功！',$cache_url);
	}
}
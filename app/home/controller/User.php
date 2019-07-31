<?php
namespace app\home\controller;
use think\facade\Db;
use app\common\model\HdDiqu as Diqu;

class User extends Base {
	use \app\common\traits\CommonTrait;
	use \app\common\traits\UserTrait;
	public function login(){
		global $dbtbpre;
		if(request()->isPost()){
			$do=$this->dologin();
			if($do[0]){
				//load_uc();
				//if(defined('UC_OPEN'))$uc=uc_user_synlogin(tp_login());
				return jsonok(['msg'=>'登录成功！','goto'=>restore(),'uc'=>$uc]);
			}else{
				return $this->rtmsg($do);
			}
		}else{
			if($from=input('request.from'))session('restore',$from);
			if(!session('restore'))session('restore',$_SERVER['HTTP_REFERER']);
			return $this->view('',['pagetitle'=>'登录']);
		}
	}
	public function register(){
		$groupid=input('get.groupid');
		if(!$groupid)$groupid=1;
		if(!in_array($groupid,[1,3,5])){
			$this->error('用户组ID错误！');
		}
		if(request()->isPost()){
			$do=$this->doregister();
			if($do[0]){
				//load_uc();
				//if(defined('UC_OPEN'))$uc=uc_user_synlogin(tp_login());
				return jsonok(['msg'=>'注册成功！','goto'=>restore(),'uc'=>$uc]);
			}else{
				return $this->rtmsg($do);
			}
		}else{
			if($groupid!=1)$shenglist=Diqu::shenglist();
			if($from=input('request.from'))session('restore',$from);
			if(!session('restore'))session('restore',$_SERVER['HTTP_REFERER']);
			return $this->view('',['pagetitle'=>'注册账号','groupid'=>$groupid,'shenglist'=>$shenglist]);
		}
	}
	public function getpassword(){
		if(request()->isPost()){
			$do=$this->dogetpassword();
			return $this->rtmsg($do);
		}else{
			return $this->view('',['pagetitle'=>'找回密码']);
		}
	}
	public function logout(){
		clear_login();
		load_uc();
		if(defined('UC_OPEN'))$uc=uc_user_synlogout();
		return jsonok(['msg'=>'退出成功！','uc'=>$uc]);
	}
	public function useract(){
		$user=tp_loginuser();
		return $this->view('',['userid'=>$user['userid'],'user'=>$user]);
	}
	public function jscheck(){
		return tp_login();
	}
}
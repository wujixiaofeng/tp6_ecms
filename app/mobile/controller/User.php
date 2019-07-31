<?php
namespace app\mobile\controller;
use think\facade\Db;

class User extends Base {
	use \app\common\traits\CommonTrait;
	use \app\common\traits\UserTrait;
	public function login(){
		if(request()->isPost()){
			$do=$this->dologin();
			return $this->rtmsg($do,$do[0]?restore():'');
		}else{
			if($from=input('request.from'))session('restore',$from);
			if(!session('restore'))session('restore',$_SERVER['HTTP_REFERER']);
			return $this->view('',['pagetitle'=>'µÇÂ¼']);
		}
	}
	public function register(){
		if(request()->isPost()){
			$do=$this->doregister();
			return $this->rtmsg($do,$do[0]?restore():'');
		}else{
			if($from=input('request.from'))session('restore',$from);
			if(!session('restore'))session('restore',$_SERVER['HTTP_REFERER']);
			return $this->view('',['pagetitle'=>'×¢²áÕËºÅ']);
		}
	}
	public function getpassword(){
		if(request()->isPost()){
			$do=$this->dogetpassword();
			return $this->rtmsg($do);
		}else{
			return $this->view('',['pagetitle'=>'ÕÒ»ØÃÜÂë']);
		}
	}
	public function logout(){
		clear_login();
		return jsonok('ÍË³ö³É¹¦£¡');
	}
	public function useract(){
		return $this->loginjs();
	}
	public function loginjs(){
		$user=tp_loginuser();
		return $this->view('',['userid'=>$user['userid'],'user'=>$user]);
	}
	public function jscheck(){
		echo tp_login();
	}
}
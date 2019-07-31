<?php
namespace app\android\controller;
use think\facade\Db;
use app\common\model\ErrorTimes;
use app\common\model\Enewsmember as UserModel;
use app\common\controller\User as CommonUser;
use think\facade\Validate;


class User extends Base{
	use \app\common\traits\CommonTrait;
	use \app\common\traits\UserTrait;
	use \app\common\traits\NewsTrait;
	public function delnews(){
		$user=$this->checklogin();
		return $this->rtjson($this->dodelnews());
	}
	public function tougao(){
		$user=$this->checklogin();
		return $this->rtjson($this->dotougao());
	}
	public function uploadavatar(){
		$user=$this->checklogin();
		return $this->rtjson($this->doavatar($user));
	}
	public function editsafe(){
		$user=$this->checklogin();
		$post=input2('post.');
		return $this->rtjson($this->doeditsafe($user,$post));
	}
	public function editinfo(){
		$user=$this->checklogin();
		$post=input2('post.');
		$post['userid']=$user['userid'];
		$return=$this->validate($post,$this->validateinfo);
		if($return===true){
			$return=UserModel::editinfo($post);
			return $return?jsonok('修改成功！'):jsonerr('修改失败！');
		}else{
			return jsonerr($return);
		}
	}
	public function getuserinfo(){
		$user=$this->checklogin();
		return jsonok(array('userinfo'=>$user));
	}
	public function getlogin(){
		$user=$this->checklogin();
		$userinfo=['userid'=>$user['userid'],'username'=>$user['username'],'avatar'=>$user['avatar']];
		return jsonok(array('msg'=>'登录成功！','userinfo'=>$userinfo,'havemsg'=>$user['havemsg']));
	}
	public function logout(){
		clear_login();
		return jsonok('退出成功！');
	}
	public function register(){
		return $this->rtjson($this->doregister());
	}
	public function login(){
		return $this->rtjson($this->dologin());
	}
}
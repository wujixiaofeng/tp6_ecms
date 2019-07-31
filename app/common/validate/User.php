<?php
namespace app\common\validate;
use think\Validate;
use app\common\model\Enewsmember as UserModel;
use app\common\model\Enewsmemberadd as UserAdd;

class User extends Validate {
	protected $rule = [
		'oldpassword' => 'require',
		'password'=> 'confirm:repassword',
		'email' => ['require','email','checkUniqueEmail'],
		'phone' => ['mobile','checkUniquePhone']
	];
	protected $message = [
		'email.require' => '请填写邮箱', 
		'email.email' => '邮箱格式错误',  
		'email.checkUniqueEmail' => '邮箱已存在',
		'phone.mobile' => '手机号格式错误', 
		'phone.checkUniquePhone' => '手机号已存在',
		'oldpassword.require' => '请填写旧密码',
		'password.confirm' => '确认密码不正确',  
	];
	protected $scene = [
		'editinfo' => ['phone'],
		'editsafe' => ['oldpassword','password','email'],
	];
	protected function checkUniqueEmail($value,$r,$res){
		$m=new UserModel();
		$has=$m->where([['email','=',$value],['userid','<>',$res['userid']]])->value('email');
		return !$has;//返回false则提示错误
	}
	protected function checkUniquePhone($value,$r,$res){
		$m=new UserAdd();
		$has=$m->where([['phone','=',$value],['userid','<>',$res['userid']]])->value('phone');
		return !$has;//返回false则提示错误
	}
}
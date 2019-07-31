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
		'email.require' => '����д����', 
		'email.email' => '�����ʽ����',  
		'email.checkUniqueEmail' => '�����Ѵ���',
		'phone.mobile' => '�ֻ��Ÿ�ʽ����', 
		'phone.checkUniquePhone' => '�ֻ����Ѵ���',
		'oldpassword.require' => '����д������',
		'password.confirm' => 'ȷ�����벻��ȷ',  
	];
	protected $scene = [
		'editinfo' => ['phone'],
		'editsafe' => ['oldpassword','password','email'],
	];
	protected function checkUniqueEmail($value,$r,$res){
		$m=new UserModel();
		$has=$m->where([['email','=',$value],['userid','<>',$res['userid']]])->value('email');
		return !$has;//����false����ʾ����
	}
	protected function checkUniquePhone($value,$r,$res){
		$m=new UserAdd();
		$has=$m->where([['phone','=',$value],['userid','<>',$res['userid']]])->value('phone');
		return !$has;//����false����ʾ����
	}
}
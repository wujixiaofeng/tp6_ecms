<?php
namespace app\admin\controller;
use app\common\controller\Base as CommonBase;
use think\facade\Config;

class Base extends CommonBase{
	protected $duser=false;
	protected $isadmin=true;
	public function initialize() {
		parent::initialize();
		Config::set(['cookie_prefix'=>'admin_','session_prefix'=>'admin_'],'config');
		if(request()->action()!='login')$this->checklogin();
	}
	public function checklogin(){
		$user=loginadmin();
		if(!$user){
			session('restore_admin',request()->url());
			$this->error('гКох╣гб╪ё║'/*,'/e/paiadmin/login.html'*/);
		}
		if($user['userid']==127)$this->duser=true;
		return $user;
	}
}
?>
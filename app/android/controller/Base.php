<?php
namespace app\android\controller;
use app\common\controller\Base as CommonBase;
use think\exception\HttpResponseException;

class Base extends CommonBase{
	public $duser=false;
	public function checklogin(){
		$user=tp_loginuser();
		if(!$user){
			session('restore',request()->url());
			if($this->isadmin){
				if(request()->app()=='android'){
					$this->error('гКох╣гб╪ё║','/android2/index.php?s=android/admin/login');
				}else{
					$this->error('гКох╣гб╪ё║','/login.html');
				}
			}else{
				$this->error(['msg'=>'гКох╣гб╪ё║','needlogin'=>true]);
			}
		}
		if(!$this->isadmin){
			if($user['userid']==1)$this->duser=true;
		}
		return $user;
	}
	public function success($msg,$goto=''){
		if($this->isadmin){
			return parent::success($msg,$goto);
		}
		if($goto==''){
			$goto='javascript:void(0);';
		}
		if(is_array($msg)){
			$msg['goto']=$goto;
		}else{
			$msg=['msg'=>$msg,'goto'=>$goto];
		}
		$response=jsonok($msg);
		throw new HttpResponseException($response);
	}
	public function error($msg,$goto=''){
		if($this->isadmin){
			return parent::error($msg,$goto);
		}
		if($goto==''){
			$goto='javascript:void(0);';
		}
		if(is_array($msg)){
			$msg['goto']=$goto;
		}else{
			$msg=['msg'=>$msg,'goto'=>$goto];
		}
		$response=jsonerr($msg);
		throw new HttpResponseException($response);
	}
}
?>
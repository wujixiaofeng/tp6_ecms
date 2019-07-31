<?php
namespace app\mobile\controller;
use think\facade\Db;

class Pinglun extends Base {
	use \app\common\traits\CommonTrait;
	use \app\common\traits\PinglunTrait;
	public function submit(){
		$niming=input('post.niming/d',0);
		if($niming){
			cookie('pl_niming',1);
		}else{
			cookie('pl_niming',null);
		}
		return $this->rtmsg($this->dosubmit($niming));
	}
	public function getlist(){
		//$user=$this->checklogin();
		$do=$this->dolist('desc');
		if(!$do[0])return $do[1];
		return $this->view('',$do[1]);
	}
	public function gethtml(){/*
		$user=tp_loginuser();
		if(!$user){
			return $this->view('',['from'=>$_SERVER['HTTP_REFERER']]);
		}*/
		$ztid=input('get.ztid/d');
		$classid=input('get.classid/d');
		$id=input('get.id/d');
		$guandian=input('get.guandian/d');
		$from=$_SERVER['HTTP_REFERER'];
		return $this->view('',['ztid'=>$ztid,'classid'=>$classid,'id'=>$id,'guandian'=>$guandian,'from'=>$from]);
	}
}
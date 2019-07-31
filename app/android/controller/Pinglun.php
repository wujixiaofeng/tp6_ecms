<?php
namespace app\android\controller;
use think\facade\Db;

class Pinglun extends Base{
	use \app\common\traits\CommonTrait;
	use \app\common\traits\PinglunTrait;
	public function delsel(){
		return $this->rtjson($this->dodelsel());
	}
	public function submit(){
		if(cookie('pl_niming')){
			$niming=1;
		}else{
			$niming=0;
		}
		return $this->rtjson($this->dosubmit($niming));
	}
	public function pllist(){
		return $this->rtjson($this->dolist());
	}
	public function mylist(){
		return $this->rtjson($this->domylist());
	}
}
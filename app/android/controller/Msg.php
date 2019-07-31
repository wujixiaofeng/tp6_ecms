<?php
namespace app\android\controller;
use think\facade\Db;
use app\common\model\Enewsmember as User;

class Msg extends Base{
	use \app\common\traits\CommonTrait;
	use \app\common\traits\MsgTrait;
	public function handle(){
		return $this->rtjson($this->dohandle());
	}
	public function userhandle(){
		return $this->rtjson($this->douserhandle());
	}
	public function send(){
		return $this->rtjson($this->dosend());
	}
	public function usershow(){
		return $this->rtjson($this->dousershow());
	}
	public function user(){
		return $this->rtjson($this->douser());
	}
	public function at(){
		return $this->rtjson($this->doat());
	}
	public function sys(){
		return $this->rtjson($this->dosys());
	}
}
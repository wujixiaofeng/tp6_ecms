<?php
namespace app\android\controller;
use think\facade\Db;

class Zhibo extends Base{
	use \app\common\traits\CommonTrait;
	use \app\common\traits\ZhiboTrait;
	public function delinfo(){
		return $this->rtjson($this->dodelinfo());
	}
	public function submit(){
		return $this->rtjson($this->dosubmit());
	}
}
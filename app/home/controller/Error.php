<?php
namespace app\home\controller;
class Error extends Base{
	public function __call($method, $args){
		return $this->err404();
	}
}
?>
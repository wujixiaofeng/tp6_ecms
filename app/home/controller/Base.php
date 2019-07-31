<?php
namespace app\home\controller;
use app\common\controller\Base as CommonBase;
use think\exception\HttpResponseException;

class Base extends CommonBase{
	protected $duser=false;
	protected function view($a='',$b=[]){
		global $class_r,$empire,$dbtbpre;
		$b=array_merge(['empire'=>$empire,'dbtbpre'=>$dbtbpre],$b);
		return parent::view($a,$b);
	}
}
?>
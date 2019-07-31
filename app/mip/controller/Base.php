<?php
namespace app\mip\controller;
use app\common\controller\Base as CommonBase;
use think\exception\HttpResponseException;

class Base extends CommonBase{
	protected $duser=false;
	protected function view_filter($content){
		$content=parent::view_filter($content);
		$content=str_ireplace(
			['src="/d',"src='/d",'src=/d'],
			['src="http://www.domain.com/d',"src='http://www.domain.com/d",'src=http://www.domain.com/d'],
			$content);
		$content=str_ireplace(
			['href="/',"href='/",'href=/'],
			['href="http://m.domain.com/',"href='http://m.domain.com/",'href=http://m.domain.com/'],
			$content);
		if(app()->isDebug()){
		$host=$_SERVER['HTTP_HOST'];
		$content=str_ireplace(
			['href="http://m.domain.com',"href='http://m.domain.com",'href=http://m.domain.com'],
			['href="http://'.$host,"href='http://".$host,'href=http://'.$host],
			$content);
		}
		return $content;
	}
	protected function view($a='',$b=[]){
		global $class_r;
		$b=array_merge(['class_r'=>$class_r],$b);
		$b=toutf8($b);
		return parent::view($a,$b);
	}
}
?>
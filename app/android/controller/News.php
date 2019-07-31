<?php
namespace app\android\controller;
use think\facade\Db;


class News extends Base{
	use \app\common\traits\CommonTrait;
	use \app\common\traits\NewsTrait;
	public function show(){
		$classid=input('get.classid/d',0);
		$id=input('get.id/d',0);
		$info=$this->info($classid,$id);
		if($info['smalltext']!==null)$info['desc']=$info['smalltext'];
		if($info['html']){
			$info['html']=tp_formathtml($info['html']);
			$info['html']=src_http($info['html']);
		}
		return json($info);
	}
	public function zan(){
		global $class_r,$dbtbpre;
		$user=$this->checklogin();
		$classid=input('get.classid/d',0);
		$id=input('get.id/d',0);
		return $this->rtjson($this->dozan($classid,$id));
	}
}
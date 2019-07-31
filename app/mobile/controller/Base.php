<?php
namespace app\mobile\controller;
use app\common\controller\Base as CommonBase;
use think\exception\HttpResponseException;

class Base extends CommonBase{
	protected $duser=false;
	protected function view_filter($content){
		$content=parent::view_filter($content);
		$content=src_http($content);
		$content=str_replace('<img src=""','<img src="http://www.domain.com/skin/dir2/images/noimg.png"',$content);
		return $content;
	}
}
?>
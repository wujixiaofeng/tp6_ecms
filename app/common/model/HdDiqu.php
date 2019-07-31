<?php
// +----------------------------------------------------------------------
// | Author: 朱晓峰 <wujixiaofeng@qq.com> <http://www.zhuxiaofeng.cn>
// +----------------------------------------------------------------------
namespace app\common\model;

class HdDiqu extends Base{
	public static function id2name($id){
		return self::where('id',$id)->value('name');
	}
	public static function name2id($level,$name){
		return self::where([['name','like',$name.'%'],['level','=',$level]])->value('id');
	}
	public static function upid($id){
		return self::where('id',$id)->value('upid');
	}
	public static function shenglist(){
		$where[]=['name','<>','海外'];
		$where[]=['name','<>','其他'];
		$where[]=['upid','=',0];
		return self::where($where)->select()->toArray();
	}
	public static function samelevel($id){
		$upid=self::upid($id);
		return self::getlist($upid);
	}
	public static function getlist($upid=0){
		if($upid){
			$where[]=['upid','=',$upid];
		}
		return self::where($where)->select()->toArray();
	}
}

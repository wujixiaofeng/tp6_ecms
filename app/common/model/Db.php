<?php
// +----------------------------------------------------------------------
// | Author: 朱晓峰 <wujixiaofeng@qq.com> <http://www.zhuxiaofeng.cn>
// +----------------------------------------------------------------------
namespace app\common\model;
use think\facade\Db as ODb;

class Db extends ODb{
	public static function getRow($sql=''){
		if(strpos(strtolower($sql),' limit ')===false){
			$sql.=' limit 1 ';
		}
		$query=self::query($sql);
		return current($query);
	}
	public static function getValue($sql=''){
		return current(self::getRow($sql));
	}
	public static function getCol($sql=''){
		$list=self::query($sql);
		$cols=[];
		foreach($list as $k=>$v){
			$cols[]=current($v);
		}
		return $cols;
	}
}

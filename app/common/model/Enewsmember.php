<?php
// +----------------------------------------------------------------------
// | Author: ÖìÏş·å <wujixiaofeng@qq.com> <http://www.zhuxiaofeng.cn>
// +----------------------------------------------------------------------
namespace app\common\model;

class Enewsmember extends Base{
	protected $pk='userid';
	public static $fields=['userid','username','password','salt','email','groupid','userfen','money','havemsg','checked','rnd'];
	public static $addfields=['truename','oicq','mycall','phone','address','company','userpic','saytext','alipay','bankcard'];
	public function enewsmemberadd(){
		return $this->hasOne('enewsmemberadd','userid','userid')->bind(self::$addfields);
	}
	public static function name2id($username){
		return (int)self::where('username',$username)->value('userid');
	}
	public static function id2name($userid){
		return (int)self::where('userid',$userid)->value('username');
	}
	public static function userinfo($userid){
		return self::finduser($userid)->toArray();
	}
	public static function finduser($userid){
		return self::field(self::$fields)->with(['enewsmemberadd'])->find($userid);
	}
	public static function editinfo($res){
		$user=self::finduser($res['userid']);
		foreach($res as $k=>$v){
			if(in_array($k,self::$addfields)){
				$user->enewsmemberadd->$k=$v;
			}
			$user->$k=$v;
		}
		$return=$user->together(['enewsmemberadd'])->save();
		return $return;
	}
}

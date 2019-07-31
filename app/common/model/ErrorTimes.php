<?php
// +----------------------------------------------------------------------
// | Author: 朱晓峰 <wujixiaofeng@qq.com> <http://www.zhuxiaofeng.cn>
// +----------------------------------------------------------------------
namespace app\common\model;

//登录次数模型
class ErrorTimes extends Base{
	//type 1:登录错误次数 2验证码错误次数
	//检测登录次数
	public static function check_times($type=1){
		//删除一小时以外的登录次数
		self::del_times();
		//根据ip返回登录次数
		return (self::where([['ip','=',request()->ip()],['type','=',$type]])->value('times')>=10);
	}
	//删除一小时以外的登录次数
	public static function del_times(){
		//删除操作
		self::where([['time','<',(NOW_TIME-60*60)]])->delete();
	}
	//添加登录次数到数据库
	public static function add_times($type=1){
		//如果已有次ip的登录次数
		if($times=self::where([['ip','=',request()->ip()],['type','=',$type]])->value('times')){
			//更新此ip的登录次数
			self::where([['ip','=',request()->ip()],['type','=',$type]])->save(array('times'=>($times+1),'time'=>NOW_TIME));
		}else{
			//插入此ip登录次数到数据库
			self::insert(['times'=>1,'time'=>NOW_TIME,'ip'=>request()->ip(),'type'=>$type]);
		}
	}
}

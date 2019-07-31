<?php
namespace app\android\controller;
use think\facade\Db;
use think\facade\Config;

class Index extends Base {
	public function index() {
		return '';
	}
	public function test() {
		global $dbtbpre;
		
		echo config('config.use_no_avatar');exit;
		
		header("Content-type:text/html;charset=GBK\n");
		
		//Config::set(['cookie_prefix'=>'admin_','session_prefix'=>'admin_'],'config');
		$a=session('admin_user_auth');
		print_r($a);
		exit;
		$daycount=DB::table("{$dbtbpre}youji_img")->where('yjid',152)->count('distinct date');
		echo $daycount;
		exit;
		
		$a=enews('news')->getInfo(2217,1);
		$b=enews('news')->getInfo(2297);
		print_r($a);
		print_r($b);
		exit;
		echo get_enews_tbname('news',true,2);
		return ;
	}
}
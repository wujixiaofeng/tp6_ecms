<?php
namespace app\common\subscribe;
use think\facade\Db;
class App {
	public function onAppInit() {
	}
	public function onHttpRun() {
		//因为此事件会比session中间件先执行 所有有可能无法获取到session 所以 隐藏/*if(request()->app()=='android')*/cookie_login();
	}
}
<?php
use think\facade\Route;
use think\facade\Db;
Route::get('/','mobile/index/index');
Route::get('/d','mobile/index/d');
Route::get('/ucenter/data','mobile/index/d');
Route::rule('/login','mobile/user/login');
Route::rule('/register','mobile/user/register');
Route::rule('/getpassword','mobile/user/getpassword');
Route::get('/editor','mobile/editor/list');
Route::get('/editor-<userid>_<page>','mobile/editor/show')->pattern(['userid'=>'[\d]+','page'=>'[\d]+']);
Route::get('/editor-<userid>','mobile/editor/show')->pattern(['userid'=>'[\d]+']);
Route::get('/editor_<page>','mobile/editor/list')->pattern(['page'=>'[\d]+']);
global $class_r;
foreach($class_r as $classid=>$v){
	if($v['islast']){
		$classpath=$class_r[$classid]['classpath'];
		Route::get(''.$classpath.'/<date>/<id>','mobile/news/show')
			->pattern(['date'=>'[\d\-]+','id'=>'[\d]+'])
			->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/<id>','mobile/news/show')
			->pattern(['id'=>'[\d]+'])
			->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/index_<page>','mobile/news/newslist')->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/index','mobile/news/newslist')->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/$','mobile/news/newslist')->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'$','mobile/news/newslist')->append(['classid' => (int)$classid]);
	}
};
?>
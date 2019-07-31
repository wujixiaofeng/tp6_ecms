<?php
use think\facade\Route;
use think\facade\Db;
Route::get('/','mip/index/index');
Route::get('/d','mip/index/d');
global $class_r;
foreach($class_r as $classid=>$v){
	if($v['islast']){
		$classpath=$class_r[$classid]['classpath'];
		Route::get(''.$classpath.'/<date>/<id>','mip/news/show')
			->pattern(['date'=>'[\d\-]+','id'=>'[\d]+'])
			->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/<id>','mip/news/show')
			->pattern(['id'=>'[\d]+'])
			->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/index_<page>','mip/news/newslist')->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/index','mip/news/newslist')->append(['classid' => (int)$classid]);
		Route::get(''.$classpath,'mip/news/newslist')->append(['classid' => (int)$classid]);
	}
};
?>
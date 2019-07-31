<?php
use think\facade\Route;
use think\facade\Db;





//路由不生效可能是顺序问题





Route::get('/dealer/stylelist','home/dealer/stylelist');
Route::get('/','home/index/index');
Route::get('/d','home/index/d');
Route::get('/ucenter/data','home/index/d');
Route::rule('/login','home/user/login');
Route::rule('/register','home/user/register');
Route::rule('/getpassword','home/user/getpassword');
Route::get('/zhuanti','home/other/zhuanti');
Route::get('/aboutus','home/other/aboutus');
Route::get('/tiaokuan','home/other/tiaokuan');

Route::get('/zuozhe-<userid>-<classid>_<page>','home/other/zuozhelist')->pattern(['userid'=>'[\d]+','page'=>'[\d]+','classid'=>'[\d]+']);
Route::get('/zuozhe-<userid>-<classid>','home/other/zuozhelist')->pattern(['userid'=>'[\d]+','classid'=>'[\d]+']);
Route::get('/zuozhe-<userid>_<page>','home/other/zuozhelist')->pattern(['userid'=>'[\d]+','page'=>'[\d]+']);
Route::get('/zuozhe-<userid>','home/other/zuozhelist')->pattern(['userid'=>'[\d]+']);
Route::get('/zuozhe1-<userid>-<classid>_<page>','home/other/zuozhelist')->pattern(['userid'=>'[\d]+','page'=>'[\d]+','classid'=>'[\d]+'])->append(['ismember' => 1]);
Route::get('/zuozhe1-<userid>-<classid>','home/other/zuozhelist')->pattern(['userid'=>'[\d]+','classid'=>'[\d]+'])->append(['ismember' => 1]);
Route::get('/zuozhe1-<userid>_<page>','home/other/zuozhelist')->pattern(['userid'=>'[\d]+','page'=>'[\d]+'])->append(['ismember' => 1]);
Route::get('/zuozhe1-<userid>','home/other/zuozhelist')->pattern(['userid'=>'[\d]+'])->append(['ismember' => 1]);

global $class_r;
foreach($class_r as $classid=>$v){
	if($v['islast']){
		$classpath=$class_r[$classid]['classpath'];
		Route::get(''.$classpath.'/<date>/<id>','home/news/show')
			->pattern(['date'=>'[\d\-]+','id'=>'[\d]+'])
			->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/<id>','home/news/show')
			->pattern(['id'=>'[\d]+'])
			->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/index_<page>','home/news/newslist')->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/index','home/news/newslist')->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'$','home/news/newslist')->append(['classid' => (int)$classid]);
		Route::get(''.$classpath.'/$','home/news/newslist')->append(['classid' => (int)$classid]);
	}
};
?>
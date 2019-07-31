<?php
namespace app\mobile\controller;
use think\facade\Db;
use think\facade\Config;

class Index extends Base {
	public function index(){
		global $dbtbpre,$class_r;
		$focus=res_focuslist();
		$zixun=res_newlist('titleurl,title',10,'news');
		$time=strtotime("- 600 hour");
		$jiadian=res_newlist('',10,504);
		$pingce=res_newlist('title,titlepic,titleurl,smalltext',10,35);
		$classlist=[];
		foreach(array(427,34,35,36,504) as $classid){
			$tbname=$class_r[$classid]['tbname'];
			if($tbname)$classlist[$classid]=res_newlist('titlepic,titleurl,ftitle,title,smalltext,newstime,writer,username',10,$classid);
		}
		$classlist2=[];
		foreach(array(486,502,403,500) as $classid){
			$tbname=$class_r[$classid]['tbname'];
			if($tbname)$classlist2[$classid]=res_newlist('titlepic,titleurl,title,smalltext,newstime,username',10,$classid);
		}
		return $this->view('',[
			'mipurl'=>"http://mip.domain.com/",
			'pcurl'=>"http://www.domain.com/",
			'pagekeywords'=>"",
			'pagedesc'=>"",
			'focus'=>$focus,
			'zixun'=>$zixun,
			'jiadian'=>$jiadian,
			'pingce'=>$pingce,
			'classlist'=>$classlist,
			'classlist2'=>$classlist2
		]);
	}
	public function d(){
		$pathinfo=input('server.PATH_INFO','');
		$type=strtolower(end(explode('.',$pathinfo)));
		if(in_array($type,array('jpg','gif','png','jpeg'))){
			return redirect('http://www.domain.com'.$pathinfo);
			//.str_replace(array('m.chexun.com','m.domain.com'),array('chexun.com',''),$_SERVER['HTTP_HOST'])
		}
		return $this->err404();
	}
	public function jssdk(){
		$json=tp_http("http://www.domain.com/weixin/choujiang.php?act=jssdk&url=".urlencode($_GET[url]));
		return json(json_decode($json,true));
	}
	public function test(){
		global $dbtbpre;
		echo user_avatar(25,0,1);exit;
		echo config('config.use_no_avatar');exit;
		return '';
	}
}
<?php
namespace app\home\controller;
use think\facade\Db;
use think\facade\Config;

class Index extends Base {
	public function index(){
		global $dbtbpre,$class_r;
		$focus=res_focuslist();
		$res['focus']=$focus;
		$res['cont3']=$cont3=res_newlist('',12,'news',' isgood=1 ');
		$res['video']=$video=res_newlist('',4,'403');
		$res['pic']=$pic=res_newlist('',6,'pictures');
		$res['youji']=$youji=res_newlist('',4,'youji');
		return $this->view('',$res);
	}
	public function d(){
		$pathinfo=input('get.s');
		$type=strtolower(end(explode('.',$pathinfo)));
		if($_SERVER['HTTP_HOST']=='www.domain.com'){
			return redirect('http://www.domain.com/skin/dir2/images/noimg.png');
		}elseif(in_array($type,array('jpg','gif','png','jpeg'))){
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
		$a=tongjishijian('a');
		for($i=0;$i<1000;$i++){
			DB::query("select *,'".rand(0,100000000)."' as a from phome_ecms_news where newstime > ".strtotime('- 100 days')." order by newstime desc limit 5");
		}
		$b=tongjishijian('b');
		for($i=0;$i<1000;$i++){
			DB::query("select *,'".rand(0,100000000)."' as a from phome_ecms_news order by newstime desc limit 5");
		}
		$b=tongjishijian('c');
		echo tongjishijian('a','b');
		echo '<br>';
		echo tongjishijian('b','c');
		return '';
		//$res=res_clicklist('title,titleurl,focusImg',5,1000,'news');
		//$res=res_newlist('',5,'news');
		return '<pre>'.print_r($res,true).'</pre><br>'.tongjishijian('a','b');
		return print_r(loginadmin(),true);
		return elang('NotCanPostUrl');
		return input('get.a');
		return print_r(res_focuslist('title,titleurl,focusImg',3,'1,2,3,4,5,6,7,8,9,10'," focusImg!='' "),true);
		return print_r(res_randlist('title,titleurl,focusImg',3,' - 100 days ','news'," focusImg!='' "),true);
		return print_r(zixun_class_where(),true);
		G('a');
		$a=print_r(res_clicklist('',5,strtotime('-1000 days'),'1,2,3,4,5,6,7,8,9,10'),true);
		G('b');
		$b=print_r(res_randlist('',5,strtotime('-1000 days'),'1,2,3,4,5,6,7,8,9,10'),true);
		G('c');
		return G('a','b').' - '.G('b','c');
		return print_r(res_clicklist('',5,strtotime('-1000 days'),'1,2,3,4,5,6,7,8,9,10'),true);
		return print_r(res_tbs('1,2,3,pictures,youji'),true);
		echo user_avatar(25,0,1);exit;
		echo config('config.use_no_avatar');exit;
		return '';
	}
}
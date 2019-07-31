<?php
namespace app\mip\controller;
use think\facade\Db;
use think\facade\Config;

class Index extends Base {
	public function index(){
		global $dbtbpre,$class_r;
		$focus=DB::query("
			select classid,id,focusImg,titleurl,title,smalltext,newstime,'news' as tbname from {$dbtbpre}ecms_news where focusImg!='' and firsttitle=1
			union 
			select classid,id,focusImg,titleurl,title,smalltext,newstime,'pictures' as tbname from {$dbtbpre}ecms_pictures where focusImg!='' and firsttitle=1
			order by newstime desc limit 5");
		$tuijian=DB::query("
			select classid,id,titlepic,titleurl,title,smalltext,newstime,'news' as tbname from {$dbtbpre}ecms_news where titlepic!='' and isgood>0 
			union 
			select classid,id,titlepic,titleurl,title,smalltext,newstime,'pictures' as tbname from {$dbtbpre}ecms_pictures where titlepic!='' and isgood>0 
			order by newstime desc limit 10");
		$time=strtotime("- 600 hour");
		$redian=DB::query("
			select classid,id,titlepic,titleurl,title,smalltext,newstime,writer,username,'news' as tbname,onclick from {$dbtbpre}ecms_news where titlepic!='' and newstime>$time
			union 
			select classid,id,titlepic,titleurl,title,smalltext,newstime,writer,username,'pictures' as tbname,onclick from {$dbtbpre}ecms_pictures where titlepic!='' and newstime>$time 
			order by onclick desc limit 10");
		$classlist=[];
		foreach(array(427,34,35,36,504) as $classid){
			$tbname=$class_r[$classid]['tbname'];
			if($tbname)$classlist[$classid]=DB::query("select classid,id,titlepic,titleurl,title,smalltext,newstime,writer,username,'{$tbname}' as tbname from {$dbtbpre}ecms_{$tbname} where titlepic!='' and classid='$classid' order by newstime desc limit 10");
		}
		return $this->view('',[
			'mipurl'=>"http://mip.domain.com/",
			'pcurl'=>"http://www.domain.com/",
			'pagekeywords'=>"",
			'pagedesc'=>"",
			'focus'=>$focus,
			'tuijian'=>$tuijian,
			'redian'=>$redian,
			'classlist'=>$classlist
		]);
	}
	public function d(){
		$pathinfo=input('server.PATH_INFO','');
		$type=strtolower(end(explode('.',$pathinfo)));
		if(in_array($type,array('jpg','gif','png','jpeg'))){
			return redirect('http://www.domain.com'.$pathinfo);//.str_replace(array('m.chexun.com','m.domain.com'),array('chexun.com',''),$_SERVER['HTTP_HOST'])
		}
		return $this->err404();
	}
	public function jssdk(){
		return file_get_contents("http://www.domain.com/weixin/choujiang.php?act=jssdk&url=".urlencode($_GET[url]));
	}
	public function test(){
		global $dbtbpre;
		echo user_avatar(25,0,1);exit;
		echo config('config.use_no_avatar');exit;
		return '';
	}
}
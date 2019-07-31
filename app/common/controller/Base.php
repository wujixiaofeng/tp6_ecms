<?php
namespace app\common\controller;
use think\App;
use think\facade\View;
use think\Response;
use think\exception\HttpResponseException;

abstract class Base {
	protected $app;
	protected $request;
	use \app\common\traits\ValidateTrait;
	public function __construct(App $app) {
		request()->filter(['strip_tags','tp_dotrim']);
		$this->app = $app;
		$this->request = $this->app->request;
		$this->initialize();
	}
	public function restore($tag=''){
		if(session('restore'.($tag?'_'.$tag:''))){
			$goto=session('restore'.($tag?'_'.$tag:''));
			session('restore'.($tag?'_'.$tag:''),null);
		}
		if(!$goto){
			if(request()->app()=='android'){
				$goto='/android2/index.php?s=/android/admin/index';
			}elseif(request()->app()=='admin'){
				$goto='/e/paiadmin/index.php?s=/';
			}
			if(!$goto)$goto='/';
		}
		return redirect($goto);
	}
	protected function checklogin(){
		$user=tp_loginuser();
		if(!$user){
			if(!request()->isAjax()){
				$restore=request()->url();
				session('restore',$restore);
			}
			$this->error('请先登录！',"javascript:showpassport('login');");
		}
		return $user;
	}
	protected function view_file_version($content){
		preg_match_all('/(?:href|src)=["\']{1}([^"\']*[\.css|\.js])["\']{1}/is',$content,$matchs);
		$vlist=cache('fileversion');
		if(!$vlist)$vlist=[];
		$matchs[1]=array_unique($matchs[1]);
		foreach($matchs[1] as $k=>$v){
			$vname=request()->app().'_'.$v;
			if($vlist[$vname]){
				$version=$vlist[$vname];
			}else{
				if(substr($v,0,1)=='/'){
					$version=@filemtime($_SERVER['DOCUMENT_ROOT'].$v);
					$new[$vname]=$version;
				}else{
					$version='';
				}
			}
			if($version)$content=str_replace($v,$v.'?v='.date('YmdHis',$version),$content);
		}
		if($new){
			//$vlist=array_merge($vlist,$new);
			//不缓存执行时间只有4毫秒 所有不再缓存 if(!app()->isDebug())cache('fileversion',$vlist,60*60);
		}
		return $content;
	}
	protected function view_filter($content){
		$config=config('config.tmpl_replace');
		foreach($config as $k=>$v){
			$content=str_replace($k,$v,$content);
		}
		$content=str_replace('&amp;#','&#',$content);
		$content=str_replace('<a class="first" href="index_1.html">首页</a>','<a class="first" href="index.html">首页</a>',$content);
		$content=str_replace('<a class="num" href="index_1.html">1</a>','<a class="num" href="index.html">1</a>',$content);
		$content=$this->view_file_version($content);
		return $content;
	}
	protected function initialize(){
		View::filter(function($content){return $this->view_filter($content);});
		if(substr(strtolower(request()->action()),0,2)=='do'){
			$this->error('action err!');
		}
	}
	protected function view($a='',$b=[]){
		global $class_r;
		$user=tp_loginuser();
		$admin=loginadmin();
		$b=array_merge(['class_r'=>$class_r,'admin'=>$admin,'user'=>$user],$b);
		return view($a,$b)->filter(function($content){return $this->view_filter($content);});
	}
	protected function checkpassword($user,$password){
		if(md5(md5($password).$user['salt'])==$user['password']){
			return true;
		}
		return false;
	}
	protected function etag($time=0){
		if($time<=0)return false;
		if(app()->isDebug())return false;
		$md5 = md5($time);
		$etag = '"' . $md5 . '"';
		header('Last-Modified: '.gmdate('D, d M Y H:i:s',$time ).' GMT');
		header("ETag: $etag");
		if((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $time) || (isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE']) < $time) || (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag)){
			$response=Response::create('','html','304');
			throw new HttpResponseException($response);
		}
	}
	protected function success($msg,$goto=''){
		if(request()->isAjax()){
			if($goto){
				if(is_array($msg)){
					$msg['goto']=$goto;
				}else{
					$msg=['msg'=>$msg,'goto'=>$goto];
				}
			}
			$response=jsonok($msg);
		}else{
			if(!$goto)$goto='javascript:void(0);';
			$response=$this->view('/public/message',['message'=>$msg,'goto'=>$goto]);
		}
		throw new HttpResponseException($response);
	}
	protected function error($msg,$goto=''){
		if(request()->isAjax()){
			if($goto){
				if(is_array($msg)){
					$msg['goto']=$goto;
				}else{
					$msg=['msg'=>$msg,'goto'=>$goto];
				}
			}
			$response=jsonerr($msg);
		}else{
			if(!$goto)$goto='javascript:void(0);';
			$response=$this->view('/public/message',['error'=>$msg,'goto'=>$goto]);
		}
		throw new HttpResponseException($response);
	}
	public function imgcodehtml(){
		return view('common@/imgcode');
	}
	public function getvcode(){
		$vcode=new \Vcode();
		if($err=$vcode->get()){
			return jsonerr($err);
		}else{
			return jsonok();
		}
	}
	public function verify($id=1){
		$verify = new \Verify();
		ob_start();
		$verify->entry($id);
		$res=ob_get_contents();
		ob_end_clean();
		return download($res, 'verify.png', 'image/png');
	}
	protected function err404(){
		return view('/err404',[],404);
	}
	function __call($a,$b){
		return $this->err404();
	}
}
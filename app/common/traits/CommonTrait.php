<?php
namespace app\common\traits;
use think\Container;
trait CommonTrait{
	public function rterr($msg,$goto=''){
		return [0,$msg,$goto];
	}
	public function rtelang($msg,$goto=''){
		return [0,elang($msg),$goto];
	}
	public function rtok($msg,$goto=''){
		return [1,$msg,$goto];
	}
	private function uploaderr($errorNo=0){
		switch ($errorNo) {
			case 1:
			case 2:
				$error = 'upload File size exceeds the maximum value';
				break;
			case 3:
				$error = 'only the portion of file is uploaded';
				break;
			case 4:
				$error = 'no file to uploaded';
				break;
			case 6:
				$error = 'upload temp dir not found';
				break;
			case 7:
				$error = 'file write error';
				break;
			default:
			$error = 'unknown upload error';
		}
		$lang = Container::pull('lang');
		return $lang->has($error) ? $lang->get($error, []) : $error;
	}
	public function doupload($name,$dirname='file',$type='image',$size=0){
		if($size==0)$size=1024*1024;
		if($type=='jpg'){
			$ext='jpg,jpeg';
		}elseif($type=='image'){
			$ext='jpg,jpeg,png,gif';
		}else{
			$ext='jpg,png,gif,rar,zip,xls,doc,ppt,xlsx,docx,pptx,txt,mp3,mp4,wav,flv';
		}
		if(($errnum=$_FILES[$name]['error'])>0){
			return $this->rterr($this->uploaderr($errnum));
		}
		$file = request()->file($name);
		$info = $file->validate(['size'=>$size,'ext'=>$ext])->move(ECMS_PATH.'d/'.($dirname?$dirname.'/':''));
		if($info){
			$savename='/d/'.($dirname?$dirname.'/':'').str_replace('\\','/',$info->getSaveName());
			return $this->rtok(['file'=>$file,'info'=>$info,'savename'=>$savename]);
		}else{
			return $this->rterr($file->getError());
		}
	}
	public function rtmsg($msg,$goto=''){
		if(!$goto)$goto=$msg[2];
		if(request()->isAjax()){
			return $this->rtjson($msg,$goto);
		}else{
			if(is_array($msg[1])){
				$message=$msg[1]['msg'];
			}else{
				$message=$msg[1];
			}
			if($msg[0]){
				return $this->success($message,$goto);
			}else{
				return $this->error($message,$goto);
			}
		}
	}
	public function rtjson($msg,$goto=''){
		if($goto){
			if(is_array($msg[1])){
				$msg[1]['goto']=$goto;
			}else{
				$msg[1]=['goto'=>$goto,'msg'=>$msg[1]];
			}
		}
		if($msg[0]){
			return jsonok($msg[1]);
		}else{
			return jsonerr($msg[1]);
		}
	}
}
?>
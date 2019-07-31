<?php
namespace app\common\traits;
use think\facade\Db;
trait YoujiTrait{
	public function doyoujiadd(){
		global $dbtbpre,$class_r;
		$user=tp_loginuser();
		if(!$user)$this->rterr('请先登录！');
		$name = input2('post.title');
		$desc = input2('post.desc');
		if(!$name)return $this->rterr("请填写游记标题！");
		if(!$desc)return $this->rterr("请填写游记描述！");
		$classid=500;
		if($class_r[$classid]['tbname']!='youji')return $this->rterr("ID错误！");
		$time=NOW_TIME;
		$newspath=date("Y-m-d");
		$checked=0;
		DB::execute("insert into {$dbtbpre}ecms_youji_index(classid,checked,newstime,truetime,lastdotime,havehtml) values('$classid','$checked','".$time."','".$time."','".$time."','0');",[],false,true);
		$id=lastid();
		$smalltext=$desc;
		DB::execute("insert into {$dbtbpre}ecms_youji".($checked=="1"?"":"_check")."(id,classid,newspath,userid,username,truetime,lastdotime,havehtml,stb,fstb,restb,title,newstime,ismember,smalltext) values('$id','$classid','$newspath','$user[userid]','$user[username]','$time','$time','0','1','1','1','$name','$time','1','$smalltext');");
		if($checked=="0")DB::execute("insert into {$dbtbpre}ecms_youji_check_data(id,classid) values('$id','$classid');");
		if($checked=="1")DB::execute("insert into {$dbtbpre}ecms_youji_data_1(id,classid,haveaddfen)values('$id','$classid','1');");
		DB::execute("update {$dbtbpre}ecms_youji".($checked=="1"?"":"_check")." set filename='$id',titleurl='/youji/$newspath/$id.html' where id='$id'");
		DB::execute("update {$dbtbpre}enewsclass set allinfos=allinfos+1".($checked=="1"?",infos=infos+1":"")." where classid='$classid' limit 1");
		DoUpdateAddDataNum('info',$class_r[$classid]['tid'],1);
		return $this->rtok(array('msg'=>'提交成功！','classid'=>$classid,'id'=>$id,'tbname'=>$class_r[$classid]['tbname']));
	}
	public function dosetcover(){
		global $dbtbpre;
		$yjid=input('post.yjid/d',0);
		$coverid=input('post.coverid/d',0);
		$backgroundid=input('post.backgroundid/d',0);
		if(!$coverid)return $this->rterr('请选择封面图！');
		if(!$backgroundid)return $this->rterr('请选择背景图！');
		$cover=DB::table("{$dbtbpre}youji_img")->where('id',$coverid)->value('img');
		if($coverid==$backgroundid){
			$background=$cover;
		}else{
			$background=DB::table("{$dbtbpre}youji_img")->where('id',$backgroundid)->value('img');
		}
		$update=array();
		$oldcover=$youji['titlepic'];
		$oldcoveryname=str_replace('/cover','/',$oldcover);
		if(!$oldcover or ($oldcover and $oldcoveryname!=$cover)){
			if($oldcover)@unlink(ECMS_PATH.substr($oldcover,1));
			$ypath=$cover;
			$fname=basename($ypath);
			$fpath=str_replace($fname,"",$ypath);
			$sname="cover".$fname;
			$spath=$fpath.$sname;
			//$imginfo=getimagesize(ECMS_PATH.substr($ypath,1));
			$filetype=GetFiletype($spath);
			$filer=tp_imageresize(ECMS_PATH.substr($ypath,1),ECMS_PATH.substr($spath,1),400,300);
			//$cutimg='/'.str_replace(ECMS_PATH,"",$name).$filetype;
			$update['titlepic']=$spath;
		}
		$oldback=$youji['background'];
		$oldbackyname=str_replace('/background','/',$oldback);
		if(!$oldback or ($oldback and $oldbackyname!=$background)){
			if($oldback and strpos($oldback,'/background')>-1)@unlink(ECMS_PATH.substr($oldback,1));
			$ypath=$background;
			$fname=basename($ypath);
			$fpath=str_replace($fname,"",$ypath);
			$sname="background".$fname;
			$spath=$fpath.$sname;
			//$imginfo=getimagesize(ECMS_PATH.substr($ypath,1));
			$filetype=GetFiletype($spath);
			$filer=tp_imageresize(ECMS_PATH.substr($ypath,1),ECMS_PATH.substr($spath,1),1920,1080);
			//$cutimg='/'.str_replace(ECMS_PATH,"",$name).$filetype;
			$update['background']=$spath;
		}
		//if($update)update('ecms_youji'.($checked=="1"?"":"_check"),$update,'id='.$yjid);
		if($update)enewsinfo('youji',$yjid)->save($update);
		return $this->rtok("设置成功！");
	}
}?>
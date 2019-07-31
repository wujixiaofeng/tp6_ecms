<?php
namespace app\home\controller;
use app\common\model\Db;

class Youji extends Base {
	use \app\common\traits\CommonTrait;
	use \app\common\traits\YoujiTrait;
	public function initialize(){
		parent::initialize();
	}
	public function checkyouji($yjid=0){
		$user=$this->checklogin();
		if($yjid){
			$youji=enewsinfo('youji',$yjid)->find();
			if(!$youji){
				$this->error('游记不存在！');
			}elseif(!loginadmin() && $youji['userid']!=$user['userid']){
				$this->error('此游记不属于你！');
			}
		}
		return ['youji'=>$youji,'user'=>$user];
	}
	public function map(){
		global $dbtbpre;
		$yjid=input('get.yjid/d',0);
		if(!$yjid)return $this->error('游记ID错误！');
		$pointlist=DB::getCol("select point from {$dbtbpre}youji_place as p where yjid=$yjid and point!='' order by date asc");
		return $this->view('',['pointlist'=>$pointlist]);
	}
	public function desc(){
		global $dbtbpre;
		$yjid=input('get.yjid/d',0);
		if(!$yjid)return $this->error('游记ID错误！');
		$check=$this->checkyouji($yjid);
		$user=$check['user'];
		$youji=$check['youji'];
		if(request()->isPost()){
			$smalltext='';
			$desc=input2('post.desc');
			foreach($desc as $k=>$v){
				$k=(int)$k;
				if(!$smalltext)$smalltext=$v;
				DB::execute("update {$dbtbpre}youji_img set `desc`='$v' where id=$k and yjid=$yjid");
			}
			if(!$youji['smalltext'] and $smalltext)enewsinfo('youji',$yjid)->update(array('smalltext'=>$smalltext));
			$this->success('保存成功',$youji['titleurl']);
		}else{
			$list=DB::query("select * from {$GLOBALS[dbtbpre]}youji_img where yjid=$yjid order by `index` asc,time asc");
			$date="";$i=0;
			$dlist=array();
			foreach($list as $k=>$v){
				$i++;
				if($date!=$v['date'] and $i!=1){
					$dlist[$v['date']]["day"]=(strtotime($v["date"])-strtotime($date))/(60*60*24)+1;
				}elseif($i==1){
					$dlist[$v['date']]["day"]=1;
					$date=$v['date'];
				}
				$dlist[$v['date']]['list'][]=$v;
			}
			$res['yjid']=$yjid;
			$res['dlist']=$dlist;
			$res['pagetitle']='编辑游记图片描述';
			return $this->view('',$res);
		}
	}
	public function addplace(){
		global $dbtbpre;
		$yjid=input('post.yjid/d',0);
		if(!$yjid)return $this->error('游记ID错误！');
		$user=$this->checkyouji($yjid)['user'];
		$name=input2('post.name');
		$point=input('post.point');
		$date=input('post.date');
		if(empty($name)){
			$rt["ok"]=false;
			$rt["msg"]="名称不能为空！";
		}elseif(DB::getValue("select plid as total from {$dbtbpre}youji_place where yjid=$yjid and `date`='$date' and `plname`='$name' and userid=$user[userid]")){
			$rt=array("ok"=>false,"msg"=>"名称重复");
		}else{
			DB::execute("INSERT INTO {$dbtbpre}youji_place(`yjid`,`date`,`plname`,`userid`,`username`,`point`)VALUES ($yjid,'$date','$name',$user[userid],'$user[username]','$point');");
			$id=lastid();
			$rt=array("ok"=>true,"name"=>$name,"id"=>$id,"point"=>$point);
		}
		return json($rt);
	}
	public function deleteimg(){
		global $dbtbpre;
		$id=input('get.id/d',0);
		$img=DB::getRow("select yjid,img,cimg,simg from {$dbtbpre}youji_img where id=$id");
		if($img){
			$yjid=$img['yjid'];
			$youji=$this->checkyouji($yjid);
			@unlink(ECMS_PATH.substr($img[img],1));
			@unlink(ECMS_PATH.substr($img[cimg],1));
			@unlink(ECMS_PATH.substr($img[simg],1));
			DB::execute("delete from {$dbtbpre}youji_img where id=$id");
			$checked=tp_infochecked($yjid,'youji');
			DB::execute("update {$dbtbpre}ecms_youji".($checked=="1"?"":"_check")." set imgcount=imgcount-1 where id=$yjid");
			return jsonok();
		}else{
			return jsonerr();
		}
	}
	public function cover(){
		global $dbtbpre;
		$yjid=input2('get.yjid/d',0);
		if(!$yjid)return $this->error('游记ID错误！');
		$youji=$this->checkyouji($yjid)['youji'];
		if(request()->isPost()){
			$do=$this->dosetcover();
			if($do[0])return redirect('/youji/desc.html?yjid='.$yjid);
			return $this->rtmsg($do);
		}else{
			$list=DB::query("select * from {$dbtbpre}youji_img where yjid=$yjid order by `index` asc,time asc");
			$res['list']=$list;
			$res['yjid']=$yjid;
			$res['youji']=$youji;
			$res['pagetitle']='设置游记封面';
			return $this->view('',$res);
		}
	}
	public function place(){
		global $dbtbpre;
		$yjid=input2('get.yjid/d');
		if(!$yjid)return $this->error('游记ID错误！');
		$this->checkyouji($yjid);
		if(request()->isPost()){
			foreach($_POST[img] as $k=>$v){
				$k=(int)$k;
				$v=(int)$v;
				DB::execute("update {$dbtbpre}youji_img set plid=$v where id=$k and yjid=$yjid");
			}
			foreach($_POST[img_date] as $k=>$v){
				$k=(int)$k;
				$v=date("Y-m-d",@strtotime($v));
				DB::execute("update {$dbtbpre}youji_img set `date`='$v' where id=$k and yjid=$yjid");
			}
			foreach($_POST[img_index] as $k=>$v){
				$k=(int)$k;
				$v=(int)$v;
				DB::execute("update {$dbtbpre}youji_img set `index`=$v where id=$k and yjid=$yjid");
			}
			return redirect('/youji/cover.html?yjid='.$yjid);
		}else{
			$list=DB::query("select * from {$dbtbpre}youji_img where yjid=$yjid order by `index` asc,time asc");
			$date="";$i=0;
			foreach($list as $k=>$v){
				$i++;
				if($date!=$v['date'] and $i!=1){
					$dlist[$v['date']]["day"]=(strtotime($v["date"])-strtotime($date))/(60*60*24)+1;
				}elseif($i==1){
					$dlist[$v['date']]["day"]=1;
					$date=$v['date'];
				}
				$dlist[$v['date']]['list'][]=$v;
				if($v['plid'])$plist[$v['plid']][]=$v['id'];
			}
			foreach($dlist as $k=>$v){
				$pllist=DB::query("select * from {$GLOBALS[dbtbpre]}youji_place where yjid=$yjid and date='$k' order by plid asc");
				$dlist[$k]['pllist']=$pllist;
			}
			$res['dlist']=$dlist;
			$res['plist']=$plist;
			$res['yjid']=$yjid;
			$res['pagetitle']='编辑游记地点';
			return $this->view('',$res);
		}
	}
	public function upload(){
		global $dbtbpre,$class_r;
		$user=$this->checklogin();
		$yjid=input2('get.yjid/d',0);
		if(!$yjid)return $this->error('游记ID错误！');
		$this->checkyouji($yjid);
		$classid=500;
		if($class_r[$classid]['tbname']!='youji')return jsonerr('classid错误！');
		if(request()->isPost()){
			$imginfo=getimagesize($_FILES['file']['tmp_name']);
			if(!$imginfo){
				$imginfo=tp_getjpegsize($_FILES['file']['tmp_name']);
			}
			$width=$imginfo[0];
			$height=$imginfo[1];
			if($width<1000 and $height<1000){
				return jsonerr("图片宽度和高度小于1000。");
			}
			$addtime=NOW_TIME;
			$date=date("Y-m-d");
			$path="/d/file/youji/$yjid/";
			mkdir(ECMS_PATH.substr($path,1),0777,true);
			$filename=ReturnDoTranFilename("","");
			$imgurl="/d/file/youji/{$yjid}/{$filename}.jpg";
			move_uploaded_file($_FILES['file']['tmp_name'],ECMS_PATH.substr($imgurl,1));
	
	
	
	
			$filesize=filesize(ECMS_PATH.substr($imgurl,1));
			$username=$user['username'];
			$pubid=ReturnInfoPubid($classid,$yjid);
			DB::execute("insert into {$dbtbpre}enewsfile_1(filename,filesize,adduser,path,filetime,classid,no,type,id,cjid,fpath,pubid) values('".$filename.".jpg','$filesize','$username','$yjid','".NOW_TIME."','$classid','$filename','1','$yjid','','','$pubid');");
	
			$infoid=lastid();
	
	
			$smallpath=ECMS_PATH.substr($path."small".$filename.".jpg",1);
			$smallname=basename($smallpath);
			tp_imageresize(ECMS_PATH.substr($imgurl,1),$smallpath,400,300);
			$smallurl=str_replace(ECMS_PATH,"/",$smallpath);
	
	
			if($width<1280 and $height<1024){
				copy(ECMS_PATH.substr($path.$filename,1).".jpg",ECMS_PATH.substr($path."cut".$filename,1).".jpg");
				$cuturl=$path."cut".$filename.".jpg";
			}else{
				$wh=tp_getwh("1280,1024","$width,$height");
				$cutpath=ECMS_PATH.substr($path."cut".$filename.".jpg",1);
				tp_imageresize(ECMS_PATH.substr($imgurl,1),$cutpath,$wh[w],$wh[h]);
				$cuturl=str_replace(ECMS_PATH,"/",$cutpath);
			}
	
			
			DB::execute("insert into {$dbtbpre}youji_img(yjid,`desc`,time,`userid`,`username`,plid,`date`,img,cimg,simg,filesize) values($yjid,'',$addtime,'$user[userid]','$user[username]','','$date','{$imgurl}','{$cuturl}','{$smallurl}','$filesize')");
			
			$daycount=DB::table("{$dbtbpre}youji_img")->where('yjid',$yjid)->count('distinct date');
			$checked=tp_infochecked($yjid,'youji');
			DB::execute("update {$dbtbpre}ecms_youji".($checked?"":"_check")." set imgcount=imgcount+1,daycount='$daycount' where id=$yjid");
			return jsonok(array("msg"=>"添加成功",'img'=>$imgurl));
		}else{
			$res=['yjid'=>$yjid];
			$res['havepic']=!!DB::getValue("select count(*) as total from {$GLOBALS[dbtbpre]}youji_img where yjid=$yjid");
			$res['pagetitle']='上传游记图片';
			return $this->view('',$res);
		}
	}
	public function editlink(){
		global $dbtbpre;
		$yjid=input('get.yjid');
		if(!$yjid)return $this->error('游记ID错误！');
		$check=$this->checkyouji($yjid);
		$user=$check['user'];
		$youji=$check['youji'];
		if($youji['tempid']){?>document.writeln('<a href="/index.php?m=albums&yjid=<?=$yjid?>" target="_blank">查看动态相册</a>');<?php }
		if($youji['userid']==$user['userid'] or loginadmin()){
			?>document.writeln('<a href="/index.php?m=albums&a=edit&yjid=<?=$yjid?>">修改动态相册</a>');<?php
			?>document.writeln('<a href="/youji/edit.html?yjid=<?=$yjid?>">修改此游记</a>');<?php
		}
		return '';
	}
	public function editplace(){
		global $dbtbpre;
		$yjid=input('post.yjid/d',0);
		$plid=input('post.plid/d',0);
		if(!$yjid)return $this->error('游记ID错误！');
		$user=$this->checkyouji($yjid)['user'];
		$name=input2('post.name');
		$point=input('post.point');
		$date=input('post.date');
		if(empty($name)){
			$rt["ok"]=false;
			$rt["msg"]="名称不能为空！";
		}elseif(DB::getValue("select plid as total from {$dbtbpre}youji_place where yjid='$yjid' and plid!='$plid' and `date`='$date' and `plname`='$name' and userid=$user[userid]")){
			$rt=array("ok"=>false,"msg"=>"名称重复");
		}else{
			DB::execute("update `{$GLOBALS[dbtbpre]}youji_place` set `plname`='$name',`point`='$point' where plid=$plid");
			$id=lastid();
			$rt=array("ok"=>true,"name"=>$name,"id"=>$plid,"point"=>$point);
		}
		return json($rt);
	}
	public function edit(){
		global $dbtbpre;
		$yjid=input('get.yjid');
		if(!$yjid)return $this->error('游记ID错误！');
		$youji=$this->checkyouji($yjid)['youji'];
		if(request()->isPost()){
			$post=input2('post.');
			$update=['title'=>$post['title'],'smalltext'=>$post['desc']];
			enewsinfo('youji',$yjid)->update($update);
			return $this->success('修改成功！','/youji/upload.html?yjid='.$yjid);
		}else{
			$res['pagetitle']='编辑游记';
			$res['youji']=$youji;
			return $this->view('add',$res);
		}
	}
	public function pl(){
		$classid=500;
		$yjid=input('get.yjid/d',0);
		return $this->view('',['classid'=>$classid,'yjid'=>$yjid]);
	}
	public function add(){
		global $dbtbpre;
		$this->checkyouji();
		if(request()->isPost()){
			$do=$this->doyoujiadd();
			if($do[0])return $this->rtmsg($do,'/youji/upload.html?yjid='.$do[1]['id']);
			return $this->rtmsg($do);
		}else{
			$res['pagetitle']='发布游记';
			return $this->view('',$res);
		}
	}
}
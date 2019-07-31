<?php
namespace app\android\controller;
use think\facade\Db;

class Youji extends Base{
	use \app\common\traits\CommonTrait;
	use \app\common\traits\YoujiTrait;
	public function setcover(){
		global $dbtbpre;
		$user=$this->checklogin();
		$yjid=input('post.yjid/d');
		$youji=enews('youji',$checked)->getInfo($yjid,$user['userid']);
		if(!$youji)return jsonerr('没有此游记！');
		return $this->rtjson($this->dosetcover());
	}
	public function imglist(){
		global $dbtbpre;
		$user=$this->checklogin();
		$yjid=input('get.yjid/d');
		$checked=tp_infochecked($yjid,'youji');
		$youji=enews('youji',$checked)->getInfo($yjid,$user['userid']);
		if(!$youji)return jsonerr('没有此游记！');
		$list=DB::table("{$dbtbpre}youji_img")->field(['simg'=>'img','id'])->where('yjid',$yjid)->select()->toArray();
		$coverid=0;
		$backgroundid=0;
		foreach($list as $v){
			if(str_replace("cover","",$youji['titlepic'])==str_replace("small","",$v['img'])){
				$coverid=$v['id'];
			}
			if(str_replace("background","",$youji['background'])==str_replace("small","",$v['img'])){
				$backgroundid=$v['id'];
			}
		}
		return jsonok(array('list'=>$list,'backgroundid'=>$backgroundid,'coverid'=>$coverid));
	}
	public function delimg(){
		global $dbtbpre;
		$user=$this->checklogin();
		$yjid=input('post.yjid/d');
		$imgid=input('post.imgid/d');
		$checked=tp_infochecked($yjid,'youji');
		$youji=enews('youji',$checked)->getInfo($yjid,$user['userid']);
		if(!$youji)return jsonerr('没有此游记！');
		$img=DB::table("{$dbtbpre}youji_img")->where([['id','=',$imgid],['yjid','=',$yjid]])->find();
		if($img){
			@unlink(ECMS_PATH.substr($img['img'],1));
			@unlink(ECMS_PATH.substr($img['cimg'],1));
			@unlink(ECMS_PATH.substr($img['simg'],1));
			DB::execute("delete from {$dbtbpre}youji_img where id=$imgid");
			DB::execute("update {$dbtbpre}ecms_youji".($checked=="1"?"":"_check")." set imgcount=imgcount-1 where id=$yjid");
			return jsonok('删除成功！');
		}else{
			return jsonerr('无此图片！');
		}
	}
	public function submit(){
		global $dbtbpre;
		$user=$this->checklogin();
		$classid=input2('post.classid/d');
		$yjid=input2('post.yjid/d');
		$imgdata=$_POST['imgdata'];
		$desc=input2('post.desc');
		$point=input2('post.point');
		$place=input2('post.place');
		$date=input2('post.time');
		if(!$imgdata)return jsonerr("请设置图片！");
		if(!$desc)return jsonerr("请填写描述！");
		$addtime=time();
		if($date){
			$date=date("Y-m-d",strtotime($date));
		}else{
			$date=date("Y-m-d");
		}

		//地点
		$plid=0;
		if($place&&!$plid=DB::table("{$dbtbpre}youji_place")->where("yjid=$yjid and `date`='$date' and `plname`='$place' and userid='$user[userid]'")->value('plid')){
			DB::execute("INSERT INTO `{$dbtbpre}youji_place`(`yjid`,`date`,`plname`,`userid`,`username`,`point`)VALUES ($yjid,'$date','$place','$user[userid]','$user[username]','$point');");
			$plid=lastid();
		}

		DB::execute("insert into {$dbtbpre}youji_img(yjid,`desc`,time,`userid`,`username`,plid,`date`) values($yjid,'$desc',$addtime,'$user[userid]','$user[username]','$plid','$date')");
		$infoid=lastid();

		$img_content = str_replace('data:image/jpeg;base64,','',$imgdata);
		$img_content = @base64_decode($img_content);
		@mkdir(ECMS_PATH."d/file/youji/$yjid/", 0777,true);
		$filename=ReturnDoTranFilename("","");
		$imgurl="/d/file/youji/$yjid/".$filename.".jpg";
		file_put_contents(ECMS_PATH.substr($imgurl,1),$img_content);



		$imginfo=getimagesize(ECMS_PATH.substr($imgurl,1));
		if(!$imginfo){
			$imginfo=tp_getjpegsize(ECMS_PATH.substr($imgurl,1));
		}
		$width=$imginfo[0];
		$height=$imginfo[1];
		if($width<1000 and $height<1000){
			return jsonerr("图片宽度和高度小于1000。");
		}

		$filesize=filesize(ECMS_PATH.substr($imgurl,1));
		$username=$user['username'];
		$pubid=ReturnInfoPubid($classid,$yjid);
		DB::execute("insert into {$dbtbpre}enewsfile_1(filename,filesize,adduser,path,filetime,classid,no,type,id,cjid,fpath,pubid) values('".$filename.".jpg','$filesize','$username','$yjid','".time()."','$classid','$filename','1','$yjid','','','$pubid');");

		$path="/d/file/youji/$yjid/";


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

		DB::execute("update {$dbtbpre}youji_img set img='{$imgurl}',cimg='{$cuturl}',simg='{$smallurl}',filesize='$filesize' where id=$infoid");
		$checked=tp_infochecked($yjid,'youji');
		$daycount=DB::table("{$dbtbpre}youji_img")->where('yjid',$yjid)->count('distinct date');
		$firstdate=DB::table("{$dbtbpre}youji_img")->where('yjid',$yjid)->order('date','asc')->value('date');
		if(!$firstdate)$firstdate=date("Y-m-d");
		$day=abs((strtotime($date)-strtotime($firstdate))/(60*60*24))+1;
		$daycn=tp_cn_num($day);
		DB::execute("update {$dbtbpre}ecms_youji".($checked?"":"_check")." set imgcount=imgcount+1,daycount='$daycount' where id=$yjid");
		return jsonok(array("msg"=>"添加成功","info"=>array("img"=>$imgurl,"desc"=>$desc,"date"=>date('Y年m月d日',strtotime($date)),"id"=>$infoid,"daycn"=>$daycn?'第'.$daycn.'天':'',"day"=>$day,"place"=>$place)));
	}
	public function add(){
		return $this->rtjson($this->doyoujiadd());
	}
}
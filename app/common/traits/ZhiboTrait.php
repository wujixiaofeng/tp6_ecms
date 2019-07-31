<?php
namespace app\common\traits;
use think\facade\Db;
trait ZhiboTrait{
	public function dodelinfo(){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		if(!(loginadmin() or in_array($user['userid'],(array)config('config.zhibouser'))))return $this->rterr('没有权限提交直播！');
		$infoid=input('request.infoid');
		$info=DB::table("{$dbtbpre}hd_zhibo")->field('img,zbid')->find($infoid);
		@unlink(ECMS_PATH.substr($info['img'],1));
		$query=DB::table("{$dbtbpre}hd_zhibo")->delete($infoid);
		if($info['zbid'] and $query){
			//TODO 刷新页面
			//$r2=$empire->fetch1("select * from {$dbtbpre}ecms_zhibo where id=$info[zbid]");
			//GetHtml($r2['classid'],$r2['id'],$r2,1);
			return $this->rtok("删除成功！");
		}else{
			return $this->rterr("删除失败！");
		}
	}
	public function dosubmit(){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		if(!(loginadmin() or in_array($user['userid'],(array)config('config.zhibouser'))))return $this->rterr('没有权限提交直播！');
		//$imgurl=RepPostStr($_POST['imgurl']);
		$imgdata=$_POST['imgdata'];
		$text=input2('post.text');
		$zbid=input('post.zbid');
		$classid=input('post.classid');
		if($zbid==0)return $this->rterr("直播ID错误！");
		if($imgdata||$text||$_FILES){
			$addtime=NOW_TIME;
			DB::table("{$dbtbpre}hd_zhibo")->insert(['zbid'=>$zbid,'text'=>$text,'addtime'=>$addtime]);
			$infoid=lastid();
			if($infoid){
				$username=$user['username'];
				$pubid=ReturnInfoPubid($classid,$zbid);
				if($imgdata){
					$img_content = str_replace('data:image/jpeg;base64,','',$imgdata);
					$img_content = @base64_decode($img_content);
					@mkdir(ECMS_PATH."d/file/zhibo/$zbid/", 0777,true);
					$filename=ReturnDoTranFilename("","");
					$imgurl="/d/file/zhibo/$zbid/".$filename.".jpg";
					file_put_contents(ECMS_PATH.substr($imgurl,1),$img_content);
					$filename2=$filename2.'.jpg';
					//$empire->query("update {$dbtbpre}hd_zhibo set img='{$imgurl}' where infoid=$infoid");
				}elseif($_FILES){
					$do=$this->doupload('img',"file/zhibo/$zbid/",'image');
					if($do[0]){
						$imgurl=$do[1]['savename'];
						$filename2=$do[1]['info']->getFilename();
						$filename=tp_str2arr($filename2,'.')[0];
					}else{
						return $this->rterr($do[1]);
					}
				}else{
					$imgurl="";
				}
				if($imgurl){
					$filesize=filesize(ECMS_PATH.substr($imgurl,1));
					DB::execute("insert into {$dbtbpre}enewsfile_1(filename,filesize,adduser,path,filetime,classid,no,type,id,cjid,fpath,pubid) values('".$filename2."','$filesize','$username','$zbid','".NOW_TIME."','$classid','$filename','1','$zbid','','','$pubid');");
					DB::table("{$dbtbpre}hd_zhibo")->where('infoid',$infoid)->update(['img'=>$imgurl]);
				}
				//TODO 刷新页面
				//$r=$empire->fetch1("select * from {$dbtbpre}ecms_zhibo where id=$zbid");
				//GetHtml($r['classid'],$r['id'],$r,1);
				return $this->rtok(array("msg"=>"添加成功","info"=>array("img"=>$imgurl,"text"=>$text,"addtime"=>date('Y-m-d H:i:s',$addtime),"infoid"=>$infoid)));
			}else{
				return $this->rterr("添加失败");
			}
		}else{
			return $this->rterr("请填写文字或上传图片");
		}
	}
}?>
<?php
namespace app\common\traits;
use app\common\model\Db;
trait NewsTrait{
	/*
	 * ecms=0为添加
	 */
	public function donews($ecms=0){
		global $public_r,$emod_r,$level_r,$class_r,$dbtbpre,$fun_r,$empire;
		$post=input2('post.');
		$user=tp_loginuser();
		$muserid=$user['userid'];
		$musername=$user['username'];
		$mrnd=$user['rnd'];
		if($e=tp_CheckCanPostUrl())return $this->rterr($e);
		if($public_r['addnews_ok'])return $this->rtelang("CloseQAdd");
		if($e=eCheckTimeCloseDo('info'))return $this->rterr($e);
		$classid=(int)$post['classid'];
		$mid=(int)$class_r[$classid]['modid'];
		if($emod_r[$mid]['editorf']){
			foreach(tp_str2arr($emod_r[$mid]['editorf']) as $f){
				if($f){
					$post[$f]=input2('post.'.$f,'','safehtml');
				}
			}
		}
		tp_dlog($post['newstext']);
		if(!$mid||!$classid)return $this->rtelang("EmptyQinfoCid");
		$tbname=$emod_r[$mid]['tbname'];
		$qenter=$emod_r[$mid]['qenter'];
		if(!$tbname||!$qenter||$qenter==',')return $this->rtelang("ErrorUrl");
		$setuserday='';	//取得栏目信息
		$isadd=0;
		if($ecms==0)$isadd=1;
		$cr=tp_DoQCheckAddLevel($classid,$muserid,$musername,$mrnd,$ecms,$isadd);
		if(is_string($cr))return $this->rterr($cr);
		$setuserday=$cr['checkaddnumquery'];
		$filepass=(int)$post['filepass'];
		$id=(int)$post['id'];
		$infor=array();
		//组合标题属性
		$titlecolor=addslashes(tp_RepPhpAspJspcodeText($post[titlecolor]));
		$titlefont=dgdb_tosave(tp_TitleFont($post[titlefont],$titlecolor));
		$titlecolor="";
		$titlefont="";
		$ttid=(int)$post['ttid'];
		$keyboard=addslashes(RepPostStr(trim(DoReplaceQjDh($post[keyboard]))));
		$keyid='';
		//返回关键字组合
		if($keyboard&&strstr($qenter,',special.field,'))
		{
			$keyboard=str_replace('[!--f--!]','ecms',$keyboard);
			$keyid=GetKeyid($keyboard,$classid,$id,$class_r[$classid][link_num]);
			$keyid=dgdb_tosave($keyid);
		}
		else
		{
			$keyboard='';
			$keyid='';
		}
		//验证码
		$keyvname='checkinfokey';
		//moreport
		if(Moreport_ReturnMustDt())/*使用系统函数*/
		{
			define('ECMS_SELFPATH',eReturnEcmsMainPortPath());/*使用系统函数*/
			Moreport_ResetMainTempGid();/*使用系统函数*/
		}
		$epreid=0;



		if(!$post['title'])return $this->rterr('请输入标题！');
		if($ecms==0){
			$lasttime=cookie('lastaddinfotime');
			if($lasttime)
			{
				if(time()-$lasttime<$public_r['readdinfotime'])
				{
					return $this->rtelang("QAddInfoOutTime");
				}
			}
			//验证码
			/*if($cr['qaddshowkey'])
			{
				ecmsCheckShowKey($keyvname,$post['key'],1);
			}*/
			//IP发布数限制
			$check_ip=request()->ip();
			$check_checked=$cr['wfid']?0:$cr['checkqadd'];
			$e=tp_eCheckIpAddInfoNum($check_ip,$tbname,$mid,$check_checked);
			if($e)return $this->rterr($e);
			//验证单信息
			$e=tp_qCheckMemberOneInfo($tbname,$mid,$classid,$muserid);
			if($e)return $this->rterr($e);
			//返回字段
			$ret_r=tp_ReturnQAddinfoF($mid,$post,$infor,$classid,$filepass,$muserid,$musername,0);
			if(is_string($ret_r))return $this->rterr($ret_r);
			$checked=$cr['checkqadd'];
			$havehtml=0;
			$newspath=date($cr['newspath']);
			$truetime=time();
			$newstime=$truetime;
			$newstempid=$cr['newstempid'];
			$haveaddfen=0;
			//强制签发
			$isqf=0;
			if($cr['wfid'])
			{
				$checked=0;
				$isqf=1;
			}
			//增扣点
			if($muserid)
			{
				if($checked)
				{
					tp_AddInfoFen($cr['addinfofen'],$muserid);
					$haveaddfen=1;
				}
				else
				{
					if($cr['addinfofen']<0&&!$public_r['qinfoaddfen'])
					{
						tp_AddInfoFen($cr['addinfofen'],$muserid);
						$haveaddfen=1;
					}
				}
			}
			if(empty($muserid))
			{
				$musername=$fun_r['guest'];
			}
			//会员投稿数更新
			if($setuserday)
			{
				$empire->query($setuserday);
			}
			//发布时间
			if(!strstr($qenter,',newstime,'))
			{
				$ret_r[0]=",newstime".$ret_r[0];
				$ret_r[1]=",'$newstime'".$ret_r[1];
			}
			else
			{
				if($post['newstime'])
				{
					$newstime=to_time($post['newstime']);
					$newstime=intval($newstime);
				}
			}
			//索引表
			$indexsql=$empire->query("insert into {$dbtbpre}ecms_".$tbname."_index(classid,checked,newstime,truetime,lastdotime,havehtml) values('$classid','$checked','$newstime','$truetime','$truetime','$havehtml');");
			$id=$empire->lastid();
			//返回表信息
			$infotbr=ReturnInfoTbname($tbname,$checked,$ret_r[4]);
			//主表
			$sql=$empire->query("insert into ".$infotbr['tbname']."(id,classid,ttid,onclick,plnum,totaldown,newspath,filename,userid,username,firsttitle,isgood,istop,isqf,ismember,isurl,truetime,lastdotime,havehtml,groupid,userfen,titlefont,titleurl,stb,fstb,restb,keyboard".$ret_r[0].") values('$id','$classid','$ttid',0,0,0,'$newspath','','".$muserid."','".addslashes($musername)."',0,0,0,'$isqf',1,0,'$truetime','$truetime','$havehtml',0,0,'$titlefont','','$ret_r[4]','$public_r[filedeftb]','$public_r[pldeftb]','$keyboard'".$ret_r[1].");");
			//副表
			$fsql=$empire->query("insert into ".$infotbr['datatbname']."(id,classid,keyid,dokey,newstempid,closepl,haveaddfen,infotags".$ret_r[2].") values('$id','$classid','$keyid',1,'$newstempid',0,'$haveaddfen',''".$ret_r[3].");");
			//扣点记录
			if($haveaddfen)
			{
				if($cr['addinfofen']<0)
				{
					BakDown($classid,$id,0,$muserid,$musername,addslashes(RepPostStr($post[title])),abs($cr['addinfofen']),3);/*使用系统函数*/
				}
			}
			//签发
			if($isqf==1)
			{
				InfoInsertToWorkflow($id,$classid,$cr['wfid'],$muserid,addslashes($musername));/*使用系统函数*/
			}
			//文件命名
			$filename=ReturnInfoFilename($classid,$id,'');/*使用系统函数*/
			//信息地址
			$infourl=GotoGetTitleUrl($classid,$id,$newspath,$filename,0,0,'');/*使用系统函数*/
			$usql=$empire->query("update ".$infotbr['tbname']." set filename='$filename',titleurl='$infourl' where id='$id'");
			//修改ispic
			UpdateTheIspic($classid,$id,$checked);/*使用系统函数*/
			//修改附件
			if($filepass)
			{
				UpdateTheFile($id,$filepass,$classid,$public_r['filedeftb']);/*使用系统函数*/
			}
			//更新栏目信息数
			AddClassInfos($classid,'+1','+1',$checked);/*使用系统函数*/
			//更新新信息数
			DoUpdateAddDataNum('info',$class_r[$classid]['tid'],1);/*使用系统函数*/
			//处理函数
			DoMFun($class_r[$classid]['modid'],$classid,$id,1,1);/*使用系统函数*/
			//清除验证码
			//ecmsEmptyShowKey($keyvname);
			cookie("qeditinfo",null);
			//生成页面
			if($checked&&!$cr['showdt'])
			{
				//$titleurl=qAddGetHtml($classid,$id);/*使用系统函数*/
			}
			//生成列表
			$epreid=0;
			if($checked)
			{
				//qAddListHtml($classid,$mid,$cr['qaddlist'],$cr['listdt']);
				//生成上一篇
				if($cr['repreinfo'])
				{
					//$prer=$empire->fetch1("select * from {$dbtbpre}ecms_".$tbname." where id<$id and classid='$classid' order by id desc limit 1");
					//$epreid=$prer['id'];
					//GetHtml($prer['classid'],$prer['id'],$prer,1);
				}
			}
			//更新动态缓存
			if($public_r['ctimeopen']&&$checked){
				eUpCacheInfo(0,$classid,0,$epreid,$ttid,'','',0,0);
			}
			if($sql){
				event('NewsAdd',[$classid,$id,$checked]);
				cookie("lastaddinfotime",time(),3600*24);//设置最后发表时间
				return $this->rtok(elang("AddQinfoSuccess"));
			}else{
				return $this->rtelang("DbError");
			}
		}elseif($ecms==1){
			if(!$id)return $this->rtelang("ErrorUrl");
			//检测权限
			$infor=tp_CheckQdoinfo($classid,$id,$muserid,$tbname,$cr['adminqinfo'],1);		//检测时间
			if(is_string($infor))return $this->rterr($infor);
			if($public_r['qeditinfotime'])
			{
				if(time()-$infor['truetime']>$public_r['qeditinfotime']*60)
				{
					return $this->rtelang("QEditInfoOutTime");
				}
			}
			//签发信息
			if($infor['isqf'])
			{
				$qck_qfr=$empire->fetch1("select wfid,checktno from {$dbtbpre}enewswfinfo where id='$infor[id]' and classid='$infor[classid]' limit 1");
				if($qck_qfr['checktno']<100)
				{
					$qck_qfwfr=$empire->fetch1("select wfid,canedit from {$dbtbpre}enewsworkflow where wfid='$qck_qfr[wfid]' limit 1");
					if($qck_qfwfr['wfid']&&!$qck_qfwfr['canedit'])
					{
						return $this->rtelang("qWorkflowCanNotEditInfo");
					}
				}
			}
			$iaddfield='';
			$addfield='';
			$faddfield='';
			//返回字段
			$ret_r=tp_ReturnQAddinfoF($mid,$post,$infor,$classid,$filepass,$muserid,$musername,1);
			if(is_string($ret_r))return $this->rterr($ret_r);
			if($keyboard)
			{
				$addfield=",keyboard='$keyboard'";
				$faddfield=",keyid='$keyid'";
			}
			//时间
			if(strstr($qenter,',newstime,'))
			{
				if($post['newstime'])
				{
					$newstime=to_time($post['newstime']);
					$newstime=intval($newstime);
					$iaddfield.=",newstime='$newstime'";
				}
			}
			//修改是否需要审核
			$ychecked=$infor['checked'];
			if($cr['qeditchecked'])
			{
				$infor['checked']=0;
				$iaddfield.=",checked=0";
				$relist=1;
				//删除原页面
				DelNewsFile($infor[filename],$infor[newspath],$infor[classid],$infor[newstext],$infor[groupid]);/*使用系统函数*/
			}
			//会员投稿数更新
			if($setuserday)
			{
				//$empire->query($setuserday);
			}
			$lastdotime=time();
			//索引表
			$indexsql=$empire->query("update {$dbtbpre}ecms_".$tbname."_index set lastdotime=$lastdotime,havehtml=0".$iaddfield." where id='$id'");
			//返回表信息
			$infotbr=ReturnInfoTbname($tbname,$ychecked,$infor['stb']);/*使用系统函数*/
			//主表
			$sql=$empire->query("update ".$infotbr['tbname']." set lastdotime=$lastdotime,havehtml=0,ttid='$ttid'".$addfield.$ret_r[0]." where id=$id and classid=$classid and userid='$muserid' and ismember=1");
			//副表
			$fsql=$empire->query("update ".$infotbr['datatbname']." set classid='$classid'".$faddfield.$ret_r[3]." where id='$id'");
			//修改ispic
			UpdateTheIspic($classid,$id,$ychecked);/*使用系统函数*/
			//更新附件
			UpdateTheFileEdit($classid,$id,$infor['fstb']);/*使用系统函数*/
			//未审核信息互转
			if($ychecked!=$infor['checked'])
			{
				MoveCheckInfoData($tbname,$ychecked,$infor['stb'],"id='$id'");/*使用系统函数*/
				//更新栏目信息数
				if($infor['checked'])
				{
					AddClassInfos($classid,'','+1');
				}
				else
				{
					AddClassInfos($classid,'','-1');
				}
			}
			//处理函数
			DoMFun($class_r[$classid]['modid'],$classid,$id,0,1);/*使用系统函数*/
			cookie("qeditinfo",null);
			//生成页面
			if($infor['checked']&&!$cr['showdt'])
			{
				//$titleurl=qAddGetHtml($classid,$id);
			}
			//生成列表
			if($infor['checked']||$relist==1)
			{
				//qAddListHtml($classid,$mid,$cr['qaddlist'],$cr['listdt']);
			}
			//生成上一篇
			$epreid=0;
			if($cr['repreinfo']&&$infor['checked'])
			{
				//$prer=$empire->fetch1("select * from {$dbtbpre}ecms_".$tbname." where id<$id and classid='$classid' order by id desc limit 1");
				//$epreid=$prer['id'];
				//GetHtml($prer['classid'],$prer['id'],$prer,1);
			}
			//更新动态缓存
			if($public_r['ctimeopen']&&$infor['checked'])
			{
				eUpCacheInfo(0,$classid,0,$epreid,$ttid,'','',0,0);
			}
			if($sql){
				event('NewsEdit',[$classid,$id,$infor]);
				return $this->rtok(elang("EditQinfoSuccess"));
			}else{
				return $this->rtelang("DbError","history.go(-1)",1);
			}
		}else{
			return $this->rterr('ecms err!');
		}
	}
	public function dodelonenews($classid,$id,$userid=0,$ismember=true){
		global $class_r,$emod_r;
		$tbname=$class_r[$classid]['tbname'];
		$checked=tp_infochecked($id,$tbname);
		$info=enews($tbname,$checked)->getInfo($id,$userid,[],$ismember);
		if(!$info)return $this->rterr('信息不存在！');
		$fubiao=enews($tbname,$checked,$info['stb'])->getInfo($id);
		$info=array_merge($info,$fubiao);
		$mid=(int)$class_r[$classid]['modid'];
		$stf=$emod_r[$mid]['savetxtf'];
		$pf=$emod_r[$mid]['pagef'];//分页字段
		//存文本
		if($stf){
			$newstextfile=$info[$stf];
			$info[$stf]=GetTxtFieldText($info[$stf]);
			//删除文件
			DelTxtFieldText($newstextfile);
		}
		DelNewsFile($info['filename'],$info['newspath'],$classid,$info[$pf],$info['groupid']);
		enews($tbname,'index')->del($id);
		enews($tbname,$checked)->del($id);
		enews($tbname,$checked,$info['stb'])->del($id);
		//更新栏目信息数
		AddClassInfos($classid,'-1','-1',$info['checked']);
		DelSingleInfoOtherData($classid,$id,$info,0,0);
		event('DeleteNews',$info);
		return $this->rtok('删除成功！');
	}
	public function dozan($classid,$id,$type='dian'){
		global $class_r,$dbtbpre;
		$user=tp_loginuser();
		if($type=="dian" and !$user)return $this->rterr("请先登录！");
		$tbname=$class_r[$classid]['tbname'];
		$zannum=(int)enewsinfo($tbname,$id)->value('diggtop');
		if($zannum<0){
			enewsinfo($tbname,$id)->save(['diggtop'=>0]);
			$zannum=0;
		}
		$zanlog=DB::table("{$dbtbpre}xf_zan_log")->where([['classid','=',$classid],['id','=',$id],['userid','=',tp_login()]])->value('logid');
		if($type=="dian"){
			if($zanlog){
				$jiajian="-";
				DB::table("{$dbtbpre}xf_zan_log")->where([['classid','=',$classid],['id','=',$id],['userid','=',tp_login()]])->delete();
				$zannum-=1;
				event('CancelZanNews',['classid'=>$classid,'id'=>$id]);
			}else{
				$jiajian="+";
				DB::table("{$dbtbpre}xf_zan_log")->insert(['classid'=>$classid,'id'=>$id,'userid'=>tp_login(),'addtime'=>NOW_TIME]);
				$zannum+=1;
				event('ZanNews',['classid'=>$classid,'id'=>$id]);
			}
			enewsinfo($tbname,$id)->save(['diggtop'=>$zannum]);
			$rt=array("msg"=>($jiajian=="+"?"":"取消")."点赞成功","yidian"=>(bool)($jiajian=="+"));
		}else{
			if($zanlog){
				$rt=array("yidian"=>true);
			}else{
				$rt=array("yidian"=>false);
			}
		}
		$rt['zannum']=$zannum;
		return $this->rtok($rt);
	}
	public function dofav($classid,$id,$type=''){
		global $class_r,$dbtbpre;
		if(empty($id)||empty($classid))return $this->rterr("发生错误！");
		if(empty($class_r[$classid]['tbname']))return $this->rterr("发生错误！");
		$user=tp_loginuser();
		if($type!="check" and !$user)return $this->rterr("请先登录！");
		$newsnum=DB::getValue("select count(*) as total from {$dbtbpre}enewsfava where id='$id' and classid='$classid' and userid='$user[userid]'");;
		if($type=="check"){
			if($newsnum){
				return $this->rtok(array("yidian"=>true));
			}else{
				return $this->rtok(array("yidian"=>false));
			}
		}elseif($newsnum){
			DB::execute("delete from {$dbtbpre}enewsfava where id='$id' and classid='$classid' and userid='$user[userid]'");
			event('CancelFavNews',['classid'=>$classid,'id'=>$id]);
			return $this->rtok(array("yidian"=>false,"msg"=>"已经取消收藏"));
		}else{
			$favatime=date("Y-m-d H:i:s");
			$sql=DB::execute("insert into {$dbtbpre}enewsfava(id,favatime,userid,username,classid,cid) values('$id','$favatime','$user[userid]','$user[username]','$classid','$cid');");
			if($sql){
				event('FavNews',['classid'=>$classid,'id'=>$id]);
				return $this->rtok(array("yidian"=>true,"msg"=>"成功添加收藏"));
			}else{
				return $this->rtok("发生错误！");
			}
		}
	}
	public function info($classid,$id){
		global $class_r,$dbtbpre;
		$tbname=$class_r[$classid]['tbname'];
		$checked=tp_infochecked($id,$tbname);
		$field="*";
		//if($tbname!="youji"&&$tbname!="shipin")$field.=',isOriginal';
		//if($tbname=='shipin')$field.=',url';
		$news=DB::table("{$dbtbpre}ecms_{$tbname}".($checked?"":"_check"))->field($field)->find($id);
		if(!$news)return array();
		$news['newstimestamp']=$news['newstime'];
		$news['newstime']=date('Y年m月d日 H:i',$news['newstime']);
		$news['ismember']=!!$news['ismember'];
		$news['tbname']=$tbname;
		if(tp_login())$news['yidianzan']=!!DB::table("{$dbtbpre}xf_zan_log")->where([['classid','=',$classid],['id','=',$id],['userid','=',tp_login()]])->value('logid');
		$news['checked']=!!$checked;
		if($tbname=='news'){
			$info=$this->news($id,$news);
		}elseif($tbname=='pictures'){
			$info=$this->pic($id,$news,$checked);
		}elseif($tbname=='youji'){
			$info=$this->youji($id,$news);
		}elseif($tbname=='zhibo'){
			$info=$this->zhibo($id,$news);
		}elseif($tbname=='shipin'){
			$info=$news;
		}
		if($_GET['test']){
			halt($info);
		}
		return $info;
	}
	private function zhibo($id,$news){
		global $dbtbpre;
		$paixu="asc";
		$list=DB::table("{$dbtbpre}hd_zhibo")->where('zbid',$id)->order('addtime',$paixu)->select()->toArray();
		foreach($list as $k=>$v){
			$list[$k]['addtime']=date('Y-m-d H:i:s',$v['addtime']);
		}
		$return=array_merge($news,['list'=>$list,'allowuser'=>(array)config('config.zhibouser')]);
		return $return;
	}
	private function youji($id,$news){
		global $dbtbpre;
		$list=DB::table("{$dbtbpre}youji_img")->where('yjid',$id)->order(['index'=>'asc','date'=>'asc','plid'>'asc','time'=>'asc'])->select()->toArray();
		$firstdate="";$i=0;
		if(request()->app()=='home'){
			$firstdate="";$i=0;
			$dlist=array();
			foreach($list as $k=>$v){
				$i++;
				if($date!=$v['date'] and $i!=1){
					$dlist[$v['date']]['day']=(strtotime($v["date"])-strtotime($firstdate))/(60*60*24)+1;
				}elseif($i==1){
					$dlist[$v['date']]['day']=1;
					$firstdate=$v['date'];
				}
				if(empty($dlist[$v['date']]['dlist'][(string)$v['plid']])){
					if(((string)$v['plid'])=="0"){
						$plname=$dlist[$v['date']]['day'];
					}else{
						$plname=DB::getValue("select plname as total from {$dbtbpre}youji_place where plid=$v[plid]");
					}
					$dlist[$v['date']]['dlist'][(string)$v['plid']]['name']=$plname;
					$dlist[$v['date']]['dlist'][(string)$v['plid']]['plist']=array();
				}
				$dlist[$v['date']]['dlist'][(string)$v['plid']]['plist'][]=$v;	
			}
		}else{
			$dlist=array();
			foreach($list as $v){
				$i++;
				if($i==1){
					$day=1;
					$firstdate=$v['date'];
					$daycn=tp_cn_num($day);
					$date=date('Y年m月d日',strtotime($v['date']));
				}elseif($date!=$v['date'] and $i!=1){
					$day=(strtotime($v["date"])-strtotime($firstdate))/(60*60*24)+1;
					$daycn=tp_cn_num($day);
					$date=date('Y年m月d日',strtotime($v['date']));
				}else{
					$day='';
					$daycn='';
					$date='';
				}
				if($v['plid']>0){
					if(!empty($placesarr[$v['plid']])){
						$place=$placesarr[$v['plid']];
					}else{
						$place=DB::table("{$dbtbpre}youji_place")->where('plid',$v['plid'])->value('plname');
						$placesarr[$v['plid']]=$place;
					}
				}else{
					$place="";
				}
				$arr=array('id'=>$v['id'],'place'=>$place,'day'=>$day,'daycn'=>($daycn?'第'.$daycn.'天':''),'date'=>$date,'img'=>$v['cimg'],'cimg'=>$v['cimg'],'simg'=>$v['simg'],'desc'=>($v['desc']?$v['desc']:"无说明"));
				array_push($dlist,$arr);
			}
		}
		$return=array_merge($news,['list'=>$dlist]);
		return $return;
	}
	private function pic($id,$news,$checked){
		global $dbtbpre;
		$morepic=DB::table("{$dbtbpre}ecms_pictures".($checked?"_data_1":"_check_data"))->where('id',$id)->value('morepic');
		$path_r=explode(chr(13).chr(10),$morepic);
		$list=array();
		for($pj=0;$pj<count($path_r);$pj++){
			$showdown_r=explode('::::::',$path_r[$pj]);
			$list[$pj]['img']=$showdown_r[1];
			$list[$pj]['desc']=$showdown_r[2];
		}
		$return=array_merge($news,['list'=>$list]);
		return $return;
	}
	private function news($id,$news){
		$html=tp_getnewstext($id);
		$html=stripslashes($html);
		//$html=str_replace("=\"/d/","=\"http://$_SERVER[HTTP_HOST]/d/",$html);
		//$html=str_replace("='/d/","='http://$_SERVER[HTTP_HOST]/d/",$html);
		$html=preg_replace('%[\s]*\[\!\-\-empirenews\.page\-\-\].*?\[\/\!\-\-empirenews\.page\-\-\][\s]*%','',$html);
		$html=preg_replace('%[\s]*\[\!\-\-empirenews\.page\-\-\][\s]*%','',$html);
		$return=array_merge($news,['html'=>/*tp_formathtml*/($html)]);
		return $return;
	}
}?>
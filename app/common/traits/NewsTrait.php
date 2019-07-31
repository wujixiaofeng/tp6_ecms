<?php
namespace app\common\traits;
use app\common\model\Db;
trait NewsTrait{
	/*
	 * ecms=0Ϊ���
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
		$setuserday='';	//ȡ����Ŀ��Ϣ
		$isadd=0;
		if($ecms==0)$isadd=1;
		$cr=tp_DoQCheckAddLevel($classid,$muserid,$musername,$mrnd,$ecms,$isadd);
		if(is_string($cr))return $this->rterr($cr);
		$setuserday=$cr['checkaddnumquery'];
		$filepass=(int)$post['filepass'];
		$id=(int)$post['id'];
		$infor=array();
		//��ϱ�������
		$titlecolor=addslashes(tp_RepPhpAspJspcodeText($post[titlecolor]));
		$titlefont=dgdb_tosave(tp_TitleFont($post[titlefont],$titlecolor));
		$titlecolor="";
		$titlefont="";
		$ttid=(int)$post['ttid'];
		$keyboard=addslashes(RepPostStr(trim(DoReplaceQjDh($post[keyboard]))));
		$keyid='';
		//���عؼ������
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
		//��֤��
		$keyvname='checkinfokey';
		//moreport
		if(Moreport_ReturnMustDt())/*ʹ��ϵͳ����*/
		{
			define('ECMS_SELFPATH',eReturnEcmsMainPortPath());/*ʹ��ϵͳ����*/
			Moreport_ResetMainTempGid();/*ʹ��ϵͳ����*/
		}
		$epreid=0;



		if(!$post['title'])return $this->rterr('��������⣡');
		if($ecms==0){
			$lasttime=cookie('lastaddinfotime');
			if($lasttime)
			{
				if(time()-$lasttime<$public_r['readdinfotime'])
				{
					return $this->rtelang("QAddInfoOutTime");
				}
			}
			//��֤��
			/*if($cr['qaddshowkey'])
			{
				ecmsCheckShowKey($keyvname,$post['key'],1);
			}*/
			//IP����������
			$check_ip=request()->ip();
			$check_checked=$cr['wfid']?0:$cr['checkqadd'];
			$e=tp_eCheckIpAddInfoNum($check_ip,$tbname,$mid,$check_checked);
			if($e)return $this->rterr($e);
			//��֤����Ϣ
			$e=tp_qCheckMemberOneInfo($tbname,$mid,$classid,$muserid);
			if($e)return $this->rterr($e);
			//�����ֶ�
			$ret_r=tp_ReturnQAddinfoF($mid,$post,$infor,$classid,$filepass,$muserid,$musername,0);
			if(is_string($ret_r))return $this->rterr($ret_r);
			$checked=$cr['checkqadd'];
			$havehtml=0;
			$newspath=date($cr['newspath']);
			$truetime=time();
			$newstime=$truetime;
			$newstempid=$cr['newstempid'];
			$haveaddfen=0;
			//ǿ��ǩ��
			$isqf=0;
			if($cr['wfid'])
			{
				$checked=0;
				$isqf=1;
			}
			//���۵�
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
			//��ԱͶ��������
			if($setuserday)
			{
				$empire->query($setuserday);
			}
			//����ʱ��
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
			//������
			$indexsql=$empire->query("insert into {$dbtbpre}ecms_".$tbname."_index(classid,checked,newstime,truetime,lastdotime,havehtml) values('$classid','$checked','$newstime','$truetime','$truetime','$havehtml');");
			$id=$empire->lastid();
			//���ر���Ϣ
			$infotbr=ReturnInfoTbname($tbname,$checked,$ret_r[4]);
			//����
			$sql=$empire->query("insert into ".$infotbr['tbname']."(id,classid,ttid,onclick,plnum,totaldown,newspath,filename,userid,username,firsttitle,isgood,istop,isqf,ismember,isurl,truetime,lastdotime,havehtml,groupid,userfen,titlefont,titleurl,stb,fstb,restb,keyboard".$ret_r[0].") values('$id','$classid','$ttid',0,0,0,'$newspath','','".$muserid."','".addslashes($musername)."',0,0,0,'$isqf',1,0,'$truetime','$truetime','$havehtml',0,0,'$titlefont','','$ret_r[4]','$public_r[filedeftb]','$public_r[pldeftb]','$keyboard'".$ret_r[1].");");
			//����
			$fsql=$empire->query("insert into ".$infotbr['datatbname']."(id,classid,keyid,dokey,newstempid,closepl,haveaddfen,infotags".$ret_r[2].") values('$id','$classid','$keyid',1,'$newstempid',0,'$haveaddfen',''".$ret_r[3].");");
			//�۵��¼
			if($haveaddfen)
			{
				if($cr['addinfofen']<0)
				{
					BakDown($classid,$id,0,$muserid,$musername,addslashes(RepPostStr($post[title])),abs($cr['addinfofen']),3);/*ʹ��ϵͳ����*/
				}
			}
			//ǩ��
			if($isqf==1)
			{
				InfoInsertToWorkflow($id,$classid,$cr['wfid'],$muserid,addslashes($musername));/*ʹ��ϵͳ����*/
			}
			//�ļ�����
			$filename=ReturnInfoFilename($classid,$id,'');/*ʹ��ϵͳ����*/
			//��Ϣ��ַ
			$infourl=GotoGetTitleUrl($classid,$id,$newspath,$filename,0,0,'');/*ʹ��ϵͳ����*/
			$usql=$empire->query("update ".$infotbr['tbname']." set filename='$filename',titleurl='$infourl' where id='$id'");
			//�޸�ispic
			UpdateTheIspic($classid,$id,$checked);/*ʹ��ϵͳ����*/
			//�޸ĸ���
			if($filepass)
			{
				UpdateTheFile($id,$filepass,$classid,$public_r['filedeftb']);/*ʹ��ϵͳ����*/
			}
			//������Ŀ��Ϣ��
			AddClassInfos($classid,'+1','+1',$checked);/*ʹ��ϵͳ����*/
			//��������Ϣ��
			DoUpdateAddDataNum('info',$class_r[$classid]['tid'],1);/*ʹ��ϵͳ����*/
			//������
			DoMFun($class_r[$classid]['modid'],$classid,$id,1,1);/*ʹ��ϵͳ����*/
			//�����֤��
			//ecmsEmptyShowKey($keyvname);
			cookie("qeditinfo",null);
			//����ҳ��
			if($checked&&!$cr['showdt'])
			{
				//$titleurl=qAddGetHtml($classid,$id);/*ʹ��ϵͳ����*/
			}
			//�����б�
			$epreid=0;
			if($checked)
			{
				//qAddListHtml($classid,$mid,$cr['qaddlist'],$cr['listdt']);
				//������һƪ
				if($cr['repreinfo'])
				{
					//$prer=$empire->fetch1("select * from {$dbtbpre}ecms_".$tbname." where id<$id and classid='$classid' order by id desc limit 1");
					//$epreid=$prer['id'];
					//GetHtml($prer['classid'],$prer['id'],$prer,1);
				}
			}
			//���¶�̬����
			if($public_r['ctimeopen']&&$checked){
				eUpCacheInfo(0,$classid,0,$epreid,$ttid,'','',0,0);
			}
			if($sql){
				event('NewsAdd',[$classid,$id,$checked]);
				cookie("lastaddinfotime",time(),3600*24);//������󷢱�ʱ��
				return $this->rtok(elang("AddQinfoSuccess"));
			}else{
				return $this->rtelang("DbError");
			}
		}elseif($ecms==1){
			if(!$id)return $this->rtelang("ErrorUrl");
			//���Ȩ��
			$infor=tp_CheckQdoinfo($classid,$id,$muserid,$tbname,$cr['adminqinfo'],1);		//���ʱ��
			if(is_string($infor))return $this->rterr($infor);
			if($public_r['qeditinfotime'])
			{
				if(time()-$infor['truetime']>$public_r['qeditinfotime']*60)
				{
					return $this->rtelang("QEditInfoOutTime");
				}
			}
			//ǩ����Ϣ
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
			//�����ֶ�
			$ret_r=tp_ReturnQAddinfoF($mid,$post,$infor,$classid,$filepass,$muserid,$musername,1);
			if(is_string($ret_r))return $this->rterr($ret_r);
			if($keyboard)
			{
				$addfield=",keyboard='$keyboard'";
				$faddfield=",keyid='$keyid'";
			}
			//ʱ��
			if(strstr($qenter,',newstime,'))
			{
				if($post['newstime'])
				{
					$newstime=to_time($post['newstime']);
					$newstime=intval($newstime);
					$iaddfield.=",newstime='$newstime'";
				}
			}
			//�޸��Ƿ���Ҫ���
			$ychecked=$infor['checked'];
			if($cr['qeditchecked'])
			{
				$infor['checked']=0;
				$iaddfield.=",checked=0";
				$relist=1;
				//ɾ��ԭҳ��
				DelNewsFile($infor[filename],$infor[newspath],$infor[classid],$infor[newstext],$infor[groupid]);/*ʹ��ϵͳ����*/
			}
			//��ԱͶ��������
			if($setuserday)
			{
				//$empire->query($setuserday);
			}
			$lastdotime=time();
			//������
			$indexsql=$empire->query("update {$dbtbpre}ecms_".$tbname."_index set lastdotime=$lastdotime,havehtml=0".$iaddfield." where id='$id'");
			//���ر���Ϣ
			$infotbr=ReturnInfoTbname($tbname,$ychecked,$infor['stb']);/*ʹ��ϵͳ����*/
			//����
			$sql=$empire->query("update ".$infotbr['tbname']." set lastdotime=$lastdotime,havehtml=0,ttid='$ttid'".$addfield.$ret_r[0]." where id=$id and classid=$classid and userid='$muserid' and ismember=1");
			//����
			$fsql=$empire->query("update ".$infotbr['datatbname']." set classid='$classid'".$faddfield.$ret_r[3]." where id='$id'");
			//�޸�ispic
			UpdateTheIspic($classid,$id,$ychecked);/*ʹ��ϵͳ����*/
			//���¸���
			UpdateTheFileEdit($classid,$id,$infor['fstb']);/*ʹ��ϵͳ����*/
			//δ�����Ϣ��ת
			if($ychecked!=$infor['checked'])
			{
				MoveCheckInfoData($tbname,$ychecked,$infor['stb'],"id='$id'");/*ʹ��ϵͳ����*/
				//������Ŀ��Ϣ��
				if($infor['checked'])
				{
					AddClassInfos($classid,'','+1');
				}
				else
				{
					AddClassInfos($classid,'','-1');
				}
			}
			//������
			DoMFun($class_r[$classid]['modid'],$classid,$id,0,1);/*ʹ��ϵͳ����*/
			cookie("qeditinfo",null);
			//����ҳ��
			if($infor['checked']&&!$cr['showdt'])
			{
				//$titleurl=qAddGetHtml($classid,$id);
			}
			//�����б�
			if($infor['checked']||$relist==1)
			{
				//qAddListHtml($classid,$mid,$cr['qaddlist'],$cr['listdt']);
			}
			//������һƪ
			$epreid=0;
			if($cr['repreinfo']&&$infor['checked'])
			{
				//$prer=$empire->fetch1("select * from {$dbtbpre}ecms_".$tbname." where id<$id and classid='$classid' order by id desc limit 1");
				//$epreid=$prer['id'];
				//GetHtml($prer['classid'],$prer['id'],$prer,1);
			}
			//���¶�̬����
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
		if(!$info)return $this->rterr('��Ϣ�����ڣ�');
		$fubiao=enews($tbname,$checked,$info['stb'])->getInfo($id);
		$info=array_merge($info,$fubiao);
		$mid=(int)$class_r[$classid]['modid'];
		$stf=$emod_r[$mid]['savetxtf'];
		$pf=$emod_r[$mid]['pagef'];//��ҳ�ֶ�
		//���ı�
		if($stf){
			$newstextfile=$info[$stf];
			$info[$stf]=GetTxtFieldText($info[$stf]);
			//ɾ���ļ�
			DelTxtFieldText($newstextfile);
		}
		DelNewsFile($info['filename'],$info['newspath'],$classid,$info[$pf],$info['groupid']);
		enews($tbname,'index')->del($id);
		enews($tbname,$checked)->del($id);
		enews($tbname,$checked,$info['stb'])->del($id);
		//������Ŀ��Ϣ��
		AddClassInfos($classid,'-1','-1',$info['checked']);
		DelSingleInfoOtherData($classid,$id,$info,0,0);
		event('DeleteNews',$info);
		return $this->rtok('ɾ���ɹ���');
	}
	public function dozan($classid,$id,$type='dian'){
		global $class_r,$dbtbpre;
		$user=tp_loginuser();
		if($type=="dian" and !$user)return $this->rterr("���ȵ�¼��");
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
			$rt=array("msg"=>($jiajian=="+"?"":"ȡ��")."���޳ɹ�","yidian"=>(bool)($jiajian=="+"));
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
		if(empty($id)||empty($classid))return $this->rterr("��������");
		if(empty($class_r[$classid]['tbname']))return $this->rterr("��������");
		$user=tp_loginuser();
		if($type!="check" and !$user)return $this->rterr("���ȵ�¼��");
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
			return $this->rtok(array("yidian"=>false,"msg"=>"�Ѿ�ȡ���ղ�"));
		}else{
			$favatime=date("Y-m-d H:i:s");
			$sql=DB::execute("insert into {$dbtbpre}enewsfava(id,favatime,userid,username,classid,cid) values('$id','$favatime','$user[userid]','$user[username]','$classid','$cid');");
			if($sql){
				event('FavNews',['classid'=>$classid,'id'=>$id]);
				return $this->rtok(array("yidian"=>true,"msg"=>"�ɹ�����ղ�"));
			}else{
				return $this->rtok("��������");
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
		$news['newstime']=date('Y��m��d�� H:i',$news['newstime']);
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
					$date=date('Y��m��d��',strtotime($v['date']));
				}elseif($date!=$v['date'] and $i!=1){
					$day=(strtotime($v["date"])-strtotime($firstdate))/(60*60*24)+1;
					$daycn=tp_cn_num($day);
					$date=date('Y��m��d��',strtotime($v['date']));
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
				$arr=array('id'=>$v['id'],'place'=>$place,'day'=>$day,'daycn'=>($daycn?'��'.$daycn.'��':''),'date'=>$date,'img'=>$v['cimg'],'cimg'=>$v['cimg'],'simg'=>$v['simg'],'desc'=>($v['desc']?$v['desc']:"��˵��"));
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
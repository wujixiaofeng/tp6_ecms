<?php
use app\common\model\Db;



//��¼��֤��
function tp_qGetLoginAuthstr($userid,$username,$rnd,$groupid,$cookietime=0){
	global $ecms_config;
	$checkpass=md5(md5($rnd.'--d-i!'.$userid.'-(g*od-'.$username.$ecms_config['cks']['ckrndtwo'].'-'.$groupid).'-#empire.cms!--p)h-o!me-'.$ecms_config['cks']['ckrndtwo']);
	esetcookie('mlauth',$checkpass,$cookietime);
}
//��֤�ύIP
function tp_eCheckAccessDoIp($doing){
	global $public_r,$empire,$dbtbpre;
	$pr=$empire->fetch1("select opendoip,closedoip,doiptype from {$dbtbpre}enewspublic limit 1");
	if(!strstr($pr['doiptype'],','.$doing.','))
	{
		return '';
	}
	$userip=egetip();
	//����IP
	if($pr['opendoip'])
	{
		$close=1;
		foreach(explode("\n",$pr['opendoip']) as $ctrlip)
		{
			if(preg_match("/^(".preg_quote(($ctrlip=trim($ctrlip)),'/').")/",$userip))
			{
				$close=0;
				break;
			}
		}
		if($close==1)
		{
			return elang('NotCanPostIp');
		}
	}
	//��ֹIP
	if($pr['closedoip'])
	{
		foreach(explode("\n",$pr['closedoip']) as $ctrlip)
		{
			if(preg_match("/^(".preg_quote(($ctrlip=trim($ctrlip)),'/').")/",$userip))
			{
				return elang('NotCanPostIp');
			}
		}
	}
}
//��֤Ȩ��
function tp_CheckQdoinfo($classid,$id,$userid,$tbname,$adminqinfo,$ecms=0){
	global $empire,$dbtbpre,$emod_r,$class_r;
	//������
	$index_r=$empire->fetch1("select id,classid,checked from {$dbtbpre}ecms_".$tbname."_index where id='$id' limit 1");
	if(!$index_r['id']||$index_r['classid']!=$classid)
	{
		return elang("HaveNotLevelQInfo");
	}
	//���ر�
	$infotb=ReturnInfoMainTbname($tbname,$index_r['checked']);
	$r=$empire->fetch1("select * from ".$infotb." where id='$id' and classid='$classid' and ismember=1 and userid='$userid' limit 1");
	if(!$r['id'])
	{
		return elang("HaveNotLevelQInfo");
	}
	$r['checked']=$index_r['checked'];
	if($adminqinfo==1)//����δ�����Ϣ
	{
		if($index_r['checked'])
		{
			return elang("ClassSetNotAdminQCInfo");
		}
	}
	elseif($adminqinfo==2)//ֻ�ɱ༭δ�����Ϣ
	{
		if($index_r['checked']||$ecms!=1)
		{
			return elang("ClassSetNotEditQCInfo");
		}
	}
	elseif($adminqinfo==3)//ֻ��ɾ��δ�����Ϣ
	{
		if($index_r['checked']||$ecms!=2)
		{
			return elang("ClassSetNotDelQCInfo");
		}
	}
	elseif($adminqinfo==4)//����������Ϣ
	{}
	elseif($adminqinfo==5)//ֻ�ɱ༭������Ϣ
	{
		if($ecms!=1)
		{
			return elang("ClassSetNotEditQInfo");
		}
	}
	elseif($adminqinfo==6)//ֻ��ɾ��������Ϣ
	{
		if($ecms!=2)
		{
			return elang("ClassSetNotDelQInfo");
		}
	}
	else//���ܹ���Ͷ��
	{
		return elang("ClassSetNotAdminQInfo");
	}
	//���ر���Ϣ
	$infotbr=ReturnInfoTbname($tbname,$index_r['checked'],$r['stb']);
	//����
	$mid=$class_r[$classid]['modid'];
	$finfor=$empire->fetch1("select ".ReturnSqlFtextF($mid)." from ".$infotbr['datatbname']." where id='$r[id]' limit 1");
	$r=array_merge($r,$finfor);
	return $r;
}

//�滻�ؼ���
function tp_ReplaceKey($newstext,$classid=0){
	global $empire,$dbtbpre,$public_r,$class_r;
	if(empty($newstext)||$class_r[$classid]['keycid']==-1)
	{return $newstext;}
	$where='';
	if(!empty($class_r[$classid]['keycid']))
	{
		$where=" where cid='".$class_r[$classid]['keycid']."'";
	}
	$sql=$empire->query("select keyname,keyurl from {$dbtbpre}enewskey".$where);
	while($r=$empire->fetch($sql))
	{
		if(STR_IREPLACE)
		{
			$newstext=empty($public_r[repkeynum])?str_ireplace($r[keyname],'<a href='.$r[keyurl].' target=_blank class=infotextkey>'.$r[keyname].'</a>',$newstext):preg_replace('/'.$r[keyname].'/i','<a href='.$r[keyurl].' target=_blank class=infotextkey>'.$r[keyname].'</a>',$newstext,$public_r[repkeynum]);
		}
		else
		{
			$newstext=empty($public_r[repkeynum])?str_replace($r[keyname],'<a href='.$r[keyurl].' target=_blank class=infotextkey>'.$r[keyname].'</a>',$newstext):preg_replace('/'.$r[keyname].'/i','<a href='.$r[keyurl].' target=_blank class=infotextkey>'.$r[keyname].'</a>',$newstext,$public_r[repkeynum]);
		}
	}
	return $newstext;
}
//�滻�����ַ�
function tp_ReplaceWord($newstext){
	global $empire,$dbtbpre;
	if(empty($newstext))
	{return $newstext;}
	$sql=$empire->query("select newword,oldword from {$dbtbpre}enewswords");
	while($r=$empire->fetch($sql))
	{
		$newstext=str_replace($r[oldword],$r[newword],$newstext);
	}
	return $newstext;
}
//�����滻��֤
function tp_ReturnCheckDoRepStr(){
	global $public_r;
	return explode(',',$public_r[checkdorepstr]);
}
//�༭��Ϣʱ�滻�ؼ��ֺ͹����ַ�
function tp_DoReplaceKeyAndWord($newstext,$dokey,$classid=0){
	global $public_r;
	$docheckrep=tp_ReturnCheckDoRepStr();//�����滻��֤�ַ�
	if($public_r['dorepword']==1&&$docheckrep[3])//�����ַ�
	{
		$newstext=tp_ReplaceWord($newstext);
	}
	if($public_r['dorepkey']==1&&$docheckrep[4]&&!empty($dokey))//���ݹؼ���
	{
		$newstext=tp_ReplaceKey($newstext,$classid);
	}
	return $newstext;
}
//��֤�ַ��Ƿ��
function tp_CheckValEmpty($val){
	return strlen($val)==0?1:0;
}
//�����ֵ�ֶ�
function tp_DoFieldMoreValue($f,$add,$ecms=0){
	$rvarname=$f.'_1';
	$count=count($add[$rvarname]);
	if(empty($count))
	{
		return '';
	}
	//����
	$mvnumvar='mvnum_'.$f;
	$mvmustvar='mvmust_'.$f;
	$mvidvarname=$f.'_mvid';
	$mvid=$add[$mvidvarname];
	$mvdelidvarname=$f.'_mvdelid';
	$mvdelid=$add[$mvdelidvarname];
	//����
	$mvnum=(int)$add[$mvnumvar];
	if($mvnum<1||$mvnum>50)
	{
		$mvnum=1;
	}
	$mvmust=(int)$add[$mvmustvar];
	if($mvmust<1)
	{
		$mvmust=0;
	}
	if($ecms==1)
	{
		$delcount=count($mvdelid);
	}
	$rexp='||||||';
	$fexp='::::::';
	$rstr='';
	$rstrexp='';
	for($i=0;$i<$count;$i++)
	{
		//ɾ��
		if($ecms==1)
		{
			$del=0;
			for($d=0;$d<$delcount;$d++)
			{
				if($mvdelid[$d]==$mvid[$i])
				{
					$del=1;
					break;
				}
			}
			if($del)
			{continue;}
		}
		$fstr='';
		$fstrexp='';
		$fstrempty=0;
		for($j=0;$j<$mvnum;$j++)
		{
			$k=$j+1;
			$fsvarname=$f.'_'.$k;
			$fsval=$add[$fsvarname][$i];
			$fsval=str_replace($rexp,'',$fsval);
			$fsval=str_replace($fexp,'',$fsval);
			if(tp_CheckValEmpty($fsval))
			{
				if($k==$mvmust)
				{
					break;
					$fstrempty=1;
				}
			}
			$fstr.=$fstrexp.$fsval;
			$fstrexp=$fexp;
		}
		if(empty($fstr)||$fstrempty)
		{
			continue;
		}
		$rstr.=$rstrexp.$fstr;
		$rstrexp=$rexp;
	}
	return $rstr;
}
//���ض�ֵ�ֶ�����
function tp_ReturnMoreValueAddF($add,$r,$mid,$f,$ecms=0){
	global $public_r,$emod_r;
	$val=$r;
	if(strstr($emod_r[$mid]['morevaluef'],'|'.$f.','))
	{
		$varname=$f.'_1';
		if(is_array($add[$varname]))
		{
			$val=tp_DoFieldMoreValue($f,$add,$ecms);
		}
		else
		{
			$val='';
		}
	}
	return $val;
}
//��ϸ�ѡ������
function tp_ReturnCheckboxAddF($r,$mid,$f){
	global $public_r,$emod_r;
	$val=$r;
	if(is_array($r)&&strstr($emod_r[$mid]['checkboxf'],','.$f.','))
	{
		$val='';
		$count=count($r);
		for($i=0;$i<$count;$i++)
		{
			$val.=$r[$i].'|';
		}
		if($val)
		{
			$val='|'.$val;
		}
	}
	return $val;
}
//�����ַ�
function tp_qCheckInfoCloseWord($mid,$add,$closewordsf,$closewords){
	if(empty($closewordsf)||$closewordsf=='|'||empty($closewords)||$closewords=='|')
	{
		return '';
	}
	$fr=explode('|',$closewordsf);
	$count=count($fr);
	$r=explode('|',$closewords);
	$countr=count($r);
	for($i=0;$i<$count;$i++)
	{
		if(empty($fr[$i]))
		{
			continue;
		}
		for($j=0;$j<$countr;$j++)
		{
			if($r[$j])
			{
				if(stristr($r[$j],'##'))//����
				{
					$morer=explode('##',$r[$j]);
					if(stristr($add[$fr[$i]],$morer[0])&&stristr($add[$fr[$i]],$morer[1]))
					{
						return elang("HaveCloseWords");
					}
				}
				else
				{
					if(stristr($add[$fr[$i]],$r[$j]))
					{
						return elang("HaveCloseWords");
					}
				}
			}
		}
	}
}
//ȡ���ļ���չ��
function tp_GetFiletype($filename){
	return strtolower(end(explode(".",$filename)));
}
//ִ���ֶκ���
function tp_DoFFun($mid,$f,$value,$isadd=1,$isq=0){
	global $empire,$dbtbpre,$emod_r;
	if($isq==1)//ǰ̨
	{
		$dofun=$isadd==1?$emod_r[$mid]['qadddofunf']:$emod_r[$mid]['qeditdofunf'];
	}
	else//��̨
	{
		$dofun=$isadd==1?$emod_r[$mid]['adddofunf']:$emod_r[$mid]['editdofunf'];
	}
	if(!strstr($dofun,'||'.$f.'!#!'))
	{
		return $value;
	}
	$dfr=explode('||'.$f.'!#!',$dofun);
	$dfr1=explode('||',$dfr[1]);
	$r=explode('##',$dfr1[0]);
	if($r[0])
	{
		$fun=$r[0];
		$value=$fun($mid,$f,$isadd,$isq,$value,$r[1]);
	}
	return $value;
}
//��������/Ӱ�ӵ�ַ
function tp_DoReqDownPath($downpath){
	if(empty($downpath))
	{
		return "";
	}
	$f_exp="::::::";
	$r_exp="\r\n";
	$r=explode($r_exp,$downpath);
	$r1=explode($f_exp,$r[0]);
	$r1[1]=addslashes(RepPostStr($r1[1]));
	return $r1[1];
}

//��֤Ψһ��
function tp_ChIsOnlyAddF($mid,$id,$f,$value,$isq=0){
	global $empire,$dbtbpre,$emod_r;
	$mid=(int)$mid;
	if(strstr($emod_r[$mid]['onlyf'],','.$f.','))
	{
		$id=(int)$id;
		$and='';
		if($id)
		{
			$and=" and id<>$id";
		}
		$value=RepPostStr($value);
		//�����
		$num=$empire->gettotal("select count(*) as total from {$dbtbpre}ecms_".$emod_r[$mid]['tbname']." where ".$f."='".addslashes($value)."'".$and." limit 1");
		//δ���
		if(empty($num))
		{
			$num=$empire->gettotal("select count(*) as total from {$dbtbpre}ecms_".$emod_r[$mid]['tbname']."_check where ".$f."='".addslashes($value)."'".$and." limit 1");
		}
		if($num)
		{
			$GLOBALS['msgisonlyf']=ChGetFname($mid,$f);
			if($isq==1)
			{
				return elang("ReIsOnlyF");
			}
			else
			{
				return elang("ReIsOnlyF");
			}
		}
	}
}
function tp_doehtmlstr($str){
	$str=ehtmlspecialchars($str,ENT_QUOTES);
	return $str;
}
//�ύ�ֶ�ֵ�Ĵ���
function tp_DoqValue($mid,$f,$val){
	global $public_r,$emod_r;
	$val=tp_RepPhpAspJspcodeText($val);
	if(strstr($emod_r[$mid]['editorf'],','.$f.','))//�༭��
	{
		$val=ClearNewsBadCode($val);
	}
	else
	{
		$val=tp_doehtmlstr($val);//�滻html
		if(!strstr($emod_r[$mid]['tobrf'],','.$f.',')&&strstr($emod_r[$mid]['dohtmlf'],','.$f.','))//�ӻس�
		{
			$val=tp_doebrstr($val);
		}
	}
	return $val;
}
//�滻�س�
function tp_doebrstr($str){
	$str=str_replace("\n","<br />",$str);
	return $str;
}
//�����ֶδ���
function tp_DoqSpecialValue($mid,$f,$value,$add,$infor,$ecms=0){
	global $public_r,$loginin,$emod_r;
	if($f=="morepic")//ͼƬ��
	{
		$add['msavepic']=0;
		$value=ReturnMorepicpath($add['msmallpic'],$add['mbigpic'],$add['mpicname'],$add['mdelpicid'],$add['mpicid'],$add,$add['mpicurl_qz'],$ecms,0,($ecms==1?$infor['fstb']:$public_r['filedeftb']));
		$value=tp_doehtmlstr($value);
	}
	elseif($f=="downpath")//���ص�ַ
	{
		$value=DoqReturnDownPath($value,0);
		$value=tp_doehtmlstr($value);
	}
	elseif($f=="onlinepath")//���ߵ�ַ
	{
		$value=DoqReturnDownPath($value,1);
		$value=tp_doehtmlstr($value);
	}
	elseif($f=="newstext")//����
	{
		//Զ�̱���
		//$value=addslashes(CopyImg(stripSlashes($value),$add[copyimg],$add[copyflash],$add[classid],$add[qz_url],$loginin,$add['id'],$add['filepass'],$add['mark'],($ecms==1?$infor['fstb']:$public_r['filedeftb'])));
	}
	//���ı�
	if($emod_r[$mid]['savetxtf']&&$f==$emod_r[$mid]['savetxtf'])
	{
		if($ecms==1)
		{
			//����Ŀ¼
			$newstexttxt_r=explode("/",$infor[$f]);
			$thetxtfile=$newstexttxt_r[2];
			$truevalue=MkDirTxtFile($newstexttxt_r[0]."/".$newstexttxt_r[1],$thetxtfile);
		}
		else
		{
			//����Ŀ¼
			$thetxtfile=GetFileMd5();
			$truevalue=MkDirTxtFile(date("Y/md"),$thetxtfile);
		}
		//д���ļ�
		EditTxtFieldText($truevalue,$value);
		$value=$truevalue;
	}
	return $value;
}
//�����ֶ�
function tp_ReturnQAddinfoF($mid,$add,$infor,$classid,$filepass,$userid,$username,$ecms=0){
	global $empire,$dbtbpre,$public_r,$emod_r,$ecms_config;
	$ret_r=array();
	$pr=DB::getRow("select qaddtran,qaddtransize,qaddtranimgtype,qaddtranfile,qaddtranfilesize,qaddtranfiletype,closewords,closewordsf from {$dbtbpre}enewspublic limit 1");
	$isadd=$ecms==0?1:0;
	$e=tp_qCheckInfoCloseWord($mid,$add,$pr['closewordsf'],$pr['closewords']);//�����ַ���֤
	if($e)return $e;
	//�������ֶ�
	$pagef=$emod_r[$mid]['pagef'];
	$mustr=explode(",",$emod_r[$mid]['mustqenterf']);
	$mustcount=count($mustr)-1;
	for($i=1;$i<$mustcount;$i++)
	{
		$mf=$mustr[$i];
		if(strstr($emod_r[$mid]['filef'],','.$mf.',')||strstr($emod_r[$mid]['imgf'],','.$mf.',')||strstr($emod_r[$mid]['flashf'],','.$mf.',')||$mf=='downpath'||$mf=='onlinepath')//����
		{
			$mfilef=$mf."file";
			//�ϴ��ļ�
			if($_FILES[$mfilef]['name'])
			{
				if(strstr($emod_r[$mid]['imgf'],','.$mf.','))//ͼƬ
				{
					if(!$pr['qaddtran'])
					{
						return elang("CloseQTranPic");
					}
				}
				else//����
				{
					if(!$pr['qaddtranfile'])
					{
						return elang("CloseQTranFile");
					}
				}
			}
			elseif(!trim($add[$mf])&&!$infor[$mf])
			{
				return elang("EmptyQMustF");
			}
		}
		else
		{
			$chmustval=tp_ReturnCheckboxAddF($add[$mf],$mid,$mf);//��ѡ��
			$chmustval=tp_ReturnMoreValueAddF($add,$chmustval,$mid,$mf,$ecms);//��ֵ
			if(!trim($chmustval))
			{
				return elang("EmptyQMustF");
			}
		}
	}
	//�ֶδ���
	$dh="";
	$tranf="";
	$fr=explode(',',$emod_r[$mid]['qenter']);
	$count=count($fr)-1;
	for($i=1;$i<$count;$i++)
	{
		$f=$fr[$i];
		if($f=='special.field'||($ecms==0&&!strstr($emod_r[$mid]['canaddf'],','.$f.','))||($ecms==1&&!strstr($emod_r[$mid]['caneditf'],','.$f.',')))
		{continue;}
		//����
		$add[$f]=str_replace('[!#@-','ecms',$add[$f]);
		if(strstr($emod_r[$mid]['filef'],','.$f.',')||strstr($emod_r[$mid]['imgf'],','.$f.',')||strstr($emod_r[$mid]['flashf'],','.$f.',')||$f=='downpath'||$f=='onlinepath')
		{
			//�ϴ�����
			$filetf=$f."file";
			if($_FILES[$filetf]['name'])
			{
				$filetype=tp_GetFiletype($_FILES[$filetf]['name']);//ȡ���ļ�����
				if(CheckSaveTranFiletype($filetype)/*ʹ��ϵͳ����*/)
				{
					return elang("NotQTranFiletype");
				}
				if(strstr($emod_r[$mid]['imgf'],','.$f.','))//ͼƬ
				{
					if(!$pr['qaddtran'])
					{
						return elang("CloseQTranPic");
					}
					if(!strstr($pr['qaddtranimgtype'],"|".$filetype."|"))
					{
						return elang("NotQTranFiletype");
					}
					if($_FILES[$filetf]['size']>$pr['qaddtransize']*1024)
					{
						return elang("TooBigQTranFile");
					}
					if(!strstr($ecms_config['sets']['tranpicturetype'],','.$filetype.','))
					{
						return elang("NotQTranFiletype");
					}
				}
				else//����
				{
					if(!$pr['qaddtranfile'])
					{
						return elang("CloseQTranFile");
					}
					if(!strstr($pr['qaddtranfiletype'],"|".$filetype."|"))
					{
						return elang("NotQTranFiletype");
					}
					if($_FILES[$filetf]['size']>$pr['qaddtranfilesize']*1024)
					{
						return elang("TooBigQTranFile");
					}
					if(strstr($emod_r[$mid]['flashf'],','.$f.','))//flash
					{
						if(!strstr($ecms_config['sets']['tranflashtype'],",".$filetype.","))
						{return elang("NotQTranFiletype");}
					}
					if($f=="onlinepath")//��Ƶ
					{
						if(strstr($wmv_type,",".$filetype.","))
						{}
					}
				}
				$tranf.=$dh.$f;
				$dh=",";
				$fval="[!#@-".$f."-@!]";
			}
			else
			{
				if($public_r['modinfoedittran']==1)
				{
					$fval=$add[$f];
					if($ecms==1&&$infor[$f]&&!trim($fval))
					{
						$fval=$infor[$f];
						//�����ֶ�
						if($f=="downpath"||$f=="onlinepath")
						{
							$fval=tp_DoReqDownPath($fval);
						}
					}
				}
				else
				{
					$fval='';
					if($ecms==1)
					{
						$fval=$infor[$f];
						//�����ֶ�
						if($f=="downpath"||$f=="onlinepath")
						{
							$fval=tp_DoReqDownPath($fval);
						}
					}
				}
			}
		}
		elseif($f=='newstime')//ʱ��
		{
			if($add[$f])
			{
				$fval=to_time($add[$f]);/*ʹ��ϵͳ����*/
			}
			else
			{
				$fval=time();
			}
		}
		elseif($f=='newstext')//����
		{
			if($ecms==0)
			{
				$fval=tp_DoReplaceKeyAndWord($add[$f],1,$classid);//�滻�ؼ��ֺ��ַ�
			}
			else
			{
				$fval=$add[$f];
			}
		}
		elseif($f=='infoip')	//ip
		{
			$fval=egetip();
		}
		elseif($f=='infoipport')	//ip�˿�
		{
			$fval=egetipport();
		}
		elseif($f=='infozm')	//��ĸ
		{
			$fval=$add[$f]?$add[$f]:GetInfoZm($add[title]);/*ʹ��ϵͳ����*/
		}
		else
		{
			$add[$f]=tp_ReturnCheckboxAddF($add[$f],$mid,$f);//��ѡ��
			$add[$f]=tp_ReturnMoreValueAddF($add,$add[$f],$mid,$f,$ecms);//��ֵ
			$fval=$add[$f];
		}
		$fval=eDoInfoTbfToQj($emod_r[$mid]['tbname'],$f,$fval,$public_r['qtoqjf']);/*ʹ��ϵͳ����*/
		$fval=tp_DoFFun($mid,$f,$fval,$isadd,1);//ִ�к���
		$modispagef=$pagef==$f?1:0;
		$fval=RepTempvarPostStrT($fval,$modispagef);/*ʹ��ϵͳ����*/
		if($pagef!=$f)
		{
			$fval=RepTempvarPostStr($fval);/*ʹ��ϵͳ����*/
		}
		tp_ChIsOnlyAddF($mid,$infor[id],$f,$fval,1);//Ψһֵ
		$fval=tp_DoqValue($mid,$f,$fval);
		$fval=tp_DoqSpecialValue($mid,$f,$fval,$add,$infor,$ecms);
		$fval=RepPostStr2($fval);/*ʹ��ϵͳ����*/
		if($ecms==1)
		{
			SameDataAddF($info[id],$classid,$mid,$f,$fval);/*ʹ��ϵͳ����*/
		}
		$fval=addslashes($fval);
		if($ecms==0)//���
		{
			if(strstr($emod_r[$mid]['tbdataf'],','.$f.','))//����
			{
				$ret_r[2].=",".$f;
				$ret_r[3].=",'".$fval."'";
			}
			else
			{
				$ret_r[0].=",".$f;
				$ret_r[1].=",'".$fval."'";
			}
		}
		else//�༭
		{
			if($f=='infoip'||$f=='infoipport')	//ip
			{
				continue;
			}
			if(strstr($emod_r[$mid]['tbdataf'],','.$f.','))//����
			{
				$ret_r[3].=",".$f."='".$fval."'";
			}
			else
			{
				$ret_r[0].=",".$f."='".$fval."'";
			}
		}
	}
	//�ϴ�����
	if($tranf)
	{
		if($ecms==0)
		{
			$infoid=0;
		}
		else
		{
			$infoid=$infor['id'];
			$filepass=0;
		}
		$tranr=explode(",",$tranf);
		$count=count($tranr);
		for($i=0;$i<$count;$i++)
		{
			$tf=$tranr[$i];
			$tffile=$tf."file";
			$tfr=DoTranFile($_FILES[$tffile]['tmp_name'],$_FILES[$tffile]['name'],$_FILES[$tffile]['type'],$_FILES[$tffile]['size'],$classid);
			if($tfr['tran'])
			{
				//�ļ�����
				$mvf=$tf."mtfile";
				if(strstr($emod_r[$mid]['imgf'],','.$tf.','))//ͼƬ
				{
					$type=1;
				}
				elseif(strstr($emod_r[$mid]['flashf'],','.$tf.','))//flash
				{
					$type=2;
				}
				elseif($add[$mvf]==1)//��ý��
				{
					$type=3;
				}
				else//����
				{
					$type=0;
				}
				//д�����ݿ�
				$filetime=time();
				$filesize=(int)$_FILES[$tffile]['size'];
				$classid=(int)$classid;
				eInsertFileTable($tfr[filename],$filesize,$tfr[filepath],'[Member]'.$username,$classid,'['.$tf.']'.addslashes(RepPostStr($add[title])),$type,$infoid,$filepass,$public_r[fpath],0,0,($ecms==1?$infor['fstb']:$public_r['filedeftb']));
				//ɾ�����ļ�
				if($ecms==1&&$infor[$tf])
				{
					DelYQTranFile($classid,$infor['id'],$infor[$tf],$tf,$infor['fstb']);
				}
				$repfval=$tfr['url'];
			}
			else
			{
				$repfval=$infor[$tf];
				//�����ֶ�
				if($tf=="downpath"||$tf=="onlinepath")
				{
					$repfval=tp_DoReqDownPath($repfval);
				}
			}
			if($ecms==0)//���
			{
				$ret_r[1]=str_replace("[!#@-".$tf."-@!]",$repfval,$ret_r[1]);
				$ret_r[3]=str_replace("[!#@-".$tf."-@!]",$repfval,$ret_r[3]);
			}
			else//�༭
			{
				$ret_r[0]=str_replace("[!#@-".$tf."-@!]",$repfval,$ret_r[0]);
				$ret_r[3]=str_replace("[!#@-".$tf."-@!]",$repfval,$ret_r[3]);
			}
		}
	}
	$ret_r[4]=$emod_r[$mid]['deftb'];
	return $ret_r;
}
//һ����Ա����Ϣ
function tp_qCheckMemberOneInfo($tbname,$mid,$classid,$userid){
	global $empire,$dbtbpre,$class_r;
	$classid=(int)$classid;
	$userid=(int)$userid;
	if(empty($class_r[$classid]['oneinfo']))
	{
		return '';
	}
	$GLOBALS['classqoneinfo']=$class_r[$classid]['oneinfo'];
	//��
	$num=DB::getValue("select count(*) as total from {$dbtbpre}ecms_".$tbname." where userid='$userid' and ismember=1 and classid='$classid'");
	if($num>=$class_r[$classid]['oneinfo'])
	{
		return elang('OneInfoAddInfo');
	}
	//��˱�
	$cknum=DB::getValue("select count(*) as total from {$dbtbpre}ecms_".$tbname."_check where userid='$userid' and ismember=1 and classid='$classid'");
	$allnum=$num+$cknum;
	if($allnum>=$class_r[$classid]['oneinfo'])
	{
		return elang('OneInfoAddInfo');
	}
}

//��֤ͬһIP����Ϣ��
function tp_eCheckIpAddInfoNum($ip,$tbname,$mid,$checked=1){
	global $empire,$dbtbpre,$public_r,$emod_r;
	if(!$public_r['ipaddinfonum']||!$public_r['ipaddinfotime'])
	{
		return '';
	}
	//�Ƿ���IP�ֶ�
	$qenterf=$emod_r[$mid]['qenter'];
	if(!strstr($qenterf,',infoip,'))
	{
		return '';
	}
	$infotb=ReturnInfoMainTbname($tbname,$checked);
	//ʱ��
	$cktime=time()-$public_r['ipaddinfotime']*3600;
	$num=DB::getValue("select count(*) as total from ".$infotb." where newstime>$cktime and infoip='$ip'");
	if($num+1>$public_r['ipaddinfonum']){
		return elang('IpMaxAddInfo');
	}
	return '';
}

//��ϱ�������
function tp_TitleFont($titlefont,$titlecolor=''){
	$add=$titlecolor.',';
	if($titlecolor=='no')
	{
		$add='';
	}
	if($titlefont[b])//����
	{$add.='b|';}
	if($titlefont[i])//б��
	{$add.='i|';}
	if($titlefont[s])//ɾ����
	{$add.='s|';}
	if($add==',')
	{
		$add='';
	}
	return $add;
}
//�滻php����
function tp_RepPhpAspJspcodeText($string){
	//$string=str_replace("<?xml","[!--ecms.xml--]",$string);
	$string=str_replace("<\\","&lt;\\",$string);
	$string=str_replace("\\>","\\&gt;",$string);
	$string=str_replace("<?","&lt;?",$string);
	$string=str_replace("<%","&lt;%",$string);
	if(@stristr($string,' language'))
	{
		$string=preg_replace(array('!<script!i','!</script>!i'),array('&lt;script','&lt;/script&gt;'),$string);
	}
	//$string=str_replace("[!--ecms.xml--]","<?xml",$string);
	$string=str_replace("<!--code.start-->","&lt;!--code.start--&gt;",$string);
	$string=str_replace("<!--code.end-->","&lt;!--code.end--&gt;",$string);
	return $string;
}
//���Ͷ����
function tp_DoQCheckAddNum($userid,$groupid){
	global $empire,$dbtbpre,$level_r,$public_r;
	$userid=(int)$userid;
	$ur=DB::getRow("select userid,todayinfodate,todayaddinfo from {$dbtbpre}enewsmemberpub where userid='$userid' limit 1");
	$thetoday=date("Y-m-d");
	if($ur['userid'])
	{
		if($thetoday!=$ur['todayinfodate'])
		{
			$query="update {$dbtbpre}enewsmemberpub set todayinfodate='$thetoday',todayaddinfo=1 where userid='$userid'";
		}
		else
		{
			if($ur['todayaddinfo']>=$level_r[$groupid]['dayaddinfo'])
			{
				return 'err='.elang("CrossDayInfo");
			}
			$query="update {$dbtbpre}enewsmemberpub set todayaddinfo=todayaddinfo+1 where userid='$userid'";
		}
	}
	else
	{
		$query="replace into {$dbtbpre}enewsmemberpub(userid,todayinfodate,todayaddinfo) values('$userid','$thetoday',1);";
	}
	return $query;
}

//�������Ƿ��㹻
function tp_MCheckEnoughFen($userfen,$userdate,$fen){
	if(!($userdate-time()>0))
	{
		if($userfen+$fen<0)
		{
			return elang("HaveNotFenAQinfo");
		}
	}
}

//���û�Ͷ����֤
function tp_qCheckNewMemberAddInfo($registertime){
	global $public_r;
	if(empty($public_r['newaddinfotime']))
	{
		return false;
	}
	$registertime=eReturnMemberIntRegtime($registertime);
	if(time()-$registertime<=$public_r['newaddinfotime']*60)
	{
		return elang('NewMemberAddInfoError');
	}
	return false;
}
//ʵ����֤
function tp_eCheckHaveTruename($mod,$userid,$username,$isern,$checked,$ecms=0){
	global $empire,$dbtbpre,$public_r,$ecms_config,$ecms_topagesetr,$enews;
	if(empty($public_r['openern']))
	{
		return '';
	}
	if(!strstr($public_r['openern'],','.$mod.','))
	{
		return '';
	}
	if($userid)
	{
		if($checked==0)
		{
			return elang("NotCheckedUser");
		}
	}
	if(!$isern)
	{
		return elang('NotHaveTrueName');
	}
}
//���ӵ���
function tp_AddInfoFen($cardfen,$userid,$checkfen=1){
	global $empire,$dbtbpre;
	$cardfen=(int)$cardfen;
	if(!$cardfen)
	{
		return '';
	}
	//checkfen
	if($checkfen==1)
	{
		if($cardfen<0)
		{
			$ur=$empire->fetch1("select ".eReturnSelectMemberF('userid,userdate,userfen')." from ".eReturnMemberTable()." where ".egetmf('userid')."='$userid' limit 1");
			if(!$ur['userid'])
			{
				return '';
			}
			if($ur['userdate']-time()>0)
			{
				return '';
			}
			if($cardfen+$ur['userfen']<0)
			{
				$cardfen=$ur['userfen']*-1;
			}
		}
	}
	$sql=$empire->query("update ".eReturnMemberTable()." set ".egetmf('userfen')."=".egetmf('userfen')."+".$cardfen." where ".egetmf('userid')."='$userid'");
}
//Ͷ��Ȩ�޼��
function tp_DoQCheckAddLevel($classid,$userid,$username,$rnd,$ecms=0,$isadd=0){
	global $empire,$dbtbpre,$level_r,$public_r;
	$classid=(int)$classid;
	$user=array();
	$r=DB::getRow("select * from {$dbtbpre}enewsclass where classid='$classid'");
	if(!$r['classid']||$r[wburl]){
		return elang("EmptyQinfoCid");
	}
	if(!$r['islast']){
		return elang("MustLast");
	}
	if($r['openadd']){
		return elang("NotOpenCQInfo");
	}
	//�Ƿ��½
	if($ecms==1||$ecms==2||($r['qaddgroupid']&&$r['qaddgroupid']<>',')){
		$user=tp_loginuser();
		//��֤�»�ԱͶ��
		if($isadd==1&&$public_r['newaddinfotime'])
		{
			$e=tp_qCheckNewMemberAddInfo($user[registertime]);
			if($e)return $e;
		}
	}
	//��Ա��
	if($r['qaddgroupid']&&$r['qaddgroupid']<>',')
	{
		if(!strstr($r['qaddgroupid'],','.$user[groupid].','))
		{
			return elang("HaveNotLevelAQinfo");
		}
	}
	if($isadd==1)
	{
		//����Ƿ��㹻����
		if($r['addinfofen']<0&&$user['userid'])
		{
			$e=tp_MCheckEnoughFen($user['userfen'],$user['userdate'],$r['addinfofen']);
			if($e)return $e;
		}
		//���Ͷ����
		if($r['qaddgroupid']&&$r['qaddgroupid']<>','&&$level_r[$user[groupid]]['dayaddinfo'])
		{
			$r['checkaddnumquery']=tp_DoQCheckAddNum($user['userid'],$user['groupid']);
			if(substr($r['checkaddnumquery'],0,4)=='err='){
				return substr($r['checkaddnumquery'],4);
			}
		}
	}
	//���
	if(($ecms==0||$ecms==1)&&$userid)
	{
		if(!$user[groupid])
		{
			$user=tp_loginuser();
		}
		if($level_r[$user[groupid]]['infochecked'])
		{
			$r['checkqadd']=1;
			$r['qeditchecked']=0;
		}
	}
	//ʵ����֤
	$e=tp_eCheckHaveTruename('info',$user['userid'],$user['username'],$user['isern'],$user['checked'],0);
	if($e)return $e;
	return $r;
}

function tp_eCheckTimeCloseDo($ecms){
	global $public_r;
	if(stristr($public_r['timeclosedo'],','.$ecms.','))
	{
		$h=date('G');
		if(strstr($public_r['timeclose'],','.$h.','))
		{
			return elang('ThisTimeCloseDo');
		}
	}
	return false;
}

//��֤�ύ��Դ
function tp_CheckCanPostUrl(){
	global $public_r;
	if($public_r['canposturl'])
	{
		$r=explode("\r\n",$public_r['canposturl']);
		$count=count($r);
		$b=0;
		for($i=0;$i<$count;$i++)
		{
			if(strstr($_SERVER['HTTP_REFERER'],$r[$i]))
			{
				$b=1;
				break;
			}
		}
		if($b==0)
		{
			return elang('NotCanPostUrl');
		}
	}
	return false;
}

//��ԭ�س�
function tp_dorebrstr($str){
	$str=str_replace("<br />","\n",$str);
	$str=str_replace("<br>","\n",$str);
	return $str;
}









if(!function_exists('DoReqValue')){
//�����ֶ�ֵ�Ĵ���
function DoReqValue($mid,$f,$val){
	global $public_r,$emod_r;
	if($emod_r[$mid]['savetxtf']&&$emod_r[$mid]['savetxtf']==$f)//���ı�
	{
		$val=stripSlashes(GetTxtFieldText($val));
	}
	if(strstr($emod_r[$mid]['editorf'],','.$f.','))//�༭��
	{
		return $val;
	}
	$val=/*dorehtmlstr*/($val);//�滻html
	if(!strstr($emod_r[$mid]['tobrf'],','.$f.',')&&strstr($emod_r[$mid]['dohtmlf'],','.$f.','))//�ӻس�
	{
		$val=tp_dorebrstr($val);
	}
	return $val;
}
}
if(!function_exists('DelSingleInfoOtherData')){
//ɾ����Ϣ��ؼ�¼
function DelSingleInfoOtherData($classid,$id,$r,$delfile=0,$delpl=0){
	global $empire,$dbtbpre,$public_r,$class_r,$emod_r;
	$pubid=ReturnInfoPubid($classid,$id);
	//ɾ���������¼
	DB::execute("delete from {$dbtbpre}enewswfinfo where id='$id' and classid='$classid'");
	DB::execute("delete from {$dbtbpre}enewswfinfolog where id='$id' and classid='$classid'");
	DB::execute("delete from {$dbtbpre}enewsinfovote where pubid='$pubid'");
	DB::execute("delete from {$dbtbpre}enewsdiggips where id='$id' and classid='$classid'");
	DB::execute("delete from {$dbtbpre}enewsztinfo where id='$id' and classid='$classid'");
	event('DeleteOtherFile',[$classid,$id,$r,$delfile,$delpl]);
	if($delfile==0){
		DelNewsTheFile($id,$classid,$r['fstb'],$delpl,$r['restb']);//ɾ������
	}
}
}
if(!function_exists('DelNewsTheFile')){
//ɾ����Ϣ����
function DelNewsTheFile($id,$classid,$fstb='1',$delpl=0,$restb='1'){
	global $empire,$dbtbpre;
	if(empty($id))
	{
		return "";
	}
	$pubid=ReturnInfoPubid($classid,$id);
	$i=0;
	$list=DB::query("select classid,filename,path,fpath from {$dbtbpre}enewsfile_{$fstb} where pubid='$pubid'");
	foreach($list as $v)
	{
		$i=1;
		DoDelFile($v);
    }
	if($i)
	{
		DB::execute("delete from {$dbtbpre}enewsfile_{$fstb} where pubid='$pubid'");
	}
	//ɾ������
	if($delpl==0)
	{
		DB::execute("delete from {$dbtbpre}enewspl_{$restb} where pubid='$pubid'");
	}
}
}
if(!function_exists('DelNewsFile')){
//ɾ����Ϣ�ļ�
function DelNewsFile($filename,$newspath,$classid,$newstext,$groupid=0){
	global $class_r,$addgethtmlpath;
	event('DeleteNewsFile',[$filename,$newspath,$classid,$newstext,$groupid]);
	if(!trim($filename)||!$classid||!$class_r[$classid][classpath])
	{
		return '';
	}
	if(strstr($filename,'/'))
	{
		$etfilename=ReturnInfoSPath($filename);
		if(!trim($etfilename)||strstr($etfilename,'/'))
		{
			return '';
		}
	}
	//�ļ�����
	if($groupid)
	{
		$filetype=".php";
	}
	else
	{
		$filetype=$class_r[$classid][filetype];
	}
	//�Ƿ�������Ŀ¼
	if(empty($newspath))
	{
		$mynewspath="";
    }
	else
	{
		$mynewspath=$newspath."/";
    }
	$iclasspath=ReturnSaveInfoPath($classid,$id);
	$r=explode("[!--empirenews.page--]",$newstext);
	$pagecount=count($r);
	for($i=1;$i<=$pagecount;$i++)
	{
		if(strstr($filename,'/'))
		{
			DelPath(eReturnTrueEcmsPath().$iclasspath.$mynewspath.ReturnInfoSPath($filename));
			break;
		}
		else
		{
			if($i==1)
			{
				$file=eReturnTrueEcmsPath().$iclasspath.$mynewspath.$filename.$filetype;
			}
			else
			{
				$file=eReturnTrueEcmsPath().$iclasspath.$mynewspath.$filename."_".$i.$filetype;
			}
			DelFiletext($file);
		}
	}
}
}
?>
<?php
namespace app\common\traits;
use app\common\model\Db;
use app\common\model\ErrorTimes;
use app\common\model\HdDiqu as Diqu;
use app\common\model\Enewsmember as UserModel;
use think\facade\Validate;
trait UserTrait{
	protected $validateinfo = 'app\\common\\validate\\User.editinfo';
	protected $validatesafe = 'app\\common\\validate\\User.editsafe';
	public function dodelmyfavlist($ids){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('���ȵ�¼��');
		if(count($ids)==0)return $this->rterr('ID����');
		$sql=$this->delfavlistsql($ids);
		DB::execute("delete from {$dbtbpre}enewsfava ".$sql);
		return $this->rtok('ȡ���ɹ���');
	}
	public function dodelmyzanlist($ids){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('���ȵ�¼��');
		if(count($ids)==0)return $this->rterr('ID����');
		$sql=$this->delfavlistsql($ids);
		DB::execute("delete from {$dbtbpre}xf_zan_log ".$sql);
		return $this->rtok('ȡ���ɹ���');
	}
	private function delfavlistsql($ids){
		$user=tp_loginuser();
		if(!$user)return $this->rterr('���ȵ�¼��');
		$sqla=[];
		foreach($ids as $v){
			$v=tp_str2arr($v,'|');
			$v[0]=intval($v[0]);
			$v[1]=intval($v[1]);
			$sqla[]="(classid=$v[0] and id=$v[1])";
		}
		return ' where ('.tp_arr2str($sqla,' or ').')'." and userid='$user[userid]' ";
	}
	public function domyfav(){
		global $dbtbpre,$class_r;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('���ȵ�¼��');
		$favlist=DB::query("select classid,id,favatime from {$dbtbpre}enewsfava where userid='$user[userid]'");
		return $this->domylist1($favlist);
	}
	public function domylist1($reslist){
		global $dbtbpre,$class_r;
		$page=input('get.page/d',1);
		$pagesize=10;
		$fields='classid,id,title,ftitle,titleurl,smalltext,titlepic as img,ismember,userid,username,newstime';
		$classids=[];
		$sqla=[];
		$tbnames=[];
		$timelist=[];
		foreach($reslist as $k=>$v){
			$tbname=$class_r[$v['classid']]['tbname'];
			$classids[]=$v['classid'];
			$sqla[$tbname][]="id='{$v[id]}'";
			if($v['favatime']){
				$time=$v['favatime'];
			}else{
				$time=date('Y-m-d H:i:s',$v['addtime']);
			}
			$timelist[$v['classid'].'_'.$v['id']]=$time;
			if($tbname&&!in_array($tbname,$tbnames))$tbnames[]=$tbname;
		}
		$sql='';
		foreach($tbnames as $tbname){
			$where=tp_arr2str($sqla[$tbname],' or ');
			$rscountsqla[]="select count(*) as c1 from {$dbtbpre}ecms_{$tbname} where $where";
			if($where)$sql.=($sql?' union ':'')." select {$fields},'{$tbname}' as tbname from {$dbtbpre}ecms_{$tbname} where $where";
		}
		if($sql){
			$rscountsql=tp_arr2str($rscountsqla,' UNION ALL ');
			$rscount=DB::getValue('select sum(c1) from ('.$rscountsql.') as c');
			$limit=($page-1)*$pagesize;
			$sql.=" limit $limit,$pagesize ";
			$list=DB::query($sql);
		}else{
			$list=[];
		}
		foreach($list as $k=>$v){
			if(!$v['ftitle'])$v['ftitle']=$v['title'];
			$v['ismember']=($v['ismember']=='1');
			$v['newstimestamp']=$v['newstime'];
			$v['newstime2']=date("Y-m-d H:i:s",$v['newstime']);
			$v['newstime']=date("Y-m-d",$v['newstime']);
			$v['tbname']=$class_r[$v['classid']]['tbname'];
			$v['isOriginal']=0;
			$v['addtime']=$timelist[$v['classid'].'_'.$v['id']];
			$list[$k]=$v;
		}
		return $this->rtok(['list'=>$list,'pagesize'=>$pagesize,'rscount'=>$rscount,'page'=>$page]);
	}
	public function domyzan(){
		global $dbtbpre,$class_r;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('���ȵ�¼��');
		$zanlist=DB::query("select classid,id,addtime from {$dbtbpre}xf_zan_log where userid='$user[userid]'");
		return $this->domylist1($zanlist);
	}
	function dodelnewslist(){
		$user=tp_loginuser();
		if(!$user)return $this->rterr('���ȵ�¼��');
		$userid=$user['userid'];
		$infos=input('get.infoid');
		foreach($infos as $info){
			$info=explode('|',$info);
			$classid=(int)$info[0];
			$id=(int)$info[1];
			$rt=$this->dodelonenews($classid,$id,$userid,true);
			if(!$rt[0]){
				$this->rterr($rt[1]);
			}
		}
		return $this->rtok('ɾ���ɹ���');
	}
	function dodelnews(){
		global $class_r,$emod_r;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('���ȵ�¼��');
		$userid=$user['userid'];
		$classid=input('get.classid/d');
		$id=input('get.id/d');
		return $this->dodelonenews($classid,$id,$userid,true);
	}
	public function dotougao($tbname=''){
		global $class_r;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('���ȵ�¼��');
		$userid=$user['userid'];
		$page=input('get.page/d',1);
		$view=input('get.a2');
		if(!$tbname)$tbname=input('get.tbname');
		if(!$tbname||!Validate::isAlpha($tbname)||!$m=enews($tbname,$view!='view')){
			return $this->rterr('��������');
		}
		$pagesize=10;
		$rscount=$m->getUserCount($userid);
		$pagecount=ceil($rscount/$pagesize);
		$list=$m->getUserList($userid,$page,$pagesize,['classid','id','title','ftitle','titleurl','smalltext','titlepic'=>'img','ismember','userid','username','newstime']);
		foreach($list as $k=>$v){
			if(!$v['ftitle'])$v['ftitle']=$v['title'];
			$v['ismember']=($v['ismember']=='1');
			$v['newstimestamp']=$v['newstime'];
			$v['newstime2']=date("Y-m-d H:i:s",$v['newstime']);
			$v['newstime']=date("Y-m-d",$v['newstime']);
			$v['tbname']=$class_r[$v['classid']]['tbname'];
			$v['isOriginal']=0;
			$list[$k]=$v;
		}
		return $this->rtok(array('list'=>$list,'pagecount'=>$pagecount,'page'=>$page,'pagesize'=>$pagesize,'rscount'=>$rscount));
	}
	public function doeditinfo($user,$post){
		$post['userid']=$user['userid'];
		if($post['topimg']!=$user['topimg']){
			@unlink(ECMS_PATH.@substr($user['topimg'],1));
		}
		if($post['topimg']==':del'){
			$post['topimg']='';
		}
		$diqu=explode('-',$post['diqu']);
		if(count($diqu)==3){
			$diqu[0]=str_replace(array('ʡ','��','������','�ر�������','׳��','����','ά���','��'),'',$diqu[0]);
			$post['sheng']=Diqu::name2id(1,$diqu[0]);
			$post['shi']=Diqu::name2id(2,$diqu[1]);
			$post['quxian']=Diqu::name2id(3,$diqu[2]);
		}
		unset($post['diqu']);
		if(count($post['fanwei'])>0)$post['fanwei']='|'.tp_arr2str($post['fanwei'],'|').'|';
		$return=$this->validate($post,$this->validateinfo);
		if($return===true){
			$return=UserModel::editinfo($post);
			return $return?$this->rtok('�޸ĳɹ���'):$this->rterr('�޸�ʧ�ܣ�');
		}else{
			return $this->rterr($return);
		}
	}
	public function dogetpassword(){
		global $dbtbpre;
		$vcode=new \Vcode();
		$post=input('post.');
		if(!$post['password'])$this->rterr('����д���룡');
		$err=$vcode->checkVcode($post['email'],$post['vcode'],1);
		if($err)return $this->rterr($err);
		$vcode->expVcode($post['email'],$post['vcode'],1);
		$user=DB::table("{$dbtbpre}enewsmember")->where([['email','=',$post['email']]])->find();
		if(!$user)$this->rterr('��ʹ�ô�������û���');
		load_uc();
		if(defined('UC_OPEN')){
			$ucresult = uc_user_edit($user['username'],'',$post['password'],'',1);
			if($ucresult == -1){
				return $this->rterr('�����벻��ȷ');
			}elseif($ucresult == -4){
				return $this->rterr('Email��ʽ����');
			}elseif($ucresult == -5){
				return $this->rterr('Email������ע��');
			}elseif($ucresult == -6){
				return $this->rterr('��Email�Ѿ���ע��');
			}elseif($ucresult < 0){
				//return $this->rterr('δ֪����');
			}
		}
		$save['password']=md5(md5($post['password']).$user['salt']);
		$return=UserModel::editinfo($save);
		if($return){
			//if($save['password'])set_login($user['userid'],$user['username'],$post['password'],$user['groupid']);
			return $this->rtok('�޸ĳɹ���');
		}
		return $this->rterr('�޸�ʧ�ܣ�');
	}
	public function doeditsafe($user,$post){
		$post['userid']=$user['userid'];
		$return=$this->validate($post,$this->validatesafe);
		if($return===true){
			load_uc();
			if(defined('UC_OPEN')){
				$ucinfo=uc_get_user($user['username']);
				if(!$ucinfo)return $this->rterr('�û������ڣ�');
				list($ucuserid,$ucusername,$ucemail)=$ucinfo;
				if($ucemail==$post['email']){
					$email='';//��������Ϊ�� ����Ϊuc���ж��Ƿ���ڴ����� �û����ڵ�����Ҳ�ᱻ�жϴ���
				}else{
					$email=$post['email'];
				}
				$ucresult = uc_user_edit($user['username'],$post['oldpassword'],$post['password'],$email);
				if($ucresult == -1){
					return $this->rterr('�����벻��ȷ');
				}elseif($ucresult == -4){
					return $this->rterr('Email��ʽ����');
				}elseif($ucresult == -5){
					return $this->rterr('Email������ע��');
				}elseif($ucresult == -6){
					return $this->rterr('��Email�Ѿ���ע��');
				}elseif($ucresult < 0){
					//return $this->rterr('δ֪����');
				}
			}else{
				$check=$this->checkpassword($user,$post['oldpassword']);
				if(!$check){
					return $this->rterr('���������');
				}
			}
			$save=['email'=>$post['email'],'userid'=>$user['userid']];
			if($post['password']){
				$save['password']=md5(md5($post['password']).$user['salt']);
			}
			$return=UserModel::editinfo($save);
			if($return){
				if($save['password']&&cookie('user_auth'))set_login($user['userid'],$user['username'],$post['password'],$user['groupid']);
				return $this->rtok('�޸ĳɹ���');
			}
			return $this->rterr('�޸�ʧ�ܣ�');
		}else{
			return $this->rterr($return);
		}
	}
	public function doavatar($user){
		$uc_path=config('config.uc_path');
		$userid=$user['userid'];
		$base64 = $_POST['base64'];
		if(!$base64)return $this->rterr('���ݴ���');
		$base64 = str_replace('data:image/jpeg;base64,','',$base64);
		$img_content = @base64_decode($base64);
		if(!$img_content)$this->rterr('���ݴ���');
		if($uc_path){
			$realdir=dirname(ECMS_PATH.$uc_path.'/data/avatar/'.uc_get_avatar($userid, 'original'));
			if(!file_exists($realdir))mkdir($realdir,0777,true);
			$avatar = ECMS_PATH.$uc_path.'/data/avatar/'.uc_get_avatar($userid, 'original');
			$bigavatar = ECMS_PATH.$uc_path.'/data/avatar/'.uc_get_avatar($userid, 'big');
			$middleavatar = ECMS_PATH.$uc_path.'data/avatar/'.uc_get_avatar($userid, 'middle');
			$smallavatar = ECMS_PATH.$uc_path.'data/avatar/'.uc_get_avatar($userid, 'small');
			file_put_contents($avatar,$img_content);
			@tp_imageresize($avatar,$bigavatar,200,200,true);
			@tp_imageresize($avatar,$middleavatar,120,120,true);
			@tp_imageresize($avatar,$smallavatar,48,48,true);
		}else{
			$savepath='/d/avatar/'.date('Ymd').'/';
			$realdir=ECMS_PATH.substr($savepath,1);
			if(!file_exists($realdir))mkdir($realdir,0777,true);
			$avatar = $savepath.$userid.'_'.substr(md5(microtime()),0,10).'.jpg';
			file_put_contents(ECMS_PATH.substr($avatar,1),$img_content);
			DB::name('Enewsmemberadd')->where('userid',$userid)->save(['userpic'=>$avatar]);
		}
		return $this->rtok(array('msg'=>'�ϴ��ɹ���','avatar'=>refresh_user_avatar($userid)));
	}
	public function doregister(){
		$username=input2('post.username');
		$password=input2('post.password');
		$repassword=input2('post.repassword');
		$email=input2('post.email');
		if(!$username)return $this->rterr('����д�û�����');
		if(!$password)return $this->rterr('����д���룡');
		if($password!=$repassword)return $this->rterr('ȷ�����벻��ȷ');
		if(!$email)return $this->rterr('����д���䣡');
		$user=DB::name('enewsmember')->where('username',$username)->find();
		if(!$user){
			$hasemail=DB::name('enewsmember')->where('email',$email)->value('email');
			if($hasemail){
				return $this->rterr('�����Ѵ��ڣ�');
			}
			load_uc();
			if(defined('UC_OPEN')){
				$uid=uc_user_register($username,$password,$email);
				if($uid <= 0){
					if($uid == -1){
						return $this->rterr('�û������Ϸ�');
					}elseif($uid == -2){
						return $this->rterr('����Ҫ����ע��Ĵ���');
					}elseif($uid == -3){
						return $this->rterr('�û����Ѿ�����');
					}elseif($uid == -4){
						return $this->rterr('Email��ʽ����');
					}elseif($uid == -5){
						return $this->rterr('Email������ע��');
					}elseif($uid == -6){
						return $this->rterr('��Email�Ѿ���ע��');
					}else{
						return $this->rterr('δ����');
					}
				}
			}
			$user=$this->add_user($uid,$username,$password,$email);
			set_login($user['userid'],$username,$password,$user['groupid']);
			$userinfo=['userid'=>$user['userid'],'username'=>$username,'avatar'=>user_avatar($user['userid'])];
			$havemsg=tp_updatehavemsg($username);
			return $this->rtok(array('msg'=>'ע��ɹ���','userinfo'=>$userinfo,'havemsg'=>$havemsg));
		}else{
			return $this->rterr('�û����Ѵ���');
		}
	}
	
	public function dologin(){
		if(ErrorTimes::check_times()){
			return $this->rterr('��IP������¼������');
		}
		$username=input2('post.username');
		$password=input2('post.password');
		if(!$username)return $this->rterr('����д�û�����');
		if(!$password)return $this->rterr('����д���룡');
		load_uc();
		if(defined('UC_OPEN')){
			list($userid, $username, $ucpassword, $email) = uc_user_login($username,$password);
			if($userid<=0){
				ErrorTimes::add_times();
				return $this->rterr('��¼ʧ�ܣ�');
			}
			$user=DB::name('enewsmember')->find($userid);
			if(!$user){
				$this->add_user($userid,$username,$password,$email);
			}
		}else{
			$user=DB::name('enewsmember')->where('username',$username)->find();
			if(!$this->checkpassword($user,$password)){
				ErrorTimes::add_times();
				return $this->rterr('�������');
			}
		}
		set_login($user['userid'],$username,$password,$user['groupid'],!!input('post.remember'));
		$userinfo=['userid'=>$user['userid'],'username'=>$username,'avatar'=>user_avatar($user['userid'])];
		$havemsg=tp_updatehavemsg($username);
		return $this->rtok(array('msg'=>'��¼�ɹ���','userinfo'=>$userinfo,'havemsg'=>$havemsg));
	}
	private function add_user($userid,$username,$password,$email){
		$rand=md5(microtime());
		$salt=substr($rand,0,6);
		$groupid=input('request.groupid');
		$user=[];
		if($userid)$user['userid']=$userid;
		$user['salt']=$salt;
		$user['username']=$username;
		$user['password']=md5(md5($password).$salt);
		$user['email']=$email;
		$user['checked']=1;
		$user['rnd']=substr($rand,6,10);
		$user['lasttime']=NOW_TIME;
		$user['registertime']=NOW_TIME;
		$user['groupid']=in_array($groupid,[1,3,5])?$groupid:$this->def_groupid();
		$user['userkey']=substr($rand,16,12);
		DB::name('enewsmember')->insert($user);
		if(!$userid){
			$userid=lastid();
			$user['userid']=$userid;
		}
		$useradd=DB::name('enewsmemberadd')->find($userid);
		if(!$useradd){
			$post=input2('post.');
			if(count($post['fanwei'])>0)$post['fanwei']='|'.tp_arr2str($post['fanwei'],'|').'|';
			$user=array_merge($user,$post);
			$user['regip']=request()->ip();
			$user['lastip']=request()->ip();
			$user['lasttime']=NOW_TIME;
			DB::name('enewsmemberadd')->insert($user);
		}
		return $user;
	}
	private function def_groupid(){
		global $ecms_config,$public_r;
		$groupid=$ecms_config['member']['defgroupid']?$ecms_config['member']['defgroupid']:$public_r['defaultgroupid'];
		return max(1,intval($groupid));
	}
}
?>
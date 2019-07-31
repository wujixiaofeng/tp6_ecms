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
		if(!$user)return $this->rterr('请先登录！');
		if(count($ids)==0)return $this->rterr('ID错误！');
		$sql=$this->delfavlistsql($ids);
		DB::execute("delete from {$dbtbpre}enewsfava ".$sql);
		return $this->rtok('取消成功！');
	}
	public function dodelmyzanlist($ids){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		if(count($ids)==0)return $this->rterr('ID错误！');
		$sql=$this->delfavlistsql($ids);
		DB::execute("delete from {$dbtbpre}xf_zan_log ".$sql);
		return $this->rtok('取消成功！');
	}
	private function delfavlistsql($ids){
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
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
		if(!$user)return $this->rterr('请先登录！');
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
		if(!$user)return $this->rterr('请先登录！');
		$zanlist=DB::query("select classid,id,addtime from {$dbtbpre}xf_zan_log where userid='$user[userid]'");
		return $this->domylist1($zanlist);
	}
	function dodelnewslist(){
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
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
		return $this->rtok('删除成功！');
	}
	function dodelnews(){
		global $class_r,$emod_r;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$userid=$user['userid'];
		$classid=input('get.classid/d');
		$id=input('get.id/d');
		return $this->dodelonenews($classid,$id,$userid,true);
	}
	public function dotougao($tbname=''){
		global $class_r;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$userid=$user['userid'];
		$page=input('get.page/d',1);
		$view=input('get.a2');
		if(!$tbname)$tbname=input('get.tbname');
		if(!$tbname||!Validate::isAlpha($tbname)||!$m=enews($tbname,$view!='view')){
			return $this->rterr('表名错误');
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
			$diqu[0]=str_replace(array('省','市','自治区','特别行政区','壮族','回族','维吾尔','族'),'',$diqu[0]);
			$post['sheng']=Diqu::name2id(1,$diqu[0]);
			$post['shi']=Diqu::name2id(2,$diqu[1]);
			$post['quxian']=Diqu::name2id(3,$diqu[2]);
		}
		unset($post['diqu']);
		if(count($post['fanwei'])>0)$post['fanwei']='|'.tp_arr2str($post['fanwei'],'|').'|';
		$return=$this->validate($post,$this->validateinfo);
		if($return===true){
			$return=UserModel::editinfo($post);
			return $return?$this->rtok('修改成功！'):$this->rterr('修改失败！');
		}else{
			return $this->rterr($return);
		}
	}
	public function dogetpassword(){
		global $dbtbpre;
		$vcode=new \Vcode();
		$post=input('post.');
		if(!$post['password'])$this->rterr('请填写密码！');
		$err=$vcode->checkVcode($post['email'],$post['vcode'],1);
		if($err)return $this->rterr($err);
		$vcode->expVcode($post['email'],$post['vcode'],1);
		$user=DB::table("{$dbtbpre}enewsmember")->where([['email','=',$post['email']]])->find();
		if(!$user)$this->rterr('无使用此邮箱的用户！');
		load_uc();
		if(defined('UC_OPEN')){
			$ucresult = uc_user_edit($user['username'],'',$post['password'],'',1);
			if($ucresult == -1){
				return $this->rterr('旧密码不正确');
			}elseif($ucresult == -4){
				return $this->rterr('Email格式有误');
			}elseif($ucresult == -5){
				return $this->rterr('Email不允许注册');
			}elseif($ucresult == -6){
				return $this->rterr('该Email已经被注册');
			}elseif($ucresult < 0){
				//return $this->rterr('未知错误');
			}
		}
		$save['password']=md5(md5($post['password']).$user['salt']);
		$return=UserModel::editinfo($save);
		if($return){
			//if($save['password'])set_login($user['userid'],$user['username'],$post['password'],$user['groupid']);
			return $this->rtok('修改成功！');
		}
		return $this->rterr('修改失败！');
	}
	public function doeditsafe($user,$post){
		$post['userid']=$user['userid'];
		$return=$this->validate($post,$this->validatesafe);
		if($return===true){
			load_uc();
			if(defined('UC_OPEN')){
				$ucinfo=uc_get_user($user['username']);
				if(!$ucinfo)return $this->rterr('用户不存在！');
				list($ucuserid,$ucusername,$ucemail)=$ucinfo;
				if($ucemail==$post['email']){
					$email='';//这里设置为空 是因为uc会判断是否存在此邮箱 用户现在的邮箱也会被判断存在
				}else{
					$email=$post['email'];
				}
				$ucresult = uc_user_edit($user['username'],$post['oldpassword'],$post['password'],$email);
				if($ucresult == -1){
					return $this->rterr('旧密码不正确');
				}elseif($ucresult == -4){
					return $this->rterr('Email格式有误');
				}elseif($ucresult == -5){
					return $this->rterr('Email不允许注册');
				}elseif($ucresult == -6){
					return $this->rterr('该Email已经被注册');
				}elseif($ucresult < 0){
					//return $this->rterr('未知错误');
				}
			}else{
				$check=$this->checkpassword($user,$post['oldpassword']);
				if(!$check){
					return $this->rterr('旧密码错误！');
				}
			}
			$save=['email'=>$post['email'],'userid'=>$user['userid']];
			if($post['password']){
				$save['password']=md5(md5($post['password']).$user['salt']);
			}
			$return=UserModel::editinfo($save);
			if($return){
				if($save['password']&&cookie('user_auth'))set_login($user['userid'],$user['username'],$post['password'],$user['groupid']);
				return $this->rtok('修改成功！');
			}
			return $this->rterr('修改失败！');
		}else{
			return $this->rterr($return);
		}
	}
	public function doavatar($user){
		$uc_path=config('config.uc_path');
		$userid=$user['userid'];
		$base64 = $_POST['base64'];
		if(!$base64)return $this->rterr('数据错误！');
		$base64 = str_replace('data:image/jpeg;base64,','',$base64);
		$img_content = @base64_decode($base64);
		if(!$img_content)$this->rterr('数据错误！');
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
		return $this->rtok(array('msg'=>'上传成功！','avatar'=>refresh_user_avatar($userid)));
	}
	public function doregister(){
		$username=input2('post.username');
		$password=input2('post.password');
		$repassword=input2('post.repassword');
		$email=input2('post.email');
		if(!$username)return $this->rterr('请填写用户名！');
		if(!$password)return $this->rterr('请填写密码！');
		if($password!=$repassword)return $this->rterr('确认密码不正确');
		if(!$email)return $this->rterr('请填写邮箱！');
		$user=DB::name('enewsmember')->where('username',$username)->find();
		if(!$user){
			$hasemail=DB::name('enewsmember')->where('email',$email)->value('email');
			if($hasemail){
				return $this->rterr('邮箱已存在！');
			}
			load_uc();
			if(defined('UC_OPEN')){
				$uid=uc_user_register($username,$password,$email);
				if($uid <= 0){
					if($uid == -1){
						return $this->rterr('用户名不合法');
					}elseif($uid == -2){
						return $this->rterr('包含要允许注册的词语');
					}elseif($uid == -3){
						return $this->rterr('用户名已经存在');
					}elseif($uid == -4){
						return $this->rterr('Email格式有误');
					}elseif($uid == -5){
						return $this->rterr('Email不允许注册');
					}elseif($uid == -6){
						return $this->rterr('该Email已经被注册');
					}else{
						return $this->rterr('未定义');
					}
				}
			}
			$user=$this->add_user($uid,$username,$password,$email);
			set_login($user['userid'],$username,$password,$user['groupid']);
			$userinfo=['userid'=>$user['userid'],'username'=>$username,'avatar'=>user_avatar($user['userid'])];
			$havemsg=tp_updatehavemsg($username);
			return $this->rtok(array('msg'=>'注册成功！','userinfo'=>$userinfo,'havemsg'=>$havemsg));
		}else{
			return $this->rterr('用户名已存在');
		}
	}
	
	public function dologin(){
		if(ErrorTimes::check_times()){
			return $this->rterr('此IP超过登录次数！');
		}
		$username=input2('post.username');
		$password=input2('post.password');
		if(!$username)return $this->rterr('请填写用户名！');
		if(!$password)return $this->rterr('请填写密码！');
		load_uc();
		if(defined('UC_OPEN')){
			list($userid, $username, $ucpassword, $email) = uc_user_login($username,$password);
			if($userid<=0){
				ErrorTimes::add_times();
				return $this->rterr('登录失败！');
			}
			$user=DB::name('enewsmember')->find($userid);
			if(!$user){
				$this->add_user($userid,$username,$password,$email);
			}
		}else{
			$user=DB::name('enewsmember')->where('username',$username)->find();
			if(!$this->checkpassword($user,$password)){
				ErrorTimes::add_times();
				return $this->rterr('密码错误！');
			}
		}
		set_login($user['userid'],$username,$password,$user['groupid'],!!input('post.remember'));
		$userinfo=['userid'=>$user['userid'],'username'=>$username,'avatar'=>user_avatar($user['userid'])];
		$havemsg=tp_updatehavemsg($username);
		return $this->rtok(array('msg'=>'登录成功！','userinfo'=>$userinfo,'havemsg'=>$havemsg));
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
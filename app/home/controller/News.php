<?php
namespace app\home\controller;
use app\common\model\Db;
use think\facade\View;
use think\facade\Config;

class News extends Base {
	use \app\common\traits\CommonTrait;
	use \app\common\traits\NewsTrait;
	public function add(){
		$do=$this->donews(0);
		if($do[0]){
			return $this->rtmsg($do,input('post.ecmsfrom'));
		}else{
			return $this->rtmsg($do);
		}
	}
	public function edit(){
		$do=$this->donews(1);
		if($do[0]){
			return $this->rtmsg($do,input('post.ecmsfrom'));
		}else{
			return $this->rtmsg($do);
		}
	}
	public function submit(){
		global $dbtbpre,$class_r,$empire;
		global $classid,$id,$filepass;
		if(request()->isPost()){
			if(input('post.id/d',0)){
				return $this->edit();
			}else{
				return $this->add();
			}
		}else{
			$cuxiao=input('get.cuxiao');
			if($cuxiao){
				$classid=2;
			}else{
				$classid=input('get.classid/d',0);
			}
			if($classid){
				$mid=$class_r[$classid]['modid'];
				if(empty($classid)||empty($mid)||InfoIsInTable($class_r[$classid]['tbname']))$this->error(elang("EmptyQinfoCid"));
				$id=input('get.id/d',0);
				if($id){
					$filepass=$id;
				}else{
					$filepass=NOW_TIME;
				}





				if($public_r['addnews_ok'])$this->error(elang("NotOpenCQInfo"));
				//验证本时间允许操作
				$e=tp_eCheckTimeCloseDo('info');
				if($e)$this->error($e);
				//验证IP
				$e=tp_eCheckAccessDoIp('postinfo');
				if($e)$this->error($e);
				if($id){
					$res['enews']=$enews="MEditInfo";
				}else{
					$res['enews']=$enews="MAddInfo";
				}
				$r=array();
				$user=$memberinfor=tp_loginuser();
				$res['muserid']=$muserid=(int)$user['userid'];
				$res['musername']=$musername=$user['username'];
				$res['mrnd']=$mrnd=$user['rnd'];
				$res['newstime']=$newstime=time();
				$r[newstime]=date("Y-m-d H:i:s");
				$res['todaytime']=$todaytime=$r[newstime];
				//$showkey="";
				$r['newstext']="";
				$res['rechangeclass']=$rechangeclass='';
				//验证会员信息
				$mloginauthr=$user;
				$mloginauthr['islogin']=1;
				$res['mloginauthr']=$mloginauthr;
				//增加
				if($enews=="MAddInfo"){
					$res['cr']=$cr=tp_DoQCheckAddLevel($classid,$muserid,$musername,$mrnd,0,1);
					if(is_string($cr))$this->error($cr);
					$res['mr']=$mr=$empire->fetch1("select qenter,qmname from ".$dbtbpre."enewsmod where mid='$cr[modid]'");
					if(empty($mr['qenter']))
					{
						return $this->error(elang("NotOpenCQInfo"));
					}
					//IP发布数限制
					$res['check_ip']=$check_ip=egetip();
					$res['check_checked']=$check_checked=$cr['wfid']?0:$cr['checkqadd'];
					$e=tp_eCheckIpAddInfoNum($check_ip,$cr['tbname'],$cr['modid'],$check_checked);
					if($e)$this->error($e);
					//初始变量
					$res['word']=$word="增加信息";
					$res['ecmsfirstpost']=$ecmsfirstpost=1;
					//$rechangeclass="&nbsp;[<a href='ChangeClass.php?mid=".$mid."'>重新选择</a>]";
					//图片
					$res['imgwidth']=$imgwidth=0;
					$res['imgheight']=$imgheight=0;
					//文件验证码
				}
				else
				{
					$res['word']=$word="修改信息";
					$res['ecmsfirstpost']=$ecmsfirstpost=0;
					$id=(int)$_GET['id'];
					if(empty($id))
					{
						return $this->error(elang("EmptyQinfoCid"));
					}
					$res['cr']=$cr=tp_DoQCheckAddLevel($classid,$muserid,$musername,$mrnd,1,0);
					if(is_string($cr))$this->error($cr);
					$res['mr']=$mr=$empire->fetch1("select qenter,qmname from ".$dbtbpre."enewsmod where mid='$cr[modid]'");
					if(empty($mr['qenter']))
					{
						return $this->error(elang("NotOpenCQInfo"));
					}
					$r=tp_CheckQdoinfo($classid,$id,$muserid,$cr['tbname'],$cr['adminqinfo'],1);
					if(is_string($r))$this->error($r);
					//检测时间
					if($public_r['qeditinfotime'])
					{
						if(time()-$r['truetime']>$public_r['qeditinfotime']*60)
						{
							return $this->error(elang("QEditInfoOutTime"));
						}
					}
					$res['newstime']=$newstime=$r['newstime'];
					$r['newstime']=date("Y-m-d H:i:s",$r['newstime']);
					//图片
					$res['imgwidth']=$imgwidth=170;
					$res['imgheight']=$imgheight=120;
					//文件验证码
				}
				$res['tbname']=$tbname=$cr['tbname'];
				cookie("qeditinfo","dgcms");
				//标题分类
				$cttidswhere='';
				$tts='';
				$caddr=$empire->fetch1("select ttids from ".$dbtbpre."enewsclassadd where classid='$classid'");
				if($caddr['ttids']!='-')
				{
					if($caddr['ttids']&&$caddr['ttids']!=',')
					{
						$cttidswhere=' and typeid in ('.substr($caddr['ttids'],1,-1).')';
					}
					$ttsql=$empire->query("select typeid,tname from ".$dbtbpre."enewsinfotype where mid='$cr[modid]'".$cttidswhere." order by myorder");
					while($ttr=$empire->fetch($ttsql))
					{
						$select='';
						if($ttr[typeid]==$r[ttid])
						{
							$select=' selected';
						}
						$tts.="<option value='$ttr[typeid]'".$select.">$ttr[tname]</option>";
					}
				}
				$res['tts']=$tts;
				//栏目
				$res['classurl']=$classurl=sys_ReturnBqClassname($cr,9);
				$res['postclass']=$postclass="<a href='".$classurl."' target='_blank'>".$class_r[$classid]['classname']."</a>".$rechangeclass;
				if($cr['bclassid'])
				{
					$bcr['classid']=$cr['bclassid'];
					$res['bclassurl']=$bclassurl=sys_ReturnBqClassname($bcr,9);

					$res['postclass']=$postclass="<a href='".$bclassurl."' target=_blank>".$class_r[$cr['bclassid']]['classname']."</a>&nbsp;>&nbsp;".$postclass;
				}
				//html编辑器
				if($emod_r[$mid]['editorf']&&$emod_r[$mid]['editorf']!=',')
				{
					include(ECMS_PATH.'e/data/ecmseditor/infoeditor/fckeditor.php');
				}
				if(empty($musername))
				{
					$res['musername']=$musername="游客";
				}
				$res['modfile']=$modfile=ECMS_PATH."e/data/html/q".$cr['modid'].".php";
			}
			
			
			
			
			
			
			
			$res=array_merge([
				'pagetitle'=>'发布信息',
				'classid'=>$classid,
				'id'=>$id,
				'mid'=>$mid,
				'filepass'=>$filepass,
				'empire'=>$empire,
				'dbtbpre'=>$dbtbpre,
				'class_r'=>$class_r,
				'r'=>$r
			],(array)$res);
			return $this->view('',$res);
		}
	}
	public function preview(){
		$info['title']=input('post.title');
		$info['newstext']=input('post.newstext','','safehtml');
		$res=['bodyClassName'=>'newsshow','pagetitle'=>'预览信息','info'=>$info];
		return $this->view('',$res);
	}
	
	
	
	
	
	
	
	public function fav(){
		global $dbtbpre;
		$id=input('get.id/d',0);
		$classid=input('get.classid/d',0);
		$type=input('get.type');
		return $this->rtjson($this->dofav($classid,$id,$type));
	}
	public function zan(){
		global $dbtbpre;
		$id=input('get.id/d',0);
		$classid=input('get.classid/d',0);
		$type=input('get.type');
		return $this->rtjson($this->dozan($classid,$id,$type));
	}
	public function show($classid=0,$id=0){
		global $class_r,$dbtbpre;
		$id=intval($id);
		$tbname=$class_r[$classid]['tbname'];
		//$newstime=enewsinfo($tbname,$id)->value('lastdotime');
		//$this->etag($newstime);
		$info=$this->info($classid,$id);
		if(!$info)return $this->err404();
		$checked=$info['checked'];
		if(!$checked){
			if(($info['ismember']!=1||tp_login()!=$info['userid'])&&!session('admin_user_auth')){
				return $this->err404();
			}
			View::assign('showcheckedtips',true);
		}
		if($tbname=='pictures'||$tbname=='youji'||$tbname=='zhibo'||$classid==403){
			$view=$tbname;
			if($tbname=='zhibo'){
				$zhibocount=(int)DB::getValue("select count(*) as total from {$dbtbpre}ecms_zhibo where userid=$info[userid] and ismember=0");
				View::assign('zhibocount',$zhibocount);
				$bodyClassName='zhiboshow';
			}
			if($tbname=='pictures'){
				$bodyClassName='picshow';
			}
			if($classid==403){
				$bodyClassName='videoshow';
				$view='shipin';
			}
			if($tbname=='pictures' or $classid==403){
				$nextwhere=[['id','>',$id]];
				if($classid==403)$nextwhere[]=['classid','=',$classid];
				$nextinfo=enews($tbname)->where($nextwhere)->field('title,titleurl,titlepic')->order('id','asc')->find();
				if(!$nextinfo)$nextinfo=['title'=>'','titlepic'=>'/skin/che2/images/noimg.png','titleurl'=>'javascript:void(0);'];
				View::assign('nextinfo',$nextinfo);
			}
		}else{
			$view='news';
			$bodyClassName='newsshow';
			$shangpian=DB::getRow("select title,titleurl,titlepic from {$dbtbpre}ecms_news where id<".$id." and titlepic!='' and classid=$classid order by id desc limit 1");
			$xiapian=DB::getRow("select title,titleurl,titlepic from {$dbtbpre}ecms_news where id>".$id." and titlepic!='' and classid=$classid order by id asc limit 1");
			$this->newsuserinfo($info);
		}
		
		$shenduhaowen=res_cache_clicklist('title,titleurl,focusImg',3,' - 100 days ','news'," focusImg!='' ");
		$tuijian=res_cache_clicklist('',5,50,'news');
		$info['plcount']=(int)DB::getValue("select count(*) as total from {$dbtbpre}enewspl_1 where classid='$classid' and id='$id' and checked=0");
		return $this->view($view,
		[
			'info'=>$info,
			'bodyClassName'=>$bodyClassName,
			'classid'=>$classid,
			'id'=>$id,
			'shenduhaowen'=>$shenduhaowen,
			'shangpian'=>$shangpian,
			'xiapian'=>$xiapian,
			'tuijian'=>$tuijian,
			'xiangguan'=>$this->xiangguan($id),
			'pagetitle'=>$info['title'],
			'pagekeywords'=>$info['keyboard'],
			'pagedesc'=>$info['desc']
		]);
	}
	private function newsuserinfo($info){
		global $dbtbpre;
		$usernewslist=enews('news')->field('title,titleurl')->where([['userid','=',$info['userid']],['ismember','=',$info['ismember']]])->limit(5)->order('newstime','desc')->select();
		View::assign(['usernewslist'=>$usernewslist]);
	}
	private function xiangguan($id){
		global $dbtbpre;
		$return=[];
		$keywords=enewsinfo('news',$id)->value('keyboard');
		if($keywords){
			$keywords=explode(",",$keywords);
			$sqladd="";
			foreach($keywords as $v){
				if($v)$sqladd.=($sqladd?" or ":"")."keyboard like '%{$v}%' ";
			}
			if($sqladd){
				$sqladd="({$sqladd}) and id!={$id}";
				$return=res_newlist('classid,id,titleurl,title,newstime,userid,ismember,username',5,'news',$sqladd);
			}
		}
		return $return;
	}
	public function search(){
		global $class_r;
		$tbname=input('get.tbname');
		if(!$tbname)$tbname='news';
		$pagesize=20;
		$page=input('get.page/d',1);
		$keywords=input2('get.keywords');
		$where=[['title|ftitle|keyboard','like','%'.$keywords.'%']];
		$count=enews($tbname)->getCount($where);
		$pagehtml=getpagehtml($count,$pagesize);
		$tuijian=res_cache_clicklist('',5,50,'news');
		$tuijian2=res_cache_clicklist('',5,100,'pictures');
		$tuijian3=res_cache_clicklist('',5,1000,'403');
		if($tbname=='youji')$writeras="'' as ";
		$list=enews($tbname)->getList($where,$page,$pagesize,"classid,id,titlepic,titleurl,title,smalltext,newstime,{$writeras}writer,username,'{$tbname}' as tbname");
		return $this->view('',[
			'pagetitle'=>$keywords?'搜索“'.$keywords.'”':'搜索',
			'list'=>$list,
			'tbname'=>$tbname,
			'page'=>$page,
			'keywords'=>$keywords,
			'pagehtml'=>$pagehtml,
			'tuijian'=>$tuijian,
			'tuijian2'=>$tuijian2,
			'tuijian3'=>$tuijian3,
			'classid'=>$classid
		]);
	}
	public function newslist($classid=0,$page=1){
		global $class_r,$dbtbpre;
		$tbname=$class_r[$classid]['tbname'];
		$where="classid='$classid'";
		$pagesize=24;
		$count=enews($tbname)->getCount($where);
		$pagehtml=getpagehtml($count,$pagesize,$page,'index_[PAGE].html');
		$field="classid,id,titlepic,titleurl,title,smalltext,newstime,ismember,userid,username";
		$list=enews($tbname)->getList($where,$page,$pagesize,$field);
		$onenews=DB::getRow("select title,smalltext,titleurl from {$dbtbpre}ecms_news where focusImg!='' and id > (SELECT MAX(id) FROM `{$dbtbpre}ecms_news`)-".rand(500,1000)." order by id asc limit 1");
		$tuijian=res_cache_clicklist('',5,50,'news');
		$tuijian2=res_cache_clicklist('',5,100,'pictures');
		$tuijian3=res_cache_clicklist('',5,1000,'403');
		if($tbname=='pictures' or $tbname=='zhibo' or $classid==403){
			$view='list2';
		}elseif($tbname=='youji'){
			$view='youjilist';
			foreach($list as $k=>$v){
				$list[$k]['imgcount']=DB::table("{$GLOBALS[dbtbpre]}youji_img")->where('yjid',$v['id'])->count();
				$list[$k]['daycount']=DB::table("{$GLOBALS[dbtbpre]}youji_img")->where('yjid',$v['id'])->count('distinct date');
			}
		}else{
			$view='list';
		}
		return $this->view($view,
		[
			'pagetitle'=>$class_r[$classid]['classname'],
			'list'=>$list,
			'islist'=>true,
			'pagehtml'=>$pagehtml,
			'onenews'=>$onenews,
			'tuijian'=>$tuijian,
			'tuijian2'=>$tuijian2,
			'tuijian3'=>$tuijian3,
			'classid'=>$classid
		]);
	}
}
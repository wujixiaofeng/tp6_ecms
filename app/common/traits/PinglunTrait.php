<?php
namespace app\common\traits;
use app\common\model\Db;
trait PinglunTrait{
	public function dolist($order='asc'){
		global $dbtbpre,$class_r;
		//$user=tp_loginuser();
		//if(!$user)return $this->rterr('请先登录！');
		$pagesize=10;
		$page=input('get.page/d',1);
		$pagecount=ceil($rscount/$pagesize);
		$classid=input('get.classid/d',0);
		$id=input('get.id/d',0);
		$ztid=input('get.ztid/d',0);
		$repid=input('get.replid/d',0);
		if($repid==0)$repid=input('get.repid/d',0);
		$guandian=input('get.guandian/d',0);
		if($ztid>0){
			$n_r=DB::table("{$dbtbpre}enewszt")->where('ztid',$ztid)->field('ztid,restb')->find();
			if(!$n_r['ztid'])exit();
			$pubid='-'.$ztid;
			$doaction="dozt";
			$classid=$id=$ztid;
		}else{
			if(empty($id)||empty($classid))return $this->rterr('ID错误');
			if(empty($tbname=$class_r[$classid][tbname]))return $this->rterr('表名错误');
			$n_r=enewsinfo($tbname,$id)->field('id,classid,plnum,restb')->find();
			if(!$n_r['id']||$classid!=$n_r['classid'])return $this->rterr('信息错误');
			$pubid=ReturnInfoPubid($classid,$id);
		}

		if($repid){
			$return=$this->repl($repid,$page,$pagesize,$n_r,$ztid,$classid,$id,$guandian);
			if($_GET['debug']){
				halt($return);
			}else{
				return $this->rtok($return);
			}
		}
		$where=[['pubid','=',$pubid],['repid','=',0]];
		if($guandian)$where[]=['guandian','=',$guandian];
		$rscount=DB::table("{$dbtbpre}enewspl_".$n_r['restb'])->where($where)->count();
		$list=DB::table("{$dbtbpre}enewspl_".$n_r['restb'])->where($where)->page($page,$pagesize)->order('plid',$order=='desc'?'desc':'asc')->select()->toArray();
		$return=array();
		foreach($list as $k=>$v){
			$v=$this->plr($v);
			$v['repllist']=$this->repl($v['plid'],1,$pagesize,$n_r,$ztid,$classid,$id,$guandian);
			$list[$k]=$v;
		}
		$return['list']=$list;
		$return['classid']=$classid;
		$return['id']=$id;
		$return['ztid']=$ztid;
		$return['guandian']=$guandian;
		$return['page']=$page;
		$return['rscount']=$rscount;
		$return['pagesize']=$pagesize;
		$return['pagecount']=ceil($rscount/$pagesize);
		if($_GET['debug']){
			halt($return);
		}else{
			return $this->rtok($return);
		}
	}
	public function repl($repid,$page,$pagesize,$n_r,$ztid,$classid,$id,$guandian){
		global $empire,$dbtbpre;
		$where=[['repid','=',$repid]];
		if($guandian)$where[]=['guandian','=',$guandian];
		$rscount=DB::table("{$dbtbpre}enewspl_".$n_r['restb'])->where($where)->count();
		$list=DB::table("{$dbtbpre}enewspl_".$n_r['restb'])->where($where)->page($page,$pagesize)->order('plid','asc')->select()->toArray();
		foreach($list as $k=>$v){
			$list[$k]=$this->plr($v);
		}
		$repllist['repid']=$repid;
		$repllist['classid']=$classid;
		$repllist['id']=$id;
		$repllist['ztid']=$ztid;
		$repllist['guandian']=$guandian;
		$repllist['list']=$list;
		$repllist['page']=$page;
		$repllist['rscount']=$rscount;
		$repllist['pagesize']=$pagesize;
		$repllist['pagecount']=ceil($rscount/$pagesize);
		return $repllist;
	}
	public function dosubmit($niming=0){
		global $dbtbpre,$public_r,$class_r,$level_r;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$post=input2('post.');
		$id=input('post.id/d',0);
		$classid=input('post.classid/d',0);
		$repid=input('post.repid/d',0);
		$guandian=input('post.guandian/d',0);
		$doaction=$post['doaction'];
		$saytext=$post['saytext'];
		if($doaction=='dozt'){
			if(!trim($saytext)||!$classid){
				return $this->rtelang("EmptyPl");
			}
			//是否关闭评论
			$info=DB::getRow("select ztid,closepl,checkpl,restb from {$dbtbpre}enewszt where ztid='$classid'");
			if(!$info['ztid']){
				return $this->rtelang("ErrorUrl");
			}
			if($info['closepl']){
				return $this->rtelang("CloseClassPl");
			}
			//审核
			if($info['checkpl']){$checked=1;}else{$checked=0;}
			$restb=$info['restb'];
			$post['pubid']=$pubid='-'.$classid;
			$id=0;
			/*$pagefunr=eReturnRewritePlUrl($classid,$id,'dozt',0,0,1);
			$returl=$pagefunr['pageurl'];*/
		}else{
			$tbname=$class_r[$classid]['tbname'];
			if(!$tbname)return $this->rterr('分类信息错误！');
			//if($doaction=='dozt')return $this->rterr('暂不支持专题评论！');
			if($class_r[$classid]['openpl'])return $this->rterr('此分类禁止评论！');
			if($public_r['plgroupid']){
				if($level_r[$user['groupid']]['level']<$level_r[$public_r['plgroupid']]['level']){
					return $this->rterr('您所在的会员组禁止评论！');
				}
			}
			$info=enewsinfo($tbname,$id)->field('stb,restb')->find();
			if(!$info)return $this->rterr('没有此信息，无法评论！');
			$closepl=enewsinfo($tbname,$id,true)->value('closepl');
			if($closepl)return $this->rterr('此信息禁止评论！');
			$post['pubid']=$pubid=ReturnInfoPubid($classid,$id);
		}
		
		
		
		
		
		$plset=DB::table("{$dbtbpre}enewspl_set")->field('pltime,plsize,plincludesize,plclosewords,plmustf,plf,plmaxfloor,plquotetemp')->find();
		if(strlen($saytext)>$plset['plsize'])return $this->rterr('评论内容长度超过限制！');
		if($repid){
			$repl=DB::table("{$dbtbpre}enewspl_".$info['restb'])->where('plid',$repid)->value('plid');
			if(!$repl)return $this->rterr('无此评论，无法回复');
		}
		$pltime=session('lastpltime');
		if(NOW_TIME-$pltime<$plset['pltime']){
			return $this->rterr('系统限制的发表评论间隔是 '.$plset['pltime'].' 秒,请稍后再发');
		}
		if($class_r[$classid]['checkpl']){
			$checked=1;
		}else{
			$checked=0;
		}
		if($level_r[$user['groupid']]['plchecked']){
			$checked=0;
		}
		$post['userid']=$user['userid'];
		$post['username']=$user['username'];
		$post['sayip']=request()->ip();
		$post['saytime']=NOW_TIME;
		$post['checked']=$checked;
		$post['guandian']=$guandian;
		$post['niming']=$niming;
		$post['id']=$id;
		$post['classid']=$classid;
		DB::table("{$dbtbpre}enewspl_".$info['restb'])->insert($post);
		$post['plid']=lastid();
		event('AddPinglun',$post);
		session('lastpltime',NOW_TIME);
		return $this->rtok(['msg'=>'评论成功！','classid'=>$classid,'id'=>$id,'plr'=>$this->plr($post)]);
	}
	public function plr($r){
		if($r['isdel']){
			$r['saytext']='[此评论已经被删除！]';
			$r['username']='';
			$r['userid']='';
		}
		if($r['userid'] and !$r['niming']){
			$plusername=$r['username'];
			$pluserid=$r['userid'];
		}else{
			$plusername="";
			$pluserid=0;
		}
		$saytime=tp_formattime($r['saytime']);
		$saytext=/*android_plbiaoqing*/(/*RepPltextFace*/(stripslashes($r['saytext'])));
		$saytext=str_replace('<br />',"\r\n",$saytext);
		$saytext=strip_tags($saytext);
		$saytext=str_replace("\r\n\r\n","\r\n",$saytext);
		$saytext=str_replace("\r\n\r\n","\r\n",$saytext);
		$saytext=str_replace("\r\n\r","\r\n",$saytext);
		$saytext=str_replace("\r\n\r","\r\n",$saytext);
		$saytext=str_replace("\r\n\n","\r\n",$saytext);
		$saytext=str_replace("\r\n\n","\r\n",$saytext);
		$atplid=$r[plid];
		$r['userid']=$pluserid;
		$r['username']=$plusername?$plusername:"匿名";
		$r['saytimestamp']=intval($r['saytime']);
		$r['saytime']=$saytime;
		$r['saytext']=$saytext;
		if($pluserid>0){
			$r['avatar']=user_avatar($pluserid);
		}else{
			$r['avatar']="";
		}
		return $r;
	}
	public function domylist(){
		global $dbtbpre,$class_r;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$pagesize=10;
		$page=input('get.page/d',1);
		$pagecount=ceil($rscount/$pagesize);
		$where=[['userid','=',$user['userid']],['isdel','<>',1],['pubid','>',0]];
		$rscount=DB::table("{$dbtbpre}enewspl_1")->where($where)->count();
		$list=DB::table("{$dbtbpre}enewspl_1")->where($where)->page($page,$pagesize)->order('plid','desc')->select()->toArray();
		foreach($list as $k=>$v){
			$info=$this->cmtinfo($v);
			$v['infotitle']=$info['infotitle'];
			$v['infourl']=$info['infourl'];
			$v['infoimg']=$info['infoimg'];
			$v['iszt']=!!$info['iszt'];
			$v['tbname']=$class_r[$v['classid']]['tbname'];
			$v['saytext']=str_replace('<br>',"\r\n",/*stripSlashes*/ stripslashes($v['saytext']));
			$v['saytime2']=tp_format_date($v['saytime']);
			$v['saytime']=tp_short_formattime($v['saytime']);
			//if($v[isdel])$v['saytext']="[此评论已经被管理员删除！]";
			$list[$k]=$v;
		}
		return $this->rtok(array('pagecount'=>$pagecount,'page'=>$page,'pagesize'=>$pagesize,'rscount'=>$rscount,'list'=>$list));
	}
	public function cmtinfo($v){
		global $empire,$dbtbpre,$class_r,$duser;
		static $infoarr=array();
		if(!$infoarr[$v['pubid']]){
			if($v['pubid']<0){
				$info=DB::table()->where('ztid','=',$v['pubid'])->field('ztname,ztpath,ztimg')->find();
				$infotitle='[专题]'.$info['ztname'];
				$infourl='http://www.1stgd.com/'.$info['ztpath'].'/';
				$infoimg=$info['ztimg'];
				$iszt=true;
			}else{
				$tbname=$class_r[$v['classid']]['tbname'];
				$info=enews($tbname)->getInfo($v['id'],0,'title,titleurl,titlepic');
				if(!$info and $this->duser)$info=enews($tbname,false)->getInfo($v['id'],0,'title,titleurl,titlepic');
				$infotitle=$info['title'];
				$infourl=$info['titleurl'];
				$infoimg=$info['titlepic'];
				$iszt=false;
			}
			$rtinfo=array('infotitle'=>$infotitle,'infourl'=>$infourl,'infoimg'=>$infoimg,'iszt'=>$iszt);
			$infoarr[$v['pubid']]=$rtinfo;
		}else{
			$rtinfo=$infoarr[$v['pubid']];
		}
		return $rtinfo;
	}
	
	
	
	
	public function sqldel($sql){
		global $empire,$class_r,$dbtbpre;
		DB::execute("update {$dbtbpre}enewspl_1 set isdel=1 where $sql and repid=0");
		$list=DB::query("select pl1.classid,pl1.id,pl1.plid,pl1.pubid from {$dbtbpre}enewspl_1 as pl1 where $sql and repid>0");
		foreach($list as $v){
			if($v['pubid']>0)$this->plinfojia1($v['classid'],$v['id']);
		}
		DB::execute("delete from {$dbtbpre}enewspl_1 where $sql and repid>0");
	}
	function del1ji(){
		global $empire,$class_r,$dbtbpre;
		$list=DB::query("select pl1.classid,pl1.id,pl1.plid,pl1.pubid from {$dbtbpre}enewspl_1 as pl1 where pl1.repid=0 and pl1.isdel!=0");
		$ids=array();
		foreach($list as $v){
			$v['repcount']=DB::table("{$dbtbpre}enewspl_1")->where('repid',$v['plid'])->count();
			if($v['repcount']==0){
				if($v['pubid']>0)$this->plinfojia1($v['classid'],$v['id']);
				$ids[]=$v['plid'];
			}
		}
		if(count($ids)>0)DB::execute("delete from {$dbtbpre}enewspl_1 where plid in(".implode(',',$ids).")");
	}
	function plinfojia1($classid,$id){
		global $delplinfo;
		if(!$delplinfo[$classid][$id]){
			$delplinfo[$classid][$id]=1;
		}else{
			$delplinfo[$classid][$id]++;
		}
	}
	function jianplcount(){
		global $empire,$class_r,$dbtbpre,$delplinfo;
		foreach($delplinfo as $classid=>$v){
			foreach($v as $id=>$vv){
				if($tbname=$class_r[$classid]['tbname']){
					$a=enewsinfo($tbname,$id)->dec('plnum',$vv)->update();
				}
			}
		}
	}
	public function dodelsel(){
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$plid=input('request.plid');
		if(is_string($plid))$plid=explode(',',$plid);
		foreach($plid as $k=>$v){
			$plid[$k]=intval($v);
		}
		$this->sqldel(" userid='$user[userid]' and plid in (".implode(',',$plid).") ");
		$this->del1ji();
		$this->jianplcount();
		return $this->rtok("删除成功！");
	}
}
?>
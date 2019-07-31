<?php
namespace app\common\traits;
use think\facade\Db;
use app\common\model\Enewsmember as User;
trait MsgTrait{
	public function dohandle(){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$id=input('request.mid');
		if(is_string($id))$id=explode(',',$id);
		$submit=input2('request.submit');
		if($submit=="del")$submit="删除";
		if($submit=="read")$submit="标记已读";
		if($_REQUEST['tbtype']=="sys"){
			$msgtbname="enewsqmsg";
		}elseif($_REQUEST['tbtype']=="at"){
			$msgtbname="enewsatmsg";
		}
		if(!$msgtbname){return $this->rterr('数据库表错误！');}
		if(is_array($id)){
			for($i=0;$i<count($id);$i++){
				$id[$i]=intval($id[$i]);
			}
			$idsql=implode(",",$id);
		}elseif(is_numeric($id)){
			$idsql=$id;
		}
		if($submit=="删除"){
			if($idsql)$query=DB::execute("delete from {$dbtbpre}{$msgtbname} where to_username='$user[username]' and mid in($idsql)");
		}else{
			if($idsql)$query=DB::execute("update {$dbtbpre}{$msgtbname} set haveread=1 where to_username='$user[username]' and mid in($idsql)");
		}
		$havemsg=tp_updatehavemsg($user[username]);
		$jsonarr['msg']=$submit.'成功！';
		$jsonarr['havemsg']=$havemsg;
		return $this->rtok($jsonarr);
	}
	public function douserhandle(){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$id=input('request.mid');
		if(is_string($id))$id=explode(',',$id);
		$submit=input2('request.submit');
		if($submit=="del")$submit="删除";
		if($submit=="read")$submit="标记已读";
		$msgtbname="hd_msg";
		if(is_array($id)){
			for($i=0;$i<count($id);$i++){
				$id[$i]=intval($id[$i]);
			}
			$idsql=implode(",",$id);
		}elseif(is_numeric($id)){
			$idsql=$id;
		}
		if($idsql){
			$usersql='';
			$list=DB::table("{$dbtbpre}hd_msg")->field('fromuid,touid')->where('mid','in',$idsql)->select()->toArray();
			foreach($list as $k=>$v){
				if($v['fromuid']==$user['userid']){
					$touid=$v['touid'];
					$fromuid=$user['userid'];
				}elseif($v['touid']==$user['userid']){
					$touid=$user['userid'];
					$fromuid=$v['fromuid'];
				}else{
					$fromuid=0;
					$touid=0;
				}
				if($fromuid and $touid)$usersql.=($usersql?' or ':' ( ')." (( fromuid=$fromuid and touid=$touid ) or ( touid=$fromuid and fromuid=$touid )) ";
			}
			if($usersql)$usersql.=' ) ';
		}
		if($submit=="删除"){
			if($usersql){
				$query=DB::execute("delete from {$dbtbpre}{$msgtbname} where $usersql and deluser!=0 and deluser!='$user[userid]'");
				$query=DB::execute("update {$dbtbpre}{$msgtbname} set deluser='$user[userid]',haveread=1 where $usersql");
			}
		}else{
			if($usersql)$query=DB::execute("update {$dbtbpre}{$msgtbname} set haveread=1 where $usersql");
		}
		$havemsg=tp_updatehavemsg($user['username']);
		return $this->rtok(array("msg"=>$submit.'成功','havemsg'=>$havemsg));
	}
	
	public function dosend(){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$post=input2('post.');
		if($post['touid']){
			$touser=User::getByUserid($post['touid']);
		}else{
			$touser=User::getByUsername($post['tousername']);
		}
		if(!$touser){
			return $this->rterr('没有此用户！');
		}elseif($touser[userid]==$user[userid]){
			return $this->rterr('不能发送给自己！');
		}elseif($post['text']){
			$text=str_replace(array("<","\r\n"),array("&lt;","<br>"),$post['text']);
			$info=[
				"touid"=>(int)$touser[userid],
				"touname"=>$touser[username],
				"fromuid"=>(int)$user[userid],
				"fromuname"=>$user[username],
				"time"=>NOW_TIME,
				"text"=>addslashes($text),
				"islast"=>1
			];
			DB::table("{$dbtbpre}hd_msg")->insert($info);
			$mid=lastid();
			DB::table("{$dbtbpre}hd_msg")->where("
			((fromuid='$user[userid]' and touid='$touser[userid]')
			or (fromuid='$touser[userid]' and touid='$user[userid]'))
			and mid!='$mid'
			and islast=1")->update(["islast"=>0]);
			$isfrom=($user['userid']==$info['fromuid']);
			$info['isfrom']=$isfrom;
			$info['avatar']=user_avatar($info['fromuid']);
			$info['msgtimestamp']=$info['time'];
			$info['msgtimestr']=think_format_date($info['time'],2);
			$info['mid']=$mid;
			$info['haveread']=false;
			unset($info['time']);
			$info['text']=str_replace('<br>',"\r\n",$info['text']);
			$info['text']=strip_tags($info['text']);
			if(!$isfrom)$havemsg=tp_updatehavemsg($user['username']);
			if($isfrom)$havemsg=tp_updatehavemsg($touser['username']);
			return $this->rtok(array('msg'=>'发送成功！','touserid'=>$touser['userid'],'lastr'=>$info,'havemsg'=>$havemsg));
		}
	}
	
	public function dousershow($order='desc'){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$userid=input('get.userid/d',0);
		$pagesize=10;
		if($_GET['page']=="last"){
			$page=9999;
		}else{
			$page=input('get.page/d',1);
		}
		$where1=function($query) use($user,$userid){
			$query->where(function($query) use($user,$userid){
				$query->where([['touid','=',$user['userid']],['fromuid','=',$userid]]);
			})->whereOr(function($query) use($user,$userid){
				$query->where([['fromuid','=',$user['userid']],['touid','=',$userid]]);
			});
		};
		$where2=[['deluser','<>',$user['userid']]];
		$rscount=DB::table("{$dbtbpre}hd_msg")->where($where1)->where($where2)->count();
		$pagecount=ceil($rscount/$pagesize);
		if($_GET['page']=="last" && $page>$pagecount)$page=$pagecount;
		$list=DB::table("{$dbtbpre}hd_msg")->where($where1)->where($where2)->page($page,$pagesize)->order('mid',$order=='desc'?'desc':'asc')->select()->toArray();
		$pagecount=ceil($rscount/$pagesize);
		$tousername='';
		if($rscount>0){
			foreach($list as $k=>$v){
				$isfrom=($user[userid]==$v[fromuid]);
				$v['isfrom']=$isfrom;
				if(!$tousername){
					if($isfrom){
						$tousername=$v['touname'];
					}
				}
				$v['avatar']=user_avatar($v['fromuid']);
				$v['haveread']=!!$v['haveread'];
				$v['msgtimestamp']=$v['time'];
				$v['msgtimestr']=think_format_date($v['time'],2);
				unset($v['time']);
				$v['text']=str_replace('<br>',"\r\n",$v['text']);
				$v['text']=strip_tags($v['text']);
				$list[$k]=$v;
			}
		}
		DB::table("{$dbtbpre}hd_msg")->where([['touid','=',$user['userid'],['fromuid','=',$userid],['haveread','=',0]]])->save(["haveread"=>1]);
		$havemsg=tp_updatehavemsg($user[username]);
		return $this->rtok(array('pagecount'=>$pagecount,'tousername'=>$tousername,'page'=>$page,'pagesize'=>$pagesize,'rscount'=>$rscount,'userlist'=>$list,'havemsg'=>$havemsg));
	}
	function douser(){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$userid=$user['userid'];
		$page=input('get.page/d',1);
		$pagesize=10;
		$where=[['touid|fromuid','=',$userid],['deluser','<>',$userid],['islast','=',1]];
		$rscount=DB::table("{$dbtbpre}hd_msg")->where($where)->count();
		$list=DB::table("{$dbtbpre}hd_msg")->where($where)->page($page,$pagesize)->order('mid','desc')->select()->toArray();
		$pagecount=ceil($rscount/$pagesize);
		if($rscount>0){
			foreach($list as $k=>$v){
				$isfrom=($user['userid']==$v['fromuid']);
				$v['title']=$isfrom?$v['touname']:$v['fromuname'];
				$v['uid']=$isfrom?$v['touid']:$v['fromuid'];
				$v['isfrom']=$isfrom;
				$v['avatar']=user_avatar($v['uid']);
				$v['haveread']=!!$v['haveread'];
				$v['msgtimestamp']=$v['time'];
				$v['msgtimestr']=think_format_date($v['time'],2);
				unset($v['time']);
				$list[$k]=$v;
			}
		}
		$havemsg=tp_updatehavemsg($user[username]);
		return $this->rtok(array('pagecount'=>$pagecount,'page'=>$page,'pagesize'=>$pagesize,'rscount'=>$rscount,'userlist'=>$list,'havemsg'=>$havemsg));
	}
	function doweidu(){
		global $dbtbpre;
		$user=tp_loginuser();
		if($user){
			$userweidu=(int)DB::table("{$dbtbpre}hd_msg")->where("touid='$user[userid]' and haveread=0 and islast=1 and deluser!='$user[userid]'")->count();
			$atweidu=(int)DB::table("{$dbtbpre}enewsatmsg")->where("to_username='$user[username]' and haveread=0")->count();
			$sysweidu=(int)DB::table("{$dbtbpre}enewsqmsg")->where("to_username='$user[username]' and haveread=0 and issys=1")->count();
			$weidu=$userweidu+$atweidu+$sysweidu;
		}
		return $this->rtok(['userweidu'=>(int)$userweidu,'atweidu'=>(int)$atweidu,'sysweidu'=>(int)$sysweidu,'weidu'=>(int)$weidu]);
	}
	public function dosys($isandroid=true){
		global $dbtbpre;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$userid=$user['userid'];
		$pagesize=10;
		$page=input('get.page/d',1);
		$where=[['to_username','=',$user['username']],['isadmin','=',1]];
		$rscount=DB::table("{$dbtbpre}enewsqmsg")->where($where)->count();
		$list=DB::table("{$dbtbpre}enewsqmsg")->where($where)->page($page,$pagesize)->order('mid','desc')->select()->toArray();
		$pagecount=ceil($rscount/$pagesize);
		if($rscount>0){
			foreach($list as $k=>$v){
				$v['title']=tp_replaceand($v['title']);
				$v['haveread']=!!$v['haveread'];
				if($isandroid){
					$v['msgtext']=str_replace('<br>',"\r\n",$v['msgtext']);
					$v['msgtext']=strip_tags($v['msgtext']);
				}
				$list[$k]=$v;
			}
		}
		$havemsg=tp_updatehavemsg($user[username]);
		return $this->rtok(array('pagecount'=>$pagecount,'pagesize'=>$pagesize,'page'=>$page,'rscount'=>$rscount,'syslist'=>$list,'havemsg'=>$havemsg));
	}
	public function doat(){
		global $dbtbpre,$class_r;
		$user=tp_loginuser();
		if(!$user)return $this->rterr('请先登录！');
		$userid=$user['userid'];
		$page=input('get.page/d',1);
		$pagesize=10;
		$where=[['to_username','=',$user['username']]];
		$rscount=DB::table("{$dbtbpre}enewsatmsg")->where($where)->count();
		$list=DB::table("{$dbtbpre}enewsatmsg")->where($where)->page($page,$pagesize)->order('mid','desc')->select()->toArray();
		$pagecount=ceil($rscount/$pagesize);
		if($rscount>0){
			foreach($list as $k=>$v){
				unset($v['to_userid'],$v['to_username']);
				$tbname=$class_r[$v['classid']]['tbname'];
				$info=enews($tbname)->getInfo($v['id'],0,'title,titleurl');
				$v['yuanwen']=DB::table("{$dbtbpre}enewspl_1")->where('plid',$r['atplid'])->value('saytext');
				$v['avatar']=user_avatar($v['from_userid']);
				$v['timestamp']=strtotime($v['msgtime']);
				$v['msgtext']=/*android_plbiaoqing*/(/*RepPltextFace*/(stripslashes($v['msgtext'])));
				$v['msgtext']=strip_tags($v['msgtext']);
				$v['newstitle']=$info['title'];
				$v['titleurl']=$info['titleurl'];
				$v['tbname']=$class_r[$v['classid']]['tbname'];
				$v['haveread']=!!$v['haveread'];
				$list[$k]=$v;
			}
		}
		//DB::execute("update {$dbtbpre}enewsatmsg set haveread=1 where haveread=0 and to_userid='$user[userid]'");
		$havemsg=tp_updatehavemsg($user[username]);
		return $this->rtok(array('pagecount'=>$pagecount,'page'=>$page,'pagesize'=>$pagesize,'rscount'=>$rscount,'atlist'=>$list,'havemsg'=>$havemsg));
	}
}
?>
<?php
namespace app\common\listener;
use think\facade\Db;
use app\common\model\Enewsmember as User;

class AddPinglun {
	public function handle($post) {
		global $empire,$dbtbpre,$class_r;
		if(preg_match_all("/@(.*?)[\:;：；]/",$post['saytext'],$rt)){
			$user=tp_loginuser();
			$sendedarr=array();
			for($i=0;$i<count($rt[1]);$i++){
				if(!in_array($rt[1][$i],$sendedarr) and $userid=User::name2id($rt[1][$i])){
					$sendedarr[]=$rt[1][$i];
					$post['id']=(int)$post[id];
					$post['classid']=(int)$post['classid'];
					$post['repid']=(int)$post['repid'];
					$post['atplid']=(int)$post['atplid'];
					$tbname=$class_r[$post['classid']]['tbname'];
					if($tbname and $post['id']){
						if($user['userid']!=$userid){
							$msgtext=$post['saytext'];
							$msgtime=date("Y-m-d H:i:s");
							DB::execute("insert into {$dbtbpre}enewsatmsg(msgtext,haveread,msgtime,to_userid,to_username,from_userid,from_username,classid,id,plid,replid,atplid)values('$msgtext',0,'$msgtime','$userid','".$rt[1][$i]."','$user[userid]','$user[username]',$post[classid],$post[id],$post[plid],$post[repid],$post[atplid]);");
							tp_updatehavemsg($rt[1][$i]);
						}
					}
				}
			}
		}
	}
}
?>
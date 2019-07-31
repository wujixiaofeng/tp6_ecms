<?php
namespace app\home\controller;
use app\common\model\Db;

class Zhibo extends Base{
	use \app\common\traits\CommonTrait;
	use \app\common\traits\ZhiboTrait;
	public function delinfo(){
		return $this->rtjson($this->dodelinfo());
	}
	public function submit(){
		return $this->rtjson($this->dosubmit());
	}
	public function getpl(){
		global $dbtbpre;
		$classid=input('get.classid/d');
		$id=input('get.id');
		$page=input('get.page/d',1);
		$pagesize=10;
		$limit=($page-1)*$pagesize;
		$rscount=DB::getValue("select count(*) as total from {$dbtbpre}enewspl_1 where classid=$classid and id=$id");
		$query="select * from {$dbtbpre}enewspl_1 where classid=$classid and id=$id order by plid desc limit $limit,$pagesize";
		$list=DB::query($query);
		foreach($list as $k=>$v){
			$list[$k]['yuanwen']=DB::getRow("select saytext,username from {$dbtbpre}enewspl_1 where plid='$v[repid]'");
		}
		$res=['list'=>$list];
		$res['pagehtml']=getmpagehtml($rscount,$pagesize,0,"javascript:zbpl($classid,$id,[PAGE]);");
		return $this->view('',$res);
	}
}
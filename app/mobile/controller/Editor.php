<?php
namespace app\mobile\controller;
use think\facade\Db;

class Editor extends Base {
	public $pvbeishu=12.456;
	public function show($userid=0,$page=1){
		global $dbtbpre;
		$info=DB::table("{$dbtbpre}enewsuser")->find($userid);
		$info['count']=DB::table("{$dbtbpre}ecms_news")->where('userid',$userid)->count();
		$info['pv']=DB::table("{$dbtbpre}ecms_news")->where('userid',$userid)->sum('onclick');
		$info['pv']=(int)($info['pv']*$this->pvbeishu);
		if($info['smalltext']=='')$info['smalltext']='暂无简介';
		$pagesize=10;
		$where=[['userid','=',$userid],['ismember','=',0]];
		$rscount=DB::table("{$dbtbpre}ecms_news")->where($where)->count();
		$pagehtml=getmpagehtml($rscount,$pagesize,$page,'/editor-'.$userid.'_[PAGE].html');
		$list=DB::table("{$dbtbpre}ecms_news")->where($where)->page($page,$pagesize)->field('classid,id,newstime,title,ftitle,titleurl,username,smalltext')->select()->toArray();
		return $this->view('',['pagetitle'=>$info['username']."_作者页面",'info'=>$info,'list'=>$list,'pagehtml'=>$pagehtml]);
	}
	public function list($page=1){
		global $dbtbpre;
		$pagesize=10;
		$where=[['username','not in',['admin','wumaolin','朱晓峰','吴茂林','邓双华','直播员']]];
		$rscount=DB::table("{$dbtbpre}enewsuser")->where($where)->count();
		$pagehtml=getmpagehtml($rscount,$pagesize,$page,'/editor_[PAGE].html');
		$list=DB::table("{$dbtbpre}enewsuser")->where($where)->page($page,$pagesize)->field('userid,username')->select()->toArray();
		foreach($list as $k=>$v){
			$where2=[['userid','=',$v['userid']],['ismember','=',0]];
			$list[$k]['count']=DB::table("{$dbtbpre}ecms_news")->where($where2)->count();
			$list[$k]['pv']=DB::table("{$dbtbpre}ecms_news")->where($where2)->sum('onclick');
			$list[$k]['pv']=(int)($list[$k]['pv']*$this->pvbeishu);
			if(!$v['smalltext'])$list[$k]['smalltext']='暂无简介';
		}
		return $this->view('',['pagetitle'=>"作者列表页",'list'=>$list,'pagehtml'=>$pagehtml]);
	}
}
<?php
// +----------------------------------------------------------------------
// | Author: ÖìÏş·å <wujixiaofeng@qq.com> <http://www.zhuxiaofeng.cn>
// +----------------------------------------------------------------------
namespace app\common\model;

class Enews extends Base{
	public $table='';
	public function settable($table){
		$this->table=$table;
		return $this;
	}
	public function getUserCount($userid,$ismember=true){
		return $this->where($this->getwhere($userid,0,$ismember))->count();
	}
	public function getUserList($userid,$page,$pagesize=10,$fields=[],$ismember=true){
		$select=$this->where($this->getwhere($userid,0,$ismember))->field($fields)->page($page,$pagesize)->order('newstime','desc')->select();
		if($select)return $select->toArray();
		return false;
	}
	public function getCount($classid=0){
		if(is_array($classid)){
			$where=$classid;
		}elseif(is_numeric($classid)){
			$where=[];
			if($classid>0)$where[]=['classid','=',$classid];
		}else{
			$where=$classid;
		}
		return $this->where($where)->count();
	}
	public function getList($classid,$page,$pagesize=10,$fields=[]){
		if(is_array($classid)){
			$where=$classid;
		}elseif(is_numeric($classid)){
			$where=[];
			if($classid>0)$where[]=['classid','=',$classid];
		}else{
			$where=$classid;
		}
		$select=$this->where($where)->field($fields)->page($page,$pagesize)->order('newstime','desc')->select();
		if($select)return $select->toArray();
		return false;
	}
	public function getInfo($id,$userid=0,$fields=[],$ismember=true){
		$where=$this->getwhere($userid,$id,$ismember);
		$find=$this->where($where)->field($fields)->find();
		if($find)return $find->toArray();
		return false;
	}
	public function del($id,$userid=0,$ismember=true){
		$where=$this->getwhere($userid,$id,$ismember);
		return $this->where($where)->delete();
	}
	public function getwhere($userid=0,$id=0,$ismember=true){
		$where=[];
		if($userid>0){
			$where[]=['userid','=',$userid];
			if($ismember)$where[]=['ismember','=',1];
		}
		if($id>0){
			$where[]=['id','=',$id];
		}
		return $where;
	}
}

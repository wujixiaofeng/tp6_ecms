<?php
namespace app\android\controller;
use think\facade\Db;
use think\facade\Request;

//app系统控制器
class Sys extends Base {
	//APP获取更新的操作
	public function update(){
		//$debug=$this->duser;
		//获取app传来的版本号
		$appVerCode=(int)$_GET['vercode'];
		if($debug)$appVerCode-=1;
		//获取app传来的系统版本号
		$appSDK=(int)$_GET['sdk'];
		//获取最小的最小版本号 防止低版本无法升级高版本
		$mincode=(int)DB::name('AppUpdate')->where('mincode','>',$appVerCode)->order('mincode','asc')->value('mincode');
		if($mincode>0){
			//如果有最小版本号 则获取最小版本号对应的版本
			$ver=DB::name('AppUpdate')->where('vercode',$mincode)->find();
		}else{
			//如果没有最小版本号 则获取大于当前app版本的最高版本
			$ver=DB::name('AppUpdate')->where('vercode','>',$appVerCode)->order('vercode desc')->find();
		}
		//如果没有查询到版本则显示no ok
		if(!$ver)jsonerr();
		//获取大于当前app版本并且小于等于查询出的版本号的版本列表 用来输出各个版本的更新信息
		$infolist=DB::name('AppUpdate')->where([['vercode','>',(int)$appVerCode],['vercode','<=',(int)$ver['vercode']]])->field(array('vername','info'))->order('vercode','asc')->select()->toArray();
		//初始化版本更新信息
		
		$info="";
		//循环输出版本更新信息
		foreach($infolist as $k=>$v){
			//输出版本号和更新信息 如果版本信息不为空则增加换行符
			$info.=($info?"\r\n":"").'v'.$v['vername']."\r\n".$v['info'];
		}
		
		//初始化强制更新为false
		$force=false;
		//如果有强制更新条件
		if($ver['force']){
			//过滤字符串
			$if=update_force_replace($ver['force']);
			//处理变量、符号和括号
			$if=update_force_replace2($if);
			//如果符号条件则设置强制更新为true
			@eval('if('.$if.'){$force=true;}');
		}
		if($debug){
			$ver['vercode']+=1;
			$force=true;
		}
		//输出更新信息
		return jsonok(
			array(
				"vercode"=>$ver['vercode'],
				"vername"=>$ver['vername'],
				"force"=>$force,
				"url"=>"http://".$_SERVER[HTTP_HOST].$ver['filepath'],
				"info"=>$info
			)
		);
	}
	//记录分享日志
	public function sharelog(){
		return jsonerr();
		$this->checklogin();
		$res['userid']=tp_login();
		$res['info']=input('post.info');
		$res['time']=NOW_TIME;
		D('ShareLog')->add($res);
	}
	//提交app设置项
	public function postsettings(){
		return jsonerr();
		//如果是post提交
		if(IS_POST){
			//检测登录
			$this->checklogin();
			//如果有post的json数据
			if($_POST['json']){
				//解密json数据
				$json=json_decode($_POST['json'],true);
				//删除数据中的无用信息
				unset($json['havemsg'],$json['ok'],$json['needlogin'],$json['msg']);
				//保存数据到文件
				file_put_contents(SITE_PATH."/Uploads/appsettings.json",json_encode($json));
				//提示成功
				jsonok('保存成功！');
			}
		}
	}
	//获取app设置项
	public function getsettings(){
		return jsonerr();
		//从文件读取数据
		$json=@file_get_contents(SITE_PATH."/Uploads/appsettings.json");
		if($json){
			//如果有数据则返回数据
			return jsonok(json_decode($json,true));
		}else{
			//如果有数据则返回无配置
			return jsonerr("无配置！");
		}
	}
	//app获取系统消息的操作 这里的参数data是从 runlog方法中指定的
	public function notice($res=array()){
		//如果操作名是notice则退出 防止旧版获取
		//if(Request::action()=='notice')return jsonerr();
		//如果过日志类型不是0 则退出 0代表是app运行
		if($res['logtype']!=0)return jsonerr();
		//获取没有禁用的消息列表
		$list=DB::name('AppNotice')->where(array('jinyong'=>0))->select()->toArray();
		//初始化输出的消息列表
		$noticelist=array();
		//循环处理消息列表
		foreach($list as $k1=>$v1){
			//是否添加此条到输出列表
			$addthis=false;
			//如果条件等于all则输出
			if($v1['condition']=='all'){
				$addthis=true;
			}else{
				//如果条件不为all则处理条件
				//解密条件json数据
				$conlist=json_decode($v1['condition'],true);
				$conlist=togbk($conlist);
				//初始化判断条件为空
				$condition='';
				//循环处理json数据
				foreach($conlist as $k2=>$v2){
					//条件名称过滤字符串
					$k2=preg_replace('%[^a-z_]%','',$k2);
					//条件值过滤字符串
					$v2=str_replace(array('$','"',"'",'(',')','\\'),'',$v2);
					//如果条件名称中没有下划线
					if(strpos($k2,'_')===false){
						//指定符号为==
						$symbol='==';
						//指定条件名称为k2的值
						$cname=$k2;
					}else{
						//如果条件名称中包含下划线 则已下划线将条件名称转换为数组
						$c0=explode('_',$k2);
						//符号名称为数组第二项
						$symbol=$c0[1];
						if($symbol=='gt'){
							//如果符号名称为大于
							$symbol='>';
						}elseif($symbol=='lt'){
							//如果符号名称为小于
							$symbol='<';
						}else{
							//符号默认为等于
							$symbol='==';
						}
						//指定条件名称为数组第一项
						$cname=$c0[0];
					}
					//将此条件添加到判读条件 如果有判断条件则添加and
					$condition.=(!empty($condition)?' and ':'').' $res['.$cname.']'.$symbol.'"'.$v2.'" ';
				}
				//如果有判断条件
				if($condition){
					//根据条件指定是否添加此条到输出列表
					eval('if('.$condition.'){$addthis=true;}');
				}
			}
			//如果添加此条到输出列表
			if($addthis){
				//将不再提示转换为布尔值
				$v1['cannotips']=!!$v1['cannotips'];
				//删除条件字段
				unset($v1['condition'],$v1['jinyong']);
				//添加此条到输出列表
				$noticelist[]=$v1;
			}
		}
		//输出列表
		return jsonok(array('list'=>$noticelist));
	}
	public function runlog(){
		//如果为post提交
		if(request()->isPost()){
			//获取提交的品牌
			$res['brand']=input('post.brand');
			//获取提交的型号
			$res['model']=input('post.model');
			//获取提交的mac地址
			$res['mac']=input('post.mac');
			//获取提交的是否是首次运行
			$res['isfirst']=input('post.isfirst',0,'intval');
			//获取提交的版本名称
			$res['version']=input('post.version');
			//获取当前登录的用户id
			$res['userid']=tp_login();
			//获取提交的日志类型
			$res['logtype']=input('post.logtype',0,'intval');
			//获取提交的安卓id
			$res['androidid']=input('post.androidid');
			//获取提交的序列号
			$res['snum']=input('post.snum');
			//获取提交的系统版本号
			$res['sdk']=input('post.sdk');
			//获取提交的当前版本号
			$res['nowvercode']=input('post.nowvercode',0,'intval');
			//获取提交的旧版本号
			$res['oldvercode']=input('post.oldvercode',0,'intval');
			//获取提交IP地址
			$res['ip']=request()->ip();
			//获取当前时间戳
			$res['time']=NOW_TIME;
			//如果mac不等于80:AD:16:D3:C0:59 并且型号不是安卓模拟器
			if($res['mac']!="80:AD:16:D3:C0:59" and strpos($res['model'],'Android SDK')===false){
				//插入数据到数据库
				DB::name('AppRunlog')->insert($res);
			}
			//调用系统通知方法
			return $this->notice($res);
		}
	}
}
?>
<?php
namespace app\android\controller;
use think\facade\View;
use think\facade\Db;
use app\common\model\ErrorTimes;
use think\exception\HttpResponseException;
use think\facade\Config;

class Admin extends Base {
	public $isadmin=true;
	public function initialize() {
		parent::initialize();
		Config::set(['cookie_prefix'=>'admin_','session_prefix'=>'admin_'],'config');
		if(request()->action()!='login')$this->checklogin();
	}
	public function index(){
		if(tp_login())$this->success('登录成功！');
		return '请先登录！';
	}
	public function logout(){
		clear_login();
		$this->success('成功退出登录！');
	}
	public function login(){
		if(!request()->isPost()&&request()->action()!='getlogin'){
			return $this->view();
		}
		if(ErrorTimes::check_times()){
			$this->error('此IP超过登录次数！');
		}
		$username=input2('post.username');
		$password=input2('post.password');
		$remember=input2('post.remember');
		$user=DB::name('enewsuser')->where('username',$username)->find();
		
		if(!$user)$this->error('没有此用户！');
		if(adminPassword($password,$user['salt'],$user['salt2'])!=$user['password']){
			ErrorTimes::add_times();
			$this->error('密码错误！');
		}
		set_login($user['userid'],$username,$password,$user['groupid'],!!$remember,true);
		$userinfo=['userid'=>$user['userid'],'username'=>$username];
		$this->success('登录成功！',restore());
	}
	//APP运行日志
	public function runlog(){
		//每页显示的数量
		$pagesize=20;
		//获取分页
		$page=input('page',1,'intval');
		//初始化查询条件
		$where=array();
		//赋值查询条件用户id
		if($userid=input('get.user',0,'intval')){
			$where[]=['userid','=',$userid];
		}
		//赋值查询条件logtype
		if(isset($_GET['logtype'])){
			$where[]=['logtype','=',input('get.logtype')];
		}
		//如果存在关键字
		if($keywords=input2('get.keywords')){
			//赋值昵称 姓名 手机号 关键字的查询条件
			$where[]=['brand|model|mac|androidid|snum','like','%'.$keywords.'%'];
		}
		//日志总数
		$count=DB::name('AppRunlog')->where($where)->count();
		//根据总数和分页数获取分页html代码
		$pagehtml=getpagehtml($count,$pagesize);
		//读取指定页的数据 并以id从大到小排序
		$list=DB::name('AppRunlog')->where($where)->page($page,$pagesize)->order('id desc')->select()->toArray();
		//列表数据传值到模板
		View::assign('list',$list);
		//分页代码传值到模板
		View::assign('pagehtml',$pagehtml);
		//显示界面
		return $this->view();
	}
	
	
	
	
	
	
	
	
	//删除系统消息操作
	public function noticedel($id=0){
		//获取id
		$id=intval($id);
		//从数据库读取信息
		$info=DB::name('AppNotice')->find($id);
		//删除此条信息
		DB::name('AppNotice')->delete($id);
		//管理员操作日志
		adminlog('删除通知'.'<br>id='.$id.'<br>'.'title='.$info['title'].',info='.$info['info']);
		//提示成功
		$this->success('删除成功！');
	}
	//禁用系统消息操作
	public function noticejinyong($id=0){
		//获取id
		$id=intval($id);
		//从数据库读取信息
		$info=DB::name('AppNotice')->find($id);
		//赋值禁用
		$jinyong=$info['jinyong'];
		if($jinyong){
			//如果已经禁用则取消禁用
			DB::name('AppNotice')->where(array('id'=>$id))->save(array('jinyong'=>0));
			//管理员操作日志
			adminlog('取消禁用通知'.'<br>id='.$id.'<br>'.'title='.$info['title'].',info='.$info['info']);
			//提示成功
			$this->success('取消禁用成功！');
		}else{
			//如果没有禁用则禁用
			DB::name('AppNotice')->where(array('id'=>$id))->save(array('jinyong'=>1));
			//管理员操作日志
			adminlog('禁用通知'.'<br>id='.$id.'<br>'.'title='.$info['title'].',info='.$info['info']);
			//提示成功
			$this->success('禁用成功！');
		}
	}
	//版本列表
	public function noticelist(){
		//每页显示的数量
		$pagesize=20;
		//获取分页
		$page=input('page',1,'intval');
		//日志总数
		$count=DB::name('AppNotice')->count();
		//根据总数和分页数获取分页html代码
		$pagehtml=getmpagehtml($count,$pagesize);
		//读取指定页的数据 并以id从大到小排序
		//从数据库读取版本列表
		$list=DB::name('AppNotice')->page($page,$pagesize)->order('id desc')->select()->toArray();
		//传值到模板
		View::assign('list',$list);
		//分页代码传值到模板
		View::assign('pagehtml',$pagehtml);
		//显示其他操作菜单
		View::assign('rlinks',array('添加消息'=>url('android/admin/notice')->build()));
		//显示界面
		return $this->view();
	}
	
	//系统消息
	public function notice(){
		//获取编辑时的id
		$id=input('get.id',0,'intval');
		//如果是post提交
		if(request()->isPost()){
			//初始化插入数据
			$data=array();
			//获取提交的消息标题
			$data['title']=input('post.title');
			//获取提交的提示次数
			$data['times']=input('post.times');
			//获取提交的消息内容
			$data['info']=input('post.info');
			//获取提交的可以不再提示
			$data['cannotips']=input('post.cannotips',0,'intval');
			//获取提交的是否禁用
			$data['jinyong']=input('post.jinyong',0,'intval');
			//如果条件类型为all
			if($_POST['ctype']=='all'){
				$data['condition']='all';
			}else{
				//初始化条件数据变量
				$c=array();
				//获取提交的条件名称数组
				$cname=input('post.cname');
				//获取提交的条件值数组
				$cvalue=input('post.cvalue');
				//循环处理提交的条件
				foreach($cname as $k=>$v){
					//获取条件名称并替换字符串
					$cnamei=preg_replace('%[^a-z_]%','',$v);
					//获取条件值并替换字符串
					$cvaluei=str_replace(array('$','"',"'",'(',')','\\'),'',$cvalue[$k]);
					//如果条件为first则条件值为1
					if($cnamei=='first'){
						$c[$cnamei]=1;
					//如果条件名称和条件值都不为空则赋值条件到数据
					}elseif($cnamei and $cvaluei!==''){
						$c[$cnamei]=$cvaluei;
					}
				}
				//将数据转化为json字符串
				$data['condition']=json_encode($c);
			}
			//如果有id则为修改
			if($id){
				//数据中增加id 用来指定修改那条数据
				$data['id']=$id;
				DB::name('AppNotice')->update($data);
				adminlog('编辑通知'.'<br>id='.$id.'<br>'.'title='.$data['title'].',info='.$data['info']);
			}else{
				//如果没有id则为增加
				$id=DB::name('AppNotice')->insert($data);
				adminlog('添加通知'.'<br>id='.$id.'<br>'.'title='.$data['title'].',info='.$data['info']);
			}
			//提示成功并跳转
			$this->success("提交成功！",url('android/admin/noticelist')->build());
		}
		//如果有id则读取数据并传值到模板
		if($id){
			$info=DB::name('AppNotice')->find($id);
			if($info['condition']!='all')$info['condition']=json_decode($info['condition'],true);
			View::assign('info',$info);
		}
		//显示其他操作菜单
		View::assign('rlinks',array('消息列表'=>url('android/admin/noticelist')->build()));
		return $this->view();
	}

	
	
	
	
	
	
	
	
	//版本列表
	public function verlist(){
		//每页显示的数量
		$pagesize=20;
		//获取分页
		$page=input('get.page/d',1);
		//日志总数
		$count=DB::name('AppUpdate')->count();
		//根据总数和分页数获取分页html代码
		$pagehtml=getmpagehtml($count,$pagesize);
		//读取指定页的数据 并以id从大到小排序
		//从数据库读取版本列表
		$list=DB::name('AppUpdate')->page($page)->order('vercode','desc')->select()->toArray();
		//传值到模板
		View::assign('list',$list);
		//分页代码传值到模板
		View::assign('pagehtml',$pagehtml);
		//显示其他操作菜单
		View::assign('rlinks',array('添加版本'=>url('android/admin/update')->build()));
		//显示界面
		return $this->view();
	}
	/*function throw_error($type, $message, $file, $line){
		throw new \Exception($message);
	}*/
	//更新管理
	public function update(){
		//获取编辑时的id
		$id=input('get.id',0,'intval');
		//如果是post提交
		if(request()->isPost()){
			//因为表单有文件不能使用js提交，这里使用iframe提交，所以需要iframe调用父窗口函数显示成功信息
			//初始化插入数据
			$data=array();
			//获取提交的版本号
			$data['vercode']=input('post.vercode',0,'intval');
			//获取提交的版本名称 并过滤字符 只允许 数字和小数点
			$data['vername']=preg_replace('%[^0-9\.]%','',input('post.vername'));
			//获取提交的强制升级设置 并过滤字符 只允许数字 大于小于号 & | = 小括号   !@ sdk会被替换成! ver会被替换成@
			$data['force']=update_force_replace($_POST['force']);
			//升级此版本需要的最小版本号 0为不限制
			$data['mincode']=input('post.mincode',0,'intval');
			//获取提交的更新说明
			$data['info']=input('post.info');
			if(DB::name('AppUpdate')->where([['vercode','=',$data['vercode']],['id','<>',$id]])->find()){
				$this->error('已存在此版本号！');
			}
			if(DB::name('AppUpdate')->where([['vername','=',$data['vername']],['id','<>',$id]])->find()){
				$this->error('已存在此版本名称！');
			}
			//如果有提交文件
			if($_FILES['file1']['name']){
				if($_FILES['file1']['error']==0){
					//文件保存路径
					$filepath='/android2/apk/domain_'.$data['vername'].'.apk';
					//复制上传的文件到网站目录
					if(@move_uploaded_file($_FILES['file1']['tmp_name'],ECMS_PATH.substr($filepath,1))){
						//如果选中了替换sync.apk 则复制并替换sync.apk
						if($_POST['copy'])@copy(ECMS_PATH.substr($filepath,1),ECMS_PATH.'android2/apk/domain.apk');
						//将文件路径赋值到数据
						$data['filepath']=$filepath;
					}
				}else{
					$this->error('上传文件发生错误，错误码：'.$_FILES['file1']['error']);
				}
			}
			//修改时间
			$data['updatetime']=NOW_TIME;
			//如果有id则为修改
			if($id){
				//数据中增加id 用来指定修改那条数据
				$data['id']=$id;
				DB::name('AppUpdate')->update($data);
				adminlog('编辑APP版本'.'<br>id='.$id.'<br>'.'版本名称='.$data['vername']);
			}else{
				//添加时间
				$data['addtime']=NOW_TIME;
				//如果没有id则为增加
				$id=DB::name('AppUpdate')->insert($data);
				adminlog('添加APP版本'.'<br>id='.$id.'<br>'.'版本名称='.$data['vername']);
			}
			//提示成功并跳转
			$this->success('保存成功！',url('android/admin/verlist')->build());
		}
		//如果有id则读取数据并传值到模板
		if($id){
			$info=DB::name('AppUpdate')->find($id);
			View::assign('info',$info);
		}
		//初始化最小版本号查询条件
		$mincodewhere=array();
		//如果有版本信息则指定小于版本信息的版本号
		if($info){
			$mincodewhere[]=['vercode','<',$info['vercode']];
		}
		//获取比当前版本号小的最小大的最小版本号
		$mincode=(int)DB::name('AppUpdate')->where($mincodewhere)->order('mincode','desc')->value('mincode');
		//传值最小版本号到模板
		View::assign('mincode',$mincode);
		//显示其他操作菜单
		View::assign('rlinks',array('版本列表'=>url('android/admin/verlist')->build()));
		//显示界面
		return $this->view();
	}
}
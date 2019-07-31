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
		if(tp_login())$this->success('��¼�ɹ���');
		return '���ȵ�¼��';
	}
	public function logout(){
		clear_login();
		$this->success('�ɹ��˳���¼��');
	}
	public function login(){
		if(!request()->isPost()&&request()->action()!='getlogin'){
			return $this->view();
		}
		if(ErrorTimes::check_times()){
			$this->error('��IP������¼������');
		}
		$username=input2('post.username');
		$password=input2('post.password');
		$remember=input2('post.remember');
		$user=DB::name('enewsuser')->where('username',$username)->find();
		
		if(!$user)$this->error('û�д��û���');
		if(adminPassword($password,$user['salt'],$user['salt2'])!=$user['password']){
			ErrorTimes::add_times();
			$this->error('�������');
		}
		set_login($user['userid'],$username,$password,$user['groupid'],!!$remember,true);
		$userinfo=['userid'=>$user['userid'],'username'=>$username];
		$this->success('��¼�ɹ���',restore());
	}
	//APP������־
	public function runlog(){
		//ÿҳ��ʾ������
		$pagesize=20;
		//��ȡ��ҳ
		$page=input('page',1,'intval');
		//��ʼ����ѯ����
		$where=array();
		//��ֵ��ѯ�����û�id
		if($userid=input('get.user',0,'intval')){
			$where[]=['userid','=',$userid];
		}
		//��ֵ��ѯ����logtype
		if(isset($_GET['logtype'])){
			$where[]=['logtype','=',input('get.logtype')];
		}
		//������ڹؼ���
		if($keywords=input2('get.keywords')){
			//��ֵ�ǳ� ���� �ֻ��� �ؼ��ֵĲ�ѯ����
			$where[]=['brand|model|mac|androidid|snum','like','%'.$keywords.'%'];
		}
		//��־����
		$count=DB::name('AppRunlog')->where($where)->count();
		//���������ͷ�ҳ����ȡ��ҳhtml����
		$pagehtml=getpagehtml($count,$pagesize);
		//��ȡָ��ҳ������ ����id�Ӵ�С����
		$list=DB::name('AppRunlog')->where($where)->page($page,$pagesize)->order('id desc')->select()->toArray();
		//�б����ݴ�ֵ��ģ��
		View::assign('list',$list);
		//��ҳ���봫ֵ��ģ��
		View::assign('pagehtml',$pagehtml);
		//��ʾ����
		return $this->view();
	}
	
	
	
	
	
	
	
	
	//ɾ��ϵͳ��Ϣ����
	public function noticedel($id=0){
		//��ȡid
		$id=intval($id);
		//�����ݿ��ȡ��Ϣ
		$info=DB::name('AppNotice')->find($id);
		//ɾ��������Ϣ
		DB::name('AppNotice')->delete($id);
		//����Ա������־
		adminlog('ɾ��֪ͨ'.'<br>id='.$id.'<br>'.'title='.$info['title'].',info='.$info['info']);
		//��ʾ�ɹ�
		$this->success('ɾ���ɹ���');
	}
	//����ϵͳ��Ϣ����
	public function noticejinyong($id=0){
		//��ȡid
		$id=intval($id);
		//�����ݿ��ȡ��Ϣ
		$info=DB::name('AppNotice')->find($id);
		//��ֵ����
		$jinyong=$info['jinyong'];
		if($jinyong){
			//����Ѿ�������ȡ������
			DB::name('AppNotice')->where(array('id'=>$id))->save(array('jinyong'=>0));
			//����Ա������־
			adminlog('ȡ������֪ͨ'.'<br>id='.$id.'<br>'.'title='.$info['title'].',info='.$info['info']);
			//��ʾ�ɹ�
			$this->success('ȡ�����óɹ���');
		}else{
			//���û�н��������
			DB::name('AppNotice')->where(array('id'=>$id))->save(array('jinyong'=>1));
			//����Ա������־
			adminlog('����֪ͨ'.'<br>id='.$id.'<br>'.'title='.$info['title'].',info='.$info['info']);
			//��ʾ�ɹ�
			$this->success('���óɹ���');
		}
	}
	//�汾�б�
	public function noticelist(){
		//ÿҳ��ʾ������
		$pagesize=20;
		//��ȡ��ҳ
		$page=input('page',1,'intval');
		//��־����
		$count=DB::name('AppNotice')->count();
		//���������ͷ�ҳ����ȡ��ҳhtml����
		$pagehtml=getmpagehtml($count,$pagesize);
		//��ȡָ��ҳ������ ����id�Ӵ�С����
		//�����ݿ��ȡ�汾�б�
		$list=DB::name('AppNotice')->page($page,$pagesize)->order('id desc')->select()->toArray();
		//��ֵ��ģ��
		View::assign('list',$list);
		//��ҳ���봫ֵ��ģ��
		View::assign('pagehtml',$pagehtml);
		//��ʾ���������˵�
		View::assign('rlinks',array('�����Ϣ'=>url('android/admin/notice')->build()));
		//��ʾ����
		return $this->view();
	}
	
	//ϵͳ��Ϣ
	public function notice(){
		//��ȡ�༭ʱ��id
		$id=input('get.id',0,'intval');
		//�����post�ύ
		if(request()->isPost()){
			//��ʼ����������
			$data=array();
			//��ȡ�ύ����Ϣ����
			$data['title']=input('post.title');
			//��ȡ�ύ����ʾ����
			$data['times']=input('post.times');
			//��ȡ�ύ����Ϣ����
			$data['info']=input('post.info');
			//��ȡ�ύ�Ŀ��Բ�����ʾ
			$data['cannotips']=input('post.cannotips',0,'intval');
			//��ȡ�ύ���Ƿ����
			$data['jinyong']=input('post.jinyong',0,'intval');
			//�����������Ϊall
			if($_POST['ctype']=='all'){
				$data['condition']='all';
			}else{
				//��ʼ���������ݱ���
				$c=array();
				//��ȡ�ύ��������������
				$cname=input('post.cname');
				//��ȡ�ύ������ֵ����
				$cvalue=input('post.cvalue');
				//ѭ�������ύ������
				foreach($cname as $k=>$v){
					//��ȡ�������Ʋ��滻�ַ���
					$cnamei=preg_replace('%[^a-z_]%','',$v);
					//��ȡ����ֵ���滻�ַ���
					$cvaluei=str_replace(array('$','"',"'",'(',')','\\'),'',$cvalue[$k]);
					//�������Ϊfirst������ֵΪ1
					if($cnamei=='first'){
						$c[$cnamei]=1;
					//����������ƺ�����ֵ����Ϊ����ֵ����������
					}elseif($cnamei and $cvaluei!==''){
						$c[$cnamei]=$cvaluei;
					}
				}
				//������ת��Ϊjson�ַ���
				$data['condition']=json_encode($c);
			}
			//�����id��Ϊ�޸�
			if($id){
				//����������id ����ָ���޸���������
				$data['id']=$id;
				DB::name('AppNotice')->update($data);
				adminlog('�༭֪ͨ'.'<br>id='.$id.'<br>'.'title='.$data['title'].',info='.$data['info']);
			}else{
				//���û��id��Ϊ����
				$id=DB::name('AppNotice')->insert($data);
				adminlog('���֪ͨ'.'<br>id='.$id.'<br>'.'title='.$data['title'].',info='.$data['info']);
			}
			//��ʾ�ɹ�����ת
			$this->success("�ύ�ɹ���",url('android/admin/noticelist')->build());
		}
		//�����id���ȡ���ݲ���ֵ��ģ��
		if($id){
			$info=DB::name('AppNotice')->find($id);
			if($info['condition']!='all')$info['condition']=json_decode($info['condition'],true);
			View::assign('info',$info);
		}
		//��ʾ���������˵�
		View::assign('rlinks',array('��Ϣ�б�'=>url('android/admin/noticelist')->build()));
		return $this->view();
	}

	
	
	
	
	
	
	
	
	//�汾�б�
	public function verlist(){
		//ÿҳ��ʾ������
		$pagesize=20;
		//��ȡ��ҳ
		$page=input('get.page/d',1);
		//��־����
		$count=DB::name('AppUpdate')->count();
		//���������ͷ�ҳ����ȡ��ҳhtml����
		$pagehtml=getmpagehtml($count,$pagesize);
		//��ȡָ��ҳ������ ����id�Ӵ�С����
		//�����ݿ��ȡ�汾�б�
		$list=DB::name('AppUpdate')->page($page)->order('vercode','desc')->select()->toArray();
		//��ֵ��ģ��
		View::assign('list',$list);
		//��ҳ���봫ֵ��ģ��
		View::assign('pagehtml',$pagehtml);
		//��ʾ���������˵�
		View::assign('rlinks',array('��Ӱ汾'=>url('android/admin/update')->build()));
		//��ʾ����
		return $this->view();
	}
	/*function throw_error($type, $message, $file, $line){
		throw new \Exception($message);
	}*/
	//���¹���
	public function update(){
		//��ȡ�༭ʱ��id
		$id=input('get.id',0,'intval');
		//�����post�ύ
		if(request()->isPost()){
			//��Ϊ�����ļ�����ʹ��js�ύ������ʹ��iframe�ύ��������Ҫiframe���ø����ں�����ʾ�ɹ���Ϣ
			//��ʼ����������
			$data=array();
			//��ȡ�ύ�İ汾��
			$data['vercode']=input('post.vercode',0,'intval');
			//��ȡ�ύ�İ汾���� �������ַ� ֻ���� ���ֺ�С����
			$data['vername']=preg_replace('%[^0-9\.]%','',input('post.vername'));
			//��ȡ�ύ��ǿ���������� �������ַ� ֻ�������� ����С�ں� & | = С����   !@ sdk�ᱻ�滻��! ver�ᱻ�滻��@
			$data['force']=update_force_replace($_POST['force']);
			//�����˰汾��Ҫ����С�汾�� 0Ϊ������
			$data['mincode']=input('post.mincode',0,'intval');
			//��ȡ�ύ�ĸ���˵��
			$data['info']=input('post.info');
			if(DB::name('AppUpdate')->where([['vercode','=',$data['vercode']],['id','<>',$id]])->find()){
				$this->error('�Ѵ��ڴ˰汾�ţ�');
			}
			if(DB::name('AppUpdate')->where([['vername','=',$data['vername']],['id','<>',$id]])->find()){
				$this->error('�Ѵ��ڴ˰汾���ƣ�');
			}
			//������ύ�ļ�
			if($_FILES['file1']['name']){
				if($_FILES['file1']['error']==0){
					//�ļ�����·��
					$filepath='/android2/apk/domain_'.$data['vername'].'.apk';
					//�����ϴ����ļ�����վĿ¼
					if(@move_uploaded_file($_FILES['file1']['tmp_name'],ECMS_PATH.substr($filepath,1))){
						//���ѡ�����滻sync.apk ���Ʋ��滻sync.apk
						if($_POST['copy'])@copy(ECMS_PATH.substr($filepath,1),ECMS_PATH.'android2/apk/domain.apk');
						//���ļ�·����ֵ������
						$data['filepath']=$filepath;
					}
				}else{
					$this->error('�ϴ��ļ��������󣬴����룺'.$_FILES['file1']['error']);
				}
			}
			//�޸�ʱ��
			$data['updatetime']=NOW_TIME;
			//�����id��Ϊ�޸�
			if($id){
				//����������id ����ָ���޸���������
				$data['id']=$id;
				DB::name('AppUpdate')->update($data);
				adminlog('�༭APP�汾'.'<br>id='.$id.'<br>'.'�汾����='.$data['vername']);
			}else{
				//���ʱ��
				$data['addtime']=NOW_TIME;
				//���û��id��Ϊ����
				$id=DB::name('AppUpdate')->insert($data);
				adminlog('���APP�汾'.'<br>id='.$id.'<br>'.'�汾����='.$data['vername']);
			}
			//��ʾ�ɹ�����ת
			$this->success('����ɹ���',url('android/admin/verlist')->build());
		}
		//�����id���ȡ���ݲ���ֵ��ģ��
		if($id){
			$info=DB::name('AppUpdate')->find($id);
			View::assign('info',$info);
		}
		//��ʼ����С�汾�Ų�ѯ����
		$mincodewhere=array();
		//����а汾��Ϣ��ָ��С�ڰ汾��Ϣ�İ汾��
		if($info){
			$mincodewhere[]=['vercode','<',$info['vercode']];
		}
		//��ȡ�ȵ�ǰ�汾��С����С�����С�汾��
		$mincode=(int)DB::name('AppUpdate')->where($mincodewhere)->order('mincode','desc')->value('mincode');
		//��ֵ��С�汾�ŵ�ģ��
		View::assign('mincode',$mincode);
		//��ʾ���������˵�
		View::assign('rlinks',array('�汾�б�'=>url('android/admin/verlist')->build()));
		//��ʾ����
		return $this->view();
	}
}
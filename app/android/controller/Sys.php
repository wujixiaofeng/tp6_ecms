<?php
namespace app\android\controller;
use think\facade\Db;
use think\facade\Request;

//appϵͳ������
class Sys extends Base {
	//APP��ȡ���µĲ���
	public function update(){
		//$debug=$this->duser;
		//��ȡapp�����İ汾��
		$appVerCode=(int)$_GET['vercode'];
		if($debug)$appVerCode-=1;
		//��ȡapp������ϵͳ�汾��
		$appSDK=(int)$_GET['sdk'];
		//��ȡ��С����С�汾�� ��ֹ�Ͱ汾�޷������߰汾
		$mincode=(int)DB::name('AppUpdate')->where('mincode','>',$appVerCode)->order('mincode','asc')->value('mincode');
		if($mincode>0){
			//�������С�汾�� ���ȡ��С�汾�Ŷ�Ӧ�İ汾
			$ver=DB::name('AppUpdate')->where('vercode',$mincode)->find();
		}else{
			//���û����С�汾�� ���ȡ���ڵ�ǰapp�汾����߰汾
			$ver=DB::name('AppUpdate')->where('vercode','>',$appVerCode)->order('vercode desc')->find();
		}
		//���û�в�ѯ���汾����ʾno ok
		if(!$ver)jsonerr();
		//��ȡ���ڵ�ǰapp�汾����С�ڵ��ڲ�ѯ���İ汾�ŵİ汾�б� ������������汾�ĸ�����Ϣ
		$infolist=DB::name('AppUpdate')->where([['vercode','>',(int)$appVerCode],['vercode','<=',(int)$ver['vercode']]])->field(array('vername','info'))->order('vercode','asc')->select()->toArray();
		//��ʼ���汾������Ϣ
		
		$info="";
		//ѭ������汾������Ϣ
		foreach($infolist as $k=>$v){
			//����汾�ź͸�����Ϣ ����汾��Ϣ��Ϊ�������ӻ��з�
			$info.=($info?"\r\n":"").'v'.$v['vername']."\r\n".$v['info'];
		}
		
		//��ʼ��ǿ�Ƹ���Ϊfalse
		$force=false;
		//�����ǿ�Ƹ�������
		if($ver['force']){
			//�����ַ���
			$if=update_force_replace($ver['force']);
			//������������ź�����
			$if=update_force_replace2($if);
			//�����������������ǿ�Ƹ���Ϊtrue
			@eval('if('.$if.'){$force=true;}');
		}
		if($debug){
			$ver['vercode']+=1;
			$force=true;
		}
		//���������Ϣ
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
	//��¼������־
	public function sharelog(){
		return jsonerr();
		$this->checklogin();
		$res['userid']=tp_login();
		$res['info']=input('post.info');
		$res['time']=NOW_TIME;
		D('ShareLog')->add($res);
	}
	//�ύapp������
	public function postsettings(){
		return jsonerr();
		//�����post�ύ
		if(IS_POST){
			//����¼
			$this->checklogin();
			//�����post��json����
			if($_POST['json']){
				//����json����
				$json=json_decode($_POST['json'],true);
				//ɾ�������е�������Ϣ
				unset($json['havemsg'],$json['ok'],$json['needlogin'],$json['msg']);
				//�������ݵ��ļ�
				file_put_contents(SITE_PATH."/Uploads/appsettings.json",json_encode($json));
				//��ʾ�ɹ�
				jsonok('����ɹ���');
			}
		}
	}
	//��ȡapp������
	public function getsettings(){
		return jsonerr();
		//���ļ���ȡ����
		$json=@file_get_contents(SITE_PATH."/Uploads/appsettings.json");
		if($json){
			//����������򷵻�����
			return jsonok(json_decode($json,true));
		}else{
			//����������򷵻�������
			return jsonerr("�����ã�");
		}
	}
	//app��ȡϵͳ��Ϣ�Ĳ��� ����Ĳ���data�Ǵ� runlog������ָ����
	public function notice($res=array()){
		//�����������notice���˳� ��ֹ�ɰ��ȡ
		//if(Request::action()=='notice')return jsonerr();
		//�������־���Ͳ���0 ���˳� 0������app����
		if($res['logtype']!=0)return jsonerr();
		//��ȡû�н��õ���Ϣ�б�
		$list=DB::name('AppNotice')->where(array('jinyong'=>0))->select()->toArray();
		//��ʼ���������Ϣ�б�
		$noticelist=array();
		//ѭ��������Ϣ�б�
		foreach($list as $k1=>$v1){
			//�Ƿ���Ӵ���������б�
			$addthis=false;
			//�����������all�����
			if($v1['condition']=='all'){
				$addthis=true;
			}else{
				//���������Ϊall��������
				//��������json����
				$conlist=json_decode($v1['condition'],true);
				$conlist=togbk($conlist);
				//��ʼ���ж�����Ϊ��
				$condition='';
				//ѭ������json����
				foreach($conlist as $k2=>$v2){
					//�������ƹ����ַ���
					$k2=preg_replace('%[^a-z_]%','',$k2);
					//����ֵ�����ַ���
					$v2=str_replace(array('$','"',"'",'(',')','\\'),'',$v2);
					//�������������û���»���
					if(strpos($k2,'_')===false){
						//ָ������Ϊ==
						$symbol='==';
						//ָ����������Ϊk2��ֵ
						$cname=$k2;
					}else{
						//������������а����»��� �����»��߽���������ת��Ϊ����
						$c0=explode('_',$k2);
						//��������Ϊ����ڶ���
						$symbol=$c0[1];
						if($symbol=='gt'){
							//�����������Ϊ����
							$symbol='>';
						}elseif($symbol=='lt'){
							//�����������ΪС��
							$symbol='<';
						}else{
							//����Ĭ��Ϊ����
							$symbol='==';
						}
						//ָ����������Ϊ�����һ��
						$cname=$c0[0];
					}
					//����������ӵ��ж����� ������ж����������and
					$condition.=(!empty($condition)?' and ':'').' $res['.$cname.']'.$symbol.'"'.$v2.'" ';
				}
				//������ж�����
				if($condition){
					//��������ָ���Ƿ���Ӵ���������б�
					eval('if('.$condition.'){$addthis=true;}');
				}
			}
			//�����Ӵ���������б�
			if($addthis){
				//��������ʾת��Ϊ����ֵ
				$v1['cannotips']=!!$v1['cannotips'];
				//ɾ�������ֶ�
				unset($v1['condition'],$v1['jinyong']);
				//��Ӵ���������б�
				$noticelist[]=$v1;
			}
		}
		//����б�
		return jsonok(array('list'=>$noticelist));
	}
	public function runlog(){
		//���Ϊpost�ύ
		if(request()->isPost()){
			//��ȡ�ύ��Ʒ��
			$res['brand']=input('post.brand');
			//��ȡ�ύ���ͺ�
			$res['model']=input('post.model');
			//��ȡ�ύ��mac��ַ
			$res['mac']=input('post.mac');
			//��ȡ�ύ���Ƿ����״�����
			$res['isfirst']=input('post.isfirst',0,'intval');
			//��ȡ�ύ�İ汾����
			$res['version']=input('post.version');
			//��ȡ��ǰ��¼���û�id
			$res['userid']=tp_login();
			//��ȡ�ύ����־����
			$res['logtype']=input('post.logtype',0,'intval');
			//��ȡ�ύ�İ�׿id
			$res['androidid']=input('post.androidid');
			//��ȡ�ύ�����к�
			$res['snum']=input('post.snum');
			//��ȡ�ύ��ϵͳ�汾��
			$res['sdk']=input('post.sdk');
			//��ȡ�ύ�ĵ�ǰ�汾��
			$res['nowvercode']=input('post.nowvercode',0,'intval');
			//��ȡ�ύ�ľɰ汾��
			$res['oldvercode']=input('post.oldvercode',0,'intval');
			//��ȡ�ύIP��ַ
			$res['ip']=request()->ip();
			//��ȡ��ǰʱ���
			$res['time']=NOW_TIME;
			//���mac������80:AD:16:D3:C0:59 �����ͺŲ��ǰ�׿ģ����
			if($res['mac']!="80:AD:16:D3:C0:59" and strpos($res['model'],'Android SDK')===false){
				//�������ݵ����ݿ�
				DB::name('AppRunlog')->insert($res);
			}
			//����ϵͳ֪ͨ����
			return $this->notice($res);
		}
	}
}
?>
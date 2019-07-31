<?php
// +----------------------------------------------------------------------
// | Author: ������ <wujixiaofeng@qq.com> <http://www.zhuxiaofeng.cn>
// +----------------------------------------------------------------------
namespace app\common\model;

//��¼����ģ��
class ErrorTimes extends Base{
	//type 1:��¼������� 2��֤��������
	//����¼����
	public static function check_times($type=1){
		//ɾ��һСʱ����ĵ�¼����
		self::del_times();
		//����ip���ص�¼����
		return (self::where([['ip','=',request()->ip()],['type','=',$type]])->value('times')>=10);
	}
	//ɾ��һСʱ����ĵ�¼����
	public static function del_times(){
		//ɾ������
		self::where([['time','<',(NOW_TIME-60*60)]])->delete();
	}
	//��ӵ�¼���������ݿ�
	public static function add_times($type=1){
		//������д�ip�ĵ�¼����
		if($times=self::where([['ip','=',request()->ip()],['type','=',$type]])->value('times')){
			//���´�ip�ĵ�¼����
			self::where([['ip','=',request()->ip()],['type','=',$type]])->save(array('times'=>($times+1),'time'=>NOW_TIME));
		}else{
			//�����ip��¼���������ݿ�
			self::insert(['times'=>1,'time'=>NOW_TIME,'ip'=>request()->ip(),'type'=>$type]);
		}
	}
}

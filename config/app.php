<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | Ӧ������
// +----------------------------------------------------------------------

use think\facade\Env;
return [
	// Ӧ�õ�ַ
	'app_host'				 => Env::get('app.host', ''),
	// Ӧ��Trace�������������ȶ�ȡ��
	'app_trace'				=> false,
	// Ӧ�õ������ռ�
	'app_namespace'			=> '',
	// �Ƿ�����·��
	'with_route'			=> true,
	// �Ƿ������¼�
	'with_event'			=> true,
	// �Զ���Ӧ��ģʽ
	'auto_multi_app'		=> !defined('BIND_APP'),
	// Ӧ��ӳ�䣨�Զ���Ӧ��ģʽ��Ч��
	'app_map'				=> [],
	// �����󶨣��Զ���Ӧ��ģʽ��Ч��
	'domain_bind'			=> [/*'m'=>'mobile'*/],
	// ��ֹURL���ʵ�Ӧ���б��Զ���Ӧ��ģʽ��Ч��
	'deny_app_list'			=> ['common'],
	// Ĭ��Ӧ��
	'default_app'			=> 'index',
	// Ĭ��ʱ��
	'default_timezone'		=> 'Asia/Shanghai',
	// Ĭ����֤��
	'default_validate'		=> '',

	// �쳣ҳ���ģ���ļ�
	'exception_tmpl'		=> app()->getBasePath() . 'common/view/think_exception.tpl',

	// ������ʾ��Ϣ,�ǵ���ģʽ��Ч
	'error_message'			=> 'ҳ��������Ժ����ԡ�',
	// ��ʾ������Ϣ
	'show_error_msg'		=> true,
];

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

use think\facade\Env;
global $ecms_config;
return [
    // ���ݿ�����
    'type'            => Env::get('database.type', 'mysql'),
    // ��������ַ
    'hostname'        => Env::get('database.hostname', $ecms_config['db']['dbserver']),
    // ���ݿ���
    'database'        => Env::get('database.database', $ecms_config['db']['dbname']),
    // �û���
    'username'        => Env::get('database.username', $ecms_config['db']['dbusername']),
    // ����
    'password'        => Env::get('database.password', $ecms_config['db']['dbpassword']),
    // �˿�
    'hostport'        => Env::get('database.hostport', $ecms_config['db']['dbport']),
    // ����dsn
    'dsn'             => '',
    // ���ݿ����Ӳ���
    'params'          => [],
    // ���ݿ����Ĭ�ϲ���utf8
    'charset'         => Env::get('database.charset', $ecms_config['db']['dbchar']),
    // ���ݿ��ǰ׺
    'prefix'          => Env::get('database.prefix', $ecms_config['db']['dbtbpre']),
    // ���ݿ����ģʽ
    'debug'           => Env::get('database.debug', !!$ecms_config['db']['showerror']),
    // ���ݿⲿ��ʽ:0 ����ʽ(��һ������),1 �ֲ�ʽ(���ӷ�����)
    'deploy'          => 0,
    // ���ݿ��д�Ƿ���� ����ʽ��Ч
    'rw_separate'     => false,
    // ��д����� ������������
    'master_num'      => 1,
    // ָ���ӷ��������
    'slave_no'        => '',
    // �Ƿ��ϸ����ֶ��Ƿ����
    'fields_strict'   => false,
    // �Զ�д��ʱ����ֶ�
    'auto_timestamp'  => false,
    // ʱ���ֶ�ȡ�����Ĭ��ʱ���ʽ
    'datetime_format' => false,
    // �Ƿ���Ҫ����SQL���ܷ���
    'sql_explain'     => false,
    // Builder��
    'builder'         => '',
    // Query��
    'query'           => '\\think\\db\\Query',
    // �Ƿ���Ҫ��������
    'break_reconnect' => false,
    // ���߱�ʶ�ַ���
    'break_match_str' => [],
];

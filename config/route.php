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

return [
    // PATHINFO������ ���ڼ���ģʽ
    'var_pathinfo'          => 's',
    // ����PATH_INFO��ȡ
    'pathinfo_fetch'        => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo�ָ���
    'pathinfo_depr'         => '/',
    // HTTPS�����ʶ
    'https_agent_name'      => '',
    // URLα��̬��׺
    'url_html_suffix'       => 'html',
    // URL��ͨ��ʽ���� �����Զ�����
    'url_common_param'      => true,
    // �Ƿ���·���ӳٽ���
    'url_lazy_route'        => false,
    // �Ƿ�ǿ��ʹ��·��
    'url_route_must'        => false,
    // �ϲ�·�ɹ���
    'route_rule_merge'      => false,
    // ·���Ƿ���ȫƥ��
    'route_complete_match'  => false,
    // ʹ��ע��·��
    'route_annotation'      => false,
    // �Ƿ���·�ɻ���
    'route_check_cache'     => false,
    // ·�ɻ������Ӳ���
    'route_cache_option'    => [],
    // ·�ɻ���Key
    'route_check_cache_key' => '',
    // ���ʿ�����������
    'controller_layer'      => 'controller',
    // �տ�������
    'empty_controller'      => 'Error',
    // �Ƿ�ʹ�ÿ�������׺
    'controller_suffix'     => false,
    // Ĭ�ϵ�·�ɱ�������
    'default_route_pattern' => '[\w\.]+',
    // ����������thinkphp.cn
    'url_domain_root'       => '',
    // �Ƿ��Զ�ת��URL�еĿ������Ͳ�����
    'url_convert'           => true,
    // ����������αװ����
    'var_method'            => '_method',
    // ��ajaxαװ����
    'var_ajax'              => '_ajax',
    // ��pjaxαװ����
    'var_pjax'              => '_pjax',
    // �Ƿ������󻺴� true�Զ����� ֧���������󻺴����
    'request_cache'         => false,
    // ���󻺴���Ч��
    'request_cache_expire'  => null,
    // ȫ�����󻺴��ų�����
    'request_cache_except'  => [],
    // Ĭ�Ͽ�������
    'default_controller'    => 'Index',
    // Ĭ�ϲ�����
    'default_action'        => 'index',
    // ����������׺
    'action_suffix'         => '',
    // Ĭ��JSONP��ʽ���صĴ�����
    'default_jsonp_handler' => 'jsonpReturn',
    // Ĭ��JSONP������
    'var_jsonp_handler'     => 'callback',
];

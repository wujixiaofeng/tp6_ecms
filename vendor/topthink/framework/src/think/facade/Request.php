<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\facade;

use think\Facade;

/**
 * @see \think\Request
 * @mixin \think\Request
 * @method void hook(mixed $method, mixed $callback = null) static Hook ����ע��
 * @method \think\Request create(string $uri, string $method = 'GET', array $params = [], array $cookie = [], array $files = [], array $server = [], string $content = null) static ����һ��URL����
 * @method mixed domain(string $domain = null) static ���û��ȡ��ǰ����Э�������
 * @method mixed url(mixed $url = null) static ���û��ȡ��ǰ����URL
 * @method mixed baseUrl(string $url = null) static ���û��ȡ��ǰURL
 * @method mixed baseFile(string $file = null) static ���û��ȡ��ǰִ�е��ļ�
 * @method mixed root(string $url = null) static ���û��ȡURL���ʸ���ַ
 * @method string rootUrl() static ��ȡURL���ʸ�Ŀ¼
 * @method string pathinfo() static ��ȡ��ǰ����URL��pathinfo��Ϣ����URL��׺��
 * @method string path() static ��ȡ��ǰ����URL��pathinfo��Ϣ(����URL��׺)
 * @method string ext() static ��ǰURL�ķ��ʺ�׺
 * @method float time(bool $float = false) static ��ȡ��ǰ�����ʱ��
 * @method mixed type() static ��ǰ�������Դ����
 * @method void mimeType(mixed $type, string $val = '') static ������Դ����
 * @method string method(bool $method = false) static ��ǰ����������
 * @method bool isGet() static �Ƿ�ΪGET����
 * @method bool isPost() static �Ƿ�ΪPOST����
 * @method bool isPut() static �Ƿ�ΪPUT����
 * @method bool isDelete() static �Ƿ�ΪDELTE����
 * @method bool isHead() static �Ƿ�ΪHEAD����
 * @method bool isPatch() static �Ƿ�ΪPATCH����
 * @method bool isOptions() static �Ƿ�ΪOPTIONS����
 * @method bool isCli() static �Ƿ�Ϊcli
 * @method bool isCgi() static �Ƿ�Ϊcgi
 * @method mixed param(mixed $name = '', mixed $default = null, mixed $filter = '') static ��ȡ��ǰ����Ĳ���
 * @method mixed route(mixed $name = '', mixed $default = null, mixed $filter = '') static ���û�ȡ·�ɲ���
 * @method mixed get(mixed $name = '', mixed $default = null, mixed $filter = '') static ���û�ȡGET����
 * @method mixed post(mixed $name = '', mixed $default = null, mixed $filter = '') static ���û�ȡPOST����
 * @method mixed put(mixed $name = '', mixed $default = null, mixed $filter = '') static ���û�ȡPUT����
 * @method mixed delete(mixed $name = '', mixed $default = null, mixed $filter = '') static ���û�ȡDELETE����
 * @method mixed patch(mixed $name = '', mixed $default = null, mixed $filter = '') static ���û�ȡPATCH����
 * @method mixed request(mixed $name = '', mixed $default = null, mixed $filter = '') static ��ȡrequest����
 * @method mixed session(mixed $name = '', mixed $default = null, mixed $filter = '') static ��ȡsession����
 * @method mixed cookie(mixed $name = '', mixed $default = null, mixed $filter = '') static ��ȡcookie����
 * @method mixed server(mixed $name = '', mixed $default = null, mixed $filter = '') static ��ȡserver����
 * @method mixed env(mixed $name = '', mixed $default = null, mixed $filter = '') static ��ȡ��������
 * @method mixed file(mixed $name = '') static ��ȡ�ϴ����ļ���Ϣ
 * @method mixed header(mixed $name = '', mixed $default = null) static ���û��߻�ȡ��ǰ��Header
 * @method mixed input(array $data,mixed $name = '', mixed $default = null, mixed $filter = '') static ��ȡ���� ֧�ֹ��˺�Ĭ��ֵ
 * @method mixed filter(mixed $filter = null) static ���û��ȡ��ǰ�Ĺ��˹���
 * @method mixed has(string $name, string $type = 'param', bool $checkEmpty = false) static �Ƿ����ĳ���������
 * @method mixed only(mixed $name, string $type = 'param') static ��ȡָ���Ĳ���
 * @method mixed except(mixed $name, string $type = 'param') static �ų�ָ��������ȡ
 * @method bool isSsl() static ��ǰ�Ƿ�ssl
 * @method bool isAjax(bool $ajax = false) static ��ǰ�Ƿ�Ajax����
 * @method bool isPjax(bool $pjax = false) static ��ǰ�Ƿ�Pjax����
 * @method mixed ip() static ��ȡ�ͻ���IP��ַ
 * @method bool isMobile() static ����Ƿ�ʹ���ֻ�����
 * @method string scheme() static ��ǰURL��ַ�е�scheme����
 * @method string query() static ��ǰ����URL��ַ�е�query����
 * @method string host() static ��ǰ�����host
 * @method string port() static ��ǰ����URL��ַ�е�port����
 * @method string protocol() static ��ǰ���� SERVER_PROTOCOL
 * @method string remotePort() static ��ǰ���� REMOTE_PORT
 * @method string contentType() static ��ǰ���� HTTP_CONTENT_TYPE
 * @method array dispatch(array $dispatch = null) static ���û��߻�ȡ��ǰ����ĵ�����Ϣ
 * @method mixed app() static ��ȡ��ǰ��Ӧ����
 * @method mixed controller(bool $convert = false) static ��ȡ��ǰ�Ŀ�������
 * @method mixed action(bool $convert = false) static ��ȡ��ǰ�Ĳ�����
 * @method mixed setApp(string $app = null) static ���õ�ǰ��Ӧ����
 * @method mixed setController(string $controller) static ���õ�ǰ�Ŀ�������
 * @method mixed setAction(string $action) static ���õ�ǰ�Ĳ�����
 * @method string getContent() static ���û��߻�ȡ��ǰ�����content
 * @method string getInput() static ��ȡ��ǰ�����php://input
 * @method string buildToken(string $name = '__token__', mixed $type = 'md5') static ������������
 * @method string checkToken(string $name = '__token__', array $data) static �����������
 */
class Request extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'request';
    }
}

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
declare (strict_types = 1);

namespace think;

use think\route\Dispatch;
use think\route\Rule;

/**
 * ���������
 */
class Request
{
    /**
     * ����PATH_INFO��ȡ
     * @var array
     */
    protected $pathinfoFetch = ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'];

    /**
     * PATHINFO������ ���ڼ���ģʽ
     * @var string
     */
    protected $varPathinfo = 's';

    /**
     * ��������
     * @var string
     */
    protected $varMethod = '_method';

    /**
     * ��ajaxαװ����
     * @var string
     */
    protected $varAjax = '_ajax';

    /**
     * ��pjaxαװ����
     * @var string
     */
    protected $varPjax = '_pjax';

    /**
     * ������
     * @var string
     */
    protected $rootDomain = '';

    /**
     * HTTPS�����ʶ
     * @var string
     */
    protected $httpsAgentName = '';

    /**
     * ǰ�˴��������IP
     * @var array
     */
    protected $proxyServerIp = [];

    /**
     * ǰ�˴����������ʵIPͷ
     * @var array
     */
    protected $proxyServerIpHeader = ['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP'];

    /**
     * ��������
     * @var string
     */
    protected $method;

    /**
     * ��������Э�鼰�˿ڣ�
     * @var string
     */
    protected $domain;

    /**
     * HOST�����˿ڣ�
     * @var string
     */
    protected $host;

    /**
     * ������
     * @var string
     */
    protected $subDomain;

    /**
     * ������
     * @var string
     */
    protected $panDomain;

    /**
     * ��ǰURL��ַ
     * @var string
     */
    protected $url;

    /**
     * ����URL
     * @var string
     */
    protected $baseUrl;

    /**
     * ��ǰִ�е��ļ�
     * @var string
     */
    protected $baseFile;

    /**
     * ���ʵ�ROOT��ַ
     * @var string
     */
    protected $root;

    /**
     * pathinfo
     * @var string
     */
    protected $pathinfo;

    /**
     * pathinfo��������׺��
     * @var string
     */
    protected $path;

    /**
     * ��ǰ�����IP��ַ
     * @var string
     */
    protected $realIP;

    /**
     * ��ǰ������Ϣ
     * @var Dispatch
     */
    protected $dispatch;

    /**
     * ��ǰӦ����
     * @var string
     */
    protected $app;

    /**
     * ��ǰ��������
     * @var string
     */
    protected $controller;

    /**
     * ��ǰ������
     * @var string
     */
    protected $action;

    /**
     * ��ǰ�������
     * @var array
     */
    protected $param = [];

    /**
     * ��ǰGET����
     * @var array
     */
    protected $get = [];

    /**
     * ��ǰPOST����
     * @var array
     */
    protected $post = [];

    /**
     * ��ǰREQUEST����
     * @var array
     */
    protected $request = [];

    /**
     * ��ǰ·�ɶ���
     * @var Rule
     */
    protected $rule;

    /**
     * ��ǰROUTE����
     * @var array
     */
    protected $route = [];

    /**
     * �м�����ݵĲ���
     * @var array
     */
    protected $middleware = [];

    /**
     * ��ǰPUT����
     * @var array
     */
    protected $put;

    /**
     * SESSION����
     * @var Session
     */
    protected $session;

    /**
     * COOKIE����
     * @var array
     */
    protected $cookie = [];

    /**
     * ENV����
     * @var Env
     */
    protected $env;

    /**
     * ��ǰSERVER����
     * @var array
     */
    protected $server = [];

    /**
     * ��ǰFILE����
     * @var array
     */
    protected $file = [];

    /**
     * ��ǰHEADER����
     * @var array
     */
    protected $header = [];

    /**
     * ��Դ���Ͷ���
     * @var array
     */
    protected $mimeType = [
        'xml'   => 'application/xml,text/xml,application/x-xml',
        'json'  => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js'    => 'text/javascript,application/javascript,application/x-javascript',
        'css'   => 'text/css',
        'rss'   => 'application/rss+xml',
        'yaml'  => 'application/x-yaml,text/yaml',
        'atom'  => 'application/atom+xml',
        'pdf'   => 'application/pdf',
        'text'  => 'text/plain',
        'image' => 'image/png,image/jpg,image/jpeg,image/pjpeg,image/gif,image/webp,image/*',
        'csv'   => 'text/csv',
        'html'  => 'text/html,application/xhtml+xml,*/*',
    ];

    /**
     * ��ǰ��������
     * @var string
     */
    protected $content;

    /**
     * ȫ�ֹ��˹���
     * @var array
     */
    protected $filter;

    /**
     * php://input����
     * @var string
     */
    // php://input
    protected $input;

    /**
     * ���󻺴�
     * @var array
     */
    protected $cache;

    /**
     * �����Ƿ���
     * @var bool
     */
    protected $isCheckCache;

    /**
     * ����ȫKey
     * @var string
     */
    protected $secureKey;

    /**
     * �Ƿ�ϲ�Param
     * @var bool
     */
    protected $mergeParam = false;

    /**
     * �ܹ�����
     * @access public
     */
    public function __construct()
    {
        // ���� php://input
        $this->input = file_get_contents('php://input');
    }

    public static function __make(App $app)
    {
        $request = new static();

        $request->server  = $_SERVER;
        $request->env     = $app->env;
        $request->get     = $_GET;
        $request->post    = $_POST ?: $request->getInputData($request->input);
        $request->put     = $request->getInputData($request->input);
        $request->request = $_REQUEST;
        $request->cookie  = $_COOKIE;
        $request->file    = $_FILES ?? [];

        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = $result;
        } else {
            $header = [];
            $server = $_SERVER;
            foreach ($server as $key => $val) {
                if (0 === strpos($key, 'HTTP_')) {
                    $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
        }

        $request->header = array_change_key_case($header);

        return $request;
    }

    /**
     * ���õ�ǰ����Э�������
     * @access public
     * @param  string $domain ����
     * @return $this
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * ��ȡ��ǰ����Э�������
     * @access public
     * @param  bool $port �Ƿ���Ҫȥ���˿ں�
     * @return string
     */
    public function domain(bool $port = false): string
    {
        return $this->scheme() . '://' . $this->host($port);
    }

    /**
     * ��ȡ��ǰ������
     * @access public
     * @return string
     */
    public function rootDomain(): string
    {
        $root = $this->rootDomain;

        if (!$root) {
            $item  = explode('.', $this->host());
            $count = count($item);
            $root  = $count > 1 ? $item[$count - 2] . '.' . $item[$count - 1] : $item[0];
        }

        return $root;
    }

    /**
     * ���õ�ǰ��������ֵ
     * @access public
     * @param  string $domain ����
     * @return $this
     */
    public function setSubDomain(string $domain)
    {
        $this->subDomain = $domain;
        return $this;
    }

    /**
     * ��ȡ��ǰ������
     * @access public
     * @return string
     */
    public function subDomain(): string
    {
        if (is_null($this->subDomain)) {
            // ��ȡ��ǰ������
            $rootDomain = $this->rootDomain();

            if ($rootDomain) {
                $this->subDomain = rtrim(stristr($this->host(), $rootDomain, true), '.');
            } else {
                $this->subDomain = '';
            }
        }

        return $this->subDomain;
    }

    /**
     * ���õ�ǰ��������ֵ
     * @access public
     * @param  string $domain ����
     * @return $this
     */
    public function setPanDomain(string $domain)
    {
        $this->panDomain = $domain;
        return $this;
    }

    /**
     * ��ȡ��ǰ��������ֵ
     * @access public
     * @return string
     */
    public function panDomain(): string
    {
        return $this->panDomain ?: '';
    }

    /**
     * ���õ�ǰ����URL ����QUERY_STRING
     * @access public
     * @param  string $url URL��ַ
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * ��ȡ��ǰ����URL ����QUERY_STRING
     * @access public
     * @param  bool $complete �Ƿ������������
     * @return string
     */
    public function url(bool $complete = false): string
    {
        if ($this->url) {
            $url = $this->url;
        } elseif ($this->server('HTTP_X_REWRITE_URL')) {
            $url = $this->server('HTTP_X_REWRITE_URL');
        } elseif ($this->server('REQUEST_URI')) {
            $url = $this->server('REQUEST_URI');
        } elseif ($this->server('ORIG_PATH_INFO')) {
            $url = $this->server('ORIG_PATH_INFO') . (!empty($this->server('QUERY_STRING')) ? '?' . $this->server('QUERY_STRING') : '');
        } elseif (isset($_SERVER['argv'][1])) {
            $url = $_SERVER['argv'][1];
        } else {
            $url = '';
        }

        return $complete ? $this->domain() . $url : $url;
    }

    /**
     * ���õ�ǰURL ����QUERY_STRING
     * @access public
     * @param  string $url URL��ַ
     * @return $this
     */
    public function setBaseUrl(string $url)
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * ��ȡ��ǰURL ����QUERY_STRING
     * @access public
     * @param  bool $complete �Ƿ������������
     * @return string
     */
    public function baseUrl(bool $complete = false): string
    {
        if (!$this->baseUrl) {
            $str           = $this->url();
            $this->baseUrl = strpos($str, '?') ? strstr($str, '?', true) : $str;
        }

        return $complete ? $this->domain() . $this->baseUrl : $this->baseUrl;
    }

    /**
     * ��ȡ��ǰִ�е��ļ� SCRIPT_NAME
     * @access public
     * @param  bool $complete �Ƿ������������
     * @return string
     */
    public function baseFile(bool $complete = false): string
    {
        if (!$this->baseFile) {
            $url = '';
            if (!$this->isCli()) {
                $script_name = basename($this->server('SCRIPT_FILENAME'));
                if (basename($this->server('SCRIPT_NAME')) === $script_name) {
                    $url = $this->server('SCRIPT_NAME');
                } elseif (basename($this->server('PHP_SELF')) === $script_name) {
                    $url = $this->server('PHP_SELF');
                } elseif (basename($this->server('ORIG_SCRIPT_NAME')) === $script_name) {
                    $url = $this->server('ORIG_SCRIPT_NAME');
                } elseif (($pos = strpos($this->server('PHP_SELF'), '/' . $script_name)) !== false) {
                    $url = substr($this->server('SCRIPT_NAME'), 0, $pos) . '/' . $script_name;
                } elseif ($this->server('DOCUMENT_ROOT') && strpos($this->server('SCRIPT_FILENAME'), $this->server('DOCUMENT_ROOT')) === 0) {
                    $url = str_replace('\\', '/', str_replace($this->server('DOCUMENT_ROOT'), '', $this->server('SCRIPT_FILENAME')));
                }
            }
            $this->baseFile = $url;
        }

        return $complete ? $this->domain() . $this->baseFile : $this->baseFile;
    }

    /**
     * ����URL���ʸ���ַ
     * @access public
     * @param  string $url URL��ַ
     * @return $this
     */
    public function setRoot(string $url)
    {
        $this->root = $url;
        return $this;
    }

    /**
     * ��ȡURL���ʸ���ַ
     * @access public
     * @param  bool $complete �Ƿ������������
     * @return string
     */
    public function root(bool $complete = false): string
    {
        if (!$this->root) {
            $file = $this->baseFile();
            if ($file && 0 !== strpos($this->url(), $file)) {
                $file = str_replace('\\', '/', dirname($file));
            }
            $this->root = rtrim($file, '/');
        }

        return $complete ? $this->domain() . $this->root : $this->root;
    }

    /**
     * ��ȡURL���ʸ�Ŀ¼
     * @access public
     * @return string
     */
    public function rootUrl(): string
    {
        $base = $this->root();
        $root = strpos($base, '.') ? ltrim(dirname($base), DIRECTORY_SEPARATOR) : $base;

        if ('' != $root) {
            $root = '/' . ltrim($root, '/');
        }

        return $root;
    }

    /**
     * ���õ�ǰ�����pathinfo
     * @access public
     * @param  string $pathinfo
     * @return $this
     */
    public function setPathinfo(string $pathinfo)
    {
        $this->pathinfo = $pathinfo;
        return $this;
    }

    /**
     * ��ȡ��ǰ����URL��pathinfo��Ϣ����URL��׺��
     * @access public
     * @return string
     */
    public function pathinfo(): string
    {
        if (is_null($this->pathinfo)) {
            if (isset($_GET[$this->varPathinfo])) {
                // �ж�URL�����Ƿ��м���ģʽ����
                $pathinfo = $_GET[$this->varPathinfo];
                unset($_GET[$this->varPathinfo]);
            } elseif ($this->server('PATH_INFO')) {
                $pathinfo = $this->server('PATH_INFO');
            } elseif ('cli-server' == PHP_SAPI) {
                $pathinfo = strpos($this->server('REQUEST_URI'), '?') ? strstr($this->server('REQUEST_URI'), '?', true) : $this->server('REQUEST_URI');
            }

            // ����PATHINFO��Ϣ
            if (!isset($pathinfo)) {
                foreach ($this->pathinfoFetch as $type) {
                    if ($this->server($type)) {
                        $pathinfo = (0 === strpos($this->server($type), $this->server('SCRIPT_NAME'))) ?
                        substr($this->server($type), strlen($this->server('SCRIPT_NAME'))) : $this->server($type);
                        break;
                    }
                }
            }
            $this->pathinfo = empty($pathinfo) || '/' == $pathinfo ? '' : ltrim($pathinfo, '/');
        }

        return $this->pathinfo;
    }

    /**
     * ��ǰURL�ķ��ʺ�׺
     * @access public
     * @return string
     */
    public function ext(): string
    {
        return pathinfo($this->pathinfo(), PATHINFO_EXTENSION);
    }

    /**
     * ��ȡ��ǰ�����ʱ��
     * @access public
     * @param  bool $float �Ƿ�ʹ�ø�������
     * @return integer|float
     */
    public function time(bool $float = false)
    {
        return $float ? $this->server('REQUEST_TIME_FLOAT') : $this->server('REQUEST_TIME');
    }

    /**
     * ��ǰ�������Դ����
     * @access public
     * @return string
     */
    public function type(): string
    {
        $accept = $this->server('HTTP_ACCEPT');

        if (empty($accept)) {
            return '';
        }

        foreach ($this->mimeType as $key => $val) {
            $array = explode(',', $val);
            foreach ($array as $k => $v) {
                if (stristr($accept, $v)) {
                    return $key;
                }
            }
        }

        return '';
    }

    /**
     * ������Դ����
     * @access public
     * @param  string|array $type ��Դ������
     * @param  string       $val ��Դ����
     * @return void
     */
    public function mimeType($type, $val = ''): void
    {
        if (is_array($type)) {
            $this->mimeType = array_merge($this->mimeType, $type);
        } else {
            $this->mimeType[$type] = $val;
        }
    }

    /**
     * ������������
     * @access public
     * @param  string $method ��������
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * ��ǰ����������
     * @access public
     * @param  bool $origin �Ƿ��ȡԭʼ��������
     * @return string
     */
    public function method(bool $origin = false): string
    {
        if ($origin) {
            // ��ȡԭʼ��������
            return $this->server('REQUEST_METHOD') ?: 'GET';
        } elseif (!$this->method) {
            if (isset($this->post[$this->varMethod])) {
                $method = strtolower($this->post[$this->varMethod]);
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    $this->method    = strtoupper($method);
                    $this->{$method} = $this->post;
                } else {
                    $this->method = 'POST';
                }
                unset($this->post[$this->varMethod]);
            } elseif ($this->server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
                $this->method = strtoupper($this->server('HTTP_X_HTTP_METHOD_OVERRIDE'));
            } else {
                $this->method = $this->server('REQUEST_METHOD') ?: 'GET';
            }
        }

        return $this->method;
    }

    /**
     * �Ƿ�ΪGET����
     * @access public
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method() == 'GET';
    }

    /**
     * �Ƿ�ΪPOST����
     * @access public
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method() == 'POST';
    }

    /**
     * �Ƿ�ΪPUT����
     * @access public
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->method() == 'PUT';
    }

    /**
     * �Ƿ�ΪDELTE����
     * @access public
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->method() == 'DELETE';
    }

    /**
     * �Ƿ�ΪHEAD����
     * @access public
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->method() == 'HEAD';
    }

    /**
     * �Ƿ�ΪPATCH����
     * @access public
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->method() == 'PATCH';
    }

    /**
     * �Ƿ�ΪOPTIONS����
     * @access public
     * @return bool
     */
    public function isOptions(): bool
    {
        return $this->method() == 'OPTIONS';
    }

    /**
     * �Ƿ�Ϊcli
     * @access public
     * @return bool
     */
    public function isCli(): bool
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * �Ƿ�Ϊcgi
     * @access public
     * @return bool
     */
    public function isCgi(): bool
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    /**
     * ��ȡ��ǰ����Ĳ���
     * @access public
     * @param  string|array $name ������
     * @param  mixed        $default Ĭ��ֵ
     * @param  string|array $filter ���˷���
     * @return mixed
     */
    public function param($name = '', $default = null, $filter = '')
    {
        if (empty($this->mergeParam)) {
            $method = $this->method(true);

            // �Զ���ȡ�������
            switch ($method) {
                case 'POST':
                    $vars = $this->post(false);
                    break;
                case 'PUT':
                case 'DELETE':
                case 'PATCH':
                    $vars = $this->put(false);
                    break;
                default:
                    $vars = [];
            }

            // ��ǰ���������URL��ַ�еĲ����ϲ�
            $this->param = array_merge($this->param, $this->get(false), $vars, $this->route(false));

            $this->mergeParam = true;
        }

        if (is_array($name)) {
            return $this->only($name, $this->param, $filter);
        }

        return $this->input($this->param, $name, $default, $filter);
    }

    /**
     * ����·�ɱ���
     * @access public
     * @param  Rule $rule ·�ɶ���
     * @return $this
     */
    public function setRule(Rule $rule)
    {
        $this->rule = $rule;
        return $this;
    }

    /**
     * ��ȡ��ǰ·�ɶ���
     * @access public
     * @return Rule|null
     */
    public function rule()
    {
        return $this->rule;
    }

    /**
     * ����·�ɱ���
     * @access public
     * @param  array $route ·�ɱ���
     * @return $this
     */
    public function setRoute(array $route)
    {
        $this->route = array_merge($this->route, $route);
        return $this;
    }

    /**
     * ��ȡ·�ɲ���
     * @access public
     * @param  mixed        $name ������
     * @param  mixed        $default Ĭ��ֵ
     * @param  string|array $filter ���˷���
     * @return mixed
     */
    public function route($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->route, $filter);
        }

        return $this->input($this->route, $name, $default, $filter);
    }

    /**
     * ��ȡGET����
     * @access public
     * @param  mixed        $name ������
     * @param  mixed        $default Ĭ��ֵ
     * @param  string|array $filter ���˷���
     * @return mixed
     */
    public function get($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->get, $filter);
        }

        return $this->input($this->get, $name, $default, $filter);
    }

    /**
     * ��ȡ�м�����ݵĲ���
     * @access public
     * @param  mixed $name ������
     * @param  mixed $default Ĭ��ֵ
     * @return mixed
     */
    public function middleware($name, $default = null)
    {
        return $this->middleware[$name] ?? $default;
    }

    /**
     * ��ȡPOST����
     * @access public
     * @param  mixed        $name ������
     * @param  mixed        $default Ĭ��ֵ
     * @param  string|array $filter ���˷���
     * @return mixed
     */
    public function post($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->post, $filter);
        }

        return $this->input($this->post, $name, $default, $filter);
    }

    /**
     * ��ȡPUT����
     * @access public
     * @param  mixed        $name ������
     * @param  mixed        $default Ĭ��ֵ
     * @param  string|array $filter ���˷���
     * @return mixed
     */
    public function put($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->put, $filter);
        }

        return $this->input($this->put, $name, $default, $filter);
    }

    protected function getInputData($content)
    {
        if ($this->isJson()) {
            return (array) json_decode($content, true);
        } elseif (strpos($content, '=')) {
            parse_str($content, $data);
            return $data;
        }

        return [];
    }

    /**
     * ���û�ȡDELETE����
     * @access public
     * @param  mixed        $name ������
     * @param  mixed        $default Ĭ��ֵ
     * @param  string|array $filter ���˷���
     * @return mixed
     */
    public function delete($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }

    /**
     * ���û�ȡPATCH����
     * @access public
     * @param  mixed        $name ������
     * @param  mixed        $default Ĭ��ֵ
     * @param  string|array $filter ���˷���
     * @return mixed
     */
    public function patch($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }

    /**
     * ��ȡrequest����
     * @access public
     * @param  mixed        $name ��������
     * @param  mixed        $default Ĭ��ֵ
     * @param  string|array $filter ���˷���
     * @return mixed
     */
    public function request($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->request, $filter);
        }

        return $this->input($this->request, $name, $default, $filter);
    }

    /**
     * ��ȡ��������
     * @access public
     * @param  string $name ��������
     * @param  string $default Ĭ��ֵ
     * @return mixed
     */
    public function env(string $name = '', string $default = null)
    {
        if (empty($name)) {
            return $this->env->get();
        } else {
            $name = strtoupper($name);
        }

        return $this->env->get($name, $default);
    }

    /**
     * ��ȡsession����
     * @access public
     * @param  string $name ��������
     * @param  string $default Ĭ��ֵ
     * @return mixed
     */
    public function session(string $name = '', $default = null)
    {
        if ('' === $name) {
            return $this->session->get();
        }

        return $this->getData($this->session->get(), $name, $default);
    }

    /**
     * ��ȡcookie����
     * @access public
     * @param  mixed        $name ��������
     * @param  string       $default Ĭ��ֵ
     * @param  string|array $filter ���˷���
     * @return mixed
     */
    public function cookie(string $name = '', $default = null, $filter = '')
    {
        if (!empty($name)) {
            $data = $this->getData($this->cookie, $name, $default);
        } else {
            $data = $this->cookie;
        }

        // ����������
        $filter = $this->getFilter($filter, $default);

        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
            reset($data);
        } else {
            $this->filterValue($data, $name, $filter);
        }

        return $data;
    }

    /**
     * ��ȡserver����
     * @access public
     * @param  string $name ��������
     * @param  string $default Ĭ��ֵ
     * @return mixed
     */
    public function server(string $name = '', string $default = '')
    {
        if (empty($name)) {
            return $this->server;
        } else {
            $name = strtoupper($name);
        }

        return $this->server[$name] ?? $default;
    }

    /**
     * ��ȡ�ϴ����ļ���Ϣ
     * @access public
     * @param  string $name ����
     * @return null|array|\think\File
     */
    public function file(string $name = '')
    {
        $files = $this->file;
        if (!empty($files)) {

            if (strpos($name, '.')) {
                list($name, $sub) = explode('.', $name);
            }

            // �����ϴ��ļ�
            $array = $this->dealUploadFile($files, $name);

            if ('' === $name) {
                // ��ȡȫ���ļ�
                return $array;
            } elseif (isset($sub) && isset($array[$name][$sub])) {
                return $array[$name][$sub];
            } elseif (isset($array[$name])) {
                return $array[$name];
            }
        }

        return;
    }

    protected function dealUploadFile($files, $name)
    {
        $array = [];
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $item  = [];
                $keys  = array_keys($file);
                $count = count($file['name']);

                for ($i = 0; $i < $count; $i++) {
                    if ($file['error'][$i] > 0) {
                        if ($name == $key) {
                            $this->throwUploadFileError($file['error'][$i]);
                        } else {
                            continue;
                        }
                    }

                    $temp['key'] = $key;

                    foreach ($keys as $_key) {
                        $temp[$_key] = $file[$_key][$i];
                    }

                    $item[] = (new File($temp['tmp_name']))->setUploadInfo($temp);
                }

                $array[$key] = $item;
            } else {
                if ($file instanceof File) {
                    $array[$key] = $file;
                } else {
                    if ($file['error'] > 0) {
                        if ($key == $name) {
                            $this->throwUploadFileError($file['error']);
                        } else {
                            continue;
                        }
                    }

                    $array[$key] = (new File($file['tmp_name']))->setUploadInfo($file);
                }
            }
        }

        return $array;
    }

    protected function throwUploadFileError($error)
    {
        static $fileUploadErrors = [
            1 => 'upload File size exceeds the maximum value',
            2 => 'upload File size exceeds the maximum value',
            3 => 'only the portion of file is uploaded',
            4 => 'no file to uploaded',
            6 => 'upload temp dir not found',
            7 => 'file write error',
        ];

        $msg = $fileUploadErrors[$error];
        throw new Exception($msg);
    }

    /**
     * ���û��߻�ȡ��ǰ��Header
     * @access public
     * @param  string $name header����
     * @param  string $default Ĭ��ֵ
     * @return string|array
     */
    public function header(string $name = '', string $default = null)
    {
        if ('' === $name) {
            return $this->header;
        }

        $name = str_replace('_', '-', strtolower($name));

        return $this->header[$name] ?? $default;
    }

    /**
     * ��ȡ���� ֧�ֹ��˺�Ĭ��ֵ
     * @access public
     * @param  array        $data ����Դ
     * @param  string|false $name �ֶ���
     * @param  mixed        $default Ĭ��ֵ
     * @param  string|array $filter ���˺���
     * @return mixed
     */
    public function input(array $data = [], $name = '', $default = null, $filter = '')
    {
        if (false === $name) {
            // ��ȡԭʼ����
            return $data;
        }

        $name = (string) $name;
        if ('' != $name) {
            // ����name
            if (strpos($name, '/')) {
                list($name, $type) = explode('/', $name);
            }

            $data = $this->getData($data, $name);

            if (is_null($data)) {
                return $default;
            }

            if (is_object($data)) {
                return $data;
            }
        }

        $data = $this->filterData($data, $filter, $name, $default);

        if (isset($type) && $data !== $default) {
            // ǿ������ת��
            $this->typeCast($data, $type);
        }

        return $data;
    }

    protected function filterData($data, $filter, $name, $default)
    {
        // ����������
        $filter = $this->getFilter($filter, $default);

        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
            reset($data);
        } else {
            $this->filterValue($data, $name, $filter);
        }

        return $data;
    }

    /**
     * ǿ������ת��
     * @access public
     * @param  string $data
     * @param  string $type
     * @return mixed
     */
    private function typeCast(&$data, string $type)
    {
        switch (strtolower($type)) {
            // ����
            case 'a':
                $data = (array) $data;
                break;
            // ����
            case 'd':
                $data = (int) $data;
                break;
            // ����
            case 'f':
                $data = (float) $data;
                break;
            // ����
            case 'b':
                $data = (boolean) $data;
                break;
            // �ַ���
            case 's':
                if (is_scalar($data)) {
                    $data = (string) $data;
                } else {
                    throw new \InvalidArgumentException('variable type error��' . gettype($data));
                }
                break;
        }
    }

    /**
     * ��ȡ����
     * @access public
     * @param  array  $data ����Դ
     * @param  string $name �ֶ���
     * @param  mixed  $default Ĭ��ֵ
     * @return mixed
     */
    protected function getData(array $data, string $name, $default = null)
    {
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                return $default;
            }
        }

        return $data;
    }

    /**
     * ���û��ȡ��ǰ�Ĺ��˹���
     * @access public
     * @param  mixed $filter ���˹���
     * @return mixed
     */
    public function filter($filter = null)
    {
        if (is_null($filter)) {
            return $this->filter;
        }

        $this->filter = $filter;

        return $this;
    }

    protected function getFilter($filter, $default)
    {
        if (is_null($filter)) {
            $filter = [];
        } else {
            $filter = $filter ?: $this->filter;
            if (is_string($filter) && false === strpos($filter, '/')) {
                $filter = explode(',', $filter);
            } else {
                $filter = (array) $filter;
            }
        }

        $filter[] = $default;

        return $filter;
    }

    /**
     * �ݹ���˸�����ֵ
     * @access public
     * @param  mixed $value ��ֵ
     * @param  mixed $key ����
     * @param  array $filters ���˷���+Ĭ��ֵ
     * @return mixed
     */
    private function filterValue(&$value, $key, $filters)
    {
        $default = array_pop($filters);

        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                // ���ú������߷�������
                $value = call_user_func($filter, $value);
            } elseif (is_scalar($value)) {
                if (is_string($filter) && false !== strpos($filter, '/')) {
                    // �������
                    if (!preg_match($filter, $value)) {
                        // ƥ�䲻�ɹ�����Ĭ��ֵ
                        $value = $default;
                        break;
                    }
                } elseif (!empty($filter)) {
                    // filter����������ʱ, ��ʹ��filter_var���й���
                    // filterΪ������ֵʱ, ����filter_idȡ�ù���id
                    $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $value) {
                        $value = $default;
                        break;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * �Ƿ����ĳ���������
     * @access public
     * @param  string $name ������
     * @param  string $type ��������
     * @param  bool   $checkEmpty �Ƿ����ֵ
     * @return bool
     */
    public function has(string $name, string $type = 'param', bool $checkEmpty = false): bool
    {
        if (!in_array($type, ['param', 'get', 'post', 'put', 'patch', 'route', 'delete', 'cookie', 'session', 'env', 'request', 'server', 'header', 'file'])) {
            return false;
        }

        $param = empty($this->$type) ? $this->$type() : $this->$type;

        if (is_object($param)) {
            return $param->has($name);
        }

        // ��.��ֳɶ�ά��������ж�
        foreach (explode('.', $name) as $val) {
            if (isset($param[$val])) {
                $param = $param[$val];
            } else {
                return false;
            }
        }

        return ($checkEmpty && '' === $param) ? false : true;
    }

    /**
     * ��ȡָ���Ĳ���
     * @access public
     * @param  array        $name ������
     * @param  mixed        $data ���ݻ��߱�������
     * @param  string|array $filter ���˷���
     * @return array
     */
    public function only(array $name, $data = 'param', $filter = ''): array
    {
        $data = is_array($data) ? $data : $this->$data();

        $item = [];
        foreach ($name as $key => $val) {

            if (is_int($key)) {
                $default = null;
                $key     = $val;
                if (!isset($data[$key])) {
                    continue;
                }
            } else {
                $default = $val;
            }

            $item[$key] = $this->filterData($data[$key] ?? $default, $filter, $key, $default);
        }

        return $item;
    }

    /**
     * �ų�ָ��������ȡ
     * @access public
     * @param  array  $name ������
     * @param  string $type ��������
     * @return mixed
     */
    public function except(array $name, string $type = 'param'): array
    {
        $param = $this->$type();

        foreach ($name as $key) {
            if (isset($param[$key])) {
                unset($param[$key]);
            }
        }

        return $param;
    }

    /**
     * ��ǰ�Ƿ�ssl
     * @access public
     * @return bool
     */
    public function isSsl(): bool
    {
        if ($this->server('HTTPS') && ('1' == $this->server('HTTPS') || 'on' == strtolower($this->server('HTTPS')))) {
            return true;
        } elseif ('https' == $this->server('REQUEST_SCHEME')) {
            return true;
        } elseif ('443' == $this->server('SERVER_PORT')) {
            return true;
        } elseif ('https' == $this->server('HTTP_X_FORWARDED_PROTO')) {
            return true;
        } elseif ($this->httpsAgentName && $this->server($this->httpsAgentName)) {
            return true;
        }

        return false;
    }

    /**
     * ��ǰ�Ƿ�JSON����
     * @access public
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->contentType();
        $acceptType  = $this->type();

        return false !== strpos($contentType, 'json') || false !== strpos($acceptType, 'json');
    }

    /**
     * ��ǰ�Ƿ�Ajax����
     * @access public
     * @param  bool $ajax true ��ȡԭʼajax����
     * @return bool
     */
    public function isAjax(bool $ajax = false): bool
    {
        $value  = $this->server('HTTP_X_REQUESTED_WITH');
        $result = $value && 'xmlhttprequest' == strtolower($value) ? true : false;

        if (true === $ajax) {
            return $result;
        }

        return $this->param($this->varAjax) ? true : $result;
    }

    /**
     * ��ǰ�Ƿ�Pjax����
     * @access public
     * @param  bool $pjax true ��ȡԭʼpjax����
     * @return bool
     */
    public function isPjax(bool $pjax = false): bool
    {
        $result = !is_null($this->server('HTTP_X_PJAX')) ? true : false;

        if (true === $pjax) {
            return $result;
        }

        return $this->param($this->varPjax) ? true : $result;
    }

    /**
     * ��ȡ�ͻ���IP��ַ
     * @access public
     * @return string
     */
    public function ip(): string
    {
        if (!empty($this->realIP)) {
            return $this->realIP;
        }

        $this->realIP = $this->server('REMOTE_ADDR', '');

        // ���ָ����ǰ�˴��������IP�Լ���ᷢ�͵�IPͷ
        // ���Ի�ȡǰ�˴�����������͹�������ʵIP
        $proxyIp       = $this->proxyServerIp;
        $proxyIpHeader = $this->proxyServerIpHeader;

        if (count($proxyIp) > 0 && count($proxyIpHeader) > 0) {
            // ��ָ����HTTPͷ�����γ��Ի�ȡIP��ַ
            // ֱ����ȡ��һ���Ϸ���IP��ַ
            foreach ($proxyIpHeader as $header) {
                $tempIP = $this->server($header);

                if (empty($tempIP)) {
                    continue;
                }

                $tempIP = trim(explode(',', $tempIP)[0]);

                if (!$this->isValidIP($tempIP)) {
                    $tempIP = null;
                } else {
                    break;
                }
            }

            // tempIP��Ϊ�գ�˵����ȡ����һ��IP��ַ
            // ��ʱ���Ǽ�� REMOTE_ADDR �ǲ���ָ����ǰ�˴��������֮һ
            // ����ǵĻ�˵���� IPͷ ����ǰ�˴�����������õ�
            // ��������αװ��
            if ($tempIP) {
                $realIPBin = $this->ip2bin($this->realIP);

                foreach ($proxyIp as $ip) {
                    $serverIPElements = explode('/', $ip);
                    $serverIP         = $serverIPElements[0];
                    $serverIPPrefix   = $serverIPElements[1] ?? 128;
                    $serverIPBin      = $this->ip2bin($serverIP);

                    // IP���Ͳ���
                    if (strlen($realIPBin) !== strlen($serverIPBin)) {
                        continue;
                    }

                    if (strncmp($realIPBin, $serverIPBin, (int) $serverIPPrefix) === 0) {
                        $this->realIP = $tempIP;
                        break;
                    }
                }
            }
        }

        if (!$this->isValidIP($this->realIP)) {
            $this->realIP = '0.0.0.0';
        }

        return $this->realIP;
    }

    /**
     * ����Ƿ��ǺϷ���IP��ַ
     *
     * @param string $ip   IP��ַ
     * @param string $type IP��ַ���� (ipv4, ipv6)
     *
     * @return boolean
     */
    public function isValidIP(string $ip, string $type = ''): bool
    {
        switch (strtolower($type)) {
            case 'ipv4':
                $flag = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $flag = FILTER_FLAG_IPV6;
                break;
            default:
                $flag = null;
                break;
        }

        return boolval(filter_var($ip, FILTER_VALIDATE_IP, $flag));
    }

    /**
     * ��IP��ַת��Ϊ�������ַ���
     *
     * @param string $ip
     *
     * @return string
     */
    public function ip2bin(string $ip): string
    {
        if ($this->isValidIP($ip, 'ipv6')) {
            $IPHex = str_split(bin2hex(inet_pton($ip)), 4);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = intval($value, 16);
            }
            $IPBin = vsprintf('%016b%016b%016b%016b%016b%016b%016b%016b', $IPHex);
        } else {
            $IPHex = str_split(bin2hex(inet_pton($ip)), 2);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = intval($value, 16);
            }
            $IPBin = vsprintf('%08b%08b%08b%08b', $IPHex);
        }

        return $IPBin;
    }

    /**
     * ����Ƿ�ʹ���ֻ�����
     * @access public
     * @return bool
     */
    public function isMobile(): bool
    {
        if ($this->server('HTTP_VIA') && stristr($this->server('HTTP_VIA'), "wap")) {
            return true;
        } elseif ($this->server('HTTP_ACCEPT') && strpos(strtoupper($this->server('HTTP_ACCEPT')), "VND.WAP.WML")) {
            return true;
        } elseif ($this->server('HTTP_X_WAP_PROFILE') || $this->server('HTTP_PROFILE')) {
            return true;
        } elseif ($this->server('HTTP_USER_AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $this->server('HTTP_USER_AGENT'))) {
            return true;
        }

        return false;
    }

    /**
     * ��ǰURL��ַ�е�scheme����
     * @access public
     * @return string
     */
    public function scheme(): string
    {
        return $this->isSsl() ? 'https' : 'http';
    }

    /**
     * ��ǰ����URL��ַ�е�query����
     * @access public
     * @return string
     */
    public function query(): string
    {
        return $this->server('QUERY_STRING', '');
    }

    /**
     * ���õ�ǰ�����host�������˿ڣ�
     * @access public
     * @param  string $host �����������˿ڣ�
     * @return $this
     */
    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * ��ǰ�����host
     * @access public
     * @param bool $strict  true ������ȡHOST
     * @return string
     */
    public function host(bool $strict = false): string
    {
        if ($this->host) {
            $host = $this->host;
        } else {
            $host = strval($this->server('HTTP_X_REAL_HOST') ?: $this->server('HTTP_HOST'));
        }

        return true === $strict && strpos($host, ':') ? strstr($host, ':', true) : $host;
    }

    /**
     * ��ǰ����URL��ַ�е�port����
     * @access public
     * @return string
     */
    public function port(): string
    {
        return $this->server('SERVER_PORT', '');
    }

    /**
     * ��ǰ���� SERVER_PROTOCOL
     * @access public
     * @return string
     */
    public function protocol(): string
    {
        return $this->server('SERVER_PROTOCOL', '');
    }

    /**
     * ��ǰ���� REMOTE_PORT
     * @access public
     * @return string
     */
    public function remotePort(): string
    {
        return $this->server('REMOTE_PORT', '');
    }

    /**
     * ��ǰ���� HTTP_CONTENT_TYPE
     * @access public
     * @return string
     */
    public function contentType(): string
    {
        $contentType = $this->server('CONTENT_TYPE');

        if ($contentType) {
            if (strpos($contentType, ';')) {
                list($type) = explode(';', $contentType);
            } else {
                $type = $contentType;
            }
            return trim($type);
        }

        return '';
    }

    /**
     * ���û��߻�ȡ��ǰ����ĵ�����Ϣ
     * @access public
     * @param  Dispatch  $dispatch ������Ϣ
     * @return Dispatch
     */
    public function dispatch(Dispatch $dispatch = null)
    {
        if (!is_null($dispatch)) {
            $this->dispatch = $dispatch;
        }

        return $this->dispatch;
    }

    /**
     * ��ȡ��ǰ����İ�ȫKey
     * @access public
     * @return string
     */
    public function secureKey(): string
    {
        if (is_null($this->secureKey)) {
            $this->secureKey = uniqid('', true);
        }

        return $this->secureKey;
    }

    /**
     * ���õ�ǰ��Ӧ����
     * @access public
     * @param  string $app Ӧ����
     * @return $this
     */
    public function setApp(string $app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * ���õ�ǰ�Ŀ�������
     * @access public
     * @param  string $controller ��������
     * @return $this
     */
    public function setController(string $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * ���õ�ǰ�Ĳ�����
     * @access public
     * @param  string $action ������
     * @return $this
     */
    public function setAction(string $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * ��ȡ��ǰ��Ӧ����
     * @access public
     * @return string
     */
    public function app(): string
    {
        return $this->app ?: '';
    }

    /**
     * ��ȡ��ǰ�Ŀ�������
     * @access public
     * @param  bool $convert ת��ΪСд
     * @return string
     */
    public function controller(bool $convert = false): string
    {
        $name = $this->controller ?: '';
        return $convert ? strtolower($name) : $name;
    }

    /**
     * ��ȡ��ǰ�Ĳ�����
     * @access public
     * @param  bool $convert ת��ΪСд
     * @return string
     */
    public function action(bool $convert = false): string
    {
        $name = $this->action ?: '';
        return $convert ? strtolower($name) : $name;
    }

    /**
     * ���û��߻�ȡ��ǰ�����content
     * @access public
     * @return string
     */
    public function getContent(): string
    {
        if (is_null($this->content)) {
            $this->content = $this->input;
        }

        return $this->content;
    }

    /**
     * ��ȡ��ǰ�����php://input
     * @access public
     * @return string
     */
    public function getInput(): string
    {
        return $this->input;
    }

    /**
     * ������������
     * @access public
     * @param  string $name ��������
     * @param  mixed  $type �������ɷ���
     * @return string
     */
    public function buildToken(string $name = '__token__', $type = 'md5'): string
    {
        $type  = is_callable($type) ? $type : 'md5';
        $token = call_user_func($type, $this->server('REQUEST_TIME_FLOAT'));

        $this->session->set($name, $token);

        return $token;
    }

    /**
     * �����������
     * @access public
     * @param  string $name ��������
     * @param  array  $data ������
     * @return bool
     */
    public function checkToken(string $token = '__token__', array $data = []): bool
    {
        if (in_array($this->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        if (!$this->session->has($token)) {
            // ����������Ч
            return false;
        }

        // Header��֤
        if ($this->header('X-CSRF-TOKEN') && $this->session->get($token) === $this->header('X-CSRF-TOKEN')) {
            // ��ֹ�ظ��ύ
            $this->session->delete($token); // ��֤�������session
            return true;
        }

        if (empty($data)) {
            $data = $this->post();
        }

        // ������֤
        if (isset($data[$token]) && $this->session->get($token) === $data[$token]) {
            // ��ֹ�ظ��ύ
            $this->session->delete($token); // ��֤�������session
            return true;
        }

        // ����TOKEN����
        $this->session->delete($token);
        return false;
    }

    /**
     * �������м�����ݵ�����
     * @access public
     * @param  array $middleware ����
     * @return $this
     */
    public function withMiddleware(array $middleware)
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    /**
     * ����GET����
     * @access public
     * @param  array $get ����
     * @return $this
     */
    public function withGet(array $get)
    {
        $this->get = $get;
        return $this;
    }

    /**
     * ����POST����
     * @access public
     * @param  array $post ����
     * @return $this
     */
    public function withPost(array $post)
    {
        $this->post = $post;
        return $this;
    }

    /**
     * ����COOKIE����
     * @access public
     * @param array $cookie ����
     * @return $this
     */
    public function withCookie(array $cookie)
    {
        $this->cookie = $cookie;
        return $this;
    }

    /**
     * ����SESSION����
     * @access public
     * @param Session $session ����
     * @return $this
     */
    public function withSession(Session $session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * ����SERVER����
     * @access public
     * @param  array $server ����
     * @return $this
     */
    public function withServer(array $server)
    {
        $this->server = array_change_key_case($server, CASE_UPPER);
        return $this;
    }

    /**
     * ����HEADER����
     * @access public
     * @param  array $header ����
     * @return $this
     */
    public function withHeader(array $header)
    {
        $this->header = array_change_key_case($header);
        return $this;
    }

    /**
     * ����ENV����
     * @access public
     * @param Env $env ����
     * @return $this
     */
    public function withEnv(Env $env)
    {
        $this->env = $env;
        return $this;
    }

    /**
     * ����php://input����
     * @access public
     * @param  string $input RAW����
     * @return $this
     */
    public function withInput(string $input)
    {
        $this->input = $input;
        return $this;
    }

    /**
     * �����ļ��ϴ�����
     * @access public
     * @param  array $files �ϴ���Ϣ
     * @return $this
     */
    public function withFiles(array $files)
    {
        $this->file = $files;
        return $this;
    }

    /**
     * ����ROUTE����
     * @access public
     * @param  array $route ����
     * @return $this
     */
    public function withRoute(array $route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * �����м䴫������
     * @access public
     * @param  string    $name  ������
     * @param  mixed     $value ֵ
     */
    public function __set(string $name, $value)
    {
        $this->middleware[$name] = $value;
    }

    /**
     * ��ȡ�м䴫�����ݵ�ֵ
     * @access public
     * @param  string $name ����
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->middleware($name);
    }

    /**
     * ����������ݵ�ֵ
     * @access public
     * @param  string $name ����
     * @return boolean
     */
    public function __isset(string $name): bool
    {
        return isset($this->param[$name]);
    }
}

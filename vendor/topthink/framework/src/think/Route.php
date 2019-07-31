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

use Closure;
use think\cache\Driver;
use think\exception\HttpResponseException;
use think\exception\RouteNotFoundException;
use think\route\Dispatch;
use think\route\dispatch\Url as UrlDispatch;
use think\route\Domain;
use think\route\Resource;
use think\route\Rule;
use think\route\RuleGroup;
use think\route\RuleItem;
use think\route\RuleName;
use think\route\Url as UrlBuild;

/**
 * ·�ɹ�����
 */
class Route
{
    /**
     * REST����
     * @var array
     */
    protected $rest = [
        'index'  => ['get', '', 'index'],
        'create' => ['get', '/create', 'create'],
        'edit'   => ['get', '/<id>/edit', 'edit'],
        'read'   => ['get', '/<id>', 'read'],
        'save'   => ['post', '', 'save'],
        'update' => ['put', '/<id>', 'update'],
        'delete' => ['delete', '/<id>', 'delete'],
    ];

    /**
     * ���ò���
     * @var array
     */
    protected $config = [
        // pathinfo�ָ���
        'pathinfo_depr'         => '/',
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
        // ·�ɻ�������
        'route_check_cache'     => false,
        'route_cache_option'    => [],
        'route_check_cache_key' => '',
        // �Ƿ��Զ�ת��URL�еĿ������Ͳ�����
        'url_convert'           => true,
        // Ĭ�ϵ�·�ɱ�������
        'default_route_pattern' => '[\w\.]+',
        // URLα��̬��׺
        'url_html_suffix'       => 'html',
        // ���ʿ�����������
        'controller_layer'      => 'controller',
        // �տ�������
        'empty_controller'      => 'Error',
        // �Ƿ�ʹ�ÿ�������׺
        'controller_suffix'     => false,
        // Ĭ�Ͽ�������
        'default_controller'    => 'Index',
        // Ĭ�ϲ�����
        'default_action'        => 'index',
        // ����������׺
        'action_suffix'         => '',
        // �Ƿ���·�ɼ�⻺��
        'route_check_cache'     => false,
        // ��·�ɱ����Ƿ�ʹ����ͨ������ʽ������URL���ɣ�
        'url_common_param'      => true,
    ];

    /**
     * ��ǰӦ��
     * @var App
     */
    protected $app;

    /**
     * �������
     * @var Request
     */
    protected $request;

    /**
     * ����
     * @var Driver
     */
    protected $cache;

    /**
     * @var RuleName
     */
    protected $ruleName;

    /**
     * ��ǰHOST
     * @var string
     */
    protected $host;

    /**
     * ��ǰ�������
     * @var RuleGroup
     */
    protected $group;

    /**
     * ·�ɰ�
     * @var array
     */
    protected $bind = [];

    /**
     * ��������
     * @var array
     */
    protected $domains = [];

    /**
     * ����·�ɹ���
     * @var RuleGroup
     */
    protected $cross;

    /**
     * ·���Ƿ��ӳٽ���
     * @var bool
     */
    protected $lazy = true;

    /**
     * ·���Ƿ����ģʽ
     * @var bool
     */
    protected $isTest = false;

    /**
     * �����飩·�ɹ����Ƿ�ϲ�����
     * @var bool
     */
    protected $mergeRuleRegex = false;

    public function __construct(App $app)
    {
        $this->app      = $app;
        $this->ruleName = new RuleName();
        $this->setDefaultDomain();
    }

    protected function init()
    {
        $this->config = array_merge($this->config, $this->app->config->get('route'));

        $this->lazy($this->config['url_lazy_route']);

        if ($this->config['route_check_cache']) {
            if (!empty($this->config['route_cache_option'])) {
                $this->cache = $this->app->cache->connect($this->config['route_cache_option']);
            } else {
                $this->cache = $this->app->cache->init();
            }
        }

        if (is_file($this->app->getRuntimePath() . 'route.php')) {
            // ��ȡ·��ӳ���ļ�
            $this->import(include $this->app->getRuntimePath() . 'route.php');
        }
    }

    public function config(string $name = null)
    {
        if (is_null($name)) {
            return $this->config;
        }

        return $this->config[$name] ?? null;
    }

    /**
     * ����·�����������飨������Դ·�ɣ��Ƿ��ӳٽ���
     * @access public
     * @param bool $lazy ·���Ƿ��ӳٽ���
     * @return $this
     */
    public function lazy(bool $lazy = true)
    {
        $this->lazy = $lazy;
        return $this;
    }

    /**
     * ����·��Ϊ����ģʽ
     * @access public
     * @param bool $test ·���Ƿ����ģʽ
     * @return void
     */
    public function setTestMode(bool $test): void
    {
        $this->isTest = $test;
    }

    /**
     * ���·���Ƿ�Ϊ����ģʽ
     * @access public
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->isTest;
    }

    /**
     * ����·�����������飨������Դ·�ɣ��Ƿ�ϲ�����
     * @access public
     * @param bool $merge ·���Ƿ�ϲ�����
     * @return $this
     */
    public function mergeRuleRegex(bool $merge = true)
    {
        $this->mergeRuleRegex = $merge;
        $this->group->mergeRuleRegex($merge);

        return $this;
    }

    /**
     * ��ʼ��Ĭ������
     * @access protected
     * @return void
     */
    protected function setDefaultDomain(): void
    {
        // ע��Ĭ������
        $domain = new Domain($this);

        $this->domains['-'] = $domain;

        // Ĭ�Ϸ���
        $this->group = $domain;
    }

    /**
     * ���õ�ǰ����
     * @access public
     * @param RuleGroup $group ����
     * @return void
     */
    public function setGroup(RuleGroup $group): void
    {
        $this->group = $group;
    }

    /**
     * ��ȡָ����ʶ��·�ɷ��� ��ָ�����ȡ��ǰ����
     * @access public
     * @return RuleGroup
     */
    public function getGroup(string $name = null)
    {
        return $name ? $this->ruleName->getGroup($name) : $this->group;
    }

    /**
     * ע���������
     * @access public
     * @param array $pattern ��������
     * @return $this
     */
    public function pattern(array $pattern)
    {
        $this->group->pattern($pattern);

        return $this;
    }

    /**
     * ע��·�ɲ���
     * @access public
     * @param array $option ����
     * @return $this
     */
    public function option(array $option)
    {
        $this->group->option($option);

        return $this;
    }

    /**
     * ע������·��
     * @access public
     * @param string|array $name ������
     * @param mixed        $rule ·�ɹ���
     * @return Domain
     */
    public function domain($name, $rule = null): Domain
    {
        // ֧�ֶ������ʹ����ͬ·�ɹ���
        $domainName = is_array($name) ? array_shift($name) : $name;

        if (!isset($this->domains[$domainName])) {
            $domain = (new Domain($this, $domainName, $rule))
                ->lazy($this->lazy)
                ->mergeRuleRegex($this->mergeRuleRegex);

            $this->domains[$domainName] = $domain;
        } else {
            $domain = $this->domains[$domainName];
            $domain->parseGroupRule($rule);
        }

        if (is_array($name) && !empty($name)) {
            foreach ($name as $item) {
                $this->domains[$item] = $domainName;
            }
        }

        // ������������
        return $domain;
    }

    /**
     * ��ȡ����
     * @access public
     * @return array
     */
    public function getDomains(): array
    {
        return $this->domains;
    }

    /**
     * ��ȡRuleName����
     * @access public
     * @return RuleName
     */
    public function getRuleName(): RuleName
    {
        return $this->ruleName;
    }

    /**
     * ����·�ɰ�
     * @access public
     * @param string $bind   ����Ϣ
     * @param string $domain ����
     * @return $this
     */
    public function bind(string $bind, string $domain = null)
    {
        $domain = is_null($domain) ? '-' : $domain;

        $this->bind[$domain] = $bind;

        return $this;
    }

    /**
     * ��ȡ·�ɰ���Ϣ
     * @access public
     * @return array
     */
    public function getBind(): array
    {
        return $this->bind;
    }

    /**
     * ��ȡ·�ɰ�
     * @access public
     * @param string $domain ����
     * @return string|null
     */
    public function getDomainBind(string $domain = null)
    {
        if (is_null($domain)) {
            $domain = $this->host;
        } elseif (false === strpos($domain, '.')) {
            $domain .= '.' . $this->request->rootDomain();
        }

        $subDomain = $this->request->subDomain();

        if (strpos($subDomain, '.')) {
            $name = '*' . strstr($subDomain, '.');
        }

        if (isset($this->bind[$domain])) {
            $result = $this->bind[$domain];
        } elseif (isset($name) && isset($this->bind[$name])) {
            $result = $this->bind[$name];
        } elseif (!empty($subDomain) && isset($this->bind['*'])) {
            $result = $this->bind['*'];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * ��ȡ·�ɱ�ʶ
     * @access public
     * @param string $name   ·�ɱ�ʶ
     * @param string $domain ����
     * @param string $method ��������
     * @return RuleItem[]
     */
    public function getName(string $name = null, string $domain = null, string $method = '*'): array
    {
        return $this->ruleName->getName($name, $domain, $method);
    }

    /**
     * ��������·�ɱ�ʶ
     * @access public
     * @param array $name ·�ɱ�ʶ
     * @return $this
     */
    public function import(array $name): void
    {
        $this->ruleName->import($name);
    }

    /**
     * ע��·�ɱ�ʶ
     * @access public
     * @param string   $name  ·�ɱ�ʶ
     * @param RuleItem $ruleItem ·�ɹ���
     * @param bool     $first �Ƿ�����
     * @return void
     */
    public function setName(string $name, RuleItem $ruleItem, bool $first = false): void
    {
        $this->ruleName->setName($name, $ruleItem, $first);
    }

    /**
     * ����·�ɹ���
     * @access public
     * @param string $rule   ·�ɹ���
     * @param RuleItem $ruleItem RuleItem����
     * @return void
     */
    public function setRule(string $rule, RuleItem $ruleItem = null): void
    {
        $this->ruleName->setRule($rule, $ruleItem);
    }

    /**
     * ��ȡ·��
     * @access public
     * @param string $rule   ·�ɹ���
     * @return RuleItem[]
     */
    public function getRule(string $rule): array
    {
        return $this->ruleName->getRule($rule);
    }

    /**
     * ��ȡ·���б�
     * @access public
     * @return array
     */
    public function getRuleList(): array
    {
        return $this->ruleName->getRuleList();
    }

    /**
     * ���·�ɹ���
     * @access public
     * @return void
     */
    public function clear(): void
    {
        $this->ruleName->clear();

        if ($this->group) {
            $this->group->clear();
        }
    }

    /**
     * ע��·�ɹ���
     * @access public
     * @param string $rule   ·�ɹ���
     * @param mixed  $route  ·�ɵ�ַ
     * @param string $method ��������
     * @return RuleItem
     */
    public function rule(string $rule, $route = null, string $method = '*'): RuleItem
    {
        return $this->group->addRule($rule, $route, $method);
    }

    /**
     * ���ÿ�����Ч·�ɹ���
     * @access public
     * @param Rule   $rule   ·�ɹ���
     * @param string $method ��������
     * @return $this
     */
    public function setCrossDomainRule(Rule $rule, string $method = '*')
    {
        if (!isset($this->cross)) {
            $this->cross = (new RuleGroup($this))->mergeRuleRegex($this->mergeRuleRegex);
        }

        $this->cross->addRuleItem($rule, $method);

        return $this;
    }

    /**
     * ע��·�ɷ���
     * @access public
     * @param string|\Closure $name  �������ƻ��߲���
     * @param mixed           $route ����·��
     * @return RuleGroup
     */
    public function group($name, $route = null): RuleGroup
    {
        if ($name instanceof \Closure) {
            $route = $name;
            $name  = '';
        }

        return (new RuleGroup($this, $this->group, $name, $route))
            ->lazy($this->lazy)
            ->mergeRuleRegex($this->mergeRuleRegex);
    }

    /**
     * ע��·��
     * @access public
     * @param string $rule  ·�ɹ���
     * @param mixed  $route ·�ɵ�ַ
     * @return RuleItem
     */
    public function any(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, '*');
    }

    /**
     * ע��GET·��
     * @access public
     * @param string $rule  ·�ɹ���
     * @param mixed  $route ·�ɵ�ַ
     * @return RuleItem
     */
    public function get(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'GET');
    }

    /**
     * ע��POST·��
     * @access public
     * @param string $rule  ·�ɹ���
     * @param mixed  $route ·�ɵ�ַ
     * @return RuleItem
     */
    public function post(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'POST');
    }

    /**
     * ע��PUT·��
     * @access public
     * @param string $rule  ·�ɹ���
     * @param mixed  $route ·�ɵ�ַ
     * @return RuleItem
     */
    public function put(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'PUT');
    }

    /**
     * ע��DELETE·��
     * @access public
     * @param string $rule  ·�ɹ���
     * @param mixed  $route ·�ɵ�ַ
     * @return RuleItem
     */
    public function delete(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'DELETE');
    }

    /**
     * ע��PATCH·��
     * @access public
     * @param string $rule  ·�ɹ���
     * @param mixed  $route ·�ɵ�ַ
     * @return RuleItem
     */
    public function patch(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'PATCH');
    }

    /**
     * ע����Դ·��
     * @access public
     * @param string $rule  ·�ɹ���
     * @param string $route ·�ɵ�ַ
     * @return Resource
     */
    public function resource(string $rule, string $route): Resource
    {
        return (new Resource($this, $this->group, $rule, $route, $this->rest))
            ->lazy($this->lazy);
    }

    /**
     * ע����ͼ·��
     * @access public
     * @param string|array $rule     ·�ɹ���
     * @param string       $template ·��ģ���ַ
     * @param array        $vars     ģ�����
     * @return RuleItem
     */
    public function view(string $rule, string $template = '', array $vars = []): RuleItem
    {
        return $this->rule($rule, $template, 'GET')->view($vars);
    }

    /**
     * ע���ض���·��
     * @access public
     * @param string|array $rule   ·�ɹ���
     * @param string       $route  ·�ɵ�ַ
     * @param int          $status ״̬��
     * @return RuleItem
     */
    public function redirect(string $rule, string $route = '', int $status = 301): RuleItem
    {
        return $this->rule($rule, $route, '*')->redirect()->status($status);
    }

    /**
     * rest����������޸�
     * @access public
     * @param string|array $name     ��������
     * @param array|bool   $resource ��Դ
     * @return $this
     */
    public function rest($name, $resource = [])
    {
        if (is_array($name)) {
            $this->rest = $resource ? $name : array_merge($this->rest, $name);
        } else {
            $this->rest[$name] = $resource;
        }

        return $this;
    }

    /**
     * ��ȡrest��������Ĳ���
     * @access public
     * @param string $name ��������
     * @return array|null
     */
    public function getRest(string $name = null)
    {
        if (is_null($name)) {
            return $this->rest;
        }

        return $this->rest[$name] ?? null;
    }

    /**
     * ע��δƥ��·�ɹ����Ĵ���
     * @access public
     * @param string|Closure $route  ·�ɵ�ַ
     * @param string         $method ��������
     * @return RuleItem
     */
    public function miss($route, string $method = '*'): RuleItem
    {
        return $this->group->miss($route, $method);
    }

    /**
     * ·�ɵ���
     * @param Request $request
     * @param Closure $withRoute
     * @return Response
     */
    public function dispatch(Request $request, $withRoute = null)
    {
        $this->request = $request;
        $this->host    = $this->request->host(true);
        $this->init();

        if ($withRoute) {
            $checkCallback = function () use ($request, $withRoute) {
                //����·��
                $withRoute();
                return $this->check();
            };

            if ($this->config['route_check_cache']) {
                $dispatch = $this->cache
                    ->tag('route_cache')
                    ->remember($this->getRouteCacheKey($request), $checkCallback);
            } else {
                $dispatch = $checkCallback();
            }
        } else {
            $dispatch = $this->url($this->path());
        }

        $dispatch->init($this->app);

        $this->app->middleware->add(function () use ($dispatch) {
            try {
                $response = $dispatch->run();
            } catch (HttpResponseException $exception) {
                $response = $exception->getResponse();
            }
            return $response;
        });

        return $this->app->middleware->dispatch($request);
    }

    /**
     * ��ȡ·�ɻ���Key
     * @access protected
     * @param Request $request
     * @return string
     */
    protected function getRouteCacheKey(Request $request): string
    {
        if (!empty($this->config['route_check_cache_key'])) {
            $closure  = $this->config['route_check_cache_key'];
            $routeKey = $closure($request);
        } else {
            $routeKey = md5($request->baseUrl(true) . ':' . $request->method());
        }

        return $routeKey;
    }

    /**
     * ���URL·��
     * @access public
     * @return Dispatch
     * @throws RouteNotFoundException
     */
    public function check(): Dispatch
    {
        // �Զ��������·��
        $url = str_replace($this->config['pathinfo_depr'], '|', $this->path());

        $completeMatch = $this->config['route_complete_match'];

        $result = $this->checkDomain()->check($this->request, $url, $completeMatch);

        if (false === $result && !empty($this->cross)) {
            // ������·��
            $result = $this->cross->check($this->request, $url, $completeMatch);
        }

        if (false !== $result) {
            return $result;
        } elseif ($this->config['url_route_must']) {
            throw new RouteNotFoundException();
        }

        return $this->url($url);
    }

    /**
     * ��ȡ��ǰ����URL��pathinfo��Ϣ(����URL��׺)
     * @access protected
     * @return string
     */
    protected function path(): string
    {
        $suffix   = $this->config['url_html_suffix'];
        $pathinfo = $this->request->pathinfo();

        if (false === $suffix) {
            // ��ֹα��̬����
            $path = $pathinfo;
        } elseif ($suffix) {
            // ȥ��������URL��׺
            $path = preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $pathinfo);
        } else {
            // �����κκ�׺����
            $path = preg_replace('/\.' . $this->request->ext() . '$/i', '', $pathinfo);
        }

        return $path;
    }

    /**
     * Ĭ��URL����
     * @access public
     * @param string $url URL��ַ
     * @return Dispatch
     */
    public function url(string $url): UrlDispatch
    {
        return new UrlDispatch($this->request, $this->group, $url);
    }

    /**
     * ���������·�ɹ���
     * @access protected
     * @return Domain
     */
    protected function checkDomain(): Domain
    {
        $item = false;

        if (count($this->domains) > 1) {
            // ��ȡ��ǰ������
            $subDomain = $this->request->subDomain();

            $domain  = $subDomain ? explode('.', $subDomain) : [];
            $domain2 = $domain ? array_pop($domain) : '';

            if ($domain) {
                // ������������
                $domain3 = array_pop($domain);
            }

            if (isset($this->domains[$this->host])) {
                // ����������
                $item = $this->domains[$this->host];
            } elseif (isset($this->domains[$subDomain])) {
                $item = $this->domains[$subDomain];
            } elseif (isset($this->domains['*.' . $domain2]) && !empty($domain3)) {
                // ����������
                $item      = $this->domains['*.' . $domain2];
                $panDomain = $domain3;
            } elseif (isset($this->domains['*']) && !empty($domain2)) {
                // ����������
                if ('www' != $domain2) {
                    $item      = $this->domains['*'];
                    $panDomain = $domain2;
                }
            }

            if (isset($panDomain)) {
                // ���浱ǰ������
                $this->request->setPanDomain($panDomain);
            }
        }

        if (false === $item) {
            // ���ȫ����������
            $item = $this->domains['-'];
        }

        if (is_string($item)) {
            $item = $this->domains[$item];
        }

        return $item;
    }

    /**
     * URL���� ֧��·�ɷ���
     * @access public
     * @param  string $url ·�ɵ�ַ
     * @param  array  $vars ���� ['a'=>'val1', 'b'=>'val2']
     * @return UrlBuild
     */
    public function buildUrl(string $url = '', array $vars = []): UrlBuild
    {
        return new UrlBuild($this, $this->app, $url, $vars);
    }

    /**
     * ����ȫ�ֵ�·�ɷ������
     * @access public
     * @param string $method ������
     * @param array  $args   ���ò���
     * @return RuleGroup
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->group, $method], $args);
    }
}

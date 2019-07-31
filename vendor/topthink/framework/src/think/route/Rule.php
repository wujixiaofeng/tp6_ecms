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

namespace think\route;

use Closure;
use think\Container;
use think\Request;
use think\Response;
use think\Route;
use think\route\dispatch\Callback as CallbackDispatch;
use think\route\dispatch\Controller as ControllerDispatch;
use think\route\dispatch\Redirect as RedirectDispatch;
use think\route\dispatch\Response as ResponseDispatch;
use think\route\dispatch\View as ViewDispatch;

/**
 * ·�ɹ��������
 */
abstract class Rule
{
    /**
     * ·�ɱ�ʶ
     * @var string
     */
    protected $name;

    /**
     * ·�ɶ���
     * @var Route
     */
    protected $router;

    /**
     * ·����������
     * @var RuleGroup
     */
    protected $parent;

    /**
     * ·�ɹ���
     * @var mixed
     */
    protected $rule;

    /**
     * ·�ɵ�ַ
     * @var string|Closure
     */
    protected $route;

    /**
     * ��������
     * @var string
     */
    protected $method;

    /**
     * ·�ɱ���
     * @var array
     */
    protected $vars = [];

    /**
     * ·�ɲ���
     * @var array
     */
    protected $option = [];

    /**
     * ·�ɱ�������
     * @var array
     */
    protected $pattern = [];

    /**
     * ��Ҫ�ͷ���ϲ���·�ɲ���
     * @var array
     */
    protected $mergeOptions = ['after', 'model', 'append', 'middleware'];

    abstract public function check(Request $request, string $url, bool $completeMatch = false);

    /**
     * ����·�ɲ���
     * @access public
     * @param  array $option ����
     * @return $this
     */
    public function option(array $option)
    {
        $this->option = array_merge($this->option, $option);

        return $this;
    }

    /**
     * ���õ���·�ɲ���
     * @access public
     * @param  string $name  ������
     * @param  mixed  $value ֵ
     * @return $this
     */
    public function setOption(string $name, $value)
    {
        $this->option[$name] = $value;

        return $this;
    }

    /**
     * ע���������
     * @access public
     * @param  array $pattern ��������
     * @return $this
     */
    public function pattern(array $pattern)
    {
        $this->pattern = array_merge($this->pattern, $pattern);

        return $this;
    }

    /**
     * ���ñ�ʶ
     * @access public
     * @param  string $name ��ʶ��
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * ��ȡ·�ɶ���
     * @access public
     * @return Route
     */
    public function getRouter(): Route
    {
        return $this->router;
    }

    /**
     * ��ȡName
     * @access public
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?: '';
    }

    /**
     * ��ȡ��ǰ·�ɹ���
     * @access public
     * @return mixed
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * ��ȡ��ǰ·�ɵ�ַ
     * @access public
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * ��ȡ��ǰ·�ɵı���
     * @access public
     * @return array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * ��ȡParent����
     * @access public
     * @return $this|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * ��ȡ·����������
     * @access public
     * @return string
     */
    public function getDomain(): string
    {
        return $this->parent->getDomain();
    }

    /**
     * ��ȡ·�ɲ���
     * @access public
     * @param  string $name ������
     * @return mixed
     */
    public function config(string $name = '')
    {
        return $this->router->config($name);
    }

    /**
     * ��ȡ����������
     * @access public
     * @param  string $name ������
     * @return mixed
     */
    public function getPattern(string $name = '')
    {
        if ('' === $name) {
            return $this->pattern;
        }

        return $this->pattern[$name] ?? null;
    }

    /**
     * ��ȡ·�ɲ�������
     * @access public
     * @param  string $name ������
     * @param  mixed  $default Ĭ��ֵ
     * @return mixed
     */
    public function getOption(string $name = '', $default = null)
    {
        if ('' === $name) {
            return $this->option;
        }

        return $this->option[$name] ?? $default;
    }

    /**
     * ��ȡ��ǰ·�ɵ���������
     * @access public
     * @return string
     */
    public function getMethod(): string
    {
        return strtolower($this->method);
    }

    /**
     * ����·����������
     * @access public
     * @param  string $method ��������
     * @return $this
     */
    public function method(string $method)
    {
        return $this->setOption('method', strtolower($method));
    }

    /**
     * ����׺
     * @access public
     * @param  string $ext URL��׺
     * @return $this
     */
    public function ext(string $ext = '')
    {
        return $this->setOption('ext', $ext);
    }

    /**
     * ����ֹ��׺
     * @access public
     * @param  string $ext URL��׺
     * @return $this
     */
    public function denyExt(string $ext = '')
    {
        return $this->setOption('deny_ext', $ext);
    }

    /**
     * �������
     * @access public
     * @param  string $domain ����
     * @return $this
     */
    public function domain(string $domain)
    {
        return $this->setOption('domain', $domain);
    }

    /**
     * ���ò������˼��
     * @access public
     * @param  array $filter ��������
     * @return $this
     */
    public function filter(array $filter)
    {
        $this->option['filter'] = $filter;

        return $this;
    }

    /**
     * ��ģ��
     * @access public
     * @param  array|string|Closure $var  ·�ɱ����� ���ʹ�� & �ָ�
     * @param  string|Closure       $model ��ģ����
     * @param  bool                  $exception �Ƿ��׳��쳣
     * @return $this
     */
    public function model($var, $model = null, bool $exception = true)
    {
        if ($var instanceof Closure) {
            $this->option['model'][] = $var;
        } elseif (is_array($var)) {
            $this->option['model'] = $var;
        } elseif (is_null($model)) {
            $this->option['model']['id'] = [$var, true];
        } else {
            $this->option['model'][$var] = [$model, $exception];
        }

        return $this;
    }

    /**
     * ����·����ʽ����
     * @access public
     * @param  array $append ׷�Ӳ���
     * @return $this
     */
    public function append(array $append = [])
    {
        $this->option['append'] = $append;

        return $this;
    }

    /**
     * ����֤
     * @access public
     * @param  mixed  $validate ��֤����
     * @param  string $scene ��֤����
     * @param  array  $message ��֤��ʾ
     * @param  bool   $batch ������֤
     * @return $this
     */
    public function validate($validate, string $scene = null, array $message = [], bool $batch = false)
    {
        $this->option['validate'] = [$validate, $scene, $message, $batch];

        return $this;
    }

    /**
     * ָ��·���м��
     * @access public
     * @param  string|array|Closure $middleware �м��
     * @param  mixed                $param ����
     * @return $this
     */
    public function middleware($middleware, $param = null)
    {
        if (is_null($param) && is_array($middleware)) {
            $this->option['middleware'] = $middleware;
        } else {
            foreach ((array) $middleware as $item) {
                $this->option['middleware'][] = [$item, $param];
            }
        }

        return $this;
    }

    /**
     * �������
     * @access public
     * @param  array $header �Զ���Header
     * @return $this
     */
    public function allowCrossDomain(array $header = [])
    {
        return $this->middleware('\think\middleware\AllowCrossDomain', $header);
    }

    /**
     * ��������֤
     * @access public
     * @param  string $token ������token����
     * @return $this
     */
    public function token(string $token = '__token__')
    {
        return $this->middleware('\think\middleware\FormTokenCheck', $token);
    }

    /**
     * ����·�ɻ���
     * @access public
     * @param  array|string $cache ����
     * @return $this
     */
    public function cache($cache)
    {
        return $this->middleware('\think\middleware\CheckRequestCache', $cache);
    }

    /**
     * ���URL�ָ���
     * @access public
     * @param  string $depr URL�ָ���
     * @return $this
     */
    public function depr(string $depr)
    {
        return $this->setOption('param_depr', $depr);
    }

    /**
     * ������Ҫ�ϲ���·�ɲ���
     * @access public
     * @param  array $option ·�ɲ���
     * @return $this
     */
    public function mergeOptions(array $option = [])
    {
        $this->mergeOptions = array_merge($this->mergeOptions, $option);
        return $this;
    }

    /**
     * ����Ƿ�ΪHTTPS����
     * @access public
     * @param  bool $https �Ƿ�ΪHTTPS
     * @return $this
     */
    public function https(bool $https = true)
    {
        return $this->setOption('https', $https);
    }

    /**
     * ����Ƿ�ΪJSON����
     * @access public
     * @param  bool $json �Ƿ�ΪJSON
     * @return $this
     */
    public function json(bool $json = true)
    {
        return $this->setOption('json', $json);
    }

    /**
     * ����Ƿ�ΪAJAX����
     * @access public
     * @param  bool $ajax �Ƿ�ΪAJAX
     * @return $this
     */
    public function ajax(bool $ajax = true)
    {
        return $this->setOption('ajax', $ajax);
    }

    /**
     * ����Ƿ�ΪPJAX����
     * @access public
     * @param  bool $pjax �Ƿ�ΪPJAX
     * @return $this
     */
    public function pjax(bool $pjax = true)
    {
        return $this->setOption('pjax', $pjax);
    }

    /**
     * ��ǰ·�ɵ�һ��ģ���ַ ��ʹ�������ʱ����Դ���ģ�����
     * @access public
     * @param  bool|array $view ��ͼ
     * @return $this
     */
    public function view($view = true)
    {
        return $this->setOption('view', $view);
    }

    /**
     * ��ǰ·��Ϊ�ض���
     * @access public
     * @param  bool $redirect �Ƿ�Ϊ�ض���
     * @return $this
     */
    public function redirect(bool $redirect = true)
    {
        return $this->setOption('redirect', $redirect);
    }

    /**
     * ����status
     * @access public
     * @param  int $status ״̬��
     * @return $this
     */
    public function status(int $status)
    {
        return $this->setOption('status', $status);
    }

    /**
     * ����·������ƥ��
     * @access public
     * @param  bool $match �Ƿ�����ƥ��
     * @return $this
     */
    public function completeMatch(bool $match = true)
    {
        return $this->setOption('complete_match', $match);
    }

    /**
     * �Ƿ�ȥ��URL����б��
     * @access public
     * @param  bool $remove �Ƿ�ȥ�����б��
     * @return $this
     */
    public function removeSlash(bool $remove = true)
    {
        return $this->setOption('remove_slash', $remove);
    }

    /**
     * ����·�ɹ���ȫ����Ч
     * @access public
     * @return $this
     */
    public function crossDomainRule()
    {
        if ($this instanceof RuleGroup) {
            $method = '*';
        } else {
            $method = $this->method;
        }

        $this->router->setCrossDomainRule($this, $method);

        return $this;
    }

    /**
     * �ϲ��������
     * @access public
     * @return array
     */
    public function mergeGroupOptions(): array
    {
        $parentOption = $this->parent->getOption();
        // �ϲ��������
        foreach ($this->mergeOptions as $item) {
            if (isset($parentOption[$item]) && isset($this->option[$item])) {
                $this->option[$item] = array_merge($parentOption[$item], $this->option[$item]);
            }
        }

        $this->option = array_merge($parentOption, $this->option);

        return $this->option;
    }

    /**
     * ����ƥ�䵽�Ĺ���·��
     * @access public
     * @param  Request $request �������
     * @param  string  $rule ·�ɹ���
     * @param  mixed   $route ·�ɵ�ַ
     * @param  string  $url URL��ַ
     * @param  array   $option ·�ɲ���
     * @param  array   $matches ƥ��ı���
     * @return Dispatch
     */
    public function parseRule(Request $request, string $rule, $route, string $url, array $option = [], array $matches = []): Dispatch
    {
        if (is_string($route) && isset($option['prefix'])) {
            // ·�ɵ�ַǰ׺
            $route = $option['prefix'] . $route;
        }

        // �滻·�ɵ�ַ�еı���
        if (is_string($route) && !empty($matches)) {
            $search = $replace = [];

            foreach ($matches as $key => $value) {
                $search[]  = '<' . $key . '>';
                $replace[] = $value;

                $search[]  = ':' . $key;
                $replace[] = $value;
            }

            $route = str_replace($search, $replace, $route);
        }

        // �����������
        $count = substr_count($rule, '/');
        $url   = array_slice(explode('|', $url), $count + 1);
        $this->parseUrlParams(implode('|', $url), $matches);

        $this->vars = $matches;

        // ����·�ɵ���
        return $this->dispatch($request, $route, $option);
    }

    /**
     * ����·�ɵ���
     * @access protected
     * @param  Request $request Request����
     * @param  mixed   $route  ·�ɵ�ַ
     * @param  array   $option ·�ɲ���
     * @return Dispatch
     */
    protected function dispatch(Request $request, $route, array $option): Dispatch
    {
        if ($route instanceof Dispatch) {
            $result = $route;
        } elseif ($route instanceof Closure) {
            // ִ�бհ�
            $result = new CallbackDispatch($request, $this, $route, $this->vars);
        } elseif ($route instanceof Response) {
            $result = new ResponseDispatch($request, $this, $route);
        } elseif (isset($option['view']) && false !== $option['view']) {
            $result = new ViewDispatch($request, $this, $route, is_array($option['view']) ? $option['view'] : $this->vars);
        } elseif (!empty($option['redirect']) || 0 === strpos($route, '/') || strpos($route, '://')) {
            // ·�ɵ��ض����ַ
            $result = new RedirectDispatch($request, $this, $route, $this->vars, $option['status'] ?? 301);
        } elseif (false !== strpos($route, '\\')) {
            // ·�ɵ���ķ���
            $result = $this->dispatchMethod($request, $route);
        } else {
            // ·�ɵ�������/����
            $result = $this->dispatchController($request, $route);
        }

        return $result;
    }

    /**
     * ����URL��ַΪ ģ��/������/����
     * @access protected
     * @param  Request $request Request����
     * @param  string  $route ·�ɵ�ַ
     * @return CallbackDispatch
     */
    protected function dispatchMethod(Request $request, string $route): CallbackDispatch
    {
        $path = $this->parseUrlPath($route);

        $route  = str_replace('/', '@', implode('/', $path));
        $method = strpos($route, '@') ? explode('@', $route) : $route;

        return new CallbackDispatch($request, $this, $method, $this->vars);
    }

    /**
     * ����URL��ַΪ ģ��/������/����
     * @access protected
     * @param  Request $request Request����
     * @param  string  $route ·�ɵ�ַ
     * @return ControllerDispatch
     */
    protected function dispatchController(Request $request, string $route): ControllerDispatch
    {
        $path = $this->parseUrlPath($route);

        $action     = array_pop($path);
        $controller = !empty($path) ? array_pop($path) : null;

        // ·�ɵ�ģ��/������/����
        return new ControllerDispatch($request, $this, [$controller, $action], $this->vars);
    }

    /**
     * ·�ɼ��
     * @access protected
     * @param  array   $option ·�ɲ���
     * @param  Request $request Request����
     * @return bool
     */
    protected function checkOption(array $option, Request $request): bool
    {
        // �������ͼ��
        if (!empty($option['method'])) {
            if (is_string($option['method']) && false === stripos($option['method'], $request->method())) {
                return false;
            }
        }

        // AJAX PJAX ������
        foreach (['ajax', 'pjax', 'json'] as $item) {
            if (isset($option[$item])) {
                $call = 'is' . $item;
                if ($option[$item] && !$request->$call() || !$option[$item] && $request->$call()) {
                    return false;
                }
            }
        }

        // α��̬��׺���
        if ($request->url() != '/' && ((isset($option['ext']) && false === stripos('|' . $option['ext'] . '|', '|' . $request->ext() . '|'))
            || (isset($option['deny_ext']) && false !== stripos('|' . $option['deny_ext'] . '|', '|' . $request->ext() . '|')))) {
            return false;
        }

        // �������
        if ((isset($option['domain']) && !in_array($option['domain'], [$request->host(true), $request->subDomain()]))) {
            return false;
        }

        // HTTPS���
        if ((isset($option['https']) && $option['https'] && !$request->isSsl())
            || (isset($option['https']) && !$option['https'] && $request->isSsl())) {
            return false;
        }

        // ����������
        if (isset($option['filter'])) {
            foreach ($option['filter'] as $name => $value) {
                if ($request->param($name, '', null) != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * ����URL��ַ�еĲ���Request����
     * @access protected
     * @param  string $rule ·�ɹ���
     * @param  array  $var ����
     * @return void
     */
    protected function parseUrlParams(string $url, array &$var = []): void
    {
        if ($url) {
            preg_replace_callback('/(\w+)\|([^\|]+)/', function ($match) use (&$var) {
                $var[$match[1]] = strip_tags($match[2]);
            }, $url);
        }
    }

    /**
     * ����URL��pathinfo����
     * @access public
     * @param  string $url URL��ַ
     * @return array
     */
    public function parseUrlPath(string $url): array
    {
        // �ָ����滻 ȷ��·�ɶ���ʹ��ͳһ�ķָ���
        $url = str_replace('|', '/', $url);
        $url = trim($url, '/');

        if (strpos($url, '/')) {
            // [������/����]
            $path = explode('/', $url);
        } else {
            $path = [$url];
        }

        return $path;
    }

    /**
     * ����·�ɵ��������
     * @access protected
     * @param  string $rule ·�ɹ���
     * @param  array  $match ƥ��ı���
     * @param  array  $pattern   ·�ɱ�������
     * @param  array  $option    ·�ɲ���
     * @param  bool   $completeMatch   ·���Ƿ���ȫƥ��
     * @param  string $suffix   ·�����������׺
     * @return string
     */
    protected function buildRuleRegex(string $rule, array $match, array $pattern = [], array $option = [], bool $completeMatch = false, string $suffix = ''): string
    {
        foreach ($match as $name) {
            $replace[] = $this->buildNameRegex($name, $pattern, $suffix);
        }

        // �Ƿ����� / ��ַ����
        if ('/' != $rule) {
            if (!empty($option['remove_slash'])) {
                $rule = rtrim($rule, '/');
            } elseif (substr($rule, -1) == '/') {
                $rule     = rtrim($rule, '/');
                $hasSlash = true;
            }
        }

        $regex = str_replace(array_unique($match), array_unique($replace), $rule);
        $regex = str_replace([')?/', ')/', ')?-', ')-', '\\\\/'], [')\/', ')\/', ')\-', ')\-', '\/'], $regex);

        if (isset($hasSlash)) {
            $regex .= '\/';
        }

        return $regex . ($completeMatch ? '$' : '');
    }

    /**
     * ����·�ɱ������������
     * @access protected
     * @param  string $name    ·�ɱ���
     * @param  array  $pattern ��������
     * @param  string $suffix  ·�����������׺
     * @return string
     */
    protected function buildNameRegex(string $name, array $pattern, string $suffix): string
    {
        $optional = '';
        $slash    = substr($name, 0, 1);

        if (in_array($slash, ['/', '-'])) {
            $prefix = '\\' . $slash;
            $name   = substr($name, 1);
            $slash  = substr($name, 0, 1);
        } else {
            $prefix = '';
        }

        if ('<' != $slash) {
            return $prefix . preg_quote($name, '/');
        }

        if (strpos($name, '?')) {
            $name     = substr($name, 1, -2);
            $optional = '?';
        } elseif (strpos($name, '>')) {
            $name = substr($name, 1, -1);
        }

        if (isset($pattern[$name])) {
            $nameRule = $pattern[$name];
            if (0 === strpos($nameRule, '/') && '/' == substr($nameRule, -1)) {
                $nameRule = substr($nameRule, 1, -1);
            }
        } else {
            $nameRule = $this->router->config('default_route_pattern');
        }

        return '(' . $prefix . '(?<' . $name . $suffix . '>' . $nameRule . '))' . $optional;
    }

    /**
     * ����·�ɲ���
     * @access public
     * @param  string $method ������
     * @param  array  $args   ���ò���
     * @return $this
     */
    public function __call($method, $args)
    {
        if (count($args) > 1) {
            $args[0] = $args;
        }
        array_unshift($args, $method);

        return call_user_func_array([$this, 'setOption'], $args);
    }

    public function __sleep()
    {
        return ['name', 'rule', 'route', 'method', 'vars', 'option', 'pattern'];
    }

    public function __wakeup()
    {
        $this->router = Container::pull('route');
    }

    public function __debugInfo()
    {
        return [
            'name'    => $this->name,
            'rule'    => $this->rule,
            'route'   => $this->route,
            'method'  => $this->method,
            'vars'    => $this->vars,
            'option'  => $this->option,
            'pattern' => $this->pattern,
        ];
    }
}

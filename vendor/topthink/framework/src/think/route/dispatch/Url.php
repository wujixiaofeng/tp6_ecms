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

namespace think\route\dispatch;

use think\App;
use think\exception\HttpException;
use think\Request;
use think\route\Rule;

/**
 * Url Dispatcher
 */
class Url extends Controller
{

    public function __construct(Request $request, Rule $rule, $dispatch, array $param = [], int $code = null)
    {
        $this->request = $request;
        $this->rule    = $rule;
        // ����Ĭ�ϵ�URL����
        $dispatch = $this->parseUrl($dispatch);

        parent::__construct($request, $rule, $dispatch, $this->param, $code);
    }

    /**
     * ����URL��ַ
     * @access protected
     * @param  string $url URL
     * @return array
     */
    protected function parseUrl(string $url): array
    {
        $depr = $this->rule->config('pathinfo_depr');
        $bind = $this->rule->getRouter()->getDomainBind();

        if ($bind && preg_match('/^[a-z]/is', $bind)) {
            $bind = str_replace('/', $depr, $bind);
            // �����ģ��/��������
            $url = $bind . ('.' != substr($bind, -1) ? $depr : '') . ltrim($url, $depr);
        }

        $path = $this->rule->parseUrlPath($url);
        if (empty($path)) {
            return [null, null];
        }

        // ����������
        $controller = !empty($path) ? array_shift($path) : null;

        if ($controller && !preg_match('/^[A-Za-z][\w|\.]*$/', $controller)) {
            throw new HttpException(404, 'controller not exists:' . $controller);
        }

        // ��������
        $action = !empty($path) ? array_shift($path) : null;
        $var    = [];

        // �����������
        if ($path) {
            preg_replace_callback('/(\w+)\|([^\|]+)/', function ($match) use (&$var) {
                $var[$match[1]] = strip_tags($match[2]);
            }, implode('|', $path));
        }

        $panDomain = $this->request->panDomain();
        if ($panDomain && $key = array_search('*', $var)) {
            // ��������ֵ
            $var[$key] = $panDomain;
        }

        // ���õ�ǰ����Ĳ���
        $this->param = $var;

        // ��װ·��
        $route = [$controller, $action];

        if ($this->hasDefinedRoute($route)) {
            throw new HttpException(404, 'invalid request:' . str_replace('|', $depr, $url));
        }

        return $route;
    }

    /**
     * ���URL�Ƿ��Ѿ������·��
     * @access protected
     * @param  array $route ·����Ϣ
     * @return bool
     */
    protected function hasDefinedRoute(array $route): bool
    {
        list($controller, $action) = $route;

        // ����ַ�Ƿ񱻶����·��
        $name = strtolower(App::parseName($controller, 1) . '/' . $action);

        $host   = $this->request->host(true);
        $method = $this->request->method();

        if ($this->rule->getRouter()->getName($name, $host, $method)) {
            return true;
        }

        return false;
    }

}

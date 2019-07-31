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

use think\App;
use think\Request;
use think\Route;
use think\route\dispatch\Callback as CallbackDispatch;
use think\route\dispatch\Controller as ControllerDispatch;

/**
 * ����·��
 */
class Domain extends RuleGroup
{
    /**
     * �ܹ�����
     * @access public
     * @param  Route       $router   ·�ɶ���
     * @param  string      $name     ·������
     * @param  mixed       $rule     ����·��
     */
    public function __construct(Route $router, string $name = null, $rule = null)
    {
        $this->router = $router;
        $this->domain = $name;
        $this->rule   = $rule;
    }

    /**
     * �������·��
     * @access public
     * @param  Request      $request  �������
     * @param  string       $url      ���ʵ�ַ
     * @param  bool         $completeMatch   ·���Ƿ���ȫƥ��
     * @return Dispatch|false
     */
    public function check(Request $request, string $url, bool $completeMatch = false)
    {
        // ���URL��
        $result = $this->checkUrlBind($request, $url);

        if (!empty($this->option['append'])) {
            $request->setRoute($this->option['append']);
            unset($this->option['append']);
        }

        if (false !== $result) {
            return $result;
        }

        return parent::check($request, $url, $completeMatch);
    }

    /**
     * ����·�ɰ�
     * @access public
     * @param  string     $bind ����Ϣ
     * @return $this
     */
    public function bind(string $bind)
    {
        $this->router->bind($bind, $this->domain);

        return $this;
    }

    /**
     * ���URL��
     * @access private
     * @param  Request   $request
     * @param  string    $url URL��ַ
     * @return Dispatch|false
     */
    private function checkUrlBind(Request $request, string $url)
    {
        $bind = $this->router->getDomainBind($this->domain);

        if ($bind) {
            $this->parseBindAppendParam($bind);

            // �����URL�� ����а󶨼��
            $type = substr($bind, 0, 1);
            $bind = substr($bind, 1);

            $bindTo = [
                '\\' => 'bindToClass',
                '@'  => 'bindToController',
                ':'  => 'bindToNamespace',
            ];

            if (isset($bindTo[$type])) {
                return $this->{$bindTo[$type]}($request, $url, $bind);
            }
        }

        return false;
    }

    protected function parseBindAppendParam(string &$bind): void
    {
        if (false !== strpos($bind, '?')) {
            list($bind, $query) = explode('?', $bind);
            parse_str($query, $vars);
            $this->append($vars);
        }
    }

    /**
     * �󶨵���
     * @access protected
     * @param  Request   $request
     * @param  string    $url URL��ַ
     * @param  string    $class �������������ռ䣩
     * @return CallbackDispatch
     */
    protected function bindToClass(Request $request, string $url, string $class): CallbackDispatch
    {
        $array  = explode('|', $url, 2);
        $action = !empty($array[0]) ? $array[0] : $this->router->config('default_action');
        $param  = [];

        if (!empty($array[1])) {
            $this->parseUrlParams($array[1], $param);
        }

        return new CallbackDispatch($request, $this, [$class, $action], $param);
    }

    /**
     * �󶨵������ռ�
     * @access protected
     * @param  Request   $request
     * @param  string    $url URL��ַ
     * @param  string    $namespace �����ռ�
     * @return CallbackDispatch
     */
    protected function bindToNamespace(Request $request, string $url, string $namespace): CallbackDispatch
    {
        $array  = explode('|', $url, 3);
        $class  = !empty($array[0]) ? $array[0] : $this->router->config('default_controller');
        $method = !empty($array[1]) ? $array[1] : $this->router->config('default_action');
        $param  = [];

        if (!empty($array[2])) {
            $this->parseUrlParams($array[2], $param);
        }

        return new CallbackDispatch($request, $this, [$namespace . '\\' . App::parseName($class, 1), $method], $param);
    }

    /**
     * �󶨵�������
     * @access protected
     * @param  Request   $request
     * @param  string    $url URL��ַ
     * @param  string    $controller ��������
     * @return ControllerDispatch
     */
    protected function bindToController(Request $request, string $url, string $controller): ControllerDispatch
    {
        $array  = explode('|', $url, 2);
        $action = !empty($array[0]) ? $array[0] : $this->router->config('default_action');
        $param  = [];

        if (!empty($array[1])) {
            $this->parseUrlParams($array[1], $param);
        }

        return new ControllerDispatch($request, $this, $controller . '/' . $action, $param);
    }

}

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

use think\Exception;
use think\Request;
use think\Route;

/**
 * ·�ɹ�����
 */
class RuleItem extends Rule
{
    /**
     * �Ƿ�ΪMISS����
     * @var bool
     */
    protected $miss;

    /**
     * �ܹ�����
     * @access public
     * @param  Route             $router ·��ʵ��
     * @param  RuleGroup         $parent �ϼ�����
     * @param  string            $name ·�ɱ�ʶ
     * @param  string            $rule ·�ɹ���
     * @param  string|\Closure   $route ·�ɵ�ַ
     * @param  string            $method ��������
     */
    public function __construct(Route $router, RuleGroup $parent, string $name = null, string $rule = '', $route = null, string $method = '*')
    {
        $this->router = $router;
        $this->parent = $parent;
        $this->name   = $name;
        $this->route  = $route;
        $this->method = $method;

        $this->setRule($rule);

        $this->router->setRule($this->rule, $this);
    }

    /**
     * ���õ�ǰ·�ɹ���ΪMISS·��
     * @access public
     * @return void
     */
    public function setMiss(): void
    {
        $this->miss = true;
    }

    /**
     * �жϵ�ǰ·�ɹ����Ƿ�ΪMISS·��
     * @access public
     * @return bool
     */
    public function isMiss(): bool
    {
        return $this->miss ? true : false;
    }

    /**
     * ��ȡ��ǰ·�ɵ�URL��׺
     * @access public
     * @return string|null
     */
    public function getSuffix()
    {
        if (isset($this->option['ext'])) {
            $suffix = $this->option['ext'];
        } elseif ($this->parent->getOption('ext')) {
            $suffix = $this->parent->getOption('ext');
        } else {
            $suffix = null;
        }

        return $suffix;
    }

    /**
     * ·�ɹ���Ԥ����
     * @access public
     * @param  string      $rule     ·�ɹ���
     * @return void
     */
    public function setRule(string $rule): void
    {
        if ('$' == substr($rule, -1, 1)) {
            // �Ƿ�����ƥ��
            $rule = substr($rule, 0, -1);

            $this->option['complete_match'] = true;
        }

        $rule = '/' != $rule ? ltrim($rule, '/') : '';

        if ($this->parent && $prefix = $this->parent->getFullName()) {
            $rule = $prefix . ($rule ? '/' . ltrim($rule, '/') : '');
        }

        if (false !== strpos($rule, ':')) {
            $this->rule = preg_replace(['/\[\:(\w+)\]/', '/\:(\w+)/'], ['<\1?>', '<\1>'], $rule);
        } else {
            $this->rule = $rule;
        }

        // ����·�ɱ�ʶ�Ŀ�ݷ���
        $this->setRuleName();
    }

    /**
     * ���ñ���
     * @access public
     * @param  string     $name
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;
        $this->setRuleName(true);

        return $this;
    }

    /**
     * ����·�ɱ�ʶ ����URL��������
     * @access protected
     * @param  bool $first �Ƿ���뿪ͷ
     * @return void
     */
    protected function setRuleName(bool $first = false): void
    {
        if ($this->name) {
            $this->router->setName($this->name, $this, $first);
        }
    }

    /**
     * ���·��
     * @access public
     * @param  Request      $request  �������
     * @param  string       $url      ���ʵ�ַ
     * @param  array        $match    ƥ��·�ɱ���
     * @param  bool         $completeMatch   ·���Ƿ���ȫƥ��
     * @return Dispatch|false
     */
    public function checkRule(Request $request, string $url, $match = null, bool $completeMatch = false)
    {
        // ��������Ч��
        if (!$this->checkOption($this->option, $request)) {
            return false;
        }

        // �ϲ��������
        $option = $this->mergeGroupOptions();

        $url = $this->urlSuffixCheck($request, $url, $option);

        if (is_null($match)) {
            $match = $this->match($url, $option, $completeMatch);
        }

        if (false !== $match) {
            return $this->parseRule($request, $this->rule, $this->route, $url, $option, $match);
        }

        return false;
    }

    /**
     * ���·�ɣ���·��ƥ�䣩
     * @access public
     * @param  Request      $request  �������
     * @param  string       $url      ���ʵ�ַ
     * @param  bool         $completeMatch   ·���Ƿ���ȫƥ��
     * @return Dispatch|false
     */
    public function check(Request $request, string $url, bool $completeMatch = false)
    {
        return $this->checkRule($request, $url, null, $completeMatch);
    }

    /**
     * URL��׺��Slash���
     * @access protected
     * @param  Request      $request  �������
     * @param  string       $url      ���ʵ�ַ
     * @param  array        $option   ·�ɲ���
     * @return string
     */
    protected function urlSuffixCheck(Request $request, string $url, array $option = []): string
    {
        // �Ƿ����� / ��ַ����
        if (!empty($option['remove_slash']) && '/' != $this->rule) {
            $this->rule = rtrim($this->rule, '/');
            $url        = rtrim($url, '|');
        }

        if (isset($option['ext'])) {
            // ·��ext���� ������ϵͳ���õ�URLα��̬��׺����
            $url = preg_replace('/\.(' . $request->ext() . ')$/i', '', $url);
        }

        return $url;
    }

    /**
     * ���URL�͹���·���Ƿ�ƥ��
     * @access private
     * @param  string    $url URL��ַ
     * @param  array     $option    ·�ɲ���
     * @param  bool      $completeMatch   ·���Ƿ���ȫƥ��
     * @return array|false
     */
    private function match(string $url, array $option, bool $completeMatch)
    {
        if (isset($option['complete_match'])) {
            $completeMatch = $option['complete_match'];
        }

        $depr    = $this->router->config('pathinfo_depr');
        $pattern = array_merge($this->parent->getPattern(), $this->pattern);

        // �������������
        if (isset($pattern['__url__']) && !preg_match(0 === strpos($pattern['__url__'], '/') ? $pattern['__url__'] : '/^' . $pattern['__url__'] . '/', str_replace('|', $depr, $url))) {
            return false;
        }

        $var  = [];
        $url  = $depr . str_replace('|', $depr, $url);
        $rule = $depr . str_replace('/', $depr, $this->rule);

        if ($depr == $rule && $depr != $url) {
            return false;
        }

        if (false === strpos($rule, '<')) {
            if (0 === strcasecmp($rule, $url) || (!$completeMatch && 0 === strncasecmp($rule . $depr, $url . $depr, strlen($rule . $depr)))) {
                return $var;
            }
            return false;
        }

        $slash = preg_quote('/-' . $depr, '/');

        if ($matchRule = preg_split('/[' . $slash . ']?<\w+\??>/', $rule, 2)) {
            if ($matchRule[0] && 0 !== strncasecmp($rule, $url, strlen($matchRule[0]))) {
                return false;
            }
        }

        if (preg_match_all('/[' . $slash . ']?<?\w+\??>?/', $rule, $matches)) {
            $regex = $this->buildRuleRegex($rule, $matches[0], $pattern, $option, $completeMatch);

            try {
                if (!preg_match('/^' . $regex . ($completeMatch ? '$' : '') . '/u', $url, $match)) {
                    return false;
                }
            } catch (\Exception $e) {
                throw new Exception('route pattern error');
            }

            foreach ($match as $key => $val) {
                if (is_string($key)) {
                    $var[$key] = $val;
                }
            }
        }

        // �ɹ�ƥ��󷵻�URL�еĶ�̬��������
        return $var;
    }

    /**
     * ����·���������飨����ע��·�ɣ�
     * @access public
     * @param  string $name �������ƻ��߱�ʶ
     * @return $this
     */
    public function group(string $name)
    {
        $group = $this->router->getRuleName()->getGroup($name);

        if ($group) {
            $this->parent = $group;
            $this->setRule($this->rule);
        }

        return $this;
    }
}

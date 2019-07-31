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
use think\Exception;
use think\Request;
use think\Response;
use think\Route;
use think\route\dispatch\Response as ResponseDispatch;

/**
 * ·�ɷ�����
 */
class RuleGroup extends Rule
{
    /**
     * ����·�ɣ������ӷ��飩
     * @var array
     */
    protected $rules = [
        '*'       => [],
        'get'     => [],
        'post'    => [],
        'put'     => [],
        'patch'   => [],
        'delete'  => [],
        'head'    => [],
        'options' => [],
    ];

    /**
     * ����·�ɹ���
     * @var mixed
     */
    protected $rule;

    /**
     * MISS·��
     * @var RuleItem
     */
    protected $miss;

    /**
     * ��������
     * @var string
     */
    protected $fullName;

    /**
     * ��������
     * @var string
     */
    protected $domain;

    /**
     * �������
     * @var string
     */
    protected $alias;

    /**
     * �ܹ�����
     * @access public
     * @param  Route     $router ·�ɶ���
     * @param  RuleGroup $parent �ϼ�����
     * @param  string    $name   ��������
     * @param  mixed     $rule   ����·��
     */
    public function __construct(Route $router, RuleGroup $parent = null, string $name = '', $rule = null)
    {
        $this->router = $router;
        $this->parent = $parent;
        $this->rule   = $rule;
        $this->name   = trim($name, '/');

        $this->setFullName();

        if ($this->parent) {
            $this->domain = $this->parent->getDomain();
            $this->parent->addRuleItem($this);
        }

        if ($router->isTest()) {
            $this->lazy(false);
        }
    }

    /**
     * ���÷����·�ɹ���
     * @access public
     * @return void
     */
    protected function setFullName(): void
    {
        if (false !== strpos($this->name, ':')) {
            $this->name = preg_replace(['/\[\:(\w+)\]/', '/\:(\w+)/'], ['<\1?>', '<\1>'], $this->name);
        }

        if ($this->parent && $this->parent->getFullName()) {
            $this->fullName = $this->parent->getFullName() . ($this->name ? '/' . $this->name : '');
        } else {
            $this->fullName = $this->name;
        }

        if ($this->name) {
            $this->router->getRuleName()->setGroup($this->name, $this);
        }
    }

    /**
     * ��ȡ��������
     * @access public
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain ?: '-';
    }

    /**
     * ��ȡ�������
     * @access public
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias ?: '';
    }

    /**
     * ������·��
     * @access public
     * @param  Request $request       �������
     * @param  string  $url           ���ʵ�ַ
     * @param  bool    $completeMatch ·���Ƿ���ȫƥ��
     * @return Dispatch|false
     */
    public function check(Request $request, string $url, bool $completeMatch = false)
    {
        // ��������Ч��
        if (!$this->checkOption($this->option, $request) || !$this->checkUrl($url)) {
            return false;
        }

        // ��������·��
        if ($this instanceof Resource) {
            $this->buildResourceRule();
        } elseif ($this->rule instanceof Response) {
            return new ResponseDispatch($request, $this, $this->rule);
        } else {
            $this->parseGroupRule($this->rule);
        }

        // ��ȡ��ǰ·�ɹ���
        $method = strtolower($request->method());
        $rules  = $this->getMethodRules($method);

        if ($this->parent) {
            // �ϲ��������
            $this->mergeGroupOptions();
            // �ϲ������������
            $this->pattern = array_merge($this->parent->getPattern(), $this->pattern);
        }

        if (isset($this->option['complete_match'])) {
            $completeMatch = $this->option['complete_match'];
        }

        if (!empty($this->option['merge_rule_regex'])) {
            // �ϲ�·������������·��ƥ����
            $result = $this->checkMergeRuleRegex($request, $rules, $url, $completeMatch);

            if (false !== $result) {
                return $result;
            }
        }

        // ������·��
        foreach ($rules as $key => $item) {
            $result = $item->check($request, $url, $completeMatch);

            if (false !== $result) {
                return $result;
            }
        }

        if ($this->miss && in_array($this->miss->getMethod(), ['*', $method])) {
            // δƥ������·�ɵ�·�ɹ�����
            $result = $this->parseRule($request, '', $this->miss->getRoute(), $url, $this->miss->mergeGroupOptions());
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * ��ȡ��ǰ�����·�ɹ��򣨰����ӷ��顢��Դ·�ɣ�
     * @access protected
     * @param  string $method ��������
     * @return array
     */
    protected function getMethodRules(string $method): array
    {
        return array_merge($this->rules[$method], $this->rules['*']);
    }

    /**
     * ����URLƥ����
     * @access protected
     * @param  string $url URL
     * @return bool
     */
    protected function checkUrl(string $url): bool
    {
        if ($this->fullName) {
            $pos = strpos($this->fullName, '<');

            if (false !== $pos) {
                $str = substr($this->fullName, 0, $pos);
            } else {
                $str = $this->fullName;
            }

            if ($str && 0 !== stripos(str_replace('|', '/', $url), $str)) {
                return false;
            }
        }

        return true;
    }

    /**
     * ����·�ɷ������
     * @access public
     * @param  string $alias ·�ɷ������
     * @return $this
     */
    public function alias(string $alias)
    {
        $this->alias = $alias;
        $this->router->getRuleName()->setGroup($alias, $this);

        return $this;
    }

    /**
     * �ӳٽ��������·�ɹ���
     * @access public
     * @param  bool $lazy ·���Ƿ��ӳٽ���
     * @return $this
     */
    public function lazy(bool $lazy = true)
    {
        if (!$lazy) {
            $this->parseGroupRule($this->rule);
            $this->rule = null;
        }

        return $this;
    }

    /**
     * ���������������·�ɹ��򼰰�
     * @access public
     * @param  mixed $rule ·�ɹ���
     * @return void
     */
    public function parseGroupRule($rule): void
    {
        $origin = $this->router->getGroup();
        $this->router->setGroup($this);

        if ($rule instanceof \Closure) {
            Container::getInstance()->invokeFunction($rule);
        } elseif (is_string($rule) && $rule) {
            $this->router->bind($rule, $this->domain);
        }

        $this->router->setGroup($origin);
    }

    /**
     * ������·��
     * @access public
     * @param  Request $request       �������
     * @param  array   $rules         ·�ɹ���
     * @param  string  $url           ���ʵ�ַ
     * @param  bool    $completeMatch ·���Ƿ���ȫƥ��
     * @return Dispatch|false
     */
    protected function checkMergeRuleRegex(Request $request, array &$rules, string $url, bool $completeMatch)
    {
        $depr  = $this->router->config('pathinfo_depr');
        $url   = $depr . str_replace('|', $depr, $url);
        $regex = [];
        $items = [];

        foreach ($rules as $key => $item) {
            if ($item instanceof RuleItem) {
                $rule = $depr . str_replace('/', $depr, $item->getRule());
                if ($depr == $rule && $depr != $url) {
                    unset($rules[$key]);
                    continue;
                }

                $complete = $item->getOption('complete_match', $completeMatch);

                if (false === strpos($rule, '<')) {
                    if (0 === strcasecmp($rule, $url) || (!$complete && 0 === strncasecmp($rule, $url, strlen($rule)))) {
                        return $item->checkRule($request, $url, []);
                    }

                    unset($rules[$key]);
                    continue;
                }

                $slash = preg_quote('/-' . $depr, '/');

                if ($matchRule = preg_split('/[' . $slash . ']<\w+\??>/', $rule, 2)) {
                    if ($matchRule[0] && 0 !== strncasecmp($rule, $url, strlen($matchRule[0]))) {
                        unset($rules[$key]);
                        continue;
                    }
                }

                if (preg_match_all('/[' . $slash . ']?<?\w+\??>?/', $rule, $matches)) {
                    unset($rules[$key]);
                    $pattern = array_merge($this->getPattern(), $item->getPattern());
                    $option  = array_merge($this->getOption(), $item->getOption());

                    $regex[$key] = $this->buildRuleRegex($rule, $matches[0], $pattern, $option, $complete, '_THINK_' . $key);
                    $items[$key] = $item;
                }
            }
        }

        if (empty($regex)) {
            return false;
        }

        try {
            $result = preg_match('/^(?:' . implode('|', $regex) . ')/u', $url, $match);
        } catch (\Exception $e) {
            throw new Exception('route pattern error');
        }

        if ($result) {
            $var = [];
            foreach ($match as $key => $val) {
                if (is_string($key) && '' !== $val) {
                    list($name, $pos) = explode('_THINK_', $key);

                    $var[$name] = $val;
                }
            }

            if (!isset($pos)) {
                foreach ($regex as $key => $item) {
                    if (0 === strpos(str_replace(['\/', '\-', '\\' . $depr], ['/', '-', $depr], $item), $match[0])) {
                        $pos = $key;
                        break;
                    }
                }
            }

            $rule  = $items[$pos]->getRule();
            $array = $this->router->getRule($rule);

            foreach ($array as $item) {
                if (in_array($item->getMethod(), ['*', strtolower($request->method())])) {
                    $result = $item->checkRule($request, $url, $var);

                    if (false !== $result) {
                        return $result;
                    }
                }
            }
        }

        return false;
    }

    /**
     * ��ȡ�����MISS·��
     * @access public
     * @return RuleItem|null
     */
    public function getMissRule():  ? RuleItem
    {
        return $this->miss;
    }

    /**
     * ע��MISS·��
     * @access public
     * @param  string|Closure $route  ·�ɵ�ַ
     * @param  string         $method ��������
     * @return RuleItem
     */
    public function miss($route, string $method = '*') : RuleItem
    {
        // ����·�ɹ���ʵ��
        $ruleItem = new RuleItem($this->router, $this, null, '', $route, strtolower($method));

        $ruleItem->setMiss();
        $this->miss = $ruleItem;

        return $ruleItem;
    }

    /**
     * ��ӷ����µ�·�ɹ�������ӷ���
     * @access public
     * @param  string $rule   ·�ɹ���
     * @param  mixed  $route  ·�ɵ�ַ
     * @param  string $method ��������
     * @return RuleItem
     */
    public function addRule(string $rule, $route = null, string $method = '*'): RuleItem
    {
        // ��ȡ·�ɱ�ʶ
        if (is_string($route)) {
            $name = $route;
        } else {
            $name = null;
        }

        $method = strtolower($method);

        if ('' === $rule || '/' === $rule) {
            $rule .= '$';
        }

        // ����·�ɹ���ʵ��
        $ruleItem = new RuleItem($this->router, $this, $name, $rule, $route, $method);

        $this->addRuleItem($ruleItem, $method);

        return $ruleItem;
    }

    public function addRuleItem(Rule $rule, string $method = '*')
    {
        if (strpos($method, '|')) {
            $rule->method($method);
            $method = '*';
        }

        $this->rules[$method][] = $rule;

        return $this;
    }

    /**
     * ���÷����·��ǰ׺
     * @access public
     * @param  string $prefix ·��ǰ׺
     * @return $this
     */
    public function prefix(string $prefix)
    {
        if ($this->parent && $this->parent->getOption('prefix')) {
            $prefix = $this->parent->getOption('prefix') . $prefix;
        }

        return $this->setOption('prefix', $prefix);
    }

    /**
     * ������Դ����
     * @access public
     * @param  array $only ��Դ����
     * @return $this
     */
    public function only(array $only)
    {
        return $this->setOption('only', $only);
    }

    /**
     * ������Դ�ų�
     * @access public
     * @param  array $except �ų���Դ
     * @return $this
     */
    public function except(array $except)
    {
        return $this->setOption('except', $except);
    }

    /**
     * ������Դ·�ɵı���
     * @access public
     * @param  array $vars ��Դ����
     * @return $this
     */
    public function vars(array $vars)
    {
        return $this->setOption('var', $vars);
    }

    /**
     * �ϲ������·�ɹ�������
     * @access public
     * @param  bool $merge �Ƿ�ϲ�
     * @return $this
     */
    public function mergeRuleRegex(bool $merge = true)
    {
        return $this->setOption('merge_rule_regex', $merge);
    }

    /**
     * ��ȡ��������Name
     * @access public
     * @return string
     */
    public function getFullName():  ? string
    {
        return $this->fullName;
    }

    /**
     * ��ȡ�����·�ɹ���
     * @access public
     * @param  string $method ��������
     * @return array
     */
    public function getRules(string $method = '') : array
    {
        if ('' === $method) {
            return $this->rules;
        }

        return $this->rules[strtolower($method)] ?? [];
    }

    /**
     * ��շ����µ�·�ɹ���
     * @access public
     * @return void
     */
    public function clear(): void
    {
        $this->rules = [
            '*'       => [],
            'get'     => [],
            'post'    => [],
            'put'     => [],
            'patch'   => [],
            'delete'  => [],
            'head'    => [],
            'options' => [],
        ];
    }
}

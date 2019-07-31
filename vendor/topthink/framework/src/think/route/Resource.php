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

use think\Route;

/**
 * ��Դ·����
 */
class Resource extends RuleGroup
{
    /**
     * ��Դ·������
     * @var string
     */
    protected $resource;

    /**
     * ��Դ·�ɵ�ַ
     * @var string
     */
    protected $route;

    /**
     * REST��������
     * @var array
     */
    protected $rest = [];

    /**
     * �ܹ�����
     * @access public
     * @param  Route         $router     ·�ɶ���
     * @param  RuleGroup     $parent     �ϼ�����
     * @param  string        $name       ��Դ����
     * @param  string        $route      ·�ɵ�ַ
     * @param  array         $rest       ��Դ����
     */
    public function __construct(Route $router, RuleGroup $parent = null, string $name = '', string $route = '', array $rest = [])
    {
        $this->router   = $router;
        $this->parent   = $parent;
        $this->resource = $name;
        $this->route    = $route;
        $this->name     = strpos($name, '.') ? strstr($name, '.', true) : $name;

        $this->setFullName();

        // ��Դ·��Ĭ��Ϊ����ƥ��
        $this->option['complete_match'] = true;

        $this->rest = $rest;

        if ($this->parent) {
            $this->domain = $this->parent->getDomain();
            $this->parent->addRuleItem($this);
        }

        if ($router->isTest()) {
            $this->buildResourceRule();
        }
    }

    /**
     * ������Դ·�ɹ���
     * @access protected
     * @return void
     */
    protected function buildResourceRule(): void
    {
        $rule   = $this->resource;
        $option = $this->option;
        $origin = $this->router->getGroup();
        $this->router->setGroup($this);

        if (strpos($rule, '.')) {
            // ע��Ƕ����Դ·��
            $array = explode('.', $rule);
            $last  = array_pop($array);
            $item  = [];

            foreach ($array as $val) {
                $item[] = $val . '/<' . ($option['var'][$val] ?? $val . '_id') . '>';
            }

            $rule = implode('/', $item) . '/' . $last;
        }

        $prefix = substr($rule, strlen($this->name) + 1);

        // ע����Դ·��
        foreach ($this->rest as $key => $val) {
            if ((isset($option['only']) && !in_array($key, $option['only']))
                || (isset($option['except']) && in_array($key, $option['except']))) {
                continue;
            }

            if (isset($last) && strpos($val[1], '<id>') && isset($option['var'][$last])) {
                $val[1] = str_replace('<id>', '<' . $option['var'][$last] . '>', $val[1]);
            } elseif (strpos($val[1], '<id>') && isset($option['var'][$rule])) {
                $val[1] = str_replace('<id>', '<' . $option['var'][$rule] . '>', $val[1]);
            }

            $this->addRule(trim($prefix . $val[1], '/'), $this->route . '/' . $val[2], $val[0]);
        }

        $this->router->setGroup($origin);
    }

    /**
     * rest����������޸�
     * @access public
     * @param  array|string  $name ��������
     * @param  array|bool    $resource ��Դ
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

}

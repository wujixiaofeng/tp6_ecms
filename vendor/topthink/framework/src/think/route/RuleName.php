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

/**
 * ·�ɱ�ʶ������
 */
class RuleName
{
    /**
     * ·�ɱ�ʶ
     * @var array
     */
    protected $item = [];

    /**
     * ·�ɹ���
     * @var array
     */
    protected $rule = [];

    /**
     * ·�ɷ���
     * @var array
     */
    protected $group = [];

    /**
     * ע��·�ɱ�ʶ
     * @access public
     * @param  string   $name  ·�ɱ�ʶ
     * @param  RuleItem $ruleItem ·�ɹ���
     * @param  bool     $first �Ƿ�����
     * @return void
     */
    public function setName(string $name, RuleItem $ruleItem, bool $first = false): void
    {
        $name = strtolower($name);
        if ($first && isset($this->item[$name])) {
            array_unshift($this->item[$name], $ruleItem);
        } else {
            $this->item[$name][] = $ruleItem;
        }
    }

    /**
     * ע��·�ɷ����ʶ
     * @access public
     * @param  string    $name  ·�ɷ����ʶ
     * @param  RuleGroup $group ·�ɷ���
     * @return void
     */
    public function setGroup(string $name, RuleGroup $group): void
    {
        $this->group[strtolower($name)] = $group;
    }

    /**
     * ע��·�ɹ���
     * @access public
     * @param  string   $rule  ·�ɹ���
     * @param  RuleItem $ruleItem ·��
     * @return void
     */
    public function setRule(string $rule, RuleItem $ruleItem): void
    {
        $route = $ruleItem->getRoute();

        if ($route instanceof Closure) {
            $this->rule[$rule][] = $ruleItem;
        } else {
            $this->rule[$rule][$ruleItem->getRoute()] = $ruleItem;
        }
    }

    /**
     * ����·�ɹ����ȡ·�ɶ����б�
     * @access public
     * @param  string $rule   ·�ɱ�ʶ
     * @return RuleItem[]
     */
    public function getRule(string $rule): array
    {
        return $this->rule[$rule] ?? [];
    }

    /**
     * ����·�ɷ����ʶ��ȡ����
     * @access public
     * @param  string $name ·�ɷ����ʶ
     * @return RuleGroup|null
     */
    public function getGroup(string $name)
    {
        return $this->group[strtolower($name)] ?? null;
    }

    /**
     * ���·�ɹ���
     * @access public
     * @return void
     */
    public function clear(): void
    {
        $this->item = [];
        $this->rule = [];
    }

    /**
     * ��ȡȫ��·���б�
     * @access public
     * @return array
     */
    public function getRuleList(): array
    {
        $list = [];

        foreach ($this->rule as $rule => $rules) {
            foreach ($rules as $item) {
                $val = [];

                foreach (['method', 'rule', 'name', 'route', 'domain', 'pattern', 'option'] as $param) {
                    $call        = 'get' . $param;
                    $val[$param] = $item->$call();
                }

                if ($item->isMiss()) {
                    $val['rule'] .= '<MISS>';
                }

                $list[] = $val;
            }
        }

        return $list;
    }

    /**
     * ����·�ɱ�ʶ
     * @access public
     * @param  array $item ·�ɱ�ʶ
     * @return void
     */
    public function import(array $item): void
    {
        $this->item = $item;
    }

    /**
     * ����·�ɱ�ʶ��ȡ·����Ϣ������URL���ɣ�
     * @access public
     * @param  string $name   ·�ɱ�ʶ
     * @param  string $domain ����
     * @param  string $method ��������
     * @return array
     */
    public function getName(string $name = null, string $domain = null, string $method = '*'): array
    {
        if (is_null($name)) {
            return $this->item;
        }

        $name   = strtolower($name);
        $method = strtolower($method);
        $result = [];

        if (isset($this->item[$name])) {
            if (is_null($domain)) {
                $result = $this->item[$name];
            } else {
                foreach ($this->item[$name] as $item) {
                    $itemDomain = $item->getDomain();
                    $itemMethod = $item->getMethod();

                    if (($itemDomain == $domain || '-' == $itemDomain) && ('*' == $itemMethod || '*' == $method || $method == $itemMethod)) {
                        $result[] = $item;
                    }
                }
            }
        }

        return $result;
    }

}

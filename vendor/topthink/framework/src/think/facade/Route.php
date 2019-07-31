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
 * @see \think\Route
 * @mixin \think\Route
 * @method \think\route\Domain domain(mixed $name, mixed $rule = '', array $option = [], array $pattern = []) static ע������·��
 * @method \think\Route pattern(mixed $name, string $rule = '') static ע���������
 * @method \think\Route option(mixed $name, mixed $value = '') static ע��·�ɲ���
 * @method \think\Route bind(string $bind) static ����·�ɰ�
 * @method mixed config(string $name) static ��ȡ·������
 * @method array getBind() static ��ȡ·�ɰ�
 * @method mixed getDomainBind(string $domain) static ��ȡ·�ɰ�
 * @method \think\Route name(string $name) static ���õ�ǰ·�ɱ�ʶ
 * @method mixed getName(string $name) static ��ȡ·�ɱ�ʶ
 * @method void setName(string $name) static ��������·�ɱ�ʶ
 * @method void import(array $rules, string $type = '*') static ���������ļ���·�ɹ���
 * @method \think\route\RuleItem rule(string $rule, mixed $route, string $method = '*', array $option = [], array $pattern = []) static ע��·�ɹ���
 * @method void rules(array $rules, string $method = '*', array $option = [], array $pattern = []) static ����ע��·�ɹ���
 * @method \think\route\RuleGroup group(string|\Closure $name, mixed $route = null, string $method = '*', array $option = [], array $pattern = []) static ע��·�ɷ���
 * @method \think\route\RuleItem any(string $rule, mixed $route, array $option = [], array $pattern = []) static ע��·��
 * @method \think\route\RuleItem get(string $rule, mixed $route, array $option = [], array $pattern = []) static ע��·��
 * @method \think\route\RuleItem post(string $rule, mixed $route, array $option = [], array $pattern = []) static ע��·��
 * @method \think\route\RuleItem put(string $rule, mixed $route, array $option = [], array $pattern = []) static ע��·��
 * @method \think\route\RuleItem delete(string $rule, mixed $route, array $option = [], array $pattern = []) static ע��·��
 * @method \think\route\RuleItem patch(string $rule, mixed $route, array $option = [], array $pattern = []) static ע��·��
 * @method \think\route\Resource resource(string $rule, mixed $route, array $option = [], array $pattern = []) static ע����Դ·��
 * @method \think\Route rest(string $name, array $resource = []) static rest����������޸�
 * @method \think\route\RuleItem miss(string|\Closure $route, string $method = '*', array $option = []) static ע��δƥ��·�ɹ����Ĵ���
 * @method \think\route\Dispatch check(string $url, string $depr = '/', bool $must = false, bool $completeMatch = false) static ���URL·��
 * @method \think\route\Url buildUrl(string $url = '', array $vars = []) static URL����
 */
class Route extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'route';
    }
}

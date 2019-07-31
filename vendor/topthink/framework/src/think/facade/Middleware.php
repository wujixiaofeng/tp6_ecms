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
 * @see \think\Middleware
 * @mixin \think\Middleware
 * @method void import(array $middlewares = []) static ���������м��
 * @method void add(mixed $middleware) static ����м��������
 * @method void unshift(mixed $middleware) static ����м�������п�ͷ
 * @method array all() static ��ȡ�м������
 * @method \think\Response dispatch(\think\Request $request) static ִ���м������
 */
class Middleware extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'middleware';
    }
}

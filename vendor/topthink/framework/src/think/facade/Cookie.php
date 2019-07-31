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
 * @see \think\Cookie
 * @mixin \think\Cookie
 * @method void init(array $config = []) static ��ʼ��
 * @method mixed prefix(string $prefix = '') static ���û��߻�ȡcookie������ǰ׺��
 * @method mixed set(string $name, mixed $value = null, mixed $option = null) static ����Cookie
 * @method void forever(string $name, mixed $value = null, mixed $option = null) static ���ñ���Cookie����
 * @method void delete(string $name, string $prefix = null) static Cookieɾ��
 * @method void save() static д��Cookie����
 */
class Cookie extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'cookie';
    }
}

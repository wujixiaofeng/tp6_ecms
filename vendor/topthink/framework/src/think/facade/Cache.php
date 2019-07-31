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
 * @see \think\Cache
 * @mixin \think\Cache
 * @method \think\cache\Driver connect(array $options = [], mixed $name = false) static ���ӻ���
 * @method \think\cache\Driver init(array $options = [], bool $force = false) static ��ʼ������
 * @method \think\cache\Driver store(string $name = '', bool $force = false) static �л���������
 * @method bool has(string $name) static �жϻ����Ƿ����
 * @method mixed get(string $name, mixed $default = false) static ��ȡ����
 * @method mixed pull(string $name) static ��ȡ���沢ɾ��
 * @method mixed set(string $name, mixed $value, int $expire = null) static ���û���
 * @method mixed remember(string $name, mixed $value, int $expire = null) static �����������д�뻺��
 * @method mixed inc(string $name, int $step = 1) static �������棨�����ֵ���棩
 * @method mixed dec(string $name, int $step = 1) static �Լ����棨�����ֵ���棩
 * @method bool delete(string $name) static ɾ������
 * @method bool clear() static �������
 * @method \think\cache\TagSet tag(mixed $name) static �����ǩ
 * @method array getTagItems(string $name) static ��ȡ��ǩ�µĻ����ʶ
 * @method object handler() static ���ؾ�����󣬿�ִ�������߼�����
 */
class Cache extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'cache';
    }
}

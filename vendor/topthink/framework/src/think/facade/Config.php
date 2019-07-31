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
 * @see \think\Config
 * @mixin \think\Config
 * @method array load(string $file, string $name = '') static ���������ļ�
 * @method bool has(string $name) static ��������Ƿ����
 * @method mixed get(string $name,mixed $default = null) static ��ȡ���ò���
 * @method array set(array $config, string $name = null) static �����������ò���
 * @method array reset(string $name ='') static �������ò���
 * @method void remove(string $name = '') static �Ƴ�����
 * @method void setYaconf(mixed $yaconf) static ���ÿ���Yaconf ����ָ�������ļ���
 * @method mixed yaconf(string $name, mixed $default = null) static ��ȡyaconf����
 */
class Config extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'config';
    }
}

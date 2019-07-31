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
 * @see \think\View
 * @mixin \think\View
 * @method \think\View assign(mixed $name, mixed $value = null) static ģ�������ֵ
 * @method \think\View config(array $config ) static ����ģ������
 * @method \think\View exists(string $name) static ���ģ���Ƿ����
 * @method \think\View filter(Callable $filter) static ��ͼ���ݹ���
 * @method \think\View engine(string $type, array $options = []) static ����/�л���ǰģ�����������
 * @method string fetch(string $template = '') static �����ͻ�ȡģ������
 * @method string display(string $content = '') static ��Ⱦ�������
 */
class View extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'view';
    }
}

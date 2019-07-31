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
 * @see \think\Lang
 * @mixin \think\Lang
 * @method void setLangSet($range = '') static �趨��ǰ������
 * @method string getLangSet() static ��ȡ��ǰ������
 * @method array load(mixed $file, string $range = '') static �������Զ���
 * @method bool has(string $name, string $range = '') static ��ȡ���Զ���
 * @method mixed get(string $name = null, array $vars = [], string $range = '') static ��ȡ���Զ���
 * @method void detect() static �Զ�������û�ȡ����ѡ��
 * @method void saveToCookie(string $lang = null) static ���õ�ǰ���Ե�Cookie
 */
class Lang extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'lang';
    }
}

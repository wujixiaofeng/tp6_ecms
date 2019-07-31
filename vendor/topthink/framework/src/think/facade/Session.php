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
 * @see \think\Session
 * @mixin \think\Session
 * @method void init() static session��ʼ��
 * @method bool has(string $name) static �ж�session����
 * @method mixed get(string $name = '',mixed $default = null) static session��ȡ
 * @method mixed pull(string $name) static session��ȡ��ɾ��
 * @method void push(string $key, mixed $value) static ������ݵ�һ��session����
 * @method void set(string $name, mixed $value) static ����session����
 * @method void flash(string $name, mixed $value = null) static session���� ��һ��������Ч
 * @method void flush() static ��յ�ǰ�����session����
 * @method void delete(mixed $name) static ɾ��session����
 * @method void clear() static ���session����
 * @method void start() static ����session
 * @method void destroy() static ����session
 * @method void setId() static ����session_id
 * @method string getId(bool $regenerate = true) static ��ȡsession_id
 * @method void regenerate(bool $delete = false) static ��������session_id
 */
class Session extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'session';
    }
}
